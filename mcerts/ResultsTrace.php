<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title>Untitled Document</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link href="../css/stylesheet.css" rel="stylesheet" type="text/css">
</head>

<body>

	<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">[return to previous page]</a>
	<?php
	include('../adodb/adodb.inc.php');
	require_once('../Connections/mysql.php');
	require_once('datefunctions.php');

	//$db->debug=true;

	//get list of result ids
	$refMatId = $_GET['ReferenceMaterialID'];

	if (isset($_GET['Compound'])) {
		$compound = $_GET['Compound'];
		//get compound ID from compound name and reference material id
		$query_CompoundID = sprintf("SELECT Distinct CompoundID FROM compounds where ReferenceMaterialID=%s AND Compound='%s'", $refMatId, str_replace("'", "''", $_GET['Compound']));
		$row_CompoundID = $db->GetRow($query_CompoundID);
		if (count($row_CompoundID) == 0)
			return "";
		$compoundID = $row_CompoundID['COMPOUNDID'];
	}

	$sqlResultIds = sprintf(
		"select resultid, concentration,AcqOn from results
							where compound=(SELECT Compound
			                				from compounds
                							where CompoundID=%s)
    						and Sample=(select ReferenceMaterial
                      					from reference_materials
				                      where ReferenceMaterialID = %s)       
						    and AcqOn IN (select Replicate1
									from Batches
					       			where ReferenceMaterialID= %s
							       	)ORDER BY AcqOn",
		$compoundID,
		$refMatId,
		$refMatId
	);

	$sqlResultIds2 = sprintf(
		"select resultid, concentration,AcqOn from results
							where compound=(SELECT Compound
			                				from compounds
                							where CompoundID=%s)
    						and Sample=(select ReferenceMaterial
                      					from reference_materials
				                      where ReferenceMaterialID = %s)       
						    and AcqOn IN (select Replicate2
									from Batches
					       			where ReferenceMaterialID= %s
							       	)ORDER BY AcqOn",
		$compoundID,
		$refMatId,
		$refMatId
	);

	$resultIds[0] = $db->GetAll($sqlResultIds);
	$resultIds[1] = $db->GetAll($sqlResultIds2);
	//loop through resultIDs from results_trail table and get all previous concentrations and date
	//$results=new array();

	echo "<table>";
	echo "<tr class='tableheader'>
		<td>
		First Replicates
		</td>
		<td>
		Second Replicates
		</td>
	</tr>";
	echo "<tr>";
	for ($x = 0; $x < 2; $x++) {
		echo "<td>";
		foreach ($resultIds[$x] as $resultId) {
			echo "<table>";
			$sqlResultsTrail = "select acqOn, concentration, reason, datechanged 
							from results_trail 
							where resultid=" . $resultId['RESULTID'] . " ORDER BY datechanged";
			$rows = $db->GetAll($sqlResultsTrail);
			//array_push($results,$row);

			echo "<tr class='tableheader'>";
			echo "<td colspan='3'>";
			echo getStringTime($resultId['ACQON']);
			echo "</td>";
			echo "</tr>";

			foreach ($rows as $row) {
				echo "<tr>";
				echo "<td>";
				echo getStringTime($row['DATECHANGED']);
				echo "</td>";
				echo "<td>";
				echo $row['CONCENTRATION'];
				echo "</td>";
				echo "<td>";
				echo "Reason : " . $row['REASON'];
				echo "</td>";
				echo "</tr>";
			}
			echo "<tr>";
			echo "<td>";
			echo "Current Value";
			echo "</td>";
			echo "<td>";
			echo $resultId['CONCENTRATION'];
			echo "</td>";
			echo "<td>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		} //end foreach
		echo "</td>";
	} //end for

	echo "<tr>";
	echo "</table>";

	?>

</body>

</html>