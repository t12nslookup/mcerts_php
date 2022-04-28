<?php 
	
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('../utility/mathematics.php');
//$db->debug=true;

if(isset($_POST['Submitted'])) {
	$counter=intval($_POST['Counter']);
	for ($x=0;$x<$counter;$x++){
		$analyteid= $_POST['select'.$x];
		if(isset($_POST['selected'.$x]) && $analyteid!=null && $analyteid!="") {
			$summary_bias = $_POST['bias'.$x];
			$summary_precision = $_POST['precision'.$x];
			
			
			$query_update = sprintf("update analytes set biaslimit=%s, precisionlimit=%s where analyteid=%s",$summary_bias,$summary_precision,$analyteid);
			$db->Execute($query_update);			
		}
	}
	print_r("Bias and Precision Limits have been updated.<p>");
	print_r("<a href='index.php'>Return to MCERTS Validations</a>");
	exit;	
} else {

	$sample_id=$_GET['sampleid'];
	$eid = $_GET['eid'];
	$qc_sampleid = $_GET['qc_sampleid'];
	
	
	$query_compounds = sprintf("SELECT * FROM compounds where referencematerialid=%s",$sample_id);
	$compounds= $db->GetAll($query_compounds);
	
	$query_analytes = sprintf("select * from analytes where sampleid=%s",$qc_sampleid);
	$qc_analytes = $db->GetAll($query_analytes);
	
	//get MCERTS details
	
	$query_mcerts = sprintf("select rm.referencematerial,vr.equipmentid,vr.name
							from reference_materials rm,VALIDATION_REPORTS vr,validation_reference_materials vrm 
							where 
							rm.referencematerialid=%s
							and vrm.referencematerialid=%s
							and vr.eid=vrm.eid",$sample_id,$sample_id);
	$mcerts = $db->GetAll($query_mcerts);						
	
	//get QC Details
	
	$query_qc=sprintf("SELECT samples.name \"SAMPLE\",machines.name \"MACHINE\"
			FROM samples,
			  machines
			WHERE samples.sampleid = %s
			 AND samples.eid = %s
			 AND machines.eid = samples.eid",$qc_sampleid,$eid);
	$qc = $db->GetAll($query_qc);

}

?>

<html>
<head>
<meta http-equiv="Content-Language" content="en" />
<meta name="GENERATOR" content="PHPEclipse 1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Copy MCERTS Bias + Precision to QC Charts</title>
</head>
<body>

<table>
<tr>
<td colspan=2><strong>Copying from MCERTS Data</strong></td>
</tr>
<tr>
<td>Equipment ID : </td>
<td><?=$mcerts[0]['EQUIPMENTID']?></td>
</tr>
<tr>
<td>Validation Report : </td>
<td><?=$mcerts[0]['NAME']?></td>
</tr>
<tr>
<td>Reference Material : </td>
<td><?=$mcerts[0]['REFERENCEMATERIAL']?></td>
</tr>
</table>
<p>
<table>
<tr>
<td colspan=2><strong>.. into QC Charting Data</strong></td>
</tr>
<tr>
<td>Equipment ID : </td>
<td><?=$eid?></td>
</tr>
<tr>
<td>Instrument : </td>
<td><?=$qc[0]['MACHINE']?></td>
</tr>
<tr>
<td>QC Standard : </td>
<td><?=$qc[0]['SAMPLE']?></td>
</tr>
</table>
<p>
  <form action="" method="post" name="form">  
  <input name="Submitted" type="hidden" value="true">
  <input name="Counter" type="hidden" value="<?=count($compounds)?>">
  <input type="submit" name="name" value="Update bias/precision breach limits for QC compounds"/>
  <p>
	<table>
	<tr>
		<th>&nbsp;</th>
		<th>Mcerts Compound</th>
		<th>Bias</th>
		<th>Precision</th>
		<th>QC Compound</th>
	</tr>	
	<? for($i=0;$i<count($compounds);$i++) { ?>
	<tr>
		<td>
  		<input type="checkbox" name="selected<?=$i?>" checked="true"/>
		</td>
  
		<td><?=$compounds[$i]['COMPOUND']?></td>
		<td>		
  		<input type="text" name="bias<?=$i?>" value="<?=sigfigs($compounds[$i]['SUMMARY_BIAS'],3,3)?>" size="10"/>  
  		</td>
		<td>
		<input type="text" name="precision<?=$i?>" value="<?=sigfigs($compounds[$i]['SUMMARY_PRECISION'],3,3)?>" size="10"/>
		</td>			
		<td>
		<select name="select<?=$i?>" id="<?=$i?>">
			<option value="">
		<? foreach($qc_analytes as $analyte) { ?>
			<option value="<?=$analyte['ANALYTEID']?>" <? if(str_replace(" ","",$compounds[$i]['COMPOUND'])==str_replace(" ","",$analyte['NAME'])) {echo "selected";}?>>
			<?=$analyte['NAME']?>
			</option>
		<? } ?>
		</select>
		</td>
	</tr>	
	<? } ?>
	</table>
	
  
  
  </form>

</body>
</html>
  