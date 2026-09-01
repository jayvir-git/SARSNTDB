<!DOCTYPE html>
<html lang="en">
<head>
    <title>SARS-CoV-2 Repeat visualizer</title>
    
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="./canvasjs-non-commercial-3.6.6/canvasjs.min.js"></script>
    <?php include "Navigation.php";?>
    <link rel="stylesheet" href="./styles.css" />
    <style>
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
          height: 500px;
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

        table{
          margin: 20px 0px;
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
        background-color: #aeb6bf;
        color: white;
        text-align: center;
        }
        .comp-table-header-td {
          padding: 10px;
          text-align: center;
          font-size: 12px;
          padding-right: 5px;
          border-right: 2px solid #a7a7a7;
          } 
        .comp-table-row-td {
          padding: 5px;
          font-size: 12px; 
          border-right: 2px solid #cecece;
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

<?php 
    $repeatData = array();
    if(isset($_GET['repeat'])){
      //print_r($_POST['proteinData']);
      $repeatData = $_GET['repeat'];
      $repeatExternal = 1;
    }else{
      $repeatData = "ACGAAC";
      $repeatExternal = 0;
      $repeatExternaltst = "<h1>".$repeatExternal."</h1>";
    }

    $tsgRepeatMatches = [];
    require_once __DIR__ . '/connection.php';
    require_once __DIR__ . '/two_segment_helpers.php';
    if (isset($con) && $con instanceof mysqli && !$con->connect_errno && is_string($repeatData)) {
        $tsgRepeatMatches = tsg_rows_matching_repeat($con, $repeatData);
    }

  ?>
<body class="search-body">
  <h4 class="search-header">Repeats</h4>
  <?php if (!empty($tsgRepeatMatches)) : ?>
  <div class="panel panel-default" style="margin: 0 15px 12px 15px;">
    <div class="panel-heading" style="font-size:13px;"><strong>Junction table (CJ / NJ)</strong></div>
    <div class="panel-body" style="font-size:12px; padding-top:8px;">
      <p style="margin:0 0 8px 0;">This repeat matches one or more entries in the two-segment (sgmRNA) junction list. Open the schematic for the same graphic as Genome search links.</p>
      <ul style="margin-bottom:0;">
        <?php foreach ($tsgRepeatMatches as $jm) :
            $jk = htmlspecialchars(tsg_junction_kind_label($jm), ENT_QUOTES, 'UTF-8');
            $jn = htmlspecialchars($jm['name'], ENT_QUOTES, 'UTF-8');
            $ju = htmlspecialchars(tsg_viz_url((int) $jm['id']), ENT_QUOTES, 'UTF-8');
            ?>
        <li><?php echo $jk; ?> — <?php echo $jn; ?> — <a href="<?php echo $ju; ?>">Two-segment schematic</a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>
  <div class="panel-body">
  <div id="searchgrid">
    <div class="form-group" style="height:10%; width:100%;">
      <div class="row">
        <fieldset id ="Gene_row">
            <div class="col-md-2">
              <label for="motif">Enter a Repeat</label>
              <input id="motif" type="text" class="form-control" id="motif" value=<?php echo $repeatData ?> />
            </div>
      
        
            <div class="col-md-2">
                <button type="submit" class="btn btn-success" id="submit_btn" name="submit" onclick="getRepeatData()">Submit</button>
                <button type="button" onclick="resetForm();" id="clear_btn" class="btn btn-secondary">Clear</button>
            </div>
        </fieldset>
      </div>
    </div>
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
    <script src="./JS/main.js"></script>
    <div id="legendRow" class="legend" ></div>
    <div id="container">
      

    </div>

  </div>
  </div>
  <script>
    function containsAny(str) {

      for (var i = 0; i < str.length; i++) {
        var substring = str[i];
        if (['A', 'C', 'G','T','a','c','g','t'].includes(substring)) {
          continue;
        }else{
          return false;
        }
      } 
      return true; 
      }
      const nonNTalphabet = ["B","D","E","F","H","I","J","K","L","M","N","O","P","Q","R","S","U","V","W","X","Y","Z",""];

    function resetForm(){
      container.innerHTML = '';
      var highlights = document.getElementsByClassName("highlight");
      var parentNode = document.getElementById("repeatDisplay");
      while(highlights.length>0){
        parentNode.removeChild(highlights[0])
        // highlights[0]
      }
      }

     
      function makeTable(coordarray,substrarray) {
      var repeat = document.getElementById("motif").value;

      var theTable = document.createElement('table');
      var coordarray = coordarray.map(function (x) { 
        return parseInt(x, 10); 
        });

      const zip = (a, b) => Array(Math.max(b.length, a.length)).fill().map((_,i) => [a[i], b[i]]);
      
      var zippedArray = zip(coordarray,substrarray);
      zippedArray.sort(function(x,y){return x[0] - y[0];});
      

      var arrayLength = zippedArray.length;
      tr = document.createElement('tr');
      td = document.createElement('td');
      tdsuperhead = document.createElement('td')

      tr.classList.add("comp-table-header");
      td.classList.add("comp-table-row-td");

      tdsuperhead.classList.add("comp-table-row-td");
      td.appendChild(document.createTextNode("Start Coordinates"));
      tdsuperhead.appendChild(document.createTextNode("Location"));
      
      tr.appendChild(td);
      tr.appendChild(tdsuperhead);
      theTable.appendChild(tr);

      for (var i = 0, tr, td; i < arrayLength; i++) {
        
        tr = document.createElement('tr');
        td = document.createElement('td');
        td.classList.add("comp-table-row-td");
        
        if (zippedArray[i][0] == undefined){
          zippedArray[i][0] = " ";
          
        }else if((zippedArray[i][0]==zippedArray[i][0])==false){
          zippedArray[i][0] = " ";
          zippedArray[i][1] = " ";
        }

        var a = document.createElement('a');
        var linkText = document.createTextNode(zippedArray[i][0]);
        a.appendChild(linkText);
        a.title = "zippedArray[i][0]";
        // var link = "./GenomeSearch.php?start=" + zippedArray[i][0];
        var endL = parseInt(zippedArray[i][0]) + repeat.length;
        a.href = "./GenomeSearch.php?start=" + zippedArray[i][0] + "&end=" + endL;
        
        // GenomeSearch.php?start=5000&end=5010
        td.appendChild(a);
        
        
        tdsuper = document.createElement('td');
        tdsuper.classList.add("comp-table-row-td");

        var linkEle = document.createElement("div");
        linkEle.innerHTML = zippedArray[i][1];
        tdsuper.appendChild(linkEle);
        
        
        tr.appendChild(td);
        tr.appendChild(tdsuper);
        theTable.appendChild(tr);

      }

      document.getElementById('container').append(theTable);
      // console.log(tst);

  }
    function makeHTMLtable(data){
      // plotrepeats()
      var parsedData = JSON.parse(data);
      // console.log("in makehtmltable");
      // console.log(parsedData[0]);
      // console.log(parsedData[0].proteins);
      // console.log(parsedData[0].substrings.length);
      plotrepeats(parsedData[0].coordinates);

      makeTable(parsedData[0].coordinates,parsedData[0].proteins);
      
      
      
      
      //  Below makes the superstring table and allows it to be clickable 
      var theTable = document.createElement('table');
      tr = document.createElement('tr');
      td = document.createElement('td');
      tdsuperhead = document.createElement('td')

      tr.classList.add("comp-table-header");
      tdsuperhead.classList.add("comp-table-row-td");
      tdsuperhead.appendChild(document.createTextNode("Super Repeats"));

      tr.appendChild(tdsuperhead);
      theTable.appendChild(tr);

      var arrayLength = parsedData[0].substrings.length;
      for (var i = 0, tr, td; i < arrayLength; i++) {
         

        tdsuper = document.createElement('tr');
        tdsuper.classList.add("comp-table-row-td");

        var superText = document.createElement("a");
        var superLink = document.createTextNode(parsedData[0].substrings[i]);
        superText.appendChild(superLink);
        superText.href = 'motifvisualizer.php?repeat='+parsedData[0].substrings[i];
        superText.innerText = parsedData[0].substrings[i];


        // console.log("here!!!!!!!!!!!!!!!!!!!");
        // console.log(parsedData[0].substrings[i]);
        // superText.addEventListener("click",superStringSet);
        tdsuper.appendChild(superText);

        theTable.appendChild(tdsuper);
        
      }
      document.getElementById('container').appendChild(theTable);
    }

    function superStringSet(){
      // console.log(this.innerText);
      document.getElementById("motif").value = this.innerText;

      getRepeatData();
    }

    
    function getRepeatData() {
      
      if (previousRepeats){
        previousRepeats.push(document.getElementById("motif").value)
      }else{
        var previousRepeats=[document.getElementById("motif").value]
      }
      
      resetForm()
      
      // container.innerHTML = '';
      var repeat = document.getElementById("motif").value;
      
      if (repeat.length<6){
        alert("Please input a nucleotide sequence 6 nucleotides in length or greater or search for a repeat in an interval on the Genome Search page.  ")

      }else if (containsAny(repeat)==false){
        alert("Please input a valid nucleotide sequence using A,G,C, or T only.")
    

      }else{
      // console.log(repeat);
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {   

          makeHTMLtable(this.responseText);
          

          }
      }
      xmlhttp.open("GET", "repeatData.php?repeat="+repeat, true);
      xmlhttp.send();
    }
  }

    window.onload = function() {
      if (<?php echo $repeatExternal; ?>  == 1){
        getRepeatData();

      }else{
        getRepeatData();
      } 

    createLegend();

    }
    
    
  
    

  </script>
</body>

        

        
