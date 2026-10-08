<?php
require_once './connection.php';
require_once './snv_pair_compare.php';

$what = isset($_GET['what']) ? (string) $_GET['what'] : 'table';
if ($what === 'choices') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(snv_pair_choices_payload($con));
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo snv_pair_table_html($con);
