-- NJ read counts, deletion sample lists, and the LA SRA allowlist.
-- Loaded by scripts/import_vcf_nj_reads.py
-- Reverse with sql/vcf_snv_nj_drop.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `vcf_snv_nj_catalog` (
  `nj_size` int(11) NOT NULL,
  `nj_start` int(11) DEFAULT NULL,
  `nj_end` int(11) DEFAULT NULL,
  `coord_tag` varchar(16) NOT NULL DEFAULT '',
  `kind` varchar(8) NOT NULL DEFAULT 'nj',
  PRIMARY KEY (`nj_size`, `coord_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vcf_snv_nj_sample_list` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `sample_name` varchar(128) NOT NULL,
  PRIMARY KEY (`group_id`,`sample_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vcf_snv_nj_read` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `sample_name` varchar(128) NOT NULL,
  `nj_size` int(11) NOT NULL,
  `coord_tag` varchar(16) NOT NULL DEFAULT '',
  `read_count` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`group_id`,`nj_size`,`coord_tag`,`sample_name`),
  KEY `idx_vcf_snv_nj_read_count` (`group_id`,`nj_size`,`coord_tag`,`read_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vcf_snv_sra_sample` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `sample_name` varchar(128) NOT NULL,
  PRIMARY KEY (`group_id`,`sample_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
