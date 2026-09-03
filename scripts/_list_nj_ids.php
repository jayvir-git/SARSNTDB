<?php
require_once dirname(__DIR__) . '/connection.php';
if (!isset($con) || $con->connect_errno) {
    fwrite(STDERR, "mysql-fail\n");
    exit(1);
}
$want = [
    [1890, 2882, 993],
    [6188, 8953, 2766],
    [26715, 27784, 1070],
    [4314, 4672, 359],
    [11387, 19846, 8460],
    [4986, 16751, 11766],
    [13393, 18967, 5575],
    [8998, 10668, 1671],
    [10682, 26723, 16042],
    [18324, 19217, 894],
    [27801, 29172, 1372],
    [5576, 18593, 13018],
    [8710, 20174, 11465],
    [8704, 23337, 14634],
    [3402, 4640, 1239],
    [5636, 20091, 14456],
    [16705, 19480, 2776],
    [11405, 24509, 13105],
    [2949, 25052, 22104],
    [12778, 13322, 545],
    [16557, 17033, 477],
    [7233, 11504, 4272],
    [25432, 25624, 193],
    [2936, 11001, 8066],
    [720, 11341, 10622],
    [10558, 11358, 801],
    [3056, 13620, 10565],
    [7555, 8252, 698],
    [1492, 8598, 7107],
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
