<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>SARS-CoV2 & CoV Comparison Page</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
      .comparison-table-div {
        overflow-x: scroll;
      }
      .comp-table-header {
        background-color: #3c3b3b;
        color: white;
      }
      .comp-table-header-td {
        padding: 5px;
        text-align: center;
        font-size: 13px;
        padding-right: 5px;
        border-right: 1px solid #a7a7a7;
      } 
      .comp-table-row-td {
        padding: 5px;
        font-size: 12px; 
        border-right: 1px solid #cecece;
      }
      .comp-table-row-td-alt {
        padding: 5px;
        font-size: 12px;
        background-color: #efefef;
        border-right: 1px solid #cecece;    
        
      }

      .proteinseq {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        padding: 0px;
        width: 100%;
        margin: 0px
      }
      .color-palette {
        margin-left: 30px; 
        border: 1px solid grey;
        width: 20px;
        height: 20px;
      }
    </style>
    <script>


      alert('this is comparison page');
      
    </script>
  </head>

  <?php 
  
  

    // debug_to_console("test");
    require_once("GenomeComparisonInfo.php");
    $compData = array();
    $counter = 0;
    $rowClassName = '';
    $protein = '';
    $gene = $feature =  $domainNameCov2 = $domainNameCov = $cov2AAStartEnd = $dashRange = $dashRange ='';
    $cov2Start = $cov2End = $covAAStartEnd = $covStart = $covEnd = $identities = $positives ='';
    if(isset($_POST['comparisonData'])){
      $compData = json_decode($_POST['comparisonData'], true);
    }
    if(isset($_POST['protein'])){
      $protein = $_POST['protein'];
    }
    
  ?>
  
  <body>
   
  <p><b onclick="getGenomeData(false)"><u>Genome Search</u></b> -> Compare Cov & Cov-2 </p>
    <h3><?php echo $protein ?></h3>
    <div class="comparison-table-div">
        <table>
          <tr class = "comp-table-header">
            
            <th class="comp-table-header-td"><div>SARS-CoV2</div><div>Name</div></th>
            
            <th class="comp-table-header-td"><div>SARS-CoV2</div><div>AA-Range</div></th>
            <th class="comp-table-header-td"><div>SARS-CoV2</div><div>Start-End</div></th>
            <!-- <th class="comp-table-header-td"><div>SARS-CoV2</div><div>End</div></th> -->
            <th class="comp-table-header-td"><div>SARS-CoV</div><div>AA-Range</div></th>
            <th class="comp-table-header-td"><div>SARS-CoV</div><div>Start-End</div></th>
            <!-- <th class="comp-table-header-td"><div>SARS-CoV</div><div>End</div></th> -->
            <th class="comp-table-header-td">Identities</th>
            <th class="comp-table-header-td">Positives</th>
            <!-- <th class="comp-table-header-td">Publications</th> -->
            <th class="comp-table-header-td">Mutations</th>
            <!-- <th class="comp-table-header-td">Publication</th> -->

            
            

          </tr>
          <?php
                // echo  (gettype($compData))  ;    
                foreach($compData as $row) { 
                  
                  $domainNameCov2  =  $row['domainNameCov2'];
                  $domainNameCov =  $row['domainNameCov'];
                  $cov2AAStartEnd =  $row['cov2AAStartEnd'];
                  $cov2Start =  intval($row['cov2Start']);
                  $cov2End =  intval($row['cov2End']);
                  $cov2StEd = "{$cov2Start}-{$cov2End}";
                  $covAAStartEnd =  $row['covAAStartEnd'];
                  $covStart =  intval($row['covStart']);
                  $covEnd =  intval($row['covEnd']);
                  $covStEd = "{$covStart}-{$covEnd}";
                  $identities =  $row['identities'];
                  $positives =  $row['positives'];
                  $sars1seq =  $row['sars1seq'];
                  $sars2seq =  $row['sars2seq'];
                  $sarsevalseq =  $row['sarsevalseq'];
                  $sarsDash = $row['dashRange'];
                  $sarsDash2 = $row['dashRange2'];
                  $colorI = $row['color'];
                  $pub = $row['publication'];
                  $pubcov = $row['publicationcov'];
                  

                  
                  $colorli = [ "#4F73FF", "#FF4F1B","#4FFF66", "#FD7CF1", "#F3FF00", "#FF54C3", "#FEA600" , "#F6FE00","#FE00B2","#7CFD8C","#00E2FF","#FF57D3", "#DDFFB5", "#B964FF", "#FFCD64","#64FFC3"];
                 
                  $rowClassName = ($counter % 2 == 0) ? 'comp-table-row-td' : 'comp-table-row-td-alt';
                  $counter++; 

                  if (trim($cov2StEd) == "0-0" ){
                    $cov2StEd = "";
                  }
                  if (trim($covStEd) == "0-0" ){
                    $covStEd = "";
                  }
            
          ?>
                <tr>
                  
                  <td class="<?php echo $rowClassName ?>"> <span class="color-palette" style="background-color:<?php echo $colorli[$colorI] ?> "> <?php echo $domainNameCov2 ?></span></td>
                  
                  <td class="<?php echo $rowClassName ?>"><a href="<?php echo $pub ?>"><?php echo $cov2AAStartEnd ?></a></td>
                  <td class="<?php echo $rowClassName ?>"><?php echo $cov2StEd ?></td>
                  <td class="<?php echo $rowClassName ?>"><a href="<?php echo $pubcov ?>"><?php echo $covAAStartEnd ?></a></td>
                  <td class="<?php echo $rowClassName ?>"><?php echo $covStEd ?></td>
                  <td class="<?php echo $rowClassName ?>"><?php echo $identities ?></td>
                  <td class="<?php echo $rowClassName ?>"><?php echo $positives ?></td>
                  <td class="<?php echo $rowClassName ?>"> <button onClick="mutClick(this)" class="float-left submit-button mutClick-btns" >Mutations</button></td>
                </tr>
                
          <?php } ?>
        </table>

        <br/>
        <br/>
        
        
    </div>
    <div id="protein-sequence" class="proteinseq">            
              <!-- protein sequence image tags are coming from GenomeComparisonData.php -->
              <!-- innerHTML for this div is populated in GenomeSearch.php -->
        
      </div>
    
    <script src="./JS/mutClick.js"></script>
  </body>
  
</html>

