"""Add canonical (CJ) and TRS-L dependent (TL) junctions to the Two-segment structures page (Jim, 2026-11-02, D-85).

Reads Jim's size,start,end lists from the gitignored packets (junction coordinates are unpublished and this repo is
public) and writes a gitignored SQL file that replaces the rows this import added before:

    python scripts/import_two_segment_junctions.py --cj CJ.csv --trsl TRSL.csv [--trsl ...] [--trsl-hide HIDE.csv] --out OUT.sql

Each structure runs from 1 to 29903 with the deletion between Jim's start (left) and end (right), so
size = right - left + 1, as for the NJ rows. Reverse with sql/two_segment_junctions_drop.sql.
"""
from __future__ import annotations

import argparse
import csv
from pathlib import Path

NOTE = "Two-segment import (D-85)"
GENOME_END = 29903


def sql_str(value: str | None) -> str:
    if value is None:
        return "NULL"
    return "'" + value.replace("\\", "\\\\").replace("'", "''") + "'"


def read_rows(path: Path) -> list[tuple[int, int, int, str]]:
    """size, start, end, name (name may be blank); header and malformed lines are skipped."""
    rows = []
    with path.open(newline="", encoding="utf-8-sig") as fh:
        for row in csv.reader(fh):
            cells = [cell.strip() for cell in row]
            if len(cells) < 3 or not all(cell.isdigit() for cell in cells[:3]):
                continue
            size, start, end = (int(cell) for cell in cells[:3])
            if end - start + 1 != size:
                raise SystemExit(f"{path.name}: size {size} != end - start + 1 for {start}-{end}")
            rows.append((size, start, end, cells[3] if len(cells) > 3 else ""))
    return rows


def hidden_sizes(paths: list[Path]) -> set[tuple[int, str]]:
    out = set()
    for path in paths:
        with path.open(newline="", encoding="utf-8-sig") as fh:
            for row in csv.reader(fh):
                if len(row) >= 2 and row[0].strip().isdigit():
                    out.add((int(row[0]), row[1].strip()))
    return out


def structure_sql(kind: str, name: str, start: int, end: int, order: int) -> str:
    return (
        "INSERT INTO `two_segment_structure` "
        "(`subtype`,`junction_kind`,`name`,`coord_from`,`coord_left`,`coord_right`,`coord_to`,`repeat_seq`,`link_url`,`notes`,`display_order`) "
        f"VALUES ('sgmRNA',{sql_str(kind)},{sql_str(name)},1,{start},{end},{GENOME_END},NULL,NULL,{sql_str(NOTE)},{order});"
    )


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--cj", type=Path, required=True, help="Jim's canonical list: Size,Start,End,Name")
    parser.add_argument("--trsl", type=Path, action="append", default=[], help="Jim's TRS-L size,start,end list (repeatable)")
    parser.add_argument("--trsl-hide", type=Path, action="append", default=[], help="size,coord_tag rows hidden on Junction groups")
    parser.add_argument("--out", type=Path, required=True, help="SQL file to write (keep it out of git)")
    args = parser.parse_args()

    lines = [
        "-- Canonical and TRS-L dependent junctions for the Two-segment page (D-85). Unpublished: keep out of git.",
        "SET NAMES utf8mb4;",
        f"DELETE FROM `two_segment_structure` WHERE `notes` = {sql_str(NOTE)};",
    ]
    for i, (size, start, end, name) in enumerate(sorted(read_rows(args.cj))):
        label = f"CJ {name} (size {size})" if name else f"CJ size {size}"
        lines.append(structure_sql("CJ", label, start, end, 200 + i))

    trsl = []
    for path in args.trsl:
        trsl.extend(read_rows(path))
    counts: dict[int, int] = {}
    for size, _start, _end, _name in trsl:
        counts[size] = counts.get(size, 0) + 1
    hidden = hidden_sizes(args.trsl_hide)
    kept = 0
    for i, (size, start, end, _name) in enumerate(sorted(trsl)):
        tag = "L" + (str(start) if counts[size] > 1 else "")
        if (size, tag) in hidden:
            continue
        label = f"TRS-L size {size}" + (f" at {start}" if counts[size] > 1 else "")
        lines.append(structure_sql("TL", label, start, end, 300 + i))
        kept += 1

    args.out.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"wrote {args.out}: {len(read_rows(args.cj))} CJ, {kept} TRS-L ({len(trsl) - kept} hidden)")


if __name__ == "__main__":
    main()
