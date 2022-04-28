<?php
include_once('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');

$type = $_GET['type'];
if($type=="vf")
{
	$sql=sprintf("update validation_reports set validationform='' where EID=%s",
		$_GET['EID']);
	$header="index.php";
}
else if($type=="rd")
{
	$sql=sprintf("update validation_reports set review='' where EID=%s",
		$_GET['EID']);
	$header="index.php";
}
else if($type=="crm")
{
	$sql=sprintf("update reference_materials set certificatefilename='' where referencematerialid=%s",
		$_GET['ReferenceMaterialID']);
	$header="viewReferenceMaterials.php?EID=".$_GET['EID'];
} 
else exit;

$db->Execute($sql);

header("Location:".$header);
?>

