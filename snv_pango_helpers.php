<?php
/**
 * Pango lineage names attached to SNVs (Jim Kelley designation markers).
 */

if (!function_exists('snv_pango_table_exists')) {
    function snv_pango_table_exists(mysqli $con)
    {
        $sql = "SELECT COUNT(*) AS c FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'snv_pango_marker'";
        $res = $con->query($sql);
        if (!$res) {
            return false;
        }
        $row = $res->fetch_assoc();
        $res->free();

        return isset($row['c']) && (int) $row['c'] === 1;
    }
}

if (!function_exists('snv_pango_key')) {
    /**
     * @param int|string $coord
     * @param string $ref
     * @param string $alt
     */
    function snv_pango_key($coord, $ref, $alt)
    {
        return ((int) $coord) . "\t" . strtoupper(trim((string) $ref)) . "\t" . strtoupper(trim((string) $alt));
    }
}

if (!function_exists('snv_pango_format_label')) {
    /**
     * Notation Jim uses: G21987A.
     *
     * @param int|string $coord
     * @param string $ref
     * @param string $alt
     */
    function snv_pango_format_label($coord, $ref, $alt)
    {
        $ref = strtoupper(trim((string) $ref));
        $alt = strtoupper(trim((string) $alt));
        if ($ref === '' || $alt === '') {
            return (string) ((int) $coord);
        }

        return $ref . ((int) $coord) . $alt;
    }
}

if (!function_exists('snv_pango_all_map')) {
    /**
     * Map "coord\\tref\\talt" => list of lineage names (sorted).
     * The marker table is small (~900 rows); load it once per request.
     *
     * @return array<string,list<string>>
     */
    function snv_pango_all_map(mysqli $con)
    {
        $out = [];
        if (!snv_pango_table_exists($con)) {
            return $out;
        }
        $sql = 'SELECT coordinate, reference, alternate, lineage
                FROM snv_pango_marker
                ORDER BY lineage ASC';
        $res = $con->query($sql);
        if (!$res) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $key = snv_pango_key($row['coordinate'], $row['reference'], $row['alternate']);
            $name = trim((string) $row['lineage']);
            if ($name === '') {
                continue;
            }
            if (!isset($out[$key])) {
                $out[$key] = [];
            }
            if (!in_array($name, $out[$key], true)) {
                $out[$key][] = $name;
            }
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('snv_pango_lineages_for')) {
    /**
     * @return list<string>
     */
    function snv_pango_lineages_for(mysqli $con, $coord, $ref, $alt)
    {
        $map = snv_pango_all_map($con);
        $key = snv_pango_key($coord, $ref, $alt);

        return isset($map[$key]) ? $map[$key] : [];
    }
}

if (!function_exists('snv_pango_groups_at_coord')) {
    /**
     * All designation-marker SNVs at one coordinate.
     *
     * @return list<array{reference:string,alternate:string,label:string,lineages:list<string>}>
     */
    function snv_pango_groups_at_coord(mysqli $con, $coord)
    {
        $coord = (int) $coord;
        $out = [];
        foreach (snv_pango_all_map($con) as $key => $lineages) {
            $parts = explode("\t", $key);
            if (count($parts) !== 3 || (int) $parts[0] !== $coord) {
                continue;
            }
            $out[] = [
                'reference' => $parts[1],
                'alternate' => $parts[2],
                'label' => snv_pango_format_label($coord, $parts[1], $parts[2]),
                'lineages' => $lineages,
            ];
        }

        return $out;
    }
}

if (!function_exists('snv_pango_html_list')) {
    /**
     * Comma sits inside each item so CSS wrapping cannot put space before it.
     *
     * @param list<string> $lineages
     */
    function snv_pango_html_list(array $lineages)
    {
        if (!$lineages) {
            return '<span class="text-muted">—</span>';
        }
        $names = [];
        foreach ($lineages as $name) {
            $label = trim((string) $name);
            if ($label !== '') {
                $names[] = $label;
            }
        }
        if (!$names) {
            return '<span class="text-muted">—</span>';
        }
        $last = count($names) - 1;
        $bits = [];
        foreach ($names as $i => $label) {
            if ($i !== $last) {
                $label .= ',';
            }
            $bits[] = '<span class="snv-pango-item">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return '<span class="snv-pango-list">' . implode(' ', $bits) . '</span>';
    }
}

if (!function_exists('snv_pango_table_named_exists')) {
    function snv_pango_table_named_exists(mysqli $con, $table)
    {
        $table = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $table));
        if ($table === '') {
            return false;
        }
        $sql = "SELECT COUNT(*) AS c FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = '" . $con->real_escape_string($table) . "'";
        $res = $con->query($sql);
        if (!$res) {
            return false;
        }
        $row = $res->fetch_assoc();
        $res->free();

        return isset($row['c']) && (int) $row['c'] === 1;
    }
}

if (!function_exists('snv_mutation_sample_total')) {
    function snv_mutation_sample_total()
    {
        return 18900;
    }
}

if (!function_exists('pango_indel_table_exists')) {
    function pango_indel_table_exists(mysqli $con)
    {
        return snv_pango_table_named_exists($con, 'pango_indel_marker');
    }
}

if (!function_exists('pango_indel_kind_label')) {
    function pango_indel_kind_label($kind)
    {
        return ((string) $kind) === 'insertion' ? 'insertion' : 'deletion';
    }
}

if (!function_exists('pango_indel_all_groups')) {
    /**
     * Distinct indels with lineage lists, ordered by coordinate.
     *
     * @return list<array{coordinate:int,reference:string,alternate:string,kind:string,label:string,lineages:list<string>}>
     */
    function pango_indel_all_groups(mysqli $con)
    {
        $out = [];
        if (!pango_indel_table_exists($con)) {
            return $out;
        }
        $sql = 'SELECT coordinate, reference, alternate, kind, lineage
                FROM pango_indel_marker
                ORDER BY coordinate ASC, kind ASC, lineage ASC';
        $res = $con->query($sql);
        if (!$res) {
            return $out;
        }
        $index = [];
        while ($row = $res->fetch_assoc()) {
            $key = snv_pango_key($row['coordinate'], $row['reference'], $row['alternate']);
            $name = trim((string) $row['lineage']);
            if ($name === '') {
                continue;
            }
            if (!isset($index[$key])) {
                $index[$key] = count($out);
                $out[] = [
                    'coordinate' => (int) $row['coordinate'],
                    'reference' => strtoupper(trim((string) $row['reference'])),
                    'alternate' => strtoupper(trim((string) $row['alternate'])),
                    'kind' => pango_indel_kind_label($row['kind']),
                    'label' => snv_pango_format_label($row['coordinate'], $row['reference'], $row['alternate']),
                    'lineages' => [],
                ];
            }
            if (!in_array($name, $out[$index[$key]]['lineages'], true)) {
                $out[$index[$key]]['lineages'][] = $name;
            }
        }
        $res->free();

        return $out;
    }
}

if (!function_exists('pango_indel_in_range')) {
    /**
     * @return list<array{coordinate:int,reference:string,alternate:string,kind:string,label:string,lineages:list<string>}>
     */
    function pango_indel_in_range(mysqli $con, $start, $end)
    {
        $start = (int) $start;
        $end = (int) $end;
        if ($start > $end) {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }
        $out = [];
        foreach (pango_indel_all_groups($con) as $row) {
            if ($row['coordinate'] < $start || $row['coordinate'] > $end) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }
}

if (!function_exists('pango_indel_lineages_for')) {
    /**
     * @return list<string>
     */
    function pango_indel_lineages_for(mysqli $con, $coord, $ref, $alt)
    {
        $coord = (int) $coord;
        $ref = strtoupper(trim((string) $ref));
        $alt = strtoupper(trim((string) $alt));
        foreach (pango_indel_all_groups($con) as $row) {
            if ($row['coordinate'] === $coord && $row['reference'] === $ref && $row['alternate'] === $alt) {
                return $row['lineages'];
            }
        }

        return [];
    }
}

if (!function_exists('pango_indel_groups_at_coord')) {
    /**
     * @return list<array{coordinate:int,reference:string,alternate:string,kind:string,label:string,lineages:list<string>}>
     */
    function pango_indel_groups_at_coord(mysqli $con, $coord)
    {
        $coord = (int) $coord;
        $out = [];
        foreach (pango_indel_all_groups($con) as $row) {
            if ($row['coordinate'] === $coord) {
                $out[] = $row;
            }
        }

        return $out;
    }
}

if (!function_exists('snv_overlay_variants')) {
    /**
     * SNVs from the mutations table at or above the Detail frequency floor
     * (Jim's nearby overlay; email said "<= 1%" but the 23202 test is ~8%).
     *
     * @return list<array{coordinate:int,reference:string,alternate:string,label:string}>
     */
    function snv_overlay_variants(mysqli $con, $minPercent = 1.0)
    {
        $out = [];
        if (!snv_pango_table_named_exists($con, 'mutations')) {
            return $out;
        }
        $total = snv_mutation_sample_total();
        if ($total < 1) {
            return $out;
        }
        $minPercent = (float) $minPercent;
        $sql = 'SELECT coordinate, UPPER(reference) AS reference, UPPER(alternate) AS alternate,
                       SUM(mutcount) AS sample_count
                FROM mutations
                WHERE CHAR_LENGTH(reference) = 1
                  AND CHAR_LENGTH(alternate) = 1
                GROUP BY coordinate, reference, alternate
                HAVING (SUM(mutcount) / ' . (int) $total . ') * 100 >= ' . $minPercent . '
                ORDER BY coordinate ASC';
        $res = $con->query($sql);
        if (!$res) {
            return $out;
        }
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'coordinate' => (int) $row['coordinate'],
                'reference' => $row['reference'],
                'alternate' => $row['alternate'],
                'label' => snv_pango_format_label($row['coordinate'], $row['reference'], $row['alternate']),
            ];
        }
        $res->free();

        return $out;
    }
}
