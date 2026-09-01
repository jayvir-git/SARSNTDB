<?php
/**
 * Helpers for junction group / variant / primer queries (Jim Kelley prototype).
 * Tables: junction_query_dataset, junction_query_group, junction_query_measure.
 * Reverse: import sql/junction_query_drop.sql
 */

if (!function_exists('jq_tables_exist')) {
    function jq_tables_exist(mysqli $con)
    {
        $sql = "SELECT COUNT(*) AS c FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('junction_query_dataset','junction_query_group','junction_query_measure')";
        $res = $con->query($sql);
        if (!$res) {
            return false;
        }
        $row = $res->fetch_assoc();
        $res->free();

        return isset($row['c']) && (int) $row['c'] === 3;
    }
}

if (!function_exists('jq_fetch_datasets')) {
    /**
     * @return list<array<string,mixed>>
     */
    function jq_fetch_datasets(mysqli $con)
    {
        $out = [];
        $sql = 'SELECT id, code, title, source_file, chart_kind, junction_left, junction_right, junction_size, notes
                FROM junction_query_dataset
                ORDER BY id ASC';
        if (!($res = $con->query($sql))) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('jq_dataset_by_code')) {
    /**
     * @param list<array<string,mixed>> $datasets
     * @return array<string,mixed>|null
     */
    function jq_dataset_by_code(array $datasets, $code)
    {
        foreach ($datasets as $ds) {
            if ((string) $ds['code'] === (string) $code) {
                return $ds;
            }
        }

        return null;
    }
}

if (!function_exists('jq_get_list')) {
    /**
     * @return list<string>
     */
    function jq_get_list($key)
    {
        if (!isset($_GET[$key])) {
            return [];
        }
        $raw = $_GET[$key];
        if (!is_array($raw)) {
            $raw = [$raw];
        }
        $out = [];
        foreach ($raw as $v) {
            $v = trim((string) $v);
            if ($v !== '') {
                $out[] = $v;
            }
        }

        return array_values(array_unique($out));
    }
}

if (!function_exists('jq_bind_in')) {
    /**
     * @param list<string> $values
     * @param list<mixed> $params
     * @return string placeholders
     */
    function jq_bind_in(array $values, array &$params, &$types)
    {
        foreach ($values as $v) {
            $params[] = $v;
            $types .= 's';
        }

        return implode(',', array_fill(0, count($values), '?'));
    }
}

if (!function_exists('jq_filter_options')) {
    /**
     * Distinct filter values across all datasets for the selected junction.
     *
     * @return array{continents:list<string>,instruments:list<string>,variants:list<array{code:string,label:string}>,primers:list<string>,groups:list<string>}
     */
    function jq_filter_options(mysqli $con, $left, $right)
    {
        $left = (int) $left;
        $right = (int) $right;
        $empty = [
            'continents' => [],
            'instruments' => [],
            'variants' => [],
            'primers' => [],
            'groups' => [],
        ];
        $sql = 'SELECT g.continent, g.instrument, g.name AS group_name, m.variant_code, m.variant_label, m.primer
                FROM junction_query_group g
                INNER JOIN junction_query_dataset d ON d.id = g.dataset_id
                INNER JOIN junction_query_measure m ON m.group_id = g.id
                WHERE d.junction_left = ? AND d.junction_right = ?';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param('ii', $left, $right);
        $stmt->execute();
        $res = $stmt->get_result();
        $continents = [];
        $instruments = [];
        $groups = [];
        $variants = [];
        $primers = [];
        while ($row = $res->fetch_assoc()) {
            if ($row['continent'] !== null && $row['continent'] !== '') {
                $continents[$row['continent']] = true;
            }
            if ($row['instrument'] !== null && $row['instrument'] !== '') {
                $instruments[$row['instrument']] = true;
            }
            if ($row['group_name'] !== null && $row['group_name'] !== '' && $row['group_name'] !== $row['continent']) {
                $groups[$row['group_name']] = true;
            }
            if ($row['variant_code'] !== null && $row['variant_code'] !== '') {
                $variants[$row['variant_code']] = $row['variant_label'];
            }
            if ($row['primer'] !== null && $row['primer'] !== '') {
                $primers[$row['primer']] = true;
            }
        }
        $stmt->close();
        ksort($continents);
        ksort($instruments);
        ksort($groups);
        ksort($primers);
        $variantList = [];
        foreach ($variants as $code => $label) {
            $variantList[] = ['code' => $code, 'label' => $label];
        }
        usort($variantList, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return [
            'continents' => array_keys($continents),
            'instruments' => array_keys($instruments),
            'variants' => $variantList,
            'primers' => array_keys($primers),
            'groups' => array_keys($groups),
        ];
    }
}

if (!function_exists('jq_fetch_group_directory')) {
    /**
     * Unique groups for the selectable group-information table (not continent rollups).
     *
     * @return list<array{name:string,location_name:?string,continent:?string,instrument:?string}>
     */
    function jq_fetch_group_directory(mysqli $con, $left, $right)
    {
        $out = [];
        $sql = 'SELECT g.name, MAX(g.location_name) AS location_name, MAX(g.continent) AS continent, MAX(g.instrument) AS instrument
                FROM junction_query_group g
                INNER JOIN junction_query_dataset d ON d.id = g.dataset_id
                WHERE d.junction_left = ? AND d.junction_right = ?
                  AND (g.continent IS NULL OR g.name <> g.continent)
                GROUP BY g.name
                ORDER BY g.name ASC';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return $out;
        }
        $left = (int) $left;
        $right = (int) $right;
        $stmt->bind_param('ii', $left, $right);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $stmt->close();

        return $out;
    }
}

if (!function_exists('jq_fetch_measures')) {
    /**
     * @param array{left:int,right:int,dataset_id:int,continents:list<string>,instruments:list<string>,groups:list<string>,variants:list<string>,primers:list<string>} $filters
     * @return list<array<string,mixed>>
     */
    function jq_fetch_measures(mysqli $con, array $filters)
    {
        $sql = 'SELECT m.id, m.dataset_id, m.variant_code, m.variant_label, m.primer, m.source_header, m.pct,
                       g.id AS group_id, g.name AS group_name, g.location_name, g.continent, g.instrument
                FROM junction_query_measure m
                INNER JOIN junction_query_group g ON g.id = m.group_id
                INNER JOIN junction_query_dataset d ON d.id = m.dataset_id
                WHERE d.junction_left = ? AND d.junction_right = ? AND m.dataset_id = ?';
        $params = [(int) $filters['left'], (int) $filters['right'], (int) $filters['dataset_id']];
        $types = 'iii';

        if (!empty($filters['continents'])) {
            $sql .= ' AND g.continent IN (' . jq_bind_in($filters['continents'], $params, $types) . ')';
        }
        if (!empty($filters['instruments'])) {
            $sql .= ' AND g.instrument IN (' . jq_bind_in($filters['instruments'], $params, $types) . ')';
        }
        if (!empty($filters['groups'])) {
            $sql .= ' AND g.name IN (' . jq_bind_in($filters['groups'], $params, $types) . ')';
        }
        if (!empty($filters['variants'])) {
            $sql .= ' AND m.variant_code IN (' . jq_bind_in($filters['variants'], $params, $types) . ')';
        }
        if (!empty($filters['primers'])) {
            $sql .= ' AND m.primer IN (' . jq_bind_in($filters['primers'], $params, $types) . ')';
        }

        $sql .= ' ORDER BY g.name ASC, m.primer ASC, m.variant_label ASC';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $row['pct'] = (float) $row['pct'];
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }
}

if (!function_exists('jq_groups_with_samples')) {
    /**
     * Group names that have pct > -1 for every selected variant (Jim: % Delta > -1 AND % BA.1 > -1).
     *
     * @param list<array<string,mixed>> $rows
     * @param list<string> $variantCodes
     * @return list<string>
     */
    function jq_groups_with_samples(array $rows, array $variantCodes)
    {
        if (!$variantCodes) {
            $names = [];
            foreach ($rows as $r) {
                $names[$r['group_name']] = true;
            }

            return array_keys($names);
        }
        $ok = [];
        foreach ($rows as $r) {
            $g = $r['group_name'];
            $v = $r['variant_code'];
            if (!isset($ok[$g])) {
                $ok[$g] = [];
            }
            if ((float) $r['pct'] > -1) {
                $ok[$g][$v] = true;
            }
        }
        $keep = [];
        foreach ($ok as $g => $found) {
            $all = true;
            foreach ($variantCodes as $code) {
                if (empty($found[$code])) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                $keep[] = $g;
            }
        }

        return $keep;
    }
}

if (!function_exists('jq_apply_require_samples')) {
    /**
     * @param list<array<string,mixed>> $rows
     * @param list<string> $variantCodes
     * @return list<array<string,mixed>>
     */
    function jq_apply_require_samples(array $rows, array $variantCodes)
    {
        if (!$variantCodes) {
            return $rows;
        }
        $keep = array_flip(jq_groups_with_samples($rows, $variantCodes));
        $out = [];
        foreach ($rows as $r) {
            if (isset($keep[$r['group_name']])) {
                $out[] = $r;
            }
        }

        return $out;
    }
}

if (!function_exists('jq_pivot_table')) {
    /**
     * @param list<array<string,mixed>> $rows
     * @return array{groups:list<string>,columns:list<array{key:string,label:string}>,cells:array<string,array<string,float>>,meta:array<string,array<string,mixed>>}
     */
    function jq_pivot_table(array $rows)
    {
        $groups = [];
        $columns = [];
        $cells = [];
        $meta = [];
        foreach ($rows as $r) {
            $g = $r['group_name'];
            $colKey = $r['primer'] ? ($r['variant_label'] . '_' . $r['primer']) : $r['variant_label'];
            $groups[$g] = true;
            if (!isset($columns[$colKey])) {
                $columns[$colKey] = ['key' => $colKey, 'label' => $colKey];
            }
            if (!isset($cells[$g])) {
                $cells[$g] = [];
            }
            $cells[$g][$colKey] = (float) $r['pct'];
            $meta[$g] = [
                'location_name' => $r['location_name'],
                'continent' => $r['continent'],
                'instrument' => $r['instrument'],
            ];
        }

        return [
            'groups' => array_keys($groups),
            'columns' => array_values($columns),
            'cells' => $cells,
            'meta' => $meta,
        ];
    }
}

if (!function_exists('jq_chart_series')) {
    /**
     * CanvasJS series: one series per column, points per group. Omits -1 (no samples).
     *
     * @param array{groups:list<string>,columns:list<array{key:string,label:string}>,cells:array<string,array<string,float>>} $pivot
     * @param bool $normalize divide each value by number of groups (stacked-norm)
     * @return list<array{name:string,dataPoints:list<array{label:string,y:float}>}>
     */
    function jq_chart_series(array $pivot, $normalize = false)
    {
        $n = max(1, count($pivot['groups']));
        $series = [];
        foreach ($pivot['columns'] as $col) {
            $pts = [];
            foreach ($pivot['groups'] as $g) {
                if (!isset($pivot['cells'][$g][$col['key']])) {
                    continue;
                }
                $y = (float) $pivot['cells'][$g][$col['key']];
                if ($y <= -1) {
                    continue;
                }
                if ($normalize) {
                    $y = $y / $n;
                }
                $pts[] = ['label' => $g, 'y' => round($y, 4)];
            }
            $series[] = ['name' => $col['label'], 'dataPoints' => $pts];
        }

        return $series;
    }
}

if (!function_exists('jq_chart_series_by_group')) {
    /**
     * CanvasJS series: one series per group, points per variant (legend = groups).
     *
     * @param array{groups:list<string>,columns:list<array{key:string,label:string}>,cells:array<string,array<string,float>>} $pivot
     */
    function jq_chart_series_by_group(array $pivot, $normalize = false)
    {
        $n = max(1, count($pivot['groups']));
        $series = [];
        foreach ($pivot['groups'] as $g) {
            $pts = [];
            foreach ($pivot['columns'] as $col) {
                if (!isset($pivot['cells'][$g][$col['key']])) {
                    continue;
                }
                $y = (float) $pivot['cells'][$g][$col['key']];
                if ($y <= -1) {
                    continue;
                }
                if ($normalize) {
                    $y = $y / $n;
                }
                $pts[] = ['label' => $col['label'], 'y' => round($y, 4)];
            }
            $series[] = ['name' => $g, 'dataPoints' => $pts];
        }

        return $series;
    }
}

if (!function_exists('jq_parse_all_list')) {
    /**
     * @return array{0:list<string>,1:bool} selected values and whether All is active
     */
    function jq_parse_all_list($listKey, $allKey)
    {
        $sel = jq_get_list($listKey);
        $all = $sel === [] || (isset($_GET[$allKey]) && (string) $_GET[$allKey] === '1');
        if ($all) {
            $sel = [];
        }

        return [$sel, $all];
    }
}

if (!function_exists('jq_viridian_tables_exist')) {
    function jq_viridian_tables_exist(mysqli $con)
    {
        $sql = "SELECT COUNT(*) AS c FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('junction_viridian_group','junction_viridian_variant','junction_viridian_pair')";
        $res = $con->query($sql);
        if (!$res) {
            return false;
        }
        $row = $res->fetch_assoc();
        $res->free();

        return isset($row['c']) && (int) $row['c'] === 3;
    }
}

if (!function_exists('jq_fetch_viridian_groups')) {
    /**
     * @return list<array<string,mixed>>
     */
    function jq_fetch_viridian_groups(mysqli $con)
    {
        $out = [];
        $sql = 'SELECT id, code, name, location_name, continent, instrument, source_file
                FROM junction_viridian_group ORDER BY id ASC';
        if (!($res = $con->query($sql))) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('jq_fetch_viridian_variants')) {
    /**
     * @return list<array<string,mixed>>
     */
    function jq_fetch_viridian_variants(mysqli $con)
    {
        $out = [];
        $sql = 'SELECT v.group_id, g.code, g.name AS group_name, v.variant_name, v.sample_count, v.is_rollup
                FROM junction_viridian_variant v
                INNER JOIN junction_viridian_group g ON g.id = v.group_id
                ORDER BY v.is_rollup DESC, v.variant_name ASC, g.id ASC';
        if (!($res = $con->query($sql))) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('jq_fetch_viridian_pairs')) {
    /**
     * @return list<array<string,mixed>>
     */
    function jq_fetch_viridian_pairs(mysqli $con)
    {
        $out = [];
        $sql = 'SELECT p.group_id, g.code, g.name AS group_name, p.variant_name, p.primer_name, p.sample_count
                FROM junction_viridian_pair p
                INNER JOIN junction_viridian_group g ON g.id = p.group_id
                ORDER BY p.variant_name ASC, p.primer_name ASC, g.id ASC';
        if (!($res = $con->query($sql))) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('jq_pivot_viridian')) {
    /**
     * Rows = variant (or variant|primer), columns = groups, cells = counts.
     *
     * @param list<array<string,mixed>> $rows
     * @param list<array<string,mixed>> $groups
     * @return array{row_keys:list<string>,row_labels:array<string,string>,groups:list<array<string,mixed>>,cells:array<string,array<string,int>>,rollups:array<string,bool>}
     */
    function jq_pivot_viridian(array $rows, array $groups, $pairMode = false)
    {
        $rowKeys = [];
        $rowLabels = [];
        $rollups = [];
        $cells = [];
        foreach ($rows as $r) {
            if ($pairMode) {
                $key = $r['variant_name'] . "\t" . $r['primer_name'];
                $label = $r['variant_name'] . ' — ' . $r['primer_name'];
            } else {
                $key = $r['variant_name'];
                $label = $r['variant_name'];
                if (!empty($r['is_rollup'])) {
                    $rollups[$key] = true;
                }
            }
            $rowKeys[$key] = true;
            $rowLabels[$key] = $label;
            $gcode = $r['code'];
            if (!isset($cells[$key])) {
                $cells[$key] = [];
            }
            $cells[$key][$gcode] = (int) $r['sample_count'];
        }

        return [
            'row_keys' => array_keys($rowKeys),
            'row_labels' => $rowLabels,
            'groups' => $groups,
            'cells' => $cells,
            'rollups' => $rollups,
        ];
    }
}

if (!function_exists('jq_format_pct')) {
    function jq_format_pct($value)
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $n = (float) $value;
        if ($n <= -1) {
            return '—';
        }

        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
