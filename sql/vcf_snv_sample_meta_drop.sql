-- Reverse sql/vcf_snv_sample_meta.sql. Leaves vcf_snv_group/sample/call in place.

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `vcf_snv_sample_eligibility`;
DROP TABLE IF EXISTS `vcf_snv_sample_meta`;
SET FOREIGN_KEY_CHECKS=1;
