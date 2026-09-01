# Database schema

The application uses MySQL/MariaDB with database name **`app_sarsntdb`**. This section documents the main tables and columns used by the code. Schema may come from **SARS.sql** plus optional imports (**gene_1_25.sql**, **cov_comp_25.sql**). If your deployment uses different table or column names, adjust the PHP accordingly.

---

## Connection

- **connection.php** sets: host 127.0.0.1, port 3306, user and dbname **app_sarsntdb**, password in config. Variable **`$con`** is the mysqli connection used in all queries.

---

## Table: Gene_1

Stores gene (and often gene–protein) records with genome coordinates and nucleotide sequence.

| Column | Type | Description |
|--------|------|-------------|
| **Gene** | varchar(50) NOT NULL | Gene name (e.g. "S Gene", "ORF1ab"). Primary key in SARS.sql. |
| **Gene_Number** | int(10) | Numeric gene index. |
| **Accession** | int(30) / varchar | Accession ID. |
| **Start** | int(10) NOT NULL | Start position on genome (1-based). |
| **End** | int(10) NOT NULL | End position on genome (1-based). |
| **Gene_Description** | varchar(1000) | Short description. |
| **Gene_Function** | varchar(1000) | Function text. |
| **Gene_NucleotideSequence** | text NOT NULL | Nucleotide sequence of the gene. |

**Extended schema** (e.g. from gene_1_25.sql) may add or rename columns, e.g. **Protein**, **Function**, **matchedcols**, **RNA_sequence**, **protSeq**, **Domain**, **Motif**, **Region**, **Aa_count**, **Function_detail**, **Non_translated_RNA_sequence**. The code in **GenomeResult.php** uses:

- `SELECT Gene, Protein, Accession, Start, End, Function, matchedcols FROM Gene_1`

so the deployed **Gene_1** must have at least **Gene**, **Start**, **End**; and if the app expects **Protein**, **Accession**, **Function**, **matchedcols**, those columns must exist.

---

## Table: Gene_2

Supplementary gene metadata (e.g. function, non-translated RNA).

| Column | Type | Description |
|--------|------|-------------|
| **Gene** | varchar(15) NOT NULL | Gene name. |
| **Accession** | varchar(30) NOT NULL | Accession. |
| **Gene_Function** | text | Function. |
| **Non_Translated_RNA_Sequence** | varchar(1000) | Non-translated RNA sequence or description. |

---

## Table: Protein_1

Proteins encoded by genes (amino acid ranges, descriptions).

| Column | Type | Description |
|--------|------|-------------|
| **Gene_Num** | varchar(50) NOT NULL | Links to gene (numeric or code). |
| **Protein_id** | varchar(30) | Protein accession (e.g. YP_009724390.1). |
| **Gene** | varchar(20) NOT NULL | Gene name (e.g. "S Gene", "ORF1ab"). |
| **Protein** | varchar(50) NOT NULL | Protein name (e.g. "Surface Glycoprotein", "Nsp1"). |
| **Start_aa** | varchar(10) | Start position in concatenated protein coordinate (or protein-specific). |
| **End_aa** | int(10) | End position (amino acid). |
| **Aa_count** | int(11) | Amino acid count. |
| **P_Description** | varchar(500) | Description. |
| **P_Function** | varchar(1000) | Function. |

Joins between **Gene_1** and **Protein_1** are typically on **Gene** (and possibly Protein) so that genome ranges can be shown with the correct protein names and descriptions.

---

## Table: cov_comp

SARS-CoV vs SARS-CoV-2 domain comparison (used by GenomeResult.php and GenomeComparisonData.php).

| Column | Type | Description |
|--------|------|-------------|
| **gene** | varchar(55) | Gene identifier (lowercase, e.g. "nsp1", "S"). |
| **feature** | varchar(55) | e.g. "Domain". |
| **domainNameCov2** | varchar(55) | Domain name in SARS-CoV-2. |
| **domainNameCov** | varchar(55) | Domain name in SARS-CoV. |
| **cov2AAStartEnd** | varchar(55) | SARS-CoV-2 domain AA range (e.g. "1-12"). |
| **cov2Start** | varchar(55) | SARS-CoV-2 genome start. |
| **cov2End** | varchar(55) | SARS-CoV-2 genome end. |
| **covAAStartEnd** | varchar(55) | SARS-CoV domain AA range. |
| **covStart** | varchar(55) | SARS-CoV genome start. |
| **covEnd** | varchar(55) | SARS-CoV genome end. |
| **identities** | varchar(55) | e.g. "10/12(83%)". |
| **positives** | varchar(55) | e.g. "10/12(83%)". |
| **gaps** | varchar(55) | Gaps. |
| **Publication** | varchar(50) NOT NULL | DOI or link. |
| **Publicationcov** | varchar(100) NOT NULL | Publication for SARS-CoV. |
| **dashRange** | varchar(11) NOT NULL | Range for alignment display. |
| **dashRange2** | varchar(11) NOT NULL | Second range. |

**GenomeResult.php** uses: `SELECT gene, feature, domainNameCov2, cov2Start, cov2End, cov2AAStartEnd FROM cov_comp WHERE 1=1 $q2`. **GenomeComparisonData.php** uses **cov_comp** with protein/gene filter and returns sequences and dash ranges for the comparison view.

---

## Table: repeats

Stores repeat (motif) sequences and their positions. Used by **repeatData.php**.

| Column | Type | Description |
|--------|------|-------------|
| **sequence** | varchar | The repeat sequence (e.g. "ACGAAC"). |
| **coord** | numeric/varchar | Genome coordinate(s) where the repeat appears (exact format may be one value per row or a list). |
| **SUPrepeats** | text/varchar | “Super repeats” or related info. |

**repeatData.php** queries: `SELECT r.sequence, r.coord, r.SUPrepeats FROM repeats r WHERE 1=1 $q1`, with $q1 on `r.sequence` (exact or LIKE). It also queries **Gene_1** to map coordinates to genes.

---

## Table: repeatcoord

Used by **GenomeResult.php** for repeat data in a coordinate range.

- **repeatcoord.coord** – coordinate column used in conditions like `repeatcoord.coord BETWEEN $start AND $end`.
- Other columns may exist; the code builds **$q3** for this table when Start/End are set.

---

## Mutation tables

The mutations feature (MutationsSummary, MutationsDetail, MutationsResult, MutationsInfo) uses one or more tables that hold:

- Mutation by instrument / by frequency.
- Shape scores (e.g. Incarnato, WT, DELTA, GSE153984).

**MutationsInfo** class properties suggest columns or result sets such as: mutationsByInstrument, mutationsByFrequency, mutationsShapeScoreIncarnato, mutationsShapeScoreWT, mutationsShapeScoreDELTA, mutationsShapeScoreGSE153984. The exact table names and schema depend on the SQL dumps provided for your deployment; the PHP in MutationsSummary.php and related files should be inspected to match your database.

---

## Optional / alternate schema

- **gene_1_25.sql** may define a **gene_1** (lowercase) table with columns: Gene, Protein, Accession, Start, End, Domain, Motif, Region, Function, Aa_count, Function_detail, Non_translated_RNA_sequence, RNA_sequence, matchedcols, protSeq. That may target database **web_app**. The main app uses **Gene_1** (capital G) in **app_sarsntdb**; if you use gene_1_25, ensure either the app is updated to use that table name and DB or the data is imported into **Gene_1** in **app_sarsntdb** with compatible column names.
- **intraGene** – referenced in **GenomeResult.php** ($q4) for intragenic regions; the corresponding query may be commented out. If you enable it, ensure table **intraGene** exists with columns such as leftStart, rightStart, leftEnd, rightEnd, readSupport.

---

## Summary

- **app_sarsntdb** – database name.
- **Gene_1** – main gene/coordinates/sequence table; must have Gene, Start, End and (for full functionality) Protein, Accession, Function, matchedcols as used by GenomeResult.php.
- **Gene_2**, **Protein_1** – gene metadata and protein info.
- **cov_comp** – SARS-CoV vs SARS-CoV-2 domain comparison; used by genome results and comparison view.
- **repeats** – repeat sequences and coordinates; used by repeatData.php.
- **repeatcoord** – repeat coordinates for range queries in GenomeResult.php.
- Mutation tables – names and columns as in your SQL and Mutations*.php code.

When adding or changing features, keep JOINs and column names in sync with this schema (or your actual schema) to avoid “unknown column” or empty results.
