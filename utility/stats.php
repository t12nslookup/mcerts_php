<?php 
function getNumberOfBatches($ReferenceMaterialID)
{
	global $db;
	$sqlBatches  = sprintf("select count(*) from batches where referencematerialid=%s and selected=1"
					,$ReferenceMaterialID);
	$numberOfBatches=$db->GetOne($sqlBatches);
	return $numberOfBatches;
}

function getNumberOfCompounds($ReferenceMaterialID)
{
	global $db;
	$sqlCompounds = "select count(*) from compounds where referencematerialid=".$ReferenceMaterialID.
						"and includeinreport=1";
	$numberOfCompounds = $db->GetOne($sqlCompounds);
	return $numberOfCompounds;
}
?>