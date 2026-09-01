<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>SARS-CoV2 Page</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="genomedetail.css" >
  </head>

  <?php 
    $proteinData = array();
    if(isset($_POST['proteinData'])){
      //print_r($_POST['proteinData']);
      $proteinData = json_decode($_POST['proteinData'], true);
    }

  ?>
  
  <body>    
  <p><b onclick="getGenomeData(false)"><u>Genome Search</u></b> -> Genome Detail </p>
    <a href="#" onclick="getCovCov2ComparisonData()">Compare domains in SARS-CoV and SARS-CoV-2</a>
    <div class="container-fluid">
      <div class="outer-container">
      
      <?php  
          class RnaSeqInfo 
          {
              public $seqNum;
              public $seqString = '';
          }
          
          $predata = $functiondata = $postdata = '';
          $counter = 0;
          $prevProtein = "";
          $prevFeature = "";
          $colorPaletteId = "";
          $nonTransRNAseq = '';
          $seqHeaderAndValue = array();
          $proteinseq = array();
          $proteinSeqNums = array();
          $proteinSeqNumValues = array();
          $rnaCharCounter = 0;
          $rnaSeqTable = array();
          $featureArray = array();

          foreach($proteinData as $row) {
            
              
            if($row['Current_Protein'] == "") {
              continue;
            }
            // echo $row["Feature_Start"];
            // echo is_null($row["Feature_Start"]) ? 'true' : 'false';
            if ($row["Feature_Start"] == ""){
              // echo "true";
              // echo is_null($row["Feature_Start"]);
              continue;
            }


            if ($prevProtein != $row['Current_Protein']) 
            {
              $prevProtein = $row['Current_Protein'];
              $counter = 0;
              echo $predata . $functiondata . $postdata;  
              $predata = $functiondata = $postdata = '';
              $prevFeature = '';
            }            
            

            $colorPaletteId = str_replace(' ', '_', $row['domainNameCov2'].$row['Feature_Start']);
           

            if ($counter == 0) {
              
              if($row['Gene']== "ORF9b"){
                $hreflink = "<a href=https://www.rcsb.org/structure/7F2B> Rutgers PDB</a></h5>";
              
              }else{
                $hreflink  = "<a href=https://doi.org/10.1016/j.crmeth.2021.100014> Zhang Group</a></h5>";
              }

              $predata.='<h5> Protein Simulations from '.$hreflink;
              $predata.='<div class="flex-row">';
              
              $predata.=' <div id="protein-image-div">';
              //image tag here is coming from genomedetaildata.php
              // $predata.='<h5> Protein Simulations from <a href=https://doi.org/10.1016/j.crmeth.2021.100014> Zhang Group</a></h5>';
              $predata.=' </div>';

              $predata.=' <div id="function-detail-div">';       

              $predata.='   <div>';
              $predata.='     <h2 id ="proteininner">'.$row['Current_Protein'].'</h2>';
              $predata.='     <h4 style="color:#5cb85c;">'.$row['Gene'].'</h4>';
              $predata.='   </div>';
              
              $predata.='   <div><table class="table borderless">';
              $predata.='     <tr><td class="col-md-12"><b>Function Detail</b></td></tr>';
              $predata.='     <tr><td class="col-md-12">'.$row['Function_detail'].'</td></tr>';
              $predata.='   </table></div>';
              
              $predata.='   <div><table class="table borderless">';
              $predata.='     <tr><td class="col-md-2"><b>Start - End: </b></td><td><i>'.$row["Start"].' - '.$row["End"].'</i></td></tr>';
              $predata.='     <tr><td class="col-md-2"><b>AA Count: </b></td><td><i>'.$row['Aa_count'].'</i></td></tr>';
              $predata.='   </table></div>';  
            
              $predata.=' </div>';
              $predata.='</div>';
              
              $predata.='<div class="flex-row" style ="display: none">';              
              $predata.='   <input type="checkbox" id="toggleGenomeCanvas" name="toggleGenomeCanvas" value="0">';
              $predata.='   &nbsp;<label for="toggleGenomeCanvas">Show other proteins</label>';
              $predata.='</div>';
              $predata.='       <h4>Functional Domain Map</h4>';
              $predata.='      <div class="flex-row">';
              
              $predata.='        <div class="genome-div"><canvas id="genomeCanvas" class="genomeCanvas"></canvas></div>';
              $predata.='        <div id="tooltip-div">
                                    <div id="canvasDomainTooltip" class="canvasDomainTooltipHidden">Content 1</div>
                                 </div>';   
              $predata.='      </div>';

              //$predata.='<div class="row"><table>';
            }            

            

            if ($prevFeature != $row['domainNameCov2']) 
            {
              $prevFeature = $row['domainNameCov2'];

              if($counter != 0){
                $functiondata.='    </table></div>';
                               
              }
              //$functiondata.='<div class="row"><table>';
              $domst = $row["Feature_Start"];
              $domed = $row["Feature_End"];
              $domProtst = (intval($row["Start"])+intval($row["Feature_Start"]))/3;
              $domProted = ($row["End"]+$row["Feature_End"])/3;
              array_push($featureArray, array("feature" => $row["domainNameCov2"], "st" => $domst, "end" => $domed, "color" => $colorPaletteId,"AArange" => $row["cov2AAStartEnd"]));
              // $functiondata.='   <tr><td class="col-md-12"><b>'.$row['Feature'].'</b></td></tr>';
              // $functiondata.='   <tr><td class="col-md-12"><div><table class="table table-bordered">';
              // $functiondata.='      <tr>';
              // $functiondata.='        <td class="col-md-5"><i>'.$row['Name'].'</i></td>';
              // $functiondata.='        <td class="col-md-5 flex-it">';
              // $functiondata.='            <i>'.$row['Feature_Start'].' - '.$row['Feature_End'].'</i>';
              // $functiondata.='            <span id="'.$colorPaletteId.'" class="color-palette" style="background-color:#ffffff"></span>';
              // $functiondata.='        </td>';
              // $functiondata.='      </tr>';

            } else {
              $domst = $row["Feature_Start"];
              $domed = $row["Feature_End"];
              array_push($featureArray, array("feature" => $row["domainNameCov2"], "st" => $domst, "end" => $domed,"color" => $colorPaletteId,"AArange" => $row["cov2AAStartEnd"]));
              
            }                        
            
            if ($counter == 0) {
              $postdata.='  </table></div>';
              $postdata.='  <div class="row"><table class="table borderless">';
              $postdata.='    <tr><td class="col-md-12"><b><a href="https://www.ncbi.nlm.nih.gov/nuccore/1798174254">RNA Sequence</a></b></td></tr>';
              
              // if(count($seqHeaderAndValue) > 0)
              //   $postdata.='    <tr><td class="col-md-12">'.$seqHeaderAndValue[0].'</td></tr>';

              $postdata.='    <tr><td class="col-md-12">';
              $postdata.='<table class="rna-table">';

              // $postdata.= '<p>'.$row["RNA_Sequence"].'</p>';
              // echo "end-st";
              // echo $row['End']-$row['Start'] ;
              // echo "end-st";

              $postdata.='<div class="flex-row">';

              $postdata.='   <div><table class="table borderless">';
              // $postdata.='     <tr><td class="col-md-12"><b>Test</b></td></tr>';
              $postdata.='     <tr><td class="rna-td-col2">'.$row['RNA_Sequence'].'</td></tr>';
              $postdata.='   </table></div></div>';
              
              
              $postdata.='  </table></div>';
              $postdata.='</div';
              

              $postdata.='  <div class="row"><table class="table borderless">';
              $postdata.='    <tr><td class="col-md-12"><b><a href= "https://www.ncbi.nlm.nih.gov/protein/'.$row['Accession'].'">Protein Sequence</a></b></td></tr>';
              $postdata.= '<button onclick="copyFunction(\''.$row['Current_Protein'].'\',\''.$row['protSeq'].'\')">Analyze Sequence using SNAP2</button> </td>';

              // if(count($seqHeaderAndValue) > 0)
              //   $postdata.='    <tr><td class="col-md-12">'.$seqHeaderAndValue[0].'</td></tr>';

              $postdata.='    <tr><td class="col-md-12">';
              $postdata.='<table class="rna-table">';

        

              $postdata.='<div class="flex-row">';

              $postdata.='   <div><table class="table borderless">';
              // $postdata.='     <tr><td class="col-md-12"><b>Test</b></td></tr>';
              $postdata.='     <tr><td class="rna-td-col2">'.$row['protSeq'].'</td></tr>';
              $postdata.='   </table></div></div>';
              
              
              $postdata.='  </table></div>';
              $postdata.='</div';
              
            }

            
            $counter++;              
          }
          $columns = array_column($featureArray, 'st');
          array_multisort($columns, SORT_ASC, $featureArray);

          $functiondata.='   <tr><td class="col-md-12"><b>Domains</b></td></tr>';
          $functiondata.='   <tr><td class="col-md-12"><div><table class="table table-bordered">';
          $functiondata.='    <tr > ';
          $functiondata.= '     <td class= "comp-table-header-td">  <h5>Name</h5>  </td>' ;

          $functiondata.= '     <td class="col-md-5 flex-it domainTable-td-colorbox"> <h5>Nucleotide range</h5></td>' ;
          $functiondata.= '      <td class= "comp-table-header-td"><h5>Residue Range</h5></td>' ;
          $functiondata.= '    </tr>';
          foreach($featureArray as $feat){
            $functiondata.='      <tr>';
            $functiondata.='        <td ><i>'.$feat['feature'].'</i></td>';

            $functiondata.='        <td class="col-md-5 flex-it domainTable-td-colorbox">';
            $functiondata.='            <i>'.$feat['st'].' - '.$feat['end'].'</i>';
            $functiondata.='            <span id="'.$feat['color'].'"class="color-palette" style="background-color:#ffffff"></span>';
            $functiondata.='        </td>';
            $functiondata.='        <td class="domainTable-td" >';
            $functiondata.='            '.$feat["AArange"].'';
            $functiondata.='        </td>';

            // $domainAAStart = row[]

            $functiondata.='      </tr>';
            
          }
          $functiondata.='      </tr>';
          echo $predata . $functiondata . $postdata;
        ?>

      </div>
    </div>

  </body>
</html>

