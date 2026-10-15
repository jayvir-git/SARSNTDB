-- Widens the two vcf_snv_call indexes so the Mutations page VCF queries (Detail,
-- Summary, frequency chart) read only the index. They filter by group_id and
-- allele_frequency or coordinate, then need coordinate, reference, alternate and
-- sample_id (for the eligibility join). Without these columns every matching call
-- needed a primary-key lookup: 10-150 s per page on large groups.
-- Index names are unchanged. Reverse with sql/vcf_snv_call_covering_indexes_drop.sql

ALTER TABLE `vcf_snv_call`
  DROP INDEX `idx_vcf_snv_call_group_af`,
  ADD INDEX `idx_vcf_snv_call_group_af`
    (`group_id`, `allele_frequency`, `coordinate`, `reference`, `alternate`, `sample_id`),
  DROP INDEX `idx_vcf_snv_call_coord`,
  ADD INDEX `idx_vcf_snv_call_coord`
    (`group_id`, `coordinate`, `reference`, `alternate`, `allele_frequency`, `sample_id`);
