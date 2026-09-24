-- Per-sample VCF SNVs from Jim Kelley (GROM pad-bwa, POS minus 6000, FORMAT AF).
-- Generated/loaded by scripts/import_vcf_snv_groups.py
-- Reverse with sql/vcf_snv_groups_drop.sql
-- Does not replace John's mutations table.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `vcf_snv_group` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `label` varchar(128) NOT NULL,
  `sample_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `call_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vcf_snv_group_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vcf_snv_sample` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(10) UNSIGNED NOT NULL,
  `sample_name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vcf_snv_sample` (`group_id`,`sample_name`),
  KEY `idx_vcf_snv_sample_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vcf_snv_call` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(10) UNSIGNED NOT NULL,
  `sample_id` int(10) UNSIGNED NOT NULL,
  `coordinate` int(11) NOT NULL,
  `reference` char(1) NOT NULL,
  `alternate` char(1) NOT NULL,
  `allele_frequency` double NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vcf_snv_call` (`sample_id`,`coordinate`,`reference`,`alternate`),
  KEY `idx_vcf_snv_call_group_af` (`group_id`,`allele_frequency`,`coordinate`),
  KEY `idx_vcf_snv_call_coord` (`group_id`,`coordinate`,`reference`,`alternate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
