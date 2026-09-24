<?php
/**
 * Shared Mutations Detail query, display rows, and CSV export.
 * HTML and CSV must use the same filters, columns, and row order.
 */

require_once __DIR__ . '/vcf_snv_helpers.php';

if (!function_exists('mutations_detail_request_min_percent')) {
    function mutations_detail_request_min_percent()
    {
        $minPercent = 1.0;
        if (isset($_GET['MinPercent']) && $_GET['MinPercent'] !== '' && is_numeric($_GET['MinPercent'])) {
            $minPercent = floatval($_GET['MinPercent']);
        }
        if ($minPercent < 1) {
            $minPercent = 1.0;
        }
        return $minPercent;
    }
}

if (!function_exists('mutations_detail_aa_change')) {
    /**
     * Amino-acid consequence for a coding SNV. Empty string if not in a gene.
     *
     * @param array<string,mixed> $row
     */
    function mutations_detail_aa_change(array $row)
    {
        $hasGene = isset($row['Start']) && $row['Start'] !== null && $row['Start'] !== ''
            && isset($row['RNA_sequence']) && $row['RNA_sequence'] !== null && $row['RNA_sequence'] !== '';
        if (!$hasGene) {
            return '';
        }

        $aminoacids = array(
            'F', 'L', 'I', 'M', 'V', 'S', 'P', 'T', 'A', 'Y', '*', 'H', 'Q', 'N', 'K', 'D', 'E', 'C', 'W', 'R', 'G', 'X',
        );
        $triplets = array(
            '(TTT |TTC )', '(TTA |TTG |CT. )', '(ATT |ATC |ATA )', '(ATG )', '(GT. )', '(TC. |AGT |AGC )',
            '(CC. )', '(AC. )', '(GC. )', '(TAT |TAC )', '(TAA |TAG |TGA )', '(CAT |CAC )',
            '(CAA |CAG )', '(AAT |AAC )', '(AAA |AAG )', '(GAT |GAC )', '(GAA |GAG )', '(TGT |TGC )',
            '(TGG )', '(CG. |AGA |AGG )', '(GG. )', '(\S\S\S )',
        );

        $mutGeneCoord = $row['coordinate'] - $row['Start'];
        $newseq = substr_replace($row['RNA_sequence'], $row['alternate'], $mutGeneCoord, 1);
        $temp = chunk_split($newseq, 3, ' ');
        $peptide = preg_replace($triplets, $aminoacids, $temp);
        $protSeq = (string) $row['protSeq'];
        $length = strlen($protSeq);
        if ($peptide === $protSeq) {
            return 'Synonymous change';
        }
        for ($index = 0; $index < $length; $index++) {
            $newAA = $peptide[$index];
            $canonAA = $protSeq[$index];
            if ($newAA === $canonAA) {
                continue;
            }
            if ($newAA === '*') {
                return 'Nonsense Variant';
            }
            return 'Missense Variant: p.' . $canonAA . ($index + 1) . $newAA;
        }
        return '';
    }
}

if (!function_exists('mutations_detail_display_protein')) {
    /**
     * @param array<string,mixed> $row
     */
    function mutations_detail_display_protein(array $row)
    {
        $hasGene = isset($row['Start']) && $row['Start'] !== null && $row['Start'] !== ''
            && isset($row['RNA_sequence']) && $row['RNA_sequence'] !== null && $row['RNA_sequence'] !== '';
        if (!$hasGene && (!isset($row['protein']) || $row['protein'] === null || $row['protein'] === '')) {
            return '-';
        }
        return (string) $row['protein'];
    }
}

if (!function_exists('mutations_detail_pango_text')) {
    /**
     * @param list<string> $lineages
     */
    function mutations_detail_pango_text(array $lineages)
    {
        $names = [];
        foreach ($lineages as $name) {
            $label = trim((string) $name);
            if ($label !== '') {
                $names[] = $label;
            }
        }
        return $names ? implode(', ', $names) : '';
    }
}

if (!function_exists('mutations_detail_csv_field')) {
    function mutations_detail_csv_field($value)
    {
        $value = (string) $value;
        if (strpbrk($value, ",\"\r\n") === false) {
            return $value;
        }
        return '"' . str_replace('"', '""', $value) . '"';
    }
}

if (!function_exists('mutations_detail_csv_filename')) {
    /**
     * @param array<string,mixed> $pack
     */
    function mutations_detail_csv_filename(array $pack)
    {
        $group = isset($pack['group_code']) && $pack['group_code'] !== ''
            ? $pack['group_code']
            : 'original';
        $safeGroup = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $group);
        $start = isset($pack['indel_start']) ? (int) $pack['indel_start'] : 1;
        $end = isset($pack['indel_end']) ? (int) $pack['indel_end'] : 29903;
        $minPct = isset($pack['min_percent']) ? $pack['min_percent'] : 1;
        $n = isset($pack['sample_count']) ? (int) $pack['sample_count'] : 0;
        $name = $safeGroup . '_n' . $n . '_' . $start . '-' . $end . '_minPct' . $minPct;
        if (!empty($pack['is_vcf'])) {
            $name .= '_minAf' . $pack['min_af'];
        }
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        return 'mutations_' . $name . '.csv';
    }
}

if (!function_exists('mutations_detail_visible_headers')) {
    /**
     * Columns shown in the Detail table (no Mean AF; SNAP2 is a button, not a data column).
     *
     * @return list<string>
     */
    function mutations_detail_visible_headers()
    {
        return [
            'Coordinate',
            'Reference Base',
            'Alternate Base',
            'Protein',
            'Amino Acid Change',
            'No. of Samples',
            '% Containing Mutation',
            'Pango lineages',
        ];
    }
}

if (!function_exists('mutations_detail_original_rows')) {
    /**
     * John's mutations table (Original view). Same grouping as MutationsDetail.php.
     *
     * @return list<array<string,mixed>>|null
     */
    function mutations_detail_original_rows(mysqli $con, $region, $referenceBase, $alternateBase, $instrument, $start, $end)
    {
        $q1 = '';
        if ($region != '') {
            $q1 .= " AND g.Protein = '" . $con->real_escape_string($region) . "'";
        }
        if ($referenceBase != '') {
            $q1 .= " AND m.reference = '" . $con->real_escape_string($referenceBase) . "'";
        }
        if ($alternateBase != '') {
            $q1 .= " AND m.alternate = '" . $con->real_escape_string($alternateBase) . "'";
        }
        if ($start != '' and $end == '') {
            $q1 .= ' AND m.coordinate >= ' . intval($start);
        }
        if ($start != '' and $end != '') {
            $q1 .= ' AND m.coordinate BETWEEN ' . intval($start) . ' AND ' . intval($end);
        }
        if ($start == '' and $end != '') {
            $q1 .= ' AND m.coordinate <= ' . intval($end);
        }
        if ($instrument != '') {
            $q1 .= " AND m.instrument = '" . $con->real_escape_string($instrument) . "'";
        }

        $sql = "SELECT
                    distinct m.reference, m.alternate,
                    m.coordinate, g.protein, g.domain, SUM(m.mutcount) no_of_samples, g.protSeq, g.RNA_sequence, g.Start
                FROM mutations m
                    INNER JOIN gene_1 g ON m.coordinate BETWEEN g.Start AND g.End
                WHERE 1=1 $q1
                GROUP BY m.reference, m.alternate,
                m.coordinate, g.protein, g.domain, g.protSeq, g.RNA_sequence, g.Start
                ORDER BY m.coordinate";
        $result = $con->query($sql);
        if (!$result) {
            return null;
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

if (!function_exists('mutations_detail_scope_parts')) {
    /**
     * @return array{text:string,indel_start:int,indel_end:int}
     */
    function mutations_detail_scope_parts(mysqli $con, $region, $start, $end)
    {
        $indelStart = 1;
        $indelEnd = 29903;
        if ($region != '') {
            $scopeText = 'Region: ' . $region;
            $stmt = $con->prepare('SELECT Start, End FROM gene_1 WHERE Protein = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $region);
                $stmt->execute();
                $coordLookup = $stmt->get_result();
                if ($coordLookup && $coordRow = $coordLookup->fetch_assoc()) {
                    $indelStart = (int) $coordRow['Start'];
                    $indelEnd = (int) $coordRow['End'];
                    $scopeText .= ' (genomic coordinates ' . $indelStart . '–' . $indelEnd . ')';
                }
                $stmt->close();
            }
        } elseif ($start != '' && $end != '') {
            $indelStart = (int) $start;
            $indelEnd = (int) $end;
            $scopeText = 'Genomic coordinates ' . $indelStart . '–' . $indelEnd;
        } elseif ($start != '') {
            $indelStart = (int) $start;
            $scopeText = 'Genomic coordinates ' . $indelStart . ' and above';
        } elseif ($end != '') {
            $indelEnd = (int) $end;
            $scopeText = 'Genomic coordinates up to ' . $indelEnd;
        } else {
            $scopeText = 'Full genome (coordinates 1–29903)';
        }
        return ['text' => $scopeText, 'indel_start' => $indelStart, 'indel_end' => $indelEnd];
    }
}

if (!function_exists('mutations_detail_load')) {
    /**
     * Filtered Detail rows plus the denominator/filter metadata used by HTML and CSV.
     *
     * @return array<string,mixed>|null
     */
    function mutations_detail_load(mysqli $con)
    {
        $region = isset($_GET['Region']) ? $_GET['Region'] : '';
        $referenceBase = isset($_GET['ReferenceBase']) ? $_GET['ReferenceBase'] : '';
        $alternateBase = isset($_GET['AlternateBase']) ? $_GET['AlternateBase'] : '';
        $instrument = isset($_GET['Instrument']) ? $_GET['Instrument'] : '';
        $start = isset($_GET['Start']) ? $_GET['Start'] : '';
        $end = isset($_GET['End']) ? $_GET['End'] : '';
        $minPercent = mutations_detail_request_min_percent();

        $vcfGroupCode = vcf_snv_request_group();
        $vcfMinAf = vcf_snv_request_min_af();
        $vcfPack = null;
        $isVcf = false;
        $hiddenGroup = false;
        if ($vcfGroupCode !== '') {
            if (!vcf_snv_group_is_visible($vcfGroupCode)) {
                $hiddenGroup = true;
            } else {
                $vcfPack = vcf_snv_detail_pack($con, $vcfGroupCode, $vcfMinAf, $start, $end, $region);
                $isVcf = $vcfPack !== null;
            }
        }

        if ($hiddenGroup || ($vcfGroupCode !== '' && !$isVcf)) {
            $scope = mutations_detail_scope_parts($con, $region, $start, $end);
            $reason = $hiddenGroup
                ? 'hidden by the Sep 17 workbook restriction'
                : 'not found';
            $scopeText = $scope['text'] . '; group ' . $vcfGroupCode . ' is ' . $reason
                . '. Original dataset was not used.';
            return [
                'rows' => [],
                'sample_count' => 0,
                'vcf_sample_count' => 0,
                'group_label' => $vcfGroupCode,
                'group_code' => $vcfGroupCode,
                'is_vcf' => true,
                'isolated' => false,
                'eligibility' => null,
                'show_snap2' => false,
                'min_af' => $vcfMinAf,
                'min_percent' => $minPercent,
                'region' => $region,
                'start' => $start,
                'end' => $end,
                'scope_text' => $scopeText,
                'indel_start' => $scope['indel_start'],
                'indel_end' => $scope['indel_end'],
                'query_error' => false,
            ];
        }

        if ($isVcf) {
            $result_rows = $vcfPack['rows'];
            $totalSamples = (int) $vcfPack['sample_count'];
            $groupLabel = $vcfPack['group_label'];
            $groupCode = $vcfGroupCode;
        } else {
            $result_rows = mutations_detail_original_rows(
                $con,
                $region,
                $referenceBase,
                $alternateBase,
                $instrument,
                $start,
                $end
            );
            if ($result_rows === null) {
                return null;
            }
            $totalSamples = 18900;
            $groupLabel = 'Original (John)';
            $groupCode = 'original';
        }

        $columns = array_column($result_rows, 'coordinate');
        if ($columns) {
            array_multisort($columns, SORT_ASC, $result_rows);
        }

        $pangoMap = snv_pango_all_map($con);
        $filtered_rows = [];
        foreach ($result_rows as $row) {
            $rawPercentage = $totalSamples > 0 ? ($row['no_of_samples'] / $totalSamples) * 100 : 0;
            if ($rawPercentage < $minPercent) {
                continue;
            }
            $pangoKey = snv_pango_key($row['coordinate'], $row['reference'], $row['alternate']);
            $pangoNames = isset($pangoMap[$pangoKey]) ? $pangoMap[$pangoKey] : [];
            $row['_rawPercentage'] = $rawPercentage;
            $row['_percentage'] = round($rawPercentage, 2);
            $row['_aa_change'] = mutations_detail_aa_change($row);
            $row['_protein'] = mutations_detail_display_protein($row);
            $row['_pango'] = $pangoNames;
            $row['_pango_text'] = mutations_detail_pango_text($pangoNames);
            $filtered_rows[] = $row;
        }

        $scope = mutations_detail_scope_parts($con, $region, $start, $end);
        $scopeText = $scope['text'];
        if ($minPercent > 0) {
            $scopeText .= '; minimum frequency ≥ ' . $minPercent . '%';
        }
        $scopeText .= '; group ' . $groupLabel . ' (n=' . (int) $totalSamples . ')';
        if ($isVcf) {
            $scopeText .= '; min AF ≥ ' . $vcfMinAf;
        }
        $visibleRows = count($filtered_rows);
        $scopeText .= '; showing ' . $visibleRows . ' mutation' . ($visibleRows === 1 ? '' : 's');

        return [
            'rows' => $filtered_rows,
            'sample_count' => (int) $totalSamples,
            'vcf_sample_count' => $isVcf ? (int) $vcfPack['vcf_sample_count'] : (int) $totalSamples,
            'group_label' => $groupLabel,
            'group_code' => $groupCode,
            'is_vcf' => $isVcf,
            'isolated' => $isVcf && !empty($vcfPack['isolated']),
            'eligibility' => $isVcf && !empty($vcfPack['eligibility']) ? $vcfPack['eligibility'] : null,
            'show_snap2' => !$isVcf,
            'min_af' => $vcfMinAf,
            'min_percent' => $minPercent,
            'region' => $region,
            'start' => $start,
            'end' => $end,
            'scope_text' => $scopeText,
            'indel_start' => $scope['indel_start'],
            'indel_end' => $scope['indel_end'],
            'query_error' => false,
        ];
    }
}

if (!function_exists('mutations_detail_csv_meta_line')) {
    /**
     * @param array<string,mixed> $pack
     */
    function mutations_detail_csv_meta_line(array $pack)
    {
        $bits = [
            'SARSNTDB mutations export',
            'group=' . $pack['group_label'],
            'group_code=' . $pack['group_code'],
            'samples=' . (int) $pack['sample_count'],
            'min_percent=' . $pack['min_percent'],
        ];
        if (!empty($pack['is_vcf'])) {
            $bits[] = 'min_af=' . $pack['min_af'];
            if (!empty($pack['isolated'])) {
                $bits[] = 'analyzed=not_applied';
            }
        }
        $bits[] = 'coordinates=' . $pack['indel_start'] . '-' . $pack['indel_end'];
        if ($pack['region'] !== '') {
            $bits[] = 'region=' . $pack['region'];
        }
        $bits[] = 'rows=' . count($pack['rows']);
        return '# ' . implode('; ', $bits);
    }
}

if (!function_exists('mutations_detail_csv_row_values')) {
    /**
     * @param array<string,mixed> $row
     * @return list<string|int|float>
     */
    function mutations_detail_csv_row_values(array $row)
    {
        return [
            $row['coordinate'],
            $row['reference'],
            $row['alternate'],
            $row['_protein'],
            $row['_aa_change'],
            $row['no_of_samples'],
            $row['_percentage'],
            $row['_pango_text'],
        ];
    }
}
