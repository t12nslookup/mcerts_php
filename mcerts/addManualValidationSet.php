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
	/*for($i=1;$i<12;$i++) 
	{
		if($_POST['firstReplicate'.$i]=="") $incomplete=true;
		if($_POST['secondReplicate'.$i]=="") $incomplete=true;
	}*/
}

if(isset($_GET['referencematerialid']))
{
	$SQL="select equipmentid,name from validation_reports where eid=(select eid from validation_reference_materials where referencematerialid=".$_GET['referencematerialid'].")";
	$row=$db->GetRow($SQL);
	$equipId=$row['EQUIPMENTID'];
	$valName=$row['NAME'];
	$refMat=$db->GetOne("select referencematerial from reference_materials where referencematerialid=".$_GET['referencematerialid']);		
}else  
if(isset($_GET['eid']))
{
	$row=$db->GetRow("select equipmentid,name from validation_reports where eid=".$_GET['eid']);
	$equipId=$row['EQUIPMENTID'];
	$valName=$row['NAME'];
	$refMat="";
} else {$equipId="";$valName="";$refMat="";}

//get all existing defined batches:
$query_ExistingBatches = sprintf("SELECT * FROM batches 
									WHERE ReferenceMaterialID = %s and selected=1 order by replicate1", $_GET['referencematerialid']);								 
$existingBatches = $db->GetAll($query_ExistingBatches);

if(isset($_POST['Submit']) && $incomplete==false) 
{	
		
	$machine=$_POST['EID'];
	$compound=$_POST['Compound'];
	$referenceMaterial=$_POST['ReferenceMaterial'];
	$validationName=$_POST['VALIDATIONNAME'];
	
	$db->StartTrans(); //start transaction	

	echo "transaction started";
	//insert into validation_reports table
	
	//check if Validation Report allready exists. if does not, then create
	$sqlGetEID=sprintf("select EID 
						from validation_reports 
						where EquipmentID=%s
						and name='%s'",
						$machine,
						$validationName);
	$EID=$db->GetOne($sqlGetEID);
	
	//$checkReportExists="select count(*) from validation_reports where EID='".$machine."'";
	//$countEID=$db->GetOne($checkReportExists);

	if($EID=="") 
	{
		$sqlValidationReports = sprintf("insert into validation_reports(Equipmentid,EXPIRED,NAME) 
										values('%s',0,'%s')",$machine,$validationName);
		$db->Execute($sqlValidationReports);
		$EID =$db->GetOne("SELECT VALIDATION_seq.currVal FROM DUAL");
	}
	
	
	
	//check if reference material exists for machine
	$ReferenceMaterialNo=$db->GetOne("select reference_materials.referencematerialid 
										from reference_materials,validation_reference_materials vrm
										where Referencematerial='".$referenceMaterial."'
										and vrm.referencematerialid=reference_materials.referencematerialid
										and vrm.EID='".$EID."'");
										
	if($ReferenceMaterialNo=="") //if it does not exists then add reference material and get referencematerialno
	{
		$sqlReferenceMaterial = sprintf("insert into reference_materials(referencematerial,includeinreport)	values('%s',1)",$referenceMaterial);
		$db->Execute($sqlReferenceMaterial);
		$ReferenceMaterialNo =$db->GetOne("SELECT RM_seq.currVal FROM DUAL");
		//insert into validation reference materials table
		$sqlValidationReferenceMaterial = sprintf("insert into validation_reference_materials (EID,referencematerialid)
											values('%s',%s)",$EID,$ReferenceMaterialNo);
		$db->Execute($sqlValidationReferenceMaterial);
	}
	else 
	{
		$deleteBatches="delete from batches where referencematerialid=".$ReferenceMaterialNo;
		$db->Execute($deleteBatches);
	}

	//check if compound exists for reference material, if it does then delete all results,compound and batches data for that compound
	$checkCompound = $db->GetOne("select count(*) from compounds where compound='".$compound."' and referencematerialid=".$ReferenceMaterialNo);
	if($checkCompound==0)
 	{
		$sqlCompoundInsert = sprintf("insert into compounds (referencematerialid,compound,includeinreport,targetconcentration,targetstandarddeviation,targetbias)
									values (%s,'%s',1,%s,%s,%s)",
									$ReferenceMaterialNo,
									$compound,
									$_POST['TargetConcentration'],
									$_POST['TargetStandardDeviation'],
									$_POST['TargetBias']);
		$db->Execute($sqlCompoundInsert);
	}
	
	//insert into batches and results table
	for($i=1;$i<12;$i++) 
	{
		$day=$_POST['firstday'.$i];
		$day = strlen($day)==1 ? "0".$day  : $day;
		
		$month=$_POST['firstmonth'.$i];
		$month = strlen($month)==1 ? "0".$month  : $month;
		
		$year=$_POST['firstyear'.$i];
		
		$hour=$_POST['firsthour'.$i];
		$hour = strlen($hour)==1 ? "0".$hour  : $hour;
		
		$minute=$_POST['firstminute'.$i];
		$minute = strlen($minute)==1 ? "0".$minute  : $minute;
		
		$replicate1=$year.$month.$day." ".$hour.":".$minute;
		
		$secondday=$_POST['secondday'.$i];
		$secondday = strlen($secondday)==1 ? "0".$secondday : $secondday;
		
		$secondmonth=$_POST['secondmonth'.$i];
		$secondmonth = strlen($secondmonth)==1 ? "0".$secondmonth  : $secondmonth;
		
		$secondyear=$_POST['secondyear'.$i];
		
		$secondhour=$_POST['secondhour'.$i];
		$secondhour = strlen($secondhour)==1 ? "0".$secondhour : $secondhour;
		
		$secondminute=$_POST['secondminute'.$i];
		$secondminute = strlen($secondminute)==1 ? "0".$secondminute : $secondminute;
		
		$replicate2=$secondyear.$secondmonth.$secondday." ".$secondhour.":".$secondminute;
			
		if($_POST['secondReplicate'.$i]=="" || $_POST['secondReplicate'.$i]==0
			||$_POST['firstReplicate'.$i]=="" || $_POST['firstReplicate'.$i]==0
			|| $replicate1==$replicate2)
		{
		}else {
					
			$sqlBatches=sprintf("insert into batches (referencematerialid,replicate1,replicate2,selected,multiplier)
								values(%s,to_date('%s','yyyymmdd HH24:MI'),to_date('%s','yyyymmdd HH24:MI'),1,1)",
								$ReferenceMaterialNo,
								$replicate1,							
								$replicate2);
			$db->Execute($sqlBatches);
			
			$existResult1 = sprintf("delete from results where machine='%s' and compound='%s' and sample='%s' 
									and to_char(acqon,'yyyymmdd HH24:MI')='%s'",
									$machine,
									$compound,
									$referenceMaterial,
									$replicate1);
			$db->Execute($existResult1);				
			
			$existResult2 = sprintf("delete from results where machine='%s' and compound='%s' and sample='%s' 
									and to_char(acqon,'yyyymmdd HH24:MI')='%s'",
									$machine,
									$compound,
									$referenceMaterial,
									$replicate2);
			$db->Execute($existResult2);			
									
									
									
			$sqlFirstReplicateResult= sprintf("insert into results (machine,compound,concentration,acqon,sample,top,bottom)
							values('%s','%s',%s,to_date('%s','yyyymmdd HH24:MI'),'%s','%s','%s')",
							$machine,
							$compound,
							$_POST['firstReplicate'.$i],
							$replicate1,
							$referenceMaterial,
							$_POST['top'],
							$_POST['bottom']);
							
			$sqlSecondReplicateResult= sprintf("insert into results (machine,compound,concentration,acqon,sample)
							values('%s','%s',%s,to_date('%s','yyyymmdd HH24:MI'),'%s')",
							$machine,
							$compound,
							$_POST['secondReplicate'.$i],
							$replicate2,
							$referenceMaterial);		
			$db->Execute($sqlFirstReplicateResult);	
			$db->Execute($sqlSecondReplicateResult);		
		}
	}
	//$db->FailTrans();
	if($db->HasFailedTrans()==false) { echo "<h3>MATRIX HAS BEEN SUCCESSFULLY ADDED</h3>"; }					
	$db->CompleteTrans(true); //end transaction										
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
<h1>ADD 11x2 MCERTS MATRIX </h1>
<form name="form1" method="post" action="">
	<table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="41%" class="tableheader"><div align="left">Equipment ID:</div></td>
        <td width="59%"><input name="EID" type="text" id="EID3" value="<?php echo $equipId ?>"></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Validation Name: </div></td>
        <td>        <input name="VALIDATIONNAME" type="text" id="VALIDATIONNAME" value="<?php echo $valName ?>"></td>
      </tr>
      <tr>
        <td class="tableheader"><div align="left">Reference Material : </div></td>
        <td><input name="ReferenceMaterial" type="text" id="ReferenceMaterial4" value="<?php echo $refMat ?>"></td>
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
          <option value="mg">mg</option>
          <option value="kg">kg</option>
          <option value="ng">ng</option>
          <option value="ml">ml</option>
          <option value="jl">kl</option>
          <option value="nl">nl</option>
                </select>          
          <select name="bottom" id="bottom">
            <option value="Abs" selected>Absolute</option>
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
  <p>For each batch, ensure that the dates and times for replicate 1 and replicate 2 are not the same. </p>
  <p>The date and time must be unique to each replicate. </p>
  <table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr class="tableheader">
        <td width="17%">Replicate 1 Concentration </td>
        <td width="19%">Date</td>
        <td width="13%">Time</td>
        <td width="17%">Replicate 2 Concentration</td>
        <td width="20%">Date</td>
        <td width="14%">Time</td>
      </tr>
      <?php for($x=1;$x<12;$x++) { ?>
	  <tr>
        <td><div align="center">
          <input name="firstReplicate<?php echo $x?>" type="text" id="firstReplicate<?php echo $x?>" size="10">
        </div></td>
        <td><select name="firstday<?php echo $x?>" id="select15">
            <?php for($i=1;$i<32;$i++) { ?>
            <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
            <?php } ?>
          </select>
          <select name="firstmonth<?php echo $x?>" id="select16">
            <?php for($i=1;$i<13;$i++) { ?>
            <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
            <?php } ?>
          </select>
          <select name="firstyear<?php echo $x?>" id="select17">
            <?php for($i=2002;$i<2020;$i++) { ?>
            <option value="<?php echo $i ?>" <?php if($i==2005) {echo "selected";}?>> <?php echo $i ?> </option>
            <?php } ?>
          </select></td>
        <td>
          <select name="firsthour<?php echo $x?>" id="select30">
            <?php for($i=0;$i<25;$i++) { ?>
            <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
            <?php } ?>
          </select>
:
<select name="firstminute<?php echo $x?>" id="select31">
  <?php for($i=0;$i<61;$i++) { ?>
  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
  <?php } ?>
</select></td>
        <td><div align="center">
          <input name="secondReplicate<?php echo $x?>" type="text" id="secondReplicate<?php echo $x?>" size="10">
        </div></td>
        <td>		<select name="secondday<?php echo $x?>" id="select15">
            <?php for($i=1;$i<32;$i++) { ?>
            <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
            <?php } ?>
          </select>
          <select name="secondmonth<?php echo $x?>" id="select16">
            <?php for($i=1;$i<13;$i++) { ?>
            <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
            <?php } ?>
          </select>
          <select name="secondyear<?php echo $x?>" id="select17">
            <?php for($i=2002;$i<2020;$i++) { ?>
            <option value="<?php echo $i ?>" <?php if($i==2005) {echo "selected";}?>> <?php echo $i ?> </option>
            <?php } ?>
          </select>		</td>
        <td>
          <select name="secondhour<?php echo $x?>" id="select32">
            <?php for($i=0;$i<25;$i++) { ?>
            <option value="<?php echo $i ?>" <?php if($i==1) {echo "selected";}?>> <?php echo $i ?> </option>
            <?php } ?>
          </select>
:
<select name="secondminute<?php echo $x?>" id="select33">
  <?php for($i=0;$i<61;$i++) { ?>
  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
  <?php } ?>
</select></td>
    </tr>
      <?php } //end for ?>
  </table>
	<p>
      <input type="submit" name="Submit" value="Add Matrix">
      <input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER']?>">
	</p>
</form>
<h2>Existing Replicates </h2>
<table  border="0" cellspacing="0" cellpadding="0">
  <tr class="tableheader">
    <td width="207">Replicate 1 </td>
    <td width="207">Replicate 2 </td>
  </tr>
  <?php 
	  $counter=0;
	  foreach($existingBatches as $row_ExistingBatches) { ?>
  <?php if (count($existingBatches) > 0) { // Show if recordset not empty ?>
  <tr>
    <td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE1']); ?></td>
    <td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE2']); ?></td>
  </tr>
  <?php } // Show if recordset not empty ?>
  <?php $counter++;
		}//end for ?>
</table>
<p>&nbsp;</p>
</div>
</body>
</html>
