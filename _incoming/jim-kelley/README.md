# Jim Kelley inbox

Drop new attachments in this folder (flat). In chat, paste the email or notes. The agent will make a dated packet and move **only the new files** into it.

Do not commit spreadsheets, BED, CSV, PDF, or DOCX. Re-run importers if a source file changes; do not parse xlsx in PHP on page load.

## Already imported (leave these flat)

Importers read this folder by filename. Do not move these until those scripts are updated.

| Files | Importer | App page |
|-------|----------|----------|
| `definitions.pdf` | (reference only) | Junction groups — column/chart definitions |
| `LTG_bbmap_vir_072926_17943.xlsx` | `scripts/import_junction_query_xlsx.py` | `JunctionGroupQuery.php` |
| `LTG_bbmap_vir_080726_primer_17943_v3_v4.1.xlsx` | same | same |
| `var_17943_5249-23191_8_groups_stack-norm.xlsx` | same | same |
| `fav-continents-line-17943_NJ1_063025.xlsx` | same | same |
| `NJ-all_bbmap.txt-bbmap-sorted.bam-mapped_reads-C8-vir_pass-info-all-var_primer_col.csv` | `scripts/import_viridian_counts.py` | Junction groups (Viridian counts) |
| `PRJNA656534-NM-bbmap.txt-bbmap-sorted.bam-mapped_reads-C8-vir_pass-info-var_primer_col.csv` | same | same |
| `nCoV-2019_ARTIC_V3.scheme.bed` | `scripts/import_primer_beds.py` | `TwoSegmentStructures.php` (primer arrows) |
| `nCoV-2019_ARTIC_V4.1.scheme.bed` | same | same |
| `SARS-CoV-2_ARTIC_V5.3.primer.bed` | same | same |
| `nCoV-2019-midnight-1200-v1.scheme.bed` | same | same |
| `NEB_VarSkip.scheme.bed` | same | same |
| `neb_vss1a.primer.bed` | same | same |
| `NJ-table-small.csv` | same (NJ rows) | same |
| `primer_controls1.xlsx` | (junction list for primer work) | same |
| `NJ_894.docx` | (notes for NJ 18324–19217) | same |
| `histogram_example_input.csv` | not wired to an importer | leftover example |

## Next email

1. Put new files in this folder.
2. Paste the email in chat.
3. Agent creates `_incoming/jim-kelley/YYYY-MM-DD_short-slug/` with `REQUEST.md` and moves only the new files into `files/`.
