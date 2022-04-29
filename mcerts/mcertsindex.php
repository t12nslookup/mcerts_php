<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('datefunctions.php');
require('../utility/stats.php');
//$db->debug=false;
$expired = isset($_GET['SHOWEXPIRED']) ? 1 : 0;
$query_ValidationReports = "SELECT * FROM validation_reports WHERE expired =" . $expired;

$validationReports = $db->GetAll($query_ValidationReports);
$totalRows_ValidationReports = count($validationReports);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title>MCERTS VALIDATION REPORTS</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<!--<style type="text/css">@import url(../css/stylesheet.css);</style>-->
	<style type="text/css">
		@import url(../css/stylesheet.css);
	</style>
</head>

<body>
	<img src="../images/mcertsSmall.gif" width="137" height="40" hspace="5">
	<div id="navcontainer">
		<ul id="navlist">
			<li id="active"><a href="addUpdateValidationSet.php">add new validation set</a> </li>
			<li><a href="../index.php">upload report</a> </li>
			<?php if (isset($_GET['SHOWEXPIRED'])) { ?>
				<li><a href="mcertsindex.php">view non-expired validation reports</a></li>
			<?php } else { ?>
				<li><a href="mcertsindex.php?SHOWEXPIRED=true">view expired validation reports</a></li>
			<?php } ?>
			<li><a href="addManualValidationSet.php">manually add 11x2 matrix</a></li>
		</ul>
	</div>
	<p>&nbsp;</p>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr class="tableheader">
			<td width="14%">Equipment ID </td>
			<td>Date of Approval </td>
			<td width="120">Start Date </td>
			<td width="120">End Date </td>
			<td width="120">Approved By </td>
		</tr>
		<?php
		foreach ($validationReports as $row_ValidationReports) {
			$query_ReferenceMaterials = "SELECT ReferenceMaterial,reference_materials.ReferenceMaterialID 
										FROM validation_reference_materials, reference_materials 
										WHERE validation_reference_materials.EID = '" . $row_ValidationReports['EID'] . "' 
										AND reference_materials.IncludeInReport=1
										AND reference_materials.ReferenceMaterialID= validation_reference_materials.ReferenceMaterialID";
			$ReferenceMaterials = $db->GetAll($query_ReferenceMaterials);
			$totalRows_ReferenceMaterials = count($ReferenceMaterials);
		?>
			<?php if ($totalRows_ValidationReports > 0) { // Show if recordset not empty 
			?>
				<tr class="ReportRow">
					<td><?php echo $row_ValidationReports['EQUIPMENTID']; ?> <a href="addUpdateValidationSet.php?EID=<?php echo  $row_ValidationReports['EID'] ?>">[edit] </a></td>
					<td><?php echo getStringDate($row_ValidationReports['DATEOFAPPROVAL']); ?></td>
					<td width="120"><?php echo getStringDate($row_ValidationReports['STARTDATE']); ?></td>
					<td width="120"><?php echo getStringDate($row_ValidationReports['ENDDATE']); ?></td>
					<td width="120"><?php echo $row_ValidationReports['APPROVEDBY']; ?></td>
				</tr>
			<?php } // Show if recordset not empty 
			?>
			<tr>
				<td>&nbsp;</td>
				<td colspan="4"><a href="CalculateLevelSummary.php?EID=<?php echo $row_ValidationReports['EID'] ?>"> </a><a href="../reports/<?php echo $row_ValidationReports['EID'] ?>.htm">[view summary]</a> <a href="CalculateLevelSummary.php?EID=<?php echo $row_ValidationReports['EID'] ?>">[create summary]</a></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="4"><span class="tableheader">Reference Materials <a href="addReferenceMaterial.php?EID=<?php echo $row_ValidationReports['EID'] ?>">[add new reference material] </a></span></td>
			</tr>
			<?php foreach ($ReferenceMaterials as $row_ReferenceMaterials) { ?>
				<?php if ($totalRows_ReferenceMaterials > 0) { // Show if recordset not empty 
				?>
					<tr>
						<td>&nbsp;</td>
						<td colspan="3">
							<?php echo $row_ReferenceMaterials['REFERENCEMATERIAL'];
							$refMatId = $row_ReferenceMaterials['REFERENCEMATERIALID']; ?> <a href="compoundsInSample.php?ReferenceMaterialID=<?php echo $refMatId ?>">[view analytes <?php echo getNumberOfCompounds($refMatId) ?>]</a> - <a href="selectBatches.php?ReferenceMaterialID=<?php echo $refMatId ?>"><?php echo getNumberOfBatches($refMatId) ?> Batches</a>
						<td width="120"><a href="deleteReferenceMaterial.php?ReferenceMaterialID=<?php echo $refMatId ?>">[expire]</a></td>
					</tr>
				<?php } // Show if recordset not empty 
				?></td>
			<?php } ?>
		<?php } ?>
	</table>
</body>

</html>