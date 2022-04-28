<?php // @(#)CalculateAlternateSummary.php	1.1 08/19/05 ?>
<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('functionQC.php');
require_once('../utility/stats.php');
require_once('../utility/mathematics.php');
require_once('datefunctions.php');
$db->debug=false;

if(!isset($_GET['EID'])) 
{
	echo '<html>
			<head>
			<style type="text/css">@import url(../css/stylesheet.css);</style>
			</head>
			<body>
				<h3>EID URL parameter is not set</h3>
				<a href="mcertsindex.php">Return to MCERTS main menu</a>
			</body>
			</html>';
	exit;
}

$query_Compounds = sprintf("select distinct Compound from compounds 
							where compounds.ReferenceMaterialID = ( 
                         			select ReferenceMaterialID 
                         			from validation_reference_materials vrm
			                        where vrm.EID = '%s'
            						and vrm.ReferenceMaterialID = compounds.ReferenceMaterialID)
							and compounds.IncludeInReport=1
							ORDER BY Compound",
							$_GET['EID']
							);
$Compounds=$db->GetAll($query_Compounds);

$compoundCounter=0;
foreach($Compounds as $row_Compounds)
{
	$compounds[$compoundCounter]=$row_Compounds['COMPOUND'];
	$compoundCounter++;
}

$query_CRM = sprintf("select reference_materials.ReferenceMaterialID, reference_materials.ReferenceMaterial
					from reference_materials, validation_reference_materials 
					where validation_reference_materials.ReferenceMaterialID=reference_materials.ReferenceMaterialID
					and reference_materials.IncludeInReport=1
					and validation_reference_materials.EID='%s'",
					$_GET['EID']);
$theCRM=$db->GetAll($query_CRM);
$a= count($theCRM);

$crmCounter=0;
foreach($theCRM as $row_CRM)
{
	$ReferenceMaterial[$crmCounter]=$row_CRM['REFERENCEMATERIAL'];
	$ReferenceMaterialID[$crmCounter]=$row_CRM['REFERENCEMATERIALID'];
	$crmCounter++;
}

$equipmentSQL = "select equipmentid,name from validation_reports where EID=".$_GET['EID'];

$equipmentRow=$db->GetRow($equipmentSQL);
$equipmentID=$equipmentRow['EQUIPMENTID'];
$validationName=$equipmentRow['NAME'];



$content='						 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<META HTTP-EQUIV="Expires" CONTENT="0">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">
<style type="text/css">@import url(../css/stylesheet.css);</style>
</head>
<body>
<h3>
<a href="../mcerts/index.php">View All Validation Reports</a> > MCERTS Validation Summary for \''.$validationName.'\'
</h3>
<h34Created on : '.date("F j, Y, g:i a").'
<h4>Equipment ID : '.$equipmentID.'</h4>
<p>Key : </p>
<p>
SPASS = Passed on second bias test
</p>
<p>
NEB = Less than 3 batches of data
</p>
<p>
NRC = Reference concentration is zero or has not been set
</p>
<table  border="0" cellspacing="0" cellpadding="0" class="Summary">
  <tr class="SummaryHeader">
    <td width="280">&nbsp;</td>';
	
for($x=0;$x<$crmCounter;$x++) {
    $content.= '<td colspan="2">'.$ReferenceMaterial[$x].'</td>';
} 

$content.='
</tr>
<tr class="SummaryHeader">
    <td width="280">&nbsp;</td>';
		
for($x=0;$x<$crmCounter;$x++)  {
    $content.='<td width="80">Prec</td>
    			<td width="80">Bias</td>';
} 

$content.='</tr>';
for($i=0;$i<$compoundCounter;$i++) {
  $content.='<tr>
    <td width="280" class="SummaryHeader">'.$compounds[$i].'</td>';
	for($x=0;$x<$crmCounter;$x++) { 
		$resultArray=getAssessment($ReferenceMaterialID[$x],$compounds[$i]);
		if($resultArray=="")
		{
			$Precision="";
			$Bias="";
		}
		else {
			$Precision= !isset($resultArray['error']) ? $resultArray['precisiontest'] : $resultArray['error'];
			$Bias= !isset($resultArray['error']) ? $resultArray['biastest'] : $resultArray['error'];
		}	
		$content.='<td width="80">
					<a href="../mcerts/QC.php?ReferenceMaterialID='.$ReferenceMaterialID[$x].'&Compound='.$compounds[$i].'">
					<span class="'.$Precision.'">'.$Precision.'</span>
					</a>
					</td>';
		
		$content.='</a>
					</td>
					<td width="80">
						<a href="../mcerts/QC.php?ReferenceMaterialID='.$ReferenceMaterialID[$x].'&Compound='.$compounds[$i].'">
						<span class="'.$Bias.'">'.$Bias.'</span>		
						</a>
					</td>';
	}

	$content.='
	  </tr>';
}
$content.=' 
</table>
</body>
</html>';
$myFile="../reports/".$_GET['EID'].".htm";
$newFile = @fopen($myFile, 'w+') or die("can't open file");
fwrite($newFile, $content);
fclose($newFile);
//echo $content;
$updateGoTo="index.php";
$updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
$updateGoTo .="Message=Summary Created!";
//header(sprintf("Location: %s",$updateGoTo));
header(sprintf("Location: %s",$myFile));
?>
