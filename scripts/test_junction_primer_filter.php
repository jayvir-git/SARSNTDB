<?php
/**
 * Primer-name matching for JunctionGroupQuery filters (no database).
 */
require_once dirname(__DIR__) . '/junction_query_helpers.php';

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

expect(jq_primer_canonical('V3') === 'V3', 'excel V3');
expect(jq_primer_canonical('COVID-ARTIC-V3') === 'V3', 'viridian COVID-ARTIC-V3');
expect(jq_primer_canonical('ARTIC V3') === 'V3', 'label ARTIC V3');
expect(jq_primer_canonical('V4.1') === 'V4.1', 'excel V4.1');
expect(jq_primer_canonical('COVID-ARTIC-V4.1') === 'V4.1', 'viridian V4.1');
expect(jq_primer_canonical('COVID-MIDNIGHT-1200') === 'MIDNIGHT-1200', 'midnight');
expect(jq_primer_canonical('V5.0-5.3.2_400') === 'V5.0-5.3.2-400', 'v5 underscore');
expect(jq_primer_canonical('COVID-ARTIC-V5.0-5.3.2_400') === 'V5.0-5.3.2-400', 'v5 covid artic');

expect(jq_primer_selected('COVID-ARTIC-V3', ['V3']) === true, 'V3 matches COVID-ARTIC-V3');
expect(jq_primer_selected('V4.1', ['V3']) === false, 'V4.1 not selected as V3');
expect(jq_primer_selected('COVID-MIDNIGHT-1200', ['V3', 'V4.1']) === false, 'midnight excluded');
expect(jq_primer_selected('V3', []) === true, 'empty selected keeps all');

$merged = jq_merge_primer_options(['V3', 'V4.1'], ['COVID-ARTIC-V3', 'COVID-MIDNIGHT-1200', 'V5.0-5.3.2_400']);
expect(in_array('V3', $merged, true), 'merged keeps V3 once');
expect(in_array('V4.1', $merged, true), 'merged V4.1');
expect(in_array('MIDNIGHT-1200', $merged, true), 'merged midnight canonical');
expect(count(array_filter($merged, function ($p) { return jq_primer_canonical($p) === 'V3'; })) === 1, 'no duplicate V3');

$pairs = [
    ['group_id' => 1, 'code' => 'NJ', 'group_name' => 'NJ MiSeq', 'variant_name' => 'Delta (B.1.617.2-like)', 'primer_name' => 'V3', 'sample_count' => 10],
    ['group_id' => 1, 'code' => 'NJ', 'group_name' => 'NJ MiSeq', 'variant_name' => 'Delta (B.1.617.2-like)', 'primer_name' => 'V4.1', 'sample_count' => 5],
    ['group_id' => 1, 'code' => 'NJ', 'group_name' => 'NJ MiSeq', 'variant_name' => 'Omicron (BA.1-like)', 'primer_name' => 'COVID-ARTIC-V3', 'sample_count' => 3],
    ['group_id' => 1, 'code' => 'NJ', 'group_name' => 'NJ MiSeq', 'variant_name' => 'Omicron (BA.1-like)', 'primer_name' => 'COVID-MIDNIGHT-1200', 'sample_count' => 7],
];
$v3 = jq_filter_viridian_pairs($pairs, ['V3']);
expect(count($v3) === 2, 'V3 keeps excel V3 and COVID-ARTIC-V3');
$from = jq_viridian_variants_from_pairs($v3);
$byName = [];
foreach ($from as $row) {
    $byName[$row['variant_name']] = $row;
}
expect((int) $byName['Delta (B.1.617.2-like)']['sample_count'] === 10, 'delta V3 only');
expect((int) $byName['Omicron (BA.1-like)']['sample_count'] === 3, 'omicron V3 only not midnight');
expect((int) $byName['Delta (all)']['sample_count'] === 10 && !empty($byName['Delta (all)']['is_rollup']), 'delta rollup from filtered pairs');

$all = jq_filter_viridian_pairs($pairs, []);
expect(count($all) === 4, 'no filter keeps four pair rows');

expect(jq_primer_display_label('V3') === 'ARTIC V3', 'display V3');
expect(jq_primer_display_label('COVID-MIDNIGHT-1200') === 'Midnight-1200', 'display midnight');

if ($failed) {
    echo "\n$failed failed\n";
    exit(1);
}
echo "\nall passed\n";
