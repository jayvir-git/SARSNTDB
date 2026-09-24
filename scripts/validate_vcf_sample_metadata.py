"""Validate Jim's Sep 17 variant/primer CSVs against imported VCF samples.

Does not import or change denominators. Skip the 2.5M UK file unless --include-uk.
"""
from __future__ import annotations

import argparse
import csv
import io
import re
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CONN = ROOT / "connection.php"
MYSQL = Path(r"C:\xampp\mysql\bin\mysql.exe")
PACKET = ROOT / "_incoming" / "jim-kelley" / "2026-09-17_snv-variant-primer-data" / "files"
META_ZIP = PACKET / "Variant_primer_data-20260917T202015Z-1-001.zip"

PROJECT_TO_VCF = {
    "PRJNA764553": ["PAK_iseq"],
    "PRJNA625669": ["India-6000"],
    "PRJNA815364": ["LA-PRJNA815364"],
    "PRJNA708324": ["NJ-PRJNA708324"],
    "PRJNA622837": ["PRJNA622837-Broad_Inst"],
    "PRJEB37886": ["illumina_miseq", "nextseq_500", "nextseq_550"],
}


def creds():
    text = CONN.read_text(encoding="utf-8", errors="replace")

    def grab(name: str) -> str:
        m = re.search(r"\$" + name + r'\s*=\s*"([^"]*)"', text)
        return m.group(1) if m else ""

    return grab("user"), grab("password"), grab("dbname"), grab("host")


def mysql_pairs(sql: str) -> list[list[str]]:
    user, pw, db, host = creds()
    r = subprocess.run(
        [str(MYSQL), "-h", host, "-u", user, f"-p{pw}", "-N", "-B", db, "-e", sql],
        capture_output=True,
        text=True,
    )
    if r.returncode != 0:
        raise SystemExit(r.stderr)
    return [line.split("\t") for line in r.stdout.splitlines() if line.strip()]


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--include-uk", action="store_true")
    args = parser.parse_args()

    print("Portugal workbook names run_metadata.v05-PRJNA622837.csv (Broad).")
    print("Archive has run_metadata.v05-PRJEB47340.csv for Portugal PRJEB47340.")
    print("Do not map Portugal samples from the Broad metadata file.")
    print("Workbook Broad # Analyzed = 2303; local VCF group n = 2437.")
    print()

    by_group: dict[str, set[str]] = {}
    for code, name in mysql_pairs(
        "SELECT g.code, s.sample_name FROM vcf_snv_sample s JOIN vcf_snv_group g ON g.id=s.group_id"
    ):
        by_group.setdefault(code, set()).add(name)

    with zipfile.ZipFile(META_ZIP) as zf:
        for inner in zf.namelist():
            if not inner.lower().endswith(".csv"):
                continue
            project = Path(inner).stem.replace("run_metadata.v05-", "")
            if project == "PRJEB37886" and not args.include_uk:
                print("skip PRJEB37886 (use --include-uk)")
                continue
            with zf.open(inner) as fh:
                rows = list(csv.reader(io.TextIOWrapper(fh, encoding="utf-8", errors="replace")))
            ids: list[str] = []
            qc_counts: dict[str, int] = {}
            variant_counts: dict[str, int] = {}
            primer_counts: dict[str, int] = {}
            seen: dict[str, int] = {}
            for r in rows:
                if len(r) < 4:
                    continue
                sid = r[0].strip()
                if not sid:
                    continue
                ids.append(sid)
                seen[sid] = seen.get(sid, 0) + 1
                variant_counts[r[1].strip()] = variant_counts.get(r[1].strip(), 0) + 1
                primer_counts[r[2].strip()] = primer_counts.get(r[2].strip(), 0) + 1
                qc_counts[r[3].strip()] = qc_counts.get(r[3].strip(), 0) + 1
            unique = set(ids)
            dups = sorted(k for k, n in seen.items() if n > 1)
            pass_ids = {r[0].strip() for r in rows if len(r) >= 4 and r[3].strip() == "PASS"}
            print(f"{project}: rows={len(ids)} unique={len(unique)} dup_ids={len(dups)} PASS={len(pass_ids)}")
            print(f"  QC={dict(sorted(qc_counts.items()))}")
            print(f"  primers={dict(sorted(primer_counts.items(), key=lambda kv: (-kv[1], kv[0]))[:12])}")
            print(f"  variants={len(variant_counts)} labels")
            if project == "PRJEB47340":
                print("  Portugal metadata file is PRJEB47340; workbook names PRJNA622837 (Broad).")
                print("  No imported VCF group is mapped to Portugal.")
            codes = PROJECT_TO_VCF.get(project, [])
            if not codes:
                print("  no imported VCF group mapped for this project (do not import from this check)")
            for code in codes:
                samples = by_group.get(code, set())
                print(
                    f"  {code}: vcf={len(samples)} in_meta={len(samples & unique)} "
                    f"PASS_in_vcf={len(samples & pass_ids)} vcf_not_in_meta={len(samples - unique)}"
                )


if __name__ == "__main__":
    main()
