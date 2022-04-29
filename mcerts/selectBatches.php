<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('datefunctions.php');
//$db->debug = true;

if (isset($_GET['ReferenceMaterialID'])) {
	$refMatId = (get_magic_quotes_gpc()) ? $_GET['ReferenceMaterialID'] : addslashes($_GET['ReferenceMaterialID']);
	$sqlManuallyAdded = "select manuallyadded from validation_reports where EID = (select EID from validation_reference_materials where referencematerialid=" . $refMatId . ")";
	$manuallyAdded = $db->GetOne($sqlManuallyAdded);
}

if (isset($_GET['EID']))
	$EID = $_GET['EID'];
else {
	$sqlEID = "select EID 
			from validation_reports 
			where EID=(select EID from validation_reference_materials where Referencematerialid=" . $_GET['ReferenceMaterialID'] . ")";
	$EID = $db->GetOne($sqlEID);
}



$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
	$editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

//remove adjustment material. Remove all adjustment time values from current batches
if ((isset($_POST["DeleteAdjustment"])) && ($_POST["DeleteAdjustment"] == "DeleteAdjustment")) {
	$sqlDeleteAdjustmentBatches = sprintf("DELETE from batches where referencematerialid=%s", $refMatId);
	$sqlUpdateReferenceMaterial = sprintf(
		"update reference_materials set adjustmentmaterial='' where referencematerialid=%s",
		$refMatId
	);
	$db->Execute($sqlDeleteAdjustmentBatches);
	$db->Execute($sqlUpdateReferenceMaterial);
}

//batch add button been pressed:
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "addBatch")) {
	if (
		$_POST['firstReplicate'] > "" && $_POST['secondReplicate'] > ""
		&& $_POST['firstReplicate'] != $_POST['secondReplicate']
	)  //replicate fields are not empty or equal to each other
	{
		if ($_POST['multiplier'] == "")
			$multiplier = 1;
		else
			$multiplier = $_POST['multiplier'];

		if (isset($_POST['adjustmentRun']))
			$adjustmentRun = $_POST['adjustmentRun'];
		else $adjustmentRun = "";

		$insertSQL = sprintf(
			"INSERT INTO batches (ReferenceMaterialID, Replicate1, Replicate2,adjustmenttime,selected,multiplier) VALUES (%s, %s, %s,%s,1,%s)",
			GetSQLValueString($_GET['ReferenceMaterialID'], "int"),
			GetSQLValueString($_POST['firstReplicate'], "date"),
			GetSQLValueString($_POST['secondReplicate'], "date"),
			GetSQLValueString($adjustmentRun, "date"),
			GetSQLValueString($multiplier, "float")
		);

		$db->Execute($insertSQL);
	}

	$insertGoTo = "selectBatches.php";
	if (isset($_SERVER['QUERY_STRING'])) {
		$insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
		$insertGoTo .= $_SERVER['QUERY_STRING'];
	}
	//header(sprintf("Location: %s", $insertGoTo));
}



//select adjustment reference material button been pressed
if ((isset($_POST["SelectAdjustment"])) && ($_POST["SelectAdjustment"] == "true")) {
	$sqlAddAdjustmentMaterial = sprintf("update reference_materials set adjustmentmaterial='%s' where referencematerialid='%s' ", $_POST['AdjustmentMaterial'], $refMatId);
	$db->Execute($sqlAddAdjustmentMaterial);
}

//get adjustment material if it exists
$sqlAdjustmentMaterial = sprintf("select adjustmentmaterial from reference_materials where referencematerialid=%s", $refMatId);
$adjustmentMaterial = $db->GetOne($sqlAdjustmentMaterial);

//get all existing defined batches:
$query_ExistingBatches = sprintf("SELECT * FROM batches WHERE ReferenceMaterialID = %s order by replicate1", $refMatId);
$existingBatches = $db->GetAll($query_ExistingBatches);

$startD = $existingBatches[0]["REPLICATE1"];
$endD =   $existingBatches[0]["REPLICATE2"];

$n = "";
foreach ($existingBatches as $batch) {
	$n = $n . "-one-";
	if ($batch["REPLICATE1"] < $startD) {
		$startD = $batch["REPLICATE1"];
	}
	if ($batch["REPLICATE2"] < $startD) {
		$startD = $batch["REPLICATE2"];
	}

	if ($batch["REPLICATE1"] > $endD) {
		$endD = $batch["REPLICATE1"];
	}
	if ($batch["REPLICATE2"] > $endD) {
		$endD = $batch["REPLICATE2"];
	}
}

//saving batches
if ((isset($_POST["SaveBatches"])) && ($_POST["SaveBatches"] == "true")) {
	$counter = 0;
	foreach ($existingBatches as $row_ExistingBatches) {
		if (isset($_POST['checkbox' . $counter]))
			$selected = 1;
		else
			$selected = 0;

		$sqlUpdateBatches = sprintf("update batches set selected=%s where batchid=%s", $selected, $row_ExistingBatches['BATCHID']);
		$db->Execute($sqlUpdateBatches);
		$counter++;
	}
	$existingBatches = $db->GetAll($query_ExistingBatches);
}

if (isset($EID)) {
	$sqlValidationName = "select name from validation_reports where EID = (select EID from validation_reference_materials where referencematerialid=" . $refMatId . ")";
	$validationName = $db->GetOne($sqlValidationName);
	$sqlEquipmentID = "select equipmentid from validation_reports where EID=" . $EID;
	$equipmentID = $db->GetOne($sqlEquipmentID);
	$sqlReferenceMaterial = "select referencematerial from reference_materials where referencematerialid=" . $refMatId;
	$referenceMaterial = $db->GetOne($sqlReferenceMaterial);
}

$query_UniqueTimes = sprintf(
	"
					SELECT DISTINCT AcqOn 
					FROM results 
					WHERE results.Sample= 
						  (select ReferenceMaterial 
						  from reference_materials 
						  where reference_materials.ReferenceMaterialID=%s)
					AND results.machine=%s	  
					and not exists
						(SELECT Replicate1 
						FROM batches 
						WHERE ReferenceMaterialID = %s 
						and (Replicate1=AcqOn or Replicate2=AcqOn)) order by AcqOn",
	$refMatId,
	$equipmentID,
	$refMatId
);

$UniqueTimes = $db->GetAll($query_UniqueTimes);


$sqlAdjustmentTimes = sprintf("select distinct acqon from results where sample='%s' and machine=%s order by acqon", $adjustmentMaterial, $equipmentID);
$adjustmentTimes = $db->GetAll($sqlAdjustmentTimes);
if ((isset($_GET["ShowBlank"])) && ($_GET["ShowBlank"] == "true")) {
	//get all reference materials for current machine.  Is used to select adjustment reference material
	$sqlAdjustmentReferenceMaterials = sprintf(
		"select distinct sample from results where machine='%s' and acqon between add_months(%s, -1) and add_months(%s,1)",
		$equipmentID,
		GetSQLValueString($startD, "date"),
		GetSQLValueString($endD, "date")
	);
	$adjustmentReferenceMaterials = $db->GetAll($sqlAdjustmentReferenceMaterials);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title>Select Batches</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<style type="text/css">
		@import url(../css/stylesheet.css);
	</style>
</head>
<div align="center">
	<?php include('locked.php'); ?>
	<?php
	include("menu.php");
	?>

	<body>
		<?= count($UniqueTimes) ?>
		<?= count($adjustmentTimes) ?>
		<?= $sqlAdjustmentReferenceMaterials ?>
		<h3 class="title"><a href="newindex.php">View All Validation Reports</a> &gt;
			<?php if ($locked != true) { ?>
				<a href="viewReferenceMaterials.php?EID=<?php echo $EID ?>">View Reference Materials in '<?php echo $validationName; ?>'</a>
			<?php } ?>
			&gt; Batches in '<?php echo $referenceMaterial; ?>'
		</h3>
		<p><a href="../reports/<?php echo $EID ?>.htm">[view summary]</a>
			<?php if ($locked != true) { ?>
				<a href="CalculateLevelSummary.php?EID=<?php echo $EID ?>">[create summary]</a>
			<?php } ?>
		</p>
		<?php if ($manuallyAdded == 0) { ?>
			<h3>Define Batches</h3>
			<?php if (count($UniqueTimes) == 0) { ?>
				<p>There are no more batches to define.</p>
			<?php } else { ?>
				<form action="<?php echo $editFormAction; ?>" method="POST" name="addBatch" id="addBatch">
					<table border="1"">
			<tr>
			  <td>Replciate 1 </td>
			  <td>Replicate 2 </td>
			  <td>Multiplier</td>
			  <td>&nbsp;</td>
			  <td><?php if ($adjustmentMaterial != "") { ?>
		      Background Correction
	          <?php } else echo '&nbsp;' ?> </td>
		    </tr>
			<tr>
			  <td><select name=" firstReplicate" id="firstReplicate">
						<option value="">None</option>
						<?php foreach ($UniqueTimes as $row_UniqueTimes) {  ?>
							<option value="<?php echo $row_UniqueTimes['ACQON'] ?>"><?php echo getStringDateTime($row_UniqueTimes['ACQON']) ?></option>
						<?php }	?>
						</select></td>
						<td><select name="secondReplicate" id="secondReplicate">
								<option value="" selected>None</option>
								<?php foreach ($UniqueTimes as $row_UniqueTimes) {  ?>
									<option value="<?php echo $row_UniqueTimes['ACQON'] ?>"><?php echo getStringDateTime($row_UniqueTimes['ACQON']) ?></option>
								<?php }	?>
							</select>
						</td>
						<td><input name="multiplier" type="text" id="multiplier" value="1" size="10"></td>
						<td><input type="submit" name="submit" value="Add Batch"></td>
						<td>
							<?php if ($adjustmentMaterial != "") { ?>
								<select name="adjustmentRun" id="adjustmentRun">
									<option selected></option>
									<?php foreach ($adjustmentTimes as $adjustmentTime) {  ?>
										<option value="<?php echo $adjustmentTime['ACQON'] ?>"><?php echo getStringDateTime($adjustmentTime['ACQON']) ?></option>
									<?php }	?>
								</select>
							<?php } ?>
						</td>
						</tr>
					</table>
					<br>
					<a href="autoAddBatches.php?ReferenceMaterialID=<?php echo $_GET['ReferenceMaterialID'] ?>">[Automatically Add All Batches]</a> <input type="hidden" name="MM_insert" value="addBatch">
				</form>
			<?php } //end if 
			?>
			<?php if ($adjustmentMaterial == "") { ?>
				<?php if (!isset($_GET["ShowBlank"])) { ?>
					<a href="selectBatches.php?ShowBlank=true&ReferenceMaterialID=<?php echo $_GET['ReferenceMaterialID'] ?>">Show blank correction materials</a>
				<?php } ?>
				<form name="form2" method="post" action="">
					Select background correction material :

					<select name="AdjustmentMaterial" id="select">
						<option selected></option>
						<?php if (count($UniqueTimes) > 0) { ?>
							<?php foreach ($adjustmentReferenceMaterials as $adjustmentReferenceMaterial) { ?>
								<option value="<?php echo $adjustmentReferenceMaterial['SAMPLE'] ?>"><?php echo $adjustmentReferenceMaterial['SAMPLE'] ?></option>
							<?php } //end for 
							?>
						<?php } ?>
					</select>
					<input type="submit" name="Submit" value="Save">
					<input name="SelectAdjustment" type="hidden" id="SelectAdjustment2" value="true">
				</form>
			<?php } else {
				//get times for selected adjustment material
			?>
				<form name="form4" method="post" action="">
					<p><span class="fieldName">Background correction material :</span> <?php echo $adjustmentMaterial ?>
						<input type="submit" name="Submit2" value="Remove">
						<input name="DeleteAdjustment" type="hidden" id="DeleteAdjustment2" value="DeleteAdjustment">
					</p>
				</form>
			<?php } //end if 
			?>
			<br>
		<?php } ?>
		<h3>Select Batches</h3>
		<p>Check the boxes of the batches you would like to include in the report. Click on the &quot;Save selected batches&quot; button when you change your selection.</p>
		<form name="form1" method="post" action="">
			<table border="1">
				<tr>
					<th height="0">Select </th>
					<th>Replicate 1 </th>
					<th>Replicate 2 </th>
					<th>Background <br>
						Correction</th>
					<th>Multiplier</th>
					<th>&nbsp;</th>
				</tr>
				<?php
				$counter = 0;
				foreach ($existingBatches as $row_ExistingBatches) { ?>
					<?php if (count($existingBatches) > 0) { // Show if recordset not empty 
					?>
						<tr>
							<td background="deleteBatch.php">
								<input <?php if ($row_ExistingBatches['SELECTED'] == "1")
													echo "checked"; ?> type="checkbox" name="checkbox<?php echo $counter ?>" value="checkbox">
							</td>
							<td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE1']); ?></td>
							<td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE2']); ?></td>
							<td background="deleteBatch.php"><?php echo getStringTime($row_ExistingBatches['ADJUSTMENTTIME']) ?></td>
							<td background="deleteBatch.php"><?php echo $row_ExistingBatches['MULTIPLIER'] ?></td>
							<td background="deleteBatch.php">
								<?php if ($manuallyAdded == 0) { ?>
									<a href="deleteBatch.php?BatchID=<?php echo $row_ExistingBatches['BATCHID'] ?>">[undefine]<?php $row_ExistingBatches['BATCHID'] ?></a>
								<?php } ?>
							</td>
						</tr>
					<?php } // Show if recordset not empty 
					?>
				<?php $counter++;
				} //end for 
				?>
			</table>
			<p>
				<input type="submit" name="Submit3" value="Save Selected Batches">
				<input name="SaveBatches" type="hidden" id="SaveBatches" value="true">
			</p>
		</form>
</div>
</body>

</html>