<?php
/**
 * Primers within ±800 nt of an SNV coordinate (Jim Kelley, 2026-08-31).
 * Pango lineages: designation markers v1.9 (Jim Kelley, 2026-09-09).
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/two_segment_helpers.php';
require_once __DIR__ . '/snv_pango_helpers.php';

$coord = 0;
if (isset($_GET['coord']) && is_numeric($_GET['coord'])) {
    $coord = (int) $_GET['coord'];
}
$refBase = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$altBase = isset($_GET['alt']) ? trim((string) $_GET['alt']) : '';
$protein = isset($_GET['protein']) ? trim((string) $_GET['protein']) : '';
$selectedPrimerId = '';
if (isset($_GET['primer']) && ctype_digit((string) $_GET['primer'])) {
    $selectedPrimerId = (string) $_GET['primer'];
}

$primerWindow = 800;
$primerLayout = (isset($_GET['layout']) && $_GET['layout'] === 'compact') ? 'compact' : 'detailed';
$primerSchemes = [];
$selectedPrimerSchemes = [];
$selectedSchemeCodes = [];
$snvPrimers = [];
$pangoLineages = [];
$pangoGroups = [];
$dbError = null;
$primerDbError = null;

if ($coord < 1 || $coord > 29903) {
    $dbError = 'Pass a genome coordinate in ?coord= (1–29903). Example: SnvPrimerView.php?coord=23202';
} elseif (isset($con) && $con instanceof mysqli && !$con->connect_errno) {
    if ($refBase !== '' && $altBase !== '') {
        $pangoLineages = snv_pango_lineages_for($con, $coord, $refBase, $altBase);
    } else {
        $pangoGroups = snv_pango_groups_at_coord($con, $coord);
    }
    if (tsg_primer_tables_exist($con)) {
        $primerSchemes = tsg_fetch_primer_schemes($con);
        if (isset($_GET['schemes_submitted'])) {
            $selectedSchemeCodes = tsg_selected_scheme_codes(isset($_GET['schemes']) ? $_GET['schemes'] : [], $primerSchemes);
        } else {
            $selectedSchemeCodes = tsg_all_scheme_codes($primerSchemes);
        }
        foreach ($primerSchemes as $scheme) {
            if (in_array((string) $scheme['code'], $selectedSchemeCodes, true)) {
                $selectedPrimerSchemes[] = $scheme;
            }
        }
        $pack = tsg_fetch_primers_near_coord($con, $coord, $selectedSchemeCodes, $primerWindow);
        $snvPrimers = $pack['primers'];
        $primerDbError = $pack['error'];
    } else {
        $primerDbError = 'Primer tables are not installed. Import sql/primer_arrows.sql.';
    }
} else {
    $dbError = 'Database connection not available.';
}

$snvLabel = ($refBase !== '' && $altBase !== '')
    ? snv_pango_format_label($coord, $refBase, $altBase)
    : ('SNV ' . $coord);
$titleBits = [$snvLabel];
if ($protein !== '') {
    $titleBits[] = $protein;
}
$pageTitle = implode(' — ', $titleBits);

$primersByScheme = [];
foreach ($snvPrimers as $primer) {
    $label = (string) $primer['scheme_label'];
    if (!isset($primersByScheme[$label])) {
        $primersByScheme[$label] = [];
    }
    $primersByScheme[$label][] = $primer;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> — primers — SARSNTDB</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="stylesheet" type="text/css" href="two_segment_viz.css?v=20260909-pango" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <?php include __DIR__ . '/Navigation.php'; ?>
</head>
<body class="tsg-page">
<div class="panel panel-default" style="margin: 15px;">
    <div class="panel-heading">
        <h4 class="search-header" style="margin:0;"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
    </div>
    <div class="panel-body">
        <p style="font-size:13px; max-width:900px;">
            Primers whose start or end is within <strong>&plusmn;<?php echo (int) $primerWindow; ?> nt</strong>
            of this SNV (same window idea as junction primer arrows).
            Use the primer list to show one oligo at a time.
            <a href="MutationsSearch.php">Back to Mutations search</a>.
        </p>
        <?php if ($pangoLineages) : ?>
            <p style="font-size:13px; max-width:900px;">
                <strong>Pango lineages</strong> (designation markers, ratio &lt; 0.2):
                <?php echo snv_pango_html_list($pangoLineages); ?>
            </p>
        <?php elseif ($pangoGroups) : ?>
            <div style="font-size:13px; max-width:900px;">
                <strong>Pango lineages</strong> at this coordinate (designation markers, ratio &lt; 0.2):
                <ul style="margin:6px 0 0 18px;">
                    <?php foreach ($pangoGroups as $group) : ?>
                    <li>
                        <strong><?php echo htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        — <?php echo snv_pango_html_list($group['lineages']); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($coord >= 1 && $refBase !== '' && $altBase !== '') : ?>
            <p class="text-muted" style="font-size:13px; max-width:900px;">
                No pango designation-marker lineages for this SNV (needs a single-base change with ratio &lt; 0.2).
            </p>
        <?php endif; ?>

        <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning" style="max-width:900px;"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($primerDbError !== null) : ?>
            <div class="alert alert-warning" style="max-width:900px;">
                <strong>Primers:</strong> <?php echo htmlspecialchars($primerDbError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($coord >= 1 && $primerSchemes) : ?>
        <form method="get" class="tsg-scheme-picker" style="max-width:900px;">
            <input type="hidden" name="schemes_submitted" value="1" />
            <input type="hidden" name="coord" value="<?php echo (int) $coord; ?>" />
            <?php if ($refBase !== '') : ?>
                <input type="hidden" name="ref" value="<?php echo htmlspecialchars($refBase, ENT_QUOTES, 'UTF-8'); ?>" />
            <?php endif; ?>
            <?php if ($altBase !== '') : ?>
                <input type="hidden" name="alt" value="<?php echo htmlspecialchars($altBase, ENT_QUOTES, 'UTF-8'); ?>" />
            <?php endif; ?>
            <?php if ($protein !== '') : ?>
                <input type="hidden" name="protein" value="<?php echo htmlspecialchars($protein, ENT_QUOTES, 'UTF-8'); ?>" />
            <?php endif; ?>
            <?php if ($selectedPrimerId !== '') : ?>
                <input type="hidden" name="primer" id="tsgPrimerHidden" value="<?php echo htmlspecialchars($selectedPrimerId, ENT_QUOTES, 'UTF-8'); ?>" />
            <?php else : ?>
                <input type="hidden" name="primer" id="tsgPrimerHidden" value="" />
            <?php endif; ?>
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
            <?php if ($primersByScheme) : ?>
            <div class="tsg-primer-select-row">
                <label for="tsgPrimerSelect"><strong>Primer:</strong></label>
                <select id="tsgPrimerSelect" class="form-control input-sm">
                    <option value="">All primers in window</option>
                    <?php foreach ($primersByScheme as $schemeLabel => $primers) : ?>
                    <optgroup label="<?php echo htmlspecialchars($schemeLabel, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php foreach ($primers as $primer) : ?>
                        <option value="<?php echo (int) $primer['id']; ?>"<?php echo $selectedPrimerId === (string) $primer['id'] ? ' selected' : ''; ?>>
                            <?php echo htmlspecialchars($primer['primer_name'] . ' (' . (int) $primer['coord_start'] . '–' . (int) $primer['coord_end'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>
        <?php endif; ?>

        <div id="tsg-viz-wrap">
            <div id="tsg-viz-output"></div>
        </div>
    </div>
</div>

<script>
window.TSG_SNV_COORD = <?php echo (int) $coord; ?>;
window.TSG_SNV_PRIMERS = <?php echo tsg_json_for_script($snvPrimers); ?>;
window.TSG_SELECTED_SCHEMES = <?php echo tsg_json_for_script($selectedPrimerSchemes); ?>;
window.TSG_PRIMER_WINDOW = <?php echo (int) $primerWindow; ?>;
window.TSG_PRIMER_LAYOUT = <?php echo tsg_json_for_script($primerLayout); ?>;
window.TSG_SELECTED_PRIMER_ID = <?php echo tsg_json_for_script($selectedPrimerId); ?>;
</script>
<script src="JS/twoSegmentViz.js?v=20260909-pango"></script>
<script>
(function () {
    var sel = document.getElementById('tsgPrimerSelect');
    if (sel) {
        sel.addEventListener('change', function () {
            window.TSG_SELECTED_PRIMER_ID = this.value || '';
            var hidden = document.getElementById('tsgPrimerHidden');
            if (hidden) {
                hidden.value = window.TSG_SELECTED_PRIMER_ID;
            }
            try {
                var url = new URL(window.location.href);
                if (window.TSG_SELECTED_PRIMER_ID) {
                    url.searchParams.set('primer', window.TSG_SELECTED_PRIMER_ID);
                } else {
                    url.searchParams.delete('primer');
                }
                window.history.replaceState({}, '', url.toString());
            } catch (ignore) {}
            if (window.TwoSegmentViz) {
                window.TwoSegmentViz.showSnvWindow();
            }
        });
    }
    if (window.TwoSegmentViz && window.TSG_SNV_COORD) {
        TwoSegmentViz.showSnvWindow();
    }
})();
</script>
</body>
</html>
