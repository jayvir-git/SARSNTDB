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
     * @param list<string> $lineages
     */
    function snv_pango_html_list(array $lineages)
    {
        if (!$lineages) {
            return '<span class="text-muted">—</span>';
        }
        $bits = [];
        foreach ($lineages as $name) {
            $bits[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        }

        return '<span class="snv-pango-list">' . implode(', ', $bits) . '</span>';
    }
}
