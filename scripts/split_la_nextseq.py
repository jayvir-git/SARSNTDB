#!/usr/bin/env python3
"""Move NextSeq 500 samples out of the mixed LA VCF group.

The loaded zip is labeled MiSeq, but most of its runs are on the NextSeq 500
SRA. This creates LA-PRJNA815364_nextseq500, moves those samples (calls, primer
metadata, eligibility, NJ reads, and the Early list), and leaves the unmatched
samples on the hidden original group.
"""
from __future__ import annotations

import csv
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_early_samples import EARLY_BY_CODE, EARLY_ZIP, read_early_zip
from import_vcf_snv_groups import mysql_run, parse_connection_php, sql_str

ROOT = Path(__file__).resolve().parents[1]
OLD = "LA-PRJNA815364"
NEW = "LA-PRJNA815364_nextseq500"
LABEL = "USA LA NextSeq 500 PRJNA815364"
SRA = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-22_nj-reads-many-projects"
    / "drive"
    / "sra"
    / "SRA_run_tables"
    / "SraRunTable-PRJNA815364-LA_103024-NextSeq500.csv"
)
SQL_OUT = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-30_la-nextseq-pca"
    / "deploy"
    / "la_nextseq_split.sql"
)

MOVE_TABLES = (
    "vcf_snv_call",
    "vcf_snv_sample_meta",
    "vcf_snv_sample_eligibility",
)


def sra_ids(path: Path) -> list[str]:
    ids = []
    seen = set()
    with path.open(newline="", encoding="utf-8", errors="replace") as fh:
        for row in csv.reader(fh):
            if not row:
                continue
            sample = row[0].strip()
            if sample == "" or sample.lower() == "run" or sample in seen:
                continue
            seen.add(sample)
            ids.append(sample)
    return ids


def values(names: list[str]) -> str:
    return ",\n".join("(" + sql_str(name) + ")" for name in names)


def union_names(names: list[str]) -> str:
    return "\nUNION ALL\n".join("SELECT " + sql_str(name) + " AS sample_name" for name in names)


def build_sql(nextseq: list[str], early: list[str]) -> str:
    moves = []
    for table in MOVE_TABLES:
        moves.append(
            f"""UPDATE {table} t
JOIN vcf_snv_sample s ON s.id = t.sample_id
JOIN vcf_snv_group src ON src.id = s.group_id AND src.code = {sql_str(OLD)}
JOIN vcf_snv_deploy_la_nextseq n ON n.sample_name = s.sample_name
JOIN vcf_snv_group dest ON dest.code = {sql_str(NEW)}
SET t.group_id = dest.id;"""
        )
    for table in ("vcf_snv_nj_read", "vcf_snv_nj_sample_list", "vcf_snv_sra_sample"):
        moves.append(
            f"""UPDATE {table} t
JOIN vcf_snv_group src ON src.id = t.group_id AND src.code = {sql_str(OLD)}
JOIN vcf_snv_deploy_la_nextseq n ON n.sample_name = t.sample_name
JOIN vcf_snv_group dest ON dest.code = {sql_str(NEW)}
SET t.group_id = dest.id;"""
        )
    return f"""SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP TABLE IF EXISTS vcf_snv_deploy_la_nextseq;
CREATE TABLE vcf_snv_deploy_la_nextseq (
  sample_name varchar(128) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO vcf_snv_deploy_la_nextseq (sample_name) VALUES
{values(nextseq)};
INSERT INTO vcf_snv_group (code, label, sample_count, call_count, source_note)
SELECT {sql_str(NEW)}, {sql_str(LABEL)}, 0, 0,
       'NextSeq 500 runs moved out of the mixed LA zip'
WHERE NOT EXISTS (SELECT 1 FROM vcf_snv_group WHERE code = {sql_str(NEW)});
UPDATE vcf_snv_group
   SET label = {sql_str(LABEL)}
 WHERE code = {sql_str(NEW)};
{chr(10).join(moves)}
UPDATE vcf_snv_sample s
JOIN vcf_snv_group src ON src.id = s.group_id AND src.code = {sql_str(OLD)}
JOIN vcf_snv_deploy_la_nextseq n ON n.sample_name = s.sample_name
JOIN vcf_snv_group dest ON dest.code = {sql_str(NEW)}
SET s.group_id = dest.id;
DELETE e FROM vcf_snv_early_sample e
JOIN vcf_snv_group g ON g.id = e.group_id
WHERE g.code IN ({sql_str(OLD)}, {sql_str(NEW)});
INSERT INTO vcf_snv_early_sample (group_id, sample_name)
SELECT g.id, names.sample_name
  FROM vcf_snv_group g
  JOIN (
{union_names(early)}
  ) names
 WHERE g.code = {sql_str(NEW)};
UPDATE vcf_snv_group g
   SET sample_count = (SELECT COUNT(*) FROM vcf_snv_sample s WHERE s.group_id = g.id),
       call_count = (SELECT COUNT(*) FROM vcf_snv_call c WHERE c.group_id = g.id)
 WHERE g.code IN ({sql_str(OLD)}, {sql_str(NEW)});
DROP TABLE IF EXISTS vcf_snv_deploy_la_nextseq;
SELECT code, label, sample_count, call_count
  FROM vcf_snv_group
 WHERE code IN ({sql_str(OLD)}, {sql_str(NEW)});
"""


def main() -> None:
    if not SRA.is_file():
        raise SystemExit("missing NextSeq SRA: " + str(SRA))
    nextseq = sra_ids(SRA)
    early_name = EARLY_BY_CODE[NEW]
    early_by_file = read_early_zip(EARLY_ZIP)
    early = early_by_file.get(early_name, [])
    if not nextseq or not early:
        raise SystemExit(f"empty lists nextseq={len(nextseq)} early={len(early)}")
    sql = build_sql(nextseq, early)
    SQL_OUT.parent.mkdir(parents=True, exist_ok=True)
    SQL_OUT.write_text(sql, encoding="utf-8")
    print(f"nextseq runs {len(nextseq)}")
    print(f"early ids {len(early)}")
    print("wrote", SQL_OUT)
    if "--write-only" in sys.argv:
        return
    cfg = parse_connection_php(ROOT / "connection.php")
    mysql_run(sql, cfg)
    print("applied")


if __name__ == "__main__":
    main()
