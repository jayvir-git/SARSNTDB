<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>

<?php
    $referenceBase = $alternateBase = $instrument = $start = $end = "";
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

    $q1 = $q2 = "";
    if($referenceBase != ""){
      $q1 .= " AND mutations.reference = '" . $referenceBase . "'";
    }

    if($alternateBase != ""){
      $q1 .= " AND mutations.alternate = '" . $alternateBase . "'";
    }

    if($start != "" and $end == "") {
      $q1 .= " AND mutations.coordinate >= " . $start;
    }

    if($start != "" and $end != "") {
      $q1 .= " AND mutations.coordinate BETWEEN " . $start . " AND " . $end;
    }

    if($start == "" and $end != "") {
      $q1 .= " AND mutations.coordinate <= " . $end;
    }

    if($instrument != ""){
      $q1 .= " AND mutations.instrument = '" . $instrument . "'";
    }
    else {
      $q2 = " UNION
              SELECT
                  reference, alternate, 'ALL' instrument, count(coordinate) as coordinate_count
              FROM mutations
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
                            reference, alternate, instrument, count(coordinate) as coordinate_count
                        FROM mutations
                        WHERE 1=1 $q1
                        GROUP BY reference, alternate, instrument
                        $q2
                ) m1
              ) m2
            GROUP BY reference, instrument
            ORDER BY reference, instrument";
    $result = $con->query($sql);
    if (!$result) {
      echo ("query error");
      exit();
    }
    $result_rows = $result->fetch_all(MYSQLI_ASSOC);
    $total = $result->num_rows;

    $illumina_miseq = $illumina_novaseq_6000 = $nextseq_500 = $nextseq_550 = $BGI_MGISEQ_2000 = array();
    foreach($result_rows as $row) {
        if(strtoupper($row['instrument']) == "ILLUMINA_MISEQ") {
          $row['reference'] != "A" ? array_push($illumina_miseq, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($illumina_miseq, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($illumina_miseq, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($illumina_miseq, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
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
        
        } else if (strtoupper($row['instrument']) == "BGI_MGISEQ_2000") {
          $row['reference'] != "A" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-A", "y" => intval($row['alternate_A']))) : "";
          $row['reference'] != "C" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-C", "y" => intval($row['alternate_C']))) : "";
          $row['reference'] != "G" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-G", "y" => intval($row['alternate_G']))) : "";
          $row['reference'] != "T" ? array_push($BGI_MGISEQ_2000, array("label" => $row['reference']."-T", "y" => intval($row['alternate_T']))) : "";
        }
    }

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
        $data.='    <td style="width: 12%">'.(($row['alternate_A']==0) ? "" : $row['alternate_A']).'</td>';
      }
      if($alternateBase == "" || $alternateBase == "C" ) {
        $data.='    <td style="width: 12%">'.(($row['alternate_C']==0) ? "" : $row['alternate_C']).'</td>';
      }
      if($alternateBase == "" || $alternateBase == "G" ) {
        $data.='    <td style="width: 12%">'.(($row['alternate_G']==0) ? "" : $row['alternate_G']).'</td>';
      }
      if($alternateBase == "" || $alternateBase == "T" ) {
        $data.='    <td style="width: 12%">'.(($row['alternate_T']==0) ? "" : $row['alternate_T']).'</td>';
      }
      if($alternateBase == "") {
        $data.='    <td style="width: 12%">'.(($row['alternate_total']==0) ? "" : $row['alternate_total']).'</td>';
      }
      $data.='    </tr>';
    }
    $data .= '  </tbody>';
    $data .= '</table>';


    $return_arr[] = array(
                      "datagridHTML" => $data,
                      "illumina_miseq" => $illumina_miseq,
                      "illumina_novaseq_6000" => $illumina_novaseq_6000,
                      "nextseq_500" => $nextseq_500,
                      "nextseq_550" => $nextseq_550,
                      "BGI_MGISEQ-2000" => $BGI_MGISEQ_2000
                    );

    echo json_encode($return_arr);
?>
