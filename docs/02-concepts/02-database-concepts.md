# Database concepts

This document explains how data is stored and used in SARSNTDB, at a conceptual level. For the full schema and column details, see [Architecture: Database schema](../03-architecture/04-database-schema.md).

## Why a database?

The app needs to store and retrieve:

- **Genes** (names, genome coordinates, descriptions, nucleotide sequences).
- **Proteins** (names, gene, amino acid ranges, descriptions, functions).
- **Domain comparison** (SARS-CoV vs SARS-CoV-2 domains, coordinates, identity scores).
- **Mutations** (e.g. by instrument, frequency, shape scores).
- **Repeats** (short sequences and their positions in the genome).

A **relational database** (MySQL/MariaDB) holds this in **tables** with **columns**. The PHP code connects to the database, builds **queries** (e.g. “give me all genes where Start/End overlap this range”), and displays the results on the page.

## Main ideas

### Tables and columns

- A **table** is like a spreadsheet: rows = records, columns = fields.
- Example: table **Gene_1** might have columns **Gene**, **Start**, **End**, **Gene_Description**, **Gene_NucleotideSequence**. Each row is one gene (or one gene–protein combination, depending on design).
- **Primary key**: a column (or set of columns) that uniquely identifies a row (e.g. **Gene** in **Gene_1**).

### Relationships

- **Gene** and **Protein** are related: a protein is encoded by a gene. The **Protein_1** table has a **Gene** column (and **Gene_Num**) linking to **Gene_1**. The app often **JOIN**s tables to show “this gene with these proteins” or “this protein with its gene’s coordinates”.

### Queries and filters

- The app builds **SQL** queries dynamically. For example:
  - “User entered Start=21563, End=25384” → add conditions like “Start BETWEEN 21563 AND 25384 OR End BETWEEN …” so that any gene overlapping that range is returned.
  - “User selected Protein = Surface Glycoprotein” → add “AND Gene_1.Protein = 'Surface Glycoprotein'” (or equivalent, depending on schema).
- **Parameterized queries** (prepared statements) are recommended for user input to avoid SQL injection; some legacy code may concatenate strings—when adding or changing code, use parameterized queries.

### Database name and user

- The **database name** used in the project is **`app_sarsntdb`**. The PHP script **connection.php** sets `$dbname = "app_sarsntdb"` and connects with a MySQL **user** and **password**. That user must have privileges on `app_sarsntdb` so the app can run SELECT (and INSERT/UPDATE/DELETE if any feature uses them).

## What the app queries (by feature)

| Feature | Typical tables / data |
|--------|-------------------------|
| Genome search (genes in range) | **Gene_1** (Start, End, Gene, Protein, Accession, Function, etc.) |
| Domain comparison | **cov_comp** (SARS-CoV vs SARS-CoV-2 domains, cov2Start, cov2End, identities, etc.) |
| Repeats | **repeats** (sequence, coord, SUPrepeats); **Gene_1** for overlapping genes |
| Mutations | Mutation-related tables (e.g. by instrument, frequency, shape scores) as defined in the schema |
| Protein/gene info | **Gene_1**, **Protein_1**, **Gene_2** |

Exact table and column names can differ between the base **SARS.sql** and extended imports (e.g. **gene_1_25.sql**, **cov_comp_25.sql**). The code in **GenomeResult.php**, **repeatData.php**, **GenomeComparisonData.php**, etc., uses specific column names—so the database schema must match what the code expects. See [Database schema](../03-architecture/04-database-schema.md) for the actual names used in the app.
