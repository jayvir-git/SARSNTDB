<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="bootstrap.css" />
    
    
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <script src="./sortable.js"></script>
    <style>
      .header {
          position: sticky;
          top:0;
      }
      .datagrid {
          width: 100%;
          height: 500px;
          overflow: auto;
      }
      tr.dark th{
        background: #333;
        color: white;
      }
      th{
        position: sticky;
        top: 0;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
      }
      tr.snv-primer-row {
        cursor: pointer;
      }
      tr.snv-primer-row:hover td {
        outline: 1px solid #5cb85c;
      }
      table.sortable {
        border-collapse: collapse;
        width: 100%;
      }
      table.sortable th,
      table.sortable td {
        padding: 6px 8px;
        vertical-align: top;
        border-right: 1px solid #bbb;
      }
      table.sortable th:last-child,
      table.sortable td:last-child {
        border-right: none;
      }
      th.snv-pango-col,
      td.snv-pango-col {
        min-width: 220px;
        max-width: 320px;
        padding-right: 16px;
        border-right: 3px solid #222;
      }
      th.snv-pango-col {
        white-space: nowrap;
      }
      th.snv-snap2-col,
      td.snv-snap2-col {
        min-width: 130px;
        padding-left: 16px;
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
        border-left: 3px solid #222;
      }
      .snv-pango-list {
        display: block;
        font-size: 11px;
        line-height: 1.45;
      }
      .snv-pango-item {
        white-space: nowrap;
        margin: 0 0 3px 0;
      }
    </style>
  </head>

  <?php
    require_once('connection.php');
    require_once __DIR__ . '/snv_pango_helpers.php';
    require_once __DIR__ . '/mutations_detail_helpers.php';
    mysqli_report(MYSQLI_REPORT_OFF);
    $pack = mutations_detail_load($con);
    if ($pack === null) {
      echo ' query error: ' . htmlspecialchars($con->error, ENT_QUOTES, 'UTF-8');
      exit();
    }
    $filtered_rows = $pack['rows'];
    $minPercent = $pack['min_percent'];
    $showSnap2 = $pack['show_snap2'];
    $visibleRows = count($filtered_rows);
    $scopeText = htmlspecialchars($pack['scope_text'], ENT_QUOTES, 'UTF-8');
    $emptyCols = $showSnap2 ? 9 : 8;
    $pangoRangeUrl = 'PangoMarkers.php?Start=' . (int) $pack['indel_start'] . '&End=' . (int) $pack['indel_end'];
  ?>
  <body>
    <div style="padding: 8px 0; font-size: 12px;">
      <strong>Search scope:</strong> <?php echo $scopeText; ?>
      <span class="text-muted"> — Click a row to open primers ±800 bp around that SNV. Pango lineages on a row are published designation markers matching that SNV (single-base, ratio &lt; 0.2); they are not counts from this group. Full SNV and indel marker tables: <a href="<?php echo htmlspecialchars($pangoRangeUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Pango markers</a> for this coordinate range. Percentages use n=<?php echo (int) $pack['sample_count']; ?> analyzed samples in this group.</span>
    </div>
    <div class="datagrid">
      <table class='sortable' >
        <thead>
          <tr class="dark">
            <th  width='12%'>Coordinate</th>
            <th width='12%'>Reference Base</th>
            <th width='12%'>Alternate Base</th>
            <!--<th class="header" width='12%'>Instrument</th>-->
            
            <th class="no-sort" width='12%'>Protein</th>
            <th class="no-sort" width='18%'>Amino Acid Change</th>
            <th width='10%'>No. of Samples</th>
            <th width='10%' title="Share of <?php echo (int) $pack['sample_count']; ?> samples in <?php echo htmlspecialchars($pack['group_label'], ENT_QUOTES, 'UTF-8'); ?>">% Containing Mutation</th>
            <th class="no-sort snv-pango-col">Pango lineages</th>
            <?php if ($showSnap2) : ?>
            <th class="no-sort snv-snap2-col">Copy protein FASTA</th>
            <?php endif; ?>
            
          </tr>
        </thead>
        
        <tbody>
        
          <?php
              $color1 = 'background-color:White';
              $color2 = 'background-color:LightGray';
              $prev_color = $color1;
              foreach($filtered_rows as $row) {
                $change = $row['_aa_change'];
                $percentage = $row['_percentage'];
                $protein = $row['_protein'];
                $rowStyle = ($prev_color === $color1) ? $color2 : $color1;
                $prev_color = $rowStyle;
                $data = "<tr class=\"snv-primer-row\" style=".$rowStyle.
                  " data-coord=\"".htmlspecialchars((string)$row['coordinate'], ENT_QUOTES, 'UTF-8').
                  "\" data-ref=\"".htmlspecialchars((string)$row['reference'], ENT_QUOTES, 'UTF-8').
                  "\" data-alt=\"".htmlspecialchars((string)$row['alternate'], ENT_QUOTES, 'UTF-8').
                  "\" data-protein=\"".htmlspecialchars((string)$protein, ENT_QUOTES, 'UTF-8').
                  "\" title=\"Show primers ±800 bp around this SNV\">";
                $data.='<td>'.htmlspecialchars((string)$row['coordinate'], ENT_QUOTES, 'UTF-8').'</td>';
                $data.='<td>'.htmlspecialchars((string)$row['reference'], ENT_QUOTES, 'UTF-8').'</td>';
                $data.='<td>'.htmlspecialchars((string)$row['alternate'], ENT_QUOTES, 'UTF-8').'</td>';
                $data.='<td>'.htmlspecialchars((string)$protein, ENT_QUOTES, 'UTF-8').'</td>';
                $data.='<td>'.htmlspecialchars((string)$change, ENT_QUOTES, 'UTF-8').'</td>';
                $data.='<td>'.htmlspecialchars((string)$row['no_of_samples'], ENT_QUOTES, 'UTF-8').'</td>';
                $data.='<td>'.htmlspecialchars((string)$percentage, ENT_QUOTES, 'UTF-8').'</td>';
                $data.='<td class="snv-pango-col">'.snv_pango_html_list($row['_pango']).'</td>';
                if ($showSnap2) {
                  $protAttr = htmlspecialchars((string)$protein, ENT_QUOTES, 'UTF-8');
                  $seqAttr = htmlspecialchars((string)$row['protSeq'], ENT_QUOTES, 'UTF-8');
                  $data.= '<td class="snv-snap2-col"><button type="button" class="snv-copy-fasta" data-prot="'.$protAttr.'" data-seq="'.$seqAttr.'" title="Copies the canonical protein FASTA. Does not run SNAP2 analysis.">Copy FASTA</button></td>';
                }
                $data.='</tr>';
                echo $data;
              }

              if ($visibleRows === 0 && $minPercent > 0) {
                echo "<tr><td colspan='" . $emptyCols . "'>No mutations found at or above " . htmlspecialchars((string)$minPercent, ENT_QUOTES, 'UTF-8') . "% frequency for the selected scope.</td></tr>";
              }
          ?>

        </tbody>
      </table>
    </div>
  </body>
  <script>
      function copyFunction(prot,seq) {
        var copyText = ">"+prot+"\n"+seq;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(copyText);
        }
        alert("Copied the canonical protein FASTA for " + prot + ". SNAP2 is not run from this page.");
      }
      document.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('button')) {
          return;
        }
        var row = e.target.closest ? e.target.closest('tr.snv-primer-row') : null;
        if (!row) {
          return;
        }
        var coord = row.getAttribute('data-coord');
        if (!coord) {
          return;
        }
        var url = 'SnvPrimerView.php?coord=' + encodeURIComponent(coord);
        var ref = row.getAttribute('data-ref');
        var alt = row.getAttribute('data-alt');
        var protein = row.getAttribute('data-protein');
        var kind = row.getAttribute('data-kind');
        if (ref) { url += '&ref=' + encodeURIComponent(ref); }
        if (alt) { url += '&alt=' + encodeURIComponent(alt); }
        if (protein) { url += '&protein=' + encodeURIComponent(protein); }
        if (kind) { url += '&kind=' + encodeURIComponent(kind); }
        window.open(url, '_blank');
      });
    </script>
</html>
