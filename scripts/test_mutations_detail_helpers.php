<?php
/**
 * Pure-function checks for Mutations Detail CSV helpers (no database).
 */
require_once dirname(__DIR__) . '/mutations_detail_helpers.php';
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

expect(mutations_detail_csv_field('C') === 'C', 'plain field');
expect(mutations_detail_csv_field('a,b') === '"a,b"', 'comma quoted');
expect(mutations_detail_csv_field('say "hi"') === '"say ""hi"""', 'quotes doubled');
expect(mutations_detail_csv_field("a\nb") === "\"a\nb\"", 'newline quoted');

$rna = 'ATGAAATAG';
$prot = 'MK*';
$row = [
    'Start' => 1,
    'RNA_sequence' => $rna,
    'protSeq' => $prot,
    'coordinate' => 4,
    'alternate' => 'G',
];
expect(mutations_detail_aa_change($row) === 'Missense Variant: p.K2E', 'missense p.K2E');

$syn = $row;
$syn['coordinate'] = 6;
$syn['alternate'] = 'G';
expect(mutations_detail_aa_change($syn) === 'Synonymous change', 'synonymous AAA/AAG');

$intergenic = ['Start' => null, 'RNA_sequence' => null, 'protein' => null];
expect(mutations_detail_display_protein($intergenic) === '-', 'intergenic protein hyphen');
expect(mutations_detail_aa_change($intergenic) === '', 'intergenic no AA change');

expect(mutations_detail_pango_text(['Omicron_BA.2', 'Alpha_B.1.1.7']) === 'Omicron_BA.2, Alpha_B.1.1.7', 'pango text');
expect(mutations_detail_pango_text([]) === '', 'empty pango');

$pack = [
    'group_code' => 'PRJNA622837-Broad_Inst',
    'group_label' => 'Broad Institute',
    'sample_count' => 2437,
    'indel_start' => 1,
    'indel_end' => 29903,
    'min_percent' => 1,
    'min_af' => 0.8,
    'is_vcf' => true,
    'region' => '',
    'rows' => [1, 2],
];
$fn = mutations_detail_csv_filename($pack);
expect(strpos($fn, 'mutations_PRJNA622837-Broad_Inst_n2437_1-29903_minPct1_minAf0.8.csv') === 0, "filename $fn");
expect(strpos($fn, 'mean') === false && strpos($fn, 'AF_display') === false, 'filename has no mean AF');

$meta = mutations_detail_csv_meta_line($pack);
expect(strpos($meta, 'samples=2437') !== false, 'meta samples');
expect(strpos($meta, 'min_af=0.8') !== false, 'meta min AF');
expect(strpos($meta, 'Mean AF') === false, 'meta has no Mean AF column');

$eligPack = $pack;
$eligPack['group_code'] = 'PAK_iseq';
$eligPack['group_label'] = 'Pakistan iSeq';
$eligPack['sample_count'] = 780;
$eligPack['eligibility'] = [
    'vcf' => 808,
    'in_meta' => 808,
    'eligible' => 780,
    'unmatched' => 0,
    'fail_qc' => 28,
];
$eligMeta = mutations_detail_csv_meta_line($eligPack);
expect(strpos($eligMeta, 'samples=780') !== false, 'eligible denominator in CSV meta');
expect(strpos($eligMeta, 'vcf_samples=') === false, 'CSV meta omits the VCF breakdown');
expect(strpos($eligMeta, 'unmatched=') === false, 'CSV meta omits unmatched');
expect(strpos($eligMeta, 'fail_qc=') === false, 'CSV meta omits FAIL_QC');
expect(nj_read_percent(10, 40) === 25.0, 'NJ percent');
expect(nj_read_percent(1, 0) === null, 'NJ percent with empty denominator');
expect(nj_read_variant_key('Omicron (Unassigned)', false) === null, 'hide Unassigned');
expect(nj_read_variant_key('Probable Omicron (BA.1-like)', false) === null, 'hide Probable');
expect(nj_read_variant_key('Delta (AY.4-like)', false) === 'Delta (AY.4-like)', 'Delta subtype stays split');
expect(nj_read_variant_key('Delta (B.1.617.2-like) +K417N', true) === 'Delta', 'Merge Delta folds K417N');
expect(nj_read_variant_key('Alpha (B.1.1.7-like)', true) === 'Alpha (B.1.1.7-like)', 'Merge Delta leaves Alpha');

$pairs = [
    ['variant' => 'Delta (AY.4-like)', 'primer' => 'COVID-ARTIC-V3'],
    ['variant' => 'Delta (B.1.617.2-like)', 'primer' => 'COVID-ARTIC-V3'],
    ['variant' => 'Delta (AY.4-like)', 'primer' => 'COVID-ARTIC-V4.1'],
    ['variant' => 'Alpha (B.1.1.7-like)', 'primer' => 'COVID-ARTIC-V3'],
    ['variant' => 'Omicron (Unassigned)', 'primer' => 'COVID-ARTIC-V4.1'],
    ['variant' => 'Probable Omicron (BA.1-like)', 'primer' => 'COVID-ARTIC-V3'],
];
$variantKeys = [];
foreach (nj_read_breakdown_choices($pairs, 'variant', false) as $choice) {
    $variantKeys[] = $choice['key'];
}
expect(in_array('Delta (AY.4-like)', $variantKeys, true), 'variant list keeps Delta subtype');
expect(in_array('Alpha (B.1.1.7-like)', $variantKeys, true), 'variant list keeps Alpha');
expect(!in_array('Omicron (Unassigned)', $variantKeys, true), 'variant list drops Unassigned');
expect(!in_array('Probable Omicron (BA.1-like)', $variantKeys, true), 'variant list drops Probable');
$mergedKeys = [];
foreach (nj_read_breakdown_choices($pairs, 'variant', true) as $choice) {
    $mergedKeys[] = $choice['key'];
}
sort($mergedKeys);
expect($mergedKeys === ['Alpha (B.1.1.7-like)', 'Delta'], 'Merge Delta keeps Alpha and folds Delta');
$pairKeys = [];
foreach (nj_read_breakdown_choices($pairs, 'pair', true) as $choice) {
    $pairKeys[] = $choice['key'];
}
sort($pairKeys);
expect($pairKeys === ['Alpha (B.1.1.7-like)|COVID-ARTIC-V3', 'Delta|COVID-ARTIC-V3', 'Delta|COVID-ARTIC-V4.1'], 'Merge Delta pairs keep Alpha');

$raw = [
    ['group_id' => 1, 'variant_label' => 'Delta (AY.4-like)', 'primer_label' => 'COVID-ARTIC-V3', 'n_denom' => 10, 'n_num' => 4],
    ['group_id' => 1, 'variant_label' => 'Delta (B.1.617.2-like)', 'primer_label' => 'COVID-ARTIC-V3', 'n_denom' => 5, 'n_num' => 1],
    ['group_id' => 1, 'variant_label' => 'Omicron (Unassigned)', 'primer_label' => 'COVID-ARTIC-V3', 'n_denom' => 9, 'n_num' => 9],
];
$folded = nj_read_fold_breakdown($raw, ['Delta|COVID-ARTIC-V3'], 'pair', true);
expect($folded["1\tDelta|COVID-ARTIC-V3"]['d'] === 15, 'merged Delta pair denominator skips Unassigned');
expect($folded["1\tDelta|COVID-ARTIC-V3"]['n'] === 5, 'merged Delta pair numerator');
$primerFold = nj_read_fold_breakdown($raw, ['COVID-ARTIC-V3'], 'primer', false);
expect($primerFold["1\tCOVID-ARTIC-V3"]['d'] === 24, 'primer column keeps every sample with that primer');
expect(nj_read_breakdown_display(5, 15) === '33.33', 'breakdown percent');
expect(nj_read_breakdown_display(0, 0) === '-1', 'breakdown missing column is -1');
expect(nj_read_breakdown_display(0, 8) === '0.00', 'breakdown zero percent');
expect(strpos(mutations_detail_csv_filename($eligPack), '_n780_') !== false, 'filename uses eligible n');

$headers = mutations_detail_visible_headers();
expect(!in_array('Mean AF', $headers, true), 'headers omit Mean AF');
expect(!in_array('SNAP2 Analysis', $headers, true), 'headers omit SNAP2');
expect($headers[0] === 'Coordinate' && $headers[6] === '% Containing Mutation', 'header order');

$displayRow = [
    'coordinate' => 241,
    'reference' => 'C',
    'alternate' => 'T',
    '_protein' => '-',
    '_aa_change' => '',
    'no_of_samples' => 1849,
    '_percentage' => 75.87,
    '_pango_text' => 'Alpha_B.1.1.7',
];
$vals = mutations_detail_csv_row_values($displayRow);
expect(count($vals) === 8, 'csv row width matches headers');
expect($vals[5] === 1849 && $vals[6] === 75.87, 'sample count and percent, not mean AF');

if ($failed) {
    echo "\n$failed failed\n";
    exit(1);
}
echo "\nall passed\n";
