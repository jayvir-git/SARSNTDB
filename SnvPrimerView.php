<?php
/**
 * Primers within ±800 nt of an SNV coordinate (Jim Kelley, 2026-08-31).
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/two_segment_helpers.php';

$coord = 0;
if (isset($_GET['coord']) && is_numeric($_GET['coord'])) {
    $coord = (int) $_GET['coord'];
}
$refBase = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
$altBase = isset($_GET['alt']) ? trim((string) $_GET['alt']) : '';
$protein = isset($_GET['protein']) ? trim((string) $_GET['protein']) : '';

$primerWindow = 800;
$primerLayout = (isset($_GET['layout']) && $_GET['layout'] === 'compact') ? 'compact' : 'detailed';
$primerSchemes = [];
$selectedPrimerSchemes = [];
$selectedSchemeCodes = [];
$snvPrimers = [];
$dbError = null;
$primerDbError = null;

if ($coord < 1 || $coord > 29903) {
    $dbError = 'Pass a genome coordinate in ?coord= (1–29903). Example: SnvPrimerView.php?coord=23202';
} elseif (isset($con) && $con instanceof mysqli && !$con->connect_errno) {
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
        $pack = tsg_fetch_primers_near_coord($con, $coord, $selectedSchemeCodes, $primerWindow);
        $snvPrimers = $pack['primers'];
        $primerDbError = $pack['error'];
    } else {
        $primerDbError = 'Primer tables are not installed. Import sql/primer_arrows.sql.';
    }
} else {
    $dbError = 'Database connection not available.';
}

$titleBits = ['SNV ' . $coord];
if ($refBase !== '' && $altBase !== '') {
    $titleBits[] = $refBase . '>' . $altBase;
}
if ($protein !== '') {
    $titleBits[] = $protein;
}
$pageTitle = implode(' — ', $titleBits);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> — primers — SARSNTDB</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="stylesheet" type="text/css" href="two_segment_viz.css?v=20260901-thick" />
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
            <a href="MutationsSearch.php">Back to Mutations search</a>.
        </p>

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
        </form>
        <?php endif; ?>

        <div id="tsg-viz-wrap">
            <div id="tsg-viz-output"></div>
        </div>
    </div>
</div>

<script>
window.TSG_SNV_COORD = <?php echo (int) $coord; ?>;
window.TSG_SNV_PRIMERS = <?php echo json_encode($snvPrimers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.TSG_SELECTED_SCHEMES = <?php echo json_encode($selectedPrimerSchemes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.TSG_PRIMER_WINDOW = <?php echo (int) $primerWindow; ?>;
window.TSG_PRIMER_LAYOUT = <?php echo json_encode($primerLayout); ?>;
</script>
<script src="JS/twoSegmentViz.js?v=20260901-thick"></script>
<script>
(function () {
    if (window.TwoSegmentViz && window.TSG_SNV_COORD) {
        TwoSegmentViz.showSnvWindow();
    }
})();
</script>
</body>
</html>
