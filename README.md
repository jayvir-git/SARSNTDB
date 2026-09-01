# SARSNTDB

PHP web application for exploring SARS-CoV-2 genomic data: genes, proteins, mutations, repeats, two-segment (sgmRNA) structures, and junction groups.

**Live site:** [grigoriev-lab.camden.rutgers.edu/sarsntdb](https://grigoriev-lab.camden.rutgers.edu/sarsntdb/)  
**Local:** [http://localhost/SARSNTDB/GenomeSearch.php](http://localhost/SARSNTDB/GenomeSearch.php)

Assigned by Dr. Andrey Grigoriev (Grigoriev Lab, Rutgers–Camden).

## Features

| Search | Page |
|--------|------|
| Genome (coordinates or DNA sequence) | `GenomeSearch.php` |
| Mutations | `MutationsSearch.php` |
| Repeats / motifs | `motifvisualizer.php` |
| 2-segment (sgmRNA) structures | `TwoSegmentStructures.php` |
| Junction groups | `JunctionGroupQuery.php` |
| Reference and help | `reference.php`, `help.php` |

## Stack

PHP, MySQL/MariaDB, Bootstrap 3, jQuery, CanvasJS. Python scripts in `scripts/` import lab tables into MySQL. Full write-up is in [`docs/`](docs/README.md).

## Quick start (XAMPP)

1. Start Apache and MySQL. Place this project at `C:\xampp\htdocs\SARSNTDB\` (this repo *is* that folder when you develop locally).
2. In phpMyAdmin, create database `app_sarsntdb` and import `SARS.sql` (then `gene_1_25.sql`, `cov_comp_25.sql`, and files under `sql/` as needed).
3. Copy `connection.example.php` to `connection.php`. Set `$password` to match a MySQL user named `app_sarsntdb` with privileges on that database. Do not commit `connection.php`.
4. Open [http://localhost/SARSNTDB/GenomeSearch.php](http://localhost/SARSNTDB/GenomeSearch.php).

DNA sequence search also needs `fastas/reference.fasta`.

Step-by-step: [docs/01-getting-started/03-installation.md](docs/01-getting-started/03-installation.md).

## Repository layout

- **Root `*.php`** — pages Apache serves. Do not rename `ProtienInfo.php`.
- **`JS/`**, **`sql/`**, **`scripts/`** — front-end, reversible schema/imports, and importers.
- **`docs/`** — architecture, features, and operations.
- **`_incoming/`** — local lab attachments only (gitignored except README). See [`_incoming/jim-kelley/README.md`](_incoming/jim-kelley/README.md).

## License

Not set. Ask Dr. Grigoriev before publishing this repository. `canvasjs-non-commercial-3.6.6/` and `phpGrid_Lite/` are third-party; confirm those licenses before a public push. Do not publish `_incoming/` data, meeting transcripts, or `connection.php`.
