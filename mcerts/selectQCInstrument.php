<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
//$db->debug=true;

$sample_id=$_GET['sampleid'];
$query_machines = sprintf("SELECT * FROM machines where exclude=0 order by name");
$machines = $db->GetAll($query_machines);
?>

<html>
<head>
<meta http-equiv="Content-Language" content="en" />
<meta name="GENERATOR" content="PHPEclipse 1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Select QC Instrument</title>
</head>
<body>

  <h1>Select QC Instrument</h1>
  
<table>
<tr>
<th>EID</th>
<th>Name</th>
</tr>
<? foreach($machines as $machine) { ?>
<tr>
<td><?=$machine['EID']?></td>
<td><a href='selectQCStandard.php?sampleid=<?=$sample_id?>&eid=<?=$machine['EID']?>'><?=$machine['NAME']?></a></td>
</tr>
<? } ?>
</table>

</body>
</html>
  