-- Reverse sql/primer_arrows.sql without touching Junction groups / Viridian tables.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `junction_primer`;
DROP TABLE IF EXISTS `junction_primer_scheme`;
SET FOREIGN_KEY_CHECKS=1;

-- Removes only NJ rows this import inserted. Pre-existing coordinate rows keep
-- their original notes, so this DELETE does not drop them even if repeats were updated.
DELETE FROM `two_segment_structure`
 WHERE `notes` LIKE 'Primer arrows import:%';
