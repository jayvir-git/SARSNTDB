<?php
require_once dirname(__DIR__) . '/connection.php';
if (!isset($con) || $con->connect_errno) {
    fwrite(STDERR, "mysql-fail\n");
    exit(1);
}
$want = [
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
    [0, 0, 0],
];
$out = [];
$stmt = $con->prepare('SELECT id FROM two_segment_structure WHERE coord_left = ? AND coord_right = ? LIMIT 1');
foreach ($want as $row) {
    [$left, $right, $size] = $row;
    $stmt->bind_param('ii', $left, $right);
    $stmt->execute();
    $res = $stmt->get_result();
    $hit = $res->fetch_assoc();
    $res->free();
    if (!$hit) {
        fwrite(STDERR, "missing $left-$right\n");
        exit(1);
    }
    $out[] = [
        'id' => (int) $hit['id'],
        'left' => $left,
        'right' => $right,
        'size' => $size,
    ];
}
$stmt->close();
echo json_encode($out, JSON_PRETTY_PRINT);
