-- Reverses sql/vcf_snv_call_covering_indexes.sql: restores the original
-- definitions from sql/vcf_snv_groups.sql.

ALTER TABLE `vcf_snv_call`
  DROP INDEX `idx_vcf_snv_call_group_af`,
  ADD INDEX `idx_vcf_snv_call_group_af` (`group_id`, `allele_frequency`, `coordinate`),
  DROP INDEX `idx_vcf_snv_call_coord`,
  ADD INDEX `idx_vcf_snv_call_coord` (`group_id`, `coordinate`, `reference`, `alternate`);
