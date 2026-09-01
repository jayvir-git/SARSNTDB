<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SARS-CoV-2 intragenome page</title>
    <link rel="stylesheet" href="./styles.css" />
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <?php include "Navigation.php";?>
    <!-- <link rel="stylesheet" href="./styles.css" /> -->
    <style>
      .header {
          position: sticky;
          top:0;
      }
      .datagrid {
          width: 100%;
          height: 500px;
          overflow: auto;
          padding-left: 10px;
      }
      .repeatgrid {
          width: 100%;
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
      .table {
        text-align: left;
      }
        .btn 
        {
            margin-top: 25px;
            margin-right: 5px;
        }
        .search-header 
        {
            padding-left: 10px;
        }
        .search-body
        {
            background-color: lightgrey;
        }
        .panel-body
        {
            background-color: white;
        }
        .datacontainer {
            display: flex;
        }
        .datacontainer > div {
            flex: 1;
        }
        .datagrid {
            height: 500px;
            overflow: auto;
            display: inline-block;
        }
        .datagraph {
        
            padding: 15px;
        }
        tr.darkheader th{
            background: #333;
            color: white;
            position: sticky;
            top: 0;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
            text-align: center;
        }
        tr.greyheader th{
            background: grey;
            color: white;
            position: sticky;
            top: 33px;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
            text-align: left;
        }
        tr.grey td{
            background: lightgrey;
            color: black;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
            text-align: left;
        }
        .table {
            text-align: left;
            font-size: 12px;
        } 
        td.rowexpand {
            font-weight: bold;
        } 
        /* Style the tab */
        .tab {
            overflow: hidden;
            border-bottom: 1px solid #dee2e6;          
            margin-bottom: 15px;
        }

        /* Style the buttons inside the tab */
        .tab button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 10px 16px;
            transition: 0.3s;
            font-size: 12px;
        }

        /* Change background color of buttons on hover */
        .tab button:hover {
            background-color: #ddd;
        }

        /* Create an active/current tablink class */
        .tab button.active {
            background-color: #ccc;
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
        .color-palette {
        margin-left: 5px; 
        border: 1px solid grey;
        width: 16px;
        height: 16px;
        }
        #legendRow {
          max-width: 900px;
          

        }

    </style>
  </head>
<?php if (isset($_GET['start']) && isset($_GET['end'])){
    // echo "<h4>Test hello</h4>";
    $start = $_GET['start'];
    $end = $_GET['end'];
    $lStart = $_GET['lStart'];
    $rStart = $_GET['rStart'];
    $lEnd = $_GET['lEnd'];
    $rEnd = $_GET['rEnd'];
    $q4 = " AND (intraGene.leftStart BETWEEN '" . $start . "' AND '" . $end . "' OR intraGene.leftEnd BETWEEN '" . $start . "' AND '" . $end . "' OR ('" . $start . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd) OR ('" . $end . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd)) OR  (intraGene.rightStart BETWEEN '" . $start . "' AND '" . $end . "' OR intraGene.rightEnd BETWEEN '" . $start . "' AND '" . $end . "' OR ('" . $start . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd) OR ('" . $end . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd))";  

 }else if (isset($_GET['start'])){
    $start = $_GET['start'];
    $lStart = $_GET['lStart'];
    $rStart = $_GET['rStart'];
    $lEnd = $_GET['lEnd'];
    $rEnd = $_GET['rEnd'];
    $stminus = $start-15;
    $stplus = $start+15;
    $q4 = " AND (intraGene.leftStart BETWEEN '" . $start . "' AND '" . $stminus . "' OR intraGene.leftEnd BETWEEN '" . $start . "' AND '" . $stplus . "') OR ('" . $start . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd) OR ('" . $start . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd) ";  

 }else if(isset($_GET['end'])){
    $end = $_GET['end'];
    $lStart = $_GET['lStart'];
    $rStart = $_GET['rStart'];
    $lEnd = $_GET['lEnd'];
    $rEnd = $_GET['rEnd'];
    $edminus = $end-15;
    $edplus = $end+15;
    $q4 = " AND (intraGene.leftStart BETWEEN '" . $end . "' AND '" . $edminus . "' OR intraGene.leftEnd BETWEEN '" . $end . "' AND '" . $edplus . "') OR ('" . $end . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd) OR ('" . $end . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd) ";  

 }
     require_once './connection.php';

    // $q4 = " AND (intraGene.leftStart BETWEEN '" . $start . "' AND '" . $end . "' OR intraGene.leftEnd BETWEEN '" . $start . "' AND '" . $end . "' OR ('" . $start . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd) OR ('" . $end . "' BETWEEN intraGene.leftStart AND intraGene.leftEnd)) OR  (intraGene.rightStart BETWEEN '" . $start . "' AND '" . $end . "' OR intraGene.rightEnd BETWEEN '" . $start . "' AND '" . $end . "' OR ('" . $start . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd) OR ('" . $end . "' BETWEEN intraGene.rightStart AND intraGene.rightEnd))";  

    $sqlIntraGene = "SELECT leftStart, rightStart, leftEnd, rightEnd, readSupport FROM intraGene WHERE 1=1 $q4 ORDER BY intraGene.leftStart + 0";
    $resultIntragene = $con->query($sqlIntraGene);
    if (!$resultIntragene) {
        echo $sqlIntraGene;
        exit();
    }
    $result_rowsIntraGene = $resultIntragene->fetch_all(MYSQLI_ASSOC);
    $totalIntraGene = $resultIntragene->num_rows;
 ?>

<body>
    <div class="datagraph" >
        <h4 class="search-header">Intragenome Interaction Regions</h4> 
        
        <div id="coords">
        <span>5'</span>
        <span>3'</span>
        </div>
        <div class="repeatDisplay" id="repeatDisplay">
            <div id="genehighlight" class="genehighlight"></div>
        </div>
        <div id="coords">
            <span>1</span>
            <span>29903</span>
        </div>

        <div id="textoutput" > 

        </div>
        
        <div id="container"></div>
        <div id="legendRow" ></div>
        <script src="./JS/main.js"></script>
        <script>
            var lStart = <?php echo $lStart; ?>;
            var rStart = <?php echo $rStart; ?>;
            var lEnd = <?php echo $lEnd; ?>;
            var rEnd = <?php echo $rEnd; ?>;
            var LRarray = Array(lStart,rEnd,rStart,rEnd);
            // console.log(LRarray);
            
            plotrepeats(LRarray);
            createLegend();
        </script>

    </div>
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
                       


                    foreach($result_rowsIntraGene as $row) {
                      $data = '';
                    //   if ($prev_color==$color1){
                    //   $data.= "<tr style=".$color2.">" ;
                    //   $prev_color=$color2;
                    // } elseif ($prev_color==$color2){
                    //   $data.= "<tr style=".$color1.">" ;
                    //   $prev_color=$color1;
                    // }\''.$row['leftStart'].'\',\''.$row['leftEnd'].'\',\''.$row['rightStart'].'\',\''.$row['rightEnd'].'\')
                      
                      $inputArray = "Array(".$row['leftStart'].",".$row['leftEnd'].",".$row['rightStart'].",".$row['rightEnd'].")";
                    //   echo $inputArray;
                      $data .= '<td><a id="myLink" title="Click to do something" href="#" onclick="plotIntraGenome('.$inputArray.');">Visualize</a></td>';
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
        <h5> These data are taken from <a href="https://doi.org/10.1038/s41467-021-25357-1">Yang et al. 2021</a></h5>
    </div> 
<script>
    
    var lStart = "<?php echo $lStart; ?>";
    var rStart = "<?php echo $rStart; ?>";
    var lEnd = "<?php echo $lEnd; ?>";
    var rEnd = "<?php echo $rEnd; ?>";
    var LRarray = Array(lStart,rEnd,rStart,rEnd);
    // console.log(LRarray);
    
    plotrepeats(LRarray);
    function resetFormGenome(){
            // console.log("clicked");
            container.innerHTML = '';
            var highlights = document.getElementsByClassName("highlight");
            var parentNode = document.getElementById("repeatDisplay");
            while(highlights.length>0){
            parentNode.removeChild(highlights[0]);
            // highlights[0]
            }}
    
    function plotIntraGenome(array){
        resetFormGenome()

        // console.log("plotintra");
        // console.log(array);
        // console.log(typeof(array))
        for (let i = 0; i < genests.length; i++) {
            var parentNode = document.getElementById("repeatDisplay");
            var highlight = document.createElement("div");
            highlight.classList.add("genehighlight");
            parentNode.appendChild(highlight);
            highlight.style.background = "#"+ genecolor[i];
            highlight.style.width = map(geneed[i]) - map(genests[i]) +"px";
            highlight.style.marginLeft = map(genests[i]) + "px";
            // console.log(map(geneed[i]) - map(genests[i]) +"px")
        }

        // this creates the motifs on the diagram
        for (let i = 0; i < array.length; i++) {
            var parentNode = document.getElementById("repeatDisplay");
            var highlight = document.createElement("div");
            highlight.classList.add("highlight");
            parentNode.appendChild(highlight);
            highlight.style.marginLeft = map(array[i]) + "px";
        }
    }

    
</script>

</body>