<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
session_start();
?>
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <style>
      .header {
          position: sticky;
          top:0;
      }
      .datagrid {
          width: 100%;
          max-height: 500px;
          overflow-y: scroll;
      }
      .repeatgrid {
        width: 100%;
        max-height: 500px;
        overflow-y: scroll;
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
      .table {
        text-align: left;
      }
    </style>
  </head>

  <?php
    // echo ("test");
    $gene=$protein=$start=$end="";
    /*if(isset($_GET['Gene'])){
        $gene = $_GET['Gene'];
    }*/
    if(isset($_GET['Protein'])){
        $protein = $_GET['Protein'];
    }
    if(isset($_GET['Start'])){
        $start = $_GET['Start'];
    }
    if(isset($_GET['End'])){
        $end = $_GET['End'];
    }
    $runRepeat = false;
    $q1 = "";
    $q2 = "";
    $q3 = "";
    $protSelected = false;
    $q4 = "";
    
    // Function to check if a string is a DNA sequence
    function isDNASequence($str) {
      if (empty($str)) return false;
      // Check if string contains only DNA bases (A, T, G, C) - case insensitive
      return preg_match('/^[ATGCatgc]+$/', $str);
    }
    
    // Function to find all occurrences of a sequence in the reference genome
    function findSequenceMatches($sequence, $referenceFile = './fastas/reference.fasta') {
      $matches = array();
      if (!file_exists($referenceFile)) {
        return $matches;
      }
      
      // Read reference sequence
      $refseq = "";
      $handle = fopen($referenceFile, "r");
      if ($handle) {
        while (($line = fgets($handle)) !== false) {
          if (!preg_match('/^>/', $line)) { // Skip header lines
            $refseq .= trim($line);
          }
        }
        fclose($handle);
      }
      
      if (empty($refseq)) {
        return $matches;
      }
      
      // Convert to uppercase for case-insensitive matching
      $sequenceUpper = strtoupper($sequence);
      $refseqUpper = strtoupper($refseq);
      
      // Find all occurrences using strpos in a loop
      $offset = 0;
      while (($pos = strpos($refseqUpper, $sequenceUpper, $offset)) !== false) {
        // Position is 1-based in the output (add 1)
        $matches[] = $pos + 1;
        $offset = $pos + 1; // Move offset to continue searching
      }
      
      return $matches;
    }
    
    // Handle DNA sequence searches
    $startIsSequence = isDNASequence($start);
    $endIsSequence = isDNASequence($end);
    $startMatches = array();
    
    if ($startIsSequence || $endIsSequence) {
      // Start session if not already started
      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }
      
      // If start is a sequence, find all matches
      if ($startIsSequence) {
        $startMatches = findSequenceMatches($start);
        if (!empty($startMatches)) {
          // Store all matches for display
          $_SESSION['sequence_matches'] = $startMatches;
          $_SESSION['search_sequence'] = $start;
          
          // Use the first match as start coordinate for gene/protein queries
          $start = $startMatches[0];
          $end = $startMatches[0] + strlen($start) - 1;
        } else {
          $start = ""; // No matches found
        }
      }
      
      // If end is a sequence, find all matches
      if ($endIsSequence && empty($startMatches)) {
        $endMatches = findSequenceMatches($end);
        if (!empty($endMatches)) {
          $_SESSION['sequence_matches'] = $endMatches;
          $_SESSION['search_sequence'] = $end;
          $end = $endMatches[0] + strlen($end) - 1;
        } else {
          $end = "";
        }
      }
    }
    /*if($gene != ""){
      $q1 .= " AND Gene_1.Gene LIKE'%" . $gene . "%'";
    } */
    // echo $protein;


    if($protein != ""){
      if (strpos($protein,"ORF") !== false and strpos($protein,"Protein") !==false ){
        
        $tmp_prot =explode(" ",$protein); 
        $tmp_prot = array_slice($tmp_prot, 0,1);
        $tmp_prot = array_pop($tmp_prot);
        // $lpro = strtolower($tmp_prot);
        // echo $tmp_prot;
      }else if (strpos($protein,"protein")){
        // echo "protein no orf";
        $tmp_prot = substr($protein, 0,1);
        $lpro = $tmp_prot . "protein";
      }else{
        $tmp_prot = strtolower($protein);
        // echo "-";
      }
      // echo gettype($tmp_prot);
      // print_r($tmp_prot);
      $q1 .= " AND Gene_1.Protein = '" . $protein . "'";
      $q2 .= " AND cov_comp.gene = '" . $tmp_prot . "'";
      $protSelected = true;

    }

    if($start != "" and $end == "") {
      $stminus = $start-15;
      $stplus = $start +15;

      $q1 .= " AND " . $start . " BETWEEN Gene_1.Start AND Gene_1.End";
      $q2 .= " AND " . $start . " BETWEEN cov_comp.cov2Start AND cov_comp.cov2End";
      $q3 .= " AND repeatcoord.coord BETWEEN '". $stminus  ."' AND '". $stplus  ."' "; 
      $q4 .= " AND " . $start . " BETWEEN intraGene.leftStart AND intraGene.leftEnd OR " . $start . " BETWEEN intraGene.rightStart AND intraGene.rightEnd";

    }

    if($start != "" and $end != "") {
      $q1 .= " AND (Gene_1.Start BETWEEN '" . $start . "' AND '" . $end . "' OR Gene_1.End BETWEEN '" . $start . "' AND '" . $end . "' OR ('" . $start . "' BETWEEN Gene_1.Start AND Gene_1.End) OR ('" . $end . "' BETWEEN Gene_1.Start AND Gene_1.End)) ";    
      $q2 .= " AND (cov_comp.cov2Start BETWEEN '" . $start . "' AND '" . $end . "' OR cov_comp.cov2End BETWEEN '" . $start . "' AND '" . $end . "') ";
      $q3 .= " AND repeatcoord.coord BETWEEN '". $start  ."' AND '". $end  ."' ";
      // if ((intval($end) - intval($start))<100){
      //   $runRepeat = true;
      // }

      $q4 .=  " AND (intraGene.leftStart BETWEEN '" . $start . "' AND '" . $end . "' OR intraGene.leftEnd BETWEEN '" . $start . "' AND '" . $end . "' OR ('" . $start . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd) OR ('" . $end . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd)) OR  (intraGene.rightStart BETWEEN '" . $start . "' AND '" . $end . "' OR intraGene.rightEnd BETWEEN '" . $start . "' AND '" . $end . "' OR ('" . $start . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd) OR ('" . $end . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd))";  
    }

    if($start == "" and $end != "") {
      $edminus = $end-15;
      $edplus = $end +15;
      $q1 .= " AND " . $end . " BETWEEN Gene_1.Start AND Gene_1.End";
      $q2 .= " AND " . $end . " BETWEEN cov_comp.cov2Start AND cov_comp.cov2End"; 
      $q3 .= " AND repeatcoord.coord BETWEEN '". $edminus  ."' AND '". $edplus  ."' "; 
      $q4 .= " AND " . $end . " BETWEEN intraGene.leftStart AND intraGene.leftEnd OR " . $end . " BETWEEN intraGene.rightStart AND intraGene.rightEnd";

    }

    require_once './connection.php';
    require_once './two_segment_helpers.php';
    $tsgInterval = tsg_interval_from_genome_params($start, $end);
    $tsgRowsOverlap = [];
    $tsgOverlapErr = null;
    if ($tsgInterval !== null) {
      $tsgQ = tsg_fetch_overlapping($con, $tsgInterval[0], $tsgInterval[1]);
      $tsgRowsOverlap = $tsgQ['rows'];
      $tsgOverlapErr = $tsgQ['error'];
    }

    #Gets Genes
    $sql = "SELECT Gene, Protein, Accession, Start, End, Function, matchedcols FROM Gene_1 WHERE 1=1 $q1 ORDER BY Gene_1.Start = 0, Gene_1.Start, Gene_1.Protein";
    $result = $con->query($sql);
    if (!$result) {
      echo ("query error firtst");
      exit();
    }
    $result_rows = $result->fetch_all(MYSQLI_ASSOC);
    $total = $result->num_rows;

    #Gets Domains
    $sqldom = "SELECT gene, feature, domainNameCov2, cov2Start, cov2End, cov2AAStartEnd FROM cov_comp WHERE 1=1 $q2 ORDER BY cov_comp.cov2Start + 0";
    $resultDom = $con->query($sqldom);
    if (!$resultDom) {
      echo $sqldom;
      exit();
    }

    
    $result_rowsDom = $resultDom->fetch_all(MYSQLI_ASSOC);
    $totalDom = $resultDom->num_rows;

    // $sqlIntraGene = "SELECT leftStart, rightStart, leftEnd, rightEnd, readSupport FROM intraGene WHERE 1=1 $q4 ORDER BY intragene.leftStart + 0";
    // $resultIntragene = $con->query($sqlIntraGene);
    // if (!$resultIntragene) {
    //   echo $sqlIntraGene;
    //   exit();
    // }
    // $result_rowsIntraGene = $resultIntragene->fetch_all(MYSQLI_ASSOC);
    // $totalIntraGene = $resultIntragene->num_rows;

  ?>

  <script type="text/javascript">

    var repeatBlock=document.getElementById("repeatBlock")
    var protTest = '<?php echo $runRepeat ;?>';


    if (protTest == true){
      repeatBlock.className.remove("repeatgrid");
      repeatBlock.className.add("datagrid");
      // console.log(<?php  echo $runRepeat; ?>);

    }else{
      repeatBlock.className.remove("datagrid");
      repeatBlock.className.add("repeatgrid");
    }

    
  </script>

  <body>
    <?php
    // Display sequence search results if available
    if (isset($_SESSION['sequence_matches']) && !empty($_SESSION['sequence_matches'])) {
      echo '<h4 class="search-header">Sequence Search Results</h4>';
      echo '<div class="datagrid">';
      echo '<table class="table">';
      echo '<thead><tr class="dark"><th>Sequence</th><th>Matches Found</th><th>Coordinates</th></tr></thead>';
      echo '<tbody><tr>';
      echo '<td>' . htmlspecialchars($_SESSION['search_sequence']) . '</td>';
      echo '<td>' . count($_SESSION['sequence_matches']) . '</td>';
      echo '<td>' . implode(', ', $_SESSION['sequence_matches']) . '</td>';
      echo '</tr></tbody></table>';
      echo '</div>';
      // Clear session variables
      unset($_SESSION['sequence_matches']);
      unset($_SESSION['search_sequence']);
    }
    ?>
    <h6 class="header-records">Number of records: <?= $total; ?></h6>
    <div class="datagrid">
      <table class='table' id="geneTable">
        <thead>
          <tr class="dark">
            <th class="header" width='10%'> </th>
            <th class="header" width='15%'>Gene</th>
            <th class="header" width='15%'>Protein</th>
            <th class="header" width='12%'>Accession</th>
            <th class="header" width='5%'>Start</th> 
            <th class="header" width='5%'>End</th>
            <th class="header" width='38%'>Description</th>
          </tr>
        </thead>
        <tbody>
          <?php
              $dataRowCounter = 0;
              $color1 = 'background-color:White';
              $color2 = 'background-color:LightGray';
              $prev_color = $color1;
              $test = "";
              foreach($result_rows as $row) {
                if ($protSelected== true){
                  $protst = $row['Start'];
                  $proted = $row['End'];
                  $q3 .= " AND repeatcoord.coord BETWEEN '".  $protst  ."' AND '".  $proted ."'";
                  $q4 .= " AND '".  $protst  ."' BETWEEN intraGene.rightStart AND intraGene.rightEnd OR  '".  $proted  ."' BETWEEN intraGene.rightStart AND intraGene.rightEnd OR '".  $protst  ."' BETWEEN intraGene.leftStart AND intraGene.leftEnd OR  '".  $proted  ."' BETWEEN intraGene.leftStart AND intraGene.leftEnd";
                }
                // echo $row["Protein"];
                $data = '';
                
                // if ($prev_color==$color1){
                //   $data.= "<tr style=".$color2.">" ;
                //   $prev_color=$color2;
                // } elseif ($prev_color==$color2){
                //   $data.= "<tr style=".$color1.">" ;
                //   $prev_color=$color1;
                // }
                $data.= "<tr style=".$color2.">" ;

                if ($row['Gene']=="ORF10" || $row['Gene']== "ORF7b" || $row['Protein']=="Nsp11"){
                  $data ='<td>Details not available currently</td>';
                }else{
                  $data ='<td><a onclick="getGeneDetails(\''.$row['Gene'].'\',\''.$row['Protein'].'\')">View Detail</a></td>';
                }
                // $data ='<td><a onclick="getGeneDetails(\''.$row['Gene'].'\',\''.$row['Protein'].'\')">View Detail</a></td>';
                $data.='<td>'.$row['Gene'].'</td>';

                $data.='<td>'.$row['Protein'].'</td>';
                $data.='<td><a href= "https://www.ncbi.nlm.nih.gov/protein/'.$row['Accession'].'" > '.$row['Accession'].'</a></td>';
                $data.='<td>'.(($row['Start']==0) ? "" : $row['Start']).'</td>';
                $data.='<td>'.(($row['End']==0) ? "" : $row['End']).'</td>';
                $data.='<td>'.$row['Function'].'</td></tr>';
                $test.= $row['Protein'];
                $dataRowCounter++;
                echo $data;
                // echo $row['Protein'];
                // echo strpos($row['Protein'],"protein");
                if (strpos($row['Protein'],"protein") !== false ){
                  if (strpos($row['Protein'],"ORF")){
                    $tmp_prot =explode(" ", $row['Protein']); 
                    $tmp_prot = array_slice($tmp_prot, 0,1);
                  }else{
                    // echo "protein no orf";
                    $tmp_prot = substr($row["Protein"], 0,1);

                  }

                }else{
                  // echo "else statemnt";
                }

                foreach($result_rowsDom as $rowDom) {
                  // $prev_color = $color1;
                  // echo $rowDom['Protein'];
                  $test.= "domainprot".$rowDom['gene']."////";
                  // echo "   test";

                  
                  if($row["matchedcols"] == $rowDom['gene']){
                    $test.= "domainprot".$rowDom['gene']."////";
                    $data = '';
                    $data.= "<tr style=".$color1.">" ;
                    $data.='<td></td>';
                    $data.='<td></td>';
                    $data.='<td>'.$rowDom['feature'].'</td>';
                    $data.='<td>'.$rowDom['domainNameCov2'].'</td>';
                    // $data.='<td>'.$rowDom['Accession'].'</td>';
                    $data.='<td>'.(($rowDom['cov2Start']==0) ? "" : $rowDom["cov2Start"]).'</td>';
                    $data.='<td>'.(($rowDom['cov2End']==0) ? "" : $rowDom["cov2End"]).'</td>';
                    $data.='<td>Domain found in '.$rowDom['gene'].'</td></tr>';
                    $dataRowCounter++;
                    echo $data;

                  }else {
                    // echo $tmp_prot;
                    // echo "--";
                    // echo $rowDom['gene'];
                    // echo "||||";
                  }

                }
              }
              // echo $test;
              
          ?>
        </tbody>
      </table>
    </div>

    <h4 class="search-header">Junction structures (sgmRNA)</h4>
    <p class="text-muted" style="margin-bottom:8px; font-size:12px;">Canonical (CJ) and non-canonical (NJ) junctions whose <strong>left</strong> or <strong>right</strong> gap border lies in your coordinate interval. Each row links to the same two-segment schematic view. Open the <a href="TwoSegmentStructures.php">full CJ/NJ table</a> for browsing without a genome interval.</p>
    <div class="datagrid" style="max-height:280px; margin-bottom:12px;">
      <table class="table">
        <thead>
          <tr class="dark">
            <th class="header" style="width:8%;">Kind</th>
            <th class="header" style="width:36%;">Name</th>
            <th class="header" style="width:8%;">from</th>
            <th class="header" style="width:8%;">left</th>
            <th class="header" style="width:8%;">right</th>
            <th class="header" style="width:8%;">to</th>
            <th class="header" style="width:14%;">repeat</th>
            <th class="header" style="width:10%;">Graphic</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($tsgInterval === null) {
            echo '<tr><td colspan="8" class="text-muted">Enter start and/or end coordinates (or a DNA sequence that resolves to coordinates) to list overlapping CJ/NJ junctions.</td></tr>';
          } elseif ($tsgOverlapErr) {
            $hint = (stripos($tsgOverlapErr, "doesn't exist") !== false || stripos($tsgOverlapErr, 'Unknown table') !== false)
              ? ' Import <code>sql/two_segment_structure.sql</code> (and run <code>sql/two_segment_structure_junction_kind.sql</code> if upgrading an older install).'
              : '';
            echo '<tr><td colspan="8" class="text-danger">' . htmlspecialchars($tsgOverlapErr, ENT_QUOTES, 'UTF-8') . $hint . '</td></tr>';
          } elseif (empty($tsgRowsOverlap)) {
            echo '<tr><td colspan="8" class="text-muted">No junction entries have left or right coordinates in this interval.</td></tr>';
          } else {
            foreach ($tsgRowsOverlap as $tsgRow) {
              $kind = htmlspecialchars(tsg_junction_kind_label($tsgRow), ENT_QUOTES, 'UTF-8');
              $nm = htmlspecialchars($tsgRow['name'], ENT_QUOTES, 'UTF-8');
              $rep = isset($tsgRow['repeat_seq']) && $tsgRow['repeat_seq'] !== ''
                ? htmlspecialchars($tsgRow['repeat_seq'], ENT_QUOTES, 'UTF-8') : '—';
              $id = (int) $tsgRow['id'];
              $viz = '<a href="' . htmlspecialchars(tsg_viz_url($id), ENT_QUOTES, 'UTF-8') . '">Schematic</a>';
              echo '<tr>';
              echo '<td>' . $kind . '</td><td>' . $nm . '</td>';
              echo '<td>' . (int) $tsgRow['coord_from'] . '</td><td>' . (int) $tsgRow['coord_left'] . '</td>';
              echo '<td>' . (int) $tsgRow['coord_right'] . '</td><td>' . (int) $tsgRow['coord_to'] . '</td>';
              echo '<td class="text-muted" style="font-family:Consolas,monospace;font-size:11px;">' . $rep . '</td><td>' . $viz . '</td>';
              echo '</tr>';
            }
          }
          ?>
        </tbody>
      </table>
    </div>
    
    <h4 class="search-header">Repeats</h4>
    <p class="text-muted" style="margin-bottom:8px; font-size:12px;">Repeats in this coordinate range. For full repeat search with genome visualization and location descriptions (Start Coordinates &amp; Location), use <a href="motifvisualizer.php">Search &rarr; Repeats</a>.</p>
  <div class="repeatgrid" id="repeatBlock" style="height:300px">
    <!-- <h4 class="search-header">Repeats</h4>  -->

    <table class='table'>
          <thead>
            <tr class="dark">
              <th class="header" width='10%'> </th>
              <th class="header" width='40%'>Repeat</th>
              <th class="header" width='20%'>Coordinate</th>
            </tr>

          </thead>
          <tbody>
              <?php

                
                // $q3 .= " AND repeatcoord.coord BETWEEN '".  $protst  ."' AND '".  $proted ."'";
                if ($q3) {
                  $sqlRepeat = "SELECT sequence,coord FROM repeatcoord WHERE 1=1 $q3 ORDER BY repeatcoord.coord + 0";
                  $resultRepeat  = $con->query($sqlRepeat);
                  if (!$resultRepeat) {
                    echo $sqlRepeat;
                    exit();
                  }
              
                  
                  $result_rowsRepeat = $resultRepeat->fetch_all(MYSQLI_ASSOC);
                  $totalRepeat = $resultRepeat->num_rows;
                  
                  foreach($result_rowsRepeat as $row) {
                    $data = '';
                    if ($prev_color==$color1){
                    $data.= "<tr style=".$color2.">" ;
                    $prev_color=$color2;
                  } elseif ($prev_color==$color2){
                    $data.= "<tr style=".$color1.">" ;
                    $prev_color=$color1;
                  }

                    $seqarray= explode (",", $row['sequence'] );

                    $seqAtagstring = "";
                    $counter = 0;
                    $seqlen= count($seqarray);
                    foreach($seqarray as $seq){

                      if ($seqlen == 1){
                        $seqAtagstring .= "<a href= 'motifvisualizer.php?repeat=".$seq."' > ".$seq."</a>";

                      }else if ($counter == $seqlen - 1){

                        $seqAtagstring .= "<a href= 'motifvisualizer.php?repeat=".$seq."' > ".$seq."</a>";
                      }else{
                        $seqAtagstring .= "<a href= 'motifvisualizer.php?repeat=".$seq."' > ".$seq."</a>,";

                      }
                      $counter +=1;
                    }
                    
                    
                    // $data.= "<tr style=".$color1.">" ;
                    $data.='<td></td>';
                    $data.='<td>'.$seqAtagstring.'</td>';
                  
                    $data.='<td>'.$row['coord'].'</td></tr>';
              
                    echo $data;

                  }
                }else{
                  $data = '';
                  $data.= "<tr style=".$color1.">" ;
                  $data.='<td></td>';
                  $data.='<td>Enter a start coordinate, end coordinate, or both to view Repeats</td>';
                  echo $data;
                }
              
              
              ?>


          </tbody>
          </table>
    </div>
    
    <h4 class="search-header">Intragenome RNA Interaction Regions</h4> 
    <div class="datagrid">
      
      <table class='table'>
            <thead>
              <tr class="dark">
                <th class="header" width='20%'></th>
                <th class="header" width='16%'>Left Hand Start</th>
                <th class="header" width='16%'>Left Hand End</th>
                <th class="header" width='16%'>Right Hand Start</th>
                <th class="header" width='16%'>Right Hand End</th>
                <th class="header" width='16%'>Read Support</th>
              </tr>

            </thead>
            <tbody>
                <?php
                                    
                    $sqlIntraGene = "SELECT leftStart, rightStart, leftEnd, rightEnd, readSupport FROM intraGene WHERE 1=1 $q4 ORDER BY intraGene.leftStart + 0";
                    $resultIntragene = $con->query($sqlIntraGene);
                    if (!$resultIntragene) {
                      echo $sqlIntraGene;
                      exit();
                    }
                    $result_rowsIntraGene = $resultIntragene->fetch_all(MYSQLI_ASSOC);
                    $totalIntraGene = $resultIntragene->num_rows;


                    foreach($result_rowsIntraGene as $row) {
                      $data = '';
                      if ($prev_color==$color1){
                      $data.= "<tr style=".$color2.">" ;
                      $prev_color=$color2;
                    } elseif ($prev_color==$color2){
                      $data.= "<tr style=".$color1.">" ;
                      $prev_color=$color1;
                    }
                      
                      
                      // $data.= "<tr style=".$color1.">" ;
                      if ($protSelected==true){
                        $linkRowIntra='<td> <a href="visualIntragenome.php?start='.$protst.'&end='.$proted.'&lStart='.$row['leftStart'].'&lEnd='.$row['leftEnd'].'&rStart='.$row['rightStart'].'&rEnd='.$row['rightEnd'].'"> Visualize </a></td>';
                      }else if($start != "" and $end == "") {
                        $linkRowIntra = '<td> <a href="visualIntragenome.php?start='.$start.'&lStart='.$row['leftStart'].'&lEnd='.$row['leftEnd'].'&rStart='.$row['rightStart'].'&rEnd='.$row['rightEnd'].'"> Visualize </a></td>';
                      }
                      else if($start != "" and $end != "") {
                        $linkRowIntra = '<td> <a href="visualIntragenome.php?start='. $start .'&end=' .$end. '&lStart=' .$row['leftStart']. '&lEnd=' .$row['leftEnd']. '&rStart='.$row['rightStart'].'&rEnd='.$row['rightEnd'].'"> Visualize </a></td>';
                      }
                      else if($start == "" and $end != "") {
                        $linkRowIntra = '<td> <a href="visualIntragenome.php?end='.$end.'&lStart='.$row['leftStart'].'&lEnd='.$row['leftEnd'].'&rStart='.$row['rightStart'].'&rEnd='.$row['rightEnd'].'"> Visualize </a></td>';
                      }else{
                        $linkRowIntra = '<td> <a href="visualIntragenome.php?start='.$start.'&end='.$end.'&lStart='.$row['leftStart'].'&lEnd='.$row['leftEnd'].'&rStart='.$row['rightStart'].'&rEnd='.$row['rightEnd'].'"> Visualize </a></td>';
                      }
                    
                      $data.= $linkRowIntra;
                      $data.='<td>'.$row['leftStart'].'</td>';
                      $data.='<td>'.$row['leftEnd'].'</td>';
                      $data.='<td>'.$row['rightStart'].'</td>';
                      $data.='<td>'.$row['rightEnd'].'</td>';
                    
                      $data.='<td>'.$row['readSupport'].'</td></tr>';
                
                      echo $data;

                    }
                  
                
                
                ?>


            </tbody>
            </table>
    </div> 
  </body>
</html>



