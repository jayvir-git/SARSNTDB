<?php

    require_once("RepeatInfo.php");

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    if(isset($_GET['repeat'])){
        $repeat = $_GET['repeat'];
        // echo ($repeat);
    }
    
    $q1 = "";
    if($repeat != "") {
      $q1 .= " AND r.sequence = '" . $repeat . "'";
    }


    $substringBoo =false;

    require_once './connection.php';
    $sql = "  SELECT 
     r.sequence,
     r.coord,
     r.SUPrepeats

    FROM
        repeats r where 1=1 $q1";

    $result = $con->query($sql);
    // echo gettype($result);
    // echo($sql);
    if (mysqli_num_rows($result)==0) {
      
      // if repeat not in UGene database then look for repeats LIKE it (containging it)
      $q1 = " AND r.sequence LIKE '%".$repeat."%'";
      $sql = "  SELECT 
      r.sequence,
      r.coord,
      r.SUPrepeats

      FROM
          repeats r where 1=1 $q1";

      $result = $con->query($sql);
      $substringBoo =true;
      if (mysqli_num_rows($result)==0) {
        $obj-> coordinates= array(0);
        $obj -> sequence = array();
        $obj -> substrings = array();
        $obj -> proteins = "Repeat not found in database, please enter another repeat of length 6 or greater.  Alternatively, search for repeats in a nucleotide range on the genome page. ";


        echo json_encode($jsonRepeat);
      }

      // echo ("query error");
      // echo($sql);
      // exit();
    }
    

    $sqlgene = "  SELECT 
     g.Gene,
     g.Protein,
     g.Start,
     g.End

    FROM
        Gene_1 g  ORDER BY g.Start";

    $result_gene = $con->query($sqlgene);

    if (!$result_gene) {
      echo ("query error");
      echo($sqlgene);
      exit();
    }
    $obj = new RepeatInfo();
    
    $json = array( );
    $jsonRepeat[] = $obj;
    $result_rows = $result->fetch_all(MYSQLI_ASSOC);
    $result_rows_gene = $result_gene->fetch_all(MYSQLI_ASSOC);

    foreach($result_rows as $row)
      {
      // echo '<pre>'; print_r($row); echo '</pre>';
        // console_log($row);
      
      $repeatSequence =  $row["sequence"];
      $coordcln = substr($row['coord'],1);
      $coordcln = substr($coordcln,0,-1);
      $arraycoord = explode(",", $coordcln); 
      // $coordinates = $row["coordinates"];
      $coordinates = $arraycoord;
      if ($substringBoo){
        $superstr = substr($row['SUPrepeats'],1);
        $superstr = substr($superstr,0,-1);
        $superstr = str_replace("'","",$superstr);
        $arraysuper = explode(",", $superstr);
        array_push($arraysuper, $repeatSequence);


      }else{
      $superstr = substr($row['SUPrepeats'],1);
      $superstr = substr($superstr,0,-1);
      $superstr = str_replace("'","",$superstr);
      $arraysuper = explode(",", $superstr); 
        }
      
      }
    
    $arrayprot = array();
    foreach($coordinates as $coord){
      $protfound=false;
      $coord = intval($coord);
      // echo $coord;
      $prev_row_gene = array("Start"  => 100000, "End" => 100000, "Protein" => "None");
      foreach($result_rows_gene as $row_gene){
        if (intval($row_gene["Start"]) <= $coord && intval($row_gene["End"]) >= $coord){
          $link = "<a href= 'GenomeSearch.php?Gene=" ;
          $link .= $row_gene["Gene"];
          $link .= "&Protein=";
          $link .= $row_gene["Protein"];
          $link .= "'>";
          $link .= $row_gene["Protein"];
          $link.= "</a>";
          // &Gene=ORF1a/ORF1ab'+protein>Enter a Repeat</a>""';

          array_push($arrayprot,$link);
          // echo $row_gene["Protein"];
          $protfound= true; 
          break 1;

        }else if($coord<=255){
          array_push($arrayprot,"Leader Sequence");
          break 1;
        }else if(intval($prev_row_gene["End"]) <= $coord && intval($row_gene["Start"]) >= $coord){
          $currGeneLink = ' <a href= "GenomeSearch.php?Gene='.$row_gene['Gene'].'&Protein='.$row_gene["Protein"].'"> '.$row_gene["Protein"].' </a> ';
          $prevGeneLink = ' <a href= "GenomeSearch.php?Gene='.$prev_row_gene['Gene'].'&Protein='.$prev_row_gene["Protein"].'"> '.$prev_row_gene["Protein"]. '</a> ';
          $strStatement =  'In non-coding region between '.$prevGeneLink.' and '.$currGeneLink.'.';
          array_push($arrayprot, $strStatement);
          break 1;

        }else if($coord>=29675 ){
          array_push($arrayprot,"End Region");
          break 1;
        }
      $prev_row_gene = $row_gene;
      }
    // if ($protfound == false){

    }
  
      

    
    $obj-> coordinates= $coordinates;
    $obj -> sequence = $repeatSequence;
    $obj -> substrings = $arraysuper;
    $obj -> proteins = $arrayprot;


    echo json_encode($jsonRepeat);