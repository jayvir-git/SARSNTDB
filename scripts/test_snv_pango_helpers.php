<?php
require_once __DIR__ . '/../snv_pango_helpers.php';

$failed = 0;
function expect($cond, $msg)
{
    global $failed;
    if ($cond) {
        echo "ok   $msg\n";
        return;
    }
    $failed++;
    echo "FAIL $msg\n";
}

expect(snv_pango_format_label(21987, 'G', 'A') === 'G21987A', 'SNV label G21987A');
expect(snv_pango_format_label(23202, 'c', 'a') === 'C23202A', 'SNV label uppercases alleles');
expect(pango_indel_kind_from_alleles('AAAGTCATTT', 'A') === 'deletion', 'indel kind deletion');
expect(pango_indel_kind_from_alleles('G', 'GAACA') === 'insertion', 'indel kind insertion');
expect(pango_indel_kind_from_alleles('G', 'A') === '', 'single-base is not an indel');
expect(
    pango_indel_allele_label(685, 'AAAGTCATTT', 'A') === '685 deletion REF=AAAGTCATTT ALT=A',
    'indel allele label keeps full REF/ALT'
);
expect(
    strpos(pango_indel_allele_label(685, 'AAAGTCATTT', 'A'), 'AAAGTCATTT685A') === false,
    'indel label is not SNV-style substitution'
);
expect(
    pango_indel_allele_label(6512, 'AGTT', 'A', 'deletion') === '6512 deletion REF=AGTT ALT=A',
    'explicit kind is kept'
);

if ($failed) {
    echo "\n$failed failed\n";
    exit(1);
}
echo "\nall passed\n";
