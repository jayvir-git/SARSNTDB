# Genome search

The genome search is the main way to explore genes and proteins by **genome position** or by **DNA sequence**. It also powers the **SARS-CoV vs SARS-CoV-2 domain comparison**.

## Entry point

- **URL:** `GenomeSearch.php`
- **Navigation:** Search → Genome

## What you can do

1. **Search by coordinates** – Enter **Start** and/or **End** (integer positions on the SARS-CoV-2 genome, 1-based). The app returns all genes that overlap that range and, when available, domain and repeat data for that range.
2. **Search by gene/protein** – Choose a **Gene** or **Protein** from the dropdown (e.g. “S Gene”, “Surface Glycoprotein”). You can combine this with Start/End to narrow the range.
3. **Search by DNA sequence** – In the **Start** (or **End**) field, enter a **DNA sequence** (letters A, T, G, C only, e.g. `ACGAACTT`). The app finds all positions where that sequence appears in the reference genome (`fastas/reference.fasta`), uses the **first match** to define Start/End for the rest of the search, and can show “Sequence Search Results” with all match positions.

## Flow

1. **GenomeSearch.php** – Renders the form (Gene/Protein dropdown populated from **ProtienInfo.php** or backend, Start/End inputs). Form submits via GET to **GenomeResult.php** with parameters: e.g. `Protein`, `Start`, `End`.
2. **GenomeResult.php**:
   - Reads `$_GET['Start']`, `$_GET['End']`, `$_GET['Protein']`.
   - If Start (or End) looks like a DNA sequence (only A/T/G/C), calls **findSequenceMatches()** on `fastas/reference.fasta`, stores matches in session, and uses the first match as Start/End for queries.
   - Builds SQL conditions for **Gene_1** (genes overlapping the range, optional Protein filter), **cov_comp** (domains in range), **repeatcoord** (repeats in range).
   - Runs queries, then outputs HTML: optional “Sequence Search Results” block, genes table, domains section, repeats section.
3. From the result page, user can:
   - Click **View Detail** → **GenomeDetail.php** / **GenomeDetailData.php** for that gene/protein.
   - Click **Compare domains in SARS-CoV and SARS-CoV-2** → **GenomeComparisonData.php** (AJAX) with the selected Protein; the comparison table and colored sequences are inserted into the page.

## Important details

- **Reference FASTA** – Sequence search requires **`fastas/reference.fasta`**. If the file is missing, sequence search will find no matches. Coordinate search does not depend on it.
- **1-based coordinates** – Positions are 1-based (first nucleotide = 1). **findSequenceMatches()** returns 1-based positions.
- **Overlap logic** – A gene is included if its Start–End range overlaps the user’s Start–End (either the gene overlaps the range or the range overlaps the gene). The exact logic is in the **$q1** conditions in GenomeResult.php.
- **Protein name normalization** – When a protein is selected, the code may map display names (e.g. “ORF3a Protein”) to the **cov_comp** gene format (e.g. “orf3a”) for the domain query.

## Related files

| File | Role |
|------|------|
| GenomeSearch.php | Search form UI |
| GenomeResult.php | Result page + sequence search + queries |
| GenomeDetail.php, GenomeDetailData.php | Gene/protein detail view |
| GenomeComparison.php, GenomeComparisonData.php, GenomeComparisonInfo.php | SARS-CoV vs SARS-CoV-2 comparison |
| ProtienInfo.php, ProteinInfo.php | Protein/gene list and info |
| connection.php | DB connection |
| fastas/reference.fasta | Reference genome for sequence search |

## Database tables

- **Gene_1** – genes and coordinates (and optionally Protein, Function, matchedcols).
- **cov_comp** – domain comparison (cov2Start, cov2End, gene, feature, etc.).
- **repeatcoord** – repeat coordinates for range.
- **Protein_1**, **Gene_2** – used in detail and comparison where applicable.
