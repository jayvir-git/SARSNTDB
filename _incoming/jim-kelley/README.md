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

## Packets (source file lives in `files/`, not flat)

| Packet | Importer | App page |
|--------|----------|----------|
| `2026-09-09_pango-snv-lineages/files/pango-designation-markers-v1.9-calc-sort.xlsx` | `scripts/import_pango_snv_markers.py` | `MutationsSearch.php` Detail, `SnvPrimerView.php` |
| `2026-09-11_nearby-snvs-indels/` (no new spreadsheet; reuses the v1.9 xlsx) | same importer also writes `sql/pango_indel_marker.sql` | `MutationsDetail.php` indel table; nearby SNVs on `SnvPrimerView.php` and `TwoSegmentStructures.php` |
| `2026-09-16_vcf-snv-groups/files/` (group `*-bwa-pad.zip`, NJ two zips combined) | `scripts/import_vcf_snv_groups.py` | `MutationsSearch.php` group + Min AF; Detail Mean AF |
| `2026-09-16_pak-groups-list-file/` (email only) | same importer; no code change yet | list file is format example only; extra Pakistan groups not in today’s drop |
| `2026-09-17_snv-variant-primer-data/files/` (group workbook, feature note, two Google Drive ZIPs) | `scripts/import_vcf_sample_metadata.py` (PASS∩VCF; skips Broad/Portugal) | Mutations group n and Detail/CSV validation counts |
| `2026-09-17_junction-primer-filter-groups/` (email only; refers to prior packet) | primer filter on `JunctionGroupQuery.php`; **no 11-row hide** (mapping still unclear) | `RepeatCsvTwoSegment.php` kept (Jim’s 30-junction table) |
| `2026-09-17_pass-vcf-hide-groups/` (email only; follows up on both Sep 17 packets) | reversible Mutations allowlist in `vcf_snv_helpers.php`; eligibility tables in `sql/vcf_snv_sample_meta.sql` | Broad/Portugal isolated pending Jim; JunctionGroupQuery unchanged |
| `2026-09-18_vcf-folder-merge-table/files/LTG_groups_091826_with_data.xlsx` (use this sheet, not 091726) | `scripts/import_vcf_snv_groups.py --from-sep17 --replace` (S Africa + Portugal merged); PASS∩VCF metadata includes `PRJEB47340` | Mutations allowlist adds South Africa MiSeq and Portugal NextSeq 550 |
| `2026-09-21_broad-usa-labels-argentina/` (email only) | Un-isolate Broad PASS∩VCF; USA display labels; Argentina from existing metadata (VCF ZIP still missing) | `MutationsSearch.php`; Junction Group Query waits for Jim’s files |
| `2026-09-21_india-argentina-merge-table/files/LTG_groups_092126_with_nj_data.xlsx` | Extra India ZIP + two Argentina VCF ZIPs (on Drive; not in Sep 16/17 local archives). Column H later. | `MutationsSearch.php` when those ZIPs are downloaded. |
| `2026-09-22_nj-reads-many-projects/files/Groups_092226_with_nj_data.xlsx` (replaces the 21 Sep sheet for membership) | NJ percent table from Drive `NJ_data` (`{base}{size}_coord.csv`); one analyzed count; `many #s` links. Estonia and Thailand lists in `files/`. | Built on `MutationsSearch.php` and pushed to `jtc240/njdb` on 23 Sep. Superseded for page placement by the next packet. |
| `2026-09-23_nj-on-junction-page/files/` (`Groups_092226_with_nj_data1.xlsx`, `NJ-table_no_types.csv`) | Move the NJ table to Junction groups; keep only rows in the new coordinate list; row 69 of size 2766 uses `{base}2766_1883_coord.csv`. | `JunctionGroupQuery.php`. Live on `jtc240/njdb` 23 Sep. |
| `2026-09-23_india-zips-nj-table-columns/` (email only) | Replace both India-6000 VCF zips from Drive (unzipped total 2347). NJ count table columns match the SNV detail table. | Drive copies matched the loaded zips. 2347 files, 2070 unique IDs. |
| `2026-09-23_nj-row-table-more-groups/files/` (`Groups_092326_with_nj_data.xlsx`, `LTG_bbmap_vir_080726_primer_17943_v3_v4.1.csv`) | Click an NJ row for a group × variant–primer table; drop Unassigned/Probable; Merge Delta checkbox. New groups sheet only. | Live on `jtc240/njdb` 23 Sep. New group zips were not in the drop. |
| `2026-09-24_junction-filter-merges/files/` (`junction_table1.xlsx`, `filter_groups.docx`) | Min samples 30; Merge Delta keeps other variants; Omicron/BA/XBB merges; checkboxes; sortable NJ average table. Zoom stills in `transcripts/stills/2026-09-24/`. Drive folder zips in `drive/`. | Live on `jtc240/njdb` 24 Sep. Storage answer and the Drive file check are in that packet’s `REQUEST.md`. |

## Next email

1. Put new files in this folder.
2. Paste the email in chat.
3. Agent creates `_incoming/jim-kelley/YYYY-MM-DD_short-slug/` with `REQUEST.md` and moves only the new files into `files/`.
