<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
//$db->debug=true;

function autoAddBatches($refMaterialID)
{
	global $db;
	//delete current batches
	$delete_Batches = sprintf("DELETE FROM batches where ReferenceMaterialID=%s", $refMaterialID);
	$db->Execute($delete_Batches);

	//get equipment id
	$sqlEquipmentID = sprintf("select equipmentid from validation_reports
							where eid= (select eid from validation_reference_materials 
										where referencematerialid=%s)", $refMaterialID);
	$equipmentID = $db->GetOne($sqlEquipmentID);

	//get distinct dates array
	$query_UniqueTimes = sprintf("SELECT DISTINCT AcqOn 
									FROM results 
									WHERE results.machine=%s	
									AND results.Sample=
										(select ReferenceMaterial  
										from reference_materials 
										where reference_materials.ReferenceMaterialID=%s) 
									ORDER BY AcqOn", $equipmentID, $refMaterialID);
	$UniqueTimes = $db->GetAll($query_UniqueTimes);
	$noOfBatches = floor(count($UniqueTimes) / 2);


	$counter = 0;
	foreach ($UniqueTimes as $row_UniqueTimes) {
		$dates[$counter] = $row_UniqueTimes['ACQON'];
		$counter++;
	}

	for ($i = 1; $i <= $noOfBatches; $i++) {
		$insert_Batch = sprintf(
			"INSERT INTO batches (ReferenceMaterialID,Replicate1, Replicate2,selected,multiplier)
							VALUES (%s,'%s','%s',1,1)",
			$refMaterialID,
			$dates[($i - 1) * 2],
			$dates[($i * 2) - 1]
		);
		$db->Execute($insert_Batch);
	}
	$insertGoTo = "selectBatches.php";
	if (isset($_SERVER['QUERY_STRING'])) {
		$insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
		$insertGoTo .= $_SERVER['QUERY_STRING'];
	}
	header(sprintf("Location: %s", $insertGoTo));
	exit;
}

if (isset($_GET['ReferenceMaterialID'])) {
	autoAddBatches($_GET['ReferenceMaterialID']);
}
