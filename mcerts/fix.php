<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('datefunctions.php');
require('../utility/stats.php');
	$db->debug=true;
$sql="select * from batches where batchid is null";

$batches=$db->GetAll($sql);
$counter=200;


foreach($batches as $batch)
{
	$sql2=sprintf("update batches 
			set batchid=%s 
			where referencematerialid=%s 
			and to_char(replicate1,'YYYY-MM-DD HH24:MI:SS')='%s'
			and to_char(replicate2,'YYYY-MM-DD HH24:MI:SS')='%s'"
			,$counter,$batch['REFERENCEMATERIALID'],$batch['REPLICATE1'],$batch['REPLICATE2']);
	$counter++;
	$db->Execute($sql2);
}


?>