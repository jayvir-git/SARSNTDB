<?php
/**
 * Query groups / variants / primers for a junction (Jim Kelley prototype).
 * Data: sql/junction_query.sql + sql/junction_viridian_counts.sql
 * Reverse: sql/junction_query_drop.sql
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/junction_query_helpers.php';
require_once __DIR__ . '/nj_read_helpers.php';

$dbError = null;
$tablesOk = false;
$viridianOk = false;
$datasets = [];
$options = [
    'continents' => [],
    'instruments' => [],
    'variants' => [],
    'primers' => [],
    'groups' => [],
];
$viridianGroups = [];
$viridianVariantPivot = null;
$viridianPairPivot = null;

$left = isset($_GET['left']) && is_numeric($_GET['left']) ? (int) $_GET['left'] : 5249;
$right = isset($_GET['right']) && is_numeric($_GET['right']) ? (int) $_GET['right'] : 23191;
$selContinents = [];
$continentAll = true;
$selInstruments = [];
$instrumentAll = true;
$njVisibleGroups = [];
$njGroupRows = [];
$njGroupCodes = [];
$njBy = 'pair';
$njKeepSig = '';
$njEarlyCounts = [];
$njMergeDelta = false;
$njChoices = [];
$njSelectedCols = [];
$njPick = null;
$njOptions = nj_read_request_merge_options();
$njSummary = [];
$njCatalog = [];
$njNgeneIds = [];
$njKeep = [];
$njAllDenoms = [];
$njChartNames = [];
list($selPrimers, $primerAll) = jq_parse_all_list('primer', 'primer_all');
list($selVariants, $variantAll) = jq_parse_all_list('variant', 'variant_all');
list($selGroups, $groupAll) = jq_parse_all_list('group', 'group_all');
$groupDirectory = [];
$requireSamples = !isset($_GET['query']) || (isset($_GET['require_samples']) && $_GET['require_samples'] === '1');
$didQuery = isset($_GET['query']);
$chartType = isset($_GET['chart_type']) ? (string) $_GET['chart_type'] : 'clustered';
if (!in_array($chartType, ['clustered', 'stacked', 'primer'], true)) {
    $chartType = 'clustered';
}
$legendKey = isset($_GET['legend_key']) ? (string) $_GET['legend_key'] : 'variant';
if (!in_array($legendKey, ['variant', 'group'], true)) {
    $legendKey = 'variant';
}

if (isset($con) && $con instanceof mysqli && !$con->connect_errno) {
    $tablesOk = jq_tables_exist($con);
    $viridianOk = jq_viridian_tables_exist($con);
    if ($tablesOk) {
        $datasets = jq_fetch_datasets($con);
        if ($datasets) {
            $left = (int) $datasets[0]['junction_left'];
            $right = (int) $datasets[0]['junction_right'];
            if (isset($_GET['left']) && is_numeric($_GET['left'])) {
                $left = (int) $_GET['left'];
            }
            if (isset($_GET['right']) && is_numeric($_GET['right'])) {
                $right = (int) $_GET['right'];
            }
        }
        $options = jq_filter_options($con, $left, $right);
        $groupDirectory = jq_fetch_group_directory($con, $left, $right);
    } else {
        $dbError = 'Junction query tables are not installed.';
    }
    if ($viridianOk) {
        $viridianGroups = jq_fetch_viridian_groups($con);
        $viridianPairRows = jq_fetch_viridian_pairs($con);
        if (!$primerAll && $selPrimers) {
            $viridianPairRows = jq_filter_viridian_pairs($viridianPairRows, $selPrimers);
            $viridianVariantPivot = jq_pivot_viridian(
                jq_viridian_variants_from_pairs($viridianPairRows),
                $viridianGroups,
                false
            );
        } else {
            $viridianVariantPivot = jq_pivot_viridian(jq_fetch_viridian_variants($con), $viridianGroups, false);
        }
        $viridianPairPivot = jq_pivot_viridian($viridianPairRows, $viridianGroups, true);
        if ($tablesOk) {
            $options['primers'] = jq_merge_primer_options(
                $options['primers'],
                jq_fetch_viridian_primer_names($con)
            );
        }
    }
    $vcfGroups = vcf_snv_fetch_groups($con);
    $njKeep = nj_read_request_keep();
    $njOptions = nj_read_request_merge_options();
    $njVisibleGroups = $vcfGroups;
    if ($njKeep) {
        $njVisibleGroups = [];
        foreach ($vcfGroups as $vcfGroupRow) {
            if (in_array($vcfGroupRow['code'], $njKeep, true)) {
                $njVisibleGroups[] = $vcfGroupRow;
            }
        }
    }
    $njVisibleCodes = [];
    foreach ($njVisibleGroups as $vcfGroupRow) {
        $njVisibleCodes[] = $vcfGroupRow['code'];
    }
    $njKeepSig = implode("\n", $njKeep);
    $njPrevKeepSig = isset($_GET['NjKeepSig']) ? (string) $_GET['NjKeepSig'] : '';
    $njGroupCodes = nj_read_filter_group_codes(
        $njVisibleCodes,
        nj_read_request_group_codes($njVisibleGroups),
        $njKeepSig,
        $njPrevKeepSig
    );
    $njBy = nj_read_request_breakdown_by();
    $njMergeDelta = !empty($njOptions['merge_delta']);
    $njPick = nj_read_request_pick();
    $njGroupIds = [];
    foreach ($njVisibleGroups as $vcfGroupRow) {
        if (in_array($vcfGroupRow['code'], $njGroupCodes, true)) {
            $njGroupRows[] = $vcfGroupRow;
            $njGroupIds[] = (int) $vcfGroupRow['id'];
        }
    }
    $njChoices = nj_read_breakdown_choices(nj_read_label_pairs($con, $njGroupIds), $njBy, $njOptions);
    $njSelectedCols = nj_read_request_columns($njChoices);
    $njAllIds = [];
    foreach ($vcfGroups as $vcfGroupRow) {
        $njAllIds[] = (int) $vcfGroupRow['id'];
    }
    $njMinReads = nj_read_request_min_reads();
    $njWantNgene = nj_read_request_ngene();
    $njAllNgene = $njWantNgene ? nj_read_ngene_group_ids($con, $njAllIds) : [];
    $njAllDenoms = nj_read_pair_denoms($con, $njAllIds, $njMinReads, $njAllNgene);
    $njEarlyCounts = nj_read_early_counts($con);
    $njNgeneIds = $njWantNgene ? array_values(array_intersect($njAllNgene, $njGroupIds)) : [];
    $njDenoms = [];
    foreach ($njGroupIds as $njGroupId) {
        if (isset($njAllDenoms[$njGroupId])) {
            $njDenoms[$njGroupId] = $njAllDenoms[$njGroupId];
        }
    }
    $njHits = nj_read_junction_hits($con, $njGroupIds, $njMinReads, $njNgeneIds);
    $njCatalog = nj_read_catalog_rows($con);
    $njSummary = nj_read_summary_rows($njCatalog, $njGroupIds, $njDenoms, $njHits, $njOptions);
    $njChartNames = nj_read_chart_group_names($njGroupCodes);
} else {
    $dbError = 'Database connection not available.';
    $vcfGroups = [];
    $njKeep = [];
    $njOptions = nj_read_request_merge_options();
    $njAllDenoms = [];
    $njSummary = [];
    $njCatalog = [];
    $njChartNames = [];
}

$baseFilters = [
    'left' => $left,
    'right' => $right,
    'continents' => $selContinents,
    'instruments' => $selInstruments,
    'groups' => !empty($njChartNames) ? $njChartNames : ['__none__'],
    'variants' => $selVariants,
    'primers' => [],
];

function jq_load_dataset_rows(mysqli $con, array $datasets, $code, array $baseFilters, array $selPrimers, $applyPrimer, $requireSamples, array $selVariants)
{
    $ds = jq_dataset_by_code($datasets, $code);
    if (!$ds) {
        return ['dataset' => null, 'rows' => [], 'pivot' => jq_pivot_table([])];
    }
    $filters = $baseFilters;
    $filters['dataset_id'] = (int) $ds['id'];
    if ($applyPrimer) {
        $filters['primers'] = $selPrimers;
    }
    $rows = jq_fetch_measures($con, $filters);
    if ($requireSamples && $selVariants) {
        $rows = jq_apply_require_samples($rows, $selVariants);
    }

    return ['dataset' => $ds, 'rows' => $rows, 'pivot' => jq_pivot_table($rows)];
}

function jq_series_for_pivot(array $pivot, $legendKey, $normalize = false)
{
    if ($legendKey === 'group') {
        return jq_chart_series_by_group($pivot, $normalize);
    }

    return jq_chart_series($pivot, $normalize);
}

$tablePack = ['dataset' => null, 'rows' => [], 'pivot' => jq_pivot_table([])];
$packClustered = $tablePack;
$packStacked = $tablePack;
$packPrimer = $tablePack;

if ($tablesOk && $datasets && isset($con) && $con instanceof mysqli) {
    $packClustered = jq_load_dataset_rows($con, $datasets, 'group_variant', $baseFilters, $selPrimers, false, $requireSamples, $selVariants);
    $packStacked = jq_load_dataset_rows($con, $datasets, 'group_variant_8', $baseFilters, $selPrimers, false, $requireSamples, $selVariants);
    $packPrimer = jq_load_dataset_rows($con, $datasets, 'group_primer', $baseFilters, $selPrimers, !$primerAll, $requireSamples, $selVariants);

    if (!$primerAll && $selPrimers) {
        $tablePack = $packPrimer;
    } else {
        $tablePack = $packClustered;
    }
}

$primerActive = !$primerAll && $selPrimers;
$legendNote = $legendKey === 'group' ? 'Color key = groups' : 'Color key = variants';
$clusteredPivot = $primerActive ? $packPrimer['pivot'] : $packClustered['pivot'];
$clusteredTitle = $primerActive
    ? 'Variant–primer pairs, primer filter on (' . $legendNote . ')'
    : '% of samples (' . $legendNote . ')';
$clusteredEmpty = $primerActive
    ? 'No primer-pair rows for these filters.'
    : 'No group×variant rows for these filters.';

$stackedSeries = jq_series_for_pivot($packStacked['pivot'], $legendKey, true);
$stackedHas = false;
foreach ($stackedSeries as $s) {
    if (!empty($s['dataPoints'])) {
        $stackedHas = true;
        break;
    }
}
$stackedTitle = 'Stacked 8-group workbook (percent ÷ number of groups)';
if ($primerActive) {
    $stackedTitle .= ' — primer filter does not apply to this workbook';
}
$stackedPivotForChart = $packStacked['pivot'];
if (!$stackedHas) {
    $stackedPivotForChart = $packClustered['pivot'];
    $stackedTitle = 'Stacked (percent ÷ number of groups in this query)';
}

$size = $right - $left + 1;
$chartPayload = [
    'chartType' => $chartType,
    'legendKey' => $legendKey,
    'charts' => [
        'clustered' => [
            'title' => $clusteredTitle,
            'yTitle' => '% of samples',
            'series' => jq_series_for_pivot($clusteredPivot, $legendKey, false),
            'empty' => $clusteredEmpty,
        ],
        'stacked' => [
            'title' => $stackedTitle . ' (' . $legendNote . ')',
            'yTitle' => '% / n groups',
            'series' => jq_series_for_pivot($stackedPivotForChart, $legendKey, true),
            'empty' => 'No rows for a stacked chart with these filters.',
        ],
        'primer' => [
            'title' => 'Variant–primer pairs (' . $legendNote . ')',
            'yTitle' => '% of samples',
            'series' => jq_series_for_pivot($packPrimer['pivot'], $legendKey, false),
            'empty' => 'No primer-pair rows for these filters.',
        ],
    ],
];

function jq_checked(array $selected, $value)
{
    return in_array((string) $value, $selected, true) ? ' checked' : '';
}

function jq_viridian_label($name)
{
    if ((string) $name === '.') {
        return 'No variant call (.)';
    }

    return (string) $name;
}

function jq_csv_query($kind)
{
    $query = $_GET;
    $query['csv'] = $kind;
    $query['query'] = '1';

    return 'JunctionGroupQuery.php?' . http_build_query($query);
}

function jq_group_codes(array $groupRows)
{
    $codes = [];
    foreach ($groupRows as $group) {
        $codes[] = (string) $group['code'];
    }

    return $codes;
}

function jq_breakdown_csv(mysqli $con, array $groupRows, array $cols, $by, array $options, $size, $tag, $minReads, array $ngeneIds)
{
    $heads = ['Group'];
    foreach ($cols as $csvKey) {
        $head = nj_read_column_heads($csvKey, $by);
        $heads[] = trim($head[0] . ' ' . $head[1]);
    }
    $lines = [nj_read_csv_line($heads)];
    $ids = [];
    foreach ($groupRows as $group) {
        $ids[] = (int) $group['id'];
    }
    $raw = nj_read_breakdown_raw($con, $ids, $size, $tag, $minReads, $ngeneIds);
    $cells = nj_read_fold_breakdown($raw, $cols, $by, $options);
    foreach ($groupRows as $group) {
        $line = [$group['label']];
        foreach ($cols as $csvKey) {
            $cellKey = (int) $group['id'] . "\t" . $csvKey;
            $cell = isset($cells[$cellKey]) ? $cells[$cellKey] : ['n' => 0, 'd' => 0];
            $line[] = nj_read_breakdown_display($cell['n'], $cell['d'], $options['min_samples'], !empty($options['show_below']));
        }
        $lines[] = nj_read_csv_line($line);
    }

    return "\xEF\xBB\xBF" . implode("\n", $lines) . "\n";
}

$njFilterMode = in_array($njBy, ['variant', 'primer', 'pair'], true) ? $njBy : 'pair';
$njFilterTable = nj_read_filter_table(
    isset($vcfGroups) ? $vcfGroups : [],
    isset($njAllDenoms) ? $njAllDenoms : [],
    isset($njEarlyCounts) ? $njEarlyCounts : [],
    $njFilterMode,
    $njOptions
);

if (isset($_GET['csv']) && (string) $_GET['csv'] !== '' && isset($con) && $con instanceof mysqli) {
    $csvKind = (string) $_GET['csv'];
    if ($csvKind === 'filter') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . nj_read_filter_download_name($njFilterTable['mode'], $njOptions['min_samples']) . '"');
        echo "\xEF\xBB\xBF";
        foreach ($njFilterTable['header_rows'] as $filterHeader) {
            echo nj_read_csv_line($filterHeader) . "\n";
        }
        foreach ($njFilterTable['rows'] as $filterCsvRow) {
            echo nj_read_csv_line($filterCsvRow) . "\n";
        }
        exit;
    }
    $csvCodes = jq_group_codes($njGroupRows);
    $csvReady = $njSelectedCols && $njGroupRows;
    if ($csvKind === 'junctions' && $csvReady) {
        $catalogPicks = [];
        foreach ($njCatalog as $catalogRow) {
            $catalogPicks[nj_read_pick_value($catalogRow['nj_size'], $catalogRow['coord_tag'])] = true;
        }
        $chosen = [];
        foreach (nj_read_request_junction_picks() as $junctionPick) {
            $pickValue = nj_read_pick_value($junctionPick['nj_size'], $junctionPick['coord_tag']);
            if (isset($catalogPicks[$pickValue])) {
                $chosen[] = $junctionPick;
            }
        }
        if ($chosen) {
            $files = [];
            foreach ($chosen as $junctionPick) {
                $files[nj_read_junction_download_name($junctionPick['nj_size'], $csvCodes, false, $junctionPick['coord_tag'])] = jq_breakdown_csv(
                    $con,
                    $njGroupRows,
                    $njSelectedCols,
                    $njBy,
                    $njOptions,
                    $junctionPick['nj_size'],
                    $junctionPick['coord_tag'],
                    nj_read_request_min_reads(),
                    $njNgeneIds
                );
            }
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . nj_read_junction_download_name(0, $csvCodes, true) . '"');
            echo nj_read_zip_bytes($files);
            exit;
        }
    }
    $breakdownPick = $njPick;
    $checkedForCsv = nj_read_request_junction_picks();
    if (count($checkedForCsv) === 1) {
        $breakdownPick = $checkedForCsv[0];
    }
    if ($csvKind === 'breakdown' && $breakdownPick !== null && $csvReady) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . nj_read_junction_download_name($breakdownPick['nj_size'], $csvCodes, false, $breakdownPick['coord_tag']) . '"');
        echo jq_breakdown_csv(
            $con,
            $njGroupRows,
            $njSelectedCols,
            $njBy,
            $njOptions,
            $breakdownPick['nj_size'],
            $breakdownPick['coord_tag'],
            nj_read_request_min_reads(),
            $njNgeneIds
        );
        exit;
    }
    header('Content-Type: text/csv; charset=UTF-8');
    $csvName = $csvKind === 'summary'
        ? nj_read_summary_download_name($csvCodes)
        : 'junction-' . preg_replace('/[^a-z]+/', '', $csvKind) . '.csv';
    header('Content-Disposition: attachment; filename="' . $csvName . '"');
    echo "\xEF\xBB\xBF";
    if ($csvKind === 'summary') {
        echo nj_read_csv_line(['NJ Size', 'start', 'end', 'Delta V3', 'Omi V3', 'Delta V4.1', 'Omi V4.1', 'Overall Average']) . "\n";
        foreach ($njSummary as $csvRow) {
            echo nj_read_csv_line([
                $csvRow['nj_size'],
                $csvRow['nj_start'] === null ? '' : $csvRow['nj_start'],
                $csvRow['nj_end'] === null ? '' : $csvRow['nj_end'],
                $csvRow['delta_v3'],
                $csvRow['omi_v3'],
                $csvRow['delta_v41'],
                $csvRow['omi_v41'],
                $csvRow['overall'],
            ]) . "\n";
        }
    } else {
        echo nj_read_csv_line(['notice']) . "\n";
        echo nj_read_csv_line(['Choose a junction and at least one column before downloading that table.']) . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Junction groups — SARSNTDB</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="./canvasjs-non-commercial-3.6.6/canvasjs.min.js"></script>
    <script src="./sortable.js"></script>
    <?php include __DIR__ . '/Navigation.php'; ?>
    <style>
        .jq-page .search-header { padding-left: 10px; }
        .jq-filters label { font-weight: normal; margin-right: 10px; }
        .jq-filter-block { margin-bottom: 10px; }
        .jq-filter-block strong { display: block; margin-bottom: 4px; }
        .jq-table-wrap { max-height: 420px; overflow: auto; margin-top: 8px; }
        .jq-table { font-size: 12px; }
        .jq-table th, .jq-table td { text-align: center; vertical-align: middle !important; white-space: nowrap; }
        .jq-table.jq-pair th, .jq-table.jq-pair td { padding: 4px 3px; font-size: 11px; }
        .nj-datagrid { width: 100%; height: 500px; overflow: auto; margin-top: 8px; }
        .nj-datagrid table.sortable { border-collapse: collapse; width: 100%; table-layout: auto; }
        .nj-checks { max-height: 220px; overflow: auto; border: 1px solid #ccc; padding: 6px 8px; max-width: 720px; background: #fff; }
        .nj-checks label { display: block; font-weight: normal; margin: 0 0 2px 0; }
        .nj-breakgrid tr.nj-head-2 th { top: 29px; }
        .nj-datagrid tr.dark th {
            background: #333;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 1;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
        }
        .nj-datagrid table.sortable th,
        .nj-datagrid table.sortable td {
            padding: 6px 8px;
            vertical-align: top;
            border-right: 1px solid #bbb;
            overflow-wrap: anywhere;
        }
        .nj-datagrid table.sortable th:last-child,
        .nj-datagrid table.sortable td:last-child { border-right: none; }
        .nj-pick-row { cursor: pointer; }
        .nj-pick-current td { background-color: #cfe8ff !important; }
        button.nj-show {
            background: none;
            border: 0;
            padding: 0;
            color: inherit;
            font: inherit;
            cursor: pointer;
            text-decoration: underline;
        }
        select.nj-multi { height: auto; max-width: 640px; }
        .nj-breakgrid { width: 100%; max-height: 480px; overflow: auto; margin-top: 8px; }
        .nj-breakgrid table { border-collapse: collapse; }
        .nj-breakgrid th, .nj-breakgrid td {
            padding: 6px 8px;
            border: 1px solid #bbb;
            white-space: nowrap;
            text-align: right;
        }
        .nj-breakgrid tr.dark th {
            background: #333;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .nj-breakgrid th.nj-group, .nj-breakgrid td.nj-group {
            text-align: left;
            position: sticky;
            left: 0;
            z-index: 1;
        }
        .nj-breakgrid tr.dark th.nj-group { z-index: 3; }
        .jq-table th.jq-group, .jq-table td.jq-group { text-align: left; }
        .jq-missing { color: #999; }
        .jq-chart { height: 420px; width: 100%; margin: 12px 0 8px 0; }
        .jq-note { font-size: 13px; max-width: 980px; }
        tr.darkheader th { background: #333; color: #fff; position: sticky; top: 0; }
        .jq-table-wrap thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .jq-table-wrap tr.darkheader th {
            position: static;
            background: #333;
        }
        .jq-rollup { font-weight: bold; background: #f7f7f7; }
        .jq-chart-controls label { font-weight: normal; margin-right: 16px; }
        .jq-form .jq-submit { margin: 8px 0 12px 0; }
        .jq-color-key-title { font-weight: bold; margin-bottom: 6px; }
        .jq-color-key-list { list-style: none; padding: 0; margin: 0 0 16px 0; }
        .jq-color-key-list li { display: inline-block; margin: 0 12px 8px 0; font-size: 12px; }
        .jq-swatch { display: inline-block; width: 12px; height: 12px; margin-right: 6px; vertical-align: middle; border: 1px solid #888; }
        .jq-collapse-toggle { cursor: pointer; }
        .jq-panel-heading-link { display: block; color: inherit; text-decoration: none; }
        .jq-panel-heading-link:hover, .jq-panel-heading-link:focus { color: inherit; text-decoration: none; }
    </style>
</head>
<body class="jq-page">
<div class="panel panel-default" style="margin: 15px;">
    <div class="panel-heading">
        <h4 class="search-header" style="margin:0;">Junction groups, variants, and primers</h4>
    </div>
    <div class="panel-body">
        <p class="jq-note">
            Prototype for non-canonical junction
            <strong><?php echo (int) $left; ?>–<?php echo (int) $right; ?></strong>
            (size <?php echo (int) $size; ?>).
            A <strong>group</strong> is a location plus an instrument (example: New Jersey MiSeq).
            Percent tables: <strong>—</strong> means <code>-1</code> (no samples).
            Count tables use <strong>Viridian variant calls</strong> and <strong>Viridian primer calls</strong> (NJ and NM examples for now).
        </p>

        <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning" style="max-width:900px;">
                <strong>Database:</strong> <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!$tablesOk) : ?>
                    <p style="margin:8px 0 0 0;">Import <code>sql/junction_query.sql</code>.
                        For sample counts also import <code>sql/junction_viridian_counts.sql</code>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($tablesOk) : ?>
        <form method="get" action="JunctionGroupQuery.php" class="jq-form">
            <input type="hidden" name="left" value="<?php echo (int) $left; ?>" />
            <input type="hidden" name="right" value="<?php echo (int) $right; ?>" />
            <input type="hidden" name="query" value="1" />
            <input type="hidden" name="NjColSet" value="1" />
            <input type="hidden" name="NjBySig" value="<?php echo htmlspecialchars($njBy, ENT_QUOTES, 'UTF-8'); ?>" />
            <input type="hidden" name="NjOptSig" value="<?php echo htmlspecialchars(nj_read_option_signature(), ENT_QUOTES, 'UTF-8'); ?>" />
            <input type="hidden" name="NjKeepSig" value="<?php echo htmlspecialchars($njKeepSig, ENT_QUOTES, 'UTF-8'); ?>" />

            <div class="jq-filter-block">
                <strong>Junction percents</strong>
                <p class="text-muted" style="font-size:12px; margin:0 0 6px 0;">Check groups and columns, then click a junction. The summary table is the average for the checked groups. The table under a row is one group per line. <strong>-1</strong> means the sample count is under the minimum. <strong>0</strong> means the percent is actually 0. Allele frequency stays on the Mutations page.</p>
                <?php
                $njFilterMode = in_array($njBy, ['variant', 'primer', 'pair'], true) ? $njBy : 'pair';
                $njFilterTitle = [
                    'variant' => 'Filter Groups by Variant',
                    'primer' => 'Filter groups by Primer',
                    'pair' => 'Filter Groups by Variant-Primer Pairs',
                ][$njFilterMode];
                $njFilterNote = [
                    'variant' => 'Check rows, then Submit, to limit the group list below. -1 is under the minimum sample count. 0 is a real zero. Early is a 2020–2021 list sample whose variant is “.” or blank. A group with no list has none. A “.” sample off that list is ignored. long LTG is Omi- plus both BA.2–5 and XBB. LTG is Omi- plus BA.2–5 or XBB, but not both. Omi- is Alpha or Delta only. Omi-,BA.1 is that group with BA.1 as well. long Omi+ is BA.2–5 and XBB with no Alpha or Delta. Omi+ is BA.2–5 or XBB, but not both, with no Alpha or Delta. BA.1 does not make a group LTG or Omi+.',
                    'primer' => 'Check rows, then Submit, to limit the group list below. There is no Type column. Use this to find groups that use a primer, including Midnight, V5, and VarSkip. -1 is under the minimum sample count. 0 is a real zero.',
                    'pair' => 'Check rows, then Submit, to limit the group list below. There is no Type column and no Alpha column. Alpha is V3 only. Each column stays so you can find a group for that variant and primer, including V5, Midnight, and VarSkip. BA.2 and BA.5 are separate until Merge BA.2–5 is checked. Hide BA.1 removes those columns. Merge Omicron replaces BA.2, BA.5, and XBB with one Omicron block. The checklist below chooses columns for the junction table and the download. -1 is under the minimum sample count. 0 is a real zero.',
                ][$njFilterMode];
                $njFilterCols = $njFilterTable['columns'];
                ?>
                <div class="jq-chart-controls" style="margin-top:8px;">
                    <strong>Filter table</strong>
                    <label><input type="radio" name="NjBy" value="variant"<?php echo $njBy === 'variant' ? ' checked' : ''; ?> /> Variants</label>
                    <label><input type="radio" name="NjBy" value="primer"<?php echo $njBy === 'primer' ? ' checked' : ''; ?> /> Primers</label>
                    <label><input type="radio" name="NjBy" value="pair"<?php echo $njBy === 'pair' ? ' checked' : ''; ?> /> Variant–primer pairs</label>
                </div>
                <div class="panel panel-default" style="margin-top:12px;">
                    <div class="panel-heading jq-collapse-toggle">
                        <a class="jq-panel-heading-link" data-toggle="collapse" href="#jqFilterGroups"><?php echo htmlspecialchars($njFilterTitle, ENT_QUOTES, 'UTF-8'); ?> <span class="caret"></span></a>
                    </div>
                    <div id="jqFilterGroups" class="panel-collapse collapse<?php echo $didQuery ? ' in' : ''; ?>">
                        <div class="panel-body">
                            <p class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars($njFilterNote, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p style="margin:6px 0;"><a href="<?php echo htmlspecialchars(jq_csv_query('filter'), ENT_QUOTES, 'UTF-8'); ?>">Download this table (<?php echo htmlspecialchars(nj_read_filter_download_name($njFilterMode, $njOptions['min_samples']), ENT_QUOTES, 'UTF-8'); ?>)</a></p>
                            <div class="jq-table-wrap">
                                <table class="table table-bordered table-striped jq-table<?php echo $njFilterMode === 'pair' ? ' jq-pair' : ''; ?> sortable">
                                    <thead>
                                        <tr class="darkheader">
                                            <th<?php echo $njFilterMode === 'pair' ? ' rowspan="2"' : ''; ?>>Select</th>
                                            <th class="jq-group"<?php echo $njFilterMode === 'pair' ? ' rowspan="2"' : ''; ?>>Group</th>
                                            <?php if ($njFilterMode === 'variant') : ?>
                                                <th>Type</th>
                                                <th>Early</th>
                                            <?php endif; ?>
                                            <?php if ($njFilterMode === 'pair') : ?>
                                                <?php
                                                $pairSpans = [];
                                                foreach ($njFilterCols as $filterCol) {
                                                    $spanTop = $filterCol['top'];
                                                    if ($pairSpans && $pairSpans[count($pairSpans) - 1]['top'] === $spanTop) {
                                                        $pairSpans[count($pairSpans) - 1]['n']++;
                                                    } else {
                                                        $pairSpans[] = ['top' => $spanTop, 'n' => 1];
                                                    }
                                                }
                                                foreach ($pairSpans as $pairSpan) :
                                                    ?>
                                                    <th colspan="<?php echo (int) $pairSpan['n']; ?>"><?php echo htmlspecialchars($pairSpan['top'], ENT_QUOTES, 'UTF-8'); ?></th>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <?php foreach ($njFilterCols as $filterCol) : ?>
                                                    <?php $filterHead = $filterCol['top'] !== '' ? $filterCol['top'] . ' ' . $filterCol['sub'] : $filterCol['sub']; ?>
                                                    <th><?php echo htmlspecialchars($filterHead, ENT_QUOTES, 'UTF-8'); ?></th>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tr>
                                        <?php if ($njFilterMode === 'pair') : ?>
                                            <tr class="darkheader">
                                                <?php foreach ($njFilterCols as $filterCol) : ?>
                                                    <th><?php echo htmlspecialchars($filterCol['sub'], ENT_QUOTES, 'UTF-8'); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endif; ?>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($vcfGroups as $filterIndex => $filterGroup) : ?>
                                        <?php $filterLine = $njFilterTable['rows'][$filterIndex]; ?>
                                        <tr>
                                            <td><input type="checkbox" name="NjKeep[]" value="<?php echo htmlspecialchars($filterGroup['code'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($filterGroup['code'], $njKeep, true) ? ' checked' : ''; ?> /></td>
                                            <?php foreach ($filterLine as $filterCellIndex => $filterCell) : ?>
                                                <td<?php echo $filterCellIndex === 0 ? ' class="jq-group"' : ''; ?>><?php echo htmlspecialchars((string) $filterCell, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($njFilterMode !== 'variant') : ?>
                                <p class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars(nj_read_primer_caption(), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary jq-submit">Submit</button>
                <strong>Groups</strong>
                <label><input type="checkbox" class="nj-select-all" data-nj-target="NjGroup" /> Select all</label>
                <div class="nj-checks" id="NjGroup">
                    <?php foreach ($njVisibleGroups as $vcfGroupRow) : ?>
                        <label>
                            <input type="checkbox" name="NjGroup[]" value="<?php echo htmlspecialchars($vcfGroupRow['code'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($vcfGroupRow['code'], $njGroupCodes, true) ? ' checked' : ''; ?> />
                            <?php echo htmlspecialchars(vcf_snv_group_menu_label($vcfGroupRow), ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted" style="font-size:12px;">Checking rows in the filter table and submitting selects every group in this list. Select all checks or clears it. Submit applies a change here too.</p>
                <?php foreach ($njGroupRows as $njGroupRow) : ?>
                    <?php if (nj_read_many_project_file($njGroupRow['code']) !== null) : ?>
                        <p style="margin-top:6px;"><a href="VcfGroupProjects.php?group=<?php echo rawurlencode($njGroupRow['code']); ?>">View the project accessions in <?php echo htmlspecialchars($njGroupRow['label'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div style="margin-top:10px;">
                    <label for="MinReads">Min # reads</label>
                    <input id="MinReads" name="MinReads" type="number" class="form-control" min="0" step="1" value="<?php echo (int) nj_read_request_min_reads(); ?>" style="max-width:120px;" />
                </div>
                <div style="margin-top:10px;">
                    <label for="MinSamples">Min # samples</label>
                    <input id="MinSamples" name="MinSamples" type="number" class="form-control" min="0" step="1" value="<?php echo (int) $njOptions['min_samples']; ?>" style="max-width:120px;" />
                    <p class="text-muted" style="font-size:12px; margin:4px 0 0 0;">Default 30. A variant under this count is left out of merges and out of the group Type.</p>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="ShowBelow" value="0" />
                        <input id="ShowBelow" name="ShowBelow" type="checkbox" value="1"<?php echo !empty($njOptions['show_below']) ? ' checked' : ''; ?> />
                        Show counts below the minimum number of samples
                    </label>
                    <p class="text-muted" style="font-size:12px; margin:0;">Showing them does not change the group Type.</p>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="Ngene" value="0" />
                        <input id="Ngene" name="Ngene" type="checkbox" value="1"<?php echo nj_read_request_ngene() ? ' checked' : ''; ?> />
                        Filter for samples containing N gene sgRNA
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="Major" value="0" />
                        <input id="Major" name="Major" type="checkbox" value="1"<?php echo !empty($njOptions['major_only']) ? ' checked' : ''; ?> />
                        Show only major variants
                    </label>
                    <p class="text-muted" style="font-size:12px; margin:0;">Alpha, Delta, and Omicron. On by default. An extra mutation, such as B.1.1.7-like+E484K, stays off this list.</p>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="MergeDelta" value="0" />
                        <input id="MergeDelta" name="MergeDelta" type="checkbox" value="1"<?php echo !empty($njOptions['merge_delta']) ? ' checked' : ''; ?> />
                        Merge Delta
                    </label>
                    <p class="text-muted" style="font-size:12px; margin:0;">Delta subtypes become one Delta. Other variants stay on the list.</p>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="MergeOmicron" value="0" />
                        <input id="MergeOmicron" name="MergeOmicron" type="checkbox" value="1"<?php echo !empty($njOptions['merge_omicron']) ? ' checked' : ''; ?> />
                        Merge Omicron
                    </label>
                    <p class="text-muted" style="font-size:12px; margin:0;">All Omicrons except BA.1. This cannot be combined with the two merges below.</p>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="MergeBa" value="0" />
                        <input id="MergeBa" name="MergeBa" type="checkbox" value="1"<?php echo !empty($njOptions['merge_ba']) ? ' checked' : ''; ?> />
                        Merge BA.2–5
                    </label>
                    <label style="margin-left:12px;">
                        <input type="hidden" name="MergeXbb" value="0" />
                        <input id="MergeXbb" name="MergeXbb" type="checkbox" value="1"<?php echo !empty($njOptions['merge_xbb']) ? ' checked' : ''; ?> />
                        Merge XBB
                    </label>
                    <p class="text-muted" style="font-size:12px; margin:0;">BA.2–5 merges BA.2, BA.3, BA.4, and BA.5. BA.3 is rare. XBB merges XBB only. BA.1 stays separate because it is known to be contaminated with Delta.</p>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="HideBa1" value="0" />
                        <input id="HideBa1" name="HideBa1" type="checkbox" value="1"<?php echo !empty($njOptions['hide_ba1']) ? ' checked' : ''; ?> />
                        Hide BA.1
                    </label>
                </div>
                <strong><?php echo $njBy === 'primer' ? 'Primers' : ($njBy === 'variant' ? 'Variants' : 'Variant–primer pairs'); ?></strong>
                <label><input type="checkbox" class="nj-select-all" data-nj-target="NjCol" /> Select all</label>
                <div class="nj-checks" id="NjCol">
                    <?php foreach ($njChoices as $choice) : ?>
                        <?php $choiceHead = nj_read_column_heads($choice['key'], $njBy); ?>
                        <label>
                            <input type="checkbox" name="NjCol[]" value="<?php echo htmlspecialchars($choice['key'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($choice['key'], $njSelectedCols, true) ? ' checked' : ''; ?> />
                            <?php echo htmlspecialchars(trim($choiceHead[0] . ' ' . $choiceHead[1]), ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted" style="font-size:12px;">These choices set the junction table and the download. They do not remove columns from the filter table. No-variant-call, no-primer-call, Unassigned, and Probable are left out. <?php echo htmlspecialchars(nj_read_primer_caption(), ENT_QUOTES, 'UTF-8'); ?></p>
                <button type="submit" class="btn btn-primary jq-submit">Submit</button>
                <input type="hidden" name="NjPick" value="<?php echo $njPick === null ? '' : htmlspecialchars(nj_read_pick_value($njPick['nj_size'], $njPick['coord_tag']), ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <div class="jq-filter-block">
                <strong>Click a junction</strong>
                <p style="margin:6px 0;"><a href="<?php echo htmlspecialchars(jq_csv_query('summary'), ENT_QUOTES, 'UTF-8'); ?>">Download the summary table (<?php echo htmlspecialchars(nj_read_summary_download_name(jq_group_codes($njGroupRows)), ENT_QUOTES, 'UTF-8'); ?>)</a></p>
                <?php include __DIR__ . '/MutationsNj.php'; ?>
            </div>
            <div class="jq-filter-block" data-jq-all="primer">
                <strong>Primer</strong>
                <p class="text-muted" style="font-size:12px; margin:0 0 6px 0;">
                    Uncheck <strong>All</strong>, then choose schemes. This filters the percent table, the grouped-bar chart, and the Viridian count tables below. The stacked 8-group workbook has no primer split.
                </p>
                <label><input type="checkbox" class="jq-all" name="primer_all" value="1"<?php echo $primerAll ? ' checked' : ''; ?> /> All</label>
                <?php foreach ($options['primers'] as $c) : ?>
                    <label><input type="checkbox" name="primer[]" class="jq-one" value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"<?php echo jq_checked($selPrimers, $c); ?> /> <?php echo htmlspecialchars(jq_primer_display_label($c), ENT_QUOTES, 'UTF-8'); ?></label>
                <?php endforeach; ?>
            </div>
            <div class="jq-filter-block" data-jq-all="variant">
                <strong>Variant</strong>
                <label><input type="checkbox" class="jq-all" name="variant_all" value="1"<?php echo $variantAll ? ' checked' : ''; ?> /> All</label>
                <?php foreach ($options['variants'] as $v) : ?>
                    <label><input type="checkbox" name="variant[]" class="jq-one" value="<?php echo htmlspecialchars($v['code'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo jq_checked($selVariants, $v['code']); ?> /> <?php echo htmlspecialchars($v['label'], ENT_QUOTES, 'UTF-8'); ?></label>
                <?php endforeach; ?>
            </div>
            <p class="text-muted" style="font-size:12px;">The chart and the count tables use the groups checked at the top.</p>
            <div class="jq-filter-block">
                <label>
                    <input type="checkbox" name="require_samples" value="1"<?php echo $requireSamples ? ' checked' : ''; ?> />
                    Keep groups that have samples for <em>every</em> selected variant (<code>% variant &gt; -1</code>)
                </label>
            </div>

            <div class="jq-filter-block jq-chart-controls">
                <strong>Chart type</strong>
                <label><input type="radio" name="chart_type" value="clustered"<?php echo $chartType === 'clustered' ? ' checked' : ''; ?> /> Grouped bar</label>
                <label><input type="radio" name="chart_type" value="stacked"<?php echo $chartType === 'stacked' ? ' checked' : ''; ?> /> Stacked bar</label>
                <label><input type="radio" name="chart_type" value="primer"<?php echo $chartType === 'primer' ? ' checked' : ''; ?> /> Variant–primer bars</label>
            </div>
            <div class="jq-filter-block jq-chart-controls">
                <strong>Color key (legend)</strong>
                <label><input type="radio" name="legend_key" value="variant"<?php echo $legendKey === 'variant' ? ' checked' : ''; ?> /> Variants</label>
                <label><input type="radio" name="legend_key" value="group"<?php echo $legendKey === 'group' ? ' checked' : ''; ?> /> Groups</label>
                <span class="text-muted" style="font-size:12px;">The selected key is color-coded under the graph.</span>
            </div>
            <p>
                <button type="submit" class="btn btn-primary">Query</button>
                <a class="btn btn-default" href="JunctionGroupQuery.php">Reset</a>
            </p>
        </form>

        <h4 class="search-header">Chart</h4>
        <div id="jqChartMain" class="jq-chart"></div>
        <div id="jqColorKey"></div>

        <?php
        $pivot = $tablePack['pivot'];
        $tableTitle = $primerActive ? 'Groups × variant–primer (%)' : 'Groups × variants (%)';
        $pctOpen = $primerActive ? ' in' : '';
        ?>
        <div class="panel panel-default">
            <div class="panel-heading jq-collapse-toggle">
                <a class="jq-panel-heading-link" data-toggle="collapse" href="#jqPctTable"><?php echo htmlspecialchars($tableTitle, ENT_QUOTES, 'UTF-8'); ?> <span class="caret"></span></a>
            </div>
            <div id="jqPctTable" class="panel-collapse collapse<?php echo $pctOpen; ?>">
                <div class="panel-body">
                    <?php if ($didQuery) : ?>
                        <p class="text-muted" style="font-size:12px;"><?php echo $primerActive
                            ? 'Primer filter is on: percent cells come from the variant–primer workbook for the selected scheme(s).'
                            : 'From the Excel percent tables. Primer selection (when not All) switches to the variant–primer workbook.'; ?></p>
                    <?php endif; ?>
                    <div class="jq-table-wrap">
                        <table class="table table-bordered table-striped jq-table">
                            <thead>
                                <tr class="darkheader">
                                    <th class="jq-group">Group</th>
                                    <th>Continent</th>
                                    <th>Instrument</th>
                                    <?php foreach ($pivot['columns'] as $col) : ?>
                                        <th><?php echo htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$pivot['groups']) : ?>
                                <tr><td colspan="<?php echo 3 + count($pivot['columns']); ?>" class="text-muted">No groups match these filters.</td></tr>
                            <?php else : ?>
                                <?php foreach ($pivot['groups'] as $gName) : ?>
                                    <?php $meta = isset($pivot['meta'][$gName]) ? $pivot['meta'][$gName] : ['continent' => '', 'instrument' => '']; ?>
                                    <tr>
                                        <td class="jq-group"><?php echo htmlspecialchars($gName, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $meta['continent'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $meta['instrument'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php foreach ($pivot['columns'] as $col) : ?>
                                            <?php
                                            $has = isset($pivot['cells'][$gName][$col['key']]);
                                            $val = $has ? $pivot['cells'][$gName][$col['key']] : null;
                                            $missing = !$has || $val <= -1;
                                            ?>
                                            <td<?php echo $missing ? ' class="jq-missing"' : ''; ?>><?php echo htmlspecialchars(jq_format_pct($val), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($viridianOk && $viridianVariantPivot) : ?>
        <?php
        $njCountVariant = nj_read_transposed_counts($viridianVariantPivot, $njChartNames, $njOptions, false);
        $njCountPair = nj_read_transposed_counts($viridianPairPivot, $njChartNames, $njOptions, true);
        ?>
        <div class="panel panel-default">
            <div class="panel-heading jq-collapse-toggle">
                <a class="jq-panel-heading-link" data-toggle="collapse" href="#jqViridianVar">Group information — variant counts <span class="caret"></span></a>
            </div>
            <div id="jqViridianVar" class="panel-collapse collapse in">
                <div class="panel-body">
                    <p class="text-muted" style="font-size:12px;">Sample counts for the groups checked above. Groups are rows and variants are columns. The same merge rules apply. <strong>-1</strong> is under the minimum number of samples. <strong>0</strong> is a real zero.<?php echo $primerActive ? ' Restricted to the selected primer scheme(s).' : ''; ?></p>
                    <div class="jq-table-wrap">
                        <table class="table table-bordered table-striped jq-table sortable">
                            <thead>
                                <tr class="darkheader">
                                    <th class="jq-group">Group</th>
                                    <?php foreach ($njCountVariant['columns'] as $colLabel) : ?>
                                        <th><?php echo htmlspecialchars($colLabel, ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$njCountVariant['rows']) : ?>
                                <tr><td class="text-muted" colspan="<?php echo 1 + count($njCountVariant['columns']); ?>">None of the checked groups are in the Viridian count workbook.</td></tr>
                            <?php else : ?>
                                <?php foreach ($njCountVariant['rows'] as $countRow) : ?>
                                    <tr>
                                        <td class="jq-group"><?php echo htmlspecialchars($countRow['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php foreach ($njCountVariant['columns'] as $colKey => $colLabel) : ?>
                                            <td><?php echo htmlspecialchars($countRow['cells'][$colKey], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading jq-collapse-toggle">
                <a class="jq-panel-heading-link" data-toggle="collapse" href="#jqViridianPair">Group information — variant–primer counts <span class="caret"></span></a>
            </div>
            <div id="jqViridianPair" class="panel-collapse collapse<?php echo $primerActive ? ' in' : ''; ?>">
                <div class="panel-body">
                    <p class="text-muted" style="font-size:12px;">Same groups, with primer abbreviations. <?php echo htmlspecialchars(nj_read_primer_caption(), ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="jq-table-wrap">
                        <table class="table table-bordered table-striped jq-table sortable">
                            <thead>
                                <tr class="darkheader">
                                    <th class="jq-group">Group</th>
                                    <?php foreach ($njCountPair['columns'] as $colLabel) : ?>
                                        <th><?php echo htmlspecialchars($colLabel, ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$njCountPair['rows']) : ?>
                                <tr><td class="text-muted" colspan="<?php echo 1 + count($njCountPair['columns']); ?>">None of the checked groups are in the Viridian count workbook.</td></tr>
                            <?php else : ?>
                                <?php foreach ($njCountPair['rows'] as $countRow) : ?>
                                    <tr>
                                        <td class="jq-group"><?php echo htmlspecialchars($countRow['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php foreach ($njCountPair['columns'] as $colKey => $colLabel) : ?>
                                            <td><?php echo htmlspecialchars($countRow['cells'][$colKey], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($tablesOk) : ?>
            <div class="alert alert-info" style="max-width:900px;">Viridian count tables are not installed. Import <code>sql/junction_viridian_counts.sql</code> for NJ/NM sample counts.</div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<script>
window.JQ_CHARTS = <?php
$jqChartJson = json_encode(
    $chartPayload,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)
);
echo ($jqChartJson === false || $jqChartJson === '') ? '{}' : $jqChartJson;
?>;
</script>
<script src="JS/junctionQueryCharts.js"></script>
<script>
if (window.JunctionQueryCharts) {
    JunctionQueryCharts.render(window.JQ_CHARTS);
}
(function ($) {
    function bindAll($block) {
        var $all = $block.find('> label .jq-all, input.jq-all').first();
        if (!$all.length) {
            $all = $block.find('input.jq-all').first();
        }
        var $ones = $block.find('input.jq-one');
        $all.on('change', function () {
            if (this.checked) {
                $ones.prop('checked', false);
            }
        });
        $ones.on('change', function () {
            if (this.checked) {
                $all.prop('checked', false);
            } else if ($ones.filter(':checked').length === 0) {
                $all.prop('checked', true);
            }
        });
        $block.closest('form').on('submit', function () {
            if ($all.prop('checked')) {
                $ones.prop('checked', false);
            }
        });
    }
    $('.jq-filter-block[data-jq-all]').each(function () {
        bindAll($(this));
    });
    $('button.nj-show').on('click', function () {
        $(this).closest('tr').find('input[name="NjJunction[]"]').prop('checked', true);
    });
    $('.nj-pick-row').on('click', function (ev) {
        if ($(ev.target).closest('button, input').length) {
            return;
        }
        $(this).find('input[name="NjJunction[]"]').prop('checked', true);
        var btn = $(this).find('button.nj-show').get(0);
        if (btn) {
            btn.click();
        }
    });
    $('#NjJunction').on('change', 'input[name="NjJunction[]"]', function () {
        submitFilterForm();
    });
    $('.nj-select-all[data-nj-target="NjJunction"]').on('change', function () {
        var breakdown = document.getElementById('njBreakdown');
        var checked = $('#NjJunction input[name="NjJunction[]"]:checked').length;
        if (breakdown && checked !== 1) {
            breakdown.style.display = 'none';
        }
    });
    var form = document.querySelector('form.jq-form');
    if (form) {
        form.addEventListener('submit', function (ev) {
            var jump = ev.submitter && ev.submitter.classList && ev.submitter.classList.contains('nj-show');
            try {
                sessionStorage.setItem('jqScrollY', jump ? 'breakdown' : String(window.scrollY || window.pageYOffset || 0));
            } catch (err) {}
        });
    }
    var breakdown = document.getElementById('njBreakdown');
    var savedScroll = null;
    try {
        savedScroll = sessionStorage.getItem('jqScrollY');
        sessionStorage.removeItem('jqScrollY');
    } catch (err) {}
    if (savedScroll === 'breakdown' && breakdown) {
        breakdown.scrollIntoView({block: 'start'});
    } else if (savedScroll !== null && savedScroll !== '') {
        var scrollY = parseInt(savedScroll, 10);
        if (!isNaN(scrollY)) {
            window.scrollTo(0, scrollY);
        }
    }
    function syncOmicronMerges() {
        var broad = $('#MergeOmicron').prop('checked');
        $('#MergeBa, #MergeXbb').prop('disabled', broad);
        if (broad) {
            $('#MergeBa, #MergeXbb').prop('checked', false);
        }
    }
    syncOmicronMerges();
    function submitFilterForm() {
        if (!form) {
            return;
        }
        if (form.requestSubmit) {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }
    $('input[name="NjBy"], #Major, #MergeDelta, #MergeBa, #MergeXbb, #HideBa1').on('change', submitFilterForm);
    $('#MergeOmicron').on('change', function () {
        syncOmicronMerges();
        submitFilterForm();
    });
    $('.nj-select-all').each(function () {
        var $all = $(this);
        var $ones = $('#' + $all.attr('data-nj-target')).find('input[type="checkbox"]');
        function syncAll() {
            $all.prop('checked', $ones.length > 0 && $ones.filter(':checked').length === $ones.length);
        }
        $all.on('change', function () {
            $ones.prop('checked', this.checked);
        });
        $ones.on('change', syncAll);
        syncAll();
    });
})(jQuery);
</script>
</body>
</html>
