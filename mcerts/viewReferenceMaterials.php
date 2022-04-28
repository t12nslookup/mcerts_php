<?php
/*
 * Created on 04-Aug-2005
 *
 * Views a single validation and all the reference materials in it.
 * 
 */
 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('datefunctions.php');
require('../utility/stats.php');
//$db->debug=true;

$query_ValidationReports = "SELECT * FROM validation_reports WHERE EID=".$_GET['EID'];
$row_ValidationReports = $db->GetRow($query_ValidationReports);

$eid=$_GET['EID'];

if (isset($_SERVER['QUERY_STRING'])) {
		$insertGoTo= "&".$_SERVER['QUERY_STRING'];
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<style type="text/css">
@import url(../css/stylesheet.css);
</style>
<meta http-equiv="Content-Language" content="en" />
<meta name="GENERATOR" content="PHPEclipse 1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>MCERTS MAIN MENU</title>
</head>
<body >
<div align="center">
<?php include('message.php') ?>
<?php include('locked.php');	 ?>
<h3 class="title" id="refmat"><a href="index.php">
  View All Validation Reports</a> &gt; Reference Materials in '<?php  echo $row_ValidationReports['NAME'] ?>'</h3>
<p>Pass \ fail summary :  
    <?php if($locked!=true) { ?>
        <a href="../reports/<?php echo $row_ValidationReports['EID'] ?>.htm">View</a> / <a href="CalculateLevelSummary.php?EID=<?php echo $row_ValidationReports['EID'] ?>">Create</a>
        <?php } else { ?>
        <a href="../reports/<?php echo $row_ValidationReports['EID'] ?>final.htm">View</a> 
        <?php } ?>
</p>
<p>MCERTS summary : <a href="../reports/MCERTS<?php echo $row_ValidationReports['EID'] ?>.htm">View</a>
    <?php if($locked!=true) { ?>
    / <a href="McertsLevelSummary.php?EID=<?php echo $row_ValidationReports['EID'] ?>">Create</a>
    <?php } ?> 
    <?php if($locked!=true) { ?>
</p>
<p><a href="searchReferenceMaterials.php?EID=<?php echo $_GET['EID']?>">Add new reference material</a>
  <?php } ?>
</p>
<table border="1" >
  <tr class="tableheader">
    <th>Reference Material </th>
    <th>Trivial Name </th>
    <th>Analytes</th>
    <th>Batches</th>
    <th>CRM<br>Certificate</th>
    </tr>
   <?php 
	$query_ReferenceMaterials = "SELECT ReferenceMaterial,reference_materials.ReferenceMaterialID,reference_materials.defaulttargetstandarddeviation,reference_materials.referencematerialid,
								 reference_materials.DEFAULTTARGETBIAS, reference_materials.TRIVIALNAME,reference_materials.certificatefilename
								FROM validation_reference_materials, reference_materials 
								WHERE validation_reference_materials.EID = '".$row_ValidationReports['EID']."' 								
								AND reference_materials.ReferenceMaterialID= validation_reference_materials.ReferenceMaterialID";
	$ReferenceMaterials = $db->GetAll($query_ReferenceMaterials);
	$totalRows_ReferenceMaterials = count($ReferenceMaterials);
	?>
	  <?php foreach($ReferenceMaterials as $row_ReferenceMaterials) { ?>
		  <?php if ($totalRows_ReferenceMaterials > 0) { // Show if recordset not empty ?>
		  <tr>
			<td><?php echo $row_ReferenceMaterials['REFERENCEMATERIAL'];
									 $refMatId=$row_ReferenceMaterials['REFERENCEMATERIALID']; ?></td>
			<td><?php echo $row_ReferenceMaterials['TRIVIALNAME']; ?>
			  <?php if($locked!=true) { ?>
              <a href="updateReferenceMaterial.php?ReferenceMaterialID=<?php echo $row_ReferenceMaterials['REFERENCEMATERIALID']?>">Edit</a>
            <?php } ?></td>
			<td> <div align="center"><a href="compoundsInSample.php?ReferenceMaterialID=<?php echo $refMatId.$insertGoTo ?>">View</a></div></td>
			<td>
			  <?php echo getNumberOfBatches($refMatId)?>
			  <?php if($locked!=true) { ?>			  <a href="selectBatches.php?ReferenceMaterialID=<?php echo $refMatId.$insertGoTo ?>">Select batches</a>
			  <?php } else { ?>
              <a href="viewBatches.php?ReferenceMaterialID=<?php echo $refMatId.$insertGoTo ?>">[view batches]
              <?php } ?>
              </a>			</td>
			<td>
			<div align="center">
			<?php if($row_ReferenceMaterials['CERTIFICATEFILENAME']!="") { ?>
			<a href="../certificates/<?php echo $row_ReferenceMaterials['CERTIFICATEFILENAME']?>">View</a>
			<?php if($locked!=true) { ?>
			<a href="deleteDocument.php?type=crm&ReferenceMaterialID=<?=$row_ReferenceMaterials['REFERENCEMATERIALID']?>&EID=<?=$eid?>">[remove]</a>
			<br>
			<?php } ?>
		      <?php } ?>
		      <?php if($locked!=true) { ?>
              <a href="uploadDocument.php?type=crm&ReferenceMaterialID=<?=$row_ReferenceMaterials['REFERENCEMATERIALID']?>&EID=<?=$eid?>">Add</a>               <?php } ?>
			</div>			</td>
		  </tr>			 
		  <?php } // Show if recordset not empty ?>
	  <?php } ?>  
</table>
</div>
</body>

					

