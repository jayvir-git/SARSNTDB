#!/usr/bin/env python3
"""Load the LA MiSeq SRA and the 107-sample MiSeq VCF zip into LA-PRJNA815364.

The NextSeq 500 runs already sit on LA-PRJNA815364_nextseq500. Every remaining
sample in the original group is on this MiSeq SRA. The small zip is the rest
of the MiSeq VCFs and was never imported.
"""
from __future__ import annotations

import csv
import io
import sys
import zipfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_sample_metadata import stream_metadata
from import_vcf_snv_groups import (
    mysql_run,
    parse_connection_php,
    parse_vcf_lines,
    sample_name_from_filename,
    sql_str,
)

ROOT = Path(__file__).resolve().parents[1]
CODE = "LA-PRJNA815364"
LABEL = "USA LA MiSeq PRJNA815364"
SRA = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-30_la-miseq-sra"
    / "files"
    / "SraRunTable-PRJNA815364-LA_103024-MiSeq.csv"
)
OUTER = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-24_junction-filter-merges"
    / "drive"
    / "SNVs-20260924T194736Z-1-001.zip"
)
SQL_OUT = ROOT / "_incoming" / "jim-kelley" / "2026-09-30_la-miseq-sra" / "deploy" / "la_miseq.sql"


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


def small_zip_vcfs() -> dict[str, list[tuple[int, str, str, float]]]:
    parsed: dict[str, list[tuple[int, str, str, float]]] = {}
    with zipfile.ZipFile(OUTER) as outer:
        inner_name = next(name for name in outer.namelist() if name.endswith("LA_miseq-bwa-pad.zip"))
        payload = outer.read(inner_name)
    with zipfile.ZipFile(io.BytesIO(payload)) as zf:
        for info in zf.infolist():
            if info.is_dir() or not info.filename.lower().endswith(".vcf"):
                continue
            sample = sample_name_from_filename(info.filename)
            text = io.TextIOWrapper(zf.open(info), encoding="utf-8", errors="replace").read().splitlines()
            calls = list(parse_vcf_lines(text))
            parsed[sample] = calls
    return parsed


def union_select(rows: list[str]) -> str:
    return "\nUNION ALL\n".join(rows)


def values(names: list[str]) -> str:
    return ",\n".join("(" + sql_str(name) + ")" for name in names)


def batched(rows: list[str], size: int) -> list[list[str]]:
    return [rows[i : i + size] for i in range(0, len(rows), size)]


def build_sql(sra: list[str], vcfs: dict[str, list[tuple[int, str, str, float]]], meta: dict[str, dict[str, str]]) -> str:
    new_names = sorted(vcfs)
    call_rows = []
    for sample, calls in vcfs.items():
        for coord, ref, alt, af in calls:
            call_rows.append(
                "("
                + ",".join([sql_str(sample), str(int(coord)), sql_str(ref), sql_str(alt), repr(float(af))])
                + ")"
            )
    meta_rows = []
    for sample in new_names:
        row = meta.get(sample)
        if row is None or row.get("project") != "PRJNA815364":
            continue
        meta_rows.append(
            "("
            + ",".join(
                [
                    sql_str(sample),
                    sql_str(row["variant"]),
                    sql_str(row["primer"]),
                    sql_str(row["qc"]),
                    sql_str(row["source"]),
                ]
            )
            + ")"
        )
    call_sql = []
    for chunk in batched(call_rows, 400):
        call_sql.append(
            "INSERT IGNORE INTO vcf_snv_call "
            "(group_id,sample_id,coordinate,reference,alternate,allele_frequency)\n"
            "SELECT g.id, s.id, c.coordinate, c.reference, c.alternate, c.allele_frequency\n"
            "FROM vcf_snv_group g\n"
            "JOIN vcf_snv_sample s ON s.group_id = g.id\n"
            "JOIN (\n"
            "  SELECT sample_name, coordinate, reference, alternate, allele_frequency FROM (\n"
            "    SELECT NULL AS sample_name, NULL AS coordinate, NULL AS reference, NULL AS alternate, NULL AS allele_frequency WHERE 1 = 0\n"
            "  ) empty\n"
            "  UNION ALL\n"
            "  SELECT * FROM (\n"
            "    VALUES\n"
            + ",\n".join(chunk)
            + "\n  ) AS listed(sample_name, coordinate, reference, alternate, allele_frequency)\n"
            ") c ON c.sample_name = s.sample_name\n"
            "WHERE g.code = " + sql_str(CODE) + ";"
        )
    # MariaDB 10.4 has no VALUES row constructor. Use UNION ALL instead.
    call_sql = []
    for chunk in batched(call_rows, 200):
        selects = []
        for sample, calls in []:
            pass
        parts = []
        for raw in chunk:
            # raw is (name,coord,ref,alt,af)
            inner = raw[1:-1]
            name, coord, ref, alt, af = [piece.strip() for piece in split_sql_tuple(inner)]
            parts.append(
                "SELECT "
                + name
                + " AS sample_name, "
                + coord
                + " AS coordinate, "
                + ref
                + " AS reference, "
                + alt
                + " AS alternate, "
                + af
                + " AS allele_frequency"
            )
        call_sql.append(
            "INSERT IGNORE INTO vcf_snv_call "
            "(group_id,sample_id,coordinate,reference,alternate,allele_frequency)\n"
            "SELECT g.id, s.id, c.coordinate, c.reference, c.alternate, c.allele_frequency\n"
            "FROM vcf_snv_group g\n"
            "JOIN (\n"
            + "\nUNION ALL\n".join(parts)
            + "\n) c\n"
            "JOIN vcf_snv_sample s ON s.group_id = g.id AND s.sample_name = c.sample_name\n"
            "WHERE g.code = "
            + sql_str(CODE)
            + ";"
        )
    meta_sql = ""
    if meta_rows:
        meta_parts = []
        for raw in meta_rows:
            inner = raw[1:-1]
            name, variant, primer, qc, source = [piece.strip() for piece in split_sql_tuple(inner)]
            meta_parts.append(
                "SELECT "
                + name
                + " AS sample_name, "
                + variant
                + " AS variant_label, "
                + primer
                + " AS primer_label, "
                + qc
                + " AS qc, "
                + source
                + " AS source_file"
            )
        meta_sql = (
            "INSERT INTO vcf_snv_sample_meta "
            "(sample_id,group_id,project_accession,sample_name,variant_label,primer_label,qc,source_file)\n"
            "SELECT s.id, s.group_id, 'PRJNA815364', c.sample_name, c.variant_label, c.primer_label, c.qc, c.source_file\n"
            "FROM vcf_snv_group g\n"
            "JOIN (\n"
            + "\nUNION ALL\n".join(meta_parts)
            + "\n) c\n"
            "JOIN vcf_snv_sample s ON s.group_id = g.id AND s.sample_name = c.sample_name\n"
            "LEFT JOIN vcf_snv_sample_meta m ON m.sample_id = s.id\n"
            "WHERE g.code = "
            + sql_str(CODE)
            + " AND m.sample_id IS NULL;"
        )
    name_union = "\nUNION ALL\n".join("SELECT " + sql_str(name) + " AS sample_name" for name in new_names)
    return f"""SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
UPDATE vcf_snv_group SET label = {sql_str(LABEL)} WHERE code = {sql_str(CODE)};
DROP TABLE IF EXISTS vcf_snv_deploy_la_miseq;
CREATE TABLE vcf_snv_deploy_la_miseq (
  sample_name varchar(128) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO vcf_snv_deploy_la_miseq (sample_name) VALUES
{values(sra)};
DELETE a FROM vcf_snv_sra_sample a
JOIN vcf_snv_group g ON g.id = a.group_id AND g.code = {sql_str(CODE)};
INSERT INTO vcf_snv_sra_sample (group_id, sample_name)
SELECT g.id, n.sample_name
  FROM vcf_snv_group g
  JOIN vcf_snv_deploy_la_miseq n
 WHERE g.code = {sql_str(CODE)};
INSERT INTO vcf_snv_sample (group_id, sample_name)
SELECT g.id, names.sample_name
  FROM vcf_snv_group g
  JOIN (
{name_union}
  ) names
  LEFT JOIN vcf_snv_sample s ON s.group_id = g.id AND s.sample_name = names.sample_name
 WHERE g.code = {sql_str(CODE)} AND s.id IS NULL;
INSERT IGNORE INTO vcf_snv_nj_sample_list (group_id, sample_name)
SELECT g.id, names.sample_name
  FROM vcf_snv_group g
  JOIN (
{name_union}
  ) names
 WHERE g.code = {sql_str(CODE)};
{chr(10).join(call_sql)}
{meta_sql}
INSERT INTO vcf_snv_sample_eligibility (sample_id, group_id, has_vcf, qc, eligible, exclusion_reason)
SELECT s.id, s.group_id, 1, m.qc, 0, NULL
  FROM vcf_snv_sample s
  JOIN vcf_snv_group g ON g.id = s.group_id AND g.code = {sql_str(CODE)}
  LEFT JOIN vcf_snv_sample_meta m ON m.sample_id = s.id
  LEFT JOIN vcf_snv_sample_eligibility e ON e.sample_id = s.id
 WHERE e.sample_id IS NULL;
UPDATE vcf_snv_sample_eligibility e
JOIN vcf_snv_sample s ON s.id = e.sample_id
JOIN vcf_snv_group g ON g.id = e.group_id AND g.code = {sql_str(CODE)}
LEFT JOIN vcf_snv_nj_sample_list l ON l.group_id = e.group_id AND l.sample_name = s.sample_name
SET e.eligible = CASE
      WHEN e.qc = 'PASS' AND l.sample_name IS NOT NULL AND (
        NOT EXISTS (SELECT 1 FROM vcf_snv_sra_sample a WHERE a.group_id = e.group_id)
        OR EXISTS (SELECT 1 FROM vcf_snv_sra_sample a WHERE a.group_id = e.group_id AND a.sample_name = s.sample_name)
      ) THEN 1 ELSE 0 END,
    e.exclusion_reason = CASE
      WHEN e.qc IS NULL OR e.qc = '' THEN 'unmatched'
      WHEN e.qc <> 'PASS' THEN 'fail_qc'
      WHEN l.sample_name IS NULL THEN 'no_sample_list'
      WHEN EXISTS (SELECT 1 FROM vcf_snv_sra_sample a WHERE a.group_id = e.group_id)
       AND NOT EXISTS (SELECT 1 FROM vcf_snv_sra_sample a WHERE a.group_id = e.group_id AND a.sample_name = s.sample_name)
      THEN 'not_in_sra'
      ELSE NULL END;
UPDATE vcf_snv_group g
   SET sample_count = (SELECT COUNT(*) FROM vcf_snv_sample s WHERE s.group_id = g.id),
       call_count = (SELECT COUNT(*) FROM vcf_snv_call c WHERE c.group_id = g.id)
 WHERE g.code = {sql_str(CODE)};
DROP TABLE IF EXISTS vcf_snv_deploy_la_miseq;
"""


def split_sql_tuple(inner: str) -> list[str]:
    parts = []
    current = []
    quote = False
    for ch in inner:
        if ch == "'" and (not current or current[-1] != "\\"):
            quote = not quote
            current.append(ch)
            continue
        if ch == "," and not quote:
            parts.append("".join(current))
            current = []
            continue
        current.append(ch)
    parts.append("".join(current))
    return parts


def main() -> None:
    sra = sra_ids(SRA)
    vcfs = small_zip_vcfs()
    meta = stream_metadata(set(vcfs))
    pass_n = sum(1 for name in vcfs if meta.get(name, {}).get("qc") == "PASS" and meta.get(name, {}).get("project") == "PRJNA815364")
    fail_n = sum(1 for name in vcfs if meta.get(name, {}).get("qc") not in (None, "", "PASS") and meta.get(name, {}).get("project") == "PRJNA815364")
    missing = sum(1 for name in vcfs if name not in meta or meta[name].get("project") != "PRJNA815364")
    calls = sum(len(rows) for rows in vcfs.values())
    print(f"miseq sra {len(sra)}")
    print(f"new vcfs {len(vcfs)} calls {calls}")
    print(f"new PASS {pass_n} FAIL_QC {fail_n} no primer row {missing}")
    sql = build_sql(sra, vcfs, meta)
    SQL_OUT.parent.mkdir(parents=True, exist_ok=True)
    SQL_OUT.write_text(sql, encoding="utf-8")
    print("wrote", SQL_OUT, "bytes", SQL_OUT.stat().st_size)
    if "--write-only" in sys.argv:
        return
    cfg = parse_connection_php(ROOT / "connection.php")
    mysql_run(sql, cfg)
    print("applied")


if __name__ == "__main__":
    main()
