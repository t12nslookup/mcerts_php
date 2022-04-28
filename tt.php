<?php


PutEnv("ORACLE_SID=oracle10");
PutEnv("ORACLE_HOME=/export/home/oracle10g/oracle/product/10.2.0/db_1");
PutEnv("TNS_ADMIN=/var/opt/oracle");

$conn = OCILogon("SALDATA","ORACLEV1", "//10.190.0.41:1521/limsasm"); 

$stmt = OCIParse($conn,"select distinct equipmentid from validation_reports"); 
$ok = OCIExecute($stmt);
while (OCIFetchInto($stmt,$arr)) {
	print_r($arr);
	echo "<hr>";	
} 


?>
