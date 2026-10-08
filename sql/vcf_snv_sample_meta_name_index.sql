-- Speeds the Early count, which joins the 2020-2021 list to sample metadata by name.
-- Reverse: ALTER TABLE vcf_snv_sample_meta DROP INDEX idx_vcf_snv_sample_meta_group_name;

ALTER TABLE `vcf_snv_sample_meta`
  ADD INDEX `idx_vcf_snv_sample_meta_group_name` (`group_id`, `sample_name`);
