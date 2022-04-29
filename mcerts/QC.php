<?php
include('locked.php');
include('functionQC.php');
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
//include('../utility/mathematics.php');
//$db->debug=true;

$RMID = $_GET['ReferenceMaterialID'];

//get compound name
if (isset($_GET['CompoundID'])) {
	$sqlCompound = "select compound from compounds where compoundid=" . $_GET['CompoundID'];
	$compound = $db->GetOne($sqlCompound);
}

if (isset($_GET['Compound'])) {
	$compound = str_replace("\'", "'", $_GET['Compound']);
}

// get equipment id using reference materialid
$equipmentID = $db->GetOne(sprintf("select eid 
				from validation_reports
				where eid = (select EID from validation_reference_materials
							where referencematerialid=%s)", $RMID));

$result = getAssessment($RMID, $compound, true);
if (isset($result['error']) && $result['error'] == "stop")
	exit;


$compound = $result['compound'];
$EID = $result['machine'];
$sample = $result['sample'];
$batchVariance = $result['variances'];
$batchAverage = $result['averages'];
$aConc = $result['firstreplicates'];
$bConc = $result['secondreplicates'];
$noOfBatches = count($aConc);
$estimatedDOF = $result['estimateddof'];
$totalSD = $result['totalsd'];
$relativeSD = $result['relativesd'];
$targetSD = $result['targetsd'];
$targetSDPercent = $result['targetsdpercent'];
$fFromTable = $result['ffromtable'];
$fCalculated = $result['fcalculated'];
$biasTargetPercent = $result['biastargetpercent'];
$m0 = $result['m0'];
$m1 = $result['m1'];
$df = $result['df'];
$square = $result['square'];
$sdMeanRecovery = $result['sdmeanrecovery'];
$estimatedMeanRecovery = $result['estimatedmeanrecovery'];
$standardErrorOfMeanRecovery = $result['standarderrorofmeanrecovery'];
$confidenceLimits = $result['confidencelimits'];
$upperConfidenceLevel = $result['upperconfidencelevel'];
$lowerConfidenceLevel = $result['lowerconfidencelevel'];
$lowerRecoveryRange = $result['lowerrecoveryrange'];
$upperRecoveryRange = $result['upperrecoveryrange'];
$adjustmentMaterial = $result['adjustmentmaterial'];
$mean = $result['mean'];
$precisionAssessment = $result['precisiontest'];
$biasAssessment = $result['biastest'];
$referenceConcentration = $result['referenceconcentration'];
$estimatedBias = $result['estimatedbias'];
$LOD = $result['LOD'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<!--<style type="text/css">@import url(../css/stylesheet.css);</style>-->
	<link href="../css/stylesheet.css" media="screen, projection" rel="stylesheet" type="text/css" />
</head>

<body>

	<h3 class="title">

		<?php if ($locked != true) { ?>
			<a href="newindex.php">View All Validation Reports</a>
		<?php } ?>
		&gt; Compound Report
	</h3>
	<p align="center"> <a href="../reports/MCERTS<?php echo $equipmentID ?>.htm">[view mcerts summary]</a>
		<?php
		if ($locked != true) { ?>
			<a href="../reports/<?php echo $equipmentID ?>.htm">[view pass \ fail summary]</a><a href="editValues.php?ReferenceMaterialID=<?php echo $_GET['ReferenceMaterialID'] ?>&Compound=<?php echo urlencode($compound) ?>">
				<br>
				[edit concentrations]</a> <a href="selectBatches.php?ReferenceMaterialID=<? echo $_GET['ReferenceMaterialID'] ?>">[select batches]</a>
		<?php } else { ?>
			<!--
  <a href="../reports/<?php echo $equipmentID ?>final.htm">[view pass \ fail summary]
-->
		<?php } ?>
		</a>
	</p>
	<?php if ($adjustmentMaterial != "") { ?>
		<h3 align="center">Data has been background corrected by material <?php echo $adjustmentMaterial ?></h3>
	<?php } ?>
	<div class="Result">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="QCTable">
			<tr class="reportheader">
				<td colspan="5">MACHINE : <?php echo $EID ?></td>
			</tr>
			<tr class="reportheader">
				<td colspan="5">COMPOUND : <?php echo $compound ?></td>
			</tr>
			<tr class="reportheader">
				<td colspan="5">TEST SAMPLE : <?php echo $sample ?></td>
			</tr>
			<tr class="reportheader">
				<td colspan="5">CERTIFIED <span class="xl28">CONCENTRATION : <span class="xl29"><?php echo $referenceConcentration ?></td>
			</tr>
			<tr class="reportheader">
				<td width="20%">Batch </td>
				<td width="20%">Replicate</td>
				<td width="20%">&nbsp; </td>
				<td width="20%">Var </td>
				<td width="20%">Batch <span class="xl29">Means </td>
			</tr>
			<?php for ($i = 0; $i < $noOfBatches; $i++) { ?>
				<tr>
					<td><?php echo $i + 1 ?></td>
					<td width="15%">1</td>
					<td width="20%"><?php echo round($aConc[$i], 3); ?></td>
					<td><?php echo round($batchVariance[$i], 3); ?></td>
					<td><?php echo round($batchAverage[$i], 3); ?></td>
				</tr>
				<tr>
					<td><?php echo $i + 1 ?></td>
					<td>2 </td>
					<td><?php echo $bConc[$i]; ?></td>
					<td>&nbsp; </td>
					<td>&nbsp; </td>
				</tr>
			<?php } ?>
		</table>
		<br>
		<table width="100%" cellpadding="0" cellspacing="0" class="QCTable">
			<tr class="reportheader">
				<td colspan="2">Precision Test </td>
			</tr>
			<tr>
				<td>LOD</td>
				<td><?php echo round($LOD, 3); ?></td>
			</tr>
			<tr>
				<td>Mean </td>
				<td><?php echo round($mean, 3); ?></td>
			</tr>
			<tr>
				<td>Total sd </td>
				<td><?php echo round($totalSD, 3); ?></td>
			</tr>
			<tr>
				<td>Relative sd % </td>
				<td><?php echo round($relativeSD, 3); ?> % </td>
			</tr>
			<tr>
				<td>Target sd % </td>
				<td><?php echo round($targetSDPercent, 3); ?> % </td>
			</tr>
			<tr>
				<td>Target sd </td>
				<td><?php echo round($targetSD, 3); ?></td>
			</tr>
			<tr>
				<td>F 0.05 from tables </td>
				<td><?php echo round($fFromTable, 3); ?></td>
			</tr>
			<tr>
				<td>F calculated </td>
				<td><?php echo round($fCalculated, 3); ?></td>
			</tr>
			<tr>
				<td class="xl27">Estimated DOF </td>
				<td><?php echo round($estimatedDOF, 3); ?></td>
			</tr>
			<tr>
				<td>Assessment </td>
				<td><?php echo $precisionAssessment; ?></td>
			</tr>
			<tr>
				<td>Estimated Bias % </td>
				<td><?php echo round($estimatedBias, 3); ?> % </td>
			</tr>
			<tr class="reportheader">
				<td colspan="2">Bias Test</td>
			</tr>

			<?php if ($precisionAssessment == "FAIL") { ?>
				<tr>
					<td>Not Appropriate.</td>
					</td>
					<td></td>
				</tr>
			<?php } else { ?>
				<tr>
					<td>Bias Target Percent</td>
					<td><?php echo $biasTargetPercent; ?> % </td>
				</tr>
				<tr>
					<td>Reference Concentration </td>
					<td><?php echo $referenceConcentration; ?></td>
				</tr>
				<tr>
					<td>Mean measured value </td>
					<td><?php echo round($mean, 3); ?></td>
				</tr>
				<tr>
					<td>Estimate mean recovery % </td>
					<td><?php echo round($estimatedMeanRecovery, 3); ?> % </td>
				</tr>
				<tr>
					<?php if ($biasAssessment == "SPASS" || $biasAssessment == "FAIL") { ?>
						<td>Sd of mean recovery </td>
						<td><?php echo round($sdMeanRecovery, 3); ?></td>
				</tr>
				<tr>
					<td>Std error of mean recovery </td>
					<td><?php echo round($standardErrorOfMeanRecovery, 3) ?></td>
				</tr>
				<tr>
					<td>95% confidence limits on mean recovery </td>
					<td>+/- <?php echo round($confidenceLimits, 5); ?></td>
				</tr>
				<tr>
					<td>Upper confidence level </td>
					<td><?php echo round($upperConfidenceLevel, 3) ?></td>
				</tr>
				<tr>
					<td>Lower confidence level </td>
					<td><?php echo round($lowerConfidenceLevel, 3) ?></td>
				</tr>
				<tr>
					<td>Recovery Range </td>
					<td>
						<?php echo round($lowerRecoveryRange, 3) ?> % -<br>
						<?php echo round($upperRecoveryRange, 3) ?> %
					</td>
				</tr>
			<?php } //end if 
			?>
			<tr>
				<td>Assessment </td>
				<td><?php echo $biasAssessment ?> </td>
			</tr>
			<tr>
				<td>M0 </td>
				<td><?php echo round($m0, 3); ?></td>
			</tr>
			<tr>
				<td>M1 </td>
				<td><?php echo round($m1, 3); ?></td>
			</tr>
			<tr>
				<td>DF </td>
				<td><?php echo round($df, 3); ?></td>
			</tr>
			<tr>
				<td>Square </td>
				<td><?php echo round($square, 3); ?></td>
			</tr>
			<tr>
				<td>Total SD </td>
				<td><?php echo round($totalSD, 3); ?></td>
			<?php } //end if 
			?>
			</tr>
		</table>
	</div>
</body>

</html>