<?php
/**
 * Merge, minimum-sample, and group-type rules. No database.
 */
require_once dirname(__DIR__) . '/nj_read_helpers.php';

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

$major = [
    'major_only' => true,
    'min_samples' => 30,
    'merge_delta' => true,
];
expect(nj_read_variant_key('Alpha (B.1.1.7-like)', $major) === 'Alpha (B.1.1.7-like)', 'major list keeps Alpha');
expect(nj_read_variant_key('Delta (AY.4-like)', $major) === 'Delta', 'major list folds Delta');
expect(nj_read_variant_key('Beta (B.1.351-like)', $major) === null, 'major list drops Beta');
expect(nj_read_variant_key('.', $major) === null, 'no variant call is ignored');
expect(nj_read_variant_key('Omicron (BA.1-like)', ['hide_ba1' => true, 'major_only' => true]) === null, 'Hide BA.1 drops BA.1');

$omi = ['merge_omicron' => true, 'merge_ba' => true, 'merge_xbb' => true, 'major_only' => true];
expect(nj_read_variant_key('Omicron (BA.2-like)', $omi) === 'Omicron', 'Merge Omicron folds BA.2');
expect(nj_read_variant_key('Omicron (XBB.1-like)', $omi) === 'Omicron', 'Merge Omicron folds XBB');
expect(nj_read_variant_key('Omicron (BA.1-like)', $omi) === 'Omicron (BA.1-like)', 'Merge Omicron leaves BA.1');
expect(nj_read_coerce_merge($omi)['merge_ba'] === false, 'Merge Omicron turns off Merge BA');
expect(nj_read_coerce_merge($omi)['merge_xbb'] === false, 'Merge Omicron turns off Merge XBB');

$split = ['merge_ba' => true, 'merge_xbb' => true, 'major_only' => true];
expect(nj_read_variant_key('Omicron (BA.5-like)', $split) === 'Omicron BA', 'Merge BA folds BA.5');
expect(nj_read_variant_key('Omicron (XBB-like)', $split) === 'XBB', 'Merge XBB folds XBB');
expect(nj_read_variant_key('Omicron (BA.2-like)', $split) !== 'XBB', 'Merge XBB leaves BA.2');

$raw = [
    ['group_id' => 1, 'variant_label' => 'Delta (AY.4.2-like)', 'primer_label' => 'COVID-ARTIC-V3', 'n_denom' => 1, 'n_num' => 1],
    ['group_id' => 1, 'variant_label' => 'Delta (B.1.617.2-like)', 'primer_label' => 'COVID-ARTIC-V3', 'n_denom' => 40, 'n_num' => 10],
    ['group_id' => 1, 'variant_label' => 'Alpha (B.1.1.7-like)', 'primer_label' => 'COVID-ARTIC-V3', 'n_denom' => 30, 'n_num' => 0],
];
$foldOpt = ['merge_delta' => true, 'min_samples' => 30];
$folded = nj_read_fold_breakdown($raw, ['Delta|COVID-ARTIC-V3', 'Alpha (B.1.1.7-like)|COVID-ARTIC-V3'], 'pair', $foldOpt);
expect($folded["1\tDelta|COVID-ARTIC-V3"]['d'] === 40, 'below-min Delta subtype stays out of the merge');
expect($folded["1\tDelta|COVID-ARTIC-V3"]['n'] === 10, 'merged numerator uses the large subtype');
expect($folded["1\tAlpha (B.1.1.7-like)|COVID-ARTIC-V3"]['n'] === 0, 'Alpha zero junction count is kept');
expect(nj_read_breakdown_display(0, 30, 30, false) === '0.00', 'enough samples and zero junctions is 0');
expect(nj_read_breakdown_display(1, 4, 30, false) === '-1', 'under the minimum is -1');
expect(nj_read_breakdown_display(1, 4, 30, true) === '25.00', 'show-below displays the percent');

$counts = [
    'Alpha (B.1.1.7-like)' => 40,
    'Delta (B.1.617.2-like)' => 50,
    'Omicron (BA.5-like)' => 30,
    'Omicron (XBB-like)' => 4,
    'Omicron (BA.1-like)' => 80,
];
expect(nj_read_group_type($counts, 30) === 'LTG', 'Alpha Delta and BA.5 is LTG');
expect(nj_read_group_type([
    'Alpha (B.1.1.7-like)' => 40,
    'Delta (B.1.617.2-like)' => 50,
    'Omicron (BA.1-like)' => 90,
], 30) === 'Omni−', 'BA.1 does not count as Omicron for Type');
expect(nj_read_group_type([
    'Omicron (BA.2-like)' => 40,
    'Alpha (B.1.1.7-like)' => 4,
], 30) === 'Omni+', 'a small Alpha does not change Type');
expect(nj_read_count_text([1, 40], 30, false) === '40', 'count text drops the small part');
expect(nj_read_count_text([4], 30, false) === '-1', 'only a small count is -1');
expect(nj_read_count_text([0], 30, false) === '0', 'a real zero count stays 0');
expect(nj_read_primer_abbrev('COVID-ARTIC-V4.1') === 'V4.1', 'V4.1 abbreviation');
expect(nj_read_primer_abbrev('COVID-MIDNIGHT-1200') === 'Mid', 'Midnight abbreviation');
expect(nj_read_primer_abbrev('COVID-VARSKIP-V1a-2b') === 'Vsk', 'VarSkip abbreviation');
expect(nj_read_average_text([null, 0.0]) === '0.00', 'average of a real zero');
expect(nj_read_average_text([null, null]) === '-1', 'average with no groups is -1');
expect(nj_read_chart_group_names(['NJ-PRJNA708324']) === ['NJ MiSeq'], 'NJ maps to the workbook row');

if ($failed > 0) {
    echo "$failed failed\n";
    exit(1);
}
echo "all ok\n";
