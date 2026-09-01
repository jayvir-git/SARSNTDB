-- Illustrative two_segment_structure rows from Jim Kelley CSV (non-canonical junctions).
-- Apply to app_sarsntdb (same DB as connection.php).
--
-- Jim Kelley convention (NJ): these do NOT use the 1–70 leader model.
--   coord_from = 1, coord_left = Coord1   → left blue bar: genome start … left end of junction
--   coord_right = Coord2, coord_to = 29903 → right blue bar: right end of junction … genome end
--   Gap in the schematic = junction / repeat span (Coord1 … Coord2); repeat_seq = CSV Repeat
--
-- If you previously imported the old (wrong) 1–70 leader version, delete those rows first:

DELETE FROM two_segment_structure WHERE notes LIKE 'Jim Kelley CSV%';

INSERT INTO `two_segment_structure`
  (`subtype`, `junction_kind`, `name`, `coord_from`, `coord_left`, `coord_right`, `coord_to`, `repeat_seq`, `link_url`, `notes`, `display_order`)
VALUES
  ('sgmRNA', 'NJ', 'Jim CSV — ACACAGA (24296–27142) [illustrative]', 1, 24296, 27142, 29903, 'acacaga', NULL,
   'Jim Kelley CSV NJ layout: seg1 1..Coord1, seg2 Coord2..29903; gap = junction span.', 100),
  ('sgmRNA', 'NJ', 'Jim CSV — TGGTGAT (20748–20994) [illustrative]', 1, 20748, 20994, 29903, 'tggtgat', NULL,
   'Jim Kelley CSV NJ layout: seg1 1..Coord1, seg2 Coord2..29903; gap = junction span.', 101),
  ('sgmRNA', 'NJ', 'Jim CSV — ATCAAATT (6068–6328) [illustrative]', 1, 6068, 6328, 29903, 'atcaaatt', NULL,
   'Jim Kelley CSV NJ layout: seg1 1..Coord1, seg2 Coord2..29903; gap = junction span.', 102);
