<?php
/**
 * NJ percent-of-samples table for a VCF group.
 * Denominator is analyzed samples: VCF ∩ PASS ∩ deletion sample list,
 * and the LA SRA allowlist when that group has one.
 * Size 28190 is the N-gene sgRNA filter, not a table row.
 */

require_once __DIR__ . '/vcf_snv_helpers.php';

if (!function_exists('nj_read_tables_exist')) {
    function nj_read_tables_exist(mysqli $con)
    {
        return snv_pango_table_named_exists($con, 'vcf_snv_nj_read')
            && snv_pango_table_named_exists($con, 'vcf_snv_nj_catalog');
    }
}

if (!function_exists('nj_read_many_project_file')) {
    /**
     * Accession list for a combined group. Null when the project cell is one accession.
     */
    function nj_read_many_project_file($code)
    {
        $files = [
            'Thailand_mix' => 'Thailand_groups_used.csv',
            'Est-mix' => 'Estonia_groups_used.csv',
        ];
        $code = (string) $code;

        return isset($files[$code]) ? $files[$code] : null;
    }
}

if (!function_exists('nj_read_request_min_reads')) {
    function nj_read_request_min_reads()
    {
        $n = 1;
        if (isset($_GET['MinReads']) && $_GET['MinReads'] !== '' && is_numeric($_GET['MinReads'])) {
            $n = (int) $_GET['MinReads'];
        }
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 1000000) {
            $n = 1000000;
        }

        return $n;
    }
}

if (!function_exists('nj_read_request_ngene')) {
    function nj_read_request_ngene()
    {
        if (!isset($_GET['Ngene'])) {
            return true;
        }
        $raw = $_GET['Ngene'];
        if (is_array($raw)) {
            $raw = end($raw);
        }

        return (string) $raw !== '0';
    }
}

if (!function_exists('nj_read_request_label')) {
    function nj_read_request_label($key)
    {
        if (!isset($_GET[$key])) {
            return '';
        }
        $text = trim((string) $_GET[$key]);
        if ($text === '' || strcasecmp($text, 'all') === 0) {
            return '';
        }
        if (strlen($text) > 128) {
            $text = substr($text, 0, 128);
        }

        return $text;
    }
}

if (!function_exists('nj_read_label_choices')) {
    function nj_read_label_choices(mysqli $con, $groupId, $column)
    {
        if ($column !== 'variant_label' && $column !== 'primer_label') {
            return [];
        }
        if (!snv_pango_table_named_exists($con, 'vcf_snv_sample_meta')) {
            return [];
        }
        $groupId = (int) $groupId;
        $sql = 'SELECT DISTINCT ' . $column . ' AS label
                  FROM vcf_snv_sample_meta
                 WHERE group_id = ? AND ' . $column . " <> ''
              ORDER BY label";
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $res = $stmt->get_result();
        $labels = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $labels[] = (string) $row['label'];
            }
        }
        $stmt->close();

        return $labels;
    }
}

if (!function_exists('nj_read_percent')) {
    function nj_read_percent($containing, $denominator)
    {
        $denominator = (int) $denominator;
        if ($denominator < 1) {
            return null;
        }

        return round(100 * ((int) $containing) / $denominator, 2);
    }
}

if (!function_exists('nj_read_ngene_available')) {
    function nj_read_ngene_available(mysqli $con, $groupId)
    {
        if (!nj_read_tables_exist($con)) {
            return false;
        }
        $groupId = (int) $groupId;
        $stmt = $con->prepare(
            'SELECT 1 FROM vcf_snv_nj_read WHERE group_id = ? AND nj_size = 28190 LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = $res && $res->fetch_row();
        $stmt->close();

        return (bool) $ok;
    }
}

if (!function_exists('nj_read_analyzed_count')) {
    /**
     * @param bool $useNgene
     */
    function nj_read_analyzed_count(mysqli $con, $groupId, $minReads, $useNgene)
    {
        $groupId = (int) $groupId;
        $minReads = (int) $minReads;
        $variant = func_num_args() >= 5 ? (string) func_get_arg(4) : '';
        $primer = func_num_args() >= 6 ? (string) func_get_arg(5) : '';
        $sql = 'SELECT COUNT(*) AS n
                  FROM vcf_snv_sample s
                  INNER JOIN vcf_snv_sample_eligibility e
                    ON e.sample_id = s.id AND e.eligible = 1
                 WHERE s.group_id = ?';
        $types = 'i';
        $values = [$groupId];
        if ($useNgene) {
            $sql .= ' AND s.sample_name IN (
                        SELECT sample_name FROM vcf_snv_nj_read
                         WHERE group_id = ? AND nj_size = 28190 AND coord_tag = \'\' AND read_count >= ?
                      )';
            $types .= 'ii';
            $values[] = $groupId;
            $values[] = $minReads;
        }
        if ($variant !== '') {
            $sql .= ' AND s.sample_name IN (
                        SELECT sample_name FROM vcf_snv_sample_meta
                         WHERE group_id = ? AND variant_label = ?
                      )';
            $types .= 'is';
            $values[] = $groupId;
            $values[] = $variant;
        }
        if ($primer !== '') {
            $sql .= ' AND s.sample_name IN (
                        SELECT sample_name FROM vcf_snv_sample_meta
                         WHERE group_id = ? AND primer_label = ?
                      )';
            $types .= 'is';
            $values[] = $groupId;
            $values[] = $primer;
        }
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ? (int) $row['n'] : 0;
    }
}

if (!function_exists('nj_read_table_rows')) {
    /**
     * @return list<array{nj_size:int,nj_start:?int,nj_end:?int,n_containing:int,percent:?float}>
     */
    function nj_read_table_rows(mysqli $con, $groupId, $minReads, $useNgene)
    {
        $groupId = (int) $groupId;
        $minReads = (int) $minReads;
        $variant = func_num_args() >= 5 ? (string) func_get_arg(4) : '';
        $primer = func_num_args() >= 6 ? (string) func_get_arg(5) : '';
        $sampleSql = 'SELECT s.sample_name
                        FROM vcf_snv_sample s
                        INNER JOIN vcf_snv_sample_eligibility e
                          ON e.sample_id = s.id AND e.eligible = 1
                       WHERE s.group_id = ?';
        $types = 'iii';
        $values = [$minReads, $groupId, $groupId];
        if ($useNgene) {
            $sampleSql .= ' AND s.sample_name IN (
                SELECT sample_name FROM vcf_snv_nj_read
                 WHERE group_id = ? AND nj_size = 28190 AND coord_tag = \'\' AND read_count >= ?
            )';
            $types .= 'ii';
            $values[] = $groupId;
            $values[] = $minReads;
        }
        if ($variant !== '') {
            $sampleSql .= ' AND s.sample_name IN (
                SELECT sample_name FROM vcf_snv_sample_meta
                 WHERE group_id = ? AND variant_label = ?
            )';
            $types .= 'is';
            $values[] = $groupId;
            $values[] = $variant;
        }
        if ($primer !== '') {
            $sampleSql .= ' AND s.sample_name IN (
                SELECT sample_name FROM vcf_snv_sample_meta
                 WHERE group_id = ? AND primer_label = ?
            )';
            $types .= 'is';
            $values[] = $groupId;
            $values[] = $primer;
        }
        $sql = 'SELECT cat.nj_size, cat.coord_tag, cat.nj_start, cat.nj_end,
                       COALESCE(SUM(r.read_count >= ?), 0) AS n_containing
                  FROM vcf_snv_nj_catalog cat
                  LEFT JOIN vcf_snv_nj_read r
                    ON r.group_id = ? AND r.nj_size = cat.nj_size AND r.coord_tag = cat.coord_tag
                   AND r.sample_name IN (' . $sampleSql . ')
                 WHERE cat.kind = \'nj\'
              GROUP BY cat.nj_size, cat.coord_tag, cat.nj_start, cat.nj_end
              ORDER BY cat.nj_size, cat.nj_start';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = [
                    'nj_size' => (int) $row['nj_size'],
                    'nj_start' => $row['nj_start'] === null ? null : (int) $row['nj_start'],
                    'nj_end' => $row['nj_end'] === null ? null : (int) $row['nj_end'],
                    'n_containing' => (int) $row['n_containing'],
                ];
            }
        }
        $stmt->close();

        return $rows;
    }
}

if (!function_exists('nj_read_variant_is_hidden')) {
    function nj_read_variant_is_hidden($label)
    {
        $label = (string) $label;

        return stripos($label, 'Unassigned') !== false || stripos($label, 'Probable') !== false;
    }
}

if (!function_exists('nj_read_variant_key')) {
    /**
     * Null drops the label from variant and variant–primer columns.
     */
    function nj_read_variant_key($label, $mergeDelta)
    {
        $opt = nj_read_coerce_merge($mergeDelta);
        $bucket = nj_read_variant_bucket($label);
        if ($bucket === null) {
            return null;
        }
        if (!empty($opt['hide_ba1']) && $bucket === 'ba1') {
            return null;
        }
        if (!empty($opt['major_only']) && !nj_read_bucket_is_major($bucket)) {
            return null;
        }
        if ($bucket === 'delta' && !empty($opt['merge_delta'])) {
            return 'Delta';
        }
        if (!empty($opt['merge_omicron']) && nj_read_bucket_is_merged_omicron($bucket)) {
            return 'Omicron';
        }
        if (!empty($opt['merge_ba']) && $bucket === 'ba') {
            return 'Omicron BA';
        }
        if (!empty($opt['merge_xbb']) && $bucket === 'xbb') {
            return 'XBB';
        }
        if ($bucket === 'ba1') {
            return trim((string) $label);
        }

        return trim((string) $label);
    }
}

if (!function_exists('nj_read_variant_display')) {
    function nj_read_variant_display($label)
    {
        return (string) $label === '.' ? 'No variant call (.)' : (string) $label;
    }
}

if (!function_exists('nj_read_primer_display')) {
    function nj_read_primer_display($label)
    {
        return (string) $label === '.' ? 'No primer call (.)' : (string) $label;
    }
}

if (!function_exists('nj_read_request_group_codes')) {
    /**
     * @param list<array{code:string}> $groups
     * @return list<string>
     */
    function nj_read_request_group_codes(array $groups)
    {
        $allowed = [];
        foreach ($groups as $group) {
            $allowed[(string) $group['code']] = true;
        }
        $raw = [];
        if (isset($_GET['NjGroup'])) {
            $raw = is_array($_GET['NjGroup']) ? $_GET['NjGroup'] : [$_GET['NjGroup']];
        } elseif (isset($_GET['Group']) && (string) $_GET['Group'] !== '') {
            $raw = [$_GET['Group']];
        }
        $picked = [];
        foreach ($raw as $code) {
            $code = trim((string) $code);
            if ($code !== '' && isset($allowed[$code]) && !in_array($code, $picked, true)) {
                $picked[] = $code;
            }
        }
        if ($picked) {
            return $picked;
        }
        if (isset($allowed['NJ-PRJNA708324'])) {
            return ['NJ-PRJNA708324'];
        }
        $codes = array_keys($allowed);

        return $codes ? [$codes[0]] : [];
    }
}

if (!function_exists('nj_read_request_breakdown_by')) {
    function nj_read_request_breakdown_by()
    {
        $by = isset($_GET['NjBy']) ? (string) $_GET['NjBy'] : 'pair';
        if (!in_array($by, ['variant', 'primer', 'pair'], true)) {
            return 'pair';
        }

        return $by;
    }
}

if (!function_exists('nj_read_request_merge_delta')) {
    function nj_read_request_merge_delta()
    {
        if (!isset($_GET['MergeDelta'])) {
            return false;
        }
        $raw = $_GET['MergeDelta'];
        if (is_array($raw)) {
            $raw = end($raw);
        }

        return (string) $raw === '1';
    }
}

if (!function_exists('nj_read_request_pick')) {
    /**
     * @return array{nj_size:int,coord_tag:string}|null
     */
    function nj_read_request_pick()
    {
        if (!isset($_GET['NjPick'])) {
            return null;
        }
        $raw = $_GET['NjPick'];
        if (is_array($raw)) {
            $raw = end($raw);
        }
        $raw = (string) $raw;
        $bar = strpos($raw, '|');
        if ($bar === false) {
            return null;
        }
        $size = substr($raw, 0, $bar);
        $tag = substr($raw, $bar + 1);
        if ($size === '' || !ctype_digit($size) || strlen($tag) > 16 || !preg_match('/^[0-9]*$/', $tag)) {
            return null;
        }

        return ['nj_size' => (int) $size, 'coord_tag' => $tag];
    }
}

if (!function_exists('nj_read_pick_value')) {
    function nj_read_pick_value($njSize, $coordTag)
    {
        return (int) $njSize . '|' . (string) $coordTag;
    }
}

if (!function_exists('nj_read_breakdown_choices')) {
    /**
     * @param list<array{variant:string,primer:string}> $pairs
     * @return list<array{key:string,label:string}>
     */
    function nj_read_breakdown_choices(array $pairs, $by, $mergeDelta)
    {
        $seen = [];
        foreach ($pairs as $pair) {
            $variant = isset($pair['variant']) ? (string) $pair['variant'] : '';
            $primer = trim(isset($pair['primer']) ? (string) $pair['primer'] : '');
            if ($by === 'primer') {
                if ($primer === '') {
                    continue;
                }
                $seen[$primer] = nj_read_primer_display($primer);
                continue;
            }
            $variantKey = nj_read_variant_key($variant, $mergeDelta);
            if ($variantKey === null) {
                continue;
            }
            if ($by === 'pair') {
                if ($primer === '') {
                    continue;
                }
                $key = $variantKey . '|' . $primer;
                $seen[$key] = nj_read_variant_display($variantKey) . '_' . nj_read_primer_display($primer);
                continue;
            }
            $seen[$variantKey] = nj_read_variant_display($variantKey);
        }
        $out = [];
        foreach ($seen as $key => $label) {
            $out[] = ['key' => (string) $key, 'label' => (string) $label];
        }
        usort($out, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return $out;
    }
}

if (!function_exists('nj_read_request_columns')) {
    /**
     * @param list<array{key:string,label:string}> $choices
     * @return list<string>
     */
    function nj_read_request_columns(array $choices)
    {
        $allowed = [];
        foreach ($choices as $choice) {
            $allowed[$choice['key']] = true;
        }
        $raw = isset($_GET['NjCol']) ? $_GET['NjCol'] : [];
        if (!is_array($raw)) {
            $raw = [$raw];
        }
        $picked = [];
        foreach ($raw as $key) {
            $key = (string) $key;
            if (isset($allowed[$key]) && !in_array($key, $picked, true)) {
                $picked[] = $key;
            }
        }
        return $picked;
    }
}

if (!function_exists('nj_read_column_key')) {
    function nj_read_column_key($variantLabel, $primerLabel, $by, $mergeDelta)
    {
        $primer = trim((string) $primerLabel);
        if ($by === 'primer') {
            return $primer === '' ? null : $primer;
        }
        $variant = nj_read_variant_key($variantLabel, $mergeDelta);
        if ($variant === null) {
            return null;
        }
        if ($by === 'pair') {
            return $primer === '' ? null : ($variant . '|' . $primer);
        }

        return $variant;
    }
}

if (!function_exists('nj_read_fold_breakdown')) {
    /**
     * @param list<array{group_id:int,variant_label:string,primer_label:string,n_denom:int,n_num:int}> $rawRows
     * @param list<string> $columnKeys
     * @return array<string,array{n:int,d:int}>
     */
    function nj_read_fold_breakdown(array $rawRows, array $columnKeys, $by, $mergeDelta)
    {
        $opt = nj_read_coerce_merge($mergeDelta);
        $minSamples = (int) $opt['min_samples'];
        $showBelow = !empty($opt['show_below']);
        $want = array_fill_keys($columnKeys, true);
        $cells = [];
        foreach ($rawRows as $row) {
            $col = nj_read_column_key($row['variant_label'], $row['primer_label'], $by, $mergeDelta);
            if ($col === null || !isset($want[$col])) {
                continue;
            }
            if (!$showBelow && $minSamples > 0 && (int) $row['n_denom'] < $minSamples) {
                continue;
            }
            $key = (int) $row['group_id'] . "\t" . $col;
            if (!isset($cells[$key])) {
                $cells[$key] = ['n' => 0, 'd' => 0];
            }
            $cells[$key]['n'] += (int) $row['n_num'];
            $cells[$key]['d'] += (int) $row['n_denom'];
        }

        return $cells;
    }
}

if (!function_exists('nj_read_breakdown_display')) {
    function nj_read_breakdown_display($numerator, $denominator, $minSamples = 0, $showBelow = false)
    {
        $denominator = (int) $denominator;
        $minSamples = (int) $minSamples;
        if ($denominator < 1) {
            return '-1';
        }
        if (!$showBelow && $minSamples > 0 && $denominator < $minSamples) {
            return '-1';
        }
        $pct = nj_read_percent($numerator, $denominator);
        if ($pct === null) {
            return '-1';
        }

        return number_format($pct, 2, '.', '');
    }
}

if (!function_exists('nj_read_in_clause')) {
    /**
     * @param list<int> $ids
     */
    function nj_read_in_clause(array $ids, &$types, array &$values)
    {
        $slots = [];
        foreach ($ids as $id) {
            $slots[] = '?';
            $types .= 'i';
            $values[] = (int) $id;
        }

        return $slots ? implode(',', $slots) : 'NULL';
    }
}

if (!function_exists('nj_read_label_pairs')) {
    /**
     * @param list<int> $groupIds
     * @return list<array{variant:string,primer:string}>
     */
    function nj_read_label_pairs(mysqli $con, array $groupIds)
    {
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));
        if (!$groupIds || !snv_pango_table_named_exists($con, 'vcf_snv_sample_meta')) {
            return [];
        }
        $types = '';
        $values = [];
        $in = nj_read_in_clause($groupIds, $types, $values);
        $sql = 'SELECT DISTINCT variant_label, primer_label
                  FROM vcf_snv_sample_meta
                 WHERE group_id IN (' . $in . ')';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        $pairs = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $pairs[] = [
                    'variant' => (string) $row['variant_label'],
                    'primer' => (string) $row['primer_label'],
                ];
            }
        }
        $stmt->close();

        return $pairs;
    }
}

if (!function_exists('nj_read_catalog_rows')) {
    /**
     * @return list<array{nj_size:int,coord_tag:string,nj_start:?int,nj_end:?int}>
     */
    function nj_read_catalog_rows(mysqli $con)
    {
        if (!nj_read_tables_exist($con)) {
            return [];
        }
        $res = $con->query(
            "SELECT nj_size, coord_tag, nj_start, nj_end
               FROM vcf_snv_nj_catalog
              WHERE kind = 'nj'
           ORDER BY nj_size, nj_start"
        );
        if (!$res) {
            return [];
        }
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                'nj_size' => (int) $row['nj_size'],
                'coord_tag' => (string) $row['coord_tag'],
                'nj_start' => $row['nj_start'] === null ? null : (int) $row['nj_start'],
                'nj_end' => $row['nj_end'] === null ? null : (int) $row['nj_end'],
            ];
        }
        $res->free();

        return $rows;
    }
}

if (!function_exists('nj_read_ngene_group_ids')) {
    /**
     * @param list<int> $groupIds
     * @return list<int>
     */
    function nj_read_ngene_group_ids(mysqli $con, array $groupIds)
    {
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));
        if (!$groupIds || !nj_read_tables_exist($con)) {
            return [];
        }
        $types = '';
        $values = [];
        $in = nj_read_in_clause($groupIds, $types, $values);
        $sql = 'SELECT DISTINCT group_id
                  FROM vcf_snv_nj_read
                 WHERE nj_size = 28190 AND coord_tag = \'\' AND group_id IN (' . $in . ')';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $ids[] = (int) $row['group_id'];
            }
        }
        $stmt->close();

        return $ids;
    }
}

if (!function_exists('nj_read_breakdown_raw')) {
    /**
     * @param list<int> $groupIds
     * @param list<int> $ngeneGroupIds
     * @return list<array{group_id:int,variant_label:string,primer_label:string,n_denom:int,n_num:int}>
     */
    function nj_read_breakdown_raw(mysqli $con, array $groupIds, $njSize, $coordTag, $minReads, array $ngeneGroupIds)
    {
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));
        if (!$groupIds || !nj_read_tables_exist($con)) {
            return [];
        }
        $minReads = (int) $minReads;
        $types = 'isi';
        $values = [(int) $njSize, (string) $coordTag, $minReads];
        $inTypes = '';
        $inValues = [];
        $in = nj_read_in_clause($groupIds, $inTypes, $inValues);
        $sql = 'SELECT s.group_id, m.variant_label, m.primer_label,
                       COUNT(*) AS n_denom,
                       SUM(r.sample_name IS NOT NULL) AS n_num
                  FROM vcf_snv_sample s
                  INNER JOIN vcf_snv_sample_eligibility e
                    ON e.sample_id = s.id AND e.eligible = 1
                  INNER JOIN vcf_snv_sample_meta m ON m.sample_id = s.id
                  LEFT JOIN vcf_snv_nj_read r
                    ON r.group_id = s.group_id
                   AND r.sample_name = s.sample_name
                   AND r.nj_size = ?
                   AND r.coord_tag = ?
                   AND r.read_count >= ?
                 WHERE s.group_id IN (' . $in . ')';
        $types .= $inTypes;
        foreach ($inValues as $id) {
            $values[] = $id;
        }
        $ngeneGroupIds = array_values(array_intersect(array_map('intval', $ngeneGroupIds), $groupIds));
        if ($ngeneGroupIds) {
            $exists = 'EXISTS (
                        SELECT 1 FROM vcf_snv_nj_read ng
                         WHERE ng.group_id = s.group_id
                           AND ng.sample_name = s.sample_name
                           AND ng.nj_size = 28190
                           AND ng.coord_tag = \'\'
                           AND ng.read_count >= ?
                      )';
            if (count($ngeneGroupIds) === count($groupIds)) {
                $sql .= ' AND ' . $exists;
                $types .= 'i';
                $values[] = $minReads;
            } else {
                $ngTypes = '';
                $ngValues = [];
                $ngIn = nj_read_in_clause($ngeneGroupIds, $ngTypes, $ngValues);
                $sql .= ' AND (s.group_id NOT IN (' . $ngIn . ') OR ' . $exists . ')';
                $types .= $ngTypes . 'i';
                foreach ($ngValues as $id) {
                    $values[] = $id;
                }
                $values[] = $minReads;
            }
        }
        $sql .= ' GROUP BY s.group_id, m.variant_label, m.primer_label';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = [
                    'group_id' => (int) $row['group_id'],
                    'variant_label' => (string) $row['variant_label'],
                    'primer_label' => (string) $row['primer_label'],
                    'n_denom' => (int) $row['n_denom'],
                    'n_num' => (int) $row['n_num'],
                ];
            }
        }
        $stmt->close();

        return $rows;
    }
}

require_once __DIR__ . '/nj_merge_rules.php';
