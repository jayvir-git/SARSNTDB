<?php
/**
 * HTML fragment: clickable NJ list, then a group × column percent table.
 * Set $njTableKind = 'cj' (Click a Canonical Junction, D-80) or 'tl' (Click a TRS-L Dependent Junction, D-81) before including it.
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/nj_read_helpers.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($njVisibleGroups) || !is_array($njVisibleGroups)) {
    $njVisibleGroups = vcf_snv_fetch_groups($con);
}
if (!isset($njGroupRows) || !is_array($njGroupRows)) {
    $njGroupCodes = nj_read_request_group_codes($njVisibleGroups);
    $njGroupRows = [];
    foreach ($njVisibleGroups as $group) {
        if (in_array($group['code'], $njGroupCodes, true)) {
            $njGroupRows[] = $group;
        }
    }
}
if (!isset($njBy)) {
    $njBy = nj_read_request_breakdown_by();
}
if (!isset($njMergeDelta)) {
    $njMergeDelta = nj_read_request_merge_delta();
}
if (!isset($njOptions) || !is_array($njOptions)) {
    $njOptions = nj_read_request_merge_options();
}
$njMinSamples = (int) $njOptions['min_samples'];
$njShowBelow = !empty($njOptions['show_below']);
if (!isset($njSelectedCols) || !is_array($njSelectedCols)) {
    $njSelectedCols = [];
}
if (!isset($njChoices) || !is_array($njChoices)) {
    $njChoices = [];
}
$njKind = nj_read_table_kind(isset($njTableKind) ? $njTableKind : 'nj');
$njCanonical = $njKind['kind'] === 'cj';
$njPickName = $njKind['pick'];
$njCheckName = $njKind['check'];
$njBreakdownId = $njKind['breakdown'];
if ($njKind['kind'] !== 'nj') {
    $njPick = nj_read_request_pick($njPickName);
} elseif (!array_key_exists('njPick', get_defined_vars())) {
    $njPick = nj_read_request_pick();
}

if (!nj_read_tables_exist($con)) {
    echo '<p class="text-muted">NJ read tables are not loaded yet.</p>';
    return;
}

$catalog = nj_read_catalog_rows($con, $njKind['kind']);
if (!$catalog) {
    echo '<p class="text-muted">' . htmlspecialchars($njKind['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
    return;
}

$pickRow = null;
if ($njPick !== null) {
    foreach ($catalog as $row) {
        if ((int) $row['nj_size'] === $njPick['nj_size'] && (string) $row['coord_tag'] === $njPick['coord_tag']) {
            $pickRow = $row;
            break;
        }
    }
}

$minReads = nj_read_request_min_reads();
$wantNgene = nj_read_request_ngene();
$choiceLabels = [];
foreach ($njChoices as $choice) {
    $choiceLabels[$choice['key']] = $choice['label'];
}

$groupIds = [];
foreach ($njGroupRows as $group) {
    $groupIds[] = (int) $group['id'];
}
$ngeneIds = $wantNgene ? nj_read_ngene_group_ids($con, $groupIds) : [];
// Denominators and hits come from JunctionGroupQuery when it includes this file; both tables share them.
if (!isset($njDenoms) || !is_array($njDenoms) || !isset($njHits) || !is_array($njHits)) {
    $njDenoms = nj_read_pair_denoms($con, $groupIds, $minReads, $ngeneIds);
    $njHits = nj_read_junction_hits($con, $groupIds, $minReads, $ngeneIds);
}
// Summary columns are the pairs (or variants, or primers) checked in the box, then Overall (Jim, Zoom 2026-10-21, D-81).
$njDataGroups = $njKind['kind'] === 'tl' ? nj_read_tag_data_groups($con, 'L') : null;
$tableSummary = nj_read_summary_column_rows($catalog, $groupIds, $njDenoms, $njHits, $njSelectedCols, $njBy, $njOptions, $njDataGroups);
$summaryByPick = [];
foreach ($tableSummary as $summaryRow) {
    $summaryByPick[nj_read_pick_value($summaryRow['nj_size'], $summaryRow['coord_tag'])] = $summaryRow;
}
$njJunctionOn = [];
$checkedJunctions = nj_read_request_junction_picks($njCheckName);
foreach ($checkedJunctions as $junctionPick) {
    $njJunctionOn[nj_read_pick_value($junctionPick['nj_size'], $junctionPick['coord_tag'])] = true;
}
// One group table per checked junction, in list order, up to this many (Jim, Zoom 2026-10-21, D-81).
$njBreakdownMax = 25;
$njFileCodes = [];
foreach ($njGroupRows as $njFileGroup) {
    $njFileCodes[] = (string) $njFileGroup['code'];
}
$summaryHeads = [];
foreach ($njSelectedCols as $key) {
    $head = nj_read_column_heads($key, $njBy);
    $summaryHeads[$key] = trim($head[0] . ' ' . $head[1]);
}
echo '<p class="text-muted" style="font-size:12px; margin:8px 0 0 0;">Averages for the selected groups. The columns are the ones checked in the ' . ($njBy === 'primer' ? 'Primers' : ($njBy === 'variant' ? 'Variants' : 'Variant–primer pairs')) . ' box above, then Overall (every analyzed sample). ' . htmlspecialchars(nj_read_primer_caption(), ENT_QUOTES, 'UTF-8') . ' <strong>-1</strong> means the count is under the minimum number of samples. <strong>0</strong> means the percent is actually 0. Click a column heading to sort.</p>';
if ($njCanonical) {
    echo '<p class="text-muted" style="font-size:12px; margin:4px 0 0 0;">28190 is the N sgRNA junction. With <strong>Filter by N gene sgRNA</strong> on, every analyzed sample counted has 28190 reads, so 28190 is 100% wherever a group has enough samples.</p>';
}
echo '<p style="margin:6px 0;"><label><input type="checkbox" class="nj-select-all" data-nj-target="' . $njCheckName . '" /> Select all</label> ';
echo '<button type="submit" class="btn btn-default btn-sm" name="csv" value="' . $njKind['csv'] . 'junctions">Download the checked junctions</button>';
if ($njCanonical) {
    echo ' <span class="text-muted" style="font-size:12px;">One CSV per checked junction, named with the start and short group names, as in <code>21486_Arg_Port550_NJ.csv</code>. Each checked junction gets its group table below (up to ' . $njBreakdownMax . ').</span></p>';
} else {
    echo ' <span class="text-muted" style="font-size:12px;">One CSV per checked junction, named with the size and short group names. Three groups at size 894 download as <code>894_Arg_Port550_NJ.csv</code>. When another junction has the same size, its start is added, as in <code>2766_1883_NJ.csv</code>. Sizes under 100 are the small deletions. Each checked junction gets its group table below (up to ' . $njBreakdownMax . ').</span></p>';
}
echo '<div class="nj-datagrid" id="' . $njCheckName . '"><table class="sortable"><thead><tr class="dark">';
if ($njKind['kind'] === 'tl') {
    // Jim's TRS-L files are named by this number; start and end come when he sends the junction list.
    echo '<th></th><th>Junction</th><th>Start</th><th>End</th>';
} else {
    echo $njCanonical ? '<th></th><th>Start</th><th>Size</th><th>End</th>' : '<th></th><th>NJ size</th><th>Start</th><th>End</th>';
}
foreach ($summaryHeads as $headText) {
    echo '<th>' . htmlspecialchars($headText, ENT_QUOTES, 'UTF-8') . '</th>';
}
echo '<th>Overall</th>';
echo '</tr></thead><tbody>';
$color1 = 'background-color:White';
$color2 = 'background-color:LightGray';
$prevColor = $color1;
$breakdownRows = [];
foreach ($catalog as $row) {
    $start = $row['nj_start'] === null ? '' : (string) $row['nj_start'];
    $end = $row['nj_end'] === null ? '' : (string) $row['nj_end'];
    $pickValue = nj_read_pick_value($row['nj_size'], $row['coord_tag']);
    $summaryRow = isset($summaryByPick[$pickValue]) ? $summaryByPick[$pickValue] : null;
    $current = isset($njJunctionOn[$pickValue]);
    if ($current) {
        $breakdownRows[] = $row;
    }
    $rowStyle = ($prevColor === $color1) ? $color2 : $color1;
    $prevColor = $rowStyle;
    $junctionChecked = $current ? ' checked' : '';
    echo '<tr class="nj-pick-row' . ($current ? ' nj-pick-current' : '') . '" style="' . $rowStyle . '">';
    echo '<td><input type="checkbox" name="' . $njCheckName . '[]" value="' . htmlspecialchars($pickValue, ENT_QUOTES, 'UTF-8') . '"' . $junctionChecked . ' /></td>';
    echo '<td><button type="submit" class="nj-show" name="' . $njPickName . '" value="' . htmlspecialchars($pickValue, ENT_QUOTES, 'UTF-8') . '" data-nj-breakdown="' . $njBreakdownId . '">';
    echo (int) $row['nj_size'] . ($njKind['kind'] === 'tl' && strlen((string) $row['coord_tag']) > 1 ? '_' . htmlspecialchars(substr((string) $row['coord_tag'], 1), ENT_QUOTES, 'UTF-8') : '');
    echo '</button></td>';
    if ($njCanonical) {
        // Canonical rows are keyed by start (nj_size holds it); the size is end - start + 1, as in Jim's del_coord_cjs.csv.
        $size = ($row['nj_start'] !== null && $row['nj_end'] !== null) ? (string) ($row['nj_end'] - $row['nj_start'] + 1) : '';
        echo '<td>' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . '</td>';
    } else {
        echo '<td>' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo '<td>' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . '</td>';
    foreach ($njSelectedCols as $key) {
        $text = ($summaryRow === null || !isset($summaryRow['cols'][$key])) ? '-1' : (string) $summaryRow['cols'][$key];
        echo '<td>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo '<td>' . htmlspecialchars($summaryRow === null ? '-1' : (string) $summaryRow['overall'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';

if (!$breakdownRows) {
    return;
}

echo '<div id="' . $njBreakdownId . '">';
if (!$njSelectedCols) {
    echo '<p class="text-muted">Select one or more variants, primers, or variant–primer pairs, then click the junction again.</p>';
    echo '</div>';
    return;
}
if (!$njGroupRows) {
    echo '<p class="text-muted">Select at least one group.</p>';
    echo '</div>';
    return;
}

echo '<p style="font-size:12px; margin-top:12px;">Min reads ' . (int) $minReads . '. ';
if ($wantNgene) {
    echo 'N-gene filter is on for groups that have size 28190';
    if (count($ngeneIds) < count($groupIds)) {
        echo ' (' . count($ngeneIds) . ' of ' . count($groupIds) . ' selected groups)';
    }
    echo '. ';
} else {
    echo 'N-gene filter is off. ';
}
echo 'Each cell is the percent of analyzed samples in that column. <strong>-1</strong> means the sample count is under ' . (int) $njMinSamples . '. <strong>0</strong> means the percent is actually 0. ' . htmlspecialchars(nj_read_primer_caption(), ENT_QUOTES, 'UTF-8') . '</p>';
if (count($breakdownRows) > $njBreakdownMax) {
    echo '<p class="text-muted" style="font-size:12px;">' . count($breakdownRows) . ' junctions are checked; the first ' . $njBreakdownMax . ' are shown. Use <strong>Download the checked junctions</strong> for all of them.</p>';
}

foreach (array_slice($breakdownRows, 0, $njBreakdownMax) as $pickRow) {
    $pickKey = (int) $pickRow['nj_size'] . '|' . (string) $pickRow['coord_tag'];
    $njBreakdownName = nj_read_junction_download_name($pickRow['nj_size'], $njFileCodes, false, $pickRow['coord_tag']);
    $njBreakdownQuery = $_GET;
    $njBreakdownQuery['csv'] = $njKind['csv'] . 'breakdown';
    $njBreakdownQuery['query'] = '1';
    $njBreakdownQuery[$njPickName] = nj_read_pick_value($pickRow['nj_size'], $pickRow['coord_tag']);
    unset($njBreakdownQuery[$njCheckName]);
    $start = $pickRow['nj_start'] === null ? '' : (string) $pickRow['nj_start'];
    $end = $pickRow['nj_end'] === null ? '' : (string) $pickRow['nj_end'];
    echo '<h4 class="search-header" style="margin-top:14px;">' . htmlspecialchars($njKind['heading'], ENT_QUOTES, 'UTF-8') . (int) $pickRow['nj_size'];
    if ($njKind['kind'] === 'tl' && strlen((string) $pickRow['coord_tag']) > 1) {
        echo '_' . htmlspecialchars(substr((string) $pickRow['coord_tag'], 1), ENT_QUOTES, 'UTF-8');
    }
    if ($start !== '' || $end !== '') {
        echo ' (' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '–' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . ')';
    }
    echo ' <small><a href="' . htmlspecialchars('JunctionGroupQuery.php?' . http_build_query($njBreakdownQuery), ENT_QUOTES, 'UTF-8') . '">Download this junction (' . htmlspecialchars($njBreakdownName, ENT_QUOTES, 'UTF-8') . ')</a></small></h4>';

    echo '<div class="nj-breakgrid"><table><thead>';
    echo '<tr class="dark"><th class="nj-group">Group</th>';
    foreach ($njSelectedCols as $key) {
        $head = nj_read_column_heads($key, $njBy);
        echo '<th>' . htmlspecialchars($head[0], ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr>';
    if ($njBy === 'pair') {
        echo '<tr class="dark nj-head-2"><th class="nj-group"></th>';
        foreach ($njSelectedCols as $key) {
            $head = nj_read_column_heads($key, $njBy);
            echo '<th>' . htmlspecialchars($head[1], ENT_QUOTES, 'UTF-8') . '</th>';
        }
        echo '</tr>';
    }
    echo '</thead><tbody>';
    $prevColor = $color1;
    foreach ($njGroupRows as $group) {
        $gid = (int) $group['id'];
        $hasData = !is_array($njDataGroups) || isset($njDataGroups[$gid]);
        $cells = !$hasData ? [] : nj_read_group_column_cells(
            isset($njDenoms[$gid]) ? $njDenoms[$gid] : [],
            isset($njHits['pairs'][$pickKey][$gid]) ? $njHits['pairs'][$pickKey][$gid] : [],
            $njSelectedCols,
            $njBy,
            $njOptions
        );
        $rowStyle = ($prevColor === $color1) ? $color2 : $color1;
        $prevColor = $rowStyle;
        echo '<tr style="' . $rowStyle . '">';
        echo '<td class="nj-group" style="' . $rowStyle . '">' . htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') . '</td>';
        foreach ($njSelectedCols as $key) {
            $cell = isset($cells[$key]) ? $cells[$key] : ['n' => 0, 'd' => 0];
            $text = $hasData ? nj_read_breakdown_display($cell['n'], $cell['d'], $njMinSamples, $njShowBelow) : '-1';
            $title = $hasData ? (int) $cell['n'] . ' / ' . (int) $cell['d'] : 'no TRS-L files for this group yet';
            echo '<td title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
echo '</div>';
