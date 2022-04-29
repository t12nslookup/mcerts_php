<?php // %W% %G%

ob_start();
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('functionQC.php');
require_once('../utility/stats.php');
require_once('../utility/mathematics.php');
require_once('datefunctions.php');
require_once('locked.php');
$db->debug = false;

if (!isset($_GET['EID']))  //if EID url parameter is not set then show error screen
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

$query_Compounds = sprintf(
	"select distinct Compound from compounds 
							where compounds.ReferenceMaterialID = ( 
                         			select ReferenceMaterialID 
                         			from validation_reference_materials vrm
			                        where vrm.EID = '%s'
            						and vrm.ReferenceMaterialID = compounds.ReferenceMaterialID
									and exists
										(select referencematerial from reference_materials
										where referencematerialid=vrm.referencematerialid
										and includeinreport=1
										)
									)
							and compounds.IncludeInReport=1
							ORDER BY Compound",
	$_GET['EID']
);
$Compounds = $db->GetAll($query_Compounds); //list of compounds

$compoundCounter = 0;
foreach ($Compounds as $row_Compounds) //convert associative array to indexable array!
{
	$compounds[$compoundCounter] = $row_Compounds['COMPOUND'];
	$compoundCounter++;
}

$query_CRM = sprintf(
	"select reference_materials.ReferenceMaterialID, reference_materials.ReferenceMaterial, trivialname
					from reference_materials, validation_reference_materials 
					where validation_reference_materials.ReferenceMaterialID=reference_materials.ReferenceMaterialID
					and reference_materials.IncludeInReport=1
					and validation_reference_materials.EID='%s'",
	$_GET['EID']
);
$theCRM = $db->GetAll($query_CRM); //get list of reference materials
$a = count($theCRM);

$crmCounter = 0;
$nonCRMReferenceMaterial = "";

foreach ($theCRM as $row_CRM) {
	$ReferenceMaterial[$crmCounter] = $row_CRM['TRIVIALNAME'];

	if (substr_count(strtoupper($ReferenceMaterial[$crmCounter]), "CRM") == 0)
		$nonCRMReferenceMaterial = $ReferenceMaterial[$crmCounter];

	$ReferenceMaterialID[$crmCounter] = $row_CRM['REFERENCEMATERIALID'];
	$crmCounter++;
}

if ($nonCRMReferenceMaterial == "")
	$nonCRMReferenceMaterial = $ReferenceMaterial[0];


$sqlUnits = sprintf("select distinct top,bottom 
					from results 
					where sample='%s'", $nonCRMReferenceMaterial);
$units = $db->GetRow($sqlUnits);

if (substr_count(strtoupper($nonCRMReferenceMaterial), "CRM") != 0) {
	$topUnit = "mg";
	$bottomUnit = "kg";
} else {
	if (isset($units['TOP'])) {
		$topUnit = $units['TOP'];
		$bottomUnit = $units['BOTTOM'];
	} else {
		$topUnit = "";
		$bottomUnit = "";
	}
}

$equipmentSQL = "select equipmentid,name from validation_reports where EID=" . $_GET['EID'];
$equipmentRow = $db->GetRow($equipmentSQL);
$equipmentID = $equipmentRow['EQUIPMENTID'];
$validationName = $equipmentRow['NAME'];

$startDate =	$db->GetOne(sprintf("select min(replicate1)
						from batches 
						where referencematerialid in 
							  (select rm.referencematerialid 
							  from validation_reference_materials vrm,validation_reports vr, reference_materials rm
							  where vrm.eid = %s
							  and vr.EID=%s
							  and rm.referencematerialid=vrm.referencematerialid
							  and rm.includeinreport=1
							  )
						and selected=1", $_GET['EID'], $_GET['EID']));

$endDate = $db->GetOne(sprintf("select max(replicate2)
						from batches 
						where referencematerialid in 
							  (select rm.referencematerialid 
							  from validation_reference_materials vrm,validation_reports vr, reference_materials rm
							  where vrm.eid = %s
							  and vr.EID=%s
							  and rm.referencematerialid=vrm.referencematerialid
							  and rm.includeinreport=1
							  )
						and selected=1", $_GET['EID'], $_GET['EID']));
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<META HTTP-EQUIV="Expires" CONTENT="0">
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">
	<style type="text/css">
		@import url(../css/stylesheet.css);

		.style1 {
			color: #FF0000
		}
	</style>
</head>

<body>
	<h3 class="title">
		<?php if ($locked != true) { ?>
			<a href="../mcerts/index.php">View All Validation Reports</a> >
		<?php } ?>
		LOD Summary
	</h3>
	<br>
	<table class="box" width="500" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="266" class="fieldName">Validation Name :</td>
			<td width="134"><span class="newheader"><?php echo $validationName ?></span></td>
		</tr>
		<tr>
			<td class="fieldName">Equipment ID :</td>
			<td><span class="newheader"><?php echo $equipmentID ?></span></td>
		</tr>
		<tr>
			<td class="fieldName">Date validation commenced :</td>
			<td><span class="newheader"><?php echo getStringDate($startDate) ?></span></td>
		</tr>
		<tr>
			<td class="fieldName">Date validation completed :</td>
			<td><span class="newheader"><?php echo getStringDate($endDate) ?></span></td>
		</tr>
		<tr>
			<td class="fieldName">Units : </td>
			<td><span class="newheader"><?php echo $topUnit . "   " . $bottomUnit; ?></span></td>
		</tr>
	</table><br>
	<table class="box" width="500" border="0" cellspacing="0" cellpadding="0">
		<tr class="fieldName">
			<td width="69">Key</td>
			<td width="429">&nbsp;</td>
		</tr>
		<tr>
			<td><span class="newheader"><span class="PASS">BLUE</span></span></td>
			<td><span class="newheader"><span class="PASS">PASS </span></span></td>
		</tr>
		<tr>
			<td><span class="newheader"><span class="FAIL">RED</span></span></td>
			<td><span class="newheader"><span class="FAIL">FAIL </span><span class="style1"></span></span></td>
		</tr>
		<tr>
			<td><span class="newheader"><span class="SPASS">GREEN</span></span></td>
			<td><span class="newheader"><span class="SPASS">Fails the simple bias test but statistical analysis show the result to be valid </span></span></td>
		</tr>
	</table>
	<br>


	<table border="0" cellspacing="0" cellpadding="0" class="Summary">
		<tr class="SummaryHeader">
			<td width="300">Analyte</td>
			<?php
			for ($x = 0; $x < $crmCounter; $x++) { ?>
				<td><?php echo $ReferenceMaterial[$x] ?><br>
					<a href="../mcerts/viewBatches.php?ReferenceMaterialID=<?php echo $ReferenceMaterialID[$x] ?>">[view batches]</a>
				</td>
			<?php }  ?>
		</tr>
		<tr class="SummaryHeader">
			<td width="300">&nbsp;</td>
			<?php for ($x = 0; $x < $crmCounter; $x++) { ?>
				<td width="80">LOD</td>
			<?php }  ?>

		</tr>
		<?php for ($i = 0; $i < $compoundCounter; $i++) {
			$targetArray = getAnalytePrecisionAndBias($compounds[$i], $ReferenceMaterialID[0]);

		?>
			<tr>
				<td width="300" class="SummaryHeader"><?php echo $compounds[$i] ?></td>

				<?php for ($x = 0; $x < $crmCounter; $x++) {
					$resultArray = getAssessment($ReferenceMaterialID[$x], $compounds[$i]); ?>
					<td width="80">
						<a href="../mcerts/QC.php?ReferenceMaterialID=<?php echo $ReferenceMaterialID[$x] ?>&Compound=<?php echo $compounds[$i] ?>">
							<?php if (isset($resultArray['LOD'])) {
								echo sigfigs($resultArray['LOD'], 3, 3);
							} ?>

						</a>
					</td>
				<?php } ?>
			</tr>
		<?php } ?>
	</table>
</body>

</html>
<?php
/**
if(isset($_GET['final']))
	$myFile="../reports/LOD".$_GET['EID']."final.htm";
else	
	$myFile="../reports/LOD".$_GET['EID'].".htm";
	
$newFile = @fopen($myFile, 'w+') or die("can't open file");
fwrite($newFile,ob_get_contents());
fclose($newFile);
header(sprintf("Location: %s",$myFile));
 **/
?>