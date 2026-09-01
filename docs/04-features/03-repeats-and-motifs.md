# Repeats and motif visualizer

The repeats feature lets users search for a **short nucleotide sequence** (motif) in the SARS-CoV-2 genome and see **where** it occurs (coordinates) and **which genes** it overlaps.

## Entry point

- **URL:** `motifvisualizer.php`
- **Navigation:** Search → Repeats

## What you can do

- Enter a **repeat sequence** (e.g. `ACGAAC`) in the input field.
- Submit to get:
  - **Positions** – All genome coordinates where that sequence (or a containing sequence) appears.
  - **Genome visualization** – Red tick marks or similar on a genome representation.
  - **Gene overlap** – Which genes (from Gene_1) contain each position.
  - **Super Repeats** – Suggested variations or super-repeats containing the input (from **repeats.SUPrepeats** or similar).

## Flow

1. **motifvisualizer.php** – Renders the form and the area for the genome viz and table. Includes Navigation. On submit, the form may POST/GET to the same page or to an endpoint that triggers a request to **repeatData.php** with `?repeat=...`.
2. **repeatData.php**:
   - Reads `$_GET['repeat']`.
   - Builds **$q1** for table **repeats**: first exact match on `r.sequence = $repeat`. If no rows, retries with `r.sequence LIKE '%$repeat%'`.
   - Queries **repeats** (sequence, coord, SUPrepeats).
   - Queries **Gene_1** (Gene, Protein, Start, End) to map coordinates to genes.
   - Builds a **RepeatInfo** object (sequence, substrings, coordinates, proteins).
   - Returns **JSON** (or similar) to the client.
3. **Front end** (motifvisualizer.php or JS) receives the data and:
   - Draws the genome representation with marks at each coordinate.
   - Fills the table with coordinates and overlapping genes.
   - Shows “Super Repeats” if available.

If the repeat is not found at all, the backend may return a message like “Repeat not found in database, please enter another repeat of length 6 or greater…”

## Validation

- The UI or backend may restrict input to **A, C, G, T** (case-insensitive) and a minimum length (e.g. 6). Check motifvisualizer.php for any `includes()` or regex checks on the input string.

## RepeatInfo class (RepeatInfo.php)

- **sequence** – string
- **substrings** – array
- **coordinates** – array
- **proteins** – array (or list of genes overlapping the coordinates)

## Related files

| File | Role |
|------|------|
| motifvisualizer.php | Repeats page and viz |
| repeatData.php | Data endpoint (repeats + Gene_1) |
| RepeatInfo.php | Data class |
| connection.php | DB connection |

## Database tables

- **repeats** – columns: sequence, coord, SUPrepeats (exact names may vary slightly).
- **Gene_1** – used to map coord to Gene, Protein, Start, End.

## Relationship to genome search

- **Genome search** can also show **repeats in a range**: GenomeResult.php builds **$q3** for **repeatcoord** when Start/End are set, and displays a repeats section for that range.
- The **motif visualizer** is query-driven by **sequence** (repeats table) and then overlays results on the genome and Gene_1; it does not use repeatcoord in the same way as GenomeResult.php.
