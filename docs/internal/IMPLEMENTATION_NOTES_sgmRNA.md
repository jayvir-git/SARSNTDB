# IMPLEMENTATION NOTES — two-segment / sgmRNA visualization

## Architecture

- **Model**: One table `two_segment_structure` supports multiple subtypes via `subtype` (first: `sgmRNA`). Reserved `link_url` column for forward compatibility.
- **Server**: `TwoSegmentStructures.php` loads rows with mysqli (same pattern as other pages), renders HTML table, embeds `window.TSG_ENTRIES` as JSON for the viewer.
- **Client**: `TwoSegmentViz.showSelected()` reads checked boxes, renders each structure track (absolute-positioned divs), then one shared genome reference row. No new npm/chart dependencies.

## Coordinate mapping

- Linear map identical to `JS/main.js`: `x = coord * 900 / 29903`.
- **Gap length** displayed: `max(0, coord_right - coord_left - 1)` assuming closed genomic intervals for the two segments. Documented for professor confirmation.

## Files added

| File | Role |
|------|------|
| `sql/two_segment_structure.sql` | CREATE TABLE + INSERT demo rows |
| `TwoSegmentStructures.php` | List + checkboxes + Show + viz mount point |
| `JS/twoSegmentViz.js` | Scale, structure tracks, genome bar |
| `two_segment_viz.css` | Layout and track styling |

## Files modified

| File | Change |
|------|--------|
| `Navigation.php` | Search dropdown link + active state |

## Database changes

Run on the application database (e.g. `app_sarsntdb`):

```text
mysql ... < sql/two_segment_structure.sql
```

(or phpMyAdmin import of the same file).

## How to test

1. Start MySQL, ensure `connection.php` points at the DB you updated.
2. Import `sql/two_segment_structure.sql` if the table is missing.
3. Open `TwoSegmentStructures.php` (or use nav: Search → 2-segment (sgmRNA)).
4. With no selection, click **Show** → message to select rows.
5. Select one row → **Show** → one schematic above genome bar; verify labels (from/left/right/to, gap borders, gap length, repeat).
6. Select multiple rows → stacked schematics, single genome row at bottom.
7. Pick rows with large coordinates → bars stay within 900 px track.

## Duplication note

Gene coordinates/colors are duplicated from `JS/main.js` inside `twoSegmentViz.js` because `main.js` expects `#repeatDisplay` at load time. Consolidation is listed in `TASKS_sgmRNA_module.md`.
