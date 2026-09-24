-- Per-sample QC/variant/primer metadata and PASS ∩ VCF eligibility.
-- Loaded by scripts/import_vcf_sample_metadata.py
-- Reverse with sql/vcf_snv_sample_meta_drop.sql
-- Does not delete vcf_snv_* sample/call rows.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `vcf_snv_sample_meta` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sample_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `project_accession` varchar(32) NOT NULL,
  `sample_name` varchar(128) NOT NULL,
  `variant_label` varchar(128) DEFAULT NULL,
  `primer_label` varchar(128) DEFAULT NULL,
  `qc` varchar(32) DEFAULT NULL,
  `source_file` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vcf_snv_sample_meta` (`sample_id`),
  KEY `idx_vcf_snv_sample_meta_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vcf_snv_sample_eligibility` (
  `sample_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `has_vcf` tinyint(1) NOT NULL DEFAULT 1,
  `qc` varchar(32) DEFAULT NULL,
  `eligible` tinyint(1) NOT NULL DEFAULT 0,
  `exclusion_reason` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`sample_id`),
  KEY `idx_vcf_snv_elig_group` (`group_id`,`eligible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
