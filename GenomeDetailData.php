
<?php

    require_once("ProteinInfo.php");

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    if(isset($_GET['Protein'])){
        $protein = $_GET['Protein'];
    }

    if(isset($_GET['Picture'])){
      $Picture = $_GET['Picture'];
    }

    $q1 = "";
    if($protein != "") {
      if ((strpos($protein,"ORF") !== false) and ((strpos($protein,"Protein") !==false) )){
        
        $tmp_prot =explode(" ",$protein); 
        $tmp_prot = array_slice($tmp_prot, 0,1);
        $tmp_prot = array_pop($tmp_prot);
        // $lpro = strtolower($tmp_prot);
        // echo $tmp_prot;
      }else if ((strpos($protein,"protein") !== false) or (strpos($protein,"Protein") !== false)){
        // echo "protein no orf";
        $tmp_prot = substr($protein, 0,1);
        $lpro = $tmp_prot . "protein";
      }else{
        $tmp_prot = strtolower($protein);
        // echo "-";
      }


      $q1 .= " AND Gene_1.Protein = '" . $tmp_prot . "'";
    }

    
    // echo $tmp_prot;
    // echo $protein;
    require_once './connection.php';
    $sql = "  SELECT
    g.Protein Current_Protein,
    g.Gene,
    g.Accession,
    -- g.Motif,
    -- g.Region,
    g.Aa_count,
    g.Start,
    g.End,
    g.Function,
    g.Function_detail,
    -- g.Non_translated_RNA_sequence,
    g.RNA_Sequence,
    g.protSeq,
    g.matchedcols,
    pi.Picture,
    d.gene,
    trim(d.Feature) Feature,
    d.domainNameCov2,
    d.cov2Start Feature_Start,
    d.cov2End Feature_End,
    d.cov2AAStartEnd 
    FROM
        cov_comp d
        
           LEFT OUTER JOIN Gene_1 g on (g.matchedcols = d.gene AND d.gene = '" . $tmp_prot . "')
           LEFT OUTER JOIN Protein_Images pi on (pi.Protein = g.protein AND d.gene = '" . $tmp_prot . "') 
           WHERE LENGTH(d.cov2Start) > 0
    ORDER BY g.Protein, d.Feature, d.cov2Start";

    $result = $con->query($sql);

    if (!$result) {
      echo ("query error");
      echo $sql;
      exit();
    }
    
    $obj = new ProteinInfo();
  

  //   function utf8ize( $mixed ) {
  //     if (is_array($mixed)) {
  //         foreach ($mixed as $key => $value) {
  //             $mixed[$key] = utf8ize($value);
  //         }
  //     } elseif (is_string($mixed)) {
  //         return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
  //     }
  //     return $mixed;
  // 
    $json = array( );
    $proteinImageTag = '';
    $result_rows = $result->fetch_all(MYSQLI_ASSOC);
    $firstrow=true;
    foreach($result_rows as $row)
    {
      if ($row["Current_Protein"] == ""){
        continue;
      }
      if ($firstrow ==true){
        $row["Current_Protein"]=  mb_convert_encoding($row['Current_Protein'], 'UTF-8', 'UTF-8');
        $row["Gene"] = mb_convert_encoding($row['Gene'], 'UTF-8', 'UTF-8');
        $row["Accession"] =mb_convert_encoding($row['Accession'], 'UTF-8', 'UTF-8');
        // $row["Motif"] =mb_convert_encoding($row['Motif'], 'UTF-8', 'UTF-8');
        // $row["Region"] =mb_convert_encoding($row['Region'], 'UTF-8', 'UTF-8');
        $row["Aa_count"] =mb_convert_encoding($row['Aa_count'], 'UTF-8', 'UTF-8');
        $row["Start"] =mb_convert_encoding($row['Start'], 'UTF-8', 'UTF-8');
        $row["End"] =mb_convert_encoding($row['End'], 'UTF-8', 'UTF-8');
        $row["Function"] =mb_convert_encoding($row['Function'], 'UTF-8', 'UTF-8');
        $row["Function_detail"]=mb_convert_encoding($row['Function_detail'], 'UTF-8', 'UTF-8');
        $row["RNA_Sequence"]=mb_convert_encoding($row['RNA_Sequence'], 'UTF-8', 'UTF-8');
        $row["protSeq"]=mb_convert_encoding($row['protSeq'], 'UTF-8', 'UTF-8');
        $proteinImageTag = '  <img src="data:image/png;charset=utf8;base64,'.base64_encode($row['Picture']).'" width="350" height = "350" />' ;
        $row['Picture'] = base64_encode(mb_convert_encoding($row['Picture'], 'UTF-8', 'UTF-8'));
        $firstrow =false;

        }else{
          $row["Current_Protein"]=$row["Current_Protein"]=  mb_convert_encoding($row['Current_Protein'], 'UTF-8', 'UTF-8');
          $row["Accession"] ="";

          $row["Gene"] = "";
          $row["Aa_count"] ="";
          $row["Start"] =mb_convert_encoding($row['Start'], 'UTF-8', 'UTF-8');
          $row["End"] =mb_convert_encoding($row['End'], 'UTF-8', 'UTF-8');
          $row["Function"] ="";
          $row["Function_detail"]="";
          $row["RNA_Sequence"]="";
          $row["protSeq"]="";
          $row['Picture'] = "";
        }
        

      // console_log($row);
      // if ($firstrow == false){ 
      //   $row['Picture'] = "";

      // }else{
     
      //   $proteinImageTag = '  <img src="data:image/png;charset=utf8;base64,'.base64_encode($row['Picture']).'" width="350" height = "350" />' ;
      //   $row['Picture'] = base64_encode(mb_convert_encoding($row['Picture'], 'UTF-8', 'UTF-8'));
      //   // $row['Picture'] = base64_encode($row['Picture']);
      //   $picDone = true;
      // }
      $json[] = $row;

    }
    $obj->imageTag = $proteinImageTag;
    $obj->detailInfo = $json;

    echo json_encode($obj);
    // $error = json_last_error_msg();
    // var_dump($json, $error === JSON_ERROR_UTF8);
    // echo $error;
?>
