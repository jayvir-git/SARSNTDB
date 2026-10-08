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
expect(nj_read_variant_key('B.1.1.7-like+E484K', $major) === null, 'major list drops B.1.1.7 plus E484K');
expect(nj_read_variant_key('Delta (B.1.617.2-like) +K417N', $major) === null, 'major list drops Delta plus K417N');
expect(nj_read_variant_key('B.1.1.7-like+E484K', ['major_only' => false]) === 'B.1.1.7-like+E484K', 'E484K stays when major is off');
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
expect(nj_read_variant_key('Omicron (BA.5-like)', $split) === 'BA.2–5', 'Merge BA labels BA.2–5');
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
], 30) === 'Omi-,BA.1', 'Omi- with BA.1 is Omi-,BA.1');
expect(nj_read_group_type([
    'Delta (B.1.617.2-like)' => 36,
], 30) === 'Omi-', 'Delta without Alpha is Omi-');
expect(nj_read_group_type([
    'Delta (B.1.617.2-like)' => 50,
    'Omicron (BA.5-like)' => 40,
], 30) === 'LTG', 'Delta and BA.5 without Alpha is LTG');
expect(nj_read_group_type([
    'Delta (B.1.617.2-like)' => 64,
    'Omicron (BA.2-like)' => 95,
    'Omicron (XBB-like)' => 138,
], 30) === 'long LTG', 'early plus BA.2-5 and XBB is long LTG');
expect(nj_read_group_type([
    'Alpha (B.1.1.7-like)' => 40,
    'Omicron (XBB-like)' => 40,
], 30) === 'LTG', 'early plus XBB without BA.2-5 is LTG');
expect(nj_read_group_type([
    'Omicron (BA.2-like)' => 40,
    'Omicron (XBB-like)' => 40,
], 30) === 'long Omi+', 'BA.2-5 and XBB without Alpha or Delta is long Omi+');
expect(nj_read_group_type([
    'Omicron (BA.5-like)' => 40,
], 30) === 'Omi+', 'BA.2-5 alone is Omi+');
expect(nj_read_group_type([
    'Omicron (BA.2-like)' => 40,
    'Alpha (B.1.1.7-like)' => 4,
], 30) === 'Omi+', 'a small Alpha does not change Type');
expect(nj_read_count_text([1, 40], 30, false) === '40', 'count text drops the small part');
expect(nj_read_count_text([4], 30, false) === '-1', 'only a small count is -1');
expect(nj_read_count_text([0], 30, false) === '0', 'a real zero count stays 0');
expect(nj_read_primer_abbrev('COVID-ARTIC-V4.1') === 'V4.1', 'V4.1 abbreviation');
expect(nj_read_primer_abbrev('COVID-MIDNIGHT-1200') === 'Mid', 'Midnight abbreviation');
expect(nj_read_primer_abbrev('COVID-VARSKIP-V1a-2b') === 'Vsk', 'VarSkip abbreviation');
expect(nj_read_average_text([null, 0.0]) === '0.00', 'average of a real zero');
expect(nj_read_average_text([null, null]) === '-1', 'average with no groups is -1');
expect(nj_read_chart_group_names(['NJ-PRJNA708324']) === ['NJ MiSeq'], 'NJ maps to the workbook row');
$pairCols = nj_read_pair_filter_columns();
$pairHeads = [];
foreach ($pairCols as $pairCol) {
    $pairHeads[] = $pairCol['top'] . ' ' . $pairCol['sub'];
}
expect(!in_array('Alpha V3', $pairHeads, true), 'pair table has no Alpha column');
expect(in_array('Delta V5.0', $pairHeads, true), 'pair table has Delta V5');
expect(in_array('BA.2 Mid', $pairHeads, true), 'pair table splits BA.2 until Merge BA is checked');
expect(in_array('BA.5 V3', $pairHeads, true), 'pair table splits BA.5 until Merge BA is checked');
expect(!in_array('BA.2–5 Mid', $pairHeads, true), 'split pair table has no BA.2-5 column');
expect(in_array('XBB Vsk', $pairHeads, true), 'pair table has XBB VarSkip');
$mergedPair = nj_read_pair_filter_columns(['merge_ba' => true]);
$mergedHeads = [];
foreach ($mergedPair as $pairCol) {
    $mergedHeads[] = $pairCol['top'] . ' ' . $pairCol['sub'];
}
expect(in_array('BA.2–5 Mid', $mergedHeads, true) && !in_array('BA.2 Mid', $mergedHeads, true), 'Merge BA shows BA.2-5');
$hiddenBa1 = nj_read_pair_filter_columns(['hide_ba1' => true]);
$hiddenHeads = [];
foreach ($hiddenBa1 as $pairCol) {
    $hiddenHeads[] = $pairCol['top'];
}
expect(!in_array('BA.1', $hiddenHeads, true), 'Hide BA.1 drops those pair columns');
$omiPair = nj_read_pair_filter_columns(['merge_omicron' => true]);
$omiHeads = [];
foreach ($omiPair as $pairCol) {
    $omiHeads[] = $pairCol['top'];
}
expect(in_array('Omicron', $omiHeads, true) && !in_array('BA.2', $omiHeads, true) && !in_array('XBB', $omiHeads, true), 'Merge Omicron replaces BA.2, BA.5, and XBB');
$pickedPair = nj_read_pair_filter_columns([], ['Omicron (BA.2-like)|COVID-ARTIC-V3', 'Delta (AY.4-like)|COVID-ARTIC-V4.1']);
$pickedHeads = [];
foreach ($pickedPair as $pairCol) {
    $pickedHeads[] = $pairCol['top'] . ' ' . $pairCol['sub'];
}
expect($pickedHeads === ['Delta V4.1', 'BA.2 V3'], 'cleared variant-primer pairs drop those columns');
$splitDenoms = [
    'Omicron (BA.2-like)' => ['COVID-ARTIC-V3' => 40],
    'Omicron (BA.5-like)' => ['COVID-ARTIC-V3' => 12],
    'Omicron (BA.4-like)' => ['COVID-ARTIC-V3' => 9],
];
expect(nj_read_grouped_count($splitDenoms, ['ba'], ['COVID-ARTIC-V3'], ['min_samples' => 30], 'ba2') === '40', 'BA.2 column leaves out BA.5');
expect(nj_read_grouped_count($splitDenoms, ['ba'], ['COVID-ARTIC-V3'], ['min_samples' => 30], 'ba5') === '-1', 'BA.5 under the minimum is -1');
expect(nj_read_grouped_count($splitDenoms, ['ba'], ['COVID-ARTIC-V3'], ['min_samples' => 30, 'merge_ba' => true], 'ba') === '40', 'merged BA.2-5 keeps the large subtype');
expect(nj_read_junction_download_name(894, ['PRJEB46220-Argentina', 'Port-PRJEB47340', 'NJ-PRJNA708324'], false) === '894_Arg_Port550_NJ.csv', 'junction file uses size and short group names');
expect(nj_read_junction_download_name(2766, ['NJ-PRJNA708324'], false, '') === '2766_NJ.csv', 'common junction keeps the size-only name');
expect(nj_read_junction_download_name(2766, ['NJ-PRJNA708324'], false, '1883') === '2766_1883_NJ.csv', 'less common junction adds its start');
expect(nj_read_junction_download_name(118, ['NJ-PRJNA708324'], false, '29686') === '118_29686_NJ.csv', 'tagged copy of a shared size includes the start');
expect(nj_read_junction_download_name(0, ['NJ-PRJNA708324'], true) === 'junctions_NJ.zip', 'checked junctions download as a zip');
expect(nj_read_summary_download_name(['NJ-PRJNA708324']) === 'junction-summary_NJ.csv', 'summary file names the group');
expect(nj_read_summary_download_name(['PRJEB46220-Argentina', 'Port-PRJEB47340', 'NJ-PRJNA708324']) === 'junction-summary_Arg_Port550_NJ.csv', 'summary file names every selected group');
$zipBytes = nj_read_zip_bytes([
    '894_Arg_NJ.csv' => "Group,Alpha\nNJ,1\n",
    '102_Arg_NJ.csv' => "Group,Alpha\nNJ,2\n",
]);
expect(substr($zipBytes, 0, 4) === "PK\x03\x04", 'zip starts with a local file header');
expect(strpos($zipBytes, '894_Arg_NJ.csv') !== false && strpos($zipBytes, '102_Arg_NJ.csv') !== false, 'zip names each junction file');
expect(substr($zipBytes, -22, 4) === "PK\x05\x06", 'zip ends with the directory record');

$_GET = [];
$choices = [
    ['key' => 'Alpha|COVID-ARTIC-V3', 'label' => 'Alpha V3'],
    ['key' => 'Delta|COVID-ARTIC-V3', 'label' => 'Delta V3'],
];
expect(nj_read_request_columns($choices) === ['Alpha|COVID-ARTIC-V3', 'Delta|COVID-ARTIC-V3'], 'column list starts with every item selected');
$_GET = ['NjColSet' => '1', 'NjCol' => ['Delta|COVID-ARTIC-V3']];
expect(nj_read_request_columns($choices) === ['Delta|COVID-ARTIC-V3'], 'submitted column list is kept');
$_GET = ['NjColSet' => '1'];
expect(nj_read_request_columns($choices) === [], 'clearing every column stays clear');
$_GET = ['NjColSet' => '1', 'NjBy' => 'variant', 'NjBySig' => 'pair', 'NjCol' => ['Alpha|COVID-ARTIC-V3']];
expect(nj_read_request_columns($choices) === ['Alpha|COVID-ARTIC-V3', 'Delta|COVID-ARTIC-V3'], 'switching the filter table selects every column');
$_GET = ['NjColSet' => '1', 'NjBy' => 'pair', 'NjBySig' => 'pair', 'NjOptSig' => '000000', 'MergeBa' => '1', 'NjCol' => ['Alpha|COVID-ARTIC-V3']];
expect(nj_read_request_columns($choices) === ['Alpha|COVID-ARTIC-V3', 'Delta|COVID-ARTIC-V3'], 'changing a merge selects every column');
expect(nj_read_filter_group_codes(['A', 'B'], ['A'], "A\nB", '') === ['A', 'B'], 'a new filter-table selection checks every remaining group');
expect(nj_read_filter_group_codes(['A', 'B'], ['A'], "A\nB", "A\nB") === ['A'], 'an unchanged filter keeps the group checkboxes');
expect(nj_read_filter_group_codes(['A', 'B', 'C'], ['A'], '', '') === ['A'], 'no filter table selection keeps the default group');
$primerChoices = nj_read_breakdown_choices([
    ['variant' => 'Alpha (B.1.1.7-like)', 'primer' => '.'],
    ['variant' => 'Alpha (B.1.1.7-like)', 'primer' => 'COVID-ARTIC-V3'],
], 'primer', []);
expect(count($primerChoices) === 1 && $primerChoices[0]['key'] === 'COVID-ARTIC-V3', 'no-primer-call stays out of the primer list');
$_GET = [];

$baLabel = '';
foreach (nj_read_pair_filter_columns(['merge_ba' => true]) as $pairCol) {
    if ($pairCol['block'] === 'ba') {
        $baLabel = $pairCol['top'];
        break;
    }
}
$variantCols = nj_read_filter_columns('variant');
expect(isset($variantCols[3]['sub']) && $variantCols[3]['sub'] === $baLabel, 'variant filter uses the same BA.2-5 label');
$denoms = [
    'Omicron (BA.2-like)' => [
        'COVID-ARTIC-V4.1' => 25,
        'COVID-ARTIC-V5.0-5.3.2_400' => 40,
    ],
    'Omicron (XBB.1.5-like)' => [
        'COVID-ARTIC-V5.0-5.3.2_400' => 22,
    ],
];
$groups = [['id' => 7, 'label' => 'USA NJ', 'code' => 'NJ']];
$opt = ['min_samples' => 20, 'show_below' => false, 'major_only' => true];
$table = nj_read_filter_table($groups, [7 => $denoms], [7 => 3], 'pair', $opt);
expect($table['header_rows'][0][0] === 'Group' && $table['header_rows'][1][0] === '' && $table['header_rows'][1][1] === 'V3', 'pair csv has a group column and primer row');
$ba2v41 = null;
$xbbv5 = null;
foreach ($table['columns'] as $i => $col) {
    if ($col['block'] === 'ba2' && $col['sub'] === 'V4.1') {
        $ba2v41 = $i + 1;
    }
    if ($col['block'] === 'xbb' && $col['sub'] === 'V5.0') {
        $xbbv5 = $i + 1;
    }
}
expect($ba2v41 !== null && $table['rows'][0][$ba2v41] === '25', 'min 20 keeps a BA.2 V4.1 count of 25');
expect($xbbv5 !== null && $table['rows'][0][$xbbv5] === '22', 'min 20 keeps an XBB V5 count of 22');
$opt30 = $opt;
$opt30['min_samples'] = 30;
$table30 = nj_read_filter_table($groups, [7 => $denoms], [], 'pair', $opt30);
expect($table30['rows'][0][$ba2v41] === '-1' && $table30['rows'][0][$xbbv5] === '-1', 'min 30 marks those counts as -1');
$v5 = null;
foreach ($table30['columns'] as $i => $col) {
    if ($col['block'] === 'ba2' && $col['sub'] === 'V5.0') {
        $v5 = $i + 1;
    }
}
expect($v5 !== null && $table30['rows'][0][$v5] === '40', 'min 30 keeps a BA.2 V5 count of 40');
$hidden = nj_read_filter_table($groups, [7 => $denoms], [], 'pair', ['hide_ba1' => true, 'min_samples' => 20]);
expect(!in_array('BA.1', $hidden['header_rows'][0], true), 'Hide BA.1 drops those pair columns');
expect(nj_read_filter_download_name('pair', 20) === 'filter-variant-primer-pairs_min20.csv', 'pair download name includes the minimum');
expect(nj_read_filter_download_name('variant', 30) === 'filter-variants_min30.csv', 'variant download name');
$variantTable = nj_read_filter_table($groups, [7 => $denoms], [7 => 12], 'variant', ['min_samples' => 30]);
expect($variantTable['header_rows'][0][0] === 'Group' && $variantTable['header_rows'][0][1] === 'Type' && $variantTable['header_rows'][0][2] === 'Early', 'variant csv starts with group, type, and early');

if ($failed > 0) {
    echo "$failed failed\n";
    exit(1);
}
echo "all ok\n";
