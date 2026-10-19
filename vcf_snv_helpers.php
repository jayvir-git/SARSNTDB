<?php
/**
 * Jim Kelley VCF SNV groups (GROM pad-bwa). John's mutations table stays as the
 * "Original" view. Group + AF filter live in vcf_snv_* tables.
 */

require_once __DIR__ . '/snv_pango_helpers.php';

if (!function_exists('vcf_snv_tables_exist')) {
    function vcf_snv_tables_exist(mysqli $con)
    {
        return snv_pango_table_named_exists($con, 'vcf_snv_group')
            && snv_pango_table_named_exists($con, 'vcf_snv_sample')
            && snv_pango_table_named_exists($con, 'vcf_snv_call');
    }
}

if (!function_exists('vcf_snv_eligibility_tables_exist')) {
    function vcf_snv_eligibility_tables_exist(mysqli $con)
    {
        return snv_pango_table_named_exists($con, 'vcf_snv_sample_eligibility');
    }
}

if (!function_exists('vcf_snv_hide_non_workbook_groups')) {
    /**
     * Temporary Sep 17 display restriction. Flip to false to restore hidden groups.
     * Hide, never delete (D-34).
     */
    function vcf_snv_hide_non_workbook_groups()
    {
        return true;
    }
}

if (!function_exists('vcf_snv_workbook_visible_codes')) {
    /**
     * Workbook rows that map to an imported VCF group, including Argentina.
     *
     * @return list<string>
     */
    function vcf_snv_workbook_visible_codes()
    {
        return [
            'India-6000',
            'PAK_iseq',
            'illumina_miseq',
            'illumina_hiseq_2500',
            'nextseq_500',
            'nextseq_550',
            'LA-PRJNA815364',
            'LA-PRJNA815364_nextseq500',
            'PRJNA622837-Broad_Inst',
            'PRJNA746690-CA',
            'PRJNA744530-RI',
            'Denmark-nextseq550',
            'PRJEB72496-Denmark-6000',
            'NJ-PRJNA708324',
            'S_Afr-PRJNA636748',
            'Port-PRJEB47340',
            'Port_miseq',
            'PRJEB46220-Argentina',
            'Angola_miseq',
            'Botswana',
            'Slovakia_miseq',
            'NM',
            'VA',
            'Austr-PRJNA613958_nextseq500',
            'Austr-PRJNA613958_nextseq550',
            'India-miseq',
            'Thailand_mix',
            'Est-mix',
        ];
    }
}

if (!function_exists('vcf_snv_isolated_group_codes')) {
    /**
     * Visible workbook groups whose PASS∩VCF join is not activated.
     *
     * @return list<string>
     */
    function vcf_snv_isolated_group_codes()
    {
        return [];
    }
}

if (!function_exists('vcf_snv_group_display_label')) {
    /**
     * Jim 21 Sep: USA groups use country + state + accession.
     */
    function vcf_snv_group_display_label($code, $storedLabel = '')
    {
        $labels = [
            'NJ-PRJNA708324' => 'USA NJ MiSeq PRJNA708324',
            'PRJNA622837-Broad_Inst' => 'USA NE, NJ NovaSeq 6000 PRJNA622837 and PRJNA715749',
            'PRJNA746690-CA' => 'USA CA MiSeq PRJNA746690',
            'PRJNA744530-RI' => 'USA RI MiSeq PRJNA744530',
            'Denmark-nextseq550' => 'Denmark NextSeq 550 PRJEB49401 and PRJEB72496',
            'PRJEB72496-Denmark-6000' => 'Denmark NovaSeq 6000 PRJEB72496',
            'LA-PRJNA815364' => 'USA LA MiSeq PRJNA815364',
            'LA-PRJNA815364_nextseq500' => 'USA LA NextSeq 500 PRJNA815364',
            'PRJEB46220-Argentina' => 'Argentina MiSeq PRJEB46220',
            'S_Afr-PRJNA636748' => 'South Africa MiSeq PRJNA636748',
            'India-6000' => 'India NovaSeq 6000 PRJNA625669',
            'India-miseq' => 'India MiSeq PRJNA625669',
            'PAK_iseq' => 'Pakistan iSeq 100 PRJNA764553',
            'Port-PRJEB47340' => 'Portugal NextSeq 550 PRJEB47340',
            'Port_miseq' => 'Portugal MiSeq PRJEB47340',
            'illumina_miseq' => 'United Kingdom MiSeq PRJEB37886',
            'illumina_hiseq_2500' => 'United Kingdom HiSeq 2500 PRJEB37886',
            'nextseq_500' => 'United Kingdom NextSeq 500 PRJEB37886',
            'nextseq_550' => 'United Kingdom NextSeq 550 PRJEB37886',
            'Angola_miseq' => 'Angola MiSeq PRJNA717113',
            'Botswana' => 'Botswana MiSeq PRJNA782796',
            'Slovakia_miseq' => 'Slovakia MiSeq PRJEB45305',
            'NM' => 'USA NM MiSeq PRJNA656534',
            'VA' => 'USA VA MiSeq PRJNA625551',
            'Austr-PRJNA613958_nextseq500' => 'Australia NextSeq 500 PRJNA613958',
            'Austr-PRJNA613958_nextseq550' => 'Australia NextSeq 550 PRJNA613958',
            'Thailand_mix' => 'Thailand many #s',
            'Est-mix' => 'Estonia many #s',
        ];
        $code = (string) $code;
        if (isset($labels[$code])) {
            return $labels[$code];
        }
        $storedLabel = (string) $storedLabel;

        return $storedLabel !== '' ? $storedLabel : $code;
    }
}

if (!function_exists('vcf_snv_group_is_visible')) {
    function vcf_snv_group_is_visible($code)
    {
        $code = (string) $code;
        if ($code === '' || $code === 'original') {
            return true;
        }
        if (!vcf_snv_hide_non_workbook_groups()) {
            return true;
        }

        return in_array($code, vcf_snv_workbook_visible_codes(), true);
    }
}

if (!function_exists('vcf_snv_group_is_isolated')) {
    function vcf_snv_group_is_isolated($code)
    {
        return in_array((string) $code, vcf_snv_isolated_group_codes(), true);
    }
}

if (!function_exists('vcf_snv_eligibility_reason')) {
    /**
     * Future sample filters should AND extra reasons here, not replace this rule.
     * Eligible = QC PASS and has a VCF, by sample ID; never the workbook "# Analyzed" (D-33).
     *
     * @param string|null $qc
     */
    function vcf_snv_eligibility_reason($qc, $hasVcf)
    {
        if (!$hasVcf) {
            return 'no_vcf';
        }
        if ($qc === null || $qc === '') {
            return 'unmatched';
        }
        if ((string) $qc !== 'PASS') {
            return 'fail_qc';
        }

        return '';
    }
}

if (!function_exists('vcf_snv_sample_is_eligible')) {
    /**
     * @param string|null $qc
     */
    function vcf_snv_sample_is_eligible($qc, $hasVcf)
    {
        return vcf_snv_eligibility_reason($qc, $hasVcf) === '';
    }
}

if (!function_exists('vcf_snv_default_group_code')) {
    // USA NJ on first open is intentional (D-58).
    function vcf_snv_default_group_code()
    {
        return 'NJ-PRJNA708324';
    }
}

if (!function_exists('vcf_snv_eligibility_stats_by_group_id')) {
    /**
     * @return array<int,array{vcf:int,in_meta:int,eligible:int,unmatched:int,fail_qc:int}>
     */
    function vcf_snv_eligibility_stats_by_group_id(mysqli $con)
    {
        $out = [];
        if (!vcf_snv_eligibility_tables_exist($con)) {
            return $out;
        }
        $sql = 'SELECT group_id,
                       COUNT(*) AS vcf_n,
                       SUM(qc IS NOT NULL) AS in_meta,
                       SUM(eligible = 1) AS eligible_n,
                       SUM(exclusion_reason = \'unmatched\') AS unmatched_n,
                       SUM(exclusion_reason = \'fail_qc\') AS fail_n
                  FROM vcf_snv_sample_eligibility
              GROUP BY group_id';
        $res = $con->query($sql);
        if (!$res) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $out[(int) $row['group_id']] = [
                'vcf' => (int) $row['vcf_n'],
                'in_meta' => (int) $row['in_meta'],
                'eligible' => (int) $row['eligible_n'],
                'unmatched' => (int) $row['unmatched_n'],
                'fail_qc' => (int) $row['fail_n'],
            ];
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('vcf_snv_eligibility_active')) {
    function vcf_snv_eligibility_active(mysqli $con, $groupId)
    {
        $groupId = (int) $groupId;
        if ($groupId < 1 || !vcf_snv_eligibility_tables_exist($con)) {
            return false;
        }
        $stmt = $con->prepare('SELECT 1 FROM vcf_snv_sample_eligibility WHERE group_id = ? LIMIT 1');
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

if (!function_exists('vcf_snv_eligible_sql_join')) {
    /**
     * Restrict call rows to PASS ∩ matching-VCF samples. Isolated groups skip this join.
     *
     * @param array<string,mixed> $group
     */
    function vcf_snv_eligible_sql_join(mysqli $con, array $group, $alias = 'c')
    {
        if (empty($group['id']) || !empty($group['isolated'])) {
            return '';
        }
        $groupId = (int) $group['id'];
        if (!vcf_snv_eligibility_active($con, $groupId)) {
            return '';
        }
        $safeAlias = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $alias) ? $alias : 'c';

        return ' INNER JOIN vcf_snv_sample_eligibility e ON e.sample_id = ' . $safeAlias
            . '.sample_id AND e.group_id = ' . $groupId . ' AND e.eligible = 1 ';
    }
}

if (!function_exists('vcf_snv_query_sample_count')) {
    /**
     * Denominator: eligible PASS∩VCF samples when that layer is active, else stored VCF n.
     */
    function vcf_snv_query_sample_count(mysqli $con, array $group)
    {
        $stored = isset($group['vcf_sample_count'])
            ? (int) $group['vcf_sample_count']
            : (int) $group['sample_count'];
        if (empty($group['id']) || !empty($group['isolated']) || vcf_snv_group_is_isolated($group['code'])) {
            return $stored;
        }
        if (!vcf_snv_eligibility_active($con, $group['id'])) {
            return $stored;
        }
        $gid = (int) $group['id'];
        $stmt = $con->prepare('SELECT COUNT(*) AS n FROM vcf_snv_sample_eligibility WHERE group_id = ? AND eligible = 1');
        if (!$stmt) {
            return $stored;
        }
        $stmt->bind_param('i', $gid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ? (int) $row['n'] : $stored;
    }
}

if (!function_exists('vcf_snv_request_group')) {
    function vcf_snv_request_group()
    {
        if (!isset($_GET['Group'])) {
            return '';
        }
        $code = trim((string) $_GET['Group']);
        if ($code === '' || $code === 'original') {
            return '';
        }
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $code)) {
            return '';
        }
        return $code;
    }
}

if (!function_exists('vcf_snv_request_min_af')) {
    function vcf_snv_request_min_af()
    {
        $minAf = 0.8;
        if (isset($_GET['MinAf']) && $_GET['MinAf'] !== '' && is_numeric($_GET['MinAf'])) {
            $minAf = floatval($_GET['MinAf']);
        }
        if ($minAf < 0) {
            $minAf = 0.0;
        }
        if ($minAf > 1) {
            $minAf = 1.0;
        }
        return $minAf;
    }
}

if (!function_exists('vcf_snv_fetch_groups')) {
    /**
     * @return list<array<string,mixed>>
     */
    function vcf_snv_fetch_groups(mysqli $con)
    {
        $out = [];
        if (!vcf_snv_tables_exist($con)) {
            return $out;
        }
        $stats = vcf_snv_eligibility_stats_by_group_id($con);
        $sql = 'SELECT id, code, label, sample_count FROM vcf_snv_group ORDER BY label ASC';
        $res = $con->query($sql);
        if (!$res) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $code = (string) $row['code'];
            if (!vcf_snv_group_is_visible($code)) {
                continue;
            }
            $gid = (int) $row['id'];
            $stored = (int) $row['sample_count'];
            $isolated = vcf_snv_group_is_isolated($code);
            $elig = isset($stats[$gid]) ? $stats[$gid] : null;
            $usesElig = $elig !== null && !$isolated;
            $display = $usesElig ? (int) $elig['eligible'] : $stored;
            $out[] = [
                'id' => $gid,
                'code' => $code,
                'label' => vcf_snv_group_display_label($code, (string) $row['label']),
                'sample_count' => $display,
                'vcf_sample_count' => $stored,
                'isolated' => $isolated,
                'eligibility' => $elig,
            ];
        }
        $res->free();
        return $out;
    }
}

if (!function_exists('vcf_snv_group_row')) {
    /**
     * @return array<string,mixed>|null
     */
    function vcf_snv_group_row(mysqli $con, $code)
    {
        if ($code === '' || !vcf_snv_tables_exist($con) || !vcf_snv_group_is_visible($code)) {
            return null;
        }
        $stmt = $con->prepare('SELECT id, code, label, sample_count FROM vcf_snv_group WHERE code = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            return null;
        }
        $gid = (int) $row['id'];
        $stored = (int) $row['sample_count'];
        $isolated = vcf_snv_group_is_isolated($code);
        $stats = vcf_snv_eligibility_stats_by_group_id($con);
        $elig = isset($stats[$gid]) ? $stats[$gid] : null;
        $group = [
            'id' => $gid,
            'code' => (string) $row['code'],
            'label' => vcf_snv_group_display_label((string) $row['code'], (string) $row['label']),
            'sample_count' => $stored,
            'vcf_sample_count' => $stored,
            'isolated' => $isolated,
            'eligibility' => $elig,
        ];
        $group['query_sample_count'] = vcf_snv_query_sample_count($con, $group);

        return $group;
    }
}

if (!function_exists('vcf_snv_group_menu_label')) {
    /**
     * Selector text: eligible n when PASS∩VCF is active.
     *
     * @param array<string,mixed> $row
     */
    function vcf_snv_group_menu_label(array $row)
    {
        return (string) $row['label'];
    }
}

if (!function_exists('vcf_snv_eligibility_counts_text')) {
    /**
     * @param array{vcf:int,in_meta:int,eligible:int,unmatched:int,fail_qc:int}|null $elig
     */
    function vcf_snv_eligibility_counts_text($elig)
    {
        if (!is_array($elig)) {
            return '';
        }

        return 'VCF=' . (int) $elig['vcf']
            . '; metadata=' . (int) $elig['in_meta']
            . '; PASS∩VCF=' . (int) $elig['eligible']
            . '; unmatched=' . (int) $elig['unmatched']
            . '; FAIL_QC=' . (int) $elig['fail_qc'];
    }
}

if (!function_exists('vcf_snv_coord_clause')) {
    /**
     * @return array{0:string,1:list<mixed>,2:string}
     */
    function vcf_snv_coord_clause($start, $end, $alias = 'c')
    {
        $start = trim((string) $start);
        $end = trim((string) $end);
        if ($start !== '' && $end !== '' && is_numeric($start) && is_numeric($end)) {
            return [' AND ' . $alias . '.coordinate BETWEEN ? AND ? ', [(int) $start, (int) $end], 'ii'];
        }
        if ($start !== '' && is_numeric($start)) {
            return [' AND ' . $alias . '.coordinate >= ? ', [(int) $start], 'i'];
        }
        if ($end !== '' && is_numeric($end)) {
            return [' AND ' . $alias . '.coordinate <= ? ', [(int) $end], 'i'];
        }
        return ['', [], ''];
    }
}

if (!function_exists('vcf_snv_detail_pack')) {
    /**
     * Detail rows in the same shape MutationsDetail.php already renders.
     * Denominator is the group's sample_count. Per-sample allele_frequency
     * remains the Min AF filter; Mean AF is not selected for display.
     *
     * @return array{rows:list<array>,sample_count:int,group_label:string,min_af:float}|null
     */
    function vcf_snv_detail_pack(mysqli $con, $groupCode, $minAf, $start, $end, $region)
    {
        $group = vcf_snv_group_row($con, $groupCode);
        if ($group === null) {
            return null;
        }
        $gid = $group['id'];
        $region = trim((string) $region);
        $useRegion = $region !== '' && strcasecmp($region, 'All') !== 0;
        // Count calls per SNV first, then attach gene_1. Grouping by the gene's
        // RNA_sequence and protSeq per call spilled a large temporary table to disk
        // (Detail took 30-150 s). Counts are the same: each call meets a gene once.
        $inner = 'SELECT c.reference, c.alternate, c.coordinate, COUNT(*) AS no_of_samples
                    FROM vcf_snv_call c'
            . vcf_snv_eligible_sql_join($con, $group)
            . ' WHERE c.group_id = ? AND c.allele_frequency >= ?';
        $types = 'id';
        $params = [$gid, $minAf];
        if (!$useRegion) {
            $coord = vcf_snv_coord_clause($start, $end, 'c');
            $inner .= $coord[0];
            $types .= $coord[2];
            $params = array_merge($params, $coord[1]);
        }
        $inner .= ' GROUP BY c.reference, c.alternate, c.coordinate';

        $sql = 'SELECT x.reference, x.alternate, x.coordinate, g.protein, g.domain,
                       x.no_of_samples,
                       g.protSeq, g.RNA_sequence, g.Start
                  FROM (' . $inner . ') x'
            . ($useRegion ? ' INNER' : ' LEFT')
            . ' JOIN gene_1 g ON x.coordinate BETWEEN g.Start AND g.End';
        if ($useRegion) {
            $sql .= ' WHERE g.Protein = ?';
            $types .= 's';
            $params[] = $region;
        }
        $sql .= ' ORDER BY x.coordinate';

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $bind = [$types];
        foreach ($params as $i => $value) {
            $bind[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return [
            'rows' => $rows,
            'sample_count' => vcf_snv_query_sample_count($con, $group),
            'vcf_sample_count' => (int) $group['vcf_sample_count'],
            'group_label' => $group['label'],
            'min_af' => $minAf,
            'isolated' => !empty($group['isolated']),
            'eligibility' => $group['eligibility'],
        ];
    }
}

if (!function_exists('vcf_snv_summary_instrument_rows')) {
    /**
     * Same pivot shape as MutationsSummary.php: reference × instrument × alt counts.
     *
     * @return list<array<string,mixed>>
     */
    function vcf_snv_summary_instrument_rows(mysqli $con, $groupCode, $minAf, $start, $end, $region)
    {
        $group = vcf_snv_group_row($con, $groupCode);
        if ($group === null) {
            return [];
        }
        $gid = $group['id'];
        $label = $group['label'];
        $join = vcf_snv_eligible_sql_join($con, $group);
        $sql = 'SELECT c.reference, c.alternate, COUNT(*) AS coordinate_count
                  FROM vcf_snv_call c' . $join;
        $types = 'id';
        $params = [$gid, $minAf];
        $region = trim((string) $region);
        if ($region !== '' && strcasecmp($region, 'All') !== 0) {
            $join = ' INNER JOIN gene_1 g ON c.coordinate BETWEEN g.Start AND g.End';
            $sql .= $join . ' WHERE c.group_id = ? AND c.allele_frequency >= ? AND g.Protein = ?';
            $types .= 's';
            $params[] = $region;
        } else {
            $sql .= ' WHERE c.group_id = ? AND c.allele_frequency >= ?';
            $coord = vcf_snv_coord_clause($start, $end, 'c');
            $sql .= $coord[0];
            $types .= $coord[2];
            $params = array_merge($params, $coord[1]);
        }
        $sql .= ' GROUP BY c.reference, c.alternate';

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $bind = [$types];
        foreach ($params as $i => $value) {
            $bind[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
        $stmt->execute();
        $res = $stmt->get_result();
        $pairs = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        $byRef = [];
        foreach ($pairs as $row) {
            $ref = (string) $row['reference'];
            if (!isset($byRef[$ref])) {
                $byRef[$ref] = ['A' => 0, 'C' => 0, 'G' => 0, 'T' => 0];
            }
            $alt = strtoupper((string) $row['alternate']);
            if (isset($byRef[$ref][$alt])) {
                $byRef[$ref][$alt] = (int) $row['coordinate_count'];
            }
        }

        $out = [];
        foreach ($byRef as $ref => $alts) {
            $total = $alts['A'] + $alts['C'] + $alts['G'] + $alts['T'];
            foreach ([$label, 'ALL'] as $instrument) {
                $out[] = [
                    'reference' => $ref,
                    'instrument' => $instrument,
                    'alternate_A' => $alts['A'],
                    'alternate_C' => $alts['C'],
                    'alternate_G' => $alts['G'],
                    'alternate_T' => $alts['T'],
                    'alternate_total' => $total,
                ];
            }
        }
        usort($out, function ($a, $b) {
            $c = strcmp($a['reference'], $b['reference']);
            if ($c !== 0) {
                return $c;
            }
            if ($a['instrument'] === 'ALL') {
                return -1;
            }
            if ($b['instrument'] === 'ALL') {
                return 1;
            }
            return strcmp($a['instrument'], $b['instrument']);
        });
        return $out;
    }
}

if (!function_exists('vcf_snv_frequency_rows')) {
    /**
     * @return list<array{Protein:?string,coordinate:int,Start:?int,End:?int,frequency:int}>
     */
    function vcf_snv_frequency_rows(mysqli $con, $groupCode, $minAf, $start, $end, $region)
    {
        $group = vcf_snv_group_row($con, $groupCode);
        if ($group === null) {
            return [];
        }
        $gid = $group['id'];
        $region = trim((string) $region);
        $types = 'id';
        $params = [$gid, $minAf];
        if ($region !== '' && strcasecmp($region, 'All') !== 0) {
            $sql = 'SELECT g.Protein, c.coordinate, g.Start, g.End, COUNT(*) AS frequency
                      FROM vcf_snv_call c'
                . vcf_snv_eligible_sql_join($con, $group)
                . ' LEFT OUTER JOIN gene_1 g ON c.coordinate BETWEEN g.Start AND g.End
                     WHERE c.group_id = ? AND c.allele_frequency >= ? AND g.Protein = ?
                     GROUP BY g.Protein, g.Start, g.End, c.coordinate';
            $types .= 's';
            $params[] = $region;
        } else {
            // Whole genome or coordinates: count calls per coordinate first, then
            // attach gene_1 (as in vcf_snv_detail_pack); joining gene_1 to every
            // call took twice as long. Same rows: each call meets each matching
            // gene once. CAST keeps frequency an integer, as COUNT(*) returned.
            // A region keeps the query above, which is faster for one gene.
            $coord = vcf_snv_coord_clause($start, $end, 'c');
            $types .= $coord[2];
            $params = array_merge($params, $coord[1]);
            $sql = 'SELECT g.Protein, x.coordinate, g.Start, g.End, CAST(SUM(x.n) AS SIGNED) AS frequency
                      FROM (SELECT c.coordinate, COUNT(*) AS n
                              FROM vcf_snv_call c'
                . vcf_snv_eligible_sql_join($con, $group)
                . '          WHERE c.group_id = ? AND c.allele_frequency >= ?' . $coord[0] . '
                             GROUP BY c.coordinate) x
                      LEFT OUTER JOIN gene_1 g ON x.coordinate BETWEEN g.Start AND g.End
                     GROUP BY g.Protein, g.Start, g.End, x.coordinate';
        }

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $bind = [$types];
        foreach ($params as $i => $value) {
            $bind[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('vcf_snv_group_series_points')) {
    /**
     * @param list<array<string,mixed>> $instrumentRows
     * @return list<array{label:string,y:int}>
     */
    function vcf_snv_group_series_points($instrumentRows, $groupLabel)
    {
        $points = [];
        foreach ($instrumentRows as $row) {
            if ((string) $row['instrument'] !== (string) $groupLabel) {
                continue;
            }
            $ref = (string) $row['reference'];
            foreach (['A', 'C', 'G', 'T'] as $alt) {
                if ($ref === $alt) {
                    continue;
                }
                $y = (int) $row['alternate_' . $alt];
                $points[] = ['label' => $ref . '-' . $alt, 'y' => $y];
            }
        }
        return $points;
    }
}

if (!function_exists('vcf_snv_overlay_group_code')) {
    /**
     * Group for the nearby-SNV overlay: ?Group= when it names a loaded group, else the default.
     */
    function vcf_snv_overlay_group_code()
    {
        $code = vcf_snv_request_group();

        return $code !== '' ? $code : vcf_snv_default_group_code();
    }
}

if (!function_exists('vcf_snv_overlay_variants')) {
    /**
     * Nearby SNVs for primer views from one VCF group, never John's Original table (Jim, Zoom 2026-10-19, D-76).
     * Same counting as Mutations Detail: share of analyzed samples at allele frequency >= $minAf,
     * kept at >= $minPercent (1% floor, D-23). Single-base changes only.
     *
     * @return list<array{coordinate:int,reference:string,alternate:string,label:string,source:string,source_label:string}>
     */
    function vcf_snv_overlay_variants(mysqli $con, $groupCode, $minAf, $minPercent = 1.0, $start = '', $end = '')
    {
        $out = [];
        $pack = vcf_snv_detail_pack($con, $groupCode, $minAf, $start, $end, '');
        if ($pack === null || (int) $pack['sample_count'] < 1) {
            return $out;
        }
        $total = (int) $pack['sample_count'];
        $sourceLabel = $pack['group_label'] . ' (≥' . rtrim(rtrim(number_format((float) $minPercent, 2, '.', ''), '0'), '.')
            . '% of ' . $total . ' analyzed samples, AF ≥ ' . $minAf . ')';
        $seen = [];
        foreach ($pack['rows'] as $row) {
            $ref = strtoupper((string) $row['reference']);
            $alt = strtoupper((string) $row['alternate']);
            if (strlen($ref) !== 1 || strlen($alt) !== 1) {
                continue;
            }
            $key = $row['coordinate'] . $ref . $alt;
            if (isset($seen[$key]) || ((int) $row['no_of_samples'] / $total) * 100 < (float) $minPercent) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'coordinate' => (int) $row['coordinate'],
                'reference' => $ref,
                'alternate' => $alt,
                'label' => snv_pango_format_label($row['coordinate'], $ref, $alt),
                'source' => 'vcf_group',
                'source_label' => $sourceLabel,
            ];
        }

        return $out;
    }
}

if (!function_exists('vcf_snv_overlay_group_select')) {
    /**
     * <select name="Group"> of visible VCF groups for the primer views.
     */
    function vcf_snv_overlay_group_select(mysqli $con, $selectedCode)
    {
        $html = '<select name="Group" class="form-control input-sm" style="display:inline-block; width:auto;" onchange="if (this.form.requestSubmit) { this.form.requestSubmit(); } else { this.form.submit(); }">';
        foreach (vcf_snv_groups_with_min_samples(vcf_snv_fetch_groups($con)) as $row) {
            $code = (string) $row['code'];
            $html .= '<option value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '"'
                . ($code === (string) $selectedCode ? ' selected' : '') . '>'
                . htmlspecialchars((string) $row['label'], ENT_QUOTES, 'UTF-8') . '</option>';
        }

        return $html . '</select>';
    }
}

if (!function_exists('vcf_snv_groups_with_min_samples')) {
    /**
     * SNV groups with at least $min analyzed samples; smaller groups are left off the menus
     * (Jim, Zoom 2026-10-19, D-76).
     *
     * @param list<array<string,mixed>> $groups
     * @return list<array<string,mixed>>
     */
    function vcf_snv_groups_with_min_samples(array $groups, $min = 20)
    {
        return array_values(array_filter($groups, function ($row) use ($min) {
            return (int) $row['sample_count'] >= $min;
        }));
    }
}
