<?php
require_once './connection.php';
require_once './snv_pair_compare.php';

$what = isset($_GET['what']) ? (string) $_GET['what'] : 'table';
if ($what === 'choices') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(snv_pair_choices_payload($con));
    exit;
}

if ($what === 'csv') {
    $pack = snv_pair_export_pack($con);
    if (isset($pack['error'])) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: text/plain; charset=utf-8');
        echo $pack['error'];
        exit;
    }
    $filename = str_replace(['"', "\r", "\n"], '', snv_pair_csv_filename(
        $pack['group'],
        $pack['left'],
        $pack['right'],
        $pack['min_af'],
        $pack['min_samples']
    ));
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo "\xEF\xBB\xBF";
    echo snv_pair_csv_text(
        $pack['rows'],
        $pack['group'],
        $pack['left'],
        $pack['right'],
        $pack['min_af'],
        $pack['min_percent']
    );
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo snv_pair_table_html($con);
