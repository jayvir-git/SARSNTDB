-- 2020–2021 sample IDs for the Early column on the variant filter table.
-- Loaded by scripts/import_vcf_early_samples.py
-- Reverse with sql/vcf_snv_early_sample_drop.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `vcf_snv_early_sample` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `sample_name` varchar(128) NOT NULL,
  PRIMARY KEY (`group_id`, `sample_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
