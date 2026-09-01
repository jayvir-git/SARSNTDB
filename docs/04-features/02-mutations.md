# Mutations feature

The mutations feature provides search and visualization of **mutation data** for SARS-CoV-2 (e.g. likelihood, frequency, RNA structure scores such as icSHAPE).

## Entry point

- **URL:** `MutationsSearch.php`
- **Navigation:** Search → Mutations

## What you can do

- **Search/filter** mutations by gene, protein, or genomic range (depending on UI).
- **View summary** – Overview of mutations (e.g. by instrument, by frequency) in tables and charts.
- **View detail** – Per-region or per-mutation detail (e.g. structural domain, coordinates).
- **Charts** – CanvasJS is used to display mutation-related data (e.g. frequency, shape scores). Tabs and sortable tables (sortable.js) organize the data.

## Flow

1. **MutationsSearch.php** – Renders the mutations UI: tabs, filters, and placeholders for data. Includes Navigation and CanvasJS/sortable.js.
2. User selects gene/protein or range and submits or switches tabs.
3. **MutationsSummary.php** – Loaded (by link or form) for summary view. Uses **MutationsInfo.php** (class) and **connection.php**; queries mutation tables; fills MutationsInfo properties (e.g. mutationsByInstrument, mutationsByFrequency, mutationsShapeScoreIncarnato, mutationsShapeScoreWT, mutationsShapeScoreDELTA, mutationsShapeScoreGSE153984); outputs HTML and chart data.
4. **MutationsDetail.php** – Detail view for a selected region/mutation; uses connection.php and mutation tables.
5. **MutationsResult.php** – Result listing; uses connection.php.

Data from the backend is consumed by the front end to render tables and CanvasJS charts (with options like includeZero for axes).

## MutationsInfo class (MutationsInfo.php)

- **mutationsByInstrument** – array
- **mutationsByFrequency** – array
- **mutationsShapeScoreIncarnato** – array
- **mutationsShapeScoreWT** – array
- **mutationsShapeScoreDELTA** – array
- **mutationsShapeScoreGSE153984** – array

These properties are populated by the PHP that queries the mutation tables; the exact table and column names depend on the schema provided with your deployment.

## Related files

| File | Role |
|------|------|
| MutationsSearch.php | Main mutations UI (tabs, filters) |
| MutationsSummary.php | Summary view + charts |
| MutationsDetail.php | Detail view |
| MutationsResult.php | Result list |
| MutationsInfo.php | Data class for mutation arrays |
| connection.php | DB connection |
| canvasjs-non-commercial-3.6.6/ | Charts |
| sortable.js | Sortable table behavior |

## Database

- One or more **mutation tables** store: mutation by instrument, by frequency, and shape scores (Incarnato, WT, DELTA, GSE153984). Schema depends on the SQL dumps (not fully specified in the base SARS.sql). Check MutationsSummary.php and MutationsDetail.php for the exact table and column names used in your codebase.

## UI notes

- **tr.darkheader**, **tr.greyheader** – Sticky header rows for tables.
- **.datacontainer**, **.datagrid**, **.datagraph** – Layout for grid and chart areas.
- Tabs and “Mutations” button from genome/detail page link to the mutations view with parameters so the user sees mutations for the selected structural region.
