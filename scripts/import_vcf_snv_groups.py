"""Load Jim Kelley's GROM pad-bwa VCF SNVs into vcf_snv_* tables.

Jim (2026-09-16): skip ## lines; POS is padded by 6000; take REF, ALT, and
FORMAT AF. Combine ZIP stems that map to the same group code (NJ two zips;
Jim 2026-09-18: extra names to the right of vcf folder(s) also merge). Duplicate
sample IDs keep the first copy. Optional --list-file keeps only named samples.

Does not parse xlsx in PHP. Does not replace John's mutations table.
"""
from __future__ import annotations

import argparse
import io
import os
import re
import subprocess
import zipfile
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PACKET_FILES = ROOT / "_incoming" / "jim-kelley" / "2026-09-16_vcf-snv-groups" / "files"
SEP17_SNV_ZIP = (
    ROOT
    / "_incoming"
    / "jim-kelley"
    / "2026-09-17_snv-variant-primer-data"
    / "files"
    / "SNVs-20260917T202033Z-1-001.zip"
)
SCHEMA_SQL = ROOT / "sql" / "vcf_snv_groups.sql"
DROP_SQL = ROOT / "sql" / "vcf_snv_groups_drop.sql"
CONNECTION_PHP = ROOT / "connection.php"
MYSQL_EXE = Path(r"C:\xampp\mysql\bin\mysql.exe")

PAD = 6000
GENOME_MAX = 29903
BASES = set("ACGT")
SAMPLE_NAME_RE = re.compile(r"^(?P<name>.+)_pad-bwa\.SNV\.vcf$", re.IGNORECASE)

# First stem is kept when the same sample ID appears in a later ZIP.
MERGE_GROUPS = {
    "NJ-PRJNA708324": (
        "NJ-PRJNA708324-bwa-pad",
        "NJ-PRJNA708324-5-bwa-pad",
    ),
    "S_Afr-PRJNA636748": (
        "S_Afr-PRJNA636748",
        "S_Afr-PRJNA636748-1023-1024-bwa-pad",
    ),
    "India-6000": (
        "India-6000-bwa-pad",
        "India-6000-new-bwa-pad",
    ),
    "Port-PRJEB47340": (
        "Port-bwa-pad-0-8",
        "Port_8-10-bwa-pad",
        "Port_11-12-bwa-pad",
    ),
    "PRJEB46220-Argentina": (
        "PRJEB46220-Argentina-bwa-pad",
        "PRJEB46220-Argentina-new-bwa-pad",
    ),
}
MERGE_ZIP_STEMS = {stem: code for code, stems in MERGE_GROUPS.items() for stem in stems}

GROUP_LABELS = {
    "Angola_miseq": "Angola MiSeq",
    "illumina_miseq": "Illumina MiSeq",
    "India-6000": "India NovaSeq 6000",
    "India-miseq": "India MiSeq",
    "LA-PRJNA815364": "USA LA PRJNA815364",
    "nextseq_500": "NextSeq 500",
    "nextseq_550": "NextSeq 550",
    "NJ-PRJNA708324": "USA NJ PRJNA708324",
    "PAK_iseq": "Pakistan iSeq",
    "Port-PRJEB47340": "Portugal NextSeq 550",
    "Port_miseq": "Portugal MiSeq",
    "Angola_miseq": "Angola MiSeq",
    "PRJEB46220-Argentina": "Argentina PRJEB46220",
    "PRJNA622837-Broad_Inst": "USA NE, NJ PRJNA622837",
    "Russia682735": "Russia",
    "S_Afr-PRJNA636748": "South Africa MiSeq",
    "Thailand_mix": "Thailand",
}


def sql_str(value: object | None) -> str:
    if value is None:
        return "NULL"
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def group_code_from_stem(stem: str) -> str:
    if stem in MERGE_ZIP_STEMS:
        return MERGE_ZIP_STEMS[stem]
    code = stem
    if code.endswith("-bwa-pad"):
        code = code[: -len("-bwa-pad")]
    return code


def group_label(code: str) -> str:
    if code in GROUP_LABELS:
        return GROUP_LABELS[code]
    return code.replace("_", " ").replace("-", " ")


def sample_name_from_filename(filename: str) -> str:
    name = Path(filename.replace("\\", "/")).name
    match = SAMPLE_NAME_RE.match(name)
    if match:
        return match.group("name")
    if name.lower().endswith(".vcf"):
        return Path(name).stem
    return name


def load_list_file(path: Path | None) -> set[str] | None:
    if path is None:
        return None
    names: set[str] = set()
    for line in path.read_text(encoding="utf-8", errors="replace").splitlines():
        text = line.strip()
        if text == "" or text.startswith("#"):
            continue
        names.add(text)
    return names


def sample_allowed(sample_name: str, allowed: set[str] | None) -> bool:
    if allowed is None:
        return True
    return sample_name in allowed


def parse_af(fmt: str, sample: str) -> float | None:
    keys = fmt.split(":")
    vals = sample.split(":")
    if "AF" in keys:
        idx = keys.index("AF")
    elif len(vals) > 2:
        idx = 2
    else:
        return None
    if idx >= len(vals):
        return None
    try:
        return float(vals[idx])
    except ValueError:
        return None


def parse_vcf_lines(lines, pad: int = PAD, genome_max: int = GENOME_MAX):
    """Yield (coordinate, ref, alt, af) for single-base SNVs on the real genome."""
    for raw in lines:
        line = raw.rstrip("\n\r")
        if line == "" or line.startswith("#"):
            continue
        cols = line.split("\t")
        if len(cols) < 10:
            continue
        try:
            pos = int(cols[1])
        except ValueError:
            continue
        coord = pos - pad
        if coord < 1 or coord > genome_max:
            continue
        ref = cols[3].upper()
        alt = cols[4].upper()
        if len(ref) != 1 or len(alt) != 1 or ref not in BASES or alt not in BASES:
            continue
        if "," in cols[4]:
            continue
        af = parse_af(cols[8], cols[9])
        if af is None:
            continue
        yield coord, ref, alt, af


def iter_vcf_from_zip(zip_path: Path):
    with zipfile.ZipFile(zip_path) as zf:
        for info in zf.infolist():
            if info.is_dir():
                continue
            name = info.filename.replace("\\", "/")
            if not name.lower().endswith(".vcf"):
                continue
            with zf.open(info) as handle:
                wrapper = io.TextIOWrapper(handle, encoding="utf-8", errors="replace")
                text = wrapper.read()
            yield name, text.splitlines()


def iter_vcf_from_dir(folder: Path):
    for path in sorted(folder.rglob("*.vcf")):
        text = path.read_text(encoding="utf-8", errors="replace")
        yield str(path), text.splitlines()


def find_snv_zip_dir(explicit: Path | None = None) -> Path:
    if explicit is not None:
        return explicit
    hits = sorted(PACKET_FILES.glob("SNVs-*/SNVs"))
    if hits:
        return hits[0]
    nested = PACKET_FILES / "SNVs"
    if nested.is_dir():
        return nested
    raise SystemExit("Missing SNVs zip folder under " + str(PACKET_FILES))


def collect_group_sources(snv_dir: Path) -> dict[str, list[Path]]:
    by_stem = {zip_path.stem: zip_path for zip_path in snv_dir.glob("*.zip")}
    grouped: dict[str, list[Path]] = defaultdict(list)
    used: set[str] = set()
    for code, stems in MERGE_GROUPS.items():
        for stem in stems:
            path = by_stem.get(stem)
            if path is not None:
                grouped[code].append(path)
                used.add(stem)
    for stem, zip_path in sorted(by_stem.items()):
        if stem in used:
            continue
        grouped[group_code_from_stem(stem)].append(zip_path)
    return dict(grouped)


def extract_nested_snv_zips(stems: list[str], dest: Path, outer: Path = SEP17_SNV_ZIP) -> list[Path]:
    """Copy named inner ZIPs from the Sep 17 SNV archive into dest."""
    if not outer.is_file():
        raise SystemExit("Missing " + str(outer))
    dest.mkdir(parents=True, exist_ok=True)
    wanted = set(stems)
    found: dict[str, Path] = {}
    with zipfile.ZipFile(outer) as zf:
        for name in zf.namelist():
            base = Path(name.replace("\\", "/")).name
            if not base.lower().endswith(".zip"):
                continue
            stem = Path(base).stem
            if stem not in wanted:
                continue
            target = dest / base
            if not target.is_file() or target.stat().st_size == 0:
                with zf.open(name) as src, target.open("wb") as out:
                    out.write(src.read())
            found[stem] = target
    missing = [s for s in stems if s not in found]
    if missing:
        raise SystemExit("Nested ZIP not in archive: " + ", ".join(missing))
    return [found[s] for s in stems]


def parse_connection_php(path: Path = CONNECTION_PHP) -> dict[str, str | int]:
    if not path.is_file():
        raise SystemExit("Missing " + str(path))
    text = path.read_text(encoding="utf-8", errors="replace")

    def grab(name: str, default: str = "") -> str:
        match = re.search(r"\$" + name + r'\s*=\s*"([^"]*)"', text)
        return match.group(1) if match else default

    port_raw = grab("port", "3306")
    try:
        port = int(port_raw)
    except ValueError:
        port = 3306
    return {
        "host": grab("host", "127.0.0.1"),
        "user": grab("user", "app_sarsntdb"),
        "password": grab("password"),
        "dbname": grab("dbname", "app_sarsntdb"),
        "port": port,
    }


def mysql_run(sql: str, cfg: dict[str, str | int], extra_args: list[str] | None = None) -> None:
    exe = MYSQL_EXE if MYSQL_EXE.is_file() else Path("mysql")
    cmd = [
        str(exe),
        "-h",
        str(cfg["host"]),
        "-P",
        str(cfg["port"]),
        "-u",
        str(cfg["user"]),
        str(cfg["dbname"]),
        "--batch",
        "--raw",
    ]
    if extra_args:
        cmd[1:1] = extra_args
    env = os.environ.copy()
    env["MYSQL_PWD"] = str(cfg["password"])
    proc = subprocess.run(
        cmd,
        input=sql.encode("utf-8"),
        env=env,
        capture_output=True,
    )
    if proc.returncode != 0:
        err = proc.stderr.decode("utf-8", errors="replace")
        raise SystemExit("mysql failed: " + err)


def try_pymysql(cfg: dict[str, str | int]):
    try:
        import pymysql
    except ImportError:
        return None
    return pymysql.connect(
        host=str(cfg["host"]),
        port=int(cfg["port"]),
        user=str(cfg["user"]),
        password=str(cfg["password"]),
        database=str(cfg["dbname"]),
        charset="utf8mb4",
        autocommit=False,
    )


def write_sql_file(path: Path, statements: list[str]) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as handle:
        handle.write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n")
        for stmt in statements:
            handle.write(stmt)
            if not stmt.endswith(";\n") and not stmt.endswith(";"):
                handle.write(";\n")
            elif not stmt.endswith("\n"):
                handle.write("\n")
        handle.write("SET FOREIGN_KEY_CHECKS=1;\n")


def batched_values(rows: list[str], batch: int) -> list[list[str]]:
    return [rows[i : i + batch] for i in range(0, len(rows), batch)]


def mysql_pairs(sql: str, cfg: dict[str, str | int]) -> list[list[str]]:
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


def delete_group_data(cfg: dict[str, str | int], codes: list[str]) -> None:
    if not codes:
        return
    in_list = ",".join(sql_str(c) for c in codes)
    mysql_run(
        "DELETE e FROM vcf_snv_sample_eligibility e "
        "JOIN vcf_snv_group g ON g.id=e.group_id WHERE g.code IN (" + in_list + "); "
        "DELETE m FROM vcf_snv_sample_meta m "
        "JOIN vcf_snv_group g ON g.id=m.group_id WHERE g.code IN (" + in_list + "); "
        "DELETE c FROM vcf_snv_call c "
        "JOIN vcf_snv_group g ON g.id=c.group_id WHERE g.code IN (" + in_list + "); "
        "DELETE s FROM vcf_snv_sample s "
        "JOIN vcf_snv_group g ON g.id=s.group_id WHERE g.code IN (" + in_list + "); "
        "DELETE FROM vcf_snv_group WHERE code IN (" + in_list + ");",
        cfg,
    )


def insert_parsed_incremental(
    parsed: dict[str, dict[str, list[tuple[int, str, str, float]]]],
    stats: dict[str, dict[str, int]],
    cfg: dict[str, str | int],
    allowed_samples: set[str] | None,
) -> None:
    note = "Jim Kelley VCF packet; extra vcf folder(s) merged"
    if allowed_samples is not None:
        note += "; list-file filter"
    conn = try_pymysql(cfg)
    if conn is None:
        raise SystemExit("pymysql is required for --replace incremental import")
    cur = conn.cursor()
    try:
        for code in parsed:
            cur.execute(
                "INSERT INTO vcf_snv_group (code,label,sample_count,call_count,source_note) VALUES (%s,%s,%s,%s,%s)",
                (code, group_label(code), stats[code]["samples"], stats[code]["calls"], note),
            )
            gid = int(cur.lastrowid)
            name_to_id: dict[str, int] = {}
            sample_rows = [(gid, name) for name in sorted(parsed[code])]
            if sample_rows:
                cur.executemany(
                    "INSERT INTO vcf_snv_sample (group_id,sample_name) VALUES (%s,%s)",
                    sample_rows,
                )
            cur.execute("SELECT id, sample_name FROM vcf_snv_sample WHERE group_id=%s", (gid,))
            for sid, name in cur.fetchall():
                name_to_id[str(name)] = int(sid)
            call_rows = []
            for sample, calls in parsed[code].items():
                sid = name_to_id[sample]
                for coord, ref, alt, af in calls:
                    call_rows.append((gid, sid, coord, ref, alt, float(af)))
            print("Inserting " + str(len(call_rows)) + " SNV calls for " + code + "...")
            for chunk in batched_values(call_rows, 800):
                cur.executemany(
                    "INSERT INTO vcf_snv_call "
                    "(group_id,sample_id,coordinate,reference,alternate,allele_frequency) "
                    "VALUES (%s,%s,%s,%s,%s,%s)",
                    chunk,
                )
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        cur.close()
        conn.close()


def import_groups(
    snv_dir: Path,
    allowed_samples: set[str] | None = None,
    only_group: str | None = None,
    cfg: dict[str, str | int] | None = None,
    apply: bool = True,
    replace: bool = False,
) -> dict[str, dict[str, int]]:
    sources = collect_group_sources(snv_dir)
    if only_group:
        wanted = [c.strip() for c in only_group.split(",") if c.strip()]
        missing = [c for c in wanted if c not in sources]
        if missing:
            raise SystemExit("Unknown group " + ", ".join(missing) + ". Have: " + ", ".join(sorted(sources)))
        sources = {c: sources[c] for c in wanted}

    stats: dict[str, dict[str, int]] = {}
    parsed: dict[str, dict[str, list[tuple[int, str, str, float]]]] = {}

    for code, zips in sources.items():
        samples: dict[str, list[tuple[int, str, str, float]]] = {}
        vcf_n = 0
        skipped_list = 0
        duplicate_samples = 0
        for zip_path in zips:
            print("  reading " + zip_path.name + " ...")
            for filename, lines in iter_vcf_from_zip(zip_path):
                vcf_n += 1
                sample = sample_name_from_filename(filename)
                if not sample_allowed(sample, allowed_samples):
                    skipped_list += 1
                    continue
                if sample in samples:
                    duplicate_samples += 1
                    continue
                samples[sample] = list(parse_vcf_lines(lines))
        parsed[code] = samples
        stats[code] = {
            "zips": len(zips),
            "vcf_files": vcf_n,
            "skipped_list": skipped_list,
            "duplicate_samples": duplicate_samples,
            "samples": len(samples),
            "calls": sum(len(v) for v in samples.values()),
        }
        extra = ""
        if skipped_list:
            extra += " (list skipped " + str(skipped_list) + ")"
        if duplicate_samples:
            extra += " (duplicate IDs kept first: " + str(duplicate_samples) + ")"
        print(
            code
            + ": "
            + str(stats[code]["samples"])
            + " samples, "
            + str(stats[code]["calls"])
            + " SNV calls"
            + extra
        )

    if not apply:
        return stats
    if cfg is None:
        cfg = parse_connection_php()

    if replace:
        delete_group_data(cfg, list(parsed))
        insert_parsed_incremental(parsed, stats, cfg, allowed_samples)
        return stats

    mysql_run(DROP_SQL.read_text(encoding="utf-8"), cfg)
    mysql_run(SCHEMA_SQL.read_text(encoding="utf-8"), cfg)

    group_rows = []
    for idx, code in enumerate(sorted(parsed), start=1):
        note = "Jim Kelley VCF packet 2026-09-16"
        if allowed_samples is not None:
            note += "; list-file filter"
        group_rows.append(
            "("
            + str(idx)
            + ","
            + sql_str(code)
            + ","
            + sql_str(group_label(code))
            + ","
            + str(stats[code]["samples"])
            + ","
            + str(stats[code]["calls"])
            + ","
            + sql_str(note)
            + ")"
        )

    code_to_id = {code: idx for idx, code in enumerate(sorted(parsed), start=1)}
    sample_rows = []
    sample_id = 1
    sample_ids: dict[tuple[str, str], int] = {}
    for code in sorted(parsed):
        gid = code_to_id[code]
        for sample in sorted(parsed[code]):
            sample_ids[(code, sample)] = sample_id
            sample_rows.append("(" + str(sample_id) + "," + str(gid) + "," + sql_str(sample) + ")")
            sample_id += 1

    call_rows = []
    for code in sorted(parsed):
        gid = code_to_id[code]
        for sample, calls in parsed[code].items():
            sid = sample_ids[(code, sample)]
            for coord, ref, alt, af in calls:
                call_rows.append(
                    "("
                    + str(gid)
                    + ","
                    + str(sid)
                    + ","
                    + str(coord)
                    + ","
                    + sql_str(ref)
                    + ","
                    + sql_str(alt)
                    + ","
                    + repr(float(af))
                    + ")"
                )
    print("Inserting " + str(len(call_rows)) + " SNV calls...")

    conn = try_pymysql(cfg)
    if conn is not None:
        cur = conn.cursor()
        for chunk in batched_values(group_rows, 50):
            cur.execute(
                "INSERT INTO `vcf_snv_group` (`id`,`code`,`label`,`sample_count`,`call_count`,`source_note`) VALUES "
                + ",".join(chunk)
            )
        for chunk in batched_values(sample_rows, 400):
            cur.execute("INSERT INTO `vcf_snv_sample` (`id`,`group_id`,`sample_name`) VALUES " + ",".join(chunk))
        for chunk in batched_values(call_rows, 800):
            cur.execute(
                "INSERT INTO `vcf_snv_call` (`group_id`,`sample_id`,`coordinate`,`reference`,`alternate`,`allele_frequency`) VALUES "
                + ",".join(chunk)
            )
        conn.commit()
        cur.close()
        conn.close()
        return stats

    statements = []
    for chunk in batched_values(group_rows, 50):
        statements.append(
            "INSERT INTO `vcf_snv_group` (`id`,`code`,`label`,`sample_count`,`call_count`,`source_note`) VALUES "
            + ",".join(chunk)
            + ";"
        )
    for chunk in batched_values(sample_rows, 400):
        statements.append("INSERT INTO `vcf_snv_sample` (`id`,`group_id`,`sample_name`) VALUES " + ",".join(chunk) + ";")
    for chunk in batched_values(call_rows, 800):
        statements.append(
            "INSERT INTO `vcf_snv_call` (`group_id`,`sample_id`,`coordinate`,`reference`,`alternate`,`allele_frequency`) VALUES "
            + ",".join(chunk)
            + ";"
        )
    sql_path = PACKET_FILES.parent / "generated" / "vcf_snv_groups_data.sql"
    sql_path.parent.mkdir(parents=True, exist_ok=True)
    write_sql_file(sql_path, statements)
    mysql_run(sql_path.read_text(encoding="utf-8"), cfg)
    return stats


def main() -> None:
    parser = argparse.ArgumentParser(description="Import Jim Kelley VCF SNV groups")
    parser.add_argument("--input-dir", type=Path, default=None, help="Folder of group zip files")
    parser.add_argument("--list-file", type=Path, default=None, help="Optional sample-name list")
    parser.add_argument("--group", default=None, help="Import only this group code (comma-separated)")
    parser.add_argument("--replace", action="store_true", help="Replace listed groups; do not drop other VCF groups")
    parser.add_argument(
        "--from-sep17",
        action="store_true",
        help="Extract the group ZIP(s) from the Sep 17 SNV archive",
    )
    parser.add_argument("--dry-run", action="store_true", help="Parse and count, do not write MySQL")
    args = parser.parse_args()

    if args.replace and not args.group:
        raise SystemExit("--replace requires --group")

    snv_dir = find_snv_zip_dir(args.input_dir)
    if args.from_sep17:
        codes = [c.strip() for c in (args.group or "").split(",") if c.strip()]
        if not codes:
            raise SystemExit("--from-sep17 requires --group")
        stems: list[str] = []
        for code in codes:
            if code not in MERGE_GROUPS:
                raise SystemExit("No merge ZIP list for " + code)
            stems.extend(MERGE_GROUPS[code])
        dest = ROOT / "scripts" / "_tmp_merge_zips"
        extract_nested_snv_zips(stems, dest)
        snv_dir = dest

    allowed = load_list_file(args.list_file)
    import_groups(
        snv_dir,
        allowed_samples=allowed,
        only_group=args.group,
        apply=not args.dry_run,
        replace=args.replace,
    )


if __name__ == "__main__":
    main()
