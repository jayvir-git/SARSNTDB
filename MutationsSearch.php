<!DOCTYPE html>
<html lang="en">
<head>
    <title>SARS-CoV2 Mutations page</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="./canvasjs-non-commercial-3.6.6/canvasjs.min.js"></script>
    <script src="./sortable.js"></script>

    <?php include "./Navigation.php";?>
    <style>
        .btn 
        {
          margin-top: 25px;
          margin-right: 5px;
        }
        .mutation-search-row .btn {
          margin-top: 0;
          margin-right: 0;
        }
        .mutation-search-row {
          margin-bottom: 12px;
        }
        .mutation-search-label {
          font-size: 11px;
          font-weight: bold;
          color: #555;
          text-transform: uppercase;
          letter-spacing: 0.03em;
          margin: 0 0 6px 0;
        }
        .mutation-search-row .field-hint {
          display: block;
          min-height: 16px;
          font-size: 11px;
        }
        .mutation-search-row .search-field-btn {
          padding-top: 25px;
        }
        .mutation-search-row .search-field-btn .btn {
          min-width: 96px;
          padding-left: 18px;
          padding-right: 18px;
        }
        .mutation-search-row .search-field-actions {
          display: flex;
          gap: 8px;
          padding-top: 25px;
        }
        .mutation-search-row .search-field-actions .btn {
          flex: 0 0 auto;
        }
        #Mutations_row {
          padding: 0 32px 8px 32px;
          max-width: 1100px;
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
          height: 500px;
          padding: 15px;
        }
        .datagraph.canvasjs-chart-canvas {
          width: 100% !important;
          border: 1ps solid grey;
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
        .color-palette {
        margin-left: 5px; 
        border: 1px solid grey;
        width: 16px;
        height: 16px;
        }
        .comp-table-row-td {
          padding: 5px;
          font-size: 12px; 
          border-right: 2px solid #cecece;
        }
        .comp-table-header {
        background-color: #3c3b3b;
        color: white;
        } 
        .legenddiv{
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .cite {
          display: flex;
          align-items: right;
          justify-content: right;
        }
    </style>
  
</head>

<?php
  require_once('./connection.php');
  $geneRegionMap = [];
  $geneResult = $con->query("SELECT Protein, Start, End FROM Gene_1 ORDER BY Start");
  if ($geneResult) {
    while ($row = $geneResult->fetch_assoc()) {
      $geneRegionMap[$row['Protein']] = [
        'start' => (int)$row['Start'],
        'end' => (int)$row['End'],
      ];
    }
  }

  if(isset($_GET['start'])){
        //print_r($_POST['proteinData']);
        $startData = $_GET['start'];
        $startExternal = true;
      }else{
        $startData = 1;
        $startExternal = 1;
      }
      if(isset($_GET['end'])){
        //print_r($_POST['proteinData']);
        $endData = $_GET['end'];
        $endExternal = true;
      }else{
        $endData = 29903;

      }
?>

<body class="search-body">
  <h4 class="search-header">Mutations</h4> 
  <div class="panel-body">
  <div id="searchgrid">
    <div class="form-group" style="height:10%; width:100%;">
      <div class="row">
        <fieldset id="Mutations_row">
          
          <div class="row mutation-search-row">
            <div class="col-md-12">
              <p class="mutation-search-label">Search by coordinates</p>
            </div>
            <div class="col-md-3">
              <label for="Start">Start</label>
              <input id="Start" type="text" class="form-control" value=1/>
              <small class="text-muted field-hint">&nbsp;</small>
            </div>
            <div class="col-md-3">
              <label for="End">End</label>
              <input id="End" type="text" class="form-control" value=29903/>
              <small class="text-muted field-hint">&nbsp;</small>
            </div>
            <div class="col-md-3">
              <label for="MinPercentCoord">Min % Frequency</label>
              <input id="MinPercentCoord" type="number" class="form-control" min="0" max="100" step="0.01" placeholder="e.g. 1" title="Detail tab only"/>
              <small class="text-muted field-hint">Detail tab only</small>
            </div>
            <div class="col-md-3 search-field-actions">
              <button type="button" class="btn btn-secondary" id="clear_coord_btn">Clear</button>
              <button type="button" class="btn btn-success" id="submit_coord_btn">Submit</button>
            </div>
          </div>

          <div class="row mutation-search-row">
            <div class="col-md-12">
              <p class="mutation-search-label">Search by region</p>
            </div>
            <div class="col-md-3">
            <label for="Region">Region</label>
            <select class = "form-control" id="Region" >
                <option value= "All">All</option>
                <option value="Nsp1">Nsp1</option>
                <option value="Nsp2">Nsp2</option>
                <option value="Nsp3">Nsp3</option>
                <option value="Nsp4">Nsp4</option>
                <option value="Nsp5">Nsp5</option>
                <option value="Nsp6">Nsp6</option>
                <option value="Nsp7">Nsp7</option>
                <option value="Nsp8">Nsp8</option>
                <option value="Nsp9">Nsp9</option>
                <option value="Nsp10">Nsp10</option>
                <!-- <option value="Nsp11">Nsp11</option> -->
                <option value="Nsp12">Nsp12</option>
                <option value="Nsp13">Nsp13</option>
                <option value="Nsp14">Nsp14</option>
                <option value="Nsp15">Nsp15</option>
                <option value="Nsp16">Nsp16</option>
                <option value="Surface Glycoprotein">S Gene</option>
                <option value="ORF3a Protein">ORF3a</option>
                <option value="Envelope Membrane Protein">E Gene</option>
                <option value="Membrane Protein">M Gene</option>
                <option value="ORF6 Protein">ORF6</option>
                <option value="ORF7a Protein">ORF7a</option>
                <!-- <option value="ORF7b Protein">ORF7b</option> -->
                <option value="ORF8 Protein">ORF8</option>
                <option value="ORF9 protein">ORF9b</option>
                <option value="Nucleocapsid proteins">N Gene</option>
                <option value="ORF10 Protein">ORF10</option>
            </select>
            <small class="text-muted field-hint">&nbsp;</small>
          </div>
          <div class="col-md-3">
            <label for="MinPercentRegion">Min % Frequency</label>
            <input id="MinPercentRegion" type="number" class="form-control" min="0" max="100" step="0.01" placeholder="e.g. 1" title="Detail tab only"/>
            <small class="text-muted field-hint">Detail tab only</small>
          </div>
          <div class="col-md-3 col-md-offset-3 search-field-actions">
            <button type="button" class="btn btn-secondary" id="clear_region_btn">Clear</button>
            <button type="button" class="btn btn-success" id="submit_region_btn">Submit</button>
          </div>
          </div>
        </fieldset>
        <br />
      </div>
      </div>
    </div>
    <i id="emptyText">Apply filters and hit submit to start seeing results.</i>
    <div id="mutationsData" style="display:none;"> 


      <div class="tab">
        <button class="tablinks active" id="summaryTab" onclick="activateMutationsResultTab('summaryTab', 'mutationsSummary')">Summary</button>
        <button class="tablinks" id="detailTab" onclick="activateMutationsResultTab('detailTab', 'mutationsDetail')">Detail</button>
      </div>

      <div id="mutSummary" >
        <div id="mutationsSummary" class="datacontainer">
          <div id="datagrid" class="datagrid"></div>
          <div id="mutationsChart" class="datagraph"></div>    
        </div>
        
        <div id="mutationsSummaryFrequency" class="datacontainer">  
        </br></br>      
          <div id="mutationsByFreqChart" class="datagraph"></div>    
        </div>
        </br></br>
        <div class="datacontainer"> 
          <div class="legenddiv">
            <table style="padding-left:10px ;" >
              <tr class="darkheader" style="padding-left:10px ;"><th></th><th>Secondary Structure Legend</th></tr>

              <tr>
                <td class="comp-table-row-td"><div class="color-palette" style="background-color:red"></div></td>
                <td class="comp-table-row-td">  < 0.5 More likely to be double stranded </td>
              </tr>
              <tr>
                <td class="comp-table-row-td"><div class="color-palette" style="background-color:blue"></div></td>
                <td class="comp-table-row-td">  > 0.5 Less likely to be double stranded</td>
              </tr>
            </table>    
          </div> 
          </div>   
    
        <div id="mutationsShapeScoreIncarnato" class="datacontainer"> 
          
          <div id="mutationsShapeScoreChart" class="datagraph"></div>   
        </div>

        </br>
        <h4 class= "cite" id="citeIncarnato" >Data from &nbsp <a href="http://www.incarnatolab.com/datasets/SARS_Manfredonia_2020.php" > Manfredonia at al. 2020</a> </h4>

        <div id="mutationsShapeScoreWT" class="datacontainer"> 
          <div id="mutationsShapeScoreChartWT" class="datagraph"></div>   
        </div>

        <div id="mutationsShapeScoreDELTA" class="datacontainer"> 

          <div id="mutationsShapeScoreChartDELTA" class="datagraph"></div>  
        </div>

        </br>
        <h4 class= "cite" id="citeYang" >The two datasets above are from &nbsp<a href="https://www.nature.com/articles/s41467-021-25357-1" > Yang at al. 2021</a> </h4>

        <div id="mutationsShapeScoreGSE153984" class="datacontainer"> 

          <div id="mutationsShapeScoreChartGSE153984" class="datagraph"></div> 
        </div>

        </br>
        <h4 class= "cite" id="citeGSE153984" >Data from  &nbsp<a href="https://www.cell.com/cell/fulltext/S0092-8674(21)00158-6" > Sun at al. 2020</a> </h4>

        
      </div>
      <div id="mutationsDetail" class="datacontainer" style="display:none;"> 
          <i id="detailScopeHint">Detail view of the mutations. Select a region, or set Start/End with Region = All. Set Min % Frequency (e.g. 1). Click a row to open primers ±800 bp around that SNV.</i>
      </div>
    </div>  
	</div>

  <script>

    var geneRegionMap = <?php echo json_encode($geneRegionMap); ?>;
    var activeSearchMode = "coordinates";
    var activeMinPercent = "";

    function applyRegionCoordinates(region) {
      if (region !== "All" && geneRegionMap[region]) {
        document.getElementById("Start").value = geneRegionMap[region].start;
        document.getElementById("End").value = geneRegionMap[region].end;
      } else if (region === "All") {
        document.getElementById("Start").value = "1";
        document.getElementById("End").value = "29903";
      }
    }

    function resetFormMut(){
      document.getElementById("Start").value="1";
      document.getElementById("End").value="29903";  
      document.getElementById("Region").value= "All";
      document.getElementById("MinPercentCoord").value="";
      document.getElementById("MinPercentRegion").value="";
      activeSearchMode = "coordinates";
      activeMinPercent = "";
    }
    window.onload = function() { 

      
    if (<?php echo $startExternal; ?>  == true){
          document.getElementById("Start").value=parseInt(<?php echo $startData ;?>);
          document.getElementById("End").value=parseInt(<?php echo $endData ;?>);  
     }else{
      document.getElementById("Start").value=parseInt(1);
      document.getElementById("End").value=parseInt(29903);
     } 
        // if (localStorage.getItem("start")){
        //   console.log(localStorage.getItem("start"));
        
        //   document.getElementById("Start").value=parseInt(localStorage.getItem("start"));
        //   document.getElementById("End").value=parseInt(localStorage.getItem("end"));  
        // }






        var mutationsChartData = [], mutationsByFreqData=[], mutationsShapeScoreData = [] ,mutationsShapeScoreDataWT = [],mutationsShapeScoreDataDELTA = [], mutationsShapeScoreDataGSE153984 = [];
        CanvasJS.addColorSet("greenShades",
                [//colorSet Array
                "#6d78ad"     //--> lavendar       
                ]);
        CanvasJS.addColorSet("orangeShades", ['#df7970']);
        var mutationsChart = new CanvasJS.Chart("mutationsChart", {
          title: {
            text: "Mutations by instrument"
          },
          theme: "light2",
          animationEnabled: true,
          toolTip:{
            shared: true
          },
          axisY:{
            includeZero: true,
            labelFontSize: 12,
            labelFontColot: "dimGrey"
          },
          axisX: {
            labelFontSize: 12,
            labelFontColot: "dimGrey",
            interval: 1
          },
          legend:{
            fontSize: 14,
            fontColor: "Grey"      
          },
          data: mutationsChartData
        });  

        var mutationsByFreqChart = new CanvasJS.Chart("mutationsByFreqChart", {
          title: {
            text: "Mutations by frequency"
          },
          colorSet: 'greenShades',
          theme: "light2",
          zoomEnabled: true,
          animationEnabled: true,
          toolTip:{
            shared: true
          },
          axisY:{
            title: 'Frequency',
            includeZero: true,
            labelFontSize: 12,
            labelFontColot: "dimGrey"
          },
          axisX: {
            title: 'Coordinate Intervals',
            labelFontSize: 12,
            labelFontColot: "dimGrey",
            interval: 7
          },
          legend:{
            fontSize: 14,
            fontColor: "Grey"      
          },
          toolTip: {
            shared: true,
            contentFormatter: function (e) {
              var content = " ";
              for (var i = 0; i < e.entries.length; i++) {
                content += "Start - End: " + "<strong>" + e.entries[i].dataPoint.label + "</strong>";
                content += "<br/>";
                content += "Average: " + "<strong>" + e.entries[i].dataPoint.y + "</strong>";
                
              }
              return content;
            }
          },
          data: mutationsByFreqData
        });  
        
        var mutationsShapeScoreChart = new CanvasJS.Chart("mutationsShapeScoreChart", {
          title: {
            text: "RNA SHAPE Secondary Structure"

          },
          subtitles:[
            {
              text: "Incarnato Data"
            }
            ],
          // colorSet: 'orangeShades',
          zoomEnabled: true,
          theme: "light2",
          // animationEnabled: true,
          toolTip:{
            shared: true
          },
          axisY:{
            title: 'Shape Score',
            includeZero: true,
            labelFontSize: 12,
            labelFontColot: "dimGrey"
          },
          axisX: {
            title: 'Genome Position',
            labelFontSize: 12,
            labelFontColot: "dimGrey",
            interval: 7
          },
          legend: {
             horizontalAlign: "left", // "center" , "right"
             verticalAlign: "center",  // "top" , "bottom"
             fontSize: 15
          },
          toolTip: {
            shared: true,
            contentFormatter: function (e) {
              var content = " ";
              for (var i = 0; i < e.entries.length; i++) {
                content += "Start - End: " + "<strong>" + e.entries[i].dataPoint.label + "</strong>";
                content += "<br/>";
                content += "Average: " + "<strong>" + e.entries[i].dataPoint.y + "</strong>";                
              }
              return content;
            }
          },
          data: mutationsShapeScoreData
        }); 
        // console.log(mutationsShapeScoreData);


        // WT chart
        var mutationsShapeScoreChartWT = new CanvasJS.Chart("mutationsShapeScoreChartWT", {
          title: {
            text: "RNA SHAPE Secondary Structure"

          },
          subtitles:[
            {
              text: "Yang et al 2021 Wildtype Data"
            }
            ],
          // colorSet: 'orangeShades',
          zoomEnabled: true,
          theme: "light2",
          // animationEnabled: true,
          toolTip:{
            shared: true
          },
          axisY:{
            title: 'Shape Score',
            includeZero: true,
            labelFontSize: 12,
            labelFontColot: "dimGrey"
          },
          axisX: {
            title: 'Genome Position',
            labelFontSize: 12,
            labelFontColot: "dimGrey",
            interval: 7
          },
          legend:{
            fontSize: 14,
            fontColor: "Grey"      
          },
          toolTip: {
            shared: true,
            contentFormatter: function (e) {
              var content = " ";
              for (var i = 0; i < e.entries.length; i++) {
                content += "Start - End: " + "<strong>" + e.entries[i].dataPoint.label + "</strong>";
                content += "<br/>";
                content += "Average: " + "<strong>" + e.entries[i].dataPoint.y + "</strong>";                
              }
              return content;
            }
          },
          data: mutationsShapeScoreDataWT
        }); 

        
        // DELTA
        var mutationsShapeScoreChartDELTA = new CanvasJS.Chart("mutationsShapeScoreChartDELTA", {
          title: {
            text: "RNA SHAPE Secondary Structure"

          },
          subtitles:[
            {
              text: "Yang et al 2021 DELTA Variant Data"
            }
            ],
          // colorSet: 'orangeShades',
          zoomEnabled: true,
          theme: "light2",
          // animationEnabled: true,
          toolTip:{
            shared: true
          },
          axisY:{
            title: 'Shape Score',
            includeZero: true,
            labelFontSize: 12,
            labelFontColot: "dimGrey"
          },
          axisX: {
            title: 'Genome Position',
            labelFontSize: 12,
            labelFontColot: "dimGrey",
            interval: 7
          },
          legend:{
            fontSize: 14,
            fontColor: "Grey"      
          },
          toolTip: {
            shared: true,
            contentFormatter: function (e) {
              var content = " ";
              for (var i = 0; i < e.entries.length; i++) {
                content += "Start - End: " + "<strong>" + e.entries[i].dataPoint.label + "</strong>";
                content += "<br/>";
                content += "Average: " + "<strong>" + e.entries[i].dataPoint.y + "</strong>";                
              }
              return content;
            }
          },
          data: mutationsShapeScoreDataDELTA
        }); 

        // GSE153984
        var mutationsShapeScoreChartGSE153984 = new CanvasJS.Chart("mutationsShapeScoreChartGSE153984", {
          title: {
            text: "RNA SHAPE Secondary Structure"

          },
          subtitles:[
            {
              text: "Sun et al 2021 (GSE153984)"
            }
            ],
          // colorSet: 'orangeShades',
          zoomEnabled: true,
          theme: "light2",
          // animationEnabled: true,
          toolTip:{
            shared: true
          },
          axisY:{
            title: 'Shape Score',
            includeZero: true,
            labelFontSize: 12,
            labelFontColot: "dimGrey"
          },
          axisX: {
            title: 'Genome Position',
            labelFontSize: 12,
            labelFontColot: "dimGrey",
            interval: 7
          },
          legend:{
            fontSize: 14,
            fontColor: "Grey"      
          },
          toolTip: {
            shared: true,
            contentFormatter: function (e) {
              var content = " ";
              for (var i = 0; i < e.entries.length; i++) {
                content += "Start - End: " + "<strong>" + e.entries[i].dataPoint.label + "</strong>";
                content += "<br/>";
                content += "Average: " + "<strong>" + e.entries[i].dataPoint.y + "</strong>";                
              }
              return content;
            }
          },
          data: mutationsShapeScoreDataGSE153984
        }); 

        var submitCoordButton = document.getElementById("submit_coord_btn");
        submitCoordButton.addEventListener("click", submitCoordinateSearch);

        var submitRegionButton = document.getElementById("submit_region_btn");
        submitRegionButton.addEventListener("click", submitRegionSearch);
        
        var clearCoordButton = document.getElementById("clear_coord_btn");
        clearCoordButton.addEventListener("click", clearCoordinateSearch);

        var clearRegionButton = document.getElementById("clear_region_btn");
        clearRegionButton.addEventListener("click", clearRegionSearch);

        submitCoordinateSearch();

        function clearCoordinateSearch() {
          document.getElementById("Start").value = "1";
          document.getElementById("End").value = "29903";
          document.getElementById("MinPercentCoord").value = "";
          submitCoordinateSearch();
        }

        function clearRegionSearch() {
          document.getElementById("Region").value = "All";
          document.getElementById("MinPercentRegion").value = "";
        }

        function submitCoordinateSearch() {
          localStorage.clear();
          document.getElementById("Region").value = "All";
          activeSearchMode = "coordinates";
          activeMinPercent = document.getElementById("MinPercentCoord").value;

          document.getElementById("emptyText").style.display = "none";
          document.getElementById("mutationsData").style.display = "block";

          var start = document.getElementById("Start").value;
          var end = document.getElementById("End").value;
          getData(start, end, "All", activeMinPercent);
        }

        function submitRegionSearch() {
          localStorage.clear();
          var region = document.getElementById("Region").value;
          if (region === "All") {
            alert("Please select a region.");
            return;
          }

          activeSearchMode = "region";
          activeMinPercent = document.getElementById("MinPercentRegion").value;
          applyRegionCoordinates(region);

          document.getElementById("emptyText").style.display = "none";
          document.getElementById("mutationsData").style.display = "block";

          getData("", "", region, activeMinPercent);
        }

        function resetFormMut(){
          document.getElementById("Start").value="1";
          document.getElementById("End").value="29903";  
          document.getElementById("Region").value= "All";
          document.getElementById("MinPercentCoord").value="";
          document.getElementById("MinPercentRegion").value="";
          activeSearchMode = "coordinates";
          activeMinPercent = "";
        }

        function getData(start,end,region,minPercent){
          var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                // console.log(this.responseText);
                var res = JSON.parse(this.responseText);
                
                for (i = 0; i < res.mutationsByInstrument.length; i++) {
                  var datagridEle = document.getElementById("datagrid");
                  datagridEle.innerHTML = res.mutationsByInstrument[i].datagridHTML;

                  mutationsChartData.splice(0,mutationsChartData.length);

                  mutationsChartData.push({
                      type: "stackedColumn",
                      showInLegend: true,
                      name: "illumina_miseq",
                      dataPoints: res.mutationsByInstrument[i].illumina_miseq
                  },{
                      type: "stackedColumn",
                      showInLegend: true,
                      name: "illumina_novaseq_6000",
                      dataPoints: res.mutationsByInstrument[i].illumina_novaseq_6000
                  },{
                      type: "stackedColumn",
                      showInLegend: true,
                      name: "nextseq_500",
                      dataPoints: res.mutationsByInstrument[i].nextseq_500
                  },{
                      type: "stackedColumn",
                      showInLegend: true,
                      name: "nextseq_550",
                      dataPoints: res.mutationsByInstrument[i].nextseq_550
                  },{
                      type: "stackedColumn",
                      showInLegend: true,
                      name: "illumina_hiseq_2500",
                      dataPoints: res.mutationsByInstrument[i].illumina_hiseq_2500
                  },{
                      type: "stackedColumn",
                      showInLegend: true,
                      name: "minion",
                      dataPoints: res.mutationsByInstrument[i].minion
                  },{
                      type: "stackedColumn",
                      showInLegend: true,
                      name: "BGI_MGISEQ_2000",
                      dataPoints: res.mutationsByInstrument[i].BGI_MGISEQ_2000
                  }
                  
                  );
                  mutationsChart.render();
                }
                for (i = 0; i < res.mutationsByFrequency.length; i++) { 
                  mutationsByFreqData.splice(0,mutationsByFreqData.length);
                  mutationsByFreqData.push({
                      type: "column",
                      dataPoints: res.mutationsByFrequency[i].Total,
                      // showInLegend: true,
                      legendText:"Colors"
                  });
                  mutationsByFreqChart.render();
                }
                // Incarnato Data
                for (i = 0; i < res.mutationsShapeScoreIncarnato.length; i++) { 
                  mutationsShapeScoreData.splice(0,mutationsShapeScoreData.length);

                  mutationsShapeScoreData.push({
                      type: "column",
                      dataPoints: res.mutationsShapeScoreIncarnato[i].Total,
                     
                      legendText:"Colors"
                  });
                  mutationsShapeScoreChart.render();
              }
              // WT data
              for (i = 0; i < res.mutationsShapeScoreWT.length; i++) { 
                  mutationsShapeScoreDataWT.splice(0,mutationsShapeScoreDataWT.length);

           
                  mutationsShapeScoreDataWT.push({
                      type: "column",
                      dataPoints: res.mutationsShapeScoreWT[i].Total,
                      legendText:"Colors"
                  });
                  mutationsShapeScoreChartWT.render();
              }
              // DELTA data
              for (i = 0; i < res.mutationsShapeScoreDELTA.length; i++) { 
                  mutationsShapeScoreDataDELTA.splice(0,mutationsShapeScoreDataDELTA.length);

                  // if (res.mutationsShapeScore[i].Total> 0.5){
                  //   var colorData = "blue";
                  // }else{
                  //   var colorData = "red"
                  // }
                  // console.log( res.mutationsShapeScoreDELTA[i].Total);
                  mutationsShapeScoreDataDELTA.push({
                      type: "column",
                      dataPoints: res.mutationsShapeScoreDELTA[i].Total,
                      legendText:"Colors"
                  });
                  mutationsShapeScoreChartDELTA.render();
              }
              // GSE153984 data
              for (i = 0; i < res.mutationsShapeScoreGSE153984.length; i++) { 
                  mutationsShapeScoreDataGSE153984.splice(0,mutationsShapeScoreDataGSE153984.length);

                
                  mutationsShapeScoreDataGSE153984.push({
                      type: "column",
                      dataPoints: res.mutationsShapeScoreGSE153984[i].Total,
                    
                  });
                  mutationsShapeScoreChartGSE153984.render();
              }
            }
          }
          if (region=="All"){
            region="";
          }
          //xmlhttp.open("GET", "MutationsSummary.php?Region="+region+"&ReferenceBase="+referenceBase+"&AlternateBase="+alternateBase+"&Instrument="+instrument+"&Start="+start+"&End="+end, true);
          xmlhttp.open("GET", "MutationsSummary.php?Region="+region+"&Start="+start+"&End="+end, true);
          xmlhttp.send();

          var xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) { 
              var mutationsDetailEle = document.getElementById("mutationsDetail");
              mutationsDetailEle.innerHTML = this.responseText;
            }
          }
          //xmlhttp.open("GET", "MutationsDetail.php?Region="+region+"&ReferenceBase="+referenceBase+"&AlternateBase="+alternateBase+"&Instrument="+instrument+"&Start="+start+"&End="+end, true);
          var minPercentValue = (typeof minPercent === "undefined") ? activeMinPercent : minPercent;
          xmlhttp.open("GET", "MutationsDetail.php?Region="+region+"&Start="+start+"&End="+end+"&MinPercent="+encodeURIComponent(minPercentValue), true);
          xmlhttp.send();
        }
      }

      
      function showOrHideInstruments(refBaseTotalRowId, instrumentRowsName){
        var refBaseTotalRow = document.getElementById(refBaseTotalRowId);
        var instrumentRows = document.getElementsByName(instrumentRowsName);

        if (refBaseTotalRow.innerHTML == "+") {
          refBaseTotalRow.innerHTML = "-";
          for (let item of instrumentRows) {
            item.style.display = "table-row";  
          }
        } else {
          refBaseTotalRow.innerHTML = "+"
          for (let item of instrumentRows) {
            item.style.display = "none";  
          }
        }
      }

      function activateMutationsResultTab(tabId, mutationTabId) {
        var region = document.getElementById("Region").value;
        var start = document.getElementById("Start").value;
        var end = document.getElementById("End").value;
        var minPercent = parseFloat(activeMinPercent);
        var minPercentActive = !isNaN(minPercent) && minPercent > 0;
        var useCoordinateSearch = activeSearchMode === "coordinates";
        if (useCoordinateSearch && (end-start)>25000 && region=="All" && !minPercentActive){
          alert("Mutation Detail page not available for full length analysis. Select a protein, narrow the Start/End interval, or set Min % Frequency (e.g. 1).");
          return;
        }else{

          var i, tabcontent, tablinks;
          tabcontent = document.getElementsByClassName("datacontainer");
          tabcontentcite = document.getElementsByClassName("cite");
          mutDetail = document.getElementById("mutationsDetail");
          // for (i = 0; i < tabcontent.length; i++) {
          //   tabcontent[i].style.display = "none";
          //   try{
          //     tabcontentcite[i].style.display = "none";
          //   }catch (error){

          //   }

          // }
          document.getElementById("mutSummary").style.display= "none"

          
          tablinks = document.getElementsByClassName("tablinks");
          for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
          }
          document.getElementById(mutationTabId).style.display = "flex";
          document.getElementById(tabId).className += " active";
          // document.querySelectorAll("br").style.display="none";

          if(tabId == 'summaryTab') {
            document.getElementById("mutSummary").style.display= "inline"
            mutDetail.style.display= "none"
          
          }else{  
            mutDetail.style.display= "inline"
          }
        }
    }
   
    
        document.getElementById("mutationsDetail").addEventListener("click", function (e) {
          var t = e.target;
          if (t && t.closest && t.closest("button")) {
            return;
          }
          var row = t && t.closest ? t.closest("tr.snv-primer-row") : null;
          if (!row) {
            return;
          }
          var coord = row.getAttribute("data-coord");
          if (!coord) {
            return;
          }
          var url = "SnvPrimerView.php?coord=" + encodeURIComponent(coord);
          var ref = row.getAttribute("data-ref");
          var alt = row.getAttribute("data-alt");
          var protein = row.getAttribute("data-protein");
          if (ref) { url += "&ref=" + encodeURIComponent(ref); }
          if (alt) { url += "&alt=" + encodeURIComponent(alt); }
          if (protein) { url += "&protein=" + encodeURIComponent(protein); }
          window.open(url, "_blank");
        });

        function copyFunction(prot,seq) {
        // Get the text field
        var copyText = ">"+prot+"\n"+seq;

        // Select the text field
        // copyText.select();
        // copyText.setSelectionRange(0, 99999); // For mobile devices

        // Copy the text inside the text field
        navigator.clipboard.writeText(copyText);

        // Alert the copied text
        alert("Copied "+prot+" to clipboard, redirecting to SNAP2 webpage");
        document.location.href = "https://rostlab.org/services/snap2web/";
      }
    
    </script>
</body>
</html>
