<?php

include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('datefunctions.php');
require('functionQC.php');

$db->debug = true;

if (isset($_GET['ReferenceMaterialID'])) // if url param is set...
{
	global $db;

	$refMatId = $_GET['ReferenceMaterialID'];

	if (isset($_GET['CompoundID']))
		$compoundID = $_GET['CompoundID'];

	if (isset($_GET['Compound'])) {
		$compound = $_GET['Compound'];
		//get compound ID from compound name and reference material id
		$query_CompoundID = sprintf("SELECT Distinct CompoundID FROM compounds where ReferenceMaterialID=%s AND Compound='%s'", $refMatId, str_replace("'", "''", $_GET['Compound']));
		$row_CompoundID = $db->GetRow($query_CompoundID);
		if (count($row_CompoundID) == 0)
			return "";
		$compoundID = $row_CompoundID['COMPOUNDID'];
	}

	//get compound Name from Compound ID
	$query_Compound = sprintf("SELECT Compound, TargetConcentration, TargetStandardDeviation,TargetBias 
								FROM compounds 
								WHERE CompoundID=%s", $compoundID);
	$row_Compound = $db->GetRow($query_Compound);
	$referenceConcentration = $row_Compound['TARGETCONCENTRATION'];
	if ($referenceConcentration == "" || $referenceConcentration == 0) {
		echo '<html><head>
				<style type="text/css">@import url(../css/stylesheet.css);</style>
				</head>
				<body>
				<h3>This compound has no Target Concentration set</h1>
				<a href="compoundsInSample.php?ReferenceMaterialID=' . $refMatId .
			'"> Return to previous page</a>
				</body>
				</html>';
		exit;
	}
	$targetSDPercent = $row_Compound['TARGETSTANDARDDEVIATION'];
	$biasTargetPercent = $row_Compound['TARGETBIAS'];
	$compound = $row_Compound['COMPOUND'];

	//get reference material (Sample) name from Reference Material ID
	$query_ReferenceMaterial = sprintf("SELECT ReferenceMaterial 
								FROM reference_materials 
								WHERE ReferenceMaterialID=%s", $refMatId);
	$row_ReferenceMaterialID = $db->GetRow($query_ReferenceMaterial);
	$ReferenceMaterial = $row_ReferenceMaterialID['REFERENCEMATERIAL'];
	$sample = $ReferenceMaterial;

	//get EID from Reference Material ID
	$query_EID = sprintf("SELECT EID 
						FROM validation_reference_materials 
						WHERE ReferenceMaterialID=%s", $refMatId);
	$row_EID = $db->GetRow($query_EID);
	$EID = $row_EID['EID'];

	$SQL = "select equipmentid,name from validation_reports where eid=(select eid from validation_reference_materials where referencematerialid=" . $_GET['ReferenceMaterialID'] . ")";
	$row = $db->GetRow($SQL);
	$EID = $row['EQUIPMENTID'];
}

$updateGoTo = "QC.php?ReferenceMaterialID=" . $refMatId . "&Compound=" . $compound;

$query_Replicates1 = sprintf(
	"select distinct Concentration,AcqOn from results
							where compound= (SELECT Compound
			                				from compounds
                							where CompoundID=%s)
    						and Sample=(select ReferenceMaterial
                      					from reference_materials
				                      where ReferenceMaterialID = %s)       
						    and AcqOn IN (select Replicate1
					       			from Batches
					       			where ReferenceMaterialID= %s and selected=1
							       	)
	and machine=%s
	                                                 ORDER BY AcqOn",
	$compoundID,
	$refMatId,
	$refMatId,
	$EID
);

$Replicates1 = $db->GetAll($query_Replicates1);
$noOfBatches = count($Replicates1);
if ($noOfBatches < 3) {
	echo '<html>
		<head>
		<style type="text/css">@import url(../css/stylesheet.css);</style>
		</head>
		<body>' . $query_Replicates1 . '<h3>Cannot show report. There are fewer than 3 batches for this reference material and compound</h3>
		<a href="compoundsInSample.php?ReferenceMaterialID=' . $refMatId . '>Return to previous screen</a>
		</body>';
	exit;
}

$aConc = array();
$aAcqOn = array();
foreach ($Replicates1 as $row_Replicates1) {
	array_push($aConc, $row_Replicates1['CONCENTRATION']);
	array_push($aAcqOn, $row_Replicates1['ACQON']);
}

$query_Replicates2 = sprintf(
	"select distinct Concentration, AcqOn from results
							where compound= (SELECT Compound
			                				from compounds
                							where CompoundID=%s)
    						and Sample=(select ReferenceMaterial
                      					from reference_materials
				                      where ReferenceMaterialID = %s)       
						    and AcqOn IN (select Replicate2
					       			from Batches
					       			where ReferenceMaterialID= %s and selected=1
									) and machine=%s ORDER BY AcqOn",
	$compoundID,
	$refMatId,
	$refMatId,
	$EID
);
$Replicates2 = $db->GetAll($query_Replicates2);

$bConc = array();
$bAcqOn = array();

foreach ($Replicates2 as $row_Replicates2) {
	array_push($bConc, $row_Replicates2['CONCENTRATION']);
	array_push($bAcqOn, $row_Replicates2['ACQON']);
}

//creates trace of a result row by creating new row in result_trace table that stores resultID + concentration
function createResultTrace($date, $sample, $compound, $reason)
{
	global $db;
	$getResultId = sprintf(
		"select resultid, concentration from results
								where to_char(AcqOn,'YYYY-MM-DD HH24:MI:SS')='%s'
							  and sample='%s'
							  and compound='%s'
	                                         and machine=%s",
		$date,
		$sample,
		str_replace("'", "''", $compound),
		$EID
	);
	$resultRow = $db->GetRow($getResultId);
	$resultId = $resultRow['RESULTID'];
	$concentration = $resultRow['CONCENTRATION'];

	$addTrace = sprintf(
		"insert into results_trail (resultid,concentration,username,datechanged,acqon,reason) VALUES (%s,'%s','%s',%s,'%s','%s')",
		$resultId,
		$concentration,
		$_SERVER["REMOTE_USER"],
		$db->DBTimeStamp(time()),
		$date,
		$reason
	);
	$db->Execute($addTrace);
}

if (isset($_POST['Submit'])) {
	for ($x = 0; $x < count($aConc); $x++) {

		$aConcNew = $_POST['aConc' . $x];
		$bConcNew = $_POST['bConc' . $x];

		$db->StartTrans(); //start transaction

		if ($aConcNew != $aConc[$x]) //if replicate1 concentration has been changed
		{
			//store old result in result_trace table
			createResultTrace($aAcqOn[$x], $sample, $compound, $_POST['reason']);

			//SQL to update current result record				  
			$updateSQL = sprintf(
				"update results set concentration=%s
						where to_char(AcqOn,'YYYY-MM-DD HH24:MI:SS')='%s'
							  and sample='%s'
							  and compound='%s' and machine=%s",
				$aConcNew,
				$aAcqOn[$x],
				$sample,
				str_replace("'", "''", $compound),
				$EID
			);
			$db->Execute($updateSQL);
		}

		if ($bConcNew != $bConc[$x]) //if replicate2 concentration has changed
		{
			//store old result in result_trace table
			createResultTrace($bAcqOn[$x], $sample, $compound, $_POST['reason']);

			//update current result record in results table
			$updateSQL = sprintf(
				"update results set concentration=%s
						where to_char(AcqOn,'YYYY-MM-DD HH24:MI:SS')='%s'
							  and sample='%s'
							  and compound='%s' and machine=%s",
				$bConcNew,
				$bAcqOn[$x],
				$sample,
				str_replace("'", "''", $compound),
				$EID
			);
			$db->Execute($updateSQL);
		}

		//$db->FailTrans();
		$db->CompleteTrans(true);
	}

	//header(sprintf("Location: %s", $updateGoTo));
	//exit;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title>Edit Concentrations X</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link href="../css/stylesheet.css" rel="stylesheet" type="text/css">
</head>

<body>
	<div class="box">
		<h3 class="title"><a href="newindex.php">View All Validation Reports</a></h3>
		<h1>Edit Concentrations X</h1>
		<p><a href="selectBatches.php?ReferenceMaterialID=<?php echo $refMatId ?>">[select batches]</a> <a href="ResultsTrace.php?ReferenceMaterialID=<?php echo $refMatId ?>&Compound=<?php echo $compound ?>">[view audit trail for these results]</a> <a href="QC.php?ReferenceMaterialID=<?php echo $refMatId ?>&Compound=<?php echo $compound ?>">[view compound report] </a></p>
		<table width="62%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td width="27%" class="fieldName">Sample : </td>
				<td width="73%"><?php echo $sample; ?></td>
			</tr>
			<tr>
				<td class="fieldName">Compound : </td>
				<td><?php echo $compound; ?></td>
			</tr>
			<tr>
				<td class="fieldName">Precision Test : </td>
				<td><?php $result = getAssessment($refMatId, $compound);
						echo $result['precisiontest'] ?></td>
			</tr>
			<tr>
				<td class="fieldName">Bias Test : </td>
				<td><?php echo $result['biastest'] ?></td>
			</tr>
		</table>
		<br>
		<form name="form2" method="post" action="">
			<table border="0" cellspacing="0" cellpadding="0">
				<tr class="tableheader">
					<td width="190">AcqOn</td>
					<td width="148">Concentration</td>
					<td width="190">AcqOn</td>
					<td width="148">Concentration</td>
				</tr>
				<?php
				for ($x = 0; $x < count($aConc); $x++) { ?>
					<tr>
						<td><?php echo getStringTime($aAcqOn[$x]); ?></td>
						<td><input name="aConc<?php echo $x ?>" type="text" id="concentration4" value="<?php echo $aConc[$x]; ?>"></td>
						<td><?php echo getStringTime($bAcqOn[$x]); ?></td>
						<td><input name="bConc<?php echo $x ?>" type="text" id="aConc" value="<?php echo $bConc[$x]; ?>"></td>
					</tr>
				<?php } ?>
			</table>
			<p class="fieldName">
				Reason for change : </p>
			<p> <input name="reason" type="text" id="reason" size="50">
			</p>
			<p>
				<input type="submit" name="Submit" value="Update Values">
			</p>
		</form>
	</div>
</body>

</html>