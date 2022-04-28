<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('datefunctions.php');
//$db->debug=true;

//check that all neccassary form fields are filled:
$incomplete=false;
if(isset($_POST['Submit']) ) 
{
	if($_POST['EID']=="" || $_POST['ReferenceMaterial']=="" || $_POST['Compound']==""
		|| $_POST['TargetConcentration']=="" || $_POST['TargetStandardDeviation']=="" 
		|| $_POST['TargetBias']=="" || $_POST['ValidationName']="") 
		$incomplete=true;
}

echo $incomplete;
if(isset($_GET['referencematerialid']))
{
	$SQL="select equipmentid,name from validation_reports where eid=(select eid from validation_reference_materials where referencematerialid=".$_GET['referencematerialid'].")";
	$row=$db->GetRow($SQL);
	$equipId=$row['EQUIPMENTID'];
	$valName=$row['NAME'];
	$refMat=$db->GetOne("select referencematerial from reference_materials where referencematerialid=".$_GET['referencematerialid']);		
}

if(isset($_POST['Submit']) && $incomplete==false) 
{			
	$machine=$_POST['EID'];
	$compound=$_POST['Compound'];
	$referenceMaterial=$_POST['ReferenceMaterial'];
	$validationName=$_POST['VALIDATIONNAME'];
	
	$db->StartTrans(); //start transaction	
	
	//get EID
	$sqlGetEID=sprintf("select EID 
						from validation_reports 
						where EquipmentID=%s
						and name='%s'",
						$machine,
						$validationName);
	$EID=$db->GetOne($sqlGetEID);
	
	//check if reference material exists for machine
	$ReferenceMaterialNo=$db->GetOne("select reference_materials.referencematerialid 
										from reference_materials,validation_reference_materials vrm
										where Referencematerial='".$referenceMaterial."'
										and vrm.referencematerialid=reference_materials.referencematerialid
										and vrm.EID='".$EID."'");

	//add analyte
	$checkCompound = $db->GetOne("select count(*) from compounds where compound='".$compound."' and referencematerialid=".			$_GET['referencematerialid']);
	if($checkCompound==0)
 	{
		$sqlCompoundInsert = sprintf("insert into compounds (referencematerialid,compound,includeinreport,targetconcentration,targetstandarddeviation,targetbias,top,bottom)
									values (%s,'%s',1,%s,%s,%s,'%s','%s')",
									$_GET['referencematerialid'],
									$compound,
									$_POST['TargetConcentration'],
									$_POST['TargetStandardDeviation'],
									$_POST['TargetBias'],
									$_POST['top'],
									$_POST['bottom']);
		$db->Execute($sqlCompoundInsert);
	}
		
	$db->CompleteTrans(true); //end transaction	
	
	$updateGoTo = "compoundsInSample.php?ReferenceMaterialID=".$_GET['referencematerialid'];
  	if (isset($_SERVER['QUERY_STRING'])) {
    	$updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
	    $updateGoTo .= $_SERVER['QUERY_STRING'];
  	}
  	header(sprintf("Location: %s", $updateGoTo));									
} else if(isset($_POST['Submit'])) echo "<h3>All Form Fields have not been filled</h3>";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">@import url(../css/stylesheet.css);</style>
</head>

<body>
<div class="box">
<a href="index.php">[return to previous page]</a>
<h1>Add Analyte </h1>
<form name="form1" method="post" action="">
	<table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="41%" class="tableheader"><div align="left">Equipment ID:</div></td>
        <td width="59%"><input name="EID" type="hidden" id="EID3" value="<?php echo $equipId ?>">
        <?php echo $equipId ?> </td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Validation Name: </div></td>
        <td>        <input name="VALIDATIONNAME" type="hidden" id="VALIDATIONNAME" value="<?php echo $valName ?>">
        <?php echo $valName ?></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Reference Material : </div></td>
        <td><input name="ReferenceMaterial" type="hidden" id="ReferenceMaterial4" value="<?php echo $refMat ?>">
        <?php echo $refMat
 ?></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Analyte:</div></td>
        <td><input name="Compound" type="text" id="Compound5"></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Target Concentration for Analyte: </div></td>
        <td><input name="TargetConcentration" type="text" id="TargetConcentration3" value="0"></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Target Standard Deviation % for Analyte in %:</div></td>
        <td><input name="TargetStandardDeviation" type="text" id="TargetStandardDeviation4" value="15"></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Units</div></td>
        <td><select name="top" id="top">
          <option value="ph" selected>ph</option>
          <option value="mg">ug</option>
          <option value="mg">mg</option>
          <option value="kg">kg</option>
          <option value="ng">ng</option>
          <option value="ml">ml</option>
          <option value="jl">kl</option>
          <option value="nl">nl</option>
                </select>          
          <select name="bottom" id="bottom">
            <option value="Abs" selected>Absolute</option>            
            <option value="mg">ug</option>
            <option value="mg">mg</option>            
            <option value="kg">kg</option>
            <option value="ng">ng</option>
            <option value="ml">ml</option>
            <option value="jl">kl</option>
            <option value="nl">nl</option>
          </select></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Target Bias % for Analyte : </div></td>
        <td><input name="TargetBias" type="text" id="TargetBias3" value="30"></td>
      </tr>
    </table>
    <p>
      <input type="submit" name="Submit" value="Add Analyte">
      <input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER']?>">
      <input name="Submit" type="hidden" id="Submit" value="true">
</p>
</form>
</div>
</body>
</html>
