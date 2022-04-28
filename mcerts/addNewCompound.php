<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');

if (isset($_GET['EID']) && isset($_GET['ReferenceMaterialID'])) { //check that both URL parameters are set
	$colname_Compounds = (get_magic_quotes_gpc()) ? $_GET['EID'] : addslashes($_GET['EID']);
	$query_ReferenceMaterial = sprintf("SELECT ReferenceMaterial, DEFAULTTARGETSTANDARDDEVIATION,DefaultTargetBias 
										FROM reference_materials 
										WHERE ReferenceMaterialID = %s",$_GET['ReferenceMaterialID']);
	$row_ReferenceMaterial = $db->GetRow($query_ReferenceMaterial);
	$ReferenceMaterial= $row_ReferenceMaterial['REFERENCEMATERIAL'];
	$targetBias=$row_ReferenceMaterial['DEFAULTTARGETBIAS'];
	$standardDeviation=$row_ReferenceMaterial['DEFAULTTARGETSTANDARDDEVIATION'];
	
	$sqlEquipmentID="select equipmentid from validation_reports where EID=".$_GET['EID'];
	$EquipmentID=$db->GetOne($sqlEquipmentID);
	
	$query_Compounds = sprintf("SELECT DISTINCT Compound 
								FROM results 
								WHERE Machine = '%s'
								AND SAMPLE = '%s'		 
								AND NOT EXISTS 
									(SELECT * FROM compounds 
									WHERE compounds.Compound=results.Compound 
									AND results.Machine='%s' 
									AND compounds.ReferenceMaterialID = %s) 
								ORDER BY Compound", $EquipmentID,$ReferenceMaterial,$EquipmentID,$_GET['ReferenceMaterialID']);
	$Compounds = $db->GetAll($query_Compounds);
	$counter=0;
	foreach($Compounds as $row_Compounds) {
		$Compound[$counter]=$row_Compounds['COMPOUND'];
		$counter++;
	} 
}

if(isset($_POST['Insert'])) {
	for ($x=0;$x<$counter;$x++){
			$insertSQL = array();
			if(isset($_POST['checkbox'.$x])) {
			  	$compound = str_replace("'", "''", $Compound[$x]);
			  	$SQL = sprintf("INSERT INTO compounds (ReferenceMaterialID, Compound, TargetConcentration, TargetStandardDeviation, TargetBias,IncludeInReport) VALUES (%s,'%s', %s, %s, %s, %s)",
                       GetSQLValueString($_GET['ReferenceMaterialID'], "int"),
                       $compound,
                       GetSQLValueString($_POST['concentration'.$x], "double"),
                       GetSQLValueString($_POST['standarddeviation'.$x], "double"),
                       GetSQLValueString($_POST['bias'.$x], "double"),
					   GetSQLValueString(1, "int"));
				array_push($insertSQL,$SQL);
			}
			insertIntoDatabase($insertSQL);		
	}
  	$updateGoTo = "compoundsInSample.php?ReferenceMaterialID=".$_GET['ReferenceMaterialID'];
  	if (isset($_SERVER['QUERY_STRING'])) {
    	$updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
	    $updateGoTo .= $_SERVER['QUERY_STRING'];
  	}
  	header(sprintf("Location: %s", $updateGoTo));
}

function getConcentration($RefMaterial)
{
	if(preg_match('/\d+%/',$RefMaterial,$match))
	{
		$match=$match[0];
		preg_match('/\d+/',$match,$number);
		return $number[0];
	}
	if(preg_match('/\d+ppb/',$RefMaterial,$match2))
	{
		$match2=$match2[0];
		preg_match('/\d+/',$match2,$number2);
		return $number2[0];
	}
	return 0;
}
$concentration = getConcentration($ReferenceMaterial);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Add Compounds</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">@import url(../css/stylesheet.css);</style>
<script type="text/javascript">

function tickAll(){
	var noOfCheckBoxes = <?php echo $counter; ?>;

	for(i=0;i<noOfCheckBoxes;i++)
	{		
		eval("document.form1.checkbox"+i+".checked=true");
	}

} // end function

function fillAllTargetConcentration(){	
	var noOfCheckBoxes = <?php echo $counter; ?>;
	var concentration = document.form1.concentration0.value;
	var bias = document.form1.bias0.value;
	var sd = document.form1.standarddeviation0.value;
	for(i=0;i<noOfCheckBoxes;i++){
		eval("document.form1.concentration"+i+".value=concentration;");
		eval("document.form1.bias"+i+".value=bias;");
		eval("document.form1.standarddeviation"+i+".value=sd;")
	}
	return false;	
} // end function

function addConcentrations() {
	var noOfCheckBoxes = <?php echo $counter; ?>;
	var concentration = 20;
	for(i=0;i<noOfCheckBoxes;i++){
		eval("document.form1.concentration"+i+".value=concentration;");
	}
}

</script>
</head>
<body>
<div class="box">
	<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">[return to previous page]</a><?php if (isset($_GET['EID']) && isset($_GET['ReferenceMaterialID']) && $counter>0) { ?>
		<h3>Select Analytes for Reference Material : <?php echo $ReferenceMaterial;?></h3>
	<p>
		  <input type="submit" name="TickAll" value="Tick all boxes" onClick="javascript:tickAll();">		
	</p>
		<form name="form1" method="post" action="">
		  <input type="submit" name="Submit2" value="Add Checked Compounds">
		  <br>
		  <br>
		  <table  border="0" cellpadding="0" cellspacing="0" class="InputForm" nowrap>
			<tr class="tableheader">
			  <td width="50">Add</td>
			  <td width="260">Analyte</td>
			  <td>Target Concentration <br>
			  
			  </td>
			  <td>Target Bias</td>
			  <td>Target SD</td>
			</tr>
			<?php for($i=0;$i<$counter;$i++) { ?>
				<tr>
				<?php if (count($Compounds)>0) { ?>
				  <td width="50"><input class="checkbox" name="checkbox<?php echo $i; ?>" type="checkbox" value="true"></td>
				  <td><?php echo $Compound[$i]; ?></td>
				  <td>
				  <input name="concentration<?php echo $i;?>" type="text" id="concentration<?php echo $i;?>" value="<?php echo $concentration ?>"  size="10" maxlength="40">
				  </td>
				  	<td>
  					<input type="text" name="bias<?php echo $i;?>" value="<?php echo $targetBias?>" size="10" maxlength="40"/>
  					</td>
  					<td>  					
  					<input type="text" name="standarddeviation<?php echo $i;?>" value="<?php echo $standardDeviation?>" size="10" maxlength="40"/>
				  <? if ($i==0) { ?>
				  <input type="button" name="FillDown" value="Fill Down" onClick="javascript:fillAllTargetConcentration();">
				  <? } ?>  				  
  					</td>
				<? } //end if?>
				</tr>
			<?php } //end for?>
		  </table>
		  <br>
		  <input type="submit" name="Submit" value="Add Checked Compounds">
		  <input name="Insert" type="hidden" value="true">
		</form>
	<?php } else { ?>
		<h3>There are no analytes to add.</h3>
	<?php } ?>
</div>
</body>
</html>
