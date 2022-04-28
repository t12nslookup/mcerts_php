<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('datefunctions.php');
//$db->debug=true;

$SQL="select equipmentid,name from validation_reports where eid=(select eid from validation_reference_materials where referencematerialid=".$_GET['ReferenceMaterialID'].")";
$row=$db->GetRow($SQL);
$equipId=$row['EQUIPMENTID'];
$valName=$row['NAME'];
$refMat=$db->GetOne("select referencematerial from reference_materials where referencematerialid=".$_GET['ReferenceMaterialID']);		
$compoundDetails=$db->GetRow(sprintf("select compound,top,bottom from compounds where compoundid='%s'",$_GET['CompoundID']));
$top=$compoundDetails['TOP'];
$bottom=$compoundDetails['BOTTOM'];
$compound=$compoundDetails['COMPOUND'];

	$q_eid = sprintf("select distinct equipmentid from 
	validation_reports vr, validation_reference_materials vrm where vrm.referencematerialid=%s and vrm.eid = vr.eid",$_GET['ReferenceMaterialID']);
	$eid = $db->GetOne($q_eid);
	
//get all existing defined batches:
$query_ExistingBatches = sprintf("SELECT * FROM batches 
									WHERE ReferenceMaterialID = %s and selected=1 order by replicate1", $_GET['ReferenceMaterialID']);
									
$existingBatches = $db->GetAll($query_ExistingBatches);

$sample=$db->GetOne("select ReferenceMaterial
					from reference_materials
					where ReferenceMaterialID = ".$_GET['ReferenceMaterialID']);

//get background correction
$queryBlanks = sprintf("select * from results where sample=concat('%s','BLANK') and compound='%s'
						and acqon IN(select adjustmenttime
					from Batches
					where ReferenceMaterialID= %s
					and selected=1
					) and machine=%s
		",$sample,$compound,$_GET['ReferenceMaterialID'], $eid);
$blanks=$db->GetAll($queryBlanks);

//get batches data
function getBlankValue($adjustmentTime)
{
	global $blanks;
	foreach($blanks as $blank)
	{
		if($adjustmentTime==$blank['ACQON'])
			return $blank['CONCENTRATION'];			
	}
}

//get concentrations
$query_Replicates1 = sprintf("select Concentration,AcqOn from results
								where compound= (SELECT Compound
												from compounds
												where CompoundID=%s)
								and Sample=(select ReferenceMaterial
											from reference_materials
										  where ReferenceMaterialID = %s)       
								and AcqOn IN (select Replicate1
										from Batches
										where ReferenceMaterialID= %s										
										)
								and machine=%s
										ORDER BY AcqOn"
									  ,$_GET['CompoundID'],$_GET['ReferenceMaterialID'],$_GET['ReferenceMaterialID'],$equipId);
										//and selected=1
	
$Replicates1= $db->GetAll($query_Replicates1);	

$query_Replicates2 = sprintf("select Concentration,AcqOn from results
									where compound= (SELECT Compound
													from compounds
													where CompoundID=%s)
									and Sample=(select ReferenceMaterial
												from reference_materials
											  where ReferenceMaterialID = %s)       
									and AcqOn IN (select Replicate2
											from Batches
											where ReferenceMaterialID= %s 											
											)
									and machine=%s
											ORDER BY AcqOn"
										  ,$_GET['CompoundID'],$_GET['ReferenceMaterialID'],$_GET['ReferenceMaterialID'],$equipId);
										  //and selected=1
$Replicates2 = $db->GetAll($query_Replicates2);		

//form is submitted
if(isset($_POST['Submit'])) 
{			
	$refMatID=$_GET['ReferenceMaterialID'];
	$compoundID=$_GET['CompoundID'];
	$machine=$equipId;
	$referenceMaterial=$refMat;
	$validationName=$valName;
	
	$db->StartTrans(); //start transaction	
	$EID=$machine;
	
	
	//check if reference material exists for machine
	$ReferenceMaterialNo=$refMatID;
										
	
	//insert into batches and results table
	$i=0;
	$y=0;
	$AM="";
		//if any blanks are not blank then update reference material table
	foreach($existingBatches as $row_ExistingBatches)
	{	
		$y++;
		if($_POST['background'.$y]!=0 && $_POST['background'.$y]!="")
			$AM=$sample."BLANK";						
	}
	
	//update referencematerial and add adjustment material
	$updateBlank = sprintf("update reference_materials set adjustmentmaterial='%s' where 	
					referencematerialid='%s'",$AM,$_GET['ReferenceMaterialID']);
	$db->Execute($updateBlank);		
		
	foreach($existingBatches as $row_ExistingBatches)
	{

		$i++;

		$replicate1=$row_ExistingBatches['REPLICATE1'];
		$replicate2=$row_ExistingBatches['REPLICATE2'];
			
		if(false) //$_POST['secondReplicate'.$i]==""	||$_POST['firstReplicate'.$i]==""	|| $replicate1==$replicate2)
		{
		}else {
			$blankValue = $_POST['background'.$i];
			
			//delete existing blank value if one exists
			$deleteExistingBlank = sprintf("delete from results where machine=%s and compound='%s' 
											and sample=concat('%s','BLANK') 
											and to_char(acqon,'YYYY-MM-DD HH24:MI:SS')='%s'"
											,$machine,$compound,$refMat,$row_ExistingBatches['ADJUSTMENTTIME']);
			$db->Execute($deleteExistingBlank);
			
			//delete adjustment time from batches table
			$deleteAdjustmentTime = sprintf("update batches set adjustmenttime=null where batchid=%s",$row_ExistingBatches['BATCHID']);
			$db->Execute($deleteAdjustmentTime);
			
			if($blankValue!=0 && $blankValue!="")
			{
				//insert new blank value into results table.  Acq is first replicate, allthough could be any date really
				$insertBlankResult = sprintf("insert into results (concentration,sample,compound,machine,acqon)
										values (%s,concat('%s','BLANK'),'%s',%s,to_date('%s','YYYY-MM-DD HH24:MI:SS'))",
										$blankValue,$refMat,$compound,$machine,$row_ExistingBatches['REPLICATE1']);				
				$db->Execute($insertBlankResult);
				
				//update batches table with new adjustment time
				$updateBatches =sprintf("update batches set adjustmenttime=to_date('%s','YYYY-MM-DD HH24:MI:SS') where
									batchid=%s",$row_ExistingBatches['REPLICATE1'],$row_ExistingBatches['BATCHID']);
				$db->Execute($updateBatches);
			}

			//add normal results
			$existResult1 = sprintf("delete from results where machine='%s' and compound='%s' and sample='%s' 
									and to_char(acqon,'YYYY-MM-DD HH24:MI:SS')='%s'",
									$machine,
									$compound,
									$referenceMaterial,
									$replicate1);
			$db->Execute($existResult1);				
			
			$existResult2 = sprintf("delete from results where machine='%s' and compound='%s' and sample='%s' 
									and to_char(acqon,'YYYY-MM-DD HH24:MI:SS')='%s'",
									$machine,
									$compound,
									$referenceMaterial,
									$replicate2);
			$db->Execute($existResult2);											
			
			if($_POST['firstReplicate'.$i]!="")
			{
				$sqlFirstReplicateResult= sprintf("insert into results (machine,compound,concentration,acqon,sample,top,bottom)
							values('%s','%s',%s,to_date('%s','YYYY-MM-DD HH24:MI:SS'),'%s','%s','%s')",
							$machine,
							$compound,
							$_POST['firstReplicate'.$i],
							$replicate1,
							$referenceMaterial,
							$top,
							$bottom);
				$db->Execute($sqlFirstReplicateResult);
			}
				
			if($_POST['secondReplicate'.$i]!="")
			{		
				$sqlSecondReplicateResult= sprintf("insert into results (machine,compound,concentration,acqon,sample)
							values('%s','%s',%s,to_date('%s','YYYY-MM-DD HH24:MI:SS'),'%s')",
							$machine,
							$compound,
							$_POST['secondReplicate'.$i],
							$replicate2,
							$referenceMaterial);
				$db->Execute($sqlSecondReplicateResult);
			}		
				
					
		}
		$header=$_POST['previous'];//"newindex.php";
		header("Location:".$header);
	}
	//$db->FailTrans();
	if($db->HasFailedTrans()==false) { echo "<h3>MATRIX HAS BEEN SUCCESSFULLY ADDED</h3>"; }					
	$db->CompleteTrans(true); //end transaction										
} else if(isset($_POST['Submit'])) echo "<h3>All Form Fields have not been filled</h3>";

function getConcentrationFromArray($date)
{
	global $Replicates1,$Replicates2;
	foreach($Replicates1 as $replicate)
	{
		if($date==$replicate['ACQON'])
			return $replicate['CONCENTRATION'];			
	}
	
	foreach($Replicates2 as $replicate)
	{
		if($date==$replicate['ACQON'])
			return $replicate['CONCENTRATION'];			
	}
	return "";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Add Batch Data</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
@import url(../css/stylesheet.css);
</style>
</head>
<body>
<?php
echo "Hello:".$db->GetOne("SELECT VALUE FROM NLS_SESSION_PARAMETERS WHERE PARAMETER='NLS_DATE_FORMAT'");
?>
<div class="box"> <a href="newindex.php">[return to previous page]</a>
  <h1>Add Batch Concentrations</h1>
  Analyte : <?=$compound?><p>
  Sample : <?=$sample?><p>
  <?php if(count($existingBatches)==0) { ?>
  <h3>No batches have been set.  Click <a href="createBatchesForManualDataEntry.php?referencematerialid=<?php echo $_GET['ReferenceMaterialID']?>">here</a> to set batches</h3>
  <?php } else { ?>
  <form name="form1" method="post" action="">
    <table width="700"  border="0" cellspacing="0" cellpadding="0">
      <tr class="tableheader">
        <td width="15%">Replicate 1 Concentration </td>
        <td>Date</td>
        <td width="15%">Replicate 2 Concentration</td>
        <td>Date</td>
        <td width="15%">Background<br>
        Correction</td>
      </tr>
      <?php  
	  $i=0;
	  foreach($existingBatches as $row_ExistingBatches) {
	  $i++; 

	$replicate1 = getConcentrationFromArray($row_ExistingBatches['REPLICATE1']);
	$replicate2 = getConcentrationFromArray($row_ExistingBatches['REPLICATE2']);
	$blankValue = getBlankValue($row_ExistingBatches['ADJUSTMENTTIME']);
//	echo $row_ExistingBatches['ADJUSTMENTTIME'];
	
	//if(isset($Replicates1[$i-1]['CONCENTRATION']))
//		$replicate1=$Replicates1[$i-1]['CONCENTRATION'];
//	if(isset($Replicates2[$i-1]['CONCENTRATION']))
		//$replicate2=$Replicates2[$i-1]['CONCENTRATION'];?>
      <tr>
        <td><div align="center">
            <input name="firstReplicate<?php echo $i?>" type="text" id="firstReplicate<?php echo $i?>" size="10" value="<?=$replicate1?>">
          </div></td>
        <td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE1']); ?> </td>
        <td><div align="center">
            <input name="secondReplicate<?php echo $i?>" type="text" id="secondReplicate<?php echo $i?>" size="10" value="<?=$replicate2?>">
          </div></td>
        <td><?php echo getStringDateTime($row_ExistingBatches['REPLICATE2']); ?> </td>
        <td><input name="background<?php echo $i?>" type="text" id="background<?php echo $i?>" size="10" value="<?=$blankValue?>"></td>
      </tr>
      <?php } //end for ?>
    </table>
    <p>
      <input type="submit" name="Submit" value="Add Batches">
      <input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER']?>">
    </p>
  </form>
  <?php } ?>
</div>
</body>
</html>
