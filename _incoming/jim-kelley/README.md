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
| `2026-09-25_three-filter-tables/` (email and Zoom only; no new spreadsheet) | Three Filter Groups tables; BA.1 column; Type is Omi- for Alpha or Delta; `Omi-,BA.1`; long LTG when BA.2–5 and XBB are both present. Submit button. Transcripts in `transcripts/2026-09-25_My_Meeting_*.txt`. | Live on `jtc240/njdb` 25 Sep. Angola stays zero: `run_metadata.v05-PRJNA782796.csv` does not match the loaded VCF sample IDs. |
| `2026-09-28_long-ltg-angola/` (email only) | long LTG must have Omi- and Omi+ (BA.2–5 and XBB); Omi+ with both later groups is long Omi+. | Live on `jtc240/njdb` 28 Sep. |
| `2026-09-28_angola-primer-project/files/` (`Groups_092826_with_nj_data.xlsx`, `run_metadata.v05-PRJNA717113.csv`) | Replaces the 23 Sep groups sheet. Angola project is PRJNA717113. Primer CSV copied from Drive. Botswana stays PRJNA782796. | Live on `jtc240/njdb` 28 Sep. Angola filter row is Omi-. Superseded as the groups sheet by the next packet. |
| `2026-09-28_early-column-filter-bugs/files/` (`Groups_092826_with_nj_data-early.xlsx`, `2020-2021_samples` zip, Slovakia `no_Switz` SRA) | Replaces the noon sheet. Early column, Slovakia without Switzerland, Botswana from the existing SNV zip, dropdown fixes and Select all. | The 28 Sep zip is 20 lists. On 29 Sep Jim added Australia NextSeq 500 and 550, Russia HiSeq, USA NM, and USA VA to the same Drive folder. See the next packet. |
| `2026-09-29_pair-columns-and-csv/files/` (five Early lists) | Pair table: drop Alpha, add V5, VarSkip, Midnight. Hide plus-mutation labels when major variants is on. Junction CSV link, unique filenames, one CSV per checked junction. Load NM, VA, both Australia groups, and the five Early lists. | Live 29 Sep. Estonia Early list is still not ready. Russia stays hidden. |
| `2026-09-29_pair-ba-split/` (email only) | Option to show BA.2 and BA.5 instead of BA.2–5 on the pair table. Later, pair columns should follow the merge checkboxes and the variant–primer dropdown. “.” (no variant call) is also Early. | Live 29 Sep. Minimum stays 30. Estonia is low priority. The red R beside the emailed link is the Rutgers icon, not a page bug. |
| `2026-09-30_la-nextseq-pca/files/PCA_test1.docx` | Where is LA NextSeq 500? His PCA test used min 20, Argentina, Portugal NextSeq 550, LA MiSeq, NJ, NM, VA, Merge Delta and XBB, top 30 junctions by Overall. | Live 30 Sep. Group `USA LA NextSeq 500 PRJNA815364`. The bottom charts wait for a Zoom. |
| `2026-09-30_la-miseq-sra/files/SraRunTable-PRJNA815364-LA_103024-MiSeq.csv` | The LA MiSeq SRA is on Drive in `SRA_run_tables`, beside the NextSeq 500 SRA. | Built 30 Sep. USA LA MiSeq is the 892 samples on that SRA that have a VCF. 17 SRA runs have no VCF. |
| `2026-09-30_early-definition/` (email only) | Early is a 2020–2021 list sample whose variant is “.” or blank. LA MiSeq has no list, so Early is 0. `SRR18430075` is BA.1 and PASS. | Built 30 Sep. “.” off the list is ignored. A listed sample with a called variant is not Early. |
| `2026-09-30_junction-download-names/files/` (`del_coord_dupl_sizes.csv`, `del_coord5-dupl_sizes.csv`) | Same-size junctions need distinct download names (`2766_1883` for the less common one). Summary CSV should name the group. Show the breakdown table only when one junction is checked. | Built 30 Sep. USA NJ on first open stays the default. |
| `2026-09-30_la-download-which-group/` (email only) | Which LA group is in a download from yesterday. Attached name `17943_Arg_Port550_LA_NJ_NM_VA`. | Superseded by the next packet. He downloaded it 29 Sep at 3:35 PM, before the split. |
| `2026-09-30_la-miseq-remove/` (email only) | Remove USA LA MiSeq if its called-variant columns are under 30. His file has percents for Delta V3, BA.1 V3, and BA.2 V4.1. | Not built. Those percents are the samples now on USA LA NextSeq 500 (1020, 229, 259 with the N-gene filter). Current MiSeq columns are all -1. |
| `2026-09-30_pair-columns-missing/` (email only) | Delta V4.1, BA.5 V3, XBB V3, Midnight, and VarSkip look missing. The table is for finding groups that can compare a variant on each primer. | Built 30 Sep. The filter table keeps every column. The checklist only changes the junction table and the download. |
| `2026-10-01_two-segment-labels/` (email only) | Remove illustrative rows on the two-segment page. Capitalize repeats, write U as T, and say sgRNA. | Live on `jtc240/njdb` 1 Oct. The three Jim CSV rows are hidden. Gene rows stay, with DNA capitals. Nav label is sgRNA. |
| `2026-10-01_pair-table-csv/` (email only) | CSV download for the variant–primer filter table. PCA test 2: min 20, LA NextSeq 500, NJ, NM, VA, merge Delta and XBB, export every junction. | Live on `jtc240/njdb` 1 Oct. Download link on the filter table that is showing. PCA test 2 is a use case only. |
| `2026-10-01_pair-header-scroll/` (email only) | The variant–primer header changes while scrolling. Captures named start and end were not in the inbox. | Live on `jtc240/njdb` 1 Oct. The two header rows stick together. |
| `2026-10-07_snv-primer-pair-compare/` (email only; reply same day) | One group, two variant–primer pairs. Columns: SNV name, % in each pair, Rel. % Diff. Defaults stay editable (AF, min samples, min %). Example is NJ Delta V3 vs Delta V4.1. | Live on `jtc240/njdb` 7 Oct. |

## Next email

1. Put new files in this folder.
2. Paste the email in chat.
3. Agent creates `_incoming/jim-kelley/YYYY-MM-DD_short-slug/` with `REQUEST.md` and moves only the new files into `files/`.
