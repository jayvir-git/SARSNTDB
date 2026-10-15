-- Adds a (group_id, sample_name, read_count) index on vcf_snv_nj_read so the
-- Junction groups hit counts (nj_read_junction_hits) read each eligible sample's
-- junction reads in one index range. Without it every read row needed lookups
-- into the sample, eligibility and metadata tables: 12-13 s on live with 10
-- groups checked, 42 s locally. The index holds the primary key columns too, so
-- the query reads only the index. No code depends on it.
-- Reverse with sql/vcf_snv_nj_read_sample_index_drop.sql

ALTER TABLE `vcf_snv_nj_read`
  ADD INDEX `idx_vcf_snv_nj_read_sample` (`group_id`, `sample_name`, `read_count`);
