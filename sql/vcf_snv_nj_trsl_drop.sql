-- Removes the TRS-L dependent junctions loaded by: python scripts/import_vcf_nj_reads.py --trsl-root <NJ_data_TRS_L folder>
DELETE FROM `vcf_snv_nj_read` WHERE coord_tag LIKE 'L%';
DELETE FROM `vcf_snv_nj_catalog` WHERE kind = 'tl';
