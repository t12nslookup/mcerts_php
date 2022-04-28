<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('datefunctions.php');
//$db->debug = true;

if (isset($_GET['ReferenceMaterialID'])) {
  $refMatId = (get_magic_quotes_gpc()) ? $_GET['ReferenceMaterialID'] : addslashes($_GET['ReferenceMaterialID']);
}

//get all existing defined batches:
$query_ExistingBatches = sprintf("SELECT * FROM batches 
									WHERE ReferenceMaterialID = %s and selected=1 order by replicate1", $refMatId);
$existingBatches = $db->GetAll($query_ExistingBatches);

//get EID,validation name and reference material name
$sqlEID="select EID 
			from validation_reports 
			where EID=(select EID from validation_reference_materials where Referencematerialid=".$_GET['ReferenceMaterialID'].")";
$EID= $db->GetOne($sqlEID);	
$sqlValidationName="select name from validation_reports where EID = (select EID from validation_reference_materials where referencematerialid=".$refMatId.")";
$validationName=$db->GetOne($sqlValidationName);
$sqlEquipmentID="select equipmentid from validation_reports where EID=".$EID;
$equipmentID=$db->GetOne($sqlEquipmentID);
$sqlReferenceMaterial="select referencematerial from reference_materials where referencematerialid=".$refMatId;							
$referenceMaterial=$db->GetOne($sqlReferenceMaterial);							

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<!--<style type="text/css">@import url(../css/stylesheet.css);</style>-->
<link href="../css/stylesheet.css" media="screen, projection" rel="stylesheet" type="text/css" />

</head>

<body>
<div class="box">
<?php include('locked.php');?>

<!--
<h3 class="title">
<?php if($locked!=true) { ?>
<a href="newindex.php">View All Validation Reports</a> &gt; 
<?php } ?>
	<?php if($locked!=true) { ?>
	<a href="viewReferenceMaterials.php?EID=<?php echo $EID ?>">View Reference Materials in '<?php echo $validationName; ?>'</a>
	<?php } ?>
	 &gt; Batches in '<?php echo $referenceMaterial; ?>'</h3>
<p>Pass \ fail summary :<?php if($locked!=true) { ?>
  <a href="../reports/<?php echo $EID ?>.htm">View</a> <a href="CalculateLevelSummary.php?EID=<?php echo $EID ?>">Create</a>
  <?php } else { ?>
  <a href="../reports/<?php echo $EID ?>final.htm">View</a>
  <?php } ?>
-->
</p>

<!--
<p>MCERTS summary : <a href="../reports/MCERTS<?php echo $EID ?>.htm">View</a>
    <?php if($locked!=true) { ?>
    <a href="McertsLevelSummary.php?EID=<?php echo $EID ?>">Create</a>
    <?php } ?>
</p>
-->
<table  border="0" cellspacing="0" cellpadding="0">
  <tr class="tableheader">
    <td width="207">Replicate 1 </td>
    <td width="207">Replicate 2 </td>
    </tr>
  <?php 
	  $counter=0;
	  foreach($existingBatches as $row_ExistingBatches) { ?>
  <?php if (count($existingBatches) > 0) { // Show if recordset not empty ?>
  <tr>
    <td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE1']); ?></td>
    <td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE2']); ?></td>
    </tr>
  <?php } // Show if recordset not empty ?>
  <?php $counter++;
		}//end for ?>
</table>
</div>
</body>
</html>
