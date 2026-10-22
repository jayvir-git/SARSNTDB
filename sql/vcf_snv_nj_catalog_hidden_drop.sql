-- Reverses sql/vcf_snv_nj_catalog_hidden.sql: every junction row shows again.
ALTER TABLE `vcf_snv_nj_catalog` DROP COLUMN IF EXISTS `hidden`;
