<?php
/**
 * Two-segment page labels. No database.
 */
require_once dirname(__DIR__) . '/two_segment_helpers.php';

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

expect(tsg_is_illustrative_junction([
    'name' => 'Jim CSV — ACACAGA (24296–27142) [illustrative]',
    'notes' => 'Jim Kelley CSV NJ layout.',
]) === true, 'a row labeled illustrative is removed');
expect(tsg_is_illustrative_junction([
    'name' => 'sgmRNA — ORF3a (body start 25393)',
    'notes' => 'Demo: illustrative TRS/body layout; confirm with literature.',
]) === false, 'a demo gene row stays');
expect(tsg_is_illustrative_junction([
    'name' => 'NJ 5249–23191 (size 17943)',
    'notes' => 'Primer arrows import: NJ-table-small.csv; inclusive junction size 17943.',
]) === false, 'a junction from the table of 30 stays');
expect(tsg_repeat_for_display('acgaacuu') === 'ACGAACTT', 'ORF3a repeat is DNA capitals');
expect(tsg_repeat_for_display('acgaacuaaa') === 'ACGAACTAAA', 'M repeat is DNA capitals');
expect(tsg_repeat_for_display('acucaugcag') === 'ACTCATGCAG', 'N repeat is DNA capitals');
expect(tsg_repeat_for_display('AATGGTTTAAC') === 'AATGGTTTAAC', 'an existing DNA repeat stays');
expect(tsg_repeat_for_display(null) === null, 'a missing repeat stays empty');
expect(tsg_sg_label('sgmRNA') === 'sgRNA', 'subtype label is sgRNA');
expect(tsg_sg_label('sgmRNA — S (body start 21563)') === 'sgRNA — S (body start 21563)', 'gene row name says sgRNA');

if ($failed > 0) {
    echo "$failed failed\n";
    exit(1);
}
echo "all ok\n";
