-- Junction rows Jim asks to remove are hidden, not deleted (CLAUDE.md standing rule; D-83).
-- nj_read_catalog_rows() skips hidden rows, so they leave the junction tables, summaries and downloads.
-- Which rows are hidden comes from a gitignored packet list (junction sizes are unpublished), applied by
-- scripts/import_vcf_nj_reads.py --trsl-hide. Reverse with sql/vcf_snv_nj_catalog_hidden_drop.sql
ALTER TABLE `vcf_snv_nj_catalog` ADD COLUMN IF NOT EXISTS `hidden` tinyint(1) NOT NULL DEFAULT 0 AFTER `kind`;
