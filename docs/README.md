# SARSNTDB Documentation

This folder is the **complete knowledge base** for the SARSNTDB project: a PHP web application for exploring SARS-CoV-2 genomic data (genes, proteins, mutations, repeats, and reference sequences).

## Documentation structure

| Section | Description |
|--------|--------------|
| [01 - Getting started](01-getting-started/) | Overview, prerequisites, installation, and first run |
| [02 - Concepts](02-concepts/) | Biology primer (no background assumed), database and app concepts |
| [03 - Architecture](03-architecture/) | High-level design, file structure, data flow, database schema |
| [04 - Features](04-features/) | Genome search, mutations, repeats, reference, and help |
| [05 - Development](05-development/) | Adding pages/endpoints, conventions, repo vs XAMPP, [before publishing](05-development/05-before-publishing.md) |
| [06 - Operations](06-operations/) | Troubleshooting, debugging, deployment |
| [07 - Reference](07-reference/) | Quick reference tables and glossary |
| [Internal](internal/) | Working notes (sgmRNA tasks, implementation, debug log) |

## Quick links

- **I'm new (no biology background):** Start with [Concepts: Biology primer](02-concepts/01-biology-primer.md).
- **I need to run the app:** Go to [Getting started: Installation](01-getting-started/03-installation.md).
- **I need to understand the codebase:** Read [Architecture: High-level](03-architecture/01-high-level-architecture.md) and [File structure](03-architecture/02-file-structure.md).
- **Something is broken:** See [Operations: Troubleshooting](06-operations/01-troubleshooting.md).
- **I want to add a feature:** See [Development: Adding pages](05-development/01-adding-pages.md).

## Project at a glance

- **What:** PHP web app for SARS-CoV-2 genes, proteins, mutations, repeats, and reference data.
- **Stack:** PHP, MySQL/MariaDB, Bootstrap 3, jQuery, CanvasJS. Some Python for VCF/FASTA.
- **Run:** XAMPP (Apache + MySQL), project in `htdocs/SARSNTDB`, DB `app_sarsntdb`.
- **Main URL:** `http://localhost/SARSNTDB/GenomeSearch.php`

Nothing is left out: every major file, table, feature, and concept used in the project is covered in the sections above.
