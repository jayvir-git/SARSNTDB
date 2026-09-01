<head>
    <title>SARS-CoV2 Navigation</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
      $(function(){
        $(".dropdown").hover(            
                function() {
                    $('.dropdown-menu', this).stop( true, true ).fadeIn("fast");
                    $(this).toggleClass('open');
                    $('b', this).toggleClass("caret caret-up");                
                },
                function() {
                    $('.dropdown-menu', this).stop( true, true ).fadeOut("fast");
                    $(this).toggleClass('open');
                    $('b', this).toggleClass("caret caret-up");                
                });
      });
      function resetForm($form) {
        $form.find('input:text, input:password, input:file, textarea').val('');
        $form.find('select').prop('selectedIndex',0);
        $form.find('input:radio, input:checkbox').removeAttr('checked').removeAttr('selected');
      }
    </script>
    <style>
      .panel-group {
        margin-bottom: 0 !important;
      }
      .navbar {
        margin-bottom: 0 !important;
      }
      .caret-up {
          width: 0; 
          height: 0; 
          border-left: 4px solid rgba(0, 0, 0, 0);
          border-right: 4px solid rgba(0, 0, 0, 0);
          border-bottom: 4px solid;
          
          display: inline-block;
          margin-left: 2px;
          vertical-align: middle;
      }
      .navbar-inverse .navbar-nav>.active>a {
        color: #fff;
        background-color: #5cb85c;
        border-color: #4cae4c;
      } 
    </style>

    <div class="panel-group">
        <nav class="navbar navbar-inverse">
            <div class="container-fluid">
                <div class="navbar-header">
                  <a class="navbar-brand" href="GenomeSearch.php">SARSNTDB</a>
                </div>
                <div class="collapse navbar-collapse">
                  <ul class="nav navbar-nav">
                    
                    <li class="dropdown
                        <?php 
                            if(basename($_SERVER['PHP_SELF'])=="GenomeSearch.php" || basename($_SERVER['PHP_SELF'])=="MutationsSearch.php" || basename($_SERVER['PHP_SELF'])=="TwoSegmentStructures.php" || basename($_SERVER['PHP_SELF'])=="RepeatCsvTwoSegment.php" || basename($_SERVER['PHP_SELF'])=="JunctionGroupQuery.php")
                            { echo "active"; } 
                            else { echo ""; } 
                        ?>
                    "> 
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                          Search <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                          <li><a href="GenomeSearch.php"> Genome </a></li>
                          <li><a href="MutationsSearch.php"> Mutations </a></li>
                          <li><a href="motifvisualizer.php"> Repeats </a></li>
                          <li><a href="TwoSegmentStructures.php"> 2-segment (sgmRNA) </a></li>
                          <li><a href="JunctionGroupQuery.php"> Junction groups </a></li>
                          <li><a href="RepeatCsvTwoSegment.php"> CSV repeats vs 2-segment </a></li>
                        </ul>
                    </li>
                    <li class="<?php if(basename($_SERVER['PHP_SELF'])=="reference.php"){ echo "active"; } else { echo ""; } ?>">
                        <a href="reference.php">Reference</a>
                    </li>
                    
                    <li class="<?php if(basename($_SERVER['PHP_SELF'])=="help.php"){ echo "active"; } else { echo ""; } ?>">
                        <a href="help.php">Help</a>
                    </li>
                  </ul>
                </div>
            </div>
        </nav>
    </div>
</head>