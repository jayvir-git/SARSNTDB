# TASKS — sgmRNA / two-segment module

## Implemented

- MySQL table `two_segment_structure` with generalized columns: `subtype`, `name`, `coord_from`, `coord_left`, `coord_right`, `coord_to`, `repeat_seq`, `link_url` (reserved), `notes`, `display_order`.
- Seed/demo rows for subtype `sgmRNA` using **body start coordinates** from existing `Gene_1` data in the project SQL dumps; short `repeat_seq` values are **illustrative** motifs taken from `Gene_2` junction text where applicable.
- Page `TwoSegmentStructures.php`: sortable sticky-header-style table (scroll), row checkboxes, **Show** button, stacked schematics per selection.
- Client-side visualization `JS/twoSegmentViz.js` + `two_segment_viz.css`: thick segments, thin gap, gap border coordinates, gap length, repeat label; **genome reference bar** below (same 29903 → 900 px scale and gene blocks as `JS/main.js`).
- Navigation: Search → **2-segment (sgmRNA)**.

## Remaining questions (for Dr. Grigoriev)

1. **Entry point**: Keep under Search menu, move to Reference, or a dedicated section?
2. **Curated dataset**: Replace demo rows with authoritative sgmRNA coordinates and TRS/repeat sequences from the lab’s sources.
3. **Gap length convention**: Current UI uses internal gap length `max(0, right - left - 1)` for closed intervals `[from, left]` and `[right, to]`. Confirm if coordinates should be interpreted differently (e.g. inclusive/exclusive boundaries).
4. **`link_url`**: Intended use (e.g. external reference, cross-link to Genome Search with pre-filled interval)?
5. **Other subtypes**: Naming and any extra columns needed for non-sgmRNA two-segment structures.

## Future enhancements

- Admin UI or import workflow for CRUD on `two_segment_structure`.
- Share `map()` / gene arrays via a single `JS/genomeScale.js` included by `main.js` and `twoSegmentViz.js` to avoid duplication.
- Optional overlay of selected structures on the same bar as the genome (single canvas/SVG).
- API endpoint returning JSON for AJAX-driven tools.
