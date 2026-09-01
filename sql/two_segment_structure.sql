-- Two-segment genomic structures (generalized model; first subtype: sgmRNA).
-- Apply to the same database as connection.php (e.g. app_sarsntdb).
-- Intended to run once; re-running INSERTs will duplicate demo rows unless you truncate first.

CREATE TABLE IF NOT EXISTS `two_segment_structure` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subtype` varchar(32) NOT NULL DEFAULT 'sgmRNA',
  `junction_kind` varchar(3) NOT NULL DEFAULT 'CJ' COMMENT 'CJ=canonical junction, NJ=non-canonical junction',
  `name` varchar(255) NOT NULL,
  `coord_from` int(10) NOT NULL COMMENT 'Start of first segment (1-based genome coordinate)',
  `coord_left` int(10) NOT NULL COMMENT 'End of first segment',
  `coord_right` int(10) NOT NULL COMMENT 'Start of second segment',
  `coord_to` int(10) NOT NULL COMMENT 'End of second segment',
  `repeat_seq` varchar(255) DEFAULT NULL,
  `link_url` varchar(512) DEFAULT NULL COMMENT 'Reserved for future use',
  `notes` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_subtype_order` (`subtype`, `display_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo rows: body start (coord_right) matches Gene_1 Start in SARSNTDB reference data.
-- repeat_seq values are short junction-related motifs quoted in Gene_2 text in SARS.sql (illustrative).
INSERT INTO `two_segment_structure`
  (`subtype`, `junction_kind`, `name`, `coord_from`, `coord_left`, `coord_right`, `coord_to`, `repeat_seq`, `link_url`, `notes`, `display_order`)
VALUES
  ('sgmRNA', 'CJ', 'sgmRNA — S (body start 21563)', 1, 70, 21563, 29903, 'acgaac', NULL,
   'Demo: leader ~1–70, body from S gene start per Gene_1 in repo.', 10),
  ('sgmRNA', 'CJ', 'sgmRNA — ORF3a (body start 25393)', 1, 70, 25393, 29903, 'acgaacuu', NULL,
   'Demo: illustrative TRS/body layout; confirm with literature.', 20),
  ('sgmRNA', 'CJ', 'sgmRNA — E (body start 26245)', 1, 70, 26245, 29903, 'acgaac', NULL,
   'Demo: E gene body start per Gene_1.', 30),
  ('sgmRNA', 'CJ', 'sgmRNA — M (body start 26523)', 1, 70, 26523, 29903, 'acgaacuaaa', NULL,
   'Demo: motif fragment from Gene_2 M junction text.', 40),
  ('sgmRNA', 'CJ', 'sgmRNA — N (body start 28274)', 1, 70, 28274, 29903, 'acucaugcag', NULL,
   'Demo: motif fragment from Gene_2 N junction text.', 50),
  ('sgmRNA', 'NJ', 'NJ demo (non-canonical; right flank near S TRS)', 1, 70, 21558, 21570, 'acgaac', NULL,
   'Demo NJ row: coord_right inside a narrow window around the S canonical junction for interval testing.', 15);
