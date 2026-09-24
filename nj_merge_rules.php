<?php
/**
 * Junction-group merge, minimum-sample, and summary rules.
 * Loaded by nj_read_helpers.php.
 */

if (!function_exists('nj_read_coerce_merge')) {
    /**
     * A boolean is the older Merge Delta flag and does not hide other variants.
     *
     * @param bool|array<string,mixed> $merge
     * @return array{merge_delta:bool,merge_omicron:bool,merge_ba:bool,merge_xbb:bool,hide_ba1:bool,major_only:bool,min_samples:int,show_below:bool}
     */
    function nj_read_coerce_merge($merge)
    {
        $opt = [
            'merge_delta' => false,
            'merge_omicron' => false,
            'merge_ba' => false,
            'merge_xbb' => false,
            'hide_ba1' => false,
            'major_only' => false,
            'min_samples' => 0,
            'show_below' => false,
        ];
        if (is_array($merge)) {
            foreach ($opt as $key => $value) {
                if (array_key_exists($key, $merge)) {
                    $opt[$key] = $merge[$key];
                }
            }
        } else {
            $opt['merge_delta'] = (bool) $merge;
        }
        if (!empty($opt['merge_omicron'])) {
            $opt['merge_ba'] = false;
            $opt['merge_xbb'] = false;
        }
        $opt['merge_delta'] = !empty($opt['merge_delta']);
        $opt['merge_omicron'] = !empty($opt['merge_omicron']);
        $opt['merge_ba'] = !empty($opt['merge_ba']);
        $opt['merge_xbb'] = !empty($opt['merge_xbb']);
        $opt['hide_ba1'] = !empty($opt['hide_ba1']);
        $opt['major_only'] = !empty($opt['major_only']);
        $opt['show_below'] = !empty($opt['show_below']);
        $opt['min_samples'] = max(0, (int) $opt['min_samples']);

        return $opt;
    }
}

if (!function_exists('nj_read_request_flag')) {
    function nj_read_request_flag($name, $defaultWhenAbsent)
    {
        if (!isset($_GET[$name])) {
            return (bool) $defaultWhenAbsent;
        }
        $raw = $_GET[$name];
        if (is_array($raw)) {
            $raw = end($raw);
        }

        return (string) $raw === '1';
    }
}

if (!function_exists('nj_read_request_min_samples')) {
    function nj_read_request_min_samples()
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

if (!function_exists('nj_read_request_merge_options')) {
    /**
     * @return array{merge_delta:bool,merge_omicron:bool,merge_ba:bool,merge_xbb:bool,hide_ba1:bool,major_only:bool,min_samples:int,show_below:bool}
     */
    function nj_read_request_merge_options()
    {
        return nj_read_coerce_merge([
            'merge_delta' => nj_read_request_merge_delta(),
            'merge_omicron' => nj_read_request_flag('MergeOmicron', false),
            'merge_ba' => nj_read_request_flag('MergeBa', false),
            'merge_xbb' => nj_read_request_flag('MergeXbb', false),
            'hide_ba1' => nj_read_request_flag('HideBa1', false),
            'major_only' => nj_read_request_flag('Major', true),
            'min_samples' => nj_read_request_min_samples(),
            'show_below' => nj_read_request_flag('ShowBelow', false),
        ]);
    }
}

if (!function_exists('nj_read_request_keep')) {
    /**
     * @return list<string>
     */
    function nj_read_request_keep()
    {
        $raw = isset($_GET['NjKeep']) ? $_GET['NjKeep'] : [];
        if (!is_array($raw)) {
            $raw = [$raw];
        }
        $out = [];
        foreach ($raw as $code) {
            $code = trim((string) $code);
            if ($code !== '' && !in_array($code, $out, true)) {
                $out[] = $code;
            }
        }

        return $out;
    }
}

if (!function_exists('nj_read_variant_bucket')) {
    /**
     * Null drops no-call, Unassigned, and Probable labels.
     */
    function nj_read_variant_bucket($label)
    {
        $label = trim((string) $label);
        if ($label === '' || $label === '.' || nj_read_variant_is_hidden($label)) {
            return null;
        }
        $omicron = stripos($label, 'Omicron') !== false;
        if (stripos($label, 'Delta') !== false) {
            return 'delta';
        }
        if ($omicron && preg_match('/BA\.1(?!\d)/i', $label)) {
            return 'ba1';
        }
        if ($omicron && stripos($label, 'XBB') !== false) {
            return 'xbb';
        }
        if ($omicron && preg_match('/BA\.\d/i', $label)) {
            return 'ba';
        }
        if ($omicron) {
            return 'omicron_other';
        }
        if (stripos($label, 'Alpha') !== false || preg_match('/B\.1\.1\.7/i', $label)) {
            return 'alpha';
        }

        return 'other';
    }
}

if (!function_exists('nj_read_bucket_is_major')) {
    function nj_read_bucket_is_major($bucket)
    {
        return in_array($bucket, ['alpha', 'delta', 'ba1', 'ba', 'xbb', 'omicron_other'], true);
    }
}

if (!function_exists('nj_read_bucket_is_merged_omicron')) {
    function nj_read_bucket_is_merged_omicron($bucket)
    {
        return in_array($bucket, ['ba', 'xbb', 'omicron_other'], true);
    }
}

if (!function_exists('nj_read_group_type')) {
    /**
     * @param array<string,int> $variantCounts
     */
    function nj_read_group_type(array $variantCounts, $minSamples)
    {
        $minSamples = (int) $minSamples;
        $has = ['alpha' => false, 'delta' => false, 'omicron' => false];
        foreach ($variantCounts as $label => $count) {
            if ((int) $count < $minSamples) {
                continue;
            }
            $bucket = nj_read_variant_bucket($label);
            if ($bucket === 'alpha') {
                $has['alpha'] = true;
            } elseif ($bucket === 'delta') {
                $has['delta'] = true;
            } elseif (nj_read_bucket_is_merged_omicron($bucket)) {
                $has['omicron'] = true;
            }
        }
        if ($has['alpha'] && $has['delta'] && $has['omicron']) {
            return 'LTG';
        }
        if ($has['alpha'] && $has['delta'] && !$has['omicron']) {
            return 'Omni−';
        }
        if ($has['omicron'] && !$has['alpha'] && !$has['delta']) {
            return 'Omni+';
        }

        return '';
    }
}

if (!function_exists('nj_read_primer_abbrev')) {
    function nj_read_primer_abbrev($label)
    {
        $name = strtoupper(trim((string) $label));
        if ($name === '' || $name === '.') {
            return '';
        }
        if (strpos($name, 'MIDNIGHT') !== false) {
            return 'Mid';
        }
        if (strpos($name, 'VARSKIP') !== false) {
            return 'Vsk';
        }
        if (strpos($name, 'AMPLISEQ') !== false) {
            return 'Amp';
        }
        if (strpos($name, 'V4.1') !== false || strpos($name, 'V4-1') !== false || strpos($name, 'V4_1') !== false) {
            return 'V4.1';
        }
        if (strpos($name, 'V5') !== false) {
            return 'V5.0';
        }
        if (strpos($name, 'V3') !== false) {
            return 'V3';
        }

        return (string) $label;
    }
}

if (!function_exists('nj_read_primer_caption')) {
    function nj_read_primer_caption()
    {
        return 'V3 = COVID-ARTIC-V3. V4.1 = COVID-ARTIC-V4.1. V5.0 = COVID-ARTIC-V5.0-5.3.2_400. Mid = COVID-MIDNIGHT-1200. Vsk = COVID-VARSKIP-V1a-2b.';
    }
}

if (!function_exists('nj_read_average_text')) {
    /**
     * @param list<float|null> $percents
     */
    function nj_read_average_text(array $percents)
    {
        $vals = [];
        foreach ($percents as $value) {
            if ($value !== null) {
                $vals[] = (float) $value;
            }
        }
        if (!$vals) {
            return '-1';
        }
        $avg = array_sum($vals) / count($vals);
        if (abs($avg) < 0.0000001) {
            return '0.00';
        }

        return number_format($avg, 2, '.', '');
    }
}

if (!function_exists('nj_read_chart_group_names')) {
    /**
     * Workbook group names that correspond to the selected VCF groups.
     *
     * @param list<string> $codes
     * @return list<string>
     */
    function nj_read_chart_group_names(array $codes)
    {
        $map = [
            'Angola_miseq' => 'Angola MiSeq',
            'PRJEB46220-Argentina' => 'Arg MiSeq',
            'illumina_miseq' => 'UK MiSeq',
            'nextseq_550' => 'UK NextSeq 550',
            'PAK_iseq' => 'Pak iSeq 100',
            'Port-PRJEB47340' => 'Port 550',
            'S_Afr-PRJNA636748' => 'SAfr MiSeq',
            'NJ-PRJNA708324' => 'NJ MiSeq',
        ];
        $out = [];
        foreach ($codes as $code) {
            if (isset($map[$code]) && !in_array($map[$code], $out, true)) {
                $out[] = $map[$code];
            }
        }

        return $out;
    }
}

if (!function_exists('nj_read_ngene_sql')) {
    /**
     * @param list<int> $groupIds
     * @param list<int> $ngeneGroupIds
     */
    function nj_read_ngene_sql(array $groupIds, array $ngeneGroupIds, &$types, array &$values, $minReads)
    {
        $ngeneGroupIds = array_values(array_intersect(array_map('intval', $ngeneGroupIds), array_map('intval', $groupIds)));
        if (!$ngeneGroupIds) {
            return '';
        }
        $exists = 'EXISTS (
                    SELECT 1 FROM vcf_snv_nj_read ng
                     WHERE ng.group_id = s.group_id
                       AND ng.sample_name = s.sample_name
                       AND ng.nj_size = 28190
                       AND ng.coord_tag = \'\'
                       AND ng.read_count >= ?
                  )';
        if (count($ngeneGroupIds) === count($groupIds)) {
            $types .= 'i';
            $values[] = (int) $minReads;

            return ' AND ' . $exists;
        }
        $ngTypes = '';
        $ngValues = [];
        $ngIn = nj_read_in_clause($ngeneGroupIds, $ngTypes, $ngValues);
        $types .= $ngTypes . 'i';
        foreach ($ngValues as $id) {
            $values[] = $id;
        }
        $values[] = (int) $minReads;

        return ' AND (s.group_id NOT IN (' . $ngIn . ') OR ' . $exists . ')';
    }
}

if (!function_exists('nj_read_pair_denoms')) {
    /**
     * Eligible sample counts by group, variant, and primer.
     *
     * @param list<int> $groupIds
     * @param list<int> $ngeneGroupIds
     * @return array<int,array<string,array<string,int>>>
     */
    function nj_read_pair_denoms(mysqli $con, array $groupIds, $minReads, array $ngeneGroupIds)
    {
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));
        if (!$groupIds || !nj_read_tables_exist($con)) {
            return [];
        }
        $types = '';
        $values = [];
        $in = nj_read_in_clause($groupIds, $types, $values);
        $sql = 'SELECT s.group_id, m.variant_label, m.primer_label, COUNT(*) AS n_denom
                  FROM vcf_snv_sample s
                  INNER JOIN vcf_snv_sample_eligibility e
                    ON e.sample_id = s.id AND e.eligible = 1
                  INNER JOIN vcf_snv_sample_meta m ON m.sample_id = s.id
                 WHERE s.group_id IN (' . $in . ')';
        $sql .= nj_read_ngene_sql($groupIds, $ngeneGroupIds, $types, $values, $minReads);
        $sql .= ' GROUP BY s.group_id, m.variant_label, m.primer_label';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $gid = (int) $row['group_id'];
                $variant = (string) $row['variant_label'];
                $primer = (string) $row['primer_label'];
                if (!isset($out[$gid])) {
                    $out[$gid] = [];
                }
                if (!isset($out[$gid][$variant])) {
                    $out[$gid][$variant] = [];
                }
                $out[$gid][$variant][$primer] = (int) $row['n_denom'];
            }
        }
        $stmt->close();

        return $out;
    }
}

if (!function_exists('nj_read_junction_hits')) {
    /**
     * @param list<int> $groupIds
     * @param list<int> $ngeneGroupIds
     * @return array{pairs:array<string,array<int,array<string,array<string,int>>>>,overall:array<string,array<int,int>>}
     */
    function nj_read_junction_hits(mysqli $con, array $groupIds, $minReads, array $ngeneGroupIds)
    {
        $empty = ['pairs' => [], 'overall' => []];
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));
        if (!$groupIds || !nj_read_tables_exist($con)) {
            return $empty;
        }
        $minReads = (int) $minReads;
        $types = 'i';
        $values = [$minReads];
        $inTypes = '';
        $inValues = [];
        $in = nj_read_in_clause($groupIds, $inTypes, $inValues);
        $sql = 'SELECT r.nj_size, r.coord_tag, s.group_id, m.variant_label, m.primer_label, COUNT(*) AS n_num
                  FROM vcf_snv_nj_read r
                  INNER JOIN vcf_snv_sample s
                    ON s.group_id = r.group_id AND s.sample_name = r.sample_name
                  INNER JOIN vcf_snv_sample_eligibility e
                    ON e.sample_id = s.id AND e.eligible = 1
                  INNER JOIN vcf_snv_sample_meta m ON m.sample_id = s.id
                 WHERE r.read_count >= ?
                   AND s.group_id IN (' . $in . ')';
        $types .= $inTypes;
        foreach ($inValues as $id) {
            $values[] = $id;
        }
        $sql .= nj_read_ngene_sql($groupIds, $ngeneGroupIds, $types, $values, $minReads);
        $sql .= ' GROUP BY r.nj_size, r.coord_tag, s.group_id, m.variant_label, m.primer_label';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        $pairs = [];
        $overall = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $key = (int) $row['nj_size'] . '|' . (string) $row['coord_tag'];
                $gid = (int) $row['group_id'];
                $variant = (string) $row['variant_label'];
                $primer = (string) $row['primer_label'];
                $num = (int) $row['n_num'];
                if (!isset($pairs[$key])) {
                    $pairs[$key] = [];
                }
                if (!isset($pairs[$key][$gid])) {
                    $pairs[$key][$gid] = [];
                }
                if (!isset($pairs[$key][$gid][$variant])) {
                    $pairs[$key][$gid][$variant] = [];
                }
                $pairs[$key][$gid][$variant][$primer] = $num;
                if (!isset($overall[$key])) {
                    $overall[$key] = [];
                }
                if (!isset($overall[$key][$gid])) {
                    $overall[$key][$gid] = 0;
                }
                $overall[$key][$gid] += $num;
            }
        }
        $stmt->close();

        return ['pairs' => $pairs, 'overall' => $overall];
    }
}

if (!function_exists('nj_read_bucket_percent')) {
    /**
     * @param array<string,array<string,int>> $denoms
     * @param array<string,array<string,int>> $hits
     * @param array<string,mixed> $options
     */
    function nj_read_bucket_percent(array $denoms, array $hits, $bucket, $primer, array $options)
    {
        $opt = nj_read_coerce_merge($options);
        $min = (int) $opt['min_samples'];
        $showBelow = !empty($opt['show_below']);
        $denom = 0;
        $num = 0;
        foreach ($denoms as $variant => $primers) {
            if (nj_read_variant_key($variant, $opt) !== $bucket) {
                continue;
            }
            $count = isset($primers[$primer]) ? (int) $primers[$primer] : 0;
            if ($count < 1) {
                continue;
            }
            if (!$showBelow && $min > 0 && $count < $min) {
                continue;
            }
            $denom += $count;
            $num += isset($hits[$variant][$primer]) ? (int) $hits[$variant][$primer] : 0;
        }
        if ($denom < 1) {
            return null;
        }

        return nj_read_percent($num, $denom);
    }
}

if (!function_exists('nj_read_summary_options')) {
    /**
     * Summary columns are always merged Delta and merged Omicron, with BA.1 left out.
     *
     * @param array<string,mixed> $options
     */
    function nj_read_summary_options(array $options)
    {
        $opt = nj_read_coerce_merge($options);
        $opt['merge_delta'] = true;
        $opt['merge_omicron'] = true;
        $opt['merge_ba'] = false;
        $opt['merge_xbb'] = false;
        $opt['hide_ba1'] = true;
        $opt['major_only'] = true;

        return $opt;
    }
}

if (!function_exists('nj_read_summary_rows')) {
    /**
     * @param list<array{nj_size:int,coord_tag:string,nj_start:?int,nj_end:?int}> $catalog
     * @param list<int> $groupIds
     * @param array<int,array<string,array<string,int>>> $denoms
     * @param array{pairs:array,overall:array} $hits
     * @param array<string,mixed> $options
     * @return list<array<string,mixed>>
     */
    function nj_read_summary_rows(array $catalog, array $groupIds, array $denoms, array $hits, array $options)
    {
        $opt = nj_read_summary_options($options);
        $min = (int) $opt['min_samples'];
        $showBelow = !empty($opt['show_below']);
        $columns = [
            'delta_v3' => ['Delta', 'COVID-ARTIC-V3'],
            'omi_v3' => ['Omicron', 'COVID-ARTIC-V3'],
            'delta_v41' => ['Delta', 'COVID-ARTIC-V4.1'],
            'omi_v41' => ['Omicron', 'COVID-ARTIC-V4.1'],
        ];
        $rows = [];
        foreach ($catalog as $row) {
            $key = (int) $row['nj_size'] . '|' . (string) $row['coord_tag'];
            $out = [
                'nj_size' => (int) $row['nj_size'],
                'coord_tag' => (string) $row['coord_tag'],
                'nj_start' => $row['nj_start'],
                'nj_end' => $row['nj_end'],
            ];
            foreach ($columns as $name => $spec) {
                $percents = [];
                foreach ($groupIds as $gid) {
                    $gid = (int) $gid;
                    $groupDenoms = isset($denoms[$gid]) ? $denoms[$gid] : [];
                    $groupHits = isset($hits['pairs'][$key][$gid]) ? $hits['pairs'][$key][$gid] : [];
                    $percents[] = nj_read_bucket_percent($groupDenoms, $groupHits, $spec[0], $spec[1], $opt);
                }
                $out[$name] = nj_read_average_text($percents);
            }
            $overall = [];
            foreach ($groupIds as $gid) {
                $gid = (int) $gid;
                $denom = 0;
                if (isset($denoms[$gid])) {
                    foreach ($denoms[$gid] as $primers) {
                        foreach ($primers as $count) {
                            $denom += (int) $count;
                        }
                    }
                }
                if ($denom < 1 || (!$showBelow && $min > 0 && $denom < $min)) {
                    $overall[] = null;
                    continue;
                }
                $num = isset($hits['overall'][$key][$gid]) ? (int) $hits['overall'][$key][$gid] : 0;
                $overall[] = nj_read_percent($num, $denom);
            }
            $out['overall'] = nj_read_average_text($overall);
            $rows[] = $out;
        }

        return $rows;
    }
}

if (!function_exists('nj_read_variant_totals')) {
    /**
     * @param array<string,array<string,int>> $denoms
     * @return array<string,int>
     */
    function nj_read_variant_totals(array $denoms)
    {
        $out = [];
        foreach ($denoms as $variant => $primers) {
            $sum = 0;
            foreach ($primers as $count) {
                $sum += (int) $count;
            }
            $out[$variant] = $sum;
        }

        return $out;
    }
}

if (!function_exists('nj_read_count_text')) {
    /**
     * @param list<int> $parts
     */
    function nj_read_count_text(array $parts, $minSamples, $showBelow)
    {
        $minSamples = (int) $minSamples;
        $sum = 0;
        $below = 0;
        foreach ($parts as $count) {
            $count = (int) $count;
            if ($count < 1) {
                continue;
            }
            if (!$showBelow && $minSamples > 0 && $count < $minSamples) {
                $below += $count;
                continue;
            }
            $sum += $count;
        }
        if ($sum === 0 && $below > 0 && !$showBelow) {
            return '-1';
        }

        return (string) $sum;
    }
}

if (!function_exists('nj_read_grouped_count')) {
    /**
     * @param array<string,array<string,int>> $denoms
     * @param list<string> $buckets
     * @param list<string>|null $primers
     * @param array<string,mixed> $options
     */
    function nj_read_grouped_count(array $denoms, array $buckets, $primers, array $options)
    {
        $opt = nj_read_coerce_merge($options);
        $parts = [];
        foreach ($denoms as $variant => $byPrimer) {
            $bucket = nj_read_variant_bucket($variant);
            if ($bucket === null || !in_array($bucket, $buckets, true)) {
                continue;
            }
            if ($primers === null) {
                $count = 0;
                foreach ($byPrimer as $n) {
                    $count += (int) $n;
                }
                $parts[] = $count;
                continue;
            }
            foreach ($primers as $primer) {
                $parts[] = isset($byPrimer[$primer]) ? (int) $byPrimer[$primer] : 0;
            }
        }

        return nj_read_count_text($parts, $opt['min_samples'], $opt['show_below']);
    }
}

if (!function_exists('nj_read_transposed_counts')) {
    /**
     * Groups as rows, merged variant columns.
     *
     * @param array{row_keys:list<string>,groups:list<array<string,mixed>>,cells:array<string,array<string,int>>,rollups?:array<string,bool>} $pivot
     * @param list<string> $allowedNames
     * @param array<string,mixed> $options
     * @return array{columns:array<string,string>,rows:list<array{name:string,cells:array<string,string>}>}
     */
    function nj_read_transposed_counts(array $pivot, array $allowedNames, array $options, $pairMode)
    {
        $opt = nj_read_coerce_merge($options);
        $columns = [];
        $parts = [];
        $names = [];
        foreach ($pivot['groups'] as $group) {
            $name = (string) $group['name'];
            if ($allowedNames && !in_array($name, $allowedNames, true)) {
                continue;
            }
            $code = (string) $group['code'];
            $names[$code] = $name;
            $parts[$code] = [];
            foreach ($pivot['row_keys'] as $rowKey) {
                if (!empty($pivot['rollups'][$rowKey])) {
                    continue;
                }
                $count = isset($pivot['cells'][$rowKey][$code]) ? (int) $pivot['cells'][$rowKey][$code] : 0;
                if ($pairMode) {
                    $bits = explode("\t", (string) $rowKey, 2);
                    $variantKey = nj_read_variant_key(isset($bits[0]) ? $bits[0] : '', $opt);
                    $primer = isset($bits[1]) ? $bits[1] : '';
                    $abbrev = nj_read_primer_abbrev($primer);
                    if ($variantKey === null || $abbrev === '') {
                        continue;
                    }
                    $col = $variantKey . '|' . $primer;
                    $columns[$col] = trim(nj_read_variant_display($variantKey) . ' ' . $abbrev);
                } else {
                    $variantKey = nj_read_variant_key($rowKey, $opt);
                    if ($variantKey === null) {
                        continue;
                    }
                    $col = $variantKey;
                    $columns[$col] = nj_read_variant_display($variantKey);
                }
                if (!isset($parts[$code][$col])) {
                    $parts[$code][$col] = [];
                }
                $parts[$code][$col][] = $count;
            }
        }
        $rows = [];
        foreach ($names as $code => $name) {
            $cells = [];
            foreach ($columns as $col => $label) {
                $cells[$col] = nj_read_count_text(isset($parts[$code][$col]) ? $parts[$code][$col] : [], $opt['min_samples'], $opt['show_below']);
            }
            $rows[] = ['name' => $name, 'cells' => $cells];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }
}

if (!function_exists('nj_read_column_heads')) {
    /**
     * @return array{0:string,1:string}
     */
    function nj_read_column_heads($key, $by)
    {
        $key = (string) $key;
        if ($by === 'primer') {
            $abbrev = nj_read_primer_abbrev($key);

            return [$abbrev !== '' ? $abbrev : nj_read_primer_display($key), ''];
        }
        $bar = strpos($key, '|');
        if ($bar === false) {
            return [nj_read_variant_display($key), ''];
        }
        $variant = substr($key, 0, $bar);
        $primer = substr($key, $bar + 1);
        $abbrev = nj_read_primer_abbrev($primer);

        return [
            nj_read_variant_display($variant),
            $abbrev !== '' ? $abbrev : nj_read_primer_display($primer),
        ];
    }
}

if (!function_exists('nj_read_csv_field')) {
    function nj_read_csv_field($value)
    {
        $value = (string) $value;
        if (strpos($value, '"') !== false || strpos($value, ',') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}

if (!function_exists('nj_read_csv_line')) {
    /**
     * @param list<string|int|float> $fields
     */
    function nj_read_csv_line(array $fields)
    {
        $out = [];
        foreach ($fields as $field) {
            $out[] = nj_read_csv_field($field);
        }

        return implode(',', $out);
    }
}
