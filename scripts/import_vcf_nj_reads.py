"""Load Jim's 22 Sep NJ coord CSVs, deletion sample lists, and the LA SRA table.

Analyzed = VCF sample AND QC PASS AND sample-list ID AND, when that row has an
SRA file, the ID is in column 1 of that file. Sample-list lines drop the suffix
-bbmap-sorted-D-0-sorted-D_min_0-unique.csv.

Simple coord names are {base}{size}_coord.csv. Names with an extra number, and
*_coord1.csv, are skipped. Size 28190 is stored for the N-gene filter and is
not an NJ row. India files use India_6000_all_ (Drive), not the sheet hyphen.
"""
from __future__ import annotations

import csv
import sys
import zipfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_snv_groups import mysql_run, parse_connection_php, sql_str

ROOT = Path(__file__).resolve().parents[1]
SCHEMA_SQL = ROOT / "sql" / "vcf_snv_nj.sql"
DRIVE = ROOT / "_incoming" / "jim-kelley" / "2026-09-22_nj-reads-many-projects" / "drive"
CATALOG = ROOT / "_incoming" / "jim-kelley" / "2026-09-23_nj-on-junction-page" / "files" / "NJ-table_no_types.csv"
# (nj_size, coord_tag) rows the catalog accepts. Empty means tests accept simple files only.
ALLOWED: set[tuple[int, str]] = set()
LIST_SUFFIX = "-bbmap-sorted-D-0-sorted-D_min_0-unique.csv"
NGENE_SIZE = 28190

# Longest base first when matching filenames.
NJ_BASES = [
    ("Argentina-uniq-all_", "PRJEB46220-Argentina"),
    ("SAfr_miseq_0325_", "S_Afr-PRJNA636748"),
    ("India_6000_all_", "India-6000"),
    ("India-6000_all_", "India-6000"),
    ("UK_nextseq500_", "nextseq_500"),
    ("UK_nextseq550_", "nextseq_550"),
    ("UK_hiseq2500_", "illumina_hiseq_2500"),
    ("Thailand_mix_", "Thailand_mix"),
    ("Estonia_mix_", "Est-mix"),
    ("India-miseq_", "India-miseq"),
    ("Port_miseq_", "Port_miseq"),
    ("Broad_6000_", "PRJNA622837-Broad_Inst"),
    ("Port_0-12_", "Port-PRJEB47340"),
    ("Ang_miseq_", "Angola_miseq"),
    ("Botswana_", "Botswana"),
    ("PAK-iseq_", "PAK_iseq"),
    ("UK_miseq_", "illumina_miseq"),
    ("LA_all_", "LA-PRJNA815364"),
    ("NJ_all_", "NJ-PRJNA708324"),
]

LIST_FILES = {
    "Ang_miseq_unique_csv.txt": "Angola_miseq",
    "Argentina_all_unique_csv.txt": "PRJEB46220-Argentina",
    "Botswana_unique_csv.txt": "Botswana",
    "Broad_unique_csv.txt": "PRJNA622837-Broad_Inst",
    "Est_mix_unique_csv.txt": "Est-mix",
    "India-miseq_unique_csv.txt": "India-miseq",
    "India_6000_all_unique_csv.txt": "India-6000",
    "LA_all_unique_csv.txt": "LA-PRJNA815364",
    "NJ_unique_csv.txt": "NJ-PRJNA708324",
    "Pak_iseq_unique_csv.txt": "PAK_iseq",
    "Port_0-12_unique_csv.txt": "Port-PRJEB47340",
    "Port_miseq_unique_csv.txt": "Port_miseq",
    "SAfr_unique_csv.txt": "S_Afr-PRJNA636748",
    "Thailand_mix_unique_csv.txt": "Thailand_mix",
    "UK_hiseq2500_unique_csv.txt": "illumina_hiseq_2500",
    "UK_miseq_unique_csv.txt": "illumina_miseq",
    "UK_nextseq500_unique_csv.txt": "nextseq_500",
    "UK_nextseq550_unique_csv.txt": "nextseq_550",
}

SRA_BY_CODE = {
    "LA-PRJNA815364": "SraRunTable-PRJNA815364-LA_103024-NextSeq500.csv",
}


def sample_id_from_list_line(line: str) -> str:
    text = line.strip()
    if text.endswith(LIST_SUFFIX):
        return text[: -len(LIST_SUFFIX)]
    return text


def parse_coord_name(filename: str) -> tuple[str, int, str] | None:
    """Return (group code, size, coord tag). Tag is '' or the start on a duplicate size."""
    name = Path(filename.replace("\\", "/")).name
    if name.endswith("_coord1.csv"):
        return None
    for base, code in NJ_BASES:
        if not name.startswith(base) or not name.endswith("_coord.csv"):
            continue
        middle = name[len(base) : -len("_coord.csv")]
        if middle.isdigit():
            size, tag = int(middle), ""
        elif "_" in middle:
            size_text, tag = middle.split("_", 1)
            if not size_text.isdigit() or not tag.isdigit():
                return None
            size = int(size_text)
        else:
            return None
        if ALLOWED and (size, tag) not in ALLOWED:
            return None
        if not ALLOWED and tag != "":
            return None
        return code, size, tag
    return None


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
    proc = subprocess.run(cmd, capture_output=True, env=env)
    if proc.returncode != 0:
        raise SystemExit(proc.stderr.decode("utf-8", errors="replace"))
    return [line.split("\t") for line in proc.stdout.decode("utf-8", errors="replace").splitlines() if line.strip()]


def find_files(root: Path, names: set[str] | None = None) -> list[Path]:
    if not root.is_dir():
        return []
    out = []
    for path in root.rglob("*"):
        if path.is_file() and (names is None or path.name in names):
            out.append(path)
    return out


def tagged_start_pairs(nj_root: Path) -> set[tuple[int, int]]:
    found: set[tuple[int, int]] = set()
    if not nj_root.is_dir():
        return found
    for path in nj_root.rglob("*_coord.csv"):
        name = path.name
        for base, _code in NJ_BASES:
            if not name.startswith(base):
                continue
            middle = name[len(base) : -len("_coord.csv")]
            if "_" not in middle:
                break
            size_text, start_text = middle.split("_", 1)
            if size_text.isdigit() and start_text.isdigit():
                found.add((int(size_text), int(start_text)))
            break
    return found


def load_catalog_rows(nj_root: Path) -> list[tuple[int, int | None, int | None, str, str]]:
    """Return size, start, end, coord_tag, kind. Duplicate sizes use the start as the tag."""
    raw: list[tuple[int, int | None, int | None]] = []
    if CATALOG.is_file():
        with CATALOG.open(newline="", encoding="utf-8-sig", errors="replace") as fh:
            reader = csv.DictReader(fh)
            for row in reader:
                size = int(row["Size"])
                start = int(row["NJ Start"]) if row.get("NJ Start") else None
                end = int(row["NJ End"]) if row.get("NJ End") else None
                raw.append((size, start, end))
    tagged = tagged_start_pairs(nj_root)
    counts: dict[int, int] = {}
    for size, _start, _end in raw:
        counts[size] = counts.get(size, 0) + 1
    rows: list[tuple[int, int | None, int | None, str, str]] = []
    for size, start, end in raw:
        tag = ""
        if counts[size] > 1 and start is not None and (size, start) in tagged:
            tag = str(start)
        rows.append((size, start, end, tag, "nj"))
    if not any(size == NGENE_SIZE and tag == "" for size, _, _, tag, _ in rows):
        rows.append((NGENE_SIZE, None, None, "", "cj"))
    return rows


def iter_coord_rows(nj_root: Path):
    zips = [p for p in nj_root.rglob("*.zip")] if nj_root.is_dir() else []
    loose = [p for p in find_files(nj_root) if p.suffix.lower() == ".csv"] if nj_root.is_dir() else []

    def handle(name: str, text: str):
        parsed = parse_coord_name(name)
        if parsed is None:
            return
        code, size, tag = parsed
        for line in text.splitlines():
            line = line.strip()
            if line == "" or line.lower().startswith("sample"):
                continue
            parts = line.split(",") if "," in line else line.split("\t")
            if len(parts) < 2:
                continue
            sample = parts[0].strip()
            try:
                reads = int(float(parts[1].strip()))
            except ValueError:
                continue
            if sample == "":
                continue
            yield code, sample, size, tag, reads

    for path in loose:
        yield from handle(path.name, path.read_text(encoding="utf-8", errors="replace"))
    for zip_path in zips:
        with zipfile.ZipFile(zip_path) as zf:
            for info in zf.infolist():
                if info.is_dir() or not info.filename.lower().endswith(".csv"):
                    continue
                raw = zf.read(info).decode("utf-8", errors="replace")
                yield from handle(info.filename, raw)


def chunked(rows, size: int):
    batch = []
    for row in rows:
        batch.append(row)
        if len(batch) >= size:
            yield batch
            batch = []
    if batch:
        yield batch


def insert_values(table: str, columns: str, tuples: list[str], cfg: dict) -> None:
    if not tuples:
        return
    sql = "INSERT IGNORE INTO " + table + " (" + columns + ") VALUES " + ",".join(tuples)
    mysql_run(sql, cfg)


def ensure_coord_tag(cfg: dict) -> None:
    if not mysql_pairs("SHOW COLUMNS FROM vcf_snv_nj_catalog LIKE 'coord_tag'", cfg):
        mysql_run(
            "ALTER TABLE vcf_snv_nj_catalog ADD COLUMN coord_tag varchar(16) NOT NULL DEFAULT '' AFTER nj_end",
            cfg,
        )
        mysql_run(
            "ALTER TABLE vcf_snv_nj_catalog DROP PRIMARY KEY, ADD PRIMARY KEY (nj_size, coord_tag)",
            cfg,
        )
    if not mysql_pairs("SHOW COLUMNS FROM vcf_snv_nj_read LIKE 'coord_tag'", cfg):
        mysql_run(
            "ALTER TABLE vcf_snv_nj_read ADD COLUMN coord_tag varchar(16) NOT NULL DEFAULT '' AFTER nj_size",
            cfg,
        )
        mysql_run(
            "ALTER TABLE vcf_snv_nj_read DROP PRIMARY KEY, "
            "ADD PRIMARY KEY (group_id, nj_size, coord_tag, sample_name)",
            cfg,
        )


def main() -> None:
    global ALLOWED
    cfg = parse_connection_php()
    mysql_run(SCHEMA_SQL.read_text(encoding="utf-8"), cfg)
    ensure_coord_tag(cfg)
    groups = {code: int(gid) for gid, code in mysql_pairs("SELECT id, code FROM vcf_snv_group", cfg)}

    mysql_run(
        "DELETE FROM vcf_snv_nj_read; DELETE FROM vcf_snv_nj_sample_list; "
        "DELETE FROM vcf_snv_sra_sample; DELETE FROM vcf_snv_nj_catalog;",
        cfg,
    )

    nj_root = DRIVE / "nj"
    catalog_sql = []
    ALLOWED = set()
    for size, start, end, tag, kind in load_catalog_rows(nj_root):
        ALLOWED.add((size, tag))
        catalog_sql.append(
            "("
            + str(size)
            + ","
            + ("NULL" if start is None else str(start))
            + ","
            + ("NULL" if end is None else str(end))
            + ","
            + sql_str(tag)
            + ","
            + sql_str(kind)
            + ")"
        )
    insert_values("vcf_snv_nj_catalog", "nj_size,nj_start,nj_end,coord_tag,kind", catalog_sql, cfg)

    list_root = DRIVE / "sample_lists"
    loaded_lists: dict[str, int] = {}
    skipped_lists = []
    for path in find_files(list_root):
        code = LIST_FILES.get(path.name)
        if code is None:
            continue
        if code not in groups:
            skipped_lists.append(code)
            continue
        gid = groups[code]
        ids = []
        for line in path.read_text(encoding="utf-8", errors="replace").splitlines():
            sample = sample_id_from_list_line(line)
            if sample == "":
                continue
            ids.append("(" + str(gid) + "," + sql_str(sample) + ")")
        for batch in chunked(ids, 500):
            insert_values("vcf_snv_nj_sample_list", "group_id,sample_name", batch, cfg)
        loaded_lists[code] = len(ids)
        print(f"list {code} {len(ids)}")

    sra_root = DRIVE / "sra"
    loaded_sra = []
    for code, filename in SRA_BY_CODE.items():
        matches = [p for p in find_files(sra_root, {filename})]
        if not matches or code not in groups:
            print("missing SRA or group", code)
            continue
        gid = groups[code]
        ids = []
        with matches[0].open(newline="", encoding="utf-8", errors="replace") as fh:
            for row in csv.reader(fh):
                if not row:
                    continue
                sample = row[0].strip()
                if sample == "" or sample.lower() == "run":
                    continue
                ids.append("(" + str(gid) + "," + sql_str(sample) + ")")
        for batch in chunked(ids, 500):
            insert_values("vcf_snv_sra_sample", "group_id,sample_name", batch, cfg)
        loaded_sra.append((code, len(ids)))
        print(f"sra {code} {len(ids)}")

    read_counts: dict[str, int] = {}
    skipped_reads = set()
    pending: list[str] = []
    if nj_root.is_dir() and any(nj_root.rglob("*")):
        for code, sample, size, tag, reads in iter_coord_rows(nj_root):
            if code not in groups:
                skipped_reads.add(code)
                continue
            gid = groups[code]
            pending.append(
                "("
                + str(gid)
                + ","
                + sql_str(sample)
                + ","
                + str(size)
                + ","
                + sql_str(tag)
                + ","
                + str(max(0, reads))
                + ")"
            )
            read_counts[code] = read_counts.get(code, 0) + 1
            if len(pending) >= 400:
                insert_values(
                    "vcf_snv_nj_read",
                    "group_id,sample_name,nj_size,coord_tag,read_count",
                    pending,
                    cfg,
                )
                pending = []
        if pending:
            insert_values(
                "vcf_snv_nj_read",
                "group_id,sample_name,nj_size,coord_tag,read_count",
                pending,
                cfg,
            )
    else:
        print("no NJ coord files under", nj_root)

    for code, n in sorted(read_counts.items()):
        print(f"reads {code} {n}")
    if skipped_reads:
        print("skipped read groups (no vcf_snv_group row):", ", ".join(sorted(skipped_reads)))
    if skipped_lists:
        print("skipped lists:", ", ".join(sorted(set(skipped_lists))))

    for code in loaded_lists:
        gid = groups[code]
        sql = (
            "UPDATE vcf_snv_sample_eligibility e "
            "JOIN vcf_snv_sample s ON s.id = e.sample_id "
            "LEFT JOIN vcf_snv_nj_sample_list l "
            "  ON l.group_id = e.group_id AND l.sample_name = s.sample_name "
            "SET e.eligible = CASE "
            "  WHEN e.qc = 'PASS' AND l.sample_name IS NOT NULL AND ("
            "    NOT EXISTS (SELECT 1 FROM vcf_snv_sra_sample a WHERE a.group_id = e.group_id) "
            "    OR EXISTS (SELECT 1 FROM vcf_snv_sra_sample a "
            "               WHERE a.group_id = e.group_id AND a.sample_name = s.sample_name)"
            "  ) THEN 1 ELSE 0 END, "
            "e.exclusion_reason = CASE "
            "  WHEN e.qc IS NULL OR e.qc = '' THEN 'unmatched' "
            "  WHEN e.qc <> 'PASS' THEN 'fail_qc' "
            "  WHEN l.sample_name IS NULL THEN 'no_sample_list' "
            "  WHEN EXISTS (SELECT 1 FROM vcf_snv_sra_sample a WHERE a.group_id = e.group_id) "
            "   AND NOT EXISTS (SELECT 1 FROM vcf_snv_sra_sample a "
            "                   WHERE a.group_id = e.group_id AND a.sample_name = s.sample_name) "
            "  THEN 'not_in_sra' "
            "  ELSE NULL END "
            "WHERE e.group_id = " + str(gid)
        )
        mysql_run(sql, cfg)
        print("eligibility updated", code)


if __name__ == "__main__":
    main()
