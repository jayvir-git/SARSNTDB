-- Store junction repeats as DNA: U becomes T (Jim, email 2026-10-19).
-- Reverse with sql/two_segment_repeats_dna_drop.sql
UPDATE `two_segment_structure`
   SET `repeat_seq` = REPLACE(REPLACE(`repeat_seq`, 'u', 't'), 'U', 'T')
 WHERE `repeat_seq` LIKE BINARY '%u%' OR `repeat_seq` LIKE BINARY '%U%';
