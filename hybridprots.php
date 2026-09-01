<!DOCTYPE html>
<html lang="en">
<head>
    <title>SARS-CoV2 Page</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <?php include "Navigation.php";?>
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
    </style>

  <body class="search-body">
  <h4 class="search-header">Create chimeric peptides from deletion start and ends</h4> 
  <div class="panel-body">
    <div id="searchgrid">         
      <div class="form-group" style="height:10%; width:100%;">
        <div class="row">
          <fieldset id="Gene_row">
            
            <div class="col-md-2">
                <label for="Start">Start</label>
                <input id="Start" type="text" class="form-control" id="Start" />
            </div>
            <div class="col-md-2">
                <label for="End">End</label>
                <input id="End" type="text" class="form-control" id="End" />
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success" id="submit_btn" name="submit">Submit</button>
                <button type="button" onClick="resetForm();" id="clear_btn" class="btn btn-secondary">Clear</button>
            </div>

            

          </fieldset>
          <br />
      </div>
      </div>
      <div id="datagrid" style="height:90%; width:100%;">
      </div>
    </div>
    <div id="dataview" style="height:90%; width:100%;"></div>
  </div>
  <!--input type="button" id='script' name="scriptbutton" value=" Run Script " onClick="goPython()"-->

   <script src="http://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
   <script src="./fastas/reference.js"></script>
   <script src="./JS/hybridprotjs.js"></script>
   <script src = "./JS/tst.js"></script>

  

</body>
</html>
