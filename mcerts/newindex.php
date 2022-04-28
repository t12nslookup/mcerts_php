<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('datefunctions.php');
//$db->debug=true;

$expired = isset($_GET['SHOWEXPIRED']) ? 1 : 0;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<style type="text/css">@import url(../css/stylesheet.css);</style>
<meta http-equiv="Content-Language" content="en" />
<meta name="GENERATOR" content="PHPEclipse 1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>MCERTS MAIN MENU</title>
<link rel="shortcut icon" href="../images/favicon.ico">
<style type="text/css">
<!--
.style2 {color: #F39EAF}
-->
</head>
<body>
<div align="center">
<h3 class="title" id="refmat"><a href="newindex.php">All Validation Reports</a></h3>
	<p>
	<a href="sop6m.html" target="blank">Method Description</a>
		<p>
  <?php 
	include('locked.php');	
	if($locked==false) { ?>
		<br>
<a href="addUpdateValidationSet.php"> Create new validation report </a>
  <p>
  <a href="instructions/instructions.php">Instructions for use</a>
<p>
    <?php if(isset($_GET['SHOWEXPIRED'])) { ?>
    <span class="style2"><a href="newindex.php">View non-expired validation reports</a>
    <p>
    
    <?php } else { ?>
    <a href="newindex.php?SHOWEXPIRED=true">View expired validation reports</a>
    <p>
    <?php } ?>
     <a href="http://limsgate.saiman.co.uk:3000">Upload data</a>
     <p>
     <a href="unlockAll.php">Un-lock all validation reports</a>
     <p> 
    <?php } ?>
    </span></p>

<? if (false) { ?>
<h3>Drying Validation </h3>
<p><a href="../reports/DryingValidationSignificanceFeb06.pdf">Man Metals Drying Validation Significance Test Results </a></p>
<p><a href="../reports/dryingvalidationprotocolFeb06.pdf">Man Metals Drying Validation Protocol</a></p>
<p><a href="../reports/dryingvalidationresults1Feb06.pdf">Man Metals Drying Validation Results 1 </a></p>
<p><a href="../reports/dryingvalidationresults2Feb06.pdf">Man Metals Drying Validation Results 2 </a></p>
<p><a href="../reports/dryingvalidationreviewFeb06.pdf">Man Metals Drying Validation Review</a></p>
<p>
	  <!--
     <tr>
	      <td height="29">Drying Validation </td>
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>
	      <td><div align="center"><a href="../reviews/dryingresults.pdf">[view results] </a></div></td>
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>
	      <td>&nbsp;</td>
      </tr>
	  -->
<? } ?>

<p>
<table border="1">


	<?php
	$sqlEquipmentId="select distinct equipmentid from validation_reports";
	$equipmentIds=$db->GetAll($sqlEquipmentId);
        $rowcount=0;
	foreach($equipmentIds as $equipmentId)
	{ 
	    $sqlValidationReport="select * from validation_reports where Equipmentid=".$equipmentId['EQUIPMENTID'].
							" and expired =".$expired;
		$validationReports=$db->GetAll($sqlValidationReport); 
		if(count($validationReports)>0) 
		{	
		?>
                
                <?php if($rowcount % 5 == 0) { ?>
                    
   <tr>
      <th>Validation Name </th>
       <?php if($locked!=true) { ?>
      <th>Reference <br>
        Materials</th>
              <?php } ?>
      <th>MCERTS<br>
        Summary </th>
      <th>Pass\Fail<br>
        Summary</th>
      <th>LOD <br>Summary</th>
      <th>Analysis Dates</th>
      <th><p>Validation<br>
        Form </p></th>
      <th>Review<br>
        Document</th>
      <?php if($locked!=true) { ?>
      <?php } ?>
</tr>
  
                <?php } ?>
            
		  <?php if($locked!=true) { ?>
		  <tr class="equipment">
			  <td colspan=9>Instrument ID <?php echo $equipmentId['EQUIPMENTID']?></td>			  
			  <?php if($locked!=true) { ?>
			  <?php } ?>
		  </tr>
		  <?php } ?>
		<?
			foreach($validationReports as $validationReport)
			{
					$startDate=	$db->GetOne(sprintf("select min(replicate1)
							from batches 
							where referencematerialid in 
								  (select rm.referencematerialid 
								  from validation_reference_materials vrm,validation_reports vr, reference_materials rm
								  where vrm.eid = %s
								  and vr.EID=%s
								  and rm.referencematerialid=vrm.referencematerialid
								  and rm.includeinreport=1
								  )
							and selected=1",$validationReport['EID'],$validationReport['EID']));
					$endDate=	$db->GetOne(sprintf("select max(replicate2)
							from batches 
							where referencematerialid in 
								  (select rm.referencematerialid 
								  from validation_reference_materials vrm,validation_reports vr, reference_materials rm
								  where vrm.eid = %s
								  and vr.EID=%s
								  and rm.referencematerialid=vrm.referencematerialid
								  and rm.includeinreport=1
								  )
							and selected=1",$validationReport['EID'],$validationReport['EID']));
		?>
	    <tr>
	      <td><?php echo $validationReport['NAME']?><?php if($locked!=true && $validationReport['LOCKED']!=1) { ?> <a href="addUpdateValidationSet.php?EID=<?php echo  $validationReport['EID']?>">Edit
	      </a><?php } ?></td>
			<?php if($locked!=true) { ?>
			
	      <td><div align="center"><a href="viewReferenceMaterials.php?EID=<?php echo $validationReport['EID']?>">View</a> </div></td>
			<?php } ?>
	      <td><div align="center"><a href="../reports/MCERTS<?php echo $validationReport['EID'] ?>.htm">View</a>
                  <?php if($locked!=true && $validationReport['LOCKED']!=1) { ?>
                 	<br>
                  <a href="McertsLevelSummary.php?EID=<?php echo $validationReport['EID'] ?>">Create</a>
                  <?php } ?>
          </div></td>
	      <td><div align="center">
              <?php if($locked!=true && $validationReport['LOCKED']!=1) { ?>
              <a href="CalculateLevelSummary.php?EID=<?php echo $validationReport['EID'] ?>">Create</a> <br>
              <a href="../reports/<?php echo $validationReport['EID'] ?>.htm">View</a><br>
            <a href="CalculateLevelSummary.php?EID=<?php echo $validationReport['EID'] ?>&locked=true&final=true">Create final</a><br>
            <a href="../reports/<?php echo $validationReport['EID'] ?>final.htm">View final</a>
            <?php } else { ?>
            <a href="../reports/<?php echo $validationReport['EID'] ?>final.htm">View</a><a href="../reports/<?php echo $validationReport['EID'] ?>final.htm"></a><a href="../reports/<?php echo $validationReport['EID'] ?>final.htm">
            <?php } ?>
          </a></div></td>
			<td>
				<div align="center">
				<a href="LOD_summary.php?EID=<?php echo $validationReport['EID'] ?>">View</a>
			</td>
	      <td><?php echo getStringDate($startDate) ?> to <?php echo getStringDate($endDate) ?></td>	      
	      <td><div align="center">
	        <?php if($validationReport['VALIDATIONFORM']!="") {?>
	        <a href="../reviews/<?php echo $validationReport['VALIDATIONFORM']?>">View</a>
			<br>
			<?php if($locked!=true) { ?>
            </a><a href="deleteDocument.php?type=vf&EID=<?=$validationReport['EID']?>"> [remove]</a>
            <br>
            <?php } ?>
			<?php } ?>
            <?php if($locked!=true) { ?>
	        <a href="uploadDocument.php?type=vf&EID=<?=$validationReport['EID']?>">Add
			<br>
	        <?php } ?>  
	        </a><a href="../reviews/<?php echo $validationReport['REVIEW']?>">
            
	      </div></td>
	      <td><div align="center">
              <p>
                <?php if($validationReport['REVIEW']!="") {?>
                <a href="../reviews/<?php echo $validationReport['REVIEW']?>">View</a><br>
                <?php if($locked!=true) { ?>				  
                <a href="deleteDocument.php?type=rd&EID=<?=$validationReport['EID']?>">[remove]</a>
				<br>
                <?php } ?>                <?php } ?>
                <?php if($locked!=true) { ?>
                  <a href="uploadDocument.php?type=rd&EID=<?=$validationReport['EID']?>">Add</a>
				  <br>
				  <?php } ?>
                  
            </p>
              </div></td>
	      <?php if($locked!=true && $validationReport['LOCKED']!=1) { ?> <?php } ?>
			<?php } ?>

		</div>
      </tr>
	  <?php 
          $rowcount+=1;
          } //end for 
	}  //end if?>
</table>
</div>
</body>
</html>

  
