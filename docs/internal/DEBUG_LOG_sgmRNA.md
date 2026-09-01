# DEBUG LOG — sgmRNA / two-segment module

## Issues encountered

1. **Cannot include `main.js` on the new page** — `main.js` runs `document.getElementById("repeatDisplay")` at parse time and appends gene blocks immediately; missing element would break the script. **Fix**: Reimplemented the genome scale `mapPos` and gene block loop in `JS/twoSegmentViz.js` with the same numeric data as `main.js`.

2. **Empty PHP array → JSON `[]`** — `TSG_ENTRIES` must be a JS object for `data[id]` lookups. **Fix**: `json_encode(empty($byId) ? new stdClass() : $byId, ...)`.

3. **MySQL not running in dev environment** — attempted CLI import failed with connection refused. **Mitigation**: SQL file documented; page shows a clear warning if the table is missing.

## Assumptions

- Genome length **29903** nt remains the reference (consistent with existing UI).
- Demo seed data is explicitly labeled in SQL `notes` and in `TASKS_sgmRNA_module.md` as replaceable demo content.
- `coord_left` &lt; `coord_right` for normal sgmRNA layouts; UI shows a warning if not.

## Fixes made

- Safe error hint in `TwoSegmentStructures.php` when table is missing (`doesn't exist` / `Unknown table` in error text).
- `htmlspecialchars` on echoed table cells; JSON uses hex flags for script safety.
