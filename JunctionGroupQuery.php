<?php
/**
 * Query groups / variants / primers for a junction (Jim Kelley prototype).
 * Data: sql/junction_query.sql + sql/junction_viridian_counts.sql
 * Reverse: sql/junction_query_drop.sql
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/junction_query_helpers.php';

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
list($selContinents, $continentAll) = jq_parse_all_list('continent', 'continent_all');
list($selInstruments, $instrumentAll) = jq_parse_all_list('instrument', 'instrument_all');
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
        $viridianVariantPivot = jq_pivot_viridian(jq_fetch_viridian_variants($con), $viridianGroups, false);
        $viridianPairPivot = jq_pivot_viridian(jq_fetch_viridian_pairs($con), $viridianGroups, true);
    }
} else {
    $dbError = 'Database connection not available.';
}

$baseFilters = [
    'left' => $left,
    'right' => $right,
    'continents' => $selContinents,
    'instruments' => $selInstruments,
    'groups' => $selGroups,
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

$stackedSeries = jq_series_for_pivot($packStacked['pivot'], $legendKey, true);
$stackedHas = false;
foreach ($stackedSeries as $s) {
    if (!empty($s['dataPoints'])) {
        $stackedHas = true;
        break;
    }
}
$stackedTitle = 'Stacked 8-group workbook (percent ÷ number of groups)';
$stackedPivotForChart = $packStacked['pivot'];
if (!$stackedHas) {
    $stackedPivotForChart = $packClustered['pivot'];
    $stackedTitle = 'Stacked (percent ÷ number of groups in this query)';
}

$size = $right - $left + 1;
$legendNote = $legendKey === 'group' ? 'Color key = groups' : 'Color key = variants';
$chartPayload = [
    'chartType' => $chartType,
    'legendKey' => $legendKey,
    'charts' => [
        'clustered' => [
            'title' => '% of samples (' . $legendNote . ')',
            'yTitle' => '% of samples',
            'series' => jq_series_for_pivot($packClustered['pivot'], $legendKey, false),
            'empty' => 'No group×variant rows for these filters.',
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
    <?php include __DIR__ . '/Navigation.php'; ?>
    <style>
        .jq-page .search-header { padding-left: 10px; }
        .jq-filters label { font-weight: normal; margin-right: 10px; }
        .jq-filter-block { margin-bottom: 10px; }
        .jq-filter-block strong { display: block; margin-bottom: 4px; }
        .jq-table-wrap { max-height: 420px; overflow: auto; margin-top: 8px; }
        .jq-table { font-size: 12px; }
        .jq-table th, .jq-table td { text-align: center; vertical-align: middle !important; white-space: nowrap; }
        .jq-table th.jq-group, .jq-table td.jq-group { text-align: left; }
        .jq-missing { color: #999; }
        .jq-chart { height: 420px; width: 100%; margin: 12px 0 8px 0; }
        .jq-note { font-size: 13px; max-width: 980px; }
        tr.darkheader th { background: #333; color: #fff; position: sticky; top: 0; }
        .jq-rollup { font-weight: bold; background: #f7f7f7; }
        .jq-chart-controls label { font-weight: normal; margin-right: 16px; }
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

            <div class="jq-filter-block" data-jq-all="continent">
                <strong>Continent</strong>
                <label><input type="checkbox" class="jq-all" name="continent_all" value="1"<?php echo $continentAll ? ' checked' : ''; ?> /> All</label>
                <?php foreach ($options['continents'] as $c) : ?>
                    <label><input type="checkbox" name="continent[]" class="jq-one" value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"<?php echo jq_checked($selContinents, $c); ?> /> <?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></label>
                <?php endforeach; ?>
            </div>
            <div class="jq-filter-block" data-jq-all="instrument">
                <strong>Instrument</strong>
                <label><input type="checkbox" class="jq-all" name="instrument_all" value="1"<?php echo $instrumentAll ? ' checked' : ''; ?> /> All</label>
                <?php foreach ($options['instruments'] as $c) : ?>
                    <label><input type="checkbox" name="instrument[]" class="jq-one" value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"<?php echo jq_checked($selInstruments, $c); ?> /> <?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></label>
                <?php endforeach; ?>
            </div>
            <div class="jq-filter-block" data-jq-all="primer">
                <strong>Primer</strong>
                <label><input type="checkbox" class="jq-all" name="primer_all" value="1"<?php echo $primerAll ? ' checked' : ''; ?> /> All</label>
                <?php foreach ($options['primers'] as $c) : ?>
                    <label><input type="checkbox" name="primer[]" class="jq-one" value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"<?php echo jq_checked($selPrimers, $c); ?> /> ARTIC <?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></label>
                <?php endforeach; ?>
            </div>
            <div class="jq-filter-block" data-jq-all="variant">
                <strong>Variant</strong>
                <label><input type="checkbox" class="jq-all" name="variant_all" value="1"<?php echo $variantAll ? ' checked' : ''; ?> /> All</label>
                <?php foreach ($options['variants'] as $v) : ?>
                    <label><input type="checkbox" name="variant[]" class="jq-one" value="<?php echo htmlspecialchars($v['code'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo jq_checked($selVariants, $v['code']); ?> /> <?php echo htmlspecialchars($v['label'], ENT_QUOTES, 'UTF-8'); ?></label>
                <?php endforeach; ?>
            </div>
            <div class="jq-filter-block" data-jq-all="group">
                <strong>Groups</strong>
                <p class="text-muted" style="font-size:12px; margin:0 0 6px 0;">Select groups in this table (or leave <strong>All</strong> checked). Names can differ between workbooks; that is why NJ appears more than once.</p>
                <div class="jq-table-wrap" style="max-height:260px;">
                    <table class="table table-bordered table-striped jq-table">
                        <thead>
                            <tr class="darkheader">
                                <th style="width:70px;">
                                    <label style="color:#fff; margin:0; font-weight:normal;">
                                        <input type="checkbox" class="jq-all" name="group_all" value="1"<?php echo $groupAll ? ' checked' : ''; ?> /> All
                                    </label>
                                </th>
                                <th class="jq-group">Group</th>
                                <th>Location</th>
                                <th>Continent</th>
                                <th>Instrument</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$groupDirectory) : ?>
                            <tr><td colspan="5" class="text-muted">No groups in the imported tables.</td></tr>
                        <?php else : ?>
                            <?php foreach ($groupDirectory as $gRow) : ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="group[]" class="jq-one" value="<?php echo htmlspecialchars($gRow['name'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo jq_checked($selGroups, $gRow['name']); ?> />
                                    </td>
                                    <td class="jq-group"><?php echo htmlspecialchars($gRow['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) $gRow['location_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) $gRow['continent'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) $gRow['instrument'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
        $tableTitle = (!$primerAll && $selPrimers) ? 'Groups × variant–primer (%)' : 'Groups × variants (%)';
        ?>
        <div class="panel panel-default">
            <div class="panel-heading jq-collapse-toggle">
                <a class="jq-panel-heading-link" data-toggle="collapse" href="#jqPctTable"><?php echo htmlspecialchars($tableTitle, ENT_QUOTES, 'UTF-8'); ?> <span class="caret"></span></a>
            </div>
            <div id="jqPctTable" class="panel-collapse collapse">
                <div class="panel-body">
                    <?php if ($didQuery) : ?>
                        <p class="text-muted" style="font-size:12px;">From the Excel percent tables. Primer selection (when not All) switches to the variant–primer workbook.</p>
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
        <div class="panel panel-default">
            <div class="panel-heading jq-collapse-toggle">
                <a class="jq-panel-heading-link" data-toggle="collapse" href="#jqViridianVar">Group information — Viridian variant call counts <span class="caret"></span></a>
            </div>
            <div id="jqViridianVar" class="panel-collapse collapse in">
                <div class="panel-body">
                    <p class="text-muted" style="font-size:12px;">Sample counts from Viridian (NJ and NM examples). Long names as in the files. <strong>Delta (all)</strong> is the total of every Delta type. <code>.</code> is no variant call. No percentages.</p>
                    <div class="jq-table-wrap">
                        <table class="table table-bordered table-striped jq-table">
                            <thead>
                                <tr class="darkheader">
                                    <th class="jq-group">Viridian variant call</th>
                                    <?php foreach ($viridianVariantPivot['groups'] as $g) : ?>
                                        <th><?php echo htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($viridianVariantPivot['row_keys'] as $rk) : ?>
                                <?php $isRoll = !empty($viridianVariantPivot['rollups'][$rk]); ?>
                                <tr<?php echo $isRoll ? ' class="jq-rollup"' : ''; ?>>
                                    <td class="jq-group"><?php echo htmlspecialchars(jq_viridian_label($viridianVariantPivot['row_labels'][$rk]), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php foreach ($viridianVariantPivot['groups'] as $g) : ?>
                                        <?php
                                        $code = $g['code'];
                                        $cnt = isset($viridianVariantPivot['cells'][$rk][$code]) ? $viridianVariantPivot['cells'][$rk][$code] : 0;
                                        ?>
                                        <td><?php echo $cnt ? (int) $cnt : '—'; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading jq-collapse-toggle">
                <a class="jq-panel-heading-link" data-toggle="collapse" href="#jqViridianPair">Group information — Viridian variant–primer pair counts <span class="caret"></span></a>
            </div>
            <div id="jqViridianPair" class="panel-collapse collapse">
                <div class="panel-body">
                    <p class="text-muted" style="font-size:12px;">Sample counts for each Viridian variant call + Viridian primer call (full names from the file, including V5 and Midnight).</p>
                    <div class="jq-table-wrap">
                        <table class="table table-bordered table-striped jq-table">
                            <thead>
                                <tr class="darkheader">
                                    <th class="jq-group">Viridian variant call</th>
                                    <th class="jq-group">Viridian primer call</th>
                                    <?php foreach ($viridianPairPivot['groups'] as $g) : ?>
                                        <th><?php echo htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($viridianPairPivot['row_keys'] as $rk) : ?>
                                <?php
                                $parts = explode("\t", $rk, 2);
                                $vname = isset($parts[0]) ? $parts[0] : '';
                                $pname = isset($parts[1]) ? $parts[1] : '';
                                ?>
                                <tr>
                                    <td class="jq-group"><?php echo htmlspecialchars(jq_viridian_label($vname), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="jq-group"><?php echo htmlspecialchars($pname, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php foreach ($viridianPairPivot['groups'] as $g) : ?>
                                        <?php
                                        $code = $g['code'];
                                        $cnt = isset($viridianPairPivot['cells'][$rk][$code]) ? $viridianPairPivot['cells'][$rk][$code] : 0;
                                        ?>
                                        <td><?php echo $cnt ? (int) $cnt : '—'; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
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
window.JQ_CHARTS = <?php echo json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
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
})(jQuery);
</script>
</body>
</html>
