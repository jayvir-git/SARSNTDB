-- Samples with no junction reads in any _coord file were not really analyzed (Jim, email 2026-10-19):
-- leave them out everywhere, even with the N-gene filter off. Marked, not deleted.
-- Applies only to groups that have junction reads loaded. Importers re-run this after loading.
-- Reverse with sql/vcf_snv_no_nj_reads_drop.sql
UPDATE `vcf_snv_sample_eligibility` e
  JOIN `vcf_snv_sample` s ON s.id = e.sample_id
   SET e.eligible = 0, e.exclusion_reason = 'no_nj_reads'
 WHERE e.eligible = 1
   AND EXISTS (SELECT 1 FROM `vcf_snv_nj_read` g WHERE g.group_id = s.group_id)
   AND NOT EXISTS (SELECT 1 FROM `vcf_snv_nj_read` r
                    WHERE r.group_id = s.group_id AND r.sample_name = s.sample_name AND r.read_count > 0);
