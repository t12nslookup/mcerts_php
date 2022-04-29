<?php //  @(#)upload.php	1.3 07/22/05 
?>
<?php
include('adodb/adodb.inc.php');
require_once('Connections/mysql.php');
require('mcerts/datefunctions.php');
include('parseCirosICP.php');
//$db->debug=true;
echo "<pre>";

function file_open($filename) //return file as string
{
	if ($fp = @fopen($filename, "r")) {
		$fp = @fopen($filename, "r");
		$contents = fread($fp, filesize($filename));
		fclose($fp);
		return $contents;
	} else {
		return false;
	}
}

function quantitationExists($fileContents) //returns true if Quantitaion Report exists in Database
{
	global $hostname_mysql, $database_mysql, $username_mysql, $password_mysql, $mysql, $db;
	if (preg_match('/DataAcq Meth/', $fileContents)) {
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

function isQuantitaionReport($fileContents)
{
	if (preg_match('/Quantitation Report/', $fileContents))
		return true;
	else
		return false;
}

function formatWebAddress($address) //replace whitespaces with %20, making it compatible with browser
{
	return preg_replace('/ /', "%20", $address);
}

function isInorganicData($fileContents)
{
	$fileContents = removeQuotes($fileContents);

	if (
		preg_match('/Solution Label/', $fileContents)
		& preg_match('/Element/', $fileContents)
		& preg_match('/Corr Con/', $fileContents)
		& preg_match('/EID/', $fileContents)
	)
		return true;
	else
		return false;
}

function getEIDfromFile($fileContents)
{
	if (preg_match('/DataAcq Meth/', $fileContents)) {
		$dataFileAndVial = getTwoFieldsFromLine("Data File", "Vial", $fileContents);
		$alsVial = $dataFileAndVial[1];
		$dataPath = $dataFileAndVial[0];

		if (preg_match('/\\\\\\d+\\.D/', $dataPath, $dataFile)) {
			$dataFile = substr($dataFile[0], 1);
			$dataPath = str_replace($dataFile, '', $dataPath);
		}
	} else {
		$dataPath = getField("Data Path", $fileContents);
	}
	$dataPath = str_replace("\\", "\\\\", $dataPath);
	$machine = getEquipmentID($dataPath);
	return $machine;
}

function parse($fileName)
{
	global $html, $acqOn, $redirect;
	$fileContents = file_open($fileName); //return string of file
	$error = "";

	if (isQuantitaionReport($fileContents)) {  //check if file is a quantitation report
		if (getEIDfromFile($fileContents) != "") {
			$quantitaionSQL = parseQuantitationReport($fileContents);  //parse
			insertIntoDatabase($quantitaionSQL);  //insert into Database
			$header = "index.php?Message=File successfully uploaded";
			//$header=formatWebAddress("show.php?Time=".$acqOn); //replace whitespaces with %
			header("Location:" . $redirect);
			exit;
		} else ($error = "<h3>This Quantitation Report has no equipment id in the data path</h3>");
	} else if (isInorganicData($fileContents)) {
		$html = $html . "<h3> Is Organic Data </h3>";
		$inorganicSQL = parseInorganics($fileContents);

		//			   for($x=0;$x<count($inorganicSQL);$x++)
		//               {
		//					print "<pre>";
		//				   	print $inorganicSQL[$x]."\n";
		//				   	print "</pre>";
		//               }
		//			   exit;

		insertIntoDatabase($inorganicSQL);
		//               $header=formatWebAddress("show.php?Time=".$acqOn); //replace whitespaces with %
		$header = "index.php?Message=File successfully uploaded";
		header("Location:" . $redirect);
		exit;
	} else if (isICPScotland($fileContents)) {
		$html = $html . "<h3> Is ICP Scotland Data </h3>";
		$ICPSQL = parseCirosICP($fileContents);
		insertIntoDatabase($ICPSQL);
		//$header="index.php?Message=File successfully uploaded";
		header("Location:" . $redirect);
		exit;
	} else {
		$error = "<h3>Could not find a parser for this document</h3>";
	}
	$html = $html . $error;
	return false;
}


function removeQuotes($removeFrom)
{
	return preg_replace('/"/', '', $removeFrom);
}

$concentrationRegExp = array('/^\d+ppb/', '/^crm/', '/^blk/');
$types = array("soil" => "soil", "soil " => "so", "clay" => "cl", "water" => "water", "water " => "wa", "water" => "water", "sand" => "sand", "clay " => "clay", "sand " => "sa");

function getSampleType($sampleArray)
{
	global $concentrationRegExp, $types;
	for ($i = 0; $i < count($sampleArray); $i++) {

		$key = array_search($sampleArray[$i], $types);
		if ($key != FALSE)
			return trim($key);
	}
	return "";
}

function getSampleConcentration($sampleArray)
{
	global $concentrationRegExp, $types;
	for ($i = 0; $i < count($sampleArray); $i++) {
		for ($x = 0; $x < count($concentrationRegExp); $x++) {
			if (preg_match($concentrationRegExp[$x], $sampleArray[$i], $typeMatch))
				return $typeMatch[0];
		}
	}
	return "";
}

function getSampleJobNo($sampleArray)
{
	global $concentrationRegExp, $types;
	for ($i = 0; $i < count($sampleArray); $i++) {
		for ($x = 0; $x < count($concentrationRegExp); $x++) {
			if (preg_match('/^\d+(a|b|c|d)*$/', $sampleArray[$i], $jobNoMatch)) {
				preg_match('/\d+/', $jobNoMatch[0], $jobNoMatch);
				return $jobNoMatch[0];
			}
		}
	}
	return "";
}


function splitSample($sample)
{
	global $concentrationRegExp, $types;
	$sampleConcentration = "";
	$sampleType = "";
	$sampleNo = "";
	$jobNo = "";

	$splitSampleArray = split(' ', $sample);
	//echo "count : ".count($splitSampleArray)." ";

	switch (true) {
		case (preg_match('/^\d{5}\d* .*\d{3}\d*$/', $sample) && count($splitSampleArray > 2)):
			$jobNo = trim($splitSampleArray[0]);
			if (count($splitSampleArray) == 3) {
				$sampleType = $splitSampleArray[1];
				$sampleNo = $splitSampleArray[2];
				$match = true;
			} else if (count($splitSampleArray) == 4) {
				$sampleType = $splitSampleArray[1] . " " . $splitSampleArray[2];
				$sampleNo = $splitSampleArray[3];
				$match = true;
			} else if (count($splitSampleArray) == 2) {
				$sampleNo = $splitSampleArray[1];
				$match = true;
			} else $match = false;
			break;
		default:
			$sampleType = getSampleType($splitSampleArray);
			$sampleConcentration = getSampleConcentration($splitSampleArray);
			$sampleNo = getSampleJobNo($splitSampleArray);
			if ($sampleType != "" && $sampleConcentration != "" && $sampleNo != "")
				$match = true;
			else
				$match = false;
			break;
	}

	//return("\nSample No : ".$sampleNo."\nJob No : ".$sampleJobNo."\nSample Type : ".$sampleType. "\nConc : "
	//.$sampleConcentration."\n");
	return (array($sampleNo, $jobNo, $sampleType, $sampleConcentration));
}


function getTwoFieldsFromLine($firstField, $secondField, $target)
{
	$pattern = '/' . $firstField . '.*:.*' . $secondField . '.*: .*/';
	if (preg_match($pattern, $target, $match)) {
		$split = preg_split('/' . $secondField . '.*: /', $match[0]);
		$secondFieldValue = $split[1];
		$leftSection = $split[0];
		$firstFieldValue = preg_split('/' . $firstField . '.*: /', $leftSection);
		$firstFieldValue = $firstFieldValue[1];
		return array($firstFieldValue, $secondFieldValue);
	} else return array("", "");
}


function parseInorganics($fileContents)
{

	global $html, $acqOn;

	$operator = "To be done";

	$fileContents = removeQuotes($fileContents);

	preg_match_all('/.*\r/', $fileContents, $lines); //get array of lines

	$lines = $lines[0];

	//get machine number
	$dataPath = $lines[0];

	$dataPath = preg_replace('/,/', '', $dataPath);

	$machine = getEquipmentID($lines[0]);

	//get data file
	$dataFile = "shit";

	if (preg_match('/\\\\(\\w|\\d|\\s|\\.)*\\.sws/', $dataPath, $regs))
		$dataFile = substr($regs[0], 1);

	if (preg_match('/\\\\(\\w|\\d|\\s|\\.)*\\.vws/', $dataPath, $regs))
		$dataFile = substr($regs[0], 1);

	//get data path
	$dataPath = str_replace($dataFile, '', $dataPath);
	$dataPath = addcslashes($dataPath, "\\");

	//get position of specified columns from first element in lines array:

	$firstLine = preg_split('/,/', $lines[1]); //split by commas

	$solutionPosition = array_search("Solution Label", $firstLine);
	$elementPosition = array_search("Element", $firstLine);
	$concentrationPosition = array_search("Corr Con", $firstLine);
	$unitsPosition = array_search("Units", $firstLine);
	$datePosition = array_search("Date", $firstLine);
	$timePosition = array_search("Time", $firstLine);
	$flagPosition = array_search("Flags", $firstLine);
	$intPosition = array_search("Int", $firstLine);
	$internalStandardPosition = array_search("Internal Std", $firstLine);
	$sampleMultiplierPosition = array_search("DF", $firstLine);
	$typePosition = array_search("Type", $firstLine);
	$SDPosition = array_search("SD", $firstLine);
	$RSDPosition = array_search("RSD", $firstLine);
	$IntSDPosition = array_search("IntSD", $firstLine);
	$IntRSDPosition = array_search("IntRSD", $firstLine);
	$ActWgtPosition = array_search("Act Wgt", $firstLine);
	$ActVolPosition = array_search("Act Vol", $firstLine);
	$DFPosition = array_search("DF", $firstLine);
	echo "<h1>" . $DFPosition . "</h1>";

	if ($DFPosition < 3)
		$DFPosition = array_search("DF\r", $firstLine); // if older version of ICP Expert software

	$insertSQL = array();
	//loop through lines {
	for ($i = 2; $i < count($lines); $i++) {

		$line = preg_split("/,/", $lines[$i]);

		if (preg_match('/Solution Label/', $lines[$i])) //check for new batch of results and get new data
		{
			//get machine number
			$dataPath = $lines[$i - 1];
			$dataPath = preg_replace('/,/', '', $dataPath);
			$machine = getEquipmentID($lines[$i - 1]);

			//get data file
			$dataFile = "shit";

			if (preg_match('/\\\\(\\w|\\d|\\s|\\.)*\\.sws/', $dataPath, $regs))
				$dataFile = substr($regs[0], 1);

			if (preg_match('/\\\\(\\w|\\d|\\s|\\.)*\\.vws/', $dataPath, $regs))
				$dataFile = substr($regs[0], 1);

			//get data path
			$dataPath = str_replace($dataFile, '', $dataPath);
			$dataPath = addcslashes($dataPath, "\\");
		} else if (count($line) > 3) {
			$elementAndWavelength = $line[$elementPosition]; //compound + wavelength

			//get wavelength (QIon)
			if (preg_match('/\\d*\\.\\d*/', $elementAndWavelength, $match))
				$QIon = $match[0];

			//get Element (compound in table)
			/*if(preg_match('/\w+/',$elementAndWavelength,$match2))
						   $element = $match2[0];*/
			$element = $elementAndWavelength;
			$sample = $line[$solutionPosition];

			$sampleArray = splitSample($sample);
			$jobNo = $sampleArray[1];
			$sampleConcentration = $sampleArray[3];
			$sampleType = $sampleArray[2];
			$sampleNo = $sampleArray[0];


			$type = $line[$typePosition];
			$flag = $line[$flagPosition];
			$internalStandard = $line[$internalStandardPosition];
			$flag = $internalStandard == "-" ?  $flag : "I" . $flag;
			$response = $line[$intPosition];
			$sampleMultiplier = $line[$sampleMultiplierPosition];
			$concentration = $line[$concentrationPosition];
			if ($concentration == "uncal" || $concentration == "-") $concentration = 0;
			$units = $line[$unitsPosition];
			$top = substr($units, 0, 2);
			$bottom = substr($units, 3);
			$date = $line[$datePosition];

			$SD = $line[$SDPosition];
			$RSD = $line[$RSDPosition];
			$IntSD = $line[$IntSDPosition];
			$IntRSD = $line[$IntRSDPosition];
			$ActWgt = $line[$ActWgtPosition];
			$ActVol = $line[$ActVolPosition];
			$DF = $line[$DFPosition];

			//get day
			preg_match('/^\d*/', $date, $day);
			$day = $day[0];

			//get month
			preg_match('/\/\d*\//', $date, $month);
			$month = $month[0];
			preg_match('/\d+/', $month, $month);
			$month = $month[0];

			//get year
			if (preg_match('/\\d{4}/', $date, $year)) {
				$year = $year[0];
			}

			$date = $year . "-" . $month . "-" . $day . " " . $line[$timePosition];
			$acqOn = $date;


			array_push(
				$insertSQL,
				sprintf(
					"delete from results where acqon=%s and compound=%s and machine=%s",
					getSQLDate($date),
					GetSQLValueString($element, "text"),
					$machine
				)
			);

			array_push($insertSQL, sprintf(
				"INSERT INTO results (DataPath, DataFile,AcqOn, Operator,Sample,SampleMultiplier,Compound,QIon,Response, Concentration, top, bottom,Machine,Flag,Type,SD,RSD,IntSD,IntRSD,ActWgt,ActVol,DF,Sampleno,jobno,sampleconcentration,sampletype) VALUES (%s,%s, %s, %s,%s,%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,%s,%s, %s, %s, %s, %s, %s)",
				GetSQLValueString($dataPath, "text"),
				GetSQLValueString($dataFile, "text"),
				getSQLDate($date),
				GetSQLValueString($operator, "text"),
				GetSQLValueString(strtoupper(trim($sample)), "text"),
				GetSQLValueString($sampleMultiplier, "int"),
				GetSQLValueString($element, "text"),
				GetSQLValueString($QIon, "float"),
				GetSQLValueString($response, "text"),
				GetSQLValueString($concentration, "float"),
				GetSQLValueString($top, "text"),
				GetSQLValueString($bottom, "text"),
				GetSQLValueString($machine, "text"),
				GetSQLValueString($flag, "text"),
				GetSQLValueString($type, "text"),
				GetSQLValueString($SD, "float"),
				GetSQLValueString($RSD, "float"),
				GetSQLValueString($IntSD, "float"),
				GetSQLValueString($IntRSD, "float"),
				GetSQLValueString($ActWgt, "float"),
				GetSQLValueString($ActVol, "float"),
				GetSQLValueString($DF, "float"),
				GetSQLValueString($sampleNo, "int"),
				GetSQLValueString($jobNo, "int"),
				GetSQLValueString($sampleConcentration, "text"),
				GetSQLValueString($sampleType, "text")
			));
			//if($type=="Samp") echo $insertSQL[$i-2];
		}	//end if							
	}
	//print count($lines);
	//exit;

	return $insertSQL;
}

function getPositionInArray($findThis, $array)
{
	for ($i = 0; $i < count($array); $i++) {
		if ($array[$i] == $findThis)
			return $i;
	}
	return false;
}

function getField($fieldName, $content)
{
	if (preg_match('/' . $fieldName . ' *: .*\r/', $content, $regs)) {
		$result = $regs[0];
		$result = preg_split('/' . $fieldName . ' *: /', $result);
		$result = $result[1];
		$result = trim(preg_replace('/\r/', '', $result));
		$result = trim(preg_replace('/\n/', '', $result));
		return $result;
	} else return "";
}

function getEquipmentID($EID)
{
	if (preg_match('/EID\d+/', $EID, $result)) {
		preg_match('/\d+/', $result[0], $result);
		$machine = $result[0];
		return $machine;
	}
	return "";
}

function parseQuantitationReport($fileContents)
{
	global $insertSQL, $acqOn;
	$dataPath = "";
	$dataFile = "";
	$acqOn = "";
	$operator = "";
	$sample = "";
	$misc = "";
	$alsVial = "";
	$sampleMultiplier = "";
	$quantTime = "";
	$quantMethod = "";
	$quantTitle = "";
	$quantUpdate = "";
	$responseVia = "";

	if (preg_match('/DataAcq Meth/', $fileContents))
		$reportType = 'old';
	else $reportType = 'new';

	if ($reportType == 'old') {
		$dataFileAndVial = getTwoFieldsFromLine("Data File", "Vial", $fileContents);
		$alsVial = $dataFileAndVial[1];
		$dataPath = $dataFileAndVial[0];


		if (preg_match('/\\\\\\d+\\.D/', $dataPath, $dataFile)) {
			$dataFile = substr($dataFile[0], 1);
			$dataPath = str_replace($dataFile, '', $dataPath);
		}

		$acqOnAndOperator = getTwoFieldsFromLine("Acq On", "Operator", $fileContents);
		$acqOn = formatDate($acqOnAndOperator[0]);
		$operator = $acqOnAndOperator[1];
		$sampleAndInst = getTwoFieldsFromLine("Sample", "Inst", $fileContents);
		$sample = trim($sampleAndInst[0]);
		$miscAndMultiplier = getTwoFieldsFromLine("Misc", "Multiplr", $fileContents);
		$sampleAmount = getField("Sample Amount", $fileContents);
		$quantTimeAndQuantResultsFile = getTwoFieldsFromLine("Quant Time", "Quant Results File", $fileContents);
		$quantTime = formatDate($quantTimeAndQuantResultsFile[0]);
		$quantTitle = getField("Title", $fileContents);
		$quantUpdate = formatDate(getField("Last Update", $fileContents));
	} else if ($reportType == 'new') {
		$dataPath = getField("Data Path", $fileContents);
		$dataFile = getField("Data File", $fileContents);
		$finalUpload = $dataFile;
		$acqOn = formatDate(getField("Acq On", $fileContents));
		$operator = getField("Operator", $fileContents);
		$sample = trim(getField("Sample", $fileContents));
		$misc = getField("Misc", $fileContents);
		$quantTime = formatDate(getField("Quant Time", $fileContents));
		$quantTitle = getField("Quant Title", $fileContents);
		$quantUpdate = formatDate(getField("QLast Update", $fileContents));
		$vialAndMultiplier = getTwoFieldsFromLine("ALS Vial", "Sample Multiplier", $fileContents);
		$alsVial = $vialAndMultiplier[0];
		$sampleMultiplier = $vialAndMultiplier[1];
	}

	$samples = preg_split('/,/', $sample);


	// all reports :
	$dataPath = str_replace("\\", "\\\\", $dataPath);
	$quantMethod = str_replace("\\", "\\\\", getField("Quant Method", $fileContents));
	$responseVia = getField("Response via", $fileContents);

	$machine = getEquipmentID($dataPath);

	$split = preg_split('/Target Compounds/', $fileContents);  //split document into 2 parts: Internal Standards/Target Compounds
	$internalstandards = $split[0];
	preg_match_all('/\d+\).*/', $internalstandards, $internalLine);
	$noOfStandards = count($internalLine[0]);  //get the number of internal standards


	//split sample into sub fields
	/*$newSample = str_replace(" lod ","",$sample);
		$sampleParts=split(' ',$newSample);
		$partCount=count($sampleParts);
		$machine="";*/


	//get the number of Internal Standards:
	$split = preg_split('/Target Compounds/', $fileContents);  //split document into 2 parts: Internal Standards/Target Compounds
	$internalstandards = $split[0];
	preg_match_all('/\d+\).*/', $internalstandards, $internalLine);
	$noOfStandards = count($internalLine[0]);

	//loop through array of results:
	preg_match_all('/\d+\).*/', $fileContents, $matches);   //insert individual lines into array $matches
	$insertSQL = array();
	for ($i = 0; $i < count($matches[0]); $i++) {



		//initialise variables to default for each result
		$internalStandard = FALSE;
		$flag = "";
		$compound = "";
		$QValue = "";
		$RT = "";
		$QIon = "";
		$response = "";
		$concentration = "";
		$top = "";
		$bottom = "";
		$Dev = "";
		if ($i < $noOfStandards) {
			$flag = "I";
			$internalStandard = TRUE;
		}

		//get lineNo
		if (preg_match('/\d*\)/', $matches[0][$i], $regs)) {
			$result = $regs[0];
			preg_match('/\d+/', $result, $regs);
			$lineNo = $regs[0];
		}

		//get # Qualifier out of range & remove from line if it exists
		if (preg_match('/#\s*\d*\.*\d*\r/', $matches[0][$i])) {
			$flag .= "#";
			$matches[0][$i] = ereg_replace('#', '', $matches[0][$i]);
		}

		//get (+) "Signals Summed"
		if (preg_match('/\+\s*\d*\.*\d*\r/', $matches[0][$i]))
			$flag .= "+";


		//get m "manual integration"
		if (preg_match('/m\s*\d*\.*\d*\r/', $matches[0][$i]))
			$flag .= "m";

		//strip "N.D."" from line
		if (preg_match('/N\.D\./', $matches[0][$i])) {
			$flag .= "ND";
			$matches[0][$i] = preg_replace('/N\\.D\\./', '', $matches[0][$i]); //remove N.D from line
		}

		//strip "Below Cal" from line
		if (preg_match('/Below.Cal/', $matches[0][$i])) {
			$flag .= "B";
			$matches[0][$i] = preg_replace('/Below.Cal/', '', $matches[0][$i]); //remove Below Cal from line
		}

		//get Compound
		if (preg_match('/\\d+\\) ((\\S)+ )*/', $matches[0][$i], $regs))
			$compound = $regs[0];
		else
			$compound = "";

		$bracketPosition = strpos($compound, ")");
		$compound = trim(substr($compound, $bracketPosition + 2));

		//get (QValue if Target Compound) OR (Dev(min) if Internal Standard)
		if (preg_match('/(\\d+\\r|\\d+\\.\\d+\\r)/', $matches[0][$i], $regs)) {
			if ($internalStandard)
				$deviation = preg_replace('/\r/', '', $regs[0]);
			else
				$QValue = preg_replace('/\r/', '', $regs[0]);
		}

		//get RT & QIon & Response
		if (preg_match('/\d+\.\d+\s*\d+\s*\d+\s/', $matches[0][$i], $regs)) {
			$values = $regs[0];
			$values = trim($values);
			$values = preg_replace('/\\s+/', ' ', $values); //replace multiple whitespaces with single whitespace
			$split = preg_split('/\\s/', $values);
			$RT = $split[0];
			$QIon = $split[1];
			$response = $split[2];
		}

		//get  Unit + Concentration
		if (preg_match('/\d+(\.)*\d+ (a|f|p|n|u|m|k)(g|l)\/(a|f|p|n|u|m|k)(g|l)/', $matches[0][$i], $regs)) {
			$result = $regs[0];
			$result = preg_split('/ /', $result);
			$concentration = $result[0];
			$top = substr($result[1], 0, 2);
			$bottom = substr($result[1], 3, 2);
		}

		for ($s = 0; $s < count($samples); $s++) {
			$deleteRow = sprintf(
				"delete from results where acqon=%s and sample='%s' and machine=%s and compound='%s'",
				getSQLDate($acqOn),
				$samples[$s],
				$machine,
				$compound
			);
			array_push($insertSQL, $deleteRow);
		}



		//add SQL string to $insertSQL array
		for ($s = 0; $s < count($samples); $s++) {

			$addRow = sprintf(
				"INSERT INTO results (DataPath, DataFile, AcqOn, Operator, Sample, Misc, ALSVial, SampleMultiplier, QuantTime, QuantMethod, QuantTitle, QuantUpdate, ResponseVia, Compound, RT, QIon, Response, Concentration, top, bottom, QValue, Deviation, Machine,LineNo,Flag) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,%s,%s,%s)",
				GetSQLValueString($dataPath, "text"),
				GetSQLValueString($dataFile, "text"),
				getSQLDate($acqOn),
				GetSQLValueString($operator, "text"),
				GetSQLValueString($samples[$s], "text"),
				GetSQLValueString($misc, "text"),
				GetSQLValueString($alsVial, "int"),
				GetSQLValueString($sampleMultiplier, "int"),
				getSQLDate($quantTime),
				GetSQLValueString($quantMethod, "text"),
				GetSQLValueString($quantTitle, "text"),
				getSQLDate($quantUpdate),
				GetSQLValueString($responseVia, "text"),
				GetSQLValueString($compound, "text"),
				GetSQLValueString($RT, "double"),
				GetSQLValueString($QIon, "int"),
				GetSQLValueString($response, "int"),
				GetSQLValueString($concentration, "double"),
				GetSQLValueString($top, "text"),
				GetSQLValueString($bottom, "text"),
				GetSQLValueString($QValue, "double"),
				$deviation,
				GetSQLValueString($machine, "text"),
				GetSQLValueString($lineNo, "int"),
				GetSQLValueString($flag, "text")
			);

			array_push($insertSQL, $addRow);
		}
	} //end for

	return $insertSQL;
}


function uploadFile($uploadSource, $uploadDestination) //uploads file to $uploadDir
{
	if (move_uploaded_file($uploadSource, $uploadDestination))
		return true;
	else {
		print "File failed to Upload!  Here's some debugging info:\n";
		print_r($_FILES);
		return false;
	}
}

function main()
{
	global $acqOn, $html, $redirect; //define global vars that this function will use

	if (isset($_POST['QC']))
		$redirect = $_SERVER['HTTP_REFERER'];
	else
		$redirect = "index.php?Message=File successfully uploaded";

	$html = "<html><head><link href='css/stylesheet.css' rel='stylesheet' type='text/css'></head><body><h1>Results</h1>";

	$uploadTo = "uploads/" . $_FILES['userfile']['name'];
	$uploadFrom = $_FILES['userfile']['tmp_name'];

	if (uploadFile($uploadFrom, $uploadTo)) //upload file
	{
		/*if (isQReport($uploadTo))
				{
					$fileContents=file_open($uploadTo);
					$dp=getDataPath($fileContents);
					//match parent folder
					echo $dp;
					$pattern='/(\d|\w)+\\\\{1}$/';
					echo $pattern;
					preg_match($pattern,$dp,$match);
					$folder = str_replace('\\','',$match[0]);

					//create new folder and copy file there
					$destination="uploads/".$folder;
					umask();
					if(!file_exists($destination))
						mkdir($destination,0777);
					if (!copy($uploadTo, $destination."/report.txt")) {
   						echo "failed to copy $file...\n";
					}
			   }*/
		parse($uploadTo);  //parse file
	}
	$html = $html . "<p><a href='" . $redirect . "'>Click here to return to previous screen</a></p></body></html>";
	print $html;
}

function isQReport($fileName)
{
	$fileContents = file_open($fileName);
	if (isQuantitaionReport($fileContents))
		return true;
	else return false;
}

function getDataPath($fileContents)
{
	if (preg_match('/DataAcq Meth/', $fileContents))
		$reportType = 'old';
	else $reportType = 'new';

	if ($reportType == 'old') {
		$dataFileAndVial = getTwoFieldsFromLine("Data File", "Vial", $fileContents);
		$dataPath = $dataFileAndVial[0];
		if (preg_match('/\\\\\\d+\\.D/', $dataPath, $dataFile)) {
			$dataFile = substr($dataFile[0], 1);
			$dataPath = str_replace($dataFile, '', $dataPath);
		}
	} else if ($reportType == 'new') {
		$dataPath = getField("Data Path", $fileContents);
	}
	$dataPath = str_replace("\\", "\\\\", $dataPath);

	return $dataPath;
}

main();
?>
