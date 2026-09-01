<?php
/**
 * Batch report: CSV rows (Coord1, Coord2, Repeat) vs two_segment_structure junctions.
 * Same overlap rule as GenomeResult.php (coord_left or coord_right in [lo, hi]).
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/two_segment_helpers.php';

$defaultFile = 'test_coord_050626.csv';
$requested = isset($_GET['file']) ? basename((string) $_GET['file']) : $defaultFile;
if ($requested === '' || strcasecmp(substr($requested, -4), '.csv') !== 0) {
    $requested = $defaultFile;
}
$csvPath = __DIR__ . DIRECTORY_SEPARATOR . $requested;
$csvError = null;
$rows = [];

if (!is_readable($csvPath)) {
    $csvError = 'Cannot read CSV: ' . htmlspecialchars($requested, ENT_QUOTES, 'UTF-8')
        . ' (place the file in the SARSNTDB web root next to this script).';
} else {
    $fh = fopen($csvPath, 'rb');
    if ($fh === false) {
        $csvError = 'Failed to open CSV.';
    } else {
        $header = fgetcsv($fh);
        if ($header === false) {
            $csvError = 'CSV is empty.';
        } else {
            $map = [];
            foreach ($header as $i => $name) {
                $map[strtolower(trim((string) $name))] = $i;
            }
            $i1 = isset($map['coord1']) ? $map['coord1'] : (isset($map['start']) ? $map['start'] : null);
            $i2 = isset($map['coord2']) ? $map['coord2'] : (isset($map['end']) ? $map['end'] : null);
            $ir = isset($map['repeat']) ? $map['repeat'] : null;
            if ($i1 === null || $i2 === null || $ir === null) {
                $csvError = 'CSV must have headers Coord1, Coord2, Repeat (or Start, End, Repeat).';
            } else {
                while (($line = fgetcsv($fh)) !== false) {
                    if (count(array_filter($line, static function ($c) {
                        return $c !== null && trim((string) $c) !== '';
                    })) === 0) {
                        continue;
                    }
                    $c1 = isset($line[$i1]) ? trim((string) $line[$i1]) : '';
                    $c2 = isset($line[$i2]) ? trim((string) $line[$i2]) : '';
                    $rep = isset($line[$ir]) ? trim((string) $line[$ir]) : '';
                    if ($c1 === '' || $c2 === '' || !is_numeric($c1) || !is_numeric($c2)) {
                        continue;
                    }
                    $lo = min((int) $c1, (int) $c2);
                    $hi = max((int) $c1, (int) $c2);
                    $overlap = ['rows' => [], 'error' => null];
                    $motif = [];
                    if (isset($con) && $con instanceof mysqli && !$con->connect_errno) {
                        $overlap = tsg_fetch_overlapping($con, $lo, $hi);
                        if ($rep !== '') {
                            $motif = tsg_rows_matching_repeat($con, $rep);
                        }
                    }
                    $rows[] = [
                        'coord1' => (int) $c1,
                        'coord2' => (int) $c2,
                        'repeat' => $rep,
                        'lo' => $lo,
                        'hi' => $hi,
                        'overlap' => $overlap,
                        'motif' => $motif,
                    ];
                }
            }
        }
        fclose($fh);
    }
}

$dbError = null;
if (isset($con) && $con instanceof mysqli && $con->connect_errno) {
    $dbError = 'Database connection failed.';
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
        .rcsv-table td { vertical-align: top !important; }
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
            For each CSV row, this page lists <strong>CJ/NJ junctions</strong> whose gap border
            <code>coord_left</code> or <code>coord_right</code> falls inside
            <code>min(Coord1,Coord2)…max(Coord1,Coord2)</code> (same rule as Genome search results).
            It also lists junctions whose stored <code>repeat_seq</code> matches the <strong>Repeat</strong> column
            (same rules as the Repeats motif visualizer). Default file:
            <code><?php echo htmlspecialchars($defaultFile, ENT_QUOTES, 'UTF-8'); ?></code>.
            Other files in this folder: <code>?file=your.csv</code>
        </p>

        <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($csvError !== null) : ?>
            <div class="alert alert-danger"><?php echo $csvError; ?></div>
        <?php else : ?>

        <p class="text-muted" style="font-size:12px;">
            Source: <strong><?php echo htmlspecialchars($requested, ENT_QUOTES, 'UTF-8'); ?></strong>
            — <?php echo count($rows); ?> data row(s).
        </p>

        <div style="max-height:70vh; overflow:auto;">
            <table class="table table-bordered table-striped rcsv-table">
                <thead>
                    <tr class="rcsv-dark">
                        <th>Coord1</th>
                        <th>Coord2</th>
                        <th>Repeat</th>
                        <th>Junctions overlapping interval</th>
                        <th>Junctions matching repeat text</th>
                        <th>Genome interval</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r) :
                    $ov = $r['overlap'];
                    $mot = $r['motif'];
                    ?>
                    <tr>
                        <td class="text-right"><?php echo (int) $r['coord1']; ?></td>
                        <td class="text-right"><?php echo (int) $r['coord2']; ?></td>
                        <td class="rcsv-mono"><?php echo htmlspecialchars($r['repeat'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if (!empty($ov['error'])) : ?>
                                <span class="text-danger"><?php echo htmlspecialchars($ov['error'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php elseif (empty($ov['rows'])) : ?>
                                <em>None</em>
                            <?php else : ?>
                                <ul style="margin:0; padding-left:18px;">
                                    <?php foreach ($ov['rows'] as $jm) :
                                        $ju = tsg_viz_url((int) $jm['id']);
                                        $jk = htmlspecialchars(tsg_junction_kind_label($jm), ENT_QUOTES, 'UTF-8');
                                        $jn = htmlspecialchars($jm['name'], ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <li><?php echo $jk; ?> — <?php echo $jn; ?> —
                                            <a href="<?php echo htmlspecialchars($ju, ENT_QUOTES, 'UTF-8'); ?>">schematic</a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['repeat'] === '' || strlen($r['repeat']) < 4) : ?>
                                <em>—</em> <span class="text-muted">(repeat empty or &lt; 4 nt)</span>
                            <?php elseif (empty($mot)) : ?>
                                <em>None</em>
                            <?php else : ?>
                                <ul style="margin:0; padding-left:18px;">
                                    <?php foreach ($mot as $jm) :
                                        $ju = tsg_viz_url((int) $jm['id']);
                                        $jk = htmlspecialchars(tsg_junction_kind_label($jm), ENT_QUOTES, 'UTF-8');
                                        $jn = htmlspecialchars($jm['name'], ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <li><?php echo $jk; ?> — <?php echo $jn; ?> —
                                            <a href="<?php echo htmlspecialchars($ju, ENT_QUOTES, 'UTF-8'); ?>">schematic</a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="GenomeResult.php?Start=<?php echo (int) $r['coord1']; ?>&amp;End=<?php echo (int) $r['coord2']; ?>">Open GenomeResult</a>
                            <span class="text-muted">(<?php echo (int) $r['lo']; ?>–<?php echo (int) $r['hi']; ?>)</span>
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
