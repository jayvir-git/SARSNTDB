-- Reverses sql/vcf_snv_no_nj_reads.sql.
UPDATE `vcf_snv_sample_eligibility` SET eligible = 1, exclusion_reason = NULL WHERE exclusion_reason = 'no_nj_reads';
