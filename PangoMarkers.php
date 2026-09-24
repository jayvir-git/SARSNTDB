<?php
/**
 * Published Pango designation-marker SNVs and indels (Jim Kelley v1.9 workbook).
 * Independent of Mutations VCF/Original group selection.
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/snv_pango_helpers.php';

$start = 1;
$end = 29903;
if (isset($_GET['Start']) && is_numeric($_GET['Start'])) {
    $start = (int) $_GET['Start'];
} elseif (isset($_GET['start']) && is_numeric($_GET['start'])) {
    $start = (int) $_GET['start'];
}
if (isset($_GET['End']) && is_numeric($_GET['End'])) {
    $end = (int) $_GET['End'];
} elseif (isset($_GET['end']) && is_numeric($_GET['end'])) {
    $end = (int) $_GET['end'];
}
if ($start < 1) {
    $start = 1;
}
if ($end > 29903) {
    $end = 29903;
}
if ($start > $end) {
    $tmp = $start;
    $start = $end;
    $end = $tmp;
}
$lineageNeedle = isset($_GET['lineage']) ? trim((string) $_GET['lineage']) : '';

$dbError = null;
$snvRows = [];
$indelRows = [];
$snvTableOk = false;
$indelTableOk = false;

function pango_page_row_matches_lineage(array $row, $needle)
{
    if ($needle === '') {
        return true;
    }
    foreach ($row['lineages'] as $name) {
        if (stripos((string) $name, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function pango_page_primer_url($coord, $ref, $alt, $kind)
{
    $url = 'SnvPrimerView.php?coord=' . (int) $coord
        . '&ref=' . rawurlencode((string) $ref)
        . '&alt=' . rawurlencode((string) $alt);
    if ($kind === 'indel') {
        $url .= '&kind=indel';
    }

    return $url;
}

if (!isset($con) || !($con instanceof mysqli) || $con->connect_errno) {
    $dbError = 'Database connection not available.';
} else {
    $snvTableOk = snv_pango_table_exists($con);
    $indelTableOk = pango_indel_table_exists($con);
    if (!$snvTableOk && !$indelTableOk) {
        $dbError = 'Pango marker tables are not installed. Import sql/snv_pango_marker.sql and sql/pango_indel_marker.sql.';
    } else {
        if ($snvTableOk) {
            foreach (snv_pango_in_range($con, $start, $end) as $row) {
                if (pango_page_row_matches_lineage($row, $lineageNeedle)) {
                    $snvRows[] = $row;
                }
            }
        }
        if ($indelTableOk) {
            foreach (pango_indel_in_range($con, $start, $end) as $row) {
                if (pango_page_row_matches_lineage($row, $lineageNeedle)) {
                    $indelRows[] = $row;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Pango designation markers — SARSNTDB</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <?php include __DIR__ . '/Navigation.php'; ?>
    <style>
        .pango-page .search-header { padding-left: 10px; }
        .pango-note { font-size: 13px; max-width: 980px; }
        .pango-table-wrap { max-height: 480px; overflow: auto; margin-top: 8px; }
        .pango-table { font-size: 12px; }
        .pango-table td, .pango-table th { vertical-align: top !important; }
        .pango-mono { font-family: Consolas, monospace; font-size: 11px; word-break: break-all; }
        tr.darkheader th { background: #333; color: #fff; position: sticky; top: 0; }
        .snv-pango-list { display: block; font-size: 11px; line-height: 1.45; }
        .snv-pango-item { white-space: nowrap; margin: 0 0 3px 0; }
        .pango-filter label { font-weight: normal; margin-right: 12px; }
    </style>
</head>
<body class="pango-page">
<div class="panel panel-default" style="margin: 15px;">
    <div class="panel-heading">
        <h4 class="search-header" style="margin:0;">Pango designation markers</h4>
    </div>
    <div class="panel-body">
        <p class="pango-note">
            Published Pango designation markers from Jim Kelley's v1.9 workbook
            (<code>pango-designation-markers-v1.9-calc-sort.xlsx</code>).
            These are <strong>not</strong> observations from a Mutations search group
            (Original or VCF). This page does not use the selected sample group.
        </p>
        <p class="pango-note">
            <strong>SNV rule:</strong> single-base REF and ALT, ratio &lt; 0.2.
            Labels use <code>T&lt;position&gt;G</code> (example G21987A).
            <strong>Indel rule:</strong> REF and ALT different lengths, ratio &lt; 0.2;
            longer REF is a deletion, longer ALT is an insertion.
            Indels are labeled with the coordinate and full <code>REF=</code> / <code>ALT=</code> alleles
            (example 685 deletion REF=AAAGTCATTT ALT=A).
            Click a row’s primer link for a ±800 nt window.
        </p>
        <p class="pango-note text-muted">
            A later table of indels actually observed in a sample group is not on this page.
            Do not treat these published markers as that table.
        </p>

        <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning" style="max-width:900px;"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="get" action="PangoMarkers.php" class="pango-filter" style="margin:12px 0;">
            <label>Start
                <input type="number" name="Start" class="form-control input-sm" style="display:inline-block;width:110px;"
                       min="1" max="29903" value="<?php echo (int) $start; ?>" />
            </label>
            <label>End
                <input type="number" name="End" class="form-control input-sm" style="display:inline-block;width:110px;"
                       min="1" max="29903" value="<?php echo (int) $end; ?>" />
            </label>
            <label>Lineage contains
                <input type="text" name="lineage" class="form-control input-sm" style="display:inline-block;width:180px;"
                       value="<?php echo htmlspecialchars($lineageNeedle, ENT_QUOTES, 'UTF-8'); ?>" />
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Query</button>
            <a class="btn btn-default btn-sm" href="PangoMarkers.php">Reset</a>
        </form>

        <h4 style="margin-top:18px;">Pango SNVs
            <small class="text-muted"><?php echo count($snvRows); ?> in this range</small>
        </h4>
        <div class="pango-table-wrap">
            <table class="table table-bordered table-striped pango-table">
                <thead>
                    <tr class="darkheader">
                        <th>Coordinate</th>
                        <th>Label</th>
                        <th>REF</th>
                        <th>ALT</th>
                        <th>Pango lineages</th>
                        <th>Primers</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$snvRows) : ?>
                    <tr><td colspan="6"><?php echo $snvTableOk ? 'No Pango designation-marker SNVs in this range.' : 'SNV marker table is not installed.'; ?></td></tr>
                <?php else : ?>
                    <?php foreach ($snvRows as $row) : ?>
                    <tr>
                        <td><?php echo (int) $row['coordinate']; ?></td>
                        <td class="pango-mono"><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pango-mono"><?php echo htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pango-mono"><?php echo htmlspecialchars($row['alternate'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo snv_pango_html_list($row['lineages']); ?></td>
                        <td><a href="<?php echo htmlspecialchars(pango_page_primer_url($row['coordinate'], $row['reference'], $row['alternate'], 'snv'), ENT_QUOTES, 'UTF-8'); ?>">±800 nt</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h4 style="margin-top:22px;">Pango indels
            <small class="text-muted"><?php echo count($indelRows); ?> in this range</small>
        </h4>
        <div class="pango-table-wrap">
            <table class="table table-bordered table-striped pango-table">
                <thead>
                    <tr class="darkheader">
                        <th>Coordinate</th>
                        <th>Type</th>
                        <th>REF</th>
                        <th>ALT</th>
                        <th>Allele label</th>
                        <th>Pango lineages</th>
                        <th>Primers</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$indelRows) : ?>
                    <tr><td colspan="7"><?php echo $indelTableOk ? 'No Pango designation-marker indels in this range.' : 'Indel marker table is not installed.'; ?></td></tr>
                <?php else : ?>
                    <?php foreach ($indelRows as $row) : ?>
                    <tr>
                        <td><?php echo (int) $row['coordinate']; ?></td>
                        <td><?php echo htmlspecialchars($row['kind'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pango-mono"><?php echo htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pango-mono"><?php echo htmlspecialchars($row['alternate'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pango-mono"><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo snv_pango_html_list($row['lineages']); ?></td>
                        <td><a href="<?php echo htmlspecialchars(pango_page_primer_url($row['coordinate'], $row['reference'], $row['alternate'], 'indel'), ENT_QUOTES, 'UTF-8'); ?>">±800 nt</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
