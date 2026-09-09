<?php
/**
 * Jim Kelley's table of 30 NJ junctions (NJ-table-small.csv), one row each.
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/two_segment_helpers.php';

$dbError = null;
$sourceNote = '';
$rows = [];

$conOrNull = (isset($con) && $con instanceof mysqli && !$con->connect_errno) ? $con : null;
if ($conOrNull === null && isset($con) && $con instanceof mysqli && $con->connect_errno) {
    $dbError = 'Database connection failed; schematic links may be missing.';
}
$pack = tsg_fetch_nj_table30($conOrNull);
if ($pack['error'] !== null) {
    $dbError = $pack['error'];
} else {
    $rows = $pack['rows'];
    $sourceNote = $pack['source'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Repeat CSV vs two-segment junctions — SARSNTDB</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <?php include __DIR__ . '/Navigation.php'; ?>
    <style>
        .rcsv-page .search-header { padding-left: 10px; }
        .rcsv-table { font-size: 12px; }
        .rcsv-table td { vertical-align: middle !important; }
        .rcsv-mono { font-family: Consolas, monospace; font-size: 11px; }
        tr.rcsv-dark th {
            background: #333;
            color: #fff;
            position: sticky;
            top: 0;
        }
    </style>
</head>
<body class="rcsv-page">
<div class="panel panel-default" style="margin: 15px;">
    <div class="panel-heading">
        <h4 class="search-header" style="margin:0;">Repeat CSV → two-segment junctions</h4>
    </div>
    <div class="panel-body">
        <p style="font-size:13px; max-width:920px;">
            Jim Kelley’s table of 30 non-canonical junctions
            (<code>NJ-table-small.csv</code>). One junction per row.
            Illustrative extras are not listed. Size is inclusive
            (<code>End − Start + 1</code>).
        </p>

        <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($rows !== []) : ?>
        <p class="text-muted" style="font-size:12px;">
            Source: <strong><?php echo htmlspecialchars($sourceNote, ENT_QUOTES, 'UTF-8'); ?></strong>
            — <?php echo count($rows); ?> junction(s).
        </p>

        <div style="max-height:70vh; overflow:auto;">
            <table class="table table-bordered table-striped rcsv-table">
                <thead>
                    <tr class="rcsv-dark">
                        <th>Size</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Repeat</th>
                        <th>Junction</th>
                        <th>Genome</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r) :
                    $id = isset($r['id']) ? (int) $r['id'] : 0;
                    $left = (int) $r['coord_left'];
                    $right = (int) $r['coord_right'];
                    $size = isset($r['size']) ? (int) $r['size'] : tsg_junction_size($r);
                    $jk = htmlspecialchars(tsg_junction_kind_label($r), ENT_QUOTES, 'UTF-8');
                    $jn = htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8');
                    $rep = isset($r['repeat_seq']) ? (string) $r['repeat_seq'] : '';
                    ?>
                    <tr>
                        <td class="text-right"><?php echo $size; ?></td>
                        <td class="text-right"><?php echo $left; ?></td>
                        <td class="text-right"><?php echo $right; ?></td>
                        <td class="rcsv-mono"><?php echo htmlspecialchars($rep, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php echo $jk; ?> — <?php echo $jn; ?>
                            <?php if ($id > 0) : ?>
                                — <a href="<?php echo htmlspecialchars(tsg_viz_url($id), ENT_QUOTES, 'UTF-8'); ?>">schematic</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="GenomeResult.php?Start=<?php echo $left; ?>&amp;End=<?php echo $right; ?>">Open GenomeResult</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
