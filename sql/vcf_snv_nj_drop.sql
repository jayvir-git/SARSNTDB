-- Reverse sql/vcf_snv_nj.sql. Does not change vcf_snv_sample_eligibility.

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `vcf_snv_sra_sample`;
DROP TABLE IF EXISTS `vcf_snv_nj_read`;
DROP TABLE IF EXISTS `vcf_snv_nj_sample_list`;
DROP TABLE IF EXISTS `vcf_snv_nj_catalog`;
