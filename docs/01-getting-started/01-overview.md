# Project overview

## What is SARSNTDB?

**SARSNTDB** (SARS Nucleotide Database) is a web application for browsing and searching **SARS-CoV-2** genomic and protein data. It provides:

- **Genome search** by nucleotide coordinates or by DNA sequence (e.g. search for "ACGAACTT" and see where it appears in the reference genome).
- **Gene and protein information** for SARS-CoV-2 genes (S Gene, N Gene, ORF1ab, etc.) and their proteins (Spike, Nucleocapsid, Nsp1–Nsp16, etc.).
- **SARS-CoV vs SARS-CoV-2 comparison** of structural domains and sequences.
- **Mutations** data: mutation likelihood, frequency, and RNA structure (e.g. icSHAPE).
- **Repeats / motif search**: find short nucleotide sequences (e.g. "ACGAAC") and see where they occur in the genome and in which genes.
- **Reference** material: thesis PDF and FASTA reference sequence.

The project was assigned by **Dr. Andrey Grigoriev** and is intended for research and teaching (e.g. thesis work, student use). The setup has been validated to run locally using XAMPP and a MySQL database named `app_sarsntdb`.

## Who is it for?

- **Researchers and students** working with SARS-CoV-2 genes, proteins, or mutations.
- **Developers** maintaining or extending the PHP application.
- **Instructors** who need a single place to explain the app and the biology behind it.

The documentation in this knowledge base is written so that **someone without a biology background** can understand the concepts (genes, proteins, mutations, genome coordinates, etc.) before or while using the app. See [Concepts: Biology primer](../02-concepts/01-biology-primer.md).

## Technology stack

| Layer | Technology |
|-------|------------|
| Backend | PHP (server-side logic, HTML generation, database access) |
| Database | MySQL / MariaDB (database name: `app_sarsntdb`) |
| Frontend | HTML, CSS, Bootstrap 3, jQuery |
| Charts / viz | CanvasJS (non-commercial) |
| Scripts | Python (VCF/FASTA processing where used) |
| Server | Apache (via XAMPP), PHP runs on the server |

There is no separate REST API: the app is a classic PHP web app with full-page loads and some AJAX calls to PHP endpoints that return HTML or JSON.

## Main entry points (URLs)

| URL | Purpose |
|-----|--------|
| `index.php` | Home / welcome page with pie chart |
| `GenomeSearch.php` | **Main search**: genome by coordinates or sequence, gene/protein selection |
| `GenomeResult.php` | Results for genome search (genes, domains, repeats in range) |
| `MutationsSearch.php` | Mutations search and summary/detail views |
| `motifvisualizer.php` | Repeats / motif search (short sequence in genome) |
| `TwoSegmentStructures.php` | 2-segment (sgmRNA) structures and primer overlays |
| `JunctionGroupQuery.php` | Junction group / variant charts |
| `reference.php` | Reference: embedded thesis PDF |
| `help.php` | Help: how to use the database (instructions + screenshots) |

Navigation is centralized in `Navigation.php` (Search → Genome / Mutations / Repeats / 2-segment / Junction groups / CSV repeats vs 2-segment, Reference, Help).

## What you need to run it

- **XAMPP** (or equivalent: Apache + MySQL + PHP).
- Project files under `htdocs/SARSNTDB` (e.g. `C:\xampp\htdocs\SARSNTDB`).
- MySQL database `app_sarsntdb` created and populated (e.g. from `SARS.sql` and any additional SQL provided by the professor).
- MySQL user with access to `app_sarsntdb`, matching the credentials in `connection.php` (copy from `connection.example.php`).

Details are in [Prerequisites](02-prerequisites.md) and [Installation](03-installation.md).
