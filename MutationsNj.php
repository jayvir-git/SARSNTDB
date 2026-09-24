<?php
/**
 * HTML fragment: clickable NJ list, then a group × column percent table.
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
if (!array_key_exists('njPick', get_defined_vars())) {
    $njPick = nj_read_request_pick();
}

if (!nj_read_tables_exist($con)) {
    echo '<p class="text-muted">NJ read tables are not loaded yet.</p>';
    return;
}

$catalog = nj_read_catalog_rows($con);
if (!$catalog) {
    echo '<p class="text-muted">No junctions from the coordinate table are loaded.</p>';
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

if (!isset($njSummary) || !is_array($njSummary)) {
    $summaryIds = [];
    foreach ($njGroupRows as $group) {
        $summaryIds[] = (int) $group['id'];
    }
    $summaryNgene = $wantNgene ? nj_read_ngene_group_ids($con, $summaryIds) : [];
    $njSummary = nj_read_summary_rows(
        $catalog,
        $summaryIds,
        nj_read_pair_denoms($con, $summaryIds, $minReads, $summaryNgene),
        nj_read_junction_hits($con, $summaryIds, $minReads, $summaryNgene),
        $njOptions
    );
}
$summaryByPick = [];
foreach ($njSummary as $summaryRow) {
    $summaryByPick[nj_read_pick_value($summaryRow['nj_size'], $summaryRow['coord_tag'])] = $summaryRow;
}
echo '<p class="text-muted" style="font-size:12px; margin:8px 0 0 0;">Averages for the selected groups. Delta and Omicron are merged. BA.1 is left out of Omicron. ' . htmlspecialchars(nj_read_primer_caption(), ENT_QUOTES, 'UTF-8') . ' <strong>-1</strong> means the count is under the minimum number of samples. <strong>0</strong> means the percent is actually 0. Click a column heading to sort.</p>';
echo '<div class="nj-datagrid"><table class="sortable"><thead><tr class="dark">';
echo '<th>NJ size</th><th>Start</th><th>End</th>';
echo '<th>Delta V3</th><th>Omi V3</th><th>Delta V4.1</th><th>Omi V4.1</th><th>Overall</th>';
echo '</tr></thead><tbody>';
$color1 = 'background-color:White';
$color2 = 'background-color:LightGray';
$prevColor = $color1;
foreach ($catalog as $row) {
    $start = $row['nj_start'] === null ? '' : (string) $row['nj_start'];
    $end = $row['nj_end'] === null ? '' : (string) $row['nj_end'];
    $pickValue = nj_read_pick_value($row['nj_size'], $row['coord_tag']);
    $summaryRow = isset($summaryByPick[$pickValue]) ? $summaryByPick[$pickValue] : null;
    $current = $njPick !== null && $pickValue === nj_read_pick_value($njPick['nj_size'], $njPick['coord_tag']);
    $rowStyle = ($prevColor === $color1) ? $color2 : $color1;
    $prevColor = $rowStyle;
    echo '<tr class="nj-pick-row' . ($current ? ' nj-pick-current' : '') . '" style="' . $rowStyle . '">';
    echo '<td><button type="submit" class="nj-show" name="NjPick" value="' . htmlspecialchars($pickValue, ENT_QUOTES, 'UTF-8') . '">';
    echo (int) $row['nj_size'];
    echo '</button></td>';
    echo '<td>' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . '</td>';
    foreach (['delta_v3', 'omi_v3', 'delta_v41', 'omi_v41', 'overall'] as $summaryKey) {
        $text = $summaryRow === null ? '-1' : (string) $summaryRow[$summaryKey];
        echo '<td>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo '</tr>';
}
echo '</tbody></table></div>';

if ($njPick === null) {
    return;
}

echo '<div id="njBreakdown">';
echo '<h4 class="search-header" style="margin-top:16px;">';
if ($pickRow === null) {
    echo 'That junction is not in the coordinate table.';
    echo '</h4></div>';
    return;
}
$start = $pickRow['nj_start'] === null ? '' : (string) $pickRow['nj_start'];
$end = $pickRow['nj_end'] === null ? '' : (string) $pickRow['nj_end'];
echo 'NJ ' . (int) $pickRow['nj_size'];
if ($start !== '' || $end !== '') {
    echo ' (' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '–' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . ')';
}
echo '</h4>';

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

$groupIds = [];
foreach ($njGroupRows as $group) {
    $groupIds[] = (int) $group['id'];
}
$ngeneIds = $wantNgene ? nj_read_ngene_group_ids($con, $groupIds) : [];
$raw = nj_read_breakdown_raw($con, $groupIds, $pickRow['nj_size'], $pickRow['coord_tag'], $minReads, $ngeneIds);
$cells = nj_read_fold_breakdown($raw, $njSelectedCols, $njBy, $njOptions);

echo '<p style="font-size:12px;">Min reads ' . (int) $minReads . '. ';
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
    $rowStyle = ($prevColor === $color1) ? $color2 : $color1;
    $prevColor = $rowStyle;
    echo '<tr style="' . $rowStyle . '">';
    echo '<td class="nj-group" style="' . $rowStyle . '">' . htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') . '</td>';
    foreach ($njSelectedCols as $key) {
        $cell = isset($cells[(int) $group['id'] . "\t" . $key]) ? $cells[(int) $group['id'] . "\t" . $key] : ['n' => 0, 'd' => 0];
        $text = nj_read_breakdown_display($cell['n'], $cell['d'], $njMinSamples, $njShowBelow);
        $title = (int) $cell['n'] . ' / ' . (int) $cell['d'];
        echo '<td title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo '</tr>';
}
echo '</tbody></table></div></div>';
