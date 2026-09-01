<!DOCTYPE html>
<html lang="en">
<head>
    <title>tatDB Page</title>
    <link href="bootstrap.css" rel="stylesheet" />
    <style>
        pre {
            font-family: monospace;
            margin-left: 20px;
            margin-right: 20px;
        }
        h4 {
            margin-left: 20px;
            padding-top: 5px;
        }
    </style>
    <div class="panel-group">
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                  <a class="navbar-brand" href="index.php">tatDB</a>
                </div>
                <ul class="nav navbar-nav">
                    <li><a href="index.php">Home</a></li>
                    <li class="active"><a href="search.php">Search</a></li>
                    <li><a href="reference.php">Reference</a></li>
                    <li><a href="help.php">Help</a></li>
                </ul>
            </div>
        </nav>
    </div>
</head>

<body>

<?php
	include 'conn/connection.php';
	include 'function.php';

	if(isset($_GET['guide_seq'])){
		$guide_seq = $_GET['guide_seq'];
	}
	if(isset($_GET['target_seq'])){
		$target_seq = $_GET['target_seq'];
	}

	$sql = "SELECT guide_binary, target_binary, target_seq, target_ref_id, target_ref_mapped_start, target_ref_mapped_end, target_transcript_id, target_gene_mapped_region, gene_name, transcript_biotype, read_count, free_energy FROM clash_chimeras LEFT JOIN transcripts ON clash_chimeras.target_transcript_id = transcripts.transcript_id WHERE guide_seq = '" . $guide_seq . "' AND target_seq = '" . $target_seq . "'";
	//echo $sql."<br / >";
	$result = $con->query($sql);
	if($result->num_rows == 0){
		echo "<h2>No results found.</h2>";
        exit();
	}
	
	$sql_guide = "SELECT DISTINCT alternative_id, guide_id, guide_ref_id_format, guide_ref_mapped_region, guide_ref_mapped_start_s1, guide_ref_mapped_end_s1 FROM trf WHERE guide_seq = '" . $guide_seq . "'";
    //echo $sql_guide."<br / >";
    
    $sql_motif = "SELECT motif_start, motif_end, motif_bits FROM trf_motif WHERE guide_seq = '" . $guide_seq . "'";
    //echo $sql_motif."<br / >";
	
	$sql_par_mut = "SELECT mut_pos_s1, rpm FROM trf_ip_mut_rpm WHERE guide_seq = '" . $guide_seq . "' ORDER BY rpm DESC";
    //echo $sql_par_mut."<br / >";
	
	$result_motif = $con->query($sql_motif);
    if($result_motif->num_rows > 0){
		$row_motif = $result_motif->fetch_assoc();
		$motif_bits = $row_motif['motif_bits'];
		$motif_start = $row_motif['motif_start'];
        $motif_end = $row_motif['motif_end'];
        $guide_seq_format = formatTrf($guide_seq, $motif_start, $motif_end);
	} else {
		$motif_bits = "";
		$motif_start = 0;
		$motif_end = 0;
		$guide_seq_format = $guide_seq;
	}
    
    $parclip_format = array();
    $result_par_mut = $con->query($sql_par_mut);
    if($result_par_mut->num_rows > 0){
		while ($row_par_mut = $result_par_mut->fetch_assoc()) {
			$mut_pos_s1 = $row_par_mut['mut_pos_s1'];
			$parclip_line = "T > C:    ".substr($guide_seq, 0, $mut_pos_s1-1). "<span style='text-decoration:underline;text-decoration-color:red'>C</span>".substr($guide_seq, $mut_pos_s1)."\tRPM=".$row_par_mut['rpm']."<br / >";
			array_push($parclip_format, $parclip_line);
		}
	}

	$result_guide = $con->query($sql_guide);    
    if ($result_guide->num_rows == 0) {
        echo "<h2>No tRFs found.</h2>";
        exit();
    } else {
		$trf_mapping_array = array();
		while ($row_guide = $result_guide->fetch_assoc()) {
			$trf_id = "tRF ID: " .$row_guide['guide_id'];
			$trf_type = "Type: <a href='trf_type.php?trna_id=".$row_guide['guide_ref_id_format']."&trna_region=".$row_guide['guide_ref_mapped_region']."' target='_blank'>".$row_guide['guide_ref_mapped_region']."</a> (click for all isoforms of this type)";
			$trf_mapping = "tRNA gene: ".$row_guide['guide_ref_id_format'].", Start-End: ".$row_guide['guide_ref_mapped_start_s1']."-".$row_guide['guide_ref_mapped_end_s1'];
			$trf_mapping_array[$row_guide['alternative_id']] = array($trf_id, $trf_type, $trf_mapping);
		}
	}


	echo "<h4>tRF: </h4>";
	echo "<pre id='trf'>";
	
	echo "Sequence: ".$guide_seq_format ."<br / >";
	list($trf_id, $trf_type, $trf_mapping) = $trf_mapping_array[0];
	echo $trf_id."<br / >";
	echo $trf_mapping.", ".$trf_type."<br / >";
	foreach ($parclip_format as $parclip_line) {
		echo $parclip_line;
	}
	
	echo "</pre>";
	echo "<br / >";
	
	//For debugging
	$VERBOSE = 0;
	while($row = $result->fetch_assoc()){
		$target_seq = $row['target_seq'];
		$guide_binary = substr($row['guide_binary'],1);
		$target_binary = substr($row['target_binary'],1);
		$transcript_id = $row['target_transcript_id'];

		echo "<h4>Target: </h4>";
		echo "<pre id='target'>";
		echo "Sequence: ".$target_seq . "<br / >";
		echo "Gene name: " .$row['gene_name'] ."<br / >";
		if (substr($transcript_id, 0, 4)=='ENST'){
			echo "Transcript id: <a href='https://useast.ensembl.org/Homo_sapiens/Transcript/Summary?db=core;t=".$transcript_id."' target='_blank'>" .$transcript_id."</a><br / >";
		} elseif (substr($transcript_id, 0, 3)=='RNA') {
			echo "Transcript id: <a href='https://www.ncbi.nlm.nih.gov/gene/?term=".$transcript_id."' target='_blank'>" .$transcript_id."</a><br / >";
		} elseif (substr($transcript_id, 0, 3)=='hsa') {
			echo "Transcript id: <a href='http://www.mirbase.org/cgi-bin/mirna_entry.pl?acc=".$transcript_id."' target='_blank'>" .$transcript_id."</a><br / >";
		}
		else {
			echo "Transcript id: ".$transcript_id."<br / >";
		}
		echo "Region: " .$row['target_gene_mapped_region'] . "<br / >";
		if($row['target_ref_id'] == $transcript_id){
			echo "Start-End: ".$row['target_ref_mapped_start']."-".$row['target_ref_mapped_end'] . "<br / >";
		}else{
			echo "Start-End: ".$row['target_ref_id'].": ".$row['target_ref_mapped_start']."-".$row['target_ref_mapped_end'] . "<br / >";
		}
		echo "Number of reads supporting this unique hybrid: " . $row['read_count'] . "<br / >";
		echo "</pre>";
		
		if ($VERBOSE==1){
			echo "Guide Sequence: ".$guide_seq."<br / >";
			echo "Target Sequence: ".$target_seq."<br / >";
			echo "Guide Binary: " .$guide_binary."<br / >";
			echo "Target Binary: ".$target_binary."<br / >";
		}

		$lines_format = printHybrid($guide_seq, $target_seq, $guide_binary, $target_binary, $motif_start, $motif_end);
		
		echo "<h4>Hybrid: </h4>";
		echo "<pre id='hybrid'>";
		echo "MFE: " . $row['free_energy'] . "<br / >";
		echo $lines_format;
		echo "</pre>";
	}
	
?>
</body>
</html>
