"""Join Jim's Sep 17 run_metadata CSVs to imported VCF samples.

Eligibility: QC == PASS AND the sample exists in vcf_snv_sample for that group.
Match by sample ID, not project-name presence. Broad uses PRJNA622837.
Portugal uses PRJEB47340. Argentina uses PRJEB46220.
Does not apply to John's Original mutations table.

Variant/primer labels are stored for later filters; they are not applied here.
"""
from __future__ import annotations

import argparse
import csv
import io
import sys
import zipfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_snv_groups import mysql_run, parse_connection_php, sql_str

ROOT = Path(__file__).resolve().parents[1]
SCHEMA_SQL = ROOT / "sql" / "vcf_snv_sample_meta.sql"
PACKET = ROOT / "_incoming" / "jim-kelley" / "2026-09-17_snv-variant-primer-data" / "files"
EXTRA_META_DIR = ROOT / "_incoming" / "jim-kelley" / "2026-09-23_nj-on-junction-page" / "files"
META_ZIP = PACKET / "Variant_primer_data-20260917T202015Z-1-001.zip"

# Groups with imported VCFs and a matching metadata file.
GROUP_PROJECT = {
    "PAK_iseq": "PRJNA764553",
    "India-6000": "PRJNA625669",
    "LA-PRJNA815364": "PRJNA815364",
    "LA-PRJNA815364_nextseq500": "PRJNA815364",
    "NJ-PRJNA708324": "PRJNA708324",
    "illumina_miseq": "PRJEB37886",
    "nextseq_500": "PRJEB37886",
    "nextseq_550": "PRJEB37886",
    "S_Afr-PRJNA636748": "PRJNA636748",
    "Port-PRJEB47340": "PRJEB47340",
    "PRJNA622837-Broad_Inst": "PRJNA622837",
    "PRJEB46220-Argentina": "PRJEB46220",
    "India-miseq": "PRJNA625669",
    "Port_miseq": "PRJEB47340",
    "Angola_miseq": "PRJNA717113",
    "Botswana": "PRJNA782796",
    "Slovakia_miseq": "PRJEB45305",
    "Thailand_mix": "Thailand_mix",
    "NM": "PRJNA656534",
    "VA": "PRJNA625551",
    "Austr-PRJNA613958_nextseq500": "PRJNA613958",
    "Austr-PRJNA613958_nextseq550": "PRJNA613958",
}

EXTRA_META_FILES = {
    "run_metadata.v05-PRJNA782796.csv": "PRJNA782796",
    "run_metadata_Thailand_mix.csv": "Thailand_mix",
}
ANGOLA_META_CSV = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-28_angola-primer-project"
    / "files"
    / "run_metadata.v05-PRJNA717113.csv"
)
SLOVAKIA_META_CSV = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-24_junction-filter-merges"
    / "drive"
    / "Variant_primer_data-20260924T200228Z-1-001.zip"
)

ISOLATED_GROUPS = ()
SKIP_PROJECTS = set()

DISPLAY_LABELS = {
    "NJ-PRJNA708324": "USA NJ PRJNA708324",
    "PRJNA622837-Broad_Inst": "USA NE, NJ PRJNA622837",
    "LA-PRJNA815364": "USA LA MiSeq PRJNA815364",
    "LA-PRJNA815364_nextseq500": "USA LA NextSeq 500 PRJNA815364",
    "PRJEB46220-Argentina": "Argentina PRJEB46220",
    "Angola_miseq": "Angola MiSeq PRJNA717113",
    "Botswana": "Botswana MiSeq PRJNA782796",
    "Slovakia_miseq": "Slovakia MiSeq PRJEB45305",
    "NM": "USA NM MiSeq PRJNA656534",
    "VA": "USA VA MiSeq PRJNA625551",
    "Austr-PRJNA613958_nextseq500": "Australia NextSeq 500 PRJNA613958",
    "Austr-PRJNA613958_nextseq550": "Australia NextSeq 550 PRJNA613958",
}

SEP24_META_FILES = [
    ("Variant_primer_data/run_metadata.v05-PRJNA656534.csv", "PRJNA656534"),
    ("Variant_primer_data/run_metadata.v05-PRJNA625551.csv", "PRJNA625551"),
    ("Variant_primer_data/run_metadata.v05-PRJNA613958.csv", "PRJNA613958"),
]


def decide_eligibility(qc: str | None, has_vcf: bool) -> tuple[bool, str | None]:
    if not has_vcf:
        return False, "no_vcf"
    if qc is None or qc == "":
        return False, "unmatched"
    if qc != "PASS":
        return False, "fail_qc"
    return True, None


def mysql_pairs(sql: str, cfg: dict) -> list[list[str]]:
    import os
    import subprocess
    from import_vcf_snv_groups import MYSQL_EXE

    exe = MYSQL_EXE if MYSQL_EXE.is_file() else Path("mysql")
    cmd = [
        str(exe),
        "-h",
        str(cfg["host"]),
        "-P",
        str(cfg["port"]),
        "-u",
        str(cfg["user"]),
        "-N",
        "-B",
        str(cfg["dbname"]),
        "-e",
        sql,
    ]
    env = os.environ.copy()
    env["MYSQL_PWD"] = str(cfg["password"])
    proc = subprocess.run(cmd, capture_output=True, text=True, env=env)
    if proc.returncode != 0:
        raise SystemExit(proc.stderr)
    return [line.split("\t") for line in proc.stdout.splitlines() if line.strip()]


def load_vcf_samples(cfg: dict) -> dict[str, list[tuple[int, int, str]]]:
    """sample_name -> [(sample_id, group_id, group_code), ...] for eligible groups."""
    codes = ",".join("'" + c.replace("'", "''") + "'" for c in GROUP_PROJECT)
    rows = mysql_pairs(
        "SELECT s.id, s.group_id, g.code, s.sample_name "
        "FROM vcf_snv_sample s JOIN vcf_snv_group g ON g.id=s.group_id "
        "WHERE g.code IN (" + codes + ")",
        cfg,
    )
    by_name: dict[str, list[tuple[int, int, str]]] = {}
    for sid, gid, code, name in rows:
        by_name.setdefault(name, []).append((int(sid), int(gid), code))
    return by_name


def absorb_metadata_rows(hits: dict[str, dict[str, str]], reader, project: str, source: str, wanted_names: set[str]) -> None:
    for row in reader:
        if len(row) < 4:
            continue
        sid = row[0].strip()
        if sid not in wanted_names or sid in hits:
            continue
        hits[sid] = {
            "project": project,
            "variant": row[1].strip(),
            "primer": row[2].strip(),
            "qc": row[3].strip(),
            "source": source,
        }


def stream_metadata(wanted_names: set[str]) -> dict[str, dict[str, str]]:
    """sample_name -> {project, variant, primer, qc, source} for IDs we have VCFs for."""
    hits: dict[str, dict[str, str]] = {}
    with zipfile.ZipFile(META_ZIP) as zf:
        for inner in zf.namelist():
            if not inner.lower().endswith(".csv"):
                continue
            source = Path(inner).name
            project = EXTRA_META_FILES.get(source, Path(inner).stem.replace("run_metadata.v05-", ""))
            if project in SKIP_PROJECTS:
                continue
            with zf.open(inner) as fh:
                reader = csv.reader(io.TextIOWrapper(fh, encoding="utf-8", errors="replace"))
                absorb_metadata_rows(hits, reader, project, source, wanted_names)
    if EXTRA_META_DIR.is_dir():
        for path in EXTRA_META_DIR.glob("*.csv"):
            project = EXTRA_META_FILES.get(path.name)
            if project is None or project in SKIP_PROJECTS:
                continue
            with path.open(newline="", encoding="utf-8", errors="replace") as fh:
                absorb_metadata_rows(hits, csv.reader(fh), project, path.name, wanted_names)
    if ANGOLA_META_CSV.is_file():
        with ANGOLA_META_CSV.open(newline="", encoding="utf-8", errors="replace") as fh:
            absorb_metadata_rows(
                hits,
                csv.reader(fh),
                "PRJNA717113",
                ANGOLA_META_CSV.name,
                wanted_names,
            )
    if SLOVAKIA_META_CSV.is_file():
        with zipfile.ZipFile(SLOVAKIA_META_CSV) as zf:
            extra = [("Variant_primer_data/run_metadata.v05-PRJEB45305.csv", "PRJEB45305")] + SEP24_META_FILES
            for inner, project in extra:
                if inner not in zf.namelist():
                    continue
                with zf.open(inner) as fh:
                    absorb_metadata_rows(
                        hits,
                        csv.reader(io.TextIOWrapper(fh, encoding="utf-8", errors="replace")),
                        project,
                        Path(inner).name,
                        wanted_names,
                    )
    return hits


def build_rows(
    vcf_by_name: dict[str, list[tuple[int, int, str]]],
    meta_by_name: dict[str, dict[str, str]],
) -> tuple[list[str], list[str], dict[str, dict[str, int]]]:
    meta_sql: list[str] = []
    elig_sql: list[str] = []
    stats: dict[str, dict[str, int]] = {}

    def bucket(code: str) -> dict[str, int]:
        if code not in stats:
            stats[code] = {
                "vcf": 0,
                "in_meta": 0,
                "pass": 0,
                "unmatched": 0,
                "fail_qc": 0,
                "eligible": 0,
            }
        return stats[code]

    for name, copies in vcf_by_name.items():
        meta = meta_by_name.get(name)
        for sample_id, group_id, code in copies:
            expected_project = GROUP_PROJECT[code]
            qc = None
            if meta is not None and meta["project"] == expected_project:
                qc = meta["qc"]
                b = bucket(code)
                b["vcf"] += 1
                b["in_meta"] += 1
                meta_sql.append(
                    "("
                    + ",".join(
                        [
                            str(sample_id),
                            str(group_id),
                            sql_str(meta["project"]),
                            sql_str(name),
                            sql_str(meta["variant"]),
                            sql_str(meta["primer"]),
                            sql_str(qc),
                            sql_str(meta["source"]),
                        ]
                    )
                    + ")"
                )
            else:
                b = bucket(code)
                b["vcf"] += 1
            eligible, reason = decide_eligibility(qc, True)
            if eligible:
                b["eligible"] += 1
                b["pass"] += 1
            elif reason == "unmatched":
                b["unmatched"] += 1
            elif reason == "fail_qc":
                b["fail_qc"] += 1
            elig_sql.append(
                "("
                + ",".join(
                    [
                        str(sample_id),
                        str(group_id),
                        "1",
                        "NULL" if qc is None else sql_str(qc),
                        "1" if eligible else "0",
                        "NULL" if reason is None else sql_str(reason),
                    ]
                )
                + ")"
            )
    return meta_sql, elig_sql, stats


def apply_display_labels(cfg: dict) -> None:
    parts = []
    for code, label in DISPLAY_LABELS.items():
        parts.append(
            "UPDATE vcf_snv_group SET label="
            + sql_str(label)
            + " WHERE code="
            + sql_str(code)
            + ";"
        )
    if parts:
        mysql_run(" ".join(parts), cfg)


def ensure_argentina_group(cfg: dict) -> None:
    mysql_run(
        "INSERT IGNORE INTO vcf_snv_group "
        "(code, label, sample_count, call_count, source_note) VALUES ("
        + sql_str("PRJEB46220-Argentina")
        + ","
        + sql_str("Argentina PRJEB46220")
        + ",0,0,"
        + sql_str("Metadata CSV only; VCF ZIP not in 17 Sep archive")
        + ");",
        cfg,
    )


def apply_rows(cfg: dict, meta_sql: list[str], elig_sql: list[str], codes: list[str] | None = None) -> None:
    mysql_run(SCHEMA_SQL.read_text(encoding="utf-8"), cfg)
    scope = codes if codes else list(GROUP_PROJECT)
    mysql_run(
        "DELETE e FROM vcf_snv_sample_eligibility e "
        "JOIN vcf_snv_group g ON g.id=e.group_id "
        "WHERE g.code IN ("
        + ",".join(sql_str(c) for c in scope)
        + "); "
        "DELETE m FROM vcf_snv_sample_meta m "
        "JOIN vcf_snv_group g ON g.id=m.group_id "
        "WHERE g.code IN ("
        + ",".join(sql_str(c) for c in scope)
        + ");",
        cfg,
    )
    batch = 400
    for i in range(0, len(meta_sql), batch):
        chunk = meta_sql[i : i + batch]
        mysql_run(
            "INSERT INTO vcf_snv_sample_meta "
            "(sample_id,group_id,project_accession,sample_name,variant_label,primer_label,qc,source_file) VALUES "
            + ",".join(chunk)
            + ";",
            cfg,
        )
    for i in range(0, len(elig_sql), batch):
        chunk = elig_sql[i : i + batch]
        mysql_run(
            "INSERT INTO vcf_snv_sample_eligibility "
            "(sample_id,group_id,has_vcf,qc,eligible,exclusion_reason) VALUES "
            + ",".join(chunk)
            + ";",
            cfg,
        )


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--only", default="", help="Rewrite metadata for these group codes only")
    args = parser.parse_args()
    only = [part.strip() for part in args.only.split(",") if part.strip()]
    unknown = [code for code in only if code not in GROUP_PROJECT]
    if unknown:
        raise SystemExit("Unknown group " + ", ".join(unknown))
    if not META_ZIP.is_file():
        raise SystemExit("Missing " + str(META_ZIP))
    cfg = parse_connection_php()
    vcf_by_name = load_vcf_samples(cfg)
    if only:
        wanted_codes = set(only)
        filtered: dict[str, list[tuple[int, int, str]]] = {}
        for name, copies in vcf_by_name.items():
            kept = [row for row in copies if row[2] in wanted_codes]
            if kept:
                filtered[name] = kept
        vcf_by_name = filtered
    wanted = set(vcf_by_name)
    print("VCF sample IDs in eligible groups:", len(wanted))
    meta_by_name = stream_metadata(wanted)
    print("Metadata hits for those IDs:", len(meta_by_name))
    meta_sql, elig_sql, stats = build_rows(vcf_by_name, meta_by_name)
    if ISOLATED_GROUPS:
        print("Isolated (no PASS and VCF import):", ", ".join(ISOLATED_GROUPS))
    for code in sorted(stats):
        s = stats[code]
        print(
            f"{code}: vcf={s['vcf']} in_meta={s['in_meta']} PASS={s['pass']} "
            f"eligible={s['eligible']} unmatched={s['unmatched']} fail_qc={s['fail_qc']}"
        )
    if args.dry_run:
        print("dry-run: no database writes")
        return
    apply_rows(cfg, meta_sql, elig_sql, only or None)
    ensure_argentina_group(cfg)
    apply_display_labels(cfg)
    print("wrote", len(meta_sql), "meta rows and", len(elig_sql), "eligibility rows")


if __name__ == "__main__":
    main()
