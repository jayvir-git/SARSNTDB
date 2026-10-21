-- Small deletions join the Click a Junction list; canonical junctions get their own table (Jim, email 2026-10-21).
-- Small deletions: Jim's small_del.csv (size, start, end). Their coord files always carry the start: {base}{size}_{start}_coord.csv.
-- Canonical junctions: Jim's del_coord_cjs.csv (start, size, end). Their coord files are named by the start: {base}{start}_coord.csv,
-- so nj_size holds the start, as it already did for 28190 (the N-gene sgRNA junction the filter normalises by).
-- import_vcf_nj_reads.py re-applies this after rebuilding the catalog. Reverse with sql/vcf_snv_nj_catalog_small_canonical_drop.sql
INSERT INTO `vcf_snv_nj_catalog` (nj_size, nj_start, nj_end, coord_tag, kind) VALUES
  (50, 11565, 11614, '11565', 'nj'),
  (53, 2443, 2495, '2443', 'nj'),
  (54, 29011, 29064, '29011', 'nj'),
  (57, 7049, 7105, '7049', 'nj'),
  (64, 20275, 20338, '20275', 'nj'),
  (69, 27081, 27149, '27081', 'nj'),
  (77, 19749, 19825, '19749', 'nj'),
  (81, 23854, 23934, '23854', 'nj'),
  (97, 7334, 7430, '7334', 'nj'),
  (21486, 21486, 21551, '', 'cj'),
  (25315, 25315, 25381, '', 'cj'),
  (26167, 26167, 26236, '', 'cj'),
  (26403, 26403, 26467, '', 'cj'),
  (26971, 26971, 27040, '', 'cj'),
  (27318, 27318, 27384, '', 'cj'),
  (27818, 27818, 27883, '', 'cj'),
  (28190, 28190, 28254, '', 'cj')
ON DUPLICATE KEY UPDATE nj_start = VALUES(nj_start), nj_end = VALUES(nj_end), kind = VALUES(kind);
