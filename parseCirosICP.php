<?php
function isICPScotland($fileContents)
{
	if (preg_match('/Header_1/', $fileContents))
		return true;
	else
		return false;
}

function parseCirosICP($fileContents)
{
	$SQLArray = array();
	$elements = array();
	$elementStartColumn = 12;
	$topUnit = "mg";
	$bottomUnit = "kg";

	preg_match_all('/.*\n/', $fileContents, $lines); //get array of lines
	$lines = $lines[0];
	foreach ($lines as $fullLine) {
		$line = preg_split("/\t/", $fullLine);

		if (trim($line[4]) == "Sample Name") {
			if (preg_match('/\d+/', $line[8], $match)) {
				$elementStartColumn == 12;
				$EID = $match[0];
				echo "EID : " . $EID;
			} else $EID = 289;

			$elements = array();
			$colno = $elementStartColumn;
			while (isset($line[$colno]) && $line[$colno] != "") {
				$element = array();
				$element['column'] = $colno;
				$element['name'] = $line[$colno];
				array_push($elements, $element);
				$colno += 2;
			}
		}

		if (trim($line[0]) == "Average") {
			$date = $line[1];
			$date = date("Y-m-d H:i:s", strtotime($date));
			$date = "to_date('" . $date . "','YYYY-MM-DD HH24:MI:SS')";

			$method = trim($line[2]);
			$sample = trim($line[4]);
			$type = trim($line[5]);

			$dilution = trim($line[6]) == "" ? 0 : trim($line[6]);
			$operator = trim($line[7]);

			foreach ($elements as $element) {
				$elementName = trim($element['name']);
				$equalityOperator = trim($line[$element['column'] - 1]); //before each element column is either "",">","<" or "~"

				if ($equalityOperator == "")
					$flag = "";
				else if ($equalityOperator == ">")
					$flag = "x";
				else if ($equalityOperator == "<")
					$flag = "u";

				$concentration = str_replace(",", ".", $line[$element['column']]);

				$SQLDelete = sprintf(
					"delete from results where acqon=%s and sample='%s' and compound='%s' and machine=%s",
					$date,
					$sample,
					$elementName,
					$EID
				);

				$SQL = sprintf(
					"insert into results (machine,acqon,concentration,sample,compound, DF,operator,quantmethod,top,bottom,flag)
							values (%s,%s,%s,'%s','%s',%s,'%s','%s','%s','%s','%s')",
					$EID,
					$date,
					$concentration,
					$sample,
					$elementName,
					$dilution,
					$operator,
					$method,
					$topUnit,
					$bottomUnit,
					$flag
				);

				array_push($SQLArray, $SQLDelete);
				array_push($SQLArray, $SQL);
			}
		}
	}
	return $SQLArray;
}

function ICPScotlandExist($fileContents) //returns true if Quantitaion Report exists in Database
{
	global $hostname_mysql, $database_mysql, $username_mysql, $password_mysql, $mysql, $db;
	if (preg_match('/Header_1/', $fileContents)) {
		$acqOnAndOperator = getTwoFieldsFromLine("Acq On", "Operator", $fileContents);
		$acqOn = $acqOnAndOperator[0];
	} else $acqOn = getField("Acq On", $fileContents);

	$selectSQL = sprintf("SELECT count(*) FROM results WHERE AcqOn='" . formatDate($acqOn) . "'");
	$rows = $db->GetOne($selectSQL);

	if ($rows > 0)
		return true;
	else
		return false;
}
