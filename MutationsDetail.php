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
    </style>
  </head>

  <?php
    $region = $referenceBase = $alternateBase = $instrument = $start = $end = "";
    if(isset($_GET['Region'])){
        $region = $_GET['Region'];
    }
    if(isset($_GET['ReferenceBase'])){
        $referenceBase = $_GET['ReferenceBase'];
    }
    if(isset($_GET['AlternateBase'])){
        $alternateBase = $_GET['AlternateBase'];
    }
    if(isset($_GET['Instrument'])){
        $instrument = $_GET['Instrument'];
    }
    if(isset($_GET['Start'])){
        $start = $_GET['Start'];
    }
    if(isset($_GET['End'])){
        $end = $_GET['End'];
    }

    $minPercent = 0;
    if(isset($_GET['MinPercent']) && $_GET['MinPercent'] !== '' && is_numeric($_GET['MinPercent'])){
        $minPercent = floatval($_GET['MinPercent']);
        if($minPercent < 0){
            $minPercent = 0;
        }
    }

    $q1 = "";

    if($region != ""){
    $q1 .= " AND g.Protein = '" . $region . "'";
    }

    if($referenceBase != ""){
    $q1 .= " AND m.reference = '" . $referenceBase . "'";
    }

    if($alternateBase != ""){
    $q1 .= " AND m.alternate = '" . $alternateBase . "'";
    }

    if($start != "" and $end == "") {
    $q1 .= " AND m.coordinate >= " . $start;
    }

    if($start != "" and $end != "") {
    $q1 .= " AND m.coordinate BETWEEN " . $start . " AND " . $end;
    }

    if($start == "" and $end != "") {
    $q1 .= " AND m.coordinate <= " . $end;
    }

    if($instrument != ""){
    $q1 .= " AND m.instrument = '" . $instrument . "'";
    }




    
    require_once('connection.php');
    $sql = "SELECT
                distinct m.reference, m.alternate,  
                m.coordinate, g.protein, g.domain, SUM(m.mutcount) no_of_samples, g.protSeq, g.RNA_sequence, g.Start
            FROM mutations m
                INNER JOIN Gene_1 g ON m.coordinate BETWEEN g.start AND g.end
            WHERE 1=1 $q1
            GROUP BY m.reference, m.alternate, 
            
            m.coordinate, g.protein, g.domain 
            ORDER BY m.coordinate";
    $result = $con->query($sql);
    // echo ($sql);

    
    if (!$result) {
      echo ($sql);
      echo ("query error");
      exit();
    }
    $result_rows = $result->fetch_all(MYSQLI_ASSOC);
    $total = $result->num_rows;

    $totalSamples = 18900;
    $columns = array_column($result_rows, 'coordinate');
    array_multisort($columns, SORT_ASC, $result_rows);
    $filtered_rows = [];
    foreach ($result_rows as $row) {
      $rawPercentage = ($row["no_of_samples"] / $totalSamples) * 100;
      if ($minPercent > 0 && $rawPercentage < $minPercent) {
        continue;
      }
      $row['_rawPercentage'] = $rawPercentage;
      $filtered_rows[] = $row;
    }

    if ($region != "") {
      $scopeText = "Region: " . htmlspecialchars($region, ENT_QUOTES, 'UTF-8');
      $coordLookup = $con->query("SELECT Start, End FROM Gene_1 WHERE Protein = '" . $con->real_escape_string($region) . "' LIMIT 1");
      if ($coordLookup && $coordRow = $coordLookup->fetch_assoc()) {
        $scopeText .= " (genomic coordinates " . (int)$coordRow['Start'] . "–" . (int)$coordRow['End'] . ")";
      }
    } elseif ($start != "" && $end != "") {
      $scopeText = "Genomic coordinates " . (int)$start . "–" . (int)$end;
    } elseif ($start != "") {
      $scopeText = "Genomic coordinates " . (int)$start . " and above";
    } elseif ($end != "") {
      $scopeText = "Genomic coordinates up to " . (int)$end;
    } else {
      $scopeText = "Full genome (coordinates 1–29903)";
    }
    if ($minPercent > 0) {
      $scopeText .= "; minimum frequency ≥ " . htmlspecialchars($minPercent, ENT_QUOTES, 'UTF-8') . "%";
    }
    $visibleRows = count($filtered_rows);
    $scopeText .= "; showing " . $visibleRows . " mutation" . ($visibleRows === 1 ? "" : "s");
  
  ?>
  <body>
    
    <script>

      function copyFunction(prot,seq) {
        // Get the text field
        var copyText = ">"+prot+"\n"+seq;

        // Select the text field
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices

        // Copy the text inside the text field
        navigator.clipboard.writeText(copyText.value);

        // Alert the copied text
        alert("Copied the text: " + copyText.value);
      }
    </script>
    <div style="padding: 8px 0; font-size: 12px;">
      <strong>Search scope:</strong> <?php echo $scopeText; ?>
      <span class="text-muted"> — Click a row to open primers ±800 bp around that SNV.</span>
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
            <th class="no-sort" width='30%'>Amino Acid Change</th>
            <th width='10%'>No. of Samples</th>
            <th width='10%'>%    Containing Mutation</th>
            <th width='10%'>SNAP2 Analysis</th>
            
          </tr>
        </thead>
        
        <tbody>
        
          <?php
              $color1 = 'background-color:White';
              $color2 = 'background-color:LightGray';
              $prev_color = $color1;
              $aminoacids=array("F","L","I","M","V","S","P","T","A","Y","*","H","Q","N","K","D","E","C","W","R","G","X");

              $triplets=array("(TTT |TTC )","(TTA |TTG |CT. )","(ATT |ATC |ATA )","(ATG )","(GT. )","(TC. |AGT |AGC )",
              "(CC. )","(AC. )","(GC. )","(TAT |TAC )","(TAA |TAG |TGA )","(CAT |CAC )",
              "(CAA |CAG )","(AAT |AAC )","(AAA |AAG )","(GAT |GAC )","(GAA |GAG )","(TGT |TGC )",
              "(TGG )","(CG. |AGA |AGG )","(GG. )","(\S\S\S )");

              $copyFasta = "";
              foreach($filtered_rows as $row) {
                $rawPercentage = $row['_rawPercentage'];

                $mutGeneCoord = $row['coordinate']-$row['Start'];
                $newseq = substr_replace($row['RNA_sequence'], $row['alternate'], $mutGeneCoord,1);

                $temp = chunk_split($newseq,3,' ');
                $peptide = preg_replace ($triplets, $aminoacids, $temp);
                $length = strlen($row['protSeq']);
                
                // $change="No change";

                if ($peptide == $row['protSeq']){
                  $change="Synonymous change";
                }else{
                  #checks for what kind of variant it is 
                  for ($index = 0; $index < $length; $index++) {
           
                    $newAA = $peptide[$index];                      
                    $canonAA=$row['protSeq'][$index];
                    
                    if ($newAA==$canonAA){

                    }else{
                      if ($newAA== "*"){
                        $change="Nonsense Variant";
                        break;
                      }else{
                        $change="Missense Variant: p.";
                        $change.=$canonAA;
                        $change.=$index+1;
                        // $change.="|||||||||||||||||";

                        $change.=$newAA;
                        // $change.="  |  ";

                        $orf1ansp = ['Nsp1',
                        'Nsp2',
                        'Nsp3',
                        'Nsp4',
                        'Nsp5',
                        'Nsp6',
                        'Nsp7',
                        'Nsp8',
                        'Nsp9',
                        'Nsp10',
                        'Nsp11',];
                        $orf1bnsp = ['Nsp12',
                        'Nsp13',
                        'Nsp14',
                        'Nsp15',
                        'Nsp16'];


                        $abbvProts = ["Surface Glycoprotein","Envelope Membrane Protein", "Membrane Protein","Nucleocapsid proteins"];

                        #below is not really used, I disabled the covarniant link as it doesnt work for 99% of mutations
                        if (in_array($row['protein'],$abbvProts)){
                          $tmp_prot = substr($row['protein'], 0,1);
                          $covariantlink=$tmp_prot;
                          $ind1 = $index+1;
                          $covariantlink.=".".$canonAA.$ind1;
                        }else if (in_array($row['protein'],$orf1ansp)){
                          $covariantlink="";
                        }else if (in_array($row['protein'],$orf1bnsp)){
                          $covariantlink="";
                        }else{
                          $tmp_prot = $row['protein'];
                          $covariantlink=$tmp_prot;
                          

                          $ind1 = $index+1;
                                                }
                          
                        // $covariantlink = "";
                        // $change.='<a href="https://covariants.org/variants/'.$covariantlink.'">Try your luck on Covariant</a>';
                        break;
                      }
                    }
  
                  }
                }

                // for ($index = 0; $index < $length; $index++) {
                  // $triplet = substr($newseq,$index,$index+3);
                  // $peptide = preg_replace ($triplets[$genetic_code], $aminoacids, $temp);

                // }
                $percentage= round($rawPercentage, 2);
                $data = '';
                if ($prev_color==$color1){
                  $data.= "<tr class=\"snv-primer-row\" style=".$color2.
                    " data-coord=\"".htmlspecialchars((string)$row['coordinate'], ENT_QUOTES, 'UTF-8').
                    "\" data-ref=\"".htmlspecialchars((string)$row['reference'], ENT_QUOTES, 'UTF-8').
                    "\" data-alt=\"".htmlspecialchars((string)$row['alternate'], ENT_QUOTES, 'UTF-8').
                    "\" data-protein=\"".htmlspecialchars((string)$row['protein'], ENT_QUOTES, 'UTF-8').
                    "\" title=\"Show primers ±800 bp around this SNV\">" ;
                  $prev_color=$color2;
                } elseif ($prev_color==$color2){
                  $data.= "<tr class=\"snv-primer-row\" style=".$color1.
                    " data-coord=\"".htmlspecialchars((string)$row['coordinate'], ENT_QUOTES, 'UTF-8').
                    "\" data-ref=\"".htmlspecialchars((string)$row['reference'], ENT_QUOTES, 'UTF-8').
                    "\" data-alt=\"".htmlspecialchars((string)$row['alternate'], ENT_QUOTES, 'UTF-8').
                    "\" data-protein=\"".htmlspecialchars((string)$row['protein'], ENT_QUOTES, 'UTF-8').
                    "\" title=\"Show primers ±800 bp around this SNV\">" ;
                  $prev_color=$color1;
                }
                $data.='<td>'.$row['coordinate'].'</td>';
                $data.='<td>'.$row['reference'].'</td>';
                $data.='<td>'.$row['alternate'].'</td>';
                //$data.='<td>'.$row['instrument'].'</td>';
                $data.='<td>'.$row['protein'].'</td>';
                $data.='<td>'.$change.'</td>';
                $data.='<td>'.$row['no_of_samples'].'</td>';
                $data.='<td>'.$percentage.'</td>';
                $data.= '<td><button onclick="copyFunction(\''.$row['protein'].'\',\''.$row['protSeq'].'\')">SNAP2</button> </td>';
              
                $data.='</tr>';
                echo $data;
              }

              if ($visibleRows === 0 && $minPercent > 0) {
                echo "<tr><td colspan='8'>No mutations found at or above " . htmlspecialchars($minPercent, ENT_QUOTES, 'UTF-8') . "% frequency for the selected scope.</td></tr>";
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
        alert("Copied the text: " + copyText);
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
        if (ref) { url += '&ref=' + encodeURIComponent(ref); }
        if (alt) { url += '&alt=' + encodeURIComponent(alt); }
        if (protein) { url += '&protein=' + encodeURIComponent(protein); }
        window.open(url, '_blank');
      });
    </script>
</html>
