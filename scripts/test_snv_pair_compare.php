<?php
/**
 * Variant–primer SNV comparison. No database.
 */
require_once dirname(__DIR__) . '/snv_pair_compare.php';

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

expect(snv_pair_rel_diff(10, 30) === 100.0, '10% and 30% is a 100 relative difference');
expect(snv_pair_rel_diff(10, 10) === 0.0, 'equal percents are 0');
expect(snv_pair_rel_diff(0, 10) === 200.0, 'one side at 0 is 200');
expect(snv_pair_rel_diff(0, 0) === null, 'both zero has no relative difference');

$rows = snv_pair_compare_rows([
    ['coordinate' => 100, 'reference' => 'A', 'alternate' => 'G', 'n1' => 10, 'n2' => 30],
    ['coordinate' => 50, 'reference' => 'C', 'alternate' => 'T', 'n1' => 0, 'n2' => 10],
    ['coordinate' => 80, 'reference' => 'G', 'alternate' => 'A', 'n1' => 0, 'n2' => 0],
], 100, 100, 1);
expect(count($rows) === 2, 'a call under 1% in both pairs is hidden');
expect($rows[0]['name'] === 'C50T', 'the larger relative difference is first');
expect($rows[0]['rel'] === 200.0, '0% and 10% sorts as 200');
expect($rows[1]['name'] === 'A100G', '10% and 30% follows');
expect($rows[1]['pct1'] === 10.0 && $rows[1]['pct2'] === 30.0, 'percents use each pair as the denominator');

$samples = [];
for ($i = 1; $i <= 40; $i++) {
    $samples[] = ['id' => $i, 'variant' => 'Delta (B.1.617.2-like)', 'primer' => 'COVID-ARTIC-V3'];
}
$samples[] = ['id' => 41, 'variant' => 'Delta (AY.4.2-like)', 'primer' => 'COVID-ARTIC-V3'];
for ($i = 42; $i <= 53; $i++) {
    $samples[] = ['id' => $i, 'variant' => 'Delta (B.1.617.2-like)', 'primer' => 'COVID-ARTIC-V4.1'];
}
$samples[] = ['id' => 90, 'variant' => 'Delta (B.1.617.2-like) +K417N', 'primer' => 'COVID-ARTIC-V3'];
$samples[] = ['id' => 91, 'variant' => 'Omicron (BA.2-like)', 'primer' => 'COVID-ARTIC-V3'];
$samples[] = ['id' => 92, 'variant' => 'Omicron (BA.5-like)', 'primer' => 'COVID-ARTIC-V3'];

$built = snv_pair_build($samples, ['merge_delta' => true, 'major_only' => true, 'min_samples' => 30]);
$deltaV3 = snv_pair_find($built, 'delta|COVID-ARTIC-V3');
$deltaV41 = snv_pair_find($built, 'delta|COVID-ARTIC-V4.1');
expect($deltaV3 !== null && $deltaV3['n'] === 40 && $deltaV3['raw'] === 41, 'merged Delta V3 keeps the subtype that meets 30');
expect($deltaV3['ready'] === true && !in_array(41, $deltaV3['ids'], true), 'the subtype under 30 stays out of Delta V3');
expect(!in_array(90, $deltaV3['ids'], true), 'a plus-mutation label stays out');
expect($deltaV41 !== null && $deltaV41['ready'] === false && $deltaV41['raw'] === 12, '12 samples stay under the minimum');
expect(snv_pair_choice_text(snv_pair_public_choice($deltaV3)) === 'Delta V3 (40)', 'a ready pair shows its denominator');
expect(snv_pair_choice_text(snv_pair_public_choice($deltaV41)) === 'Delta V4.1 (12, under minimum)', 'an under-minimum pair shows the raw count');

$defaults = snv_pair_default_keys(array_map('snv_pair_public_choice', $built));
expect($defaults[0] === 'delta|COVID-ARTIC-V3' && $defaults[1] !== 'delta|COVID-ARTIC-V4.1', 'V4.1 is not the default while it is under the minimum');

$open = snv_pair_build($samples, ['merge_delta' => true, 'major_only' => true, 'min_samples' => 10]);
$openV41 = snv_pair_find($open, 'delta|COVID-ARTIC-V4.1');
expect($openV41 !== null && $openV41['ready'] === true && $openV41['n'] === 12, 'lowering the minimum includes the 12-sample pair');
$openDefaults = snv_pair_default_keys(array_map('snv_pair_public_choice', $open));
expect($openDefaults === ['delta|COVID-ARTIC-V3', 'delta|COVID-ARTIC-V4.1'], 'Delta V3 and Delta V4.1 are the defaults when both are ready');

$split = snv_pair_build($samples, ['merge_delta' => false, 'major_only' => true, 'min_samples' => 30]);
expect(snv_pair_find($split, 'delta|COVID-ARTIC-V3') === null, 'Merge Delta off has no combined Delta column');
$subtype = snv_pair_find($split, 'raw:Delta (B.1.617.2-like)|COVID-ARTIC-V3');
expect($subtype !== null && $subtype['n'] === 40 && $subtype['label'] === 'Delta (B.1.617.2-like) V3', 'Merge Delta off lists the subtype');
expect($split[0]['key'] === 'raw:Delta (B.1.617.2-like)|COVID-ARTIC-V3', 'Delta subtypes stay at the top of the pair list');

$ba = snv_pair_build($samples, ['merge_delta' => true, 'merge_ba' => false, 'major_only' => true, 'min_samples' => 0]);
$ba2 = snv_pair_find($ba, 'ba2|COVID-ARTIC-V3');
$ba5 = snv_pair_find($ba, 'ba5|COVID-ARTIC-V3');
expect($ba2 !== null && $ba2['n'] === 1 && $ba2['ids'] === [91], 'BA.2 stays in its own pair');
expect($ba5 !== null && $ba5['n'] === 1 && $ba5['ids'] === [92], 'BA.5 stays in its own pair');

$html = snv_pair_render($rows, ['label' => 'USA NJ'], $deltaV3, [
    'label' => 'Delta V4.1',
    'n' => 12,
    'raw' => 12,
], 0.8, 1);
expect(strpos($html, 'Rel. % Diff') !== false, 'the table names the relative column');
expect(strpos($html, '% SNV in Delta V3') !== false && strpos($html, 'C50T') !== false, 'the table shows the SNV and both pair headers');
expect(strpos($html, 'leaves out 1 sample') !== false, 'a merged pair says when a subtype was left out');

if ($failed > 0) {
    echo "$failed failed\n";
    exit(1);
}
echo "all passed\n";
