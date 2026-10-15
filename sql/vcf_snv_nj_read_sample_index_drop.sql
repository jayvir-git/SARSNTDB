-- Reverses sql/vcf_snv_nj_read_sample_index.sql.

ALTER TABLE `vcf_snv_nj_read`
  DROP INDEX `idx_vcf_snv_nj_read_sample`;
