"""Load Jim's 2020-2021 sample lists into vcf_snv_early_sample.

A listed ID is Early: it is on the group sample list and has no identified
variant in the variant-primer file. Groups with no file stay at zero.
"""
from __future__ import annotations

import sys
import zipfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_snv_groups import mysql_run, parse_connection_php, sql_str

ROOT = Path(__file__).resolve().parents[1]
SCHEMA_SQL = ROOT / "sql" / "vcf_snv_early_sample.sql"
EARLY_ZIP = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-28_early-column-filter-bugs"
    / "files"
    / "2020-2021_samples-20260928T201703Z-1-001.zip"
)
EARLY_DIR = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-29_pair-columns-and-csv"
    / "files"
)

# Sheet column "2020-2021 file". NA rows are omitted, so their count stays 0.
EARLY_BY_CODE = {
    "Angola_miseq": "Angola_miseq-2020-Jul21.txt",
    "S_Afr-PRJNA636748": "SAfr_miseq-2020-Jul21.txt",
    "India-6000": "India_6000_2020-2021.txt",
    "Port_miseq": "Port_miseq_2020-2021.txt",
    "Port-PRJEB47340": "Port_550-2020-Jul21.txt",
    "illumina_miseq": "UK_miseq_2020-Jul21.txt",
    "illumina_hiseq_2500": "UK_hiseq2500_2020-Jul21.txt",
    "nextseq_500": "UK_nextseq500_2020-2021.txt",
    "nextseq_550": "UK_nextseq550_2020-Jul21.txt",
    "PRJNA622837-Broad_Inst": "Broad-2020-Jul21.txt",
    "NJ-PRJNA708324": "NJ_miseq_2020-Jul21.txt",
    "PRJEB46220-Argentina": "Argentina-2020-Jul21.txt",
    "Slovakia_miseq": "Slovakia_2020-2021.txt",
    "NM": "NM-2020-Jul21.txt",
    "VA": "VA-2020-Jul21.txt",
    "Austr-PRJNA613958_nextseq500": "Austr-PRJNA613958_nextseq_500_2020_samples.txt",
    "Austr-PRJNA613958_nextseq550": "Austr-PRJNA613958_nextseq_550_2020_samples.txt",
    "Russia682735": "Russia682735_hiseq_2500.txt",
    "LA-PRJNA815364_nextseq500": "LA_nextseq500-2020-Jul21.txt",
}


def ids_in_text(text: str) -> list[str]:
    out = []
    seen = set()
    for line in text.splitlines():
        sample = line.strip()
        if sample == "" or sample.startswith("#") or sample in seen:
            continue
        seen.add(sample)
        out.append(sample)
    return out


def read_early_zip(path: Path) -> dict[str, list[str]]:
    by_name: dict[str, list[str]] = {}
    with zipfile.ZipFile(path) as zf:
        for info in zf.infolist():
            if info.is_dir():
                continue
            name = Path(info.filename).name
            text = zf.read(info).decode("utf-8", errors="replace")
            by_name[name] = ids_in_text(text)
    return by_name


def main() -> None:
    if not EARLY_ZIP.is_file():
        raise SystemExit("Missing " + str(EARLY_ZIP))
    lists = read_early_zip(EARLY_ZIP)
    if EARLY_DIR.is_dir():
        for path in EARLY_DIR.glob("*.txt"):
            lists[path.name] = ids_in_text(path.read_text(encoding="utf-8", errors="replace"))
    cfg = parse_connection_php()
    mysql_run(SCHEMA_SQL.read_text(encoding="utf-8"), cfg)
    from import_vcf_nj_reads import mysql_pairs

    groups = {code: int(gid) for gid, code in mysql_pairs("SELECT id, code FROM vcf_snv_group", cfg)}
    for code, filename in EARLY_BY_CODE.items():
        if code not in groups:
            print("skip", code, "no group row")
            continue
        ids = lists.get(filename)
        if not ids:
            print("skip", code, "missing", filename)
            continue
        gid = groups[code]
        mysql_run("DELETE FROM vcf_snv_early_sample WHERE group_id=" + str(gid), cfg)
        tuples = ["(" + str(gid) + "," + sql_str(sample) + ")" for sample in ids]
        for i in range(0, len(tuples), 500):
            mysql_run(
                "INSERT INTO vcf_snv_early_sample (group_id, sample_name) VALUES " + ",".join(tuples[i : i + 500]),
                cfg,
            )
        print(f"{code} {len(ids)}")


if __name__ == "__main__":
    main()
