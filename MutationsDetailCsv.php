<?php
/**
 * CSV of the currently filtered Mutations Detail table.
 * Same query, min %, Min AF, group, columns, and order as the HTML table.
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/mutations_detail_helpers.php';

mysqli_report(MYSQLI_REPORT_OFF);
$pack = mutations_detail_load($con);
if ($pack === null) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Could not load mutations for export.';
    exit;
}

$filename = mutations_detail_csv_filename($pack);
$filename = str_replace(['"', "\r", "\n"], '', $filename);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
if ($out === false) {
    exit;
}

fwrite($out, "\xEF\xBB\xBF");
fwrite($out, mutations_detail_csv_meta_line($pack) . "\n");

$headers = mutations_detail_visible_headers();
$line = [];
foreach ($headers as $h) {
    $line[] = mutations_detail_csv_field($h);
}
fwrite($out, implode(',', $line) . "\n");

foreach ($pack['rows'] as $row) {
    $vals = mutations_detail_csv_row_values($row);
    $cells = [];
    foreach ($vals as $value) {
        $cells[] = mutations_detail_csv_field($value);
    }
    fwrite($out, implode(',', $cells) . "\n");
}
fclose($out);
