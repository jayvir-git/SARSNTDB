<?php
/**
 * Two-segment genomic structures — table + schematic view (sgmRNA first subtype).
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/two_segment_helpers.php';

$rows = [];
$dbError = null;
$primerDbError = null;
$primerWindow = 800;
$primerSchemes = [];
$selectedPrimerSchemes = [];
$selectedSchemeCodes = [];
$primersByStructure = [];
$primerLayout = (isset($_GET['layout']) && $_GET['layout'] === 'compact') ? 'compact' : 'detailed';

if (isset($con) && $con instanceof mysqli && !$con->connect_errno) {
    $sql = 'SELECT id, subtype, junction_kind, name, coord_from, coord_left, coord_right, coord_to, repeat_seq, link_url, notes, display_order
            FROM two_segment_structure
            ORDER BY display_order ASC, id ASC';
    $result = $con->query($sql);
    if (!$result && (int) $con->errno === 1054) {
        $sql = 'SELECT id, subtype, name, coord_from, coord_left, coord_right, coord_to, repeat_seq, link_url, notes, display_order
            FROM two_segment_structure
            ORDER BY display_order ASC, id ASC';
        $result = $con->query($sql);
    }
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!isset($row['junction_kind'])) {
                $row['junction_kind'] = 'CJ';
            }
            $rows[] = $row;
        }
        $result->free();
    } else {
        $dbError = $con->error;
    }
} else {
    $dbError = 'Database connection not available.';
}

$byId = [];
foreach ($rows as $r) {
    $byId[(string) $r['id']] = $r;
}

if (isset($con) && $con instanceof mysqli && !$con->connect_errno) {
    if (tsg_primer_tables_exist($con)) {
        $primerSchemes = tsg_fetch_primer_schemes($con);
        if (isset($_GET['schemes_submitted'])) {
            $selectedSchemeCodes = tsg_selected_scheme_codes(isset($_GET['schemes']) ? $_GET['schemes'] : [], $primerSchemes);
        } else {
            $selectedSchemeCodes = tsg_selected_scheme_codes(['artic_v3'], $primerSchemes);
        }
        foreach ($primerSchemes as $scheme) {
            if (in_array((string) $scheme['code'], $selectedSchemeCodes, true)) {
                $selectedPrimerSchemes[] = $scheme;
            }
        }
        $primerPack = tsg_fetch_primers_near_structures($con, $rows, $selectedSchemeCodes, $primerWindow);
        $primersByStructure = $primerPack['by_structure'];
        $primerDbError = $primerPack['error'];
    } else {
        $primerDbError = 'Primer-arrow tables are not installed. Import sql/primer_arrows.sql.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Two-segment structures — SARSNTDB</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="stylesheet" type="text/css" href="two_segment_viz.css?v=20260902-center" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <?php include __DIR__ . '/Navigation.php'; ?>
    <style>
        .tsg-page .search-header { padding-left: 10px; }
        .tsg-toolbar { margin: 12px 0; }
        tr.darkheader th {
            background: #333;
            color: #fff;
            position: sticky;
            top: 0;
            text-align: center;
        }
        .tsg-table-wrap { max-height: 420px; overflow: auto; margin-top: 8px; }
        .tsg-table { font-size: 12px; }
        .tsg-table td, .tsg-table th { vertical-align: middle !important; }
        .tsg-mono { font-family: Consolas, monospace; font-size: 11px; }
    </style>
</head>
<body class="tsg-page">
<div class="panel panel-default" style="margin: 15px;">
    <div class="panel-heading">
        <h4 class="search-header" style="margin:0;">Two-segment structures</h4>
    </div>
    <div class="panel-body">
        <p style="font-size:13px; max-width:900px;">
            Select entries that have two genome segments separated by a gap (conceptual fields:
            <code>from</code>, <code>left</code>, <code>right</code>, <code>to</code>, <code>repeat</code>, <code>link</code> reserved).
            The first implemented subtype is <strong>sgmRNA</strong>; additional subtypes can use the same table and UI.
            For group / variant / primer percents on junction 5249–23191, open
            <a href="JunctionGroupQuery.php?left=5249&amp;right=23191">Junction groups</a>.
        </p>

        <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning" style="max-width:900px;">
                <strong>Database:</strong> <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?>
                <?php if (stripos($dbError, "doesn't exist") !== false || stripos($dbError, 'Unknown table') !== false) : ?>
                    <p style="margin:8px 0 0 0;">Create the table and seed data by running
                        <code>sql/two_segment_structure.sql</code> on your MySQL database.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$rows && $dbError === null) : ?>
            <div class="alert alert-info" style="max-width:900px;">No rows in <code>two_segment_structure</code>. Import <code>sql/two_segment_structure.sql</code>.</div>
        <?php endif; ?>

        <?php if ($primerDbError !== null) : ?>
            <div class="alert alert-warning" style="max-width:900px;">
                <strong>Primer arrows:</strong> <?php echo htmlspecialchars($primerDbError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($primerSchemes) : ?>
        <form method="get" class="tsg-scheme-picker" style="max-width:900px;">
            <input type="hidden" name="schemes_submitted" value="1" />
            <?php foreach (['id', 'ids', 'left', 'right'] as $keepKey) : ?>
                <?php if (isset($_GET[$keepKey]) && !is_array($_GET[$keepKey])) : ?>
                    <input type="hidden" name="<?php echo $keepKey; ?>" value="<?php echo htmlspecialchars((string) $_GET[$keepKey], ENT_QUOTES, 'UTF-8'); ?>" />
                <?php endif; ?>
            <?php endforeach; ?>
            <strong>Primer schemes:</strong>
            <?php foreach ($primerSchemes as $scheme) : ?>
                <label class="checkbox-inline">
                    <input type="checkbox" name="schemes[]"
                           value="<?php echo htmlspecialchars($scheme['code'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array((string) $scheme['code'], $selectedSchemeCodes, true) ? ' checked' : ''; ?> />
                    <?php echo htmlspecialchars($scheme['label'], ENT_QUOTES, 'UTF-8'); ?>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-default btn-sm">Apply</button>
            <input type="hidden" name="layout" id="tsgLayoutHidden" value="<?php echo htmlspecialchars($primerLayout, ENT_QUOTES, 'UTF-8'); ?>" />
            <span class="tsg-view-toggle">
                <strong>View:</strong>
                <label class="checkbox-inline"><input type="checkbox" id="tsgLayoutDetailed" /> Detailed</label>
                <label class="checkbox-inline"><input type="checkbox" id="tsgLayoutCompact" /> Compact</label>
            </span>
            <span class="help-block" style="margin-bottom:0;">
                The full-junction track is an overview. Primer arrows appear only in separate left/right
                &plusmn;<?php echo (int) $primerWindow; ?> nt breakpoint panels, drawn to scale, with each LEFT/RIGHT pair on its own line.
                Midnight primers are spaced ~1,120 bp apart.
            </span>
        </form>
        <?php endif; ?>

        <?php if ($rows) : ?>
        <div class="tsg-toolbar">
            <button type="button" class="btn btn-primary" onclick="TwoSegmentViz.showSelected();">Show</button>
        </div>

        <div class="tsg-table-wrap">
            <table class="table table-bordered table-striped tsg-table">
                <thead>
                    <tr class="darkheader">
                        <th style="width:36px;">Sel.</th>
                        <th>Subtype</th>
                        <th>CJ / NJ</th>
                        <th>Name</th>
                        <th>from</th>
                        <th>left</th>
                        <th>right</th>
                        <th>to</th>
                        <th>repeat</th>
                        <th>link</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="tsg_sel[]" value="<?php echo (int) $row['id']; ?>" />
                        </td>
                        <td><?php echo htmlspecialchars($row['subtype'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(tsg_junction_kind_label($row), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-right"><?php echo (int) $row['coord_from']; ?></td>
                        <td class="text-right"><?php echo (int) $row['coord_left']; ?></td>
                        <td class="text-right"><?php echo (int) $row['coord_right']; ?></td>
                        <td class="text-right"><?php echo (int) $row['coord_to']; ?></td>
                        <td class="tsg-mono"><?php echo $row['repeat_seq'] !== null && $row['repeat_seq'] !== ''
                            ? htmlspecialchars($row['repeat_seq'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                        <td class="tsg-mono"><?php
                            if ($row['link_url'] !== null && $row['link_url'] !== '') {
                                $lu = (string) $row['link_url'];
                                $safe = htmlspecialchars($lu, ENT_QUOTES, 'UTF-8');
                                if (preg_match('/^[A-Za-z0-9_.:\\/?=&%-]+$/', $lu) && strpos($lu, '://') === false) {
                                    echo '<a href="' . $safe . '">' . $safe . '</a>';
                                } else {
                                    echo $safe;
                                }
                            } else {
                                echo '—';
                            }
                        ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div id="tsg-viz-wrap">
            <div id="tsg-viz-output"></div>
        </div>
    </div>
</div>

<script>
window.TSG_ENTRIES = <?php echo json_encode(empty($byId) ? new stdClass() : $byId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.TSG_PRIMERS = <?php echo json_encode(empty($primersByStructure) ? new stdClass() : $primersByStructure, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.TSG_SELECTED_SCHEMES = <?php echo json_encode($selectedPrimerSchemes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.TSG_PRIMER_WINDOW = <?php echo (int) $primerWindow; ?>;
window.TSG_PRIMER_LAYOUT = <?php echo json_encode($primerLayout); ?>;
</script>
<script src="JS/twoSegmentViz.js?v=20260902-center"></script>
<script>
(function ($) {
    $(function () {
        var params = new URLSearchParams(window.location.search);
        var raw = params.get('id') || params.get('ids');
        if (!window.TwoSegmentViz) {
            return;
        }
        var ids = raw ? String(raw).split(/[\s,]+/).map(function (s) {
                return parseInt(s, 10);
            }).filter(function (n) {
                return n > 0;
            }) : [];
        var left = parseInt(params.get('left'), 10);
        var right = parseInt(params.get('right'), 10);
        if (isFinite(left) && isFinite(right)) {
            Object.keys(window.TSG_ENTRIES || {}).forEach(function (id) {
                var entry = window.TSG_ENTRIES[id];
                if (Number(entry.coord_left) === left && Number(entry.coord_right) === right) {
                    ids.push(parseInt(id, 10));
                }
            });
        }
        ids = ids.filter(function (id, index, all) { return all.indexOf(id) === index; });
        ids.forEach(function (id) {
            var inp = document.querySelector('input[name="tsg_sel[]"][value="' + id + '"]');
            if (inp) {
                inp.checked = true;
            }
        });
        if (ids.length) {
            TwoSegmentViz.showSelected();
            var w = document.getElementById('tsg-viz-wrap');
            if (w) {
                setTimeout(function () {
                    w.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 150);
            }
        }
    });
})(jQuery);
</script>
</body>
</html>
