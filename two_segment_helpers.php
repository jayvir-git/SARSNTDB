<?php
/**
 * Shared helpers for two_segment_structure (sgmRNA CJ/NJ) — genome interval overlap
 * and repeat motif matching for links to TwoSegmentStructures.php.
 */

if (!function_exists('tsg_interval_from_genome_params')) {
    /**
     * @param string|int $start
     * @param string|int $end
     * @return array{0:int,1:int}|null [lo, hi] inclusive, or null if no numeric window
     */
    function tsg_interval_from_genome_params($start, $end)
    {
        $hasS = ($start !== '' && $start !== null && is_numeric($start));
        $hasE = ($end !== '' && $end !== null && is_numeric($end));
        if (!$hasS && !$hasE) {
            return null;
        }
        if ($hasS && !$hasE) {
            return [(int) $start, (int) $start];
        }
        if (!$hasS && $hasE) {
            return [(int) $end, (int) $end];
        }
        $a = (int) $start;
        $b = (int) $end;

        return [min($a, $b), max($a, $b)];
    }
}

if (!function_exists('tsg_viz_url')) {
    function tsg_viz_url($id)
    {
        return 'TwoSegmentStructures.php?id=' . (int) $id;
    }
}

if (!function_exists('tsg_junction_kind_label')) {
    function tsg_junction_kind_label($row)
    {
        $k = isset($row['junction_kind']) ? strtoupper(trim((string) $row['junction_kind'])) : 'CJ';
        if ($k === 'NJ') {
            return 'NJ';
        }

        return 'CJ';
    }
}

if (!function_exists('tsg_fetch_overlapping')) {
    /**
     * Rows where coord_left or coord_right lies in [lo, hi] (inclusive).
     *
     * @return array{rows: array<int, array>, error: string|null}
     */
    function tsg_fetch_overlapping(mysqli $con, $lo, $hi)
    {
        $lo = (int) $lo;
        $hi = (int) $hi;
        $rows = [];
        $sql = 'SELECT id, subtype, junction_kind, name, coord_from, coord_left, coord_right, coord_to, repeat_seq, display_order
                FROM two_segment_structure
                WHERE (coord_left >= ' . $lo . ' AND coord_left <= ' . $hi . ')
                   OR (coord_right >= ' . $lo . ' AND coord_right <= ' . $hi . ')
                ORDER BY display_order ASC, id ASC';
        $result = $con->query($sql);
        if (!$result && (int) $con->errno === 1054) {
            $sql = 'SELECT id, subtype, name, coord_from, coord_left, coord_right, coord_to, repeat_seq, display_order
                FROM two_segment_structure
                WHERE (coord_left >= ' . $lo . ' AND coord_left <= ' . $hi . ')
                   OR (coord_right >= ' . $lo . ' AND coord_right <= ' . $hi . ')
                ORDER BY display_order ASC, id ASC';
            $result = $con->query($sql);
        }
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!isset($row['junction_kind'])) {
                    $row['junction_kind'] = 'CJ';
                }
                $rows[] = $row;
            }
            $result->free();

            return ['rows' => $rows, 'error' => null];
        }

        return ['rows' => [], 'error' => $con->error];
    }
}

if (!function_exists('tsg_rows_matching_repeat')) {
    /**
     * Match user repeat motif to CJ/NJ repeat_seq (exact, substring, or superstring).
     *
     * @return list<array<string,mixed>>
     */
    function tsg_rows_matching_repeat(mysqli $con, $userRepeat)
    {
        $userRepeat = trim((string) $userRepeat);
        if (strlen($userRepeat) < 4) {
            return [];
        }
        $lu = strtolower($userRepeat);
        $out = [];
        $seen = [];
        $sql = 'SELECT id, name, junction_kind, repeat_seq FROM two_segment_structure
                WHERE repeat_seq IS NOT NULL AND TRIM(repeat_seq) <> \'\'';
        $res = $con->query($sql);
        if (!$res && (int) $con->errno === 1054) {
            $sql = 'SELECT id, name, repeat_seq FROM two_segment_structure
                WHERE repeat_seq IS NOT NULL AND TRIM(repeat_seq) <> \'\'';
            $res = $con->query($sql);
        }
        if (!$res) {
            return [];
        }
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['junction_kind'])) {
                $row['junction_kind'] = 'CJ';
            }
            $r = strtolower(trim((string) $row['repeat_seq']));
            if ($r === '') {
                continue;
            }
            $id = (int) $row['id'];
            $match = ($r === $lu);
            if (!$match && strlen($r) >= 4 && strpos($lu, $r) !== false) {
                $match = true;
            }
            if (!$match && strlen($lu) >= 6 && strpos($r, $lu) !== false) {
                $match = true;
            }
            if ($match && empty($seen[$id])) {
                $seen[$id] = true;
                $out[] = $row;
            }
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('tsg_primer_tables_exist')) {
    function tsg_primer_tables_exist(mysqli $con)
    {
        $sql = "SELECT COUNT(*) AS c FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('junction_primer_scheme','junction_primer')";
        $res = $con->query($sql);
        if (!$res) {
            return false;
        }
        $row = $res->fetch_assoc();
        $res->free();

        return isset($row['c']) && (int) $row['c'] === 2;
    }
}

if (!function_exists('tsg_fetch_primer_schemes')) {
    /**
     * @return list<array<string,mixed>>
     */
    function tsg_fetch_primer_schemes(mysqli $con)
    {
        $out = [];
        $sql = 'SELECT id, code, label, source_file, display_order
                FROM junction_primer_scheme
                ORDER BY display_order ASC, id ASC';
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

if (!function_exists('tsg_selected_scheme_codes')) {
    /**
     * Keep only codes installed in the database; order follows the scheme table.
     *
     * @param mixed $raw
     * @param list<array<string,mixed>> $schemes
     * @return list<string>
     */
    function tsg_selected_scheme_codes($raw, array $schemes)
    {
        if (!is_array($raw)) {
            $raw = $raw === null ? [] : [$raw];
        }
        $requested = [];
        foreach ($raw as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $requested[$value] = true;
            }
        }
        $out = [];
        foreach ($schemes as $scheme) {
            $code = (string) $scheme['code'];
            if (isset($requested[$code])) {
                $out[] = $code;
            }
        }

        return $out;
    }
}

if (!function_exists('tsg_fetch_primers_near_structures')) {
    /**
     * A primer matches when either normalized endpoint is within $window nt of
     * either junction border. Scheme values are bound as SQL parameters.
     *
     * @param list<array<string,mixed>> $structures
     * @param list<string> $schemeCodes
     * @return array{by_structure:array<string,list<array<string,mixed>>>,error:string|null}
     */
    function tsg_fetch_primers_near_structures(mysqli $con, array $structures, array $schemeCodes, $window = 800)
    {
        $byStructure = [];
        foreach ($structures as $structure) {
            $byStructure[(string) $structure['id']] = [];
        }
        if (!$structures || !$schemeCodes) {
            return ['by_structure' => $byStructure, 'error' => null];
        }

        $placeholders = implode(',', array_fill(0, count($schemeCodes), '?'));
        $sql = 'SELECT p.id, p.reference_name, p.bed_start, p.bed_end,
                       p.coord_start, p.coord_end, p.primer_name, p.pool_name,
                       p.strand, p.direction, s.code AS scheme_code,
                       s.label AS scheme_label, s.display_order
                FROM junction_primer p
                INNER JOIN junction_primer_scheme s ON s.id = p.scheme_id
                WHERE s.code IN (' . $placeholders . ')
                ORDER BY s.display_order ASC, p.coord_start ASC, p.coord_end ASC, p.id ASC';
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            return ['by_structure' => $byStructure, 'error' => $con->error];
        }
        $types = str_repeat('s', count($schemeCodes));
        $stmt->bind_param($types, ...$schemeCodes);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();

            return ['by_structure' => $byStructure, 'error' => $error];
        }
        $result = $stmt->get_result();
        $primers = [];
        while ($row = $result->fetch_assoc()) {
            $primers[] = $row;
        }
        $result->free();
        $stmt->close();

        $window = max(0, (int) $window);
        foreach ($structures as $structure) {
            $sid = (string) $structure['id'];
            $left = (int) $structure['coord_left'];
            $right = (int) $structure['coord_right'];
            foreach ($primers as $primer) {
                $start = (int) $primer['coord_start'];
                $end = (int) $primer['coord_end'];
                if (abs($start - $left) <= $window || abs($end - $left) <= $window
                    || abs($start - $right) <= $window || abs($end - $right) <= $window) {
                    $byStructure[$sid][] = $primer;
                }
            }
        }

        return ['by_structure' => $byStructure, 'error' => null];
    }
}

if (!function_exists('tsg_fetch_primers_near_coord')) {
    /**
     * Primers whose start or end is within $window nt of a genome coordinate (SNV).
     *
     * @param list<string> $schemeCodes
     * @return array{primers:list<array<string,mixed>>,error:string|null}
     */
    function tsg_fetch_primers_near_coord(mysqli $con, $coord, array $schemeCodes, $window = 800)
    {
        $coord = (int) $coord;
        $pack = tsg_fetch_primers_near_structures(
            $con,
            [['id' => 'snv', 'coord_left' => $coord, 'coord_right' => $coord]],
            $schemeCodes,
            $window
        );
        $primers = isset($pack['by_structure']['snv']) ? $pack['by_structure']['snv'] : [];

        return ['primers' => $primers, 'error' => $pack['error']];
    }
}
