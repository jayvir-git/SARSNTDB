# Quick reference

One-page reference for key URLs, files, and configuration.

## URLs (local)

| URL | Page |
|-----|------|
| http://localhost/SARSNTDB/ | index.php (home) |
| http://localhost/SARSNTDB/GenomeSearch.php | Genome search (main) |
| http://localhost/SARSNTDB/GenomeResult.php | Genome results (with query params) |
| http://localhost/SARSNTDB/MutationsSearch.php | Mutations search |
| http://localhost/SARSNTDB/motifvisualizer.php | Repeats / motif |
| http://localhost/SARSNTDB/reference.php | Reference (thesis PDF) |
| http://localhost/SARSNTDB/help.php | Help (instructions) |

Replace **SARSNTDB** with your folder name under htdocs if different.

## Configuration

| Item | Location / value |
|------|-------------------|
| DB connection | **connection.php** (root) |
| DB name | **app_sarsntdb** |
| DB user | Same as in connection.php (e.g. **app_sarsntdb**) |
| Host | 127.0.0.1 (in connection.php) |
| Reference genome (sequence search) | **fastas/reference.fasta** |

## Key files by role

| Role | Files |
|------|--------|
| Nav / shared UI | **Navigation.php** |
| DB connection | **connection.php** |
| Protein/gene list (typo name) | **ProtienInfo.php** |
| Protein info (detail) | **ProteinInfo.php** |
| Genome form | **GenomeSearch.php** |
| Genome results + sequence search | **GenomeResult.php** |
| Genome detail | **GenomeDetail.php**, **GenomeDetailData.php** |
| CoV comparison | **GenomeComparison.php**, **GenomeComparisonData.php**, **GenomeComparisonInfo.php** |
| Mutations | **MutationsSearch.php**, **MutationsSummary.php**, **MutationsDetail.php**, **MutationsResult.php**, **MutationsInfo.php** |
| Repeats | **motifvisualizer.php**, **repeatData.php**, **RepeatInfo.php** |
| Reference / Help | **reference.php**, **help.php** |

## Main database tables

| Table | Use |
|-------|-----|
| **Gene_1** | Genes, Start/End, nucleotide sequence, Protein, Function, matchedcols |
| **Gene_2** | Gene metadata, non-translated RNA |
| **Protein_1** | Proteins, Gene, Start_aa, End_aa, descriptions |
| **cov_comp** | SARS-CoV vs SARS-CoV-2 domain comparison |
| **repeats** | Repeat sequence, coord, SUPrepeats |
| **repeatcoord** | Repeat coordinates for range queries (GenomeResult.php) |
| Mutation tables | As in your schema (MutationsSummary/Detail/Result) |

## GET parameters (common)

| Parameter | Page | Meaning |
|-----------|------|--------|
| Start | GenomeResult.php | Start coordinate (number) or DNA sequence |
| End | GenomeResult.php | End coordinate (number) or DNA sequence |
| Protein | GenomeResult.php, GenomeComparisonData.php | Selected protein/gene name |
| Gene | Various | Gene name |
| repeat | repeatData.php | Repeat sequence (e.g. ACGAAC) |

## Two folder locations

| Location | Purpose |
|----------|--------|
| **Repo** (e.g. Documents\SARSNTDB-main\...) | Where you edit; Git tracks this |
| **htdocs** (e.g. C:\xampp\htdocs\SARSNTDB\) | What Apache serves; copy repo here to see changes |

## Setup checklist

- [ ] XAMPP: Apache + MySQL running  
- [ ] Database **app_sarsntdb** created and SQL imported  
- [ ] MySQL user created, privileges on app_sarsntdb, credentials in **connection.php**  
- [ ] Project (or updated files) copied to **htdocs/SARSNTDB/**  
- [ ] **fastas/reference.fasta** present (for sequence search)  
- [ ] Open http://localhost/SARSNTDB/GenomeSearch.php and test search  
