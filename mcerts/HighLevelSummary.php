<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('functionQC.php');

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
?>
						 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">@import url(../css/stylesheet.css);</style>
</head>

<body>
<h3>MCERTS VALIDATION SUMMARY</h3>
<h3>Equipment : <?php echo $_GET['EID'] ?></h3>
<table  border="0" cellspacing="0" cellpadding="0" class="Summary">
  <tr class="SummaryHeader">
    <td width="280">&nbsp;</td>
    <?php for($x=0;$x<$crmCounter;$x++) { ?>
    <td colspan="2"><?php echo $ReferenceMaterial[$x] ?></td>
    <?php } ?>
  </tr>
  <tr class="SummaryHeader">
    <td width="280">&nbsp;</td>
	<?php for($x=0;$x<$crmCounter;$x++) { ?>
    <td width="80">Prec</td>
    <td width="80">Bias</td>
	<?php } ?>
  </tr>
  
  <?php
  for($i=0;$i<$compoundCounter;$i++) {
  ?>
  <tr>
    <td width="280" class="SummaryHeader"><?php echo $compounds[$i] ?></td>
    <?php for($x=0;$x<$crmCounter;$x++) { 
	$Precision=getAssessment($ReferenceMaterialID[$x],$compounds[$i],"Precision");
	$Bias=getAssessment($ReferenceMaterialID[$x],$compounds[$i],"Bias");
	?>
	<td width="80">
		<a href="QC.php?ReferenceMaterialID=<?php echo $ReferenceMaterialID[$x]?>&Compound=<?php echo $compounds[$i]?>">
		<span class="<?php if($Precision=="FAIL") echo "Fail"; else echo "Pass";?>"><?php echo $Precision ?></span>
		</a>
	</td>
	<td width="80">
		<a href="QC.php?ReferenceMaterialID=<?php echo $ReferenceMaterialID[$x]?>&Compound=<?php echo $compounds[$i]?>">
		<span class="<?php if($Bias=="FAIL") echo "Fail"; else echo "Pass";?>"><?php echo $Bias?></span>		
		</a>
	</td>
	<?php } ?>
  </tr>
  <?php } ?>
</table>
</body>
</html>
