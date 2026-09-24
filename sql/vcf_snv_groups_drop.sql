-- Reverse sql/vcf_snv_groups.sql without touching John's mutations table.

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `vcf_snv_sample_eligibility`;
DROP TABLE IF EXISTS `vcf_snv_sample_meta`;
DROP TABLE IF EXISTS `vcf_snv_call`;
DROP TABLE IF EXISTS `vcf_snv_sample`;
DROP TABLE IF EXISTS `vcf_snv_group`;
SET FOREIGN_KEY_CHECKS=1;
