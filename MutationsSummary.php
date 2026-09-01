<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>

<?php
    require_once 'MutationsInfo.php';
    $region = $referenceBase = $alternateBase = $instrument = $start = $end = "";
    $instrumentALLTotal = $altBaseTempPercentValue = 0;
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

    $q1 = $q2 = $q3 = $q4 = "";
    $selection = '';
    

    if($referenceBase != ""){
      $q1 .= " AND m.reference = '" . $referenceBase . "'";
    }

    if($alternateBase != ""){
      $q1 .= " AND m.alternate = '" . $alternateBase . "'";
    }

    if($start != "" and $end == "") {
      $q1 .= " AND m.coordinate >= " . $start;
      $q4 .= " AND irs.coordinate >= " . $start;
      $selection = 'coordinates';
    }

    if($start != "" and $end != "") {
      $q1 .= " AND m.coordinate BETWEEN " . $start . " AND " . $end;
      $q4 .= " AND irs.Coordinate BETWEEN " . $start . " AND " . $end;
      $selection = 'coordinates';
    }
    
    if($start == "" and $end != "") {
      $q1 .= " AND m.coordinate <= " . $end;
      $q4 .= " AND irs.Coordinate <= " . $end;

      $selection = 'coordinates';
    }
    if($region != "" and $selection == ""){
      $q1 .= " AND g.Protein = '" . $region . "'";
      $q3 .= "INNER JOIN Gene_1 g ON m.coordinate BETWEEN g.start AND g.end";
      $q4 .= " AND g.Protein = '" . $region . "'";
      $selection = 'region';
    }
    if($instrument != ""){
      $q1 .= " AND m.instrument = '" . $instrument . "'";
    }
    else {
      $q2 = " UNION
              SELECT
                  reference, alternate, 'ALL' instrument, sum(mutcount) as coordinate_count
              FROM mutations m $q3
              WHERE 1=1 $q1
              GROUP BY reference, alternate ";
    }

    require_once('./connection.php');
    $sql = "SELECT
              reference,
              instrument,
              MAX(alternate_A) alternate_A,
              MAX(alternate_C) alternate_C,
              MAX(alternate_G) alternate_G,
              MAX(alternate_T) alternate_T,
              MAX(alternate_A) + MAX(alternate_C) + MAX(alternate_G) + MAX(alternate_T) alternate_total
            FROM
              (SELECT
                reference,
                instrument,
                CASE WHEN alternate = 'A' THEN coordinate_count ELSE 0 END alternate_A,
                CASE WHEN alternate = 'C' THEN coordinate_count ELSE 0 END alternate_C,
                CASE WHEN alternate = 'G' THEN coordinate_count ELSE 0 END alternate_G,
                CASE WHEN alternate = 'T' THEN coordinate_count ELSE 0 END alternate_T
              FROM
                (
                        SELECT
                            reference, alternate, instrument, sum(mutcount) as coordinate_count
                        FROM mutations m $q3
                        WHERE 1=1 $q1
                        GROUP BY reference, alternate, instrument
                        $q2
                ) m1
              ) m2
            GROUP BY reference, instrument
            ORDER BY reference, instrument";
    $result = $con->query($sql);
    // echo ($sql);
    if (!$result) {
      echo ($sql);
      echo ("query 1 error");
      exit();
    }
    $result_rows = $result->fetch_all(MYSQLI_ASSOC);
    $total = $result->num_rows;

    $illumina_miseq = $illumina_novaseq_6000 = $nextseq_500 = $nextseq_550 = $illumina_hiseq_2500 = $minion = $BGI_MGISEQ_2000 = array();
    foreach($result_rows as $row) {
      // echo '<pre>'; print_r($row); echo '</pre>';
        if(strtoupper($row['instrument']) == "ILLUMINA_MISEQ") {
          $row['reference'] != "A" ? array_push($illumina_miseq, array("label" => $row['reference']."-A", "y" => intval( $row['alternate_A'] ))) : "";
          $row['reference'] != "C" ? array_push($illumina_miseq, array("label" => $row['reference']."-C", "y" => intval( $row['alternate_C'] ))) : "";
          $row['reference'] != "G" ? array_push($illumina_miseq, array("label" => $row['reference']."-G", "y" => intval( $row['alternate_G'] ))): "";
          $row['reference'] != "T" ? array_push($illumina_miseq, array("label" => $row['reference']."-T", "y" => intval( $row['alternate_T']))) : "";
        } else if (strtoupper($row['instrument']) == "ILLUMINA_NOVASEQ_6000") {
          $row['reference'] != "A" ? array_push($illumina_novaseq_6000, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($illumina_novaseq_6000, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($illumina_novaseq_6000, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($illumina_novaseq_6000, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
        } else if (strtoupper($row['instrument']) == "NEXTSEQ_500") {
          $row['reference'] != "A" ? array_push($nextseq_500, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($nextseq_500, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($nextseq_500, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($nextseq_500, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
        } else if (strtoupper($row['instrument']) == "NEXTSEQ_550") {
          $row['reference'] != "A" ? array_push($nextseq_550, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($nextseq_550, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($nextseq_550, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($nextseq_550, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
        } else if (strtoupper($row['instrument']) == "ILLUMINA_HISEQ_2500") {
          $row['reference'] != "A" ? array_push($illumina_hiseq_2500, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($illumina_hiseq_2500, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($illumina_hiseq_2500, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($illumina_hiseq_2500, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
        } else if (strtoupper($row['instrument']) == "MINION") {
          $row['reference'] != "A" ? array_push($minion, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($minion, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($minion, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($minion, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
        } else if (strtoupper($row['instrument']) == "BGI_MGISEQ_2000") {
          $row['reference'] != "A" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
        } 
    }

    $sql2 = "SELECT g.Protein, m.coordinate, g.Start, g.End, SUM(m.mutcount) as frequency
              FROM mutations m
              left outer join Gene_1 g on m.coordinate between g.Start and g.End
             where 1=1 $q1
              group by g.Protein, g.Start, g.End, m.coordinate";

    $result2 = $con->query($sql2);
    // echo ($sql2);

    if (!$result2) {

      echo ($sql2);
      echo ("query 2 error");
      exit();
    }

    $result2_rows = $result2->fetch_all(MYSQLI_ASSOC);
    if($selection == 'region') {
      foreach($result2_rows as $row) {
        $xAxisGraphStart = intval($row['Start']);
        $xAxisGraphEnd = intval($row['End']);
        $numOfSections = 30;
        
        if(($xAxisGraphEnd-$xAxisGraphStart)>=1000){
          if (($xAxisGraphEnd-$xAxisGraphStart)>=10000){
            $gap = 100;
          }else{
            $gap = 30;
          }

        }elseif(($xAxisGraphEnd-$xAxisGraphStart)<=1000 && ($xAxisGraphEnd-$xAxisGraphStart) >=100){
          $gap=10;
        }elseif(($xAxisGraphEnd-$xAxisGraphStart)<=100){
          $gap = 1;
          
        }
        break;
      }
    } else if($selection == '') {
      $xAxisGraphStart = 0;
      $xAxisGraphEnd = 30000;
      $numOfSections = 300;
      $gap=100;

    } else {
      $xAxisGraphStart = intval($start);
      $xAxisGraphEnd = intval($end);
      // $numOfSections = 30;
      if(($xAxisGraphEnd-$xAxisGraphStart)>=1000){
        if (($xAxisGraphEnd-$xAxisGraphStart)>=10000){
          $gap = 100;
        }else{
          $gap = 30;
        }

      }elseif(($xAxisGraphEnd-$xAxisGraphStart)<=1000 && ($xAxisGraphEnd-$xAxisGraphStart) >=100){
        $gap=10;
      }elseif(($xAxisGraphEnd-$xAxisGraphStart)<=100){
        $gap = 1;
      }
      // break;
    }   

    
    // if($lenInterval>1000){
    //   if ($lenInterval>10000){
    //     $gap = 100;
    //   }else{
    //     $gap = 30;
    //   }

    // }elseif($lenInterval<1000 && $lenInterval >=100){
    //   $gap=10;
    // }elseif($lenInterval<100){
    //   $gap = 1;
    // }


    $numOfSections = ceil(($xAxisGraphEnd-$xAxisGraphStart)/$gap);
    // $gap = 30;
    $pq = '';
    $pqwt = '';
    $pqDelta = '';
    $pqGSE153984 = '';
    $lowerBound = $xAxisGraphStart;
    $upperBound = ($xAxisGraphStart + $gap) - 1;
    if($gap==1){
      $numOfSections=     $numOfSections +1;
      $upperBound = ($xAxisGraphStart + $gap);
    };


    // echo $lowerBound;
    // echo $upperBound;
    // echo $gap;
    for($k = $lowerBound; $k <= $xAxisGraphEnd; ) {     
      
      $pq .= "avg (case when coordinate BETWEEN $lowerBound and $upperBound
                 then shapeval end) '($lowerBound - $upperBound)' ";

      $pqwt.= "avg (case when coordinate BETWEEN $lowerBound and $upperBound
      then WTSHAPE end) '($lowerBound - $upperBound)'";

      $pqDelta.= "avg (case when coordinate BETWEEN $lowerBound and $upperBound
      then DELTA end) '($lowerBound - $upperBound)'";
      
      $pqGSE153984.= "avg (case when coordinate BETWEEN $lowerBound and $upperBound
      then GSE153984 end) '($lowerBound - $upperBound)'";
      
      if(!($k > ($xAxisGraphEnd-$gap) && $k <= $xAxisGraphEnd)  ) 
      {
          $pq .= ",\r\n";
          $pqwt .= ",\r\n";
          $pqDelta .= ",\r\n";
          $pqGSE153984 .= ",\r\n";
      }       
      $lowerBound = $upperBound + 1;
      $k = $k + $gap;

      if ($gap==1){
        $upperBound+=1;
      }else{
        $upperBound = ($k > ($xAxisGraphEnd-$gap) && $k <= $xAxisGraphEnd) ? $xAxisGraphEnd : ($k - 1);

      }
    }

    //$pq = substr($pq, 0, -1);

    $sql3 = "SELECT $pq FROM shapedata irs 
                inner join Gene_1 g on irs.Coordinate between g.Start and g.End
              where 1=1 $q4 and irs.shapeval is not null";
              
    // //console.log($sql3) and irs.icshape_score is not null;
    // echo ($sql3);
    $result3 = $con->query($sql3);
    if (!$result3) {

      echo ($sql3);
      echo ("query 3 error");
      exit();
    }
    $result3_rows = $result3->fetch_all(MYSQLI_ASSOC);

    $sql3wt = "SELECT $pqwt FROM shapedata irs 
                inner join Gene_1 g on irs.Coordinate between g.Start and g.End
              where 1=1 $q4 and irs.shapeval is not null";

    // //console.log($sql3) and irs.icshape_score is not null;
    // echo ($sql3);
    $result3wt = $con->query($sql3wt);
    if (!$result3wt) {

      echo ($sql3wt);
      echo ("query 3 wt error");
      exit();
    }
    $result3wt_rows = $result3wt->fetch_all(MYSQLI_ASSOC);



    $sql3Delta = "SELECT $pqDelta FROM shapedata irs 
                inner join Gene_1 g on irs.Coordinate between g.Start and g.End
              where 1=1 $q4 and irs.shapeval is not null";


    $result3Delta = $con->query($sql3Delta);
    if (!$result3Delta) {

      echo ($sql3Delta);
      echo ("query 3Delta error");
      exit();
    }
    $result3DELTA_rows = $result3Delta->fetch_all(MYSQLI_ASSOC);



    $sql3GSE153984 = "SELECT $pqGSE153984 FROM shapedata irs 
                inner join Gene_1 g on irs.Coordinate between g.Start and g.End
              where 1=1 $q4 and irs.shapeval is not null";

    // //console.log($sql3) and irs.icshape_score is not null;
    // echo ($sql3GSE153984);
    $result3GSE153984 = $con->query($sql3GSE153984);
    if (!$result3GSE153984) {

      echo ($sql3GSE153984);
      echo ("query 3GSE153984 error");
      exit();
    }
    $result3GSE153984_rows = $result3GSE153984->fetch_all(MYSQLI_ASSOC);



    $data = '';
    $data .= ' <table class="table">';
    $data .= '  <colgroup>';
    $data .= '    <col style="width: 5%">';
    $data .= '    <col style="width: 10%">';
    $data .= '    <col style="width: 25%">';
    $data .= '    <col style="width: 12%">';
    $data .= '    <col style="width: 12%">';
    $data .= '    <col style="width: 12%">';
    $data .= '    <col style="width: 12%">';
    $data .= '    <col style="width: 12%">';
    $data .= '  </colgroup>';
    $data .= '  <thead>';
    $data .= '    <tr class="darkheader">';
    $data .= '      <th colspan="3">Reference Base</th>';
    //$data .= '      <th rowspan="2" scope="rowgroup" width="25%">Instrument</th>';
    if($alternateBase != "") {
      $data .= '    <th>Alternate Base</th>';
    } else {
      $data .= '    <th colspan="5" scope="colgroup">Alternate Base</th>';
    }
    $data .= '    </tr>';
    $data .= '  </thead>';
    $data .= '  <tbody>';
    $data .= '    <tr class="greyheader">';
    $data .= '      <th scope="row" colspan="2"> </th>';
    $data .= '      <th scope="row">Instrument</th>';
    if($alternateBase == "" || $alternateBase == "A" ) {
      $data .=  '   <th scope="row">A</th>';
    }
    if($alternateBase == "" || $alternateBase == "C" ) {
      $data .= '    <th scope="row">C</th>';
    }
    if($alternateBase == "" || $alternateBase == "G" ) {
      $data .= '    <th scope="row">G</th>';
    }
    if($alternateBase == "" || $alternateBase == "T" ) {
      $data .= '    <th scope="row">T</th>';
    }
    if($alternateBase == "") {
      $data .= '    <th scope="row">Total</th>';
    }
    $data .= '    </tr>';
    $prev_ref_base = $curr_ref_base = "";

    foreach($result_rows as $row) {
      $curr_ref_base = $row['reference'];
      if ($row['instrument'] == "ALL") {
        $data.='   <tr class="grey">';
        $instrumentALLTotal = $row['alternate_total'];
      } else {
        if($instrument == ""){
          $data.='   <tr style="display:none;" name="'.$row['reference'].'_instruments">';
        } else {
          $data.='   <tr>';
        }
      }


      if ($prev_ref_base == "" || $prev_ref_base != $curr_ref_base) {
        $prev_ref_base = $row['reference'];
        if($instrument == ""){
          $data.='  <td class="rowexpand" style="width: 5%;cursor: pointer;" id="'.$row['reference'].'_total" onclick="showOrHideInstruments(\''.$row['reference'].'_total\',\''.$row['reference'].'_instruments\')">+</td>';
        } else {
          $data.='  <td style="width: 5%">&nbsp;</td>';
        }
        $data.='  <td style="width: 10%">'.$row['reference'].'</td>';
      }
      else {
        $data.='  <td style="width: 5%">&nbsp;</td>';
        $data.='  <td style="width: 10%">&nbsp;</td>';
      }
      $data.='      <td style="width: 25%" scope="row">'.$row['instrument'].'</td>';
      if($alternateBase == "" || $alternateBase == "A" ) {
        $altBaseTempPercentValue = round($row['alternate_A'] / $row['alternate_total'], 4) * 100;
        $data.='    <td style="width: 12%">'.(($row['alternate_A']==0) ? "" : $altBaseTempPercentValue). ($altBaseTempPercentValue == 0 ? '': '%') .'</td>';
      }
      if($alternateBase == "" || $alternateBase == "C" ) {
        $altBaseTempPercentValue = round($row['alternate_C'] / $row['alternate_total'], 4) * 100;
        $data.='    <td style="width: 12%">'.(($row['alternate_C']==0) ? "" : $altBaseTempPercentValue). ($altBaseTempPercentValue == 0 ? '': '%') .'</td>';
      }
      if($alternateBase == "" || $alternateBase == "G" ) {
        $altBaseTempPercentValue = round($row['alternate_G'] / $row['alternate_total'], 4) * 100;
        $data.='    <td style="width: 12%">'.(($row['alternate_G']==0) ? "" : $altBaseTempPercentValue). ($altBaseTempPercentValue == 0 ? '': '%') .'</td>';
      }
      if($alternateBase == "" || $alternateBase == "T" ) {
        $altBaseTempPercentValue = round($row['alternate_T'] / $row['alternate_total'], 4) * 100;
        $data.='    <td style="width: 12%">'.(($row['alternate_T']==0) ? "" : $altBaseTempPercentValue). ($altBaseTempPercentValue == 0 ? '': '%') .'</td>';
      }
      if($alternateBase == "") {
        if ($row['instrument'] == "ALL") {
          $data.='    <td style="width: 12%">'.(($row['alternate_total']==0) ? "" : $row['alternate_total'] ).'</td>';
        } else {
          $data.='    <td style="width: 12%">'.(($row['alternate_total']==0) ? "" : round($row['alternate_total'] / $instrumentALLTotal, 4) * 100 ).'%</td>';
        }

      }
      $data.='    </tr>';
    }
    $data .= '  </tbody>';
    $data .= '</table>';


    $A = $B = $BWT = $BDELTA = $BGSE153984 = $C = $D = array();

    $counter = 0;
    // $numOfSections = 30;
    $interval = floor( (intval($xAxisGraphEnd) - intval($xAxisGraphStart))/ $numOfSections ) ;
    $interval=$gap;
    if ($interval ==0){
      $interval=1;
    }
    $xAxisLabel = '';
    $barStart = $xAxisGraphStart-1;
    $barEnd = 0;
    $subSectionTotal = 0;
    $coordWidth = 0;

    // echo $interval;
    for($i = 0; $i < $numOfSections; $i++) {
      $tmpObj = new FreqSubSectionInfo();
      
      if ($interval==1){
        $barStart+=1;
        $barEnd = $barStart;
      }else{
        $barStart = ($i == 0 ?  $xAxisGraphStart: ($barStart + $interval ));

        $barEnd = ($barStart + $interval) > $xAxisGraphEnd ? $xAxisGraphEnd : ($barStart + $interval)-1;

      }
      // echo $interval;
      // echo $barStart;
      // echo "-";
      // echo $barEnd;
      // echo "  ||||  ";
      foreach($result2_rows as $row) 
      { 
        if ($interval ==1){
          // $barStart+=1;
          // $barEnd = $barStart;
          if(intval($row['coordinate']) == $barStart) 
        { 
          $tmpObj->subSectionTotal += intval($row['frequency']);
        }

        }else{
        if(intval($row['coordinate']) >= $barStart && intval($row['coordinate']) <= $barEnd) 
        { 
          $tmpObj->subSectionTotal += intval($row['frequency']);
        }
        }
        // $proteinSelected= $row['Protein'];
      }
      
      $tmpObj->subSectionLabel = strval($barStart).'-'.strval($barEnd);
      array_push($A, array("label" => $tmpObj->subSectionLabel, "y" => $tmpObj->subSectionTotal)); 
    }

    
    $lowerBound = $xAxisGraphStart;
    // echo $lowerBound;
    // $upperBound = ($xAxisGraphEnd + 30) - 1;

    if ($gap ==1){
      $upperBound = $xAxisGraphStart+1;
    }else{
      $upperBound = $xAxisGraphStart+$gap-1;
    }
    
    $shapeScoreLabel = $shapeScoreKey = '';
    // $k = $lowerBound;

    $labelcount = 0;
    foreach($result3_rows as $row) 
    {
      
      for($k = $lowerBound; $k <= $xAxisGraphEnd; ) 
      {
        
        $shapeScoreKey = "($lowerBound - $upperBound)";
        $shapeScoreLabel = ($k > ($xAxisGraphEnd - $gap) && $k <= $xAxisGraphEnd) ? $xAxisGraphEnd: $k;
        
        ///Incarnato lab
        if ($row[$shapeScoreKey]>.5){
          $colorPoint = "blue";
        }else{
          $colorPoint = "red";
        }

        if (round($row[$shapeScoreKey],3) == 0){
          $row[$shapeScoreKey]= 'NaN';
          array_push($B, array("label" => "$shapeScoreLabel", "y" => 'NaN', "color" => "black")); 
       
        }else{
          array_push($B, array("label" => "$shapeScoreLabel", "y" => round($row[$shapeScoreKey], 3), "color" => "$colorPoint")); 
       
        }

        ///WT
        foreach($result3wt_rows as $rowWT) {
          if ($rowWT[$shapeScoreKey]>.5){
            $colorPoint = "blue";
          }else{
            $colorPoint = "red";
          }
  
          if (round($rowWT[$shapeScoreKey],3) == 0){
            $rowWT[$shapeScoreKey]= 'NaN';
            array_push($BWT, array("label" => "$shapeScoreLabel", "y" => 'NaN', "color" => "black")); 
         
          }else{
            array_push($BWT, array("label" => "$shapeScoreLabel", "y" => round($rowWT[$shapeScoreKey], 3), "color" => "$colorPoint")); 
         
          }
        }

        foreach($result3DELTA_rows as $rowDELTA) {
          if ($rowDELTA[$shapeScoreKey]>.5){
            $colorPoint = "blue";
          }else{
            $colorPoint = "red";
          }
  
          if (round($rowDELTA[$shapeScoreKey],3) == 0){
            $rowDELTA[$shapeScoreKey]= 'NaN';
            array_push($BDELTA, array("label" => "$shapeScoreLabel", "y" => 'NaN', "color" => "black")); 
         
          }else{
            array_push($BDELTA, array("label" => "$shapeScoreLabel", "y" => round($rowDELTA[$shapeScoreKey], 3), "color" => "$colorPoint")); 
         
          }
        }

        foreach($result3GSE153984_rows as $rowGSE153984) {
          if ($rowGSE153984[$shapeScoreKey]>.5){
            $colorPoint = "blue";
          }else{
            $colorPoint = "red";
          }
  
          if (round($rowGSE153984[$shapeScoreKey],3) == 0){
            $rowGSE153984[$shapeScoreKey]= 'NaN';
            array_push($BGSE153984, array("label" => "$shapeScoreLabel", "y" => 'NaN', "color" => "black")); 
         
          }else{
            array_push($BGSE153984, array("label" => "$shapeScoreLabel", "y" => round($rowGSE153984[$shapeScoreKey], 3), "color" => "$colorPoint")); 
         
          }
        }

        // array_push($B, array("label" => "$shapeScoreLabel", "y" => round($row[$shapeScoreKey], 3), "color" => "$colorPoint")); 
       
        $lowerBound = $upperBound + 1;
        
        $k = $k + $gap;
        if ($gap ==1){
          $upperBound +=1;
        }else{
          $upperBound = ($k > ($xAxisGraphEnd - $gap) && $k <= $xAxisGraphEnd) ? $xAxisGraphEnd: ($k - 1);

        }
      }
    }


    $obj = new MutationsInfo();
    $obj->mutationsByInstrument[] = array(
                      "datagridHTML" => $data,
                      "illumina_miseq" => $illumina_miseq,
                      "illumina_novaseq_6000" => $illumina_novaseq_6000,
                      "nextseq_500" => $nextseq_500,
                      "nextseq_550" => $nextseq_550,
                      "illumina_hiseq_2500" => $illumina_hiseq_2500,
                      "minion" => $minion,
                      "BGI_MGISEQ_2000" => $BGI_MGISEQ_2000

                    );

    $obj->mutationsByFrequency[] = array(      
      "Total" => $A
    );

    $obj->mutationsShapeScoreIncarnato[] = array(      
      "Total" => $B
    );
    $obj->mutationsShapeScoreWT[] = array(      
      "Total" => $BWT
    );
    $obj->mutationsShapeScoreDELTA[] = array(      
      "Total" => $BDELTA
    );
    $obj->mutationsShapeScoreGSE153984[] = array(      
      "Total" => $BGSE153984
    );
    // $obj ->proteinSelectedInfo = $proteinSelected;
    

    echo json_encode($obj);
?>
