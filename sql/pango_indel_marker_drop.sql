-- Reverse sql/pango_indel_marker.sql without touching snv_pango_marker or mutations.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `pango_indel_marker`;
SET FOREIGN_KEY_CHECKS=1;
