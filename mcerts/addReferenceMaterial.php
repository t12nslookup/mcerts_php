<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
//$db->debug = true;

if (isset($_GET['EID'])) {
	$EID = (get_magic_quotes_gpc()) ? $_GET['EID'] : addslashes($_GET['EID']);
	$sqlEquipmentID="select equipmentid from validation_reports where EID=".$EID;
	$EquipmentID=$db->GetOne($sqlEquipmentID);
	$sqlManuallyAdded="select manuallyAdded from validation_reports where EID=".$EID;
	$manuallyAdded=$db->GetOne($sqlManuallyAdded);

  $query_SampleList = "";

  if(isset($_GET['SAMPLE']))
  {
    $query_SampleList = sprintf("
        SELECT distinct results.Sample
        FROM results
        where results.Machine='%s' and sample='%s'",$EquipmentID, $_GET['SAMPLE']);
  } else {
    $query_SampleList = sprintf("
        SELECT distinct results.Sample
        FROM results
        where results.Machine='%s' and acqon > (sysdate - 40)",$EquipmentID);
  }
			// and (UPPER(results.sample) like '%%BLANK%%' or UPPER(results.sample) like '%%MCERTS%%'
			//or UPPER(results.sample) like '%%UKAS%%')
			/*and not exists
				( select * from reference_materials,validation_reference_materials,validation_reports
				where reference_materials.REFERENCEMATERIALID=validation_reference_materials.REFERENCEMATERIALID
				and validation_reports.EID = validation_reference_materials.EID
				and validation_reports.EquipmentID='%s'
				and reference_materials.REFERENCEMATERIAL=results.Sample
				and reference_materials.INCLUDEINREPORT=1)", $EquipmentID,$EquipmentID);	*/
	//print $query_SampleList;
	$SampleList=$db->GetAll($query_SampleList);
}

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form2") && isset($_GET["EID"])) {
	//insert Reference Material SQL:

	$insertMaterial = sprintf("INSERT INTO reference_materials (ReferenceMaterial, TrivialName, DEFAULTTARGETSTANDARDDEVIATION, DefaultTargetBias, IncludeInReport) VALUES (%s, %s, %s, %s, %s)",
    	                   GetSQLValueString($_POST['ReferenceMaterial'], "text"),
        	               GetSQLValueString($_POST['TrivialName'], "text"),
            	           GetSQLValueString($_POST['DefaultTargetStandardDeviation'], "double"),
                	       GetSQLValueString($_POST['DefaultTargetBias'], "double"),
                    	   GetSQLValueString(isset($_POST['IncludeInReport']) ? "true" : "", "defined","1","0"));
	$getSequenceNo = 'SELECT RM_seq.currVal FROM DUAL';
	
	$db->StartTrans(); //start transaction	
		$db->Execute($insertMaterial);
		$maxReferenceMaterialID =$db->GetOne($getSequenceNo);
		//insert data into link table   
		$insertLink = sprintf("INSERT INTO validation_reference_materials (EID, ReferenceMaterialID) 
							VALUES (%s, %s)",
							GetSQLValueString($_POST['EID'], "text"),
							GetSQLValueString($maxReferenceMaterialID, "int"));
		$db->Execute($insertLink);
	$db->CompleteTrans(true); //end transaction
	
	//goto index page
	$insertGoTo = "mcertsindex.php";
	header(sprintf("Location: %s",$_POST['previous']));
//	header(sprintf("Location: %s", $insertGoTo));
} 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Add Reference Material</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">@import url(../css/stylesheet.css);</style>
</head>
<body>
<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">[return to previous page]</a>
<?php if(!isset($_GET['EID'])) { exit; } ?>
<?php if (count($SampleList)==0 && $manuallyAdded==0) { ?>
	<h3>There are no reference materials </h3>
<?php exit;} ?>
<h1>Add Reference Material</h1>

<form method="post" name="form2" action="<?php echo $editFormAction; ?>">
	<table border="0" cellpadding="0" cellspacing="0" class="InputForm">
		<tr valign="baseline">
		  <td width="261" align="right" nowrap>EID:</td>
		  <td width="640"><?php if(isset($_GET['EID'])) {echo $EquipmentID;} ?></td>
		</tr>
		<?php if($manuallyAdded==0) { ?>
		<tr valign="baseline">
			<td nowrap align="right">Reference Material :</td>
			<td>
			<select name="ReferenceMaterial" id="ReferenceMaterial">
			<option value=""></option>
			<?php foreach($SampleList as $row_SampleList) { ?>
				<option value="<?php echo $row_SampleList['SAMPLE']?>" selected><?php echo $row_SampleList['SAMPLE']?></option>
			<?php } ?>
			</select>
			</td>
		</tr>
		<?php } ?>
		<?php if($manuallyAdded==1) { ?>
		<tr valign="baseline">
		  <td nowrap align="right">Reference Material :</td>
		  <td><input name="ReferenceMaterial" type="text" id="ReferenceMaterial" size="30"></td>
		</tr>
		<?php } ?>
		<tr valign="baseline">
		  <td nowrap align="right">TrivialName:</td>
		  <td><input type="text" name="TrivialName" size="32" value="<?php echo $_GET["SAMPLE"] ?>"></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">Default Target Standard Deviation:</td>
		  <td><input name="DefaultTargetStandardDeviation" type="text" id="DefaultTargetStandardDeviation" value="15" size="32"></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">Default Target Bias:</td>
		  <td><input type="text" name="DefaultTargetBias" value="30" size="32"></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">Include In Report:</td>
		  <td><input name="IncludeInReport" type="checkbox" value="" checked ></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">&nbsp;</td>
		  <td><input type="submit" value="Add Reference Material"></td>
		</tr>
  </table>
	<input type="hidden" name="MM_insert" value="form2">
	<input type="hidden" name="EID" value="<?php echo $_GET['EID']?>">
	<input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER']?>">
</form>
</body>
</html>
