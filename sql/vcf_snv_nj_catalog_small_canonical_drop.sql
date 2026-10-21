-- Reverses sql/vcf_snv_nj_catalog_small_canonical.sql. 28190 stays: the N-gene filter needs its reads.
DELETE FROM `vcf_snv_nj_read`
 WHERE (nj_size, coord_tag) IN ((0, '0'), (0, '0'), (0, '0'), (0, '0'), (0, '0'),
                                (0, '0'), (0, '0'), (0, '0'), (0, '0'),
                                (21486, ''), (25315, ''), (26167, ''), (26403, ''), (26971, ''), (27318, ''), (27818, ''));
DELETE FROM `vcf_snv_nj_catalog`
 WHERE (nj_size, coord_tag) IN ((0, '0'), (0, '0'), (0, '0'), (0, '0'), (0, '0'),
                                (0, '0'), (0, '0'), (0, '0'), (0, '0'),
                                (21486, ''), (25315, ''), (26167, ''), (26403, ''), (26971, ''), (27318, ''), (27818, ''));
UPDATE `vcf_snv_nj_catalog` SET nj_start = NULL, nj_end = NULL WHERE nj_size = 28190 AND coord_tag = '';
