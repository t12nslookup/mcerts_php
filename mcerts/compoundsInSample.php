<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('datefunctions.php');
require('../utility/stats.php');
require('../utility/mathematics.php');

//$db->debug=true;


if (isset($_GET['EID']))
	$EID = $_GET['EID'];

if (isset($_GET['ReferenceMaterialID'])) {
	$RefMatID = (get_magic_quotes_gpc()) ? $_GET['ReferenceMaterialID'] : addslashes($_GET['ReferenceMaterialID']);
} else exit;

$query_Compounds = sprintf("SELECT * FROM compounds WHERE ReferenceMaterialID = %s order by compound", $RefMatID);
$Compounds = $db->GetAll($query_Compounds);
$query_ReferenceMaterial = sprintf("SELECT * FROM reference_materials WHERE ReferenceMaterialID = %s", $RefMatID);
$row_ReferenceMaterial  = $db->GetRow($query_ReferenceMaterial);

if (isset($_GET['ReferenceMaterialID'])) {
	$query_EID = sprintf("SELECT DISTINCT EID FROM validation_reference_materials WHERE ReferenceMaterialID = %s", $_GET['ReferenceMaterialID']);
	$row_EID = $db->GetRow($query_EID);
	$EID = $row_EID['EID'];
} else {
	$EID = "";
};

if (isset($EID)) {
	$sqlValidationName = "select name from validation_reports where EID = (select EID from validation_reference_materials where referencematerialid=" . $RefMatID . ")";
	$sqlManuallyAdded = "select manuallyadded from validation_reports where EID = (select EID from validation_reference_materials where referencematerialid=" . $RefMatID . ")";
	$validationName = $db->GetOne($sqlValidationName);
	$manuallyAdded = $db->GetOne($sqlManuallyAdded);
	$sqlEquipmentID = "select equipmentid from validation_reports where EID=" . $EID;
	$equipmentID = $db->GetOne($sqlEquipmentID);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title>Compounds in Reference Sample</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<style type="text/css">
		@import url(../css/stylesheet.css);
	</style>
</head>

<body onLosad="new Effect.Highlight ('refmat')">
	<div align="center">
		<h3 class="title" id="refmat"><a href="index.php">View All Validation Reports</a> &gt; <a href="viewReferenceMaterials.php?EID=<?php echo $EID ?>">View Reference Materials in '<?php echo $validationName ?>'</a> &gt; Analytes in '<?php echo $row_ReferenceMaterial['REFERENCEMATERIAL']; ?>'</h3>
		<p>
			<?php
			include('locked.php');

			if ($locked != true) { ?>

				<?php if ($manuallyAdded == 1) { ?>
					<a href="addManualAnalyte.php?referencematerialid=<?php echo $RefMatID ?>">[add analyte]</a> <a href="createBatchesForManualDataEntry.php?referencematerialid=<?php echo $RefMatID ?>">[set batch dates]</a><?php } else { ?>
					<a href="addNewCompound.php?EID=<?php echo $EID ?>&ReferenceMaterialID=<?php echo $row_ReferenceMaterial['REFERENCEMATERIALID'] ?>">[Add analytes to reference material]</a><a href="addManualAnalyte.php?referencematerialid=<?php echo $RefMatID ?>"><?php } ?>
					<p>
						<a href="selectQCInstrument.php?sampleid=<?php echo $RefMatID ?>">[Use the Exact Bias/Precision given here as breach limits in the QC Charts]</a>
					<? } ?>

					</a>
		</p>
		<table border="1">
			<tr>
				<th>Analyte</th>
				<th>Statistical<br>
					Analysis<br>
					Report</th>
				<th>Target SD </th>
				<th>Concentration</th>
				<th>Target Bias </th>
				<th>Exact Bias <br>from Summary</th>
				<th>Exact Precision<br> from Summary</th>
				<th>Include In Report </th>
				<th>DELETE FROM MCERTS </th>
			</tr>
			<?php foreach ($Compounds as $row_Compounds) { ?>
				<?php if (count($Compounds) > 0) { // Show if recordset not empty 
				?>
					<tr>
						<td><?php echo $row_Compounds['COMPOUND']; ?>
							<?php if ($locked != true) { ?>
								<a href="updateCompound.php?CompoundID=<?php echo $row_Compounds['COMPOUNDID']; ?>&EID=<?php echo $EID ?>&ReferenceMaterialID=<?php echo $row_ReferenceMaterial['REFERENCEMATERIALID'] ?>">[edit]</a><a href="addBatchesToAnalyte.php?ReferenceMaterialID=<?php echo $row_ReferenceMaterial['REFERENCEMATERIALID'] ?>&CompoundID=<?php echo $row_Compounds['COMPOUNDID'] ?>">
									<?php if ($manuallyAdded == 1) { ?>
										[add batch data]
									<?php } ?>
								</a> <?php } ?>
						</td>
						<td>
							<div align="center"><a href="QC.php?ReferenceMaterialID=<?php echo $row_ReferenceMaterial['REFERENCEMATERIALID'] ?>&CompoundID=<?php echo $row_Compounds['COMPOUNDID'] ?>">[VIEW]</a></div>
						</td>
						<td>
							<div align="center"><?php echo $row_Compounds['TARGETSTANDARDDEVIATION']; ?></div>
						</td>
						<td>
							<div align="center"><?php echo $row_Compounds['TARGETCONCENTRATION']; ?></div>
						</td>
						<td>
							<div align="center"><?php echo $row_Compounds['TARGETBIAS']; ?></div>
						</td>
						<td>
							<div align="center"><?= sigfigs($row_Compounds['SUMMARY_PRECISION'], 3, 3) ?></div>
						</td>
						<td>
							<div align="center"><?= sigfigs($row_Compounds['SUMMARY_BIAS'], 3, 3) ?></div>
						</td>
						<td><?php if (count($Compounds) > 0) { // Show if recordset not empty 
								?>
								<div align="center"><?php echo $row_Compounds['INCLUDEINREPORT'] == 1 ? "Yes" : "No"; ?>
								<?php } // Show if recordset not empty 
								?>
								</div>
						</td>
						<td>
							<?php if ($locked != true) { ?>
								<div align="center"><a href="deleteCompound.php?CompoundID=<?php echo $row_Compounds['COMPOUNDID'] ?>&ReferenceMaterialID=<?php echo $row_ReferenceMaterial['REFERENCEMATERIALID'] ?>">
										[DELETE]
									</a>
								<?php } ?>
								</div>
						</td>
					</tr>
				<?php } // Show if recordset not empty 
				?>
			<?php } //end foreach 
			?>
		</table>
	</div>
</body>

</html>