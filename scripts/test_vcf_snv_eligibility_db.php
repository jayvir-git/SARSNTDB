<?php
/**
 * Live eligibility checks against app_sarsntdb. Run after import_vcf_sample_metadata.py.
 */
require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/vcf_snv_helpers.php';
require_once dirname(__DIR__) . '/mutations_detail_helpers.php';

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

$codes = [];
foreach (vcf_snv_fetch_groups($con) as $row) {
    $codes[] = $row['code'];
}
expect(in_array('Angola_miseq', $codes, true), 'Angola in selector');
expect(in_array('India-miseq', $codes, true), 'India MiSeq in selector');
expect(!in_array('Russia682735', $codes, true), 'hidden Russia not in selector');
expect(in_array('Thailand_mix', $codes, true), 'Thailand in selector');
expect(in_array('PAK_iseq', $codes, true), 'Pakistan visible');
expect(in_array('S_Afr-PRJNA636748', $codes, true), 'South Africa visible');
expect(in_array('Port-PRJEB47340', $codes, true), 'Portugal visible');
expect(in_array('PRJNA622837-Broad_Inst', $codes, true), 'Broad visible');
expect(in_array('PRJEB46220-Argentina', $codes, true), 'Argentina visible');
expect(vcf_snv_group_row($con, 'Russia682735') === null, 'hidden group_row is null');

$pak = vcf_snv_group_row($con, 'PAK_iseq');
expect($pak !== null, 'Pakistan group exists');
if ($pak) {
    expect(!empty($pak['eligibility']), 'Pakistan has eligibility stats');
    $n = vcf_snv_query_sample_count($con, $pak);
    expect($n === (int) $pak['eligibility']['eligible'], "Pakistan denominator $n equals eligible");
    expect($n !== (int) $pak['vcf_sample_count'] || $n === (int) $pak['eligibility']['eligible'], 'eligible may differ from VCF n');
    $join = vcf_snv_eligible_sql_join($con, $pak);
    expect(strpos($join, 'vcf_snv_sample_eligibility') !== false, 'Pakistan uses eligibility JOIN');
}

$broad = vcf_snv_group_row($con, 'PRJNA622837-Broad_Inst');
expect($broad !== null, 'Broad group exists');
if ($broad) {
    expect($broad['label'] === 'USA NE, NJ PRJNA622837', 'Broad display label');
    expect(!empty($broad['eligibility']), 'Broad has eligibility stats');
    $join = vcf_snv_eligible_sql_join($con, $broad);
    expect(strpos($join, 'vcf_snv_sample_eligibility') !== false, 'Broad uses eligibility JOIN');
    expect(vcf_snv_query_sample_count($con, $broad) === (int) $broad['eligibility']['eligible'], 'Broad denominator is PASS∩VCF');
}

$arg = vcf_snv_group_row($con, 'PRJEB46220-Argentina');
expect($arg !== null, 'Argentina group exists');
if ($arg) {
    expect($arg['label'] === 'Argentina PRJEB46220', 'Argentina display label');
    expect((int) $arg['vcf_sample_count'] === 2129, 'Argentina VCF n after merge');
    expect(!empty($arg['eligibility']), 'Argentina has eligibility stats');
    expect((int) $arg['eligibility']['eligible'] <= 2129, 'Argentina analyzed is within the VCF set');
    expect((int) $arg['eligibility']['eligible'] > 0, 'Argentina analyzed is non-zero');
    $join = vcf_snv_eligible_sql_join($con, $arg);
    expect(strpos($join, 'vcf_snv_sample_eligibility') !== false, 'Argentina uses eligibility JOIN');
    expect(vcf_snv_query_sample_count($con, $arg) === (int) $arg['eligibility']['eligible'], 'Argentina denominator is analyzed n');
}

$india = vcf_snv_group_row($con, 'India-6000');
expect($india !== null, 'India-6000 group exists');
if ($india) {
    expect((int) $india['vcf_sample_count'] === 2070, 'India VCF n after extra ZIP merge');
    expect((int) $india['eligibility']['eligible'] <= 2070, 'India analyzed is within the VCF set');
    expect((int) $india['eligibility']['eligible'] > 0, 'India analyzed is non-zero');
    expect(vcf_snv_query_sample_count($con, $india) === (int) $india['eligibility']['eligible'], 'India denominator is analyzed n');
}

$_GET = [
    'Group' => 'PAK_iseq',
    'MinAf' => '0.8',
    'MinPercent' => '1',
    'Start' => '1',
    'End' => '500',
    'Region' => '',
];
$pack = mutations_detail_load($con);
expect($pack !== null, 'detail pack loads');
if ($pack) {
    expect($pack['is_vcf'] === true, 'Pakistan is VCF');
    expect((int) $pack['sample_count'] === (int) $pak['eligibility']['eligible'], 'HTML/CSV pack uses eligible n');
    $meta = mutations_detail_csv_meta_line($pack);
    expect(strpos($meta, 'samples=' . (int) $pack['sample_count']) !== false, 'CSV samples= eligible n');
    expect(strpos($meta, 'unmatched=') === false, 'CSV meta omits unmatched');
}

$_GET = [
    'Group' => 'S_Afr-PRJNA636748',
    'MinAf' => '0.8',
    'MinPercent' => '1',
    'Start' => '1',
    'End' => '241',
    'Region' => '',
];
$sa = mutations_detail_load($con);
expect($sa !== null && (int) $sa['sample_count'] > 0, 'South Africa HTML n is analyzed samples');
expect(strpos(mutations_detail_csv_meta_line($sa), 'samples=' . (int) $sa['sample_count']) !== false, 'South Africa CSV n matches');
expect(strpos($sa['scope_text'], 'unmatched=') === false, 'South Africa scope omits unmatched');

$_GET = [
    'Group' => 'Port-PRJEB47340',
    'MinAf' => '0.8',
    'MinPercent' => '1',
    'Start' => '1',
    'End' => '241',
    'Region' => '',
];
$port = mutations_detail_load($con);
expect($port !== null && (int) $port['sample_count'] > 0, 'Portugal HTML n is analyzed samples');
expect(strpos(mutations_detail_csv_meta_line($port), 'samples=' . (int) $port['sample_count']) !== false, 'Portugal CSV n matches');
expect(strpos(mutations_detail_csv_meta_line($port), 'PRJNA622837') === false, 'Portugal CSV is not Broad metadata');

$_GET = ['Group' => 'Russia682735', 'Start' => '1', 'End' => '500'];
$hidden = mutations_detail_load($con);
expect($hidden !== null && $hidden['sample_count'] === 0, 'hidden group does not fall back to Original n');
expect(strpos($hidden['scope_text'], 'Original dataset was not used') !== false, 'hidden group states Original unused');

$_GET = ['Group' => 'original', 'Start' => '1', 'End' => '200'];
$orig = mutations_detail_load($con);
expect($orig !== null && empty($orig['is_vcf']), 'Original is not a VCF group');
expect((int) $orig['sample_count'] === 18900, 'Original denominator unchanged');
expect($orig['eligibility'] === null, 'Original has no PASS∩VCF stats');

if ($failed) {
    echo "\n$failed failed\n";
    exit(1);
}
echo "\nall passed\n";
