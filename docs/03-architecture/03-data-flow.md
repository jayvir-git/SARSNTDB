# Data flow

This document describes how requests and data move through the application: from user action to database and back to the browser.

## 1. Genome search (coordinates or sequence)

### User path

1. User opens **GenomeSearch.php** (or navigates Search → Genome).
2. User either:
   - Selects **Gene/Protein** from dropdown and/or enters **Start** and **End** (numbers), or
   - Enters a **DNA sequence** (e.g. `ACGAACTT`) in Start (or End).
3. User submits the form → GET request to **GenomeResult.php** with query parameters (e.g. `?Start=21563&End=25384` or `?Start=ACGAACTT`).

### GenomeResult.php flow

1. **Session** – If sequence search is used, `session_start()` may be called.
2. **Parameters** – Read `$_GET['Protein']`, `$_GET['Start']`, `$_GET['End']`.
3. **Sequence detection** – `isDNASequence($start)` checks if Start (or End) is a string of A/T/G/C. If yes:
   - **findSequenceMatches($sequence, './fastas/reference.fasta')** reads the reference FASTA, finds all 1-based positions where the sequence occurs, stores them in `$_SESSION['sequence_matches']` and `$_SESSION['search_sequence']`.
   - The **first match** is used as Start (and End = Start + length - 1) for the rest of the logic.
4. **Query building** – PHP builds SQL conditions:
   - **$q1** – for **Gene_1**: filters by Protein (if selected) and by Start/End overlap (BETWEEN, OR conditions for range overlap).
   - **$q2** – for **cov_comp**: same range and gene/protein.
   - **$q3** – for **repeatcoord**: coord in range.
   - **$q4** – for intraGene (if used); may be commented out.
5. **Database** – `require_once './connection.php'`; then:
   - Query **Gene_1**: `SELECT Gene, Protein, Accession, Start, End, Function, matchedcols FROM Gene_1 WHERE 1=1 $q1 ORDER BY ...`
   - Query **cov_comp**: domains in range.
   - Optionally query repeat data.
6. **Output** – HTML page with:
   - Optional “Sequence Search Results” block (if session has sequence_matches): sequence, count, coordinates.
   - Genes table (from Gene_1).
   - Domains table/section (from cov_comp).
   - Repeats section if applicable.

So: **GenomeSearch.php** (form) → **GenomeResult.php** (parameters → optional sequence lookup in reference.fasta → SQL to Gene_1, cov_comp, repeatcoord → HTML).

## 2. Genome comparison (SARS-CoV vs SARS-CoV-2)

- Triggered from the genome result/detail page (e.g. “Compare domains in SARS-CoV and SARS-CoV-2”).
- **JavaScript** calls **GenomeComparisonData.php** with GET parameter **Protein** (e.g. `GenomeComparisonData.php?Protein=Surface Glycoprotein`).
- **GenomeComparisonData.php**:
  - Includes **GenomeComparisonInfo.php** (class) and **connection.php**.
  - Builds query (and possibly normalizes protein name for cov_comp).
  - Queries **cov_comp** (and any related tables) for that gene/protein.
  - Returns data (JSON or HTML fragment) with sequences, dash ranges, identities, etc.
- **Front end** injects the result into the page (e.g. comparison table and colored sequence view).

Data flow: **Browser (click)** → **AJAX GET GenomeComparisonData.php?Protein=...** → **PHP queries cov_comp** → **Response** → **JS updates DOM**.

## 3. Repeats / motif search

### User path

1. User opens **motifvisualizer.php** (Search → Repeats).
2. User enters a short **sequence** (e.g. `ACGAAC`) and submits.

### Back end

- Form may POST/GET to the same page or to an endpoint that calls **repeatData.php** with `?repeat=ACGAAC`.
- **repeatData.php**:
  - Includes **RepeatInfo.php** and **connection.php**.
  - Builds **$q1** from `$_GET['repeat']` (exact match on `r.sequence`).
  - Queries table **repeats** (columns e.g. sequence, coord, SUPrepeats). If no rows, retries with `LIKE '%repeat%'`.
  - Queries **Gene_1** (all genes with Start, End) to map coordinates to genes.
  - Builds a **RepeatInfo** object (sequence, substrings, coordinates, proteins) and returns **JSON** (or similar).
- **motifvisualizer.php** (or JS) receives the data and renders the genome visualization and table of positions/genes.

Data flow: **User input** → **repeatData.php?repeat=...** → **repeats + Gene_1** → **JSON** → **Front end** (chart + table).

## 4. Mutations

- **MutationsSearch.php** loads the mutations UI (tabs, filters). User selects gene/protein or range.
- **MutationsSummary.php**, **MutationsDetail.php**, **MutationsResult.php** are loaded (by link or form) with parameters; each uses **connection.php** and possibly **MutationsInfo.php** (class holding mutationsByInstrument, mutationsByFrequency, shape scores, etc.).
- Queries hit the **mutations-related tables** (exact names depend on schema). Results drive the summary/detail tables and **CanvasJS** charts (e.g. by frequency, by instrument).
- Data flow: **MutationsSearch** (form/filters) → **MutationsSummary / Detail / Result** (PHP + SQL) → **HTML + charts**.

## 5. Gene/protein detail

- From genome results, user clicks “View Detail” (or similar) → **GenomeDetail.php** (or link to **GenomeDetailData.php** with gene/protein/id).
- **GenomeDetailData.php** includes **ProteinInfo.php** and **connection.php**, queries **Gene_1** (and possibly Protein_1, cov_comp) for the chosen gene/protein, builds **ProteinInfo** (image tag, detail array), returns HTML fragment or full page.
- **GenomeDetail.php** includes that content and Navigation.

## 6. Include chain (typical page)

For a typical search page:

1. **Navigation.php** – output first (or in &lt;head&gt;); provides nav bar.
2. **ProtienInfo.php** or **ProteinInfo.php** – defines class / data used by the page or by included logic.
3. **connection.php** – only in PHP files that run queries (GenomeResult.php, repeatData.php, GenomeComparisonData.php, MutationsSummary.php, etc.). Not included by pure HTML-output pages that don’t query DB.
4. **Page-specific logic** – read GET/POST, build queries, fetch rows, output HTML (and optional JSON for AJAX).

## Summary table

| Feature | User action | PHP entry | DB tables | Output |
|---------|-------------|-----------|-----------|--------|
| Genome search | Submit Start/End or sequence | GenomeResult.php | Gene_1, cov_comp, repeatcoord | HTML (genes, domains, repeats; optional sequence matches) |
| Genome comparison | Click compare | GenomeComparisonData.php (AJAX) | cov_comp | JSON/HTML fragment |
| Repeats | Submit sequence | repeatData.php | repeats, Gene_1 | JSON |
| Mutations | Select filters / gene | MutationsSummary/Detail/Result | Mutation tables | HTML + charts |
| Detail | View Detail | GenomeDetailData.php | Gene_1, Protein_1, cov_comp | HTML fragment |

All database access goes through **connection.php** (`$con`). Table and column names must match what the PHP expects (see [Database schema](04-database-schema.md)).
