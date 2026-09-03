# High-level architecture

## Stack overview

```
┌─────────────────────────────────────────────────────────────────┐
│  Browser (HTML, CSS, jQuery, CanvasJS)                           │
└───────────────────────────────┬─────────────────────────────────┘
                                │ HTTP (GET/POST, AJAX)
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│  Apache (XAMPP) → PHP (GenomeSearch.php, GenomeResult.php, …)    │
└───────────────────────────────┬─────────────────────────────────┘
                                │ mysqli ($con)
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│  MySQL / MariaDB (database: app_sarsntdb)                         │
│  Tables: Gene_1, Gene_2, Protein_1, cov_comp, repeats, …         │
└─────────────────────────────────────────────────────────────────┘
```

- **User** interacts with the browser (clicks, form submit, sequence search).
- **Apache** serves PHP and static files. **PHP** scripts handle each URL, optionally read GET/POST, connect to MySQL via **connection.php** (`$con`), run queries, and output HTML (and trigger JSON/HTML responses for AJAX).
- **MySQL** holds genes, proteins, domains, mutations, repeats. The app uses the database name **app_sarsntdb** and a dedicated user (see `connection.php`).

## Entry points and main flows

| User action | Entry URL | What runs | Next / data |
|-------------|-----------|-----------|-------------|
| Open home | `index.php` | index.php, includes Navigation | Welcome + pie chart |
| Genome search | `GenomeSearch.php` | GenomeSearch.php, ProtienInfo.php, Navigation | Form: Gene/Protein, Start, End (or sequence) |
| Submit genome search | `GenomeResult.php?Start=...&End=...` (or sequence in Start) | GenomeResult.php, connection.php, queries to Gene_1, cov_comp, repeatcoord | Genes table, domains, repeats in range |
| Compare SARS-CoV vs SARS-CoV-2 | Same page + button / AJAX | GenomeComparisonData.php (AJAX) | Comparison table and sequence view |
| Mutations | `MutationsSearch.php` | MutationsSearch.php, later MutationsSummary/Detail/Result | Tabs, charts, mutation tables |
| Repeats | `motifvisualizer.php` | motifvisualizer.php, repeatData.php (AJAX or form) | Repeat positions, genome viz |
| Reference | `reference.php` | reference.php | Embedded thesis PDF |
| Help | `help.php` | help.php | Instructions + screenshots |

## Shared components

- **Navigation.php** – Top navbar (Search → Genome / Mutations / Repeats, Reference, Help). Included by almost every page.
- **connection.php** – Creates `$con` (mysqli) to the database. Required by any PHP that runs queries.
- **ProtienInfo.php** – Defines a small class / info used by genome search (note: filename typo is intentional for compatibility). **ProteinInfo.php** (correct spelling) also exists and is used by GenomeDetailData.php.

## External files and scripts

- **fastas/reference.fasta** – Reference genome sequence; used by GenomeResult.php for **DNA sequence search** (finding where a user-entered sequence appears).
- **Python scripts** (e.g. fastas/fastasequences.py, getvcfdata.py) – Used for generating or processing FASTA/VCF data; not required for the normal web UI.
- **CanvasJS** – Chart library (local under canvasjs-non-commercial-3.6.6/). Used for pie charts and mutation charts.
- **phpGrid_Lite** – Local third-party grid library (not in the public GitHub tree); not used by the main documented flows.

## Security and configuration

- **Credentials** – Database user and password live in **connection.php**. Keep this file out of version control if the repo is public, or use environment variables and document the expected variable names.
- **SQL** – User input (Start, End, sequence, protein name) is used in queries. Prefer **parameterized queries** (prepared statements) for new or updated code to avoid SQL injection.
- **Deployment** – The app is designed to run on **localhost** (XAMPP). For any production or shared deployment, lock down DB credentials, disable PHP error display, and follow standard PHP security practices.
