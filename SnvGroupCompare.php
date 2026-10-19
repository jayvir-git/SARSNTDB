<?php
require_once './connection.php';
require_once './snv_group_compare.php';

$pack = snv_group_compare_pack($con, snv_group_compare_request());
$what = isset($_GET['what']) ? (string) $_GET['what'] : 'table';

if ($what === 'csv') {
    if (isset($pack['error'])) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: text/plain; charset=utf-8');
        echo $pack['error'];
        exit;
    }
    $csv = snv_group_compare_csv($pack);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $csv['filename']) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo "\xEF\xBB\xBF";
    echo $csv['text'];
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo snv_group_compare_html($pack);
