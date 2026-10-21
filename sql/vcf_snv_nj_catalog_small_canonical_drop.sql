-- Removes the small deletions and canonical junctions that import_vcf_nj_reads.py adds from Jim's lists (D-80).
-- Small deletions are the only Click a Junction rows under size 100; canonical rows are kind cj. 28190 stays: the
-- N-gene filter needs its reads. No coordinates here: they are unpublished and this repo is public.
DELETE r FROM `vcf_snv_nj_read` r
  JOIN `vcf_snv_nj_catalog` c ON c.nj_size = r.nj_size AND c.coord_tag = r.coord_tag
 WHERE (c.kind = 'nj' AND c.nj_size < 100) OR (c.kind = 'cj' AND c.nj_size <> 28190);
DELETE FROM `vcf_snv_nj_catalog` WHERE (kind = 'nj' AND nj_size < 100) OR (kind = 'cj' AND nj_size <> 28190);
UPDATE `vcf_snv_nj_catalog` SET nj_start = NULL, nj_end = NULL WHERE nj_size = 28190 AND coord_tag = '';
