<?php

    require_once("GenomeComparisonInfo.php");

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    if(isset($_GET['Protein'])){
        $protein = $_GET['Protein'];
    }

    $q1 = "";

    // Normalizes protein input to gene
    if($protein != "") {
      if (strpos($protein,"ORF") !== false and strpos($protein,"Protein") !==false ){
        
        $tmp_prot =explode(" ",$protein); 
        $tmp_prot = array_slice($tmp_prot, 0,1);
        $tmp_prot = array_pop($tmp_prot);
        $lpro = strtolower($tmp_prot);
        // echo $tmp_prot;
      }else if ((strpos($protein,"protein") !== false) or (strpos($protein,"Protein") !== false)){
        // echo "protein no orf";
        $tmp_prot = substr($protein, 0,1);
        $lpro = $tmp_prot . "protein";
      }else{
        $lpro = $protein;
        $tmp_prot = $protein;
        $tmp_prot = strtolower($protein);
        // echo "-";
      }

      $q1 .= " AND cov_comp.gene = '" . $tmp_prot . "'";



    }
      

    require_once './connection.php';
    // $sql = "  SELECT cov_comp.*, s.Sequence FROM domain cov_comp
    //               inner join Sequence s on cov_comp.gene = s.Protein
    //             where 1=1 $q1";

    $sql = "  SELECT * FROM cov_comp 
                where 1=1 $q1 ORDER BY cov2Start+1";

    $result = $con->query($sql);
    if (!$result) {
      echo ("query error");
      echo $sql;
      exit();
    }


    //new******
    $sql = "  SELECT * FROM compseq ";

    
    $resultseq = $con->query($sql);


    if (!$result) {
      echo ("query error covcomp data");
      exit();
    }

    
    //********* */
    // debug_to_console("test");
    $result_rows_seq = $resultseq->fetch_all(MYSQLI_ASSOC);
    $lpro= strtolower($lpro);
    $counterp=0;

    // if ($Protein=="S"){
      
    //   $lpro = "sprotein";

      
    // }elseif($Protein=="N"){
    //   $lpro = "nprotein";
    // }elseif($Protein=="M"){
    //   $lpro = "mprotein";
    // }elseif($Protein=="E"){
    //   $lpro = "eprotein";
    // }
    // echo $lpro;
    // echo gettype($lpro);
    foreach($result_rows_seq as $row)
    {
      // echo 
      if ($counterp == 0){
        $sars1 = $row[$lpro];
        // $counter++;
        $counterp++;

      }elseif($counterp==1){
          
        $sars2 = $row[$lpro];
        $counterp++;   
      } elseif ($counterp==2){
        $sarsevalu = $row[$lpro];
        
      }
    }
      
    
    $result_rows = $result->fetch_all(MYSQLI_ASSOC);
    
    // $sarsCov2Name = strtolower($Protein) = '';
    $sarsCov2Name = $proteinSequence = '';
    $rowCounter = 0;
    foreach($result_rows as $row)
    {
        // debug_to_console($row);
        if($sarsCov2Name != $row['domainNameCov2'])
        {
          $obj = new GenomeComparisonInfo();
          $comparisonRecs[] = $obj;
          $sarsCov2Name = $row['domainNameCov2'];
          // echo "???";
        }

        // $obj -> gene = $row['Gene'];
        // $obj -> feature = $row['Feature'];
        // $obj -> domainNameCov2 = $row['Name_SARSCoV2'];
        // $obj -> domainNameCov = $row['Name_SARSCoV'];
        // $obj -> cov2AAStartEnd = $row['SARS-CoV-2_aa_Range_(start-end)'];
        // $obj -> cov2Start = $row['SARS-CoV-2_nt_Range_start'];
        // $obj -> cov2End = $row['SARS-CoV-2_nt_Range_end'];
        // $obj -> covAAStartEnd = $row['SARS-CoV_aa_Range_(start-end)'];
        // $obj -> covStart = $row['SARS-CoV_nt_Range_start'];
        // $obj -> covEnd = $row['SARS-CoV_nt_Range_end'];
        // $obj -> identities = $row['Identities'];
        // $obj -> positives = $row['Positives'];

        if (!function_exists('str_starts_with')) {
          function str_starts_with($haystack, $needle) {
              return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
          }
      }


        if (str_starts_with($row['Publicationcov'], 'doi.org/')) {
          // check if doi is in string
          if (str_starts_with($row['Publicationcov'], 'https://')){
            $publicationcov=$row['Publicationcov'];
          }else{
            $publicationcov='https://www.';
            $publicationcov.=$row['Publicationcov'];
          }
        }else if(str_starts_with($row['Publicationcov'], 'https://')){
          $publicationcov=$row['Publicationcov'];
        }else{
          $publicationcov='https://www.doi.org/';
          $publicationcov.=$row['Publicationcov'];
        }

        if (str_starts_with($row['Publication'], 'doi.org/')) {
          // check if doi is in string
          if (str_starts_with($row['Publication'], 'https://')){
            $publication=$row['Publication'];
          }else{
            
            $publication='https://www.';
            $publication.=$row['Publication'];
          }
        }else if (str_starts_with($row['Publication'], 'https://')){
          $publication=$row['Publication'];

        }else{
          $publication='https://www.doi.org/';
          $publication.=$row['Publication'];
        }

        $obj -> gene = $row['gene'];
        $obj -> feature = $row['feature'];
        $obj -> domainNameCov2 = $row['domainNameCov2'];
        $obj -> domainNameCov = $row['domainNameCov'];
        $obj -> cov2AAStartEnd = $row['cov2AAStartEnd'];
        $obj -> cov2Start = $row['cov2Start'];
        $obj -> cov2End = $row['cov2End'];
        $obj -> covAAStartEnd = $row['covAAStartEnd'];
        $obj -> covStart = $row['covStart'];
        $obj -> covEnd = $row['covEnd'];
        $obj -> identities = $row['identities'];
        $obj -> positives = $row['positives'];
        $obj -> sars1seq = $sars1;
        $obj -> sars2seq = $sars2;
        $obj -> sarsevalseq = $sarsevalu;
        $obj -> dashRange = $row['dashRange'];
        $obj -> dashRange2 = $row['dashRange2'];
        $obj -> color = $rowCounter;
        $obj -> publication = $publication;
        $obj -> publicationcov = $publicationcov;
        
        
        $rowCounter ++;

        // $obj -> gene = $row[0];
        // $obj -> feature = $row[1];
        // $obj -> domainNameCov2 = $row[2];
        // $obj -> domainNameCov = $row[3];
        // $obj -> cov2AAStartEnd = $row[4];
        // $obj -> cov2Start = $row[5];
        // $obj -> cov2End = $row[6];
        // $obj -> covAAStartEnd = $row[7];
        // $obj -> covStart = $row[8];
        // $obj -> covEnd = $row[9];
        // $obj -> identities = $row[10];
        // $obj -> positives = $row[11];

        // if($row['Sequence'] != "")
        // {
        //   $proteinSequence .= '<div><img src="data:image/png;charset=utf8;base64,'.base64_encode($row['Sequence']).'"  /></div>' ;
        //   //$row['Sequence'] = base64_encode($row['Sequence']);
        // }

        // $obj -> proteinSequenceImgTags = $proteinSequence;


    }
    echo json_encode($comparisonRecs);

?>
