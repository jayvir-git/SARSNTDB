# Handover — primer arrows on junctions (Jim Kelley, 2026-08-25)

Do this in a **new chat** with a fresh context window. Branch: `feature/junction-group-variant-query` (or a new branch off current htdocs). Workspace: `C:\xampp\htdocs\SARSNTDB`. Do **not** redo the Junction groups / Viridian counts work; that already exists.

## Goal (highest priority)

Show **primers as arrows** on the two-segment junction schematic (`TwoSegmentStructures.php` + `JS/twoSegmentViz.js`), so Jim can compare primer kits. Java Genome Navigator was a demo; Dr. Grigoriev wants this **in SARSNTDB**.

Jim asked for a **screenshot of junction size 17943 first**, even if preliminary.

## Junctions to add (NJ = non-canonical junction)

Jim convention: `coord_from=1`, `coord_left=start`, `coord_right=end`, `coord_to=29903`.

**Do first (call):**
1. 5249–23191, size 17943 — may already exist (`sql/junction_query_two_segment.sql`). Ensure it is in `two_segment_structure`.
2. 18324–19217, size 894 — **new**.

**Then (email `primer_controls1.xlsx`, columns Size / NJ Start / NJ End):**
- 12778–13322 (545)
- 25432–25624 (193)
- 5636–20091 (14456)
- 6188–8953 (2766)
- 27801–29172 (1372)

Call said: get 17943 + 894 looking right **before** dumping every extra junction on him. Safe approach: insert all in SQL, but UI screenshot / default deep-link is **17943**.

## Primer BED files

`_incoming/jim-kelley/` (same six he showed on Zoom):

| File | Scheme label |
|------|----------------|
| `nCoV-2019_ARTIC_V3.scheme.bed` | ARTIC V3 |
| `nCoV-2019_ARTIC_V4.1.scheme.bed` | ARTIC V4.1 |
| `SARS-CoV-2_ARTIC_V5.3.primer.bed` | ARTIC V5.3 |
| `nCoV-2019-midnight-1200-v1.scheme.bed` | Midnight-1200 |
| `NEB_VarSkip.scheme.bed` | VarSkip |
| `neb_vss1a.primer.bed` | VarSkip vss1a |

Jim: two VarSkip files may differ by a few lines — **import both**; pick “more lines” only if you must choose one.

BED columns (not always all present): chrom (`MN908947.3`), start, end, name, pool, strand (`+`/`-`). Some files **lack strand**. Start/end can be reversed (Midnight).

Generate SQL from a Python importer (pattern: `scripts/import_viridian_counts.py`). Reversible drop in `sql/junction_query_drop.sql` or a dedicated drop file.

## Which primers to draw

For a junction with borders `left` and `right`:

Include a primer if **start or end** is within **500 nt** of **either** border (both sides of the gap).

Jim’s science: V3 primers sit at this junction; V4.1 missing one (another primer *inside* the gap “shouldn’t matter”); V5 and Midnight have **none nearby**; VarSkip has some. The 500 bp window is so those differences are visible.

## How to draw (Dr. Grigoriev)

- **Arrows**, not the thick junction bars.
- Name contains **LEFT** → arrow points **right**.
- Name contains **RIGHT** → arrow points **left** (reverse complement / minus strand).
- If strand column exists: `-` agrees with RIGHT→left; `+` with LEFT→right. If missing, use LEFT/RIGHT in the name.
- Toggle schemes (like his Genome Navigator menu: ARTIC V3 vs V4.1 Apply). Checkboxes or a dropdown: V3 / V4.1 / V5 / Midnight / both VarSkips.
- Pink highlight in his demo was the V3 track — optional, not required for v1.

Scale: same as existing viz, `29903 nt → 900 px` (`JS/twoSegmentViz.js`).

## Files to touch (minimal)

- `sql/` new primer tables + INSERT NJ rows
- `scripts/import_primer_beds.py` (or similar)
- `two_segment_helpers.php` — fetch primers near a junction
- `TwoSegmentStructures.php` — scheme picker, pass primer JSON
- `JS/twoSegmentViz.js` + `two_segment_viz.css` — arrow overlay
- `Navigation.php` only if a new page is needed (prefer **same schematic**, not a new app)

Do not break Genome search CJ/NJ links or Junction groups (`JunctionGroupQuery.php`).

## Verify

1. Import SQL on `app_sarsntdb`.
2. Open `TwoSegmentStructures.php?id=<17943 row>`.
3. V3: arrows near 5249 and/or 23191.
4. V5 / Midnight: few or none in the 500 bp window (Jim’s claim).
5. LEFT arrows point right; RIGHT arrows point left.
6. Repeat for 894 (18324–19217).

## Context sources (read-only)

- Meeting transcripts (local, gitignored under `transcripts/`)
- `_incoming/jim-kelley/*.bed`, `primer_controls1.xlsx`

## Do not

- Re-import Viridian NJ/NM counts unless asked.
- Put primers on the Junction groups **percent** charts.
- Wait for more junctions before shipping 17943 + 894.
