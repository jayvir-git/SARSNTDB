-- Optional: add this NJ to the existing two-segment schematic table.
-- Safe to re-run; skips if 5249–23191 is already present.
-- Reverse with sql/junction_query_drop.sql (the DELETE at the bottom).

INSERT INTO `two_segment_structure`
  (`subtype`, `junction_kind`, `name`, `coord_from`, `coord_left`, `coord_right`, `coord_to`, `repeat_seq`, `link_url`, `notes`, `display_order`)
SELECT 'sgmRNA', 'NJ', 'NJ 5249–23191 (size 17943)', 1, 5249, 23191, 29903, NULL,
       'JunctionGroupQuery.php?left=5249&right=23191',
       'Jim Kelley junction query prototype: group/variant/primer percents for this NJ.', 90
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `two_segment_structure`
  WHERE `coord_left` = 5249 AND `coord_right` = 23191
);
