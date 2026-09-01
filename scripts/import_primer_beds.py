"""Build reversible primer-arrow SQL from Jim Kelley's six BED files.

BED coordinates are retained verbatim and also normalized to 1-based inclusive
genome coordinates for comparison with SARSNTDB junction coordinates.
"""
from __future__ import annotations

import csv
from dataclasses import dataclass
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BED_DIR = ROOT / "_incoming" / "jim-kelley"
NJ_TABLE = BED_DIR / "NJ-table-small.csv"
OUT_SQL = ROOT / "sql" / "primer_arrows.sql"
OUT_DROP_SQL = ROOT / "sql" / "primer_arrows_drop.sql"


@dataclass(frozen=True)
class Scheme:
    code: str
    label: str
    filename: str
    display_order: int


SCHEMES = (
    Scheme("artic_v3", "ARTIC V3", "nCoV-2019_ARTIC_V3.scheme.bed", 10),
    Scheme("artic_v4_1", "ARTIC V4.1", "nCoV-2019_ARTIC_V4.1.scheme.bed", 20),
    Scheme("artic_v5_3", "ARTIC V5.3", "SARS-CoV-2_ARTIC_V5.3.primer.bed", 30),
    Scheme("midnight_1200", "Midnight-1200", "nCoV-2019-midnight-1200-v1.scheme.bed", 40),
    Scheme("varskip", "VarSkip", "NEB_VarSkip.scheme.bed", 50),
    Scheme("varskip_vss1a", "VarSkip vss1a", "neb_vss1a.primer.bed", 60),
)

@dataclass(frozen=True)
class Junction:
    size: int
    left: int
    right: int
    repeat: str | None
    display_order: int
    source: str


def sql_str(value: object | None) -> str:
    if value is None:
        return "NULL"
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def direction_for(name: str, strand: str | None) -> str:
    if strand == "+":
        return "R"
    if strand == "-":
        return "L"
    upper = name.upper()
    if "LEFT" in upper:
        return "R"
    if "RIGHT" in upper:
        return "L"
    raise ValueError(f"Cannot determine direction for primer {name!r}")


def parse_bed(scheme: Scheme) -> list[dict[str, object]]:
    path = BED_DIR / scheme.filename
    if not path.exists():
        raise FileNotFoundError(f"Missing BED file: {path}")

    rows: list[dict[str, object]] = []
    for line_no, raw in enumerate(path.read_text(encoding="utf-8-sig").splitlines(), 1):
        line = raw.strip()
        if not line or line.startswith(("#", "track", "browser")):
            continue
        parts = raw.rstrip("\r\n").split("\t")
        if len(parts) < 4:
            raise ValueError(f"{path.name}:{line_no}: expected at least 4 BED columns")

        bed_start = int(parts[1])
        bed_end = int(parts[2])
        name = parts[3].strip()
        pool = parts[4].strip() if len(parts) > 4 and parts[4].strip() else None
        strand_raw = parts[5].strip() if len(parts) > 5 else ""
        strand = strand_raw if strand_raw in {"+", "-"} else None

        # BED is 0-based and end-exclusive. Some Midnight rows reverse the
        # two endpoints to encode reverse orientation, so normalize first.
        coord_start = min(bed_start, bed_end) + 1
        coord_end = max(bed_start, bed_end)
        direction = direction_for(name, strand)

        upper = name.upper()
        expected = "R" if "LEFT" in upper else "L" if "RIGHT" in upper else None
        if expected is not None and strand is not None and direction != expected:
            raise ValueError(
                f"{path.name}:{line_no}: strand conflicts with LEFT/RIGHT name for {name!r}"
            )

        rows.append(
            {
                "reference_name": parts[0].strip(),
                "bed_start": bed_start,
                "bed_end": bed_end,
                "coord_start": coord_start,
                "coord_end": coord_end,
                "primer_name": name,
                "pool_name": pool,
                "strand": strand,
                "direction": direction,
                "source_line": line_no,
            }
        )
    return rows


def load_nj_table() -> list[Junction]:
    if not NJ_TABLE.exists():
        raise FileNotFoundError(f"Missing NJ table: {NJ_TABLE}")

    expected_header = ["Size", "NJ Start", "NJ End", "Repeat"]
    junctions: list[Junction] = []
    with NJ_TABLE.open(encoding="utf-8-sig", newline="") as handle:
        reader = csv.reader(handle)
        header = next(reader, None)
        if header is None or [cell.strip() for cell in header] != expected_header:
            raise ValueError(f"{NJ_TABLE.name}: expected header {expected_header}, got {header}")
        for line_no, parts in enumerate(reader, 2):
            if not parts or not any(cell.strip() for cell in parts):
                continue
            if len(parts) < 3:
                raise ValueError(f"{NJ_TABLE.name}:{line_no}: expected Size, NJ Start, NJ End")
            size = int(parts[0].strip())
            left = int(parts[1].strip())
            right = int(parts[2].strip())
            repeat_raw = parts[3].strip() if len(parts) > 3 else ""
            repeat = repeat_raw.upper() if repeat_raw else None
            inclusive = right - left + 1
            if size != inclusive:
                raise ValueError(
                    f"{NJ_TABLE.name}:{line_no}: size {size} != inclusive {inclusive} for {left}-{right}"
                )
            junctions.append(
                Junction(
                    size=size,
                    left=left,
                    right=right,
                    repeat=repeat,
                    display_order=90 + len(junctions),
                    source="NJ-table-small.csv",
                )
            )

    if len(junctions) != 30:
        raise ValueError(f"{NJ_TABLE.name}: expected 30 junctions, got {len(junctions)}")
    return junctions


def junction_upsert(junction: Junction) -> str:
    name = f"NJ {junction.left}\u2013{junction.right} (size {junction.size})"
    note = (
        f"Primer arrows import: {junction.source}; inclusive junction size {junction.size}."
    )
    link = (
        "JunctionGroupQuery.php?left=5249&right=23191"
        if (junction.left, junction.right) == (5249, 23191)
        else None
    )
    return "\n".join(
        [
            "INSERT INTO `two_segment_structure`",
            "  (`subtype`,`junction_kind`,`name`,`coord_from`,`coord_left`,`coord_right`,`coord_to`,`repeat_seq`,`link_url`,`notes`,`display_order`)",
            "SELECT 'sgmRNA','NJ',{name},1,{left},{right},29903,{repeat},{link},{note},{order}".format(
                name=sql_str(name),
                left=junction.left,
                right=junction.right,
                repeat=sql_str(junction.repeat),
                link=sql_str(link),
                note=sql_str(note),
                order=junction.display_order,
            ),
            "FROM DUAL",
            "WHERE NOT EXISTS (",
            "  SELECT 1 FROM `two_segment_structure`",
            f"  WHERE `coord_left` = {junction.left} AND `coord_right` = {junction.right}",
            ");",
            "",
            "-- Fill/refresh Jim's repeat sequence without deleting a pre-existing row.",
            "UPDATE `two_segment_structure`",
            f"   SET `repeat_seq` = {sql_str(junction.repeat)},",
            f"       `name` = {sql_str(name)},",
            f"       `display_order` = {junction.display_order}",
            f" WHERE `coord_left` = {junction.left} AND `coord_right` = {junction.right};",
        ]
    )


def main() -> None:
    parsed = [(scheme, parse_bed(scheme)) for scheme in SCHEMES]
    total = sum(len(rows) for _, rows in parsed)
    junctions = load_nj_table()

    lines = [
        "-- Primer schemes and arrows for the two-segment junction schematic.",
        "-- Generated by scripts/import_primer_beds.py from _incoming/jim-kelley/*.bed",
        "-- NJ rows come from NJ-table-small.csv (INSERT if missing, UPDATE repeat_seq if present).",
        "-- BED coordinates are preserved; coord_start/coord_end are 1-based inclusive.",
        "-- Reverse with sql/primer_arrows_drop.sql",
        "",
        "SET NAMES utf8mb4;",
        "SET FOREIGN_KEY_CHECKS=0;",
        "DROP TABLE IF EXISTS `junction_primer`;",
        "DROP TABLE IF EXISTS `junction_primer_scheme`;",
        "SET FOREIGN_KEY_CHECKS=1;",
        "",
        "CREATE TABLE `junction_primer_scheme` (",
        "  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,",
        "  `code` varchar(32) NOT NULL,",
        "  `label` varchar(64) NOT NULL,",
        "  `source_file` varchar(255) NOT NULL,",
        "  `display_order` int(11) NOT NULL DEFAULT 0,",
        "  PRIMARY KEY (`id`),",
        "  UNIQUE KEY `uq_jps_code` (`code`)",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "CREATE TABLE `junction_primer` (",
        "  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,",
        "  `scheme_id` int(10) UNSIGNED NOT NULL,",
        "  `reference_name` varchar(64) NOT NULL,",
        "  `bed_start` int(11) NOT NULL COMMENT 'Original BED start',",
        "  `bed_end` int(11) NOT NULL COMMENT 'Original BED end',",
        "  `coord_start` int(11) NOT NULL COMMENT 'Normalized 1-based inclusive start',",
        "  `coord_end` int(11) NOT NULL COMMENT 'Normalized 1-based inclusive end',",
        "  `primer_name` varchar(255) NOT NULL,",
        "  `pool_name` varchar(128) DEFAULT NULL,",
        "  `strand` char(1) DEFAULT NULL,",
        "  `direction` char(1) NOT NULL COMMENT 'R=right, L=left',",
        "  `source_line` int(11) NOT NULL,",
        "  PRIMARY KEY (`id`),",
        "  UNIQUE KEY `uq_jp_source_line` (`scheme_id`,`source_line`),",
        "  KEY `idx_jp_scheme_coords` (`scheme_id`,`coord_start`,`coord_end`),",
        "  CONSTRAINT `fk_jp_scheme` FOREIGN KEY (`scheme_id`) REFERENCES `junction_primer_scheme` (`id`) ON DELETE CASCADE",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "INSERT INTO `junction_primer_scheme` (`id`,`code`,`label`,`source_file`,`display_order`)",
        "VALUES",
    ]
    scheme_values = [
        f"  ({i},{sql_str(s.code)},{sql_str(s.label)},{sql_str(s.filename)},{s.display_order})"
        for i, s in enumerate(SCHEMES, 1)
    ]
    lines.append(",\n".join(scheme_values) + ";")
    lines.extend(["", "INSERT INTO `junction_primer`", "  (`id`,`scheme_id`,`reference_name`,`bed_start`,`bed_end`,`coord_start`,`coord_end`,`primer_name`,`pool_name`,`strand`,`direction`,`source_line`)", "VALUES"])

    primer_values = []
    primer_id = 0
    for scheme_id, (_, rows) in enumerate(parsed, 1):
        for row in rows:
            primer_id += 1
            primer_values.append(
                "  ({id},{scheme},{reference},{bed_start},{bed_end},{coord_start},{coord_end},{name},{pool},{strand},{direction},{source_line})".format(
                    id=primer_id,
                    scheme=scheme_id,
                    reference=sql_str(row["reference_name"]),
                    bed_start=row["bed_start"],
                    bed_end=row["bed_end"],
                    coord_start=row["coord_start"],
                    coord_end=row["coord_end"],
                    name=sql_str(row["primer_name"]),
                    pool=sql_str(row["pool_name"]),
                    strand=sql_str(row["strand"]),
                    direction=sql_str(row["direction"]),
                    source_line=row["source_line"],
                )
            )
    lines.append(",\n".join(primer_values) + ";")
    lines.append("")
    lines.append("-- Required NJ rows from NJ-table-small.csv.")
    lines.append("-- INSERT skips existing coordinate pairs; UPDATE fills repeat_seq/name/order.")
    lines.append("-- Drop SQL deletes only rows whose notes start with 'Primer arrows import:'.")
    for junction in junctions:
        lines.extend([junction_upsert(junction), ""])

    OUT_SQL.write_text("\n".join(lines), encoding="utf-8")
    OUT_DROP_SQL.write_text(
        "\n".join(
            [
                "-- Reverse sql/primer_arrows.sql without touching Junction groups / Viridian tables.",
                "SET FOREIGN_KEY_CHECKS=0;",
                "DROP TABLE IF EXISTS `junction_primer`;",
                "DROP TABLE IF EXISTS `junction_primer_scheme`;",
                "SET FOREIGN_KEY_CHECKS=1;",
                "",
                "-- Removes only NJ rows this import inserted. Pre-existing coordinate rows keep",
                "-- their original notes, so this DELETE does not drop them even if repeats were updated.",
                "DELETE FROM `two_segment_structure`",
                " WHERE `notes` LIKE 'Primer arrows import:%';",
                "",
            ]
        ),
        encoding="utf-8",
    )
    print(f"Wrote {OUT_SQL} with {len(SCHEMES)} schemes, {total} primers, and {len(junctions)} NJ rows")
    print(f"Wrote {OUT_DROP_SQL}")


if __name__ == "__main__":
    main()
