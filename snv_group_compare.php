<?php
/**
 * SNV comparison across VCF groups (Jim, Zoom 2026-10-19, D-76): one % column per group.
 * Rel. % Diff only for exactly two groups; with more it is left out (a standard deviation may come later).
 */
require_once __DIR__ . '/snv_pair_compare.php';
require_once __DIR__ . '/vcf_snv_helpers.php';

if (!function_exists('snv_group_compare_request')) {
    /**
     * @return array{codes:list<string>,min_af:float,min_percent:float}
     */
    function snv_group_compare_request()
    {
        $codes = [];
        $raw = isset($_GET['Groups']) ? $_GET['Groups'] : [];
        if (!is_array($raw)) {
            $raw = explode(',', (string) $raw);
        }
        foreach ($raw as $code) {
            $code = trim((string) $code);
            if ($code !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $code) && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return [
            'codes' => array_slice($codes, 0, 12),
            'min_af' => snv_pair_request_min_af(),
            'min_percent' => snv_pair_request_min_percent(),
        ];
    }
}

if (!function_exists('snv_group_compare_pack')) {
    /**
     * Percent of analyzed samples carrying each SNV in each group, same counting as Mutations Detail.
     *
     * @return array<string,mixed>
     */
    function snv_group_compare_pack(mysqli $con, array $req)
    {
        if (count($req['codes']) < 2) {
            return ['error' => 'Choose at least two groups.'];
        }
        $groups = [];
        $calls = [];
        foreach ($req['codes'] as $i => $code) {
            $pack = vcf_snv_detail_pack($con, $code, $req['min_af'], '', '', '');
            if ($pack === null || (int) $pack['sample_count'] < 1) {
                return ['error' => 'Group ' . $code . ' is not available.'];
            }
            $groups[$i] = ['code' => $code, 'label' => (string) $pack['group_label'], 'n' => (int) $pack['sample_count']];
            $seen = [];
            foreach ($pack['rows'] as $row) {
                $ref = strtoupper(trim((string) $row['reference']));
                $alt = strtoupper(trim((string) $row['alternate']));
                $key = (int) $row['coordinate'] . '|' . $ref . '|' . $alt;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                if (!isset($calls[$key])) {
                    $calls[$key] = ['coordinate' => (int) $row['coordinate'], 'reference' => $ref, 'alternate' => $alt, 'n' => []];
                }
                $calls[$key]['n'][$i] = (int) $row['no_of_samples'];
            }
        }
        $two = count($groups) === 2;
        $rows = [];
        foreach ($calls as $call) {
            $pcts = [];
            $keep = false;
            foreach ($groups as $i => $group) {
                $pct = round(((isset($call['n'][$i]) ? $call['n'][$i] : 0) / $group['n']) * 100, 2);
                $pcts[$i] = $pct;
                if ($pct >= $req['min_percent']) {
                    $keep = true;
                }
            }
            if (!$keep) {
                continue;
            }
            $rows[] = [
                'name' => snv_pango_format_label($call['coordinate'], $call['reference'], $call['alternate']),
                'coordinate' => $call['coordinate'],
                'reference' => $call['reference'],
                'alternate' => $call['alternate'],
                'pcts' => $pcts,
                'rel' => $two ? snv_pair_rel_diff($pcts[0], $pcts[1]) : null,
            ];
        }
        usort($rows, function ($a, $b) use ($two) {
            if ($two && $a['rel'] !== $b['rel']) {
                if ($a['rel'] === null) {
                    return 1;
                }
                if ($b['rel'] === null) {
                    return -1;
                }
                return $b['rel'] <=> $a['rel'];
            }

            return $a['coordinate'] <=> $b['coordinate'];
        });

        return ['groups' => $groups, 'rows' => $rows, 'two' => $two] + $req;
    }
}

if (!function_exists('snv_group_compare_csv_href')) {
    function snv_group_compare_csv_href(array $pack)
    {
        $href = 'SnvGroupCompare.php?what=csv';
        foreach ($pack['groups'] as $group) {
            $href .= '&Groups[]=' . rawurlencode($group['code']);
        }

        return $href . '&MinAf=' . rawurlencode(snv_pair_format_number($pack['min_af']))
            . '&MinPercent=' . rawurlencode(snv_pair_format_number($pack['min_percent']));
    }
}

if (!function_exists('snv_group_compare_html')) {
    function snv_group_compare_html(array $pack)
    {
        if (isset($pack['error'])) {
            return snv_pair_note($pack['error']);
        }
        $names = [];
        foreach ($pack['groups'] as $group) {
            $names[] = $group['label'] . ' n=' . $group['n'];
        }
        $scope = 'Groups: ' . implode('; ', $names) . '. '
            . 'Each percent is the share of analyzed samples in that group with the SNV at allele frequency '
            . snv_pair_format_number($pack['min_af']) . ' or more. '
            . 'Rows under ' . snv_pair_format_number($pack['min_percent']) . '% in every group are hidden. '
            . ($pack['two']
                ? 'Rel. % Diff is the absolute difference divided by the average of the two percents. Largest relative difference first.'
                : 'Rel. % Diff is shown only when two groups are compared. Sorted by coordinate.');
        $html = '<div style="padding:8px 0 4px;font-size:12px;">' . htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') . '</div>';
        $html .= '<p style="margin:6px 0;"><a href="' . htmlspecialchars(snv_group_compare_csv_href($pack), ENT_QUOTES, 'UTF-8') . '">Download this table</a></p>';
        $html .= '<div class="datagrid"><table class="sortable"><thead><tr class="dark"><th>SNV</th>';
        foreach ($pack['groups'] as $group) {
            $html .= '<th>% SNV in ' . htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') . '</th>';
        }
        if ($pack['two']) {
            $html .= '<th title="Absolute difference divided by the average of the two percents">Rel. % Diff</th>';
        }
        $html .= '</tr></thead><tbody>';
        if (!$pack['rows']) {
            $html .= '<tr><td colspan="' . (count($pack['groups']) + 2) . '">No SNVs at or above '
                . htmlspecialchars(snv_pair_format_number($pack['min_percent']), ENT_QUOTES, 'UTF-8') . '% in any group.</td></tr>';
        }
        foreach ($pack['rows'] as $row) {
            $html .= '<tr class="snv-primer-row" title="Show primers ±800 bp around this SNV"'
                . ' data-coord="' . (int) $row['coordinate'] . '"'
                . ' data-ref="' . htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8') . '"'
                . ' data-alt="' . htmlspecialchars($row['alternate'], ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<td>' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</td>';
            foreach ($row['pcts'] as $pct) {
                $text = htmlspecialchars(snv_pair_format_number($pct), ENT_QUOTES, 'UTF-8');
                $html .= '<td data-sort="' . $text . '">' . $text . '</td>';
            }
            if ($pack['two']) {
                $rel = $row['rel'] === null ? '' : snv_pair_format_number($row['rel']);
                $html .= '<td data-sort="' . htmlspecialchars($row['rel'] === null ? '-1' : $rel, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table></div>';
    }
}

if (!function_exists('snv_group_compare_csv')) {
    /**
     * @return array{filename:string,text:string}
     */
    function snv_group_compare_csv(array $pack)
    {
        $safe = function ($value) {
            return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $value), '_');
        };
        $codes = [];
        $head = ['SNV'];
        $meta = '# SARSNTDB group SNV comparison';
        foreach ($pack['groups'] as $i => $group) {
            $codes[] = $safe($group['code']);
            $head[] = '% SNV in ' . $group['label'];
            $meta .= '; group' . ($i + 1) . '=' . $group['label'] . '; n' . ($i + 1) . '=' . $group['n'];
        }
        if ($pack['two']) {
            $head[] = 'Rel. % Diff';
        }
        $meta .= '; min_af=' . snv_pair_format_number($pack['min_af'])
            . '; min_percent=' . snv_pair_format_number($pack['min_percent']) . '; rows=' . count($pack['rows']);
        $lines = [$meta, nj_read_csv_line($head)];
        foreach ($pack['rows'] as $row) {
            $line = [$row['name']];
            foreach ($row['pcts'] as $pct) {
                $line[] = snv_pair_format_number($pct);
            }
            if ($pack['two']) {
                $line[] = $row['rel'] === null ? '' : snv_pair_format_number($row['rel']);
            }
            $lines[] = nj_read_csv_line($line);
        }

        return [
            'filename' => 'snv-groups_' . implode('_', $codes) . '_minAf' . snv_pair_format_number($pack['min_af']) . '.csv',
            'text' => implode("\n", $lines) . "\n",
        ];
    }
}
