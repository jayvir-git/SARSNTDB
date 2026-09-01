-- Reverse the junction group/variant/primer prototype.
-- Import this after you no longer want the feature.

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `junction_viridian_pair`;
DROP TABLE IF EXISTS `junction_viridian_variant`;
DROP TABLE IF EXISTS `junction_viridian_group`;
DROP TABLE IF EXISTS `junction_query_measure`;
DROP TABLE IF EXISTS `junction_query_group`;
DROP TABLE IF EXISTS `junction_query_dataset`;
SET FOREIGN_KEY_CHECKS=1;

-- Optional NJ schematic row added by sql/junction_query_two_segment.sql
-- Skip this statement if two_segment_structure does not exist.
DELETE FROM `two_segment_structure`
 WHERE `notes` LIKE 'Jim Kelley junction query prototype%';
