<?php
/**
 * Visibility and PASS∩VCF helpers (no database).
 */
require_once dirname(__DIR__) . '/vcf_snv_helpers.php';

$failed = 0;
function expect($cond, $msg)
{
    global $failed;
    if (!$cond) {
        echo "FAIL $msg\n";
        $failed++;
        return;
    }
    echo "ok   $msg\n";
}

expect(vcf_snv_hide_non_workbook_groups() === true, 'temporary hide is on');
expect(vcf_snv_group_is_visible('original') === true, 'Original stays visible');
expect(vcf_snv_group_is_visible('NJ-PRJNA708324') === true, 'NJ workbook group visible');
expect(vcf_snv_group_is_visible('S_Afr-PRJNA636748') === true, 'South Africa visible');
expect(vcf_snv_group_is_visible('Port-PRJEB47340') === true, 'Portugal visible');
expect(vcf_snv_group_is_isolated('Port-PRJEB47340') === false, 'Portugal is not isolated');
expect(vcf_snv_group_is_visible('PRJNA622837-Broad_Inst') === true, 'Broad stays visible');
expect(vcf_snv_group_is_isolated('PRJNA622837-Broad_Inst') === false, 'Broad uses PASS∩VCF');
expect(vcf_snv_group_is_isolated('PAK_iseq') === false, 'Pakistan is not isolated');
expect(vcf_snv_group_is_visible('PRJEB46220-Argentina') === true, 'Argentina visible');
expect(vcf_snv_group_is_visible('Angola_miseq') === true, 'Angola visible');
expect(vcf_snv_group_is_visible('India-miseq') === true, 'India MiSeq visible');
expect(vcf_snv_group_is_visible('Port_miseq') === true, 'Portugal MiSeq visible');
expect(vcf_snv_group_is_visible('Russia682735') === false, 'Russia hidden');
expect(vcf_snv_group_is_visible('Thailand_mix') === true, 'Thailand visible');
expect(in_array('PRJNA622837-Broad_Inst', vcf_snv_workbook_visible_codes(), true), 'Broad is on the allowlist');
expect(in_array('PRJEB46220-Argentina', vcf_snv_workbook_visible_codes(), true), 'Argentina is on the allowlist');
expect(vcf_snv_isolated_group_codes() === [], 'no groups are isolated');
expect(vcf_snv_group_display_label('NJ-PRJNA708324', 'NJ PRJNA708324') === 'USA NJ MiSeq PRJNA708324', 'NJ label includes MiSeq');
expect(vcf_snv_group_display_label('PRJNA622837-Broad_Inst', 'Broad Institute') === 'USA NE, NJ NovaSeq 6000 PRJNA622837', 'Broad label includes NovaSeq');
expect(vcf_snv_group_display_label('LA-PRJNA815364', 'LA PRJNA815364') === 'USA LA MiSeq PRJNA815364', 'LA label includes MiSeq');
expect(vcf_snv_group_display_label('PRJEB46220-Argentina', '') === 'Argentina MiSeq PRJEB46220', 'Argentina label includes MiSeq');
expect(vcf_snv_group_display_label('PAK_iseq', 'Pakistan iSeq') === 'Pakistan iSeq 100 PRJNA764553', 'Pakistan label includes iSeq');
expect(vcf_snv_group_display_label('illumina_miseq', 'Illumina MiSeq') === 'United Kingdom MiSeq PRJEB37886', 'UK MiSeq label');
expect(vcf_snv_group_display_label('Thailand_mix', '') === 'Thailand many #s', 'Thailand many projects label');

expect(vcf_snv_eligibility_reason('PASS', true) === '', 'PASS and VCF eligible');
expect(vcf_snv_sample_is_eligible('PASS', true) === true, 'sample_is_eligible PASS');
expect(vcf_snv_eligibility_reason('FAIL_QC', true) === 'fail_qc', 'FAIL_QC excluded');
expect(vcf_snv_eligibility_reason(null, true) === 'unmatched', 'unmatched ID');
expect(vcf_snv_eligibility_reason('', true) === 'unmatched', 'empty QC unmatched');
expect(vcf_snv_eligibility_reason('PASS', false) === 'no_vcf', 'no VCF excluded');
expect(vcf_snv_sample_is_eligible('PASS', false) === false, 'PASS without VCF ineligible');

$menu = vcf_snv_group_menu_label([
    'label' => 'Pakistan PRJNA764553',
    'sample_count' => 780,
    'vcf_sample_count' => 808,
    'isolated' => false,
    'eligibility' => ['vcf' => 808, 'in_meta' => 808, 'eligible' => 780, 'unmatched' => 0, 'fail_qc' => 28],
]);
expect($menu === 'Pakistan PRJNA764553', "menu label is the group name ($menu)");

$broad = vcf_snv_group_menu_label([
    'label' => 'USA NE, NJ PRJNA622837',
    'sample_count' => 2000,
    'vcf_sample_count' => 2437,
    'isolated' => false,
    'eligibility' => ['vcf' => 2437, 'in_meta' => 2300, 'eligible' => 2000, 'unmatched' => 100, 'fail_qc' => 200],
]);
expect($broad === 'USA NE, NJ PRJNA622837', "Broad menu is the group name ($broad)");
expect(strpos($broad, 'PASS') === false, 'menu omits the analyzed fraction');

$counts = vcf_snv_eligibility_counts_text([
    'vcf' => 808,
    'in_meta' => 808,
    'eligible' => 780,
    'unmatched' => 0,
    'fail_qc' => 28,
]);
expect(strpos($counts, 'PASS∩VCF=780') !== false, "counts text $counts");
expect(vcf_snv_eligibility_counts_text(null) === '', 'null counts empty');

if ($failed) {
    echo "\n$failed failed\n";
    exit(1);
}
echo "\nall passed\n";
