<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
//$db->debug=true;

$sample_id=$_GET['sampleid'];
$eid = $_GET['eid'];

$query_samples = sprintf("SELECT * FROM samples where deleted=0 and eid=%s order by name",$eid);
$samples= $db->GetAll($query_samples);
?>

<html>
<head>
<meta http-equiv="Content-Language" content="en" />
<meta name="GENERATOR" content="PHPEclipse 1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Select QC Standard</title>
</head>
<body>

  <h1>Select QC Standard</h1>
  
<ul>
<? foreach($samples as $sample) { ?>
<li>
<a href='copyMCERTStoQC.php?sampleid=<?=$sample_id?>&eid=<?=$eid?>&qc_sampleid=<?=$sample['SAMPLEID']?>'><?=$sample['NAME']?></a>
</li>
<? } ?>
</ul>
</body>
</html>
  
