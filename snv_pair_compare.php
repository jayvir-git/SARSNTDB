<?php
/**
 * SNV comparison for two variant–primer pairs in one VCF group.
 * Percent is samples in that pair with the SNV (AF at or above the cutoff)
 * divided by samples in that pair. Rel. % Diff is the absolute difference
 * of the two percents divided by their average.
 */

require_once __DIR__ . '/nj_read_helpers.php';

if (!function_exists('snv_pair_rel_diff')) {
    /**
     * @param float|int $left
     * @param float|int $right
     * @return float|null
     */
    function snv_pair_rel_diff($left, $right)
    {
        $left = (float) $left;
        $right = (float) $right;
        $sum = $left + $right;
        if ($sum <= 0) {
            return null;
        }

        return round(abs($left - $right) / ($sum / 2) * 100, 2);
    }
}

if (!function_exists('snv_pair_format_number')) {
    function snv_pair_format_number($value)
    {
        return number_format((float) $value, 2, '.', '');
    }
}

if (!function_exists('snv_pair_matches_column')) {
    /**
     * @param array<string,mixed> $col
     */
    function snv_pair_matches_column($variant, $primer, array $col)
    {
        $primers = isset($col['primers']) ? $col['primers'] : [];
        if (!in_array($primer, $primers, true)) {
            return false;
        }
        if (isset($col['block']) && $col['block'] === 'raw') {
            return $variant === (isset($col['raw_variant']) ? $col['raw_variant'] : '');
        }

        return nj_read_pair_label_in_block($variant, isset($col['block']) ? $col['block'] : '');
    }
}

if (!function_exists('snv_pair_column_defs')) {
    /**
     * @param list<array{id?:int,variant:string,primer:string}> $samples
     * @param array<string,mixed> $options
     * @return list<array<string,mixed>>
     */
    function snv_pair_column_defs(array $samples, array $options)
    {
        $opt = nj_read_coerce_merge($options);
        $cols = [];
        foreach (nj_read_pair_filter_columns($opt) as $col) {
            if (empty($opt['merge_delta']) && $col['block'] === 'delta') {
                continue;
            }
            $primer = $col['primers'][0];
            $col['key'] = $col['block'] . '|' . $primer;
            $col['label'] = trim($col['top'] . ' ' . $col['sub']);
            $cols[] = $col;
        }
        if (!empty($opt['merge_delta'])) {
            return $cols;
        }
        $seen = [];
        $deltaCols = [];
        foreach ($samples as $sample) {
            $variant = trim((string) $sample['variant']);
            $primer = trim((string) $sample['primer']);
            if (nj_read_variant_bucket($variant) !== 'delta') {
                continue;
            }
            if (nj_read_variant_key($variant, $opt) === null || nj_read_primer_is_hidden($primer)) {
                continue;
            }
            $key = 'raw:' . $variant . '|' . $primer;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $abbrev = nj_read_primer_abbrev($primer);
            $deltaCols[] = [
                'key' => $key,
                'label' => $variant . ($abbrev !== '' ? ' ' . $abbrev : ''),
                'top' => $variant,
                'sub' => $abbrev,
                'buckets' => ['delta'],
                'primers' => [$primer],
                'block' => 'raw',
                'raw_variant' => $variant,
            ];
        }

        return array_merge($deltaCols, $cols);
    }
}

if (!function_exists('snv_pair_build')) {
    /**
     * Pair columns for one group. A merged column keeps a subtype only when
     * that subtype itself meets the minimum sample count.
     *
     * @param list<array{id?:int,variant:string,primer:string}> $samples
     * @param array<string,mixed> $options
     * @return list<array<string,mixed>>
     */
    function snv_pair_build(array $samples, array $options)
    {
        $opt = nj_read_coerce_merge($options);
        $min = (int) $opt['min_samples'];
        $cols = snv_pair_column_defs($samples, $opt);
        $rawCounts = [];
        $matched = [];
        foreach ($samples as $sample) {
            $variant = trim((string) $sample['variant']);
            $primer = trim((string) $sample['primer']);
            if (nj_read_primer_is_hidden($primer) || nj_read_variant_key($variant, $opt) === null) {
                continue;
            }
            $idx = null;
            foreach ($cols as $i => $col) {
                if (snv_pair_matches_column($variant, $primer, $col)) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx === null) {
                continue;
            }
            $rawKey = $variant . "\t" . $primer;
            if (!isset($rawCounts[$rawKey])) {
                $rawCounts[$rawKey] = 0;
            }
            $rawCounts[$rawKey]++;
            $matched[] = [
                'id' => isset($sample['id']) ? (int) $sample['id'] : 0,
                'idx' => $idx,
                'raw' => $rawKey,
            ];
        }
        foreach ($cols as $i => $col) {
            $cols[$i]['n'] = 0;
            $cols[$i]['raw'] = 0;
            $cols[$i]['ids'] = [];
        }
        foreach ($matched as $item) {
            $i = $item['idx'];
            $cols[$i]['raw']++;
            $count = $rawCounts[$item['raw']];
            if ($min > 0 && $count < $min) {
                continue;
            }
            $cols[$i]['n']++;
            $cols[$i]['ids'][] = $item['id'];
        }
        $out = [];
        foreach ($cols as $col) {
            if ($col['raw'] < 1) {
                continue;
            }
            $col['ready'] = $col['n'] > 0;
            $out[] = $col;
        }

        return $out;
    }
}

if (!function_exists('snv_pair_public_choice')) {
    /**
     * @param array<string,mixed> $col
     * @return array{key:string,label:string,n:int,raw:int,ready:bool}
     */
    function snv_pair_public_choice(array $col)
    {
        return [
            'key' => (string) $col['key'],
            'label' => (string) $col['label'],
            'n' => (int) $col['n'],
            'raw' => (int) $col['raw'],
            'ready' => !empty($col['ready']),
        ];
    }
}

if (!function_exists('snv_pair_choice_text')) {
    /**
     * @param array{label:string,n:int,raw:int,ready:bool} $choice
     */
    function snv_pair_choice_text(array $choice)
    {
        if (!empty($choice['ready'])) {
            return $choice['label'] . ' (' . (int) $choice['n'] . ')';
        }

        return $choice['label'] . ' (' . (int) $choice['raw'] . ', under minimum)';
    }
}

if (!function_exists('snv_pair_default_keys')) {
    /**
     * @param list<array{key:string,ready:bool}> $choices
     * @return array{0:string,1:string}
     */
    function snv_pair_default_keys(array $choices)
    {
        $byKey = [];
        foreach ($choices as $choice) {
            $byKey[$choice['key']] = $choice;
        }
        $v3 = 'delta|COVID-ARTIC-V3';
        $v41 = 'delta|COVID-ARTIC-V4.1';
        if (isset($byKey[$v3], $byKey[$v41]) && !empty($byKey[$v3]['ready']) && !empty($byKey[$v41]['ready'])) {
            return [$v3, $v41];
        }
        $ready = [];
        foreach ($choices as $choice) {
            if (!empty($choice['ready'])) {
                $ready[] = $choice['key'];
            }
        }

        return [
            isset($ready[0]) ? $ready[0] : '',
            isset($ready[1]) ? $ready[1] : '',
        ];
    }
}

if (!function_exists('snv_pair_find')) {
    /**
     * @param list<array<string,mixed>> $built
     * @return array<string,mixed>|null
     */
    function snv_pair_find(array $built, $key)
    {
        foreach ($built as $col) {
            if ((string) $col['key'] === (string) $key) {
                return $col;
            }
        }

        return null;
    }
}

if (!function_exists('snv_pair_compare_rows')) {
    /**
     * @param list<array{coordinate:int,reference:string,alternate:string,n1:int,n2:int}> $calls
     * @return list<array<string,mixed>>
     */
    function snv_pair_compare_rows(array $calls, $denom1, $denom2, $minPercent)
    {
        $denom1 = (int) $denom1;
        $denom2 = (int) $denom2;
        $minPercent = (float) $minPercent;
        $rows = [];
        if ($denom1 < 1 || $denom2 < 1) {
            return $rows;
        }
        foreach ($calls as $call) {
            $p1 = round(((int) $call['n1'] / $denom1) * 100, 2);
            $p2 = round(((int) $call['n2'] / $denom2) * 100, 2);
            if ($p1 < $minPercent && $p2 < $minPercent) {
                continue;
            }
            $rows[] = [
                'name' => snv_pango_format_label($call['coordinate'], $call['reference'], $call['alternate']),
                'coordinate' => (int) $call['coordinate'],
                'reference' => strtoupper(trim((string) $call['reference'])),
                'alternate' => strtoupper(trim((string) $call['alternate'])),
                'pct1' => $p1,
                'pct2' => $p2,
                'rel' => snv_pair_rel_diff($p1, $p2),
            ];
        }
        usort($rows, function ($a, $b) {
            if ($a['rel'] === null && $b['rel'] === null) {
                return $a['coordinate'] <=> $b['coordinate'];
            }
            if ($a['rel'] === null) {
                return 1;
            }
            if ($b['rel'] === null) {
                return -1;
            }
            if ($a['rel'] != $b['rel']) {
                return $b['rel'] <=> $a['rel'];
            }

            return $a['coordinate'] <=> $b['coordinate'];
        });

        return $rows;
    }
}

if (!function_exists('snv_pair_note')) {
    function snv_pair_note($text)
    {
        return '<p class="text-muted" style="margin:12px 0;">' . htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8') . '</p>';
    }
}

if (!function_exists('snv_pair_left_out_text')) {
    /**
     * @param array<string,mixed> $choice
     */
    function snv_pair_left_out_text($label, array $choice)
    {
        $leftOut = (int) $choice['raw'] - (int) $choice['n'];
        if ($leftOut < 1) {
            return '';
        }
        $word = $leftOut === 1 ? 'sample' : 'samples';

        return ' ' . $label . ' leaves out ' . $leftOut . ' ' . $word . ' in subtypes under the minimum.';
    }
}

if (!function_exists('snv_pair_unready_text')) {
    /**
     * @param array<string,mixed> $choice
     */
    function snv_pair_unready_text(array $choice, $minSamples)
    {
        return $choice['label'] . ' has ' . (int) $choice['raw']
            . ' samples, under the minimum of ' . (int) $minSamples . '.';
    }
}

if (!function_exists('snv_pair_render')) {
    /**
     * @param list<array<string,mixed>> $rows
     * @param array<string,mixed> $group
     * @param array<string,mixed> $left
     * @param array<string,mixed> $right
     */
    function snv_pair_render(array $rows, array $group, array $left, array $right, $minAf, $minPercent)
    {
        $leftLabel = (string) $left['label'];
        $rightLabel = (string) $right['label'];
        $scope = (string) $group['label']
            . '. ' . $leftLabel . ' n=' . (int) $left['n']
            . ', ' . $rightLabel . ' n=' . (int) $right['n'] . '. '
            . 'A sample counts when its allele frequency is at least ' . snv_pair_format_number($minAf) . '. '
            . 'Each percent is the share of samples in that pair. '
            . 'Rows under ' . snv_pair_format_number($minPercent) . '% in both pairs are hidden. '
            . 'Rel. % Diff is the absolute difference divided by the average of the two percents. '
            . 'Largest relative difference first.';
        $scope .= snv_pair_left_out_text($leftLabel, $left);
        $scope .= snv_pair_left_out_text($rightLabel, $right);
        $html = '<div style="padding:8px 0 4px;font-size:12px;">' . htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') . '</div>';
        $html .= '<div class="datagrid"><table class="sortable"><thead><tr class="dark">';
        $html .= '<th>SNV</th>';
        $html .= '<th data-sort-method="number">% SNV in ' . htmlspecialchars($leftLabel, ENT_QUOTES, 'UTF-8') . '</th>';
        $html .= '<th>% SNV in ' . htmlspecialchars($rightLabel, ENT_QUOTES, 'UTF-8') . '</th>';
        $html .= '<th title="Absolute difference divided by the average of the two percents">Rel. % Diff</th>';
        $html .= '</tr></thead><tbody>';
        if (!$rows) {
            $html .= '<tr><td colspan="4">No SNVs at or above '
                . htmlspecialchars(snv_pair_format_number($minPercent), ENT_QUOTES, 'UTF-8')
                . '% in either pair.</td></tr>';
        }
        foreach ($rows as $row) {
            $rel = $row['rel'] === null ? '' : snv_pair_format_number($row['rel']);
            $relSort = $row['rel'] === null ? '-1' : $rel;
            $html .= '<tr class="snv-primer-row" title="Show primers ±800 bp around this SNV"'
                . ' data-coord="' . (int) $row['coordinate'] . '"'
                . ' data-ref="' . htmlspecialchars((string) $row['reference'], ENT_QUOTES, 'UTF-8') . '"'
                . ' data-alt="' . htmlspecialchars((string) $row['alternate'], ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<td>' . htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td data-sort="' . htmlspecialchars(snv_pair_format_number($row['pct1']), ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars(snv_pair_format_number($row['pct1']), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td data-sort="' . htmlspecialchars(snv_pair_format_number($row['pct2']), ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars(snv_pair_format_number($row['pct2']), ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td data-sort="' . htmlspecialchars($relSort, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }
}

if (!function_exists('snv_pair_request_min_samples')) {
    function snv_pair_request_min_samples()
    {
        if (!isset($_GET['MinSamples']) || trim((string) $_GET['MinSamples']) === '') {
            return 30;
        }
        $raw = trim((string) $_GET['MinSamples']);
        if (!preg_match('/^\d+$/', $raw)) {
            return 30;
        }

        return (int) $raw;
    }
}

if (!function_exists('snv_pair_request_min_percent')) {
    function snv_pair_request_min_percent()
    {
        if (!isset($_GET['MinPercent']) || trim((string) $_GET['MinPercent']) === '') {
            return 1.0;
        }
        $raw = str_replace(',', '.', trim((string) $_GET['MinPercent']));
        if (!is_numeric($raw)) {
            return 1.0;
        }
        $n = (float) $raw;
        if ($n < 0) {
            return 0.0;
        }
        if ($n > 100) {
            return 100.0;
        }

        return $n;
    }
}

if (!function_exists('snv_pair_request_min_af')) {
    function snv_pair_request_min_af()
    {
        if (!isset($_GET['MinAf']) || !is_numeric($_GET['MinAf'])) {
            return 0.8;
        }
        $n = (float) $_GET['MinAf'];
        if ($n < 0) {
            return 0.0;
        }
        if ($n > 1) {
            return 1.0;
        }

        return $n;
    }
}

if (!function_exists('snv_pair_request_options')) {
    /**
     * @return array<string,mixed>
     */
    function snv_pair_request_options()
    {
        $merge = true;
        if (isset($_GET['MergeDelta'])) {
            $raw = $_GET['MergeDelta'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $merge = (string) $raw === '1';
        }

        return nj_read_coerce_merge([
            'merge_delta' => $merge,
            'major_only' => true,
            'min_samples' => snv_pair_request_min_samples(),
        ]);
    }
}

if (!function_exists('snv_pair_fetch_samples')) {
    /**
     * Eligible samples with a variant and primer call. Same PASS∩VCF set as the detail table.
     *
     * @param array<string,mixed> $group
     * @return list<array{id:int,variant:string,primer:string}>
     */
    function snv_pair_fetch_samples(mysqli $con, array $group)
    {
        if (empty($group['id']) || !snv_pango_table_named_exists($con, 'vcf_snv_sample_meta')) {
            return [];
        }
        $gid = (int) $group['id'];
        $sql = 'SELECT s.id, m.variant_label, m.primer_label
                  FROM vcf_snv_sample s
                  INNER JOIN vcf_snv_sample_meta m ON m.sample_id = s.id';
        if (empty($group['isolated']) && vcf_snv_eligibility_active($con, $gid)) {
            $sql .= ' INNER JOIN vcf_snv_sample_eligibility e
                        ON e.sample_id = s.id AND e.group_id = ' . $gid . ' AND e.eligible = 1';
        }
        $sql .= ' WHERE s.group_id = ?';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $gid);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'id' => (int) $row['id'],
                    'variant' => (string) $row['variant_label'],
                    'primer' => (string) $row['primer_label'],
                ];
            }
        }
        $stmt->close();

        return $out;
    }
}

if (!function_exists('snv_pair_insert_slots')) {
    /**
     * @param list<int> $ids
     */
    function snv_pair_insert_slots(mysqli $con, array $ids, $slot)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $slot = (int) $slot;
        $size = 400;
        for ($offset = 0; $offset < count($ids); $offset += $size) {
            $slice = array_slice($ids, $offset, $size);
            if (!$slice) {
                continue;
            }
            $places = implode(',', array_fill(0, count($slice), '(?,?)'));
            $stmt = $con->prepare('INSERT INTO snv_pair_slot (sample_id, slot) VALUES ' . $places);
            if (!$stmt) {
                return false;
            }
            $types = str_repeat('ii', count($slice));
            $params = [];
            foreach ($slice as $id) {
                $params[] = $id;
                $params[] = $slot;
            }
            $stmt->bind_param($types, ...$params);
            $ok = $stmt->execute();
            $stmt->close();
            if (!$ok) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('snv_pair_fetch_calls')) {
    /**
     * @param list<int> $ids1
     * @param list<int> $ids2
     * @return list<array{coordinate:int,reference:string,alternate:string,n1:int,n2:int}>
     */
    function snv_pair_fetch_calls(mysqli $con, $groupId, array $ids1, array $ids2, $minAf)
    {
        $groupId = (int) $groupId;
        $minAf = (float) $minAf;
        if ($groupId < 1 || !$ids1 || !$ids2) {
            return [];
        }
        $con->query('DROP TEMPORARY TABLE IF EXISTS snv_pair_slot');
        $made = $con->query(
            'CREATE TEMPORARY TABLE snv_pair_slot (
                sample_id INT UNSIGNED NOT NULL PRIMARY KEY,
                slot TINYINT NOT NULL
             ) ENGINE=MEMORY'
        );
        if (!$made) {
            return [];
        }
        if (!snv_pair_insert_slots($con, $ids1, 1) || !snv_pair_insert_slots($con, $ids2, 2)) {
            $con->query('DROP TEMPORARY TABLE IF EXISTS snv_pair_slot');
            return [];
        }
        $sql = 'SELECT c.coordinate, c.reference, c.alternate,
                       SUM(p.slot = 1) AS n1,
                       SUM(p.slot = 2) AS n2
                  FROM vcf_snv_call c
                  INNER JOIN snv_pair_slot p ON p.sample_id = c.sample_id
                 WHERE c.group_id = ? AND c.allele_frequency >= ?
                 GROUP BY c.coordinate, c.reference, c.alternate';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            $con->query('DROP TEMPORARY TABLE IF EXISTS snv_pair_slot');
            return [];
        }
        $stmt->bind_param('id', $groupId, $minAf);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'coordinate' => (int) $row['coordinate'],
                    'reference' => (string) $row['reference'],
                    'alternate' => (string) $row['alternate'],
                    'n1' => (int) $row['n1'],
                    'n2' => (int) $row['n2'],
                ];
            }
        }
        $stmt->close();
        $con->query('DROP TEMPORARY TABLE IF EXISTS snv_pair_slot');

        return $out;
    }
}

if (!function_exists('snv_pair_choices_payload')) {
    /**
     * @return array{choices:list<array<string,mixed>>,pair1:string,pair2:string}
     */
    function snv_pair_choices_payload(mysqli $con)
    {
        $empty = ['choices' => [], 'pair1' => '', 'pair2' => ''];
        $code = isset($_GET['Group']) ? trim((string) $_GET['Group']) : '';
        if ($code === '' || $code === 'original') {
            return $empty;
        }
        $group = vcf_snv_group_row($con, $code);
        if ($group === null) {
            return $empty;
        }
        $built = snv_pair_build(snv_pair_fetch_samples($con, $group), snv_pair_request_options());
        $choices = [];
        foreach ($built as $col) {
            $choices[] = snv_pair_public_choice($col);
        }
        $defaults = snv_pair_default_keys($choices);

        return [
            'choices' => $choices,
            'pair1' => $defaults[0],
            'pair2' => $defaults[1],
        ];
    }
}

if (!function_exists('snv_pair_table_html')) {
    function snv_pair_table_html(mysqli $con)
    {
        $code = isset($_GET['Group']) ? trim((string) $_GET['Group']) : '';
        if ($code === '' || $code === 'original') {
            return snv_pair_note('Pick a VCF group. Original has no variant or primer calls.');
        }
        $group = vcf_snv_group_row($con, $code);
        if ($group === null) {
            return snv_pair_note('That group is not available.');
        }
        $options = snv_pair_request_options();
        $built = snv_pair_build(snv_pair_fetch_samples($con, $group), $options);
        $key1 = isset($_GET['Pair1']) ? (string) $_GET['Pair1'] : '';
        $key2 = isset($_GET['Pair2']) ? (string) $_GET['Pair2'] : '';
        if ($key1 === '' || $key2 === '') {
            return snv_pair_note('Pick two pairs.');
        }
        if ($key1 === $key2) {
            return snv_pair_note('Pick two different pairs.');
        }
        $left = snv_pair_find($built, $key1);
        $right = snv_pair_find($built, $key2);
        if ($left === null || $right === null) {
            return snv_pair_note('One of those pairs is not in this group.');
        }
        $min = (int) $options['min_samples'];
        if (empty($left['ready']) || empty($right['ready'])) {
            $bits = [];
            if (empty($left['ready'])) {
                $bits[] = snv_pair_unready_text($left, $min);
            }
            if (empty($right['ready'])) {
                $bits[] = snv_pair_unready_text($right, $min);
            }

            return snv_pair_note(implode(' ', $bits));
        }
        $minAf = snv_pair_request_min_af();
        $minPercent = snv_pair_request_min_percent();
        $calls = snv_pair_fetch_calls($con, (int) $group['id'], $left['ids'], $right['ids'], $minAf);
        $rows = snv_pair_compare_rows($calls, $left['n'], $right['n'], $minPercent);

        return snv_pair_render($rows, $group, $left, $right, $minAf, $minPercent);
    }
}
