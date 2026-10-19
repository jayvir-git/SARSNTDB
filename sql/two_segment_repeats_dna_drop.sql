-- Reverses sql/two_segment_repeats_dna.sql: restores the three seed repeats that were written with U.
UPDATE `two_segment_structure` SET `repeat_seq` = 'acgaacuu'   WHERE `repeat_seq` = 'acgaactt'   AND `coord_right` = 25393;
UPDATE `two_segment_structure` SET `repeat_seq` = 'acgaacuaaa' WHERE `repeat_seq` = 'acgaactaaa' AND `coord_right` = 26523;
UPDATE `two_segment_structure` SET `repeat_seq` = 'acucaugcag' WHERE `repeat_seq` = 'actcatgcag' AND `coord_right` = 28274;
