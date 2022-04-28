<?php
//$hostname="primary11";
//$database="primarydb";
$database="LIMSASM";
$username="saldata";
$password="oraclev1";

PutEnv("ORACLE_SID=oracle10");
PutEnv("ORACLE_HOME=/export/home/oracle10g/oracle/product/10.2.0/db_1");
PutEnv("TNS_ADMIN=/export/home/oracle10g/oracle/product/10.2.0/db_1/network/admin");
PutEnv("TWO_TASK=LIMSASM");
PutEnv("ORA_NLS33=/export/home/oracle10g/oracle/product/10.2.0/db_1/nls/data");
PutEnv("NLS_DATE_FORMAT='RRRR-MM-DD HH24:MI:SS'");
PutEnv("NLS_TIMESTAMP_FORMAT='RRRR-MM-DD HH24:MI:SS'");

//
//$conn = OCILogon("saldata","oraclev1", "//x4140-1:1521/limsasm");
//
//if (!$conn) { echo "Error connecting"; die; }
//
//query($conn, "select * from nls_session_parameters where parameter='NLS_DATE_FORMAT'");
//query($conn, "select sysdate from dual");
//
//alterdate($conn);
//
//query($conn, "select * from nls_session_parameters where parameter='NLS_DATE_FORMAT'");
//query($conn, "select sysdate from dual");
//
//exit;
//
//function query($conn, $query)
//{
//$stid = OCIParse($conn, $query);
//if (!$stid) { echo "Error parsing"; die; }
//
//$r = OCIExecute($stid, OCI_DEFAULT);
//if (!$r) { echo "Error executing"; die; }
//
//print '<table border="1">';
//while ($succ = OCIFetchInto($stid, $row, OCI_RETURN_NULLS)) {
//print '<tr>';
//foreach ($row as $item) {
//print '<td>'.($item?htmlentities($item):' ').'</td>';
//}
//print '</tr>';
//}
//print '</table>';
//}
//
//function alterdate($conn)
//{
//$cmd = "alter session set nls_date_format = 'YYYY-MON-DD HH:MI'";
//$stid = OCIParse($conn, $cmd);
//if (!$stid) { echo "Error parsing"; die; }
//
//$r = ociexecute($stid, OCI_DEFAULT);
//if (!$r) { echo "Error executing"; die; }
//
//}
//
//$stmt = OCIParse($conn,"SELECT * FROM batches WHERE ReferenceMaterialID = 4139 and selected=1 order by replicate1");
//$ok = OCIExecute($stmt);
//while (OCIFetchInto($stmt,$arr)) {
//	print_r($arr[2]);
//	echo "<hr>";
//}




//ADODB
$currentPage = $_SERVER["PHP_SELF"];
$db = NewADOConnection("oci8"); # eg. 'mysql' or 'oci8'
//$db->debug = true;
$db->NLS_DATE_FORMAT = 'RRRR-MM-DD HH24:MI:SS';

//$db->debug = true;
$db->Connect($hostname, $username, $password, $database);
//
//
//$db->Execute("ALTER SESSION SET NLS_DATE_FORMAT = 'RRRR-MM-DD HH24:MI:SS'");
//$db->Execute("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'RRRR-MM-DD HH24:MI:SS'");
//
//echo "Hello:".$db->GetOne("SELECT VALUE FROM NLS_SESSION_PARAMETERS WHERE PARAMETER='NLS_DATE_FORMAT'");
//
//echo "Date:".$db->GetOne("SELECT sysdate FROM dual");
//
//
//$rs = $db->Execute("SELECT * FROM batches WHERE ReferenceMaterialID = 4139 and selected=1 order by replicate1 ");
//while ($arr = $rs->FetchRow()) {
//    print_r($arr);
//	echo "<hr>";
//}


//$db->Connect(false, $username, $password); //oracle

function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

function insertIntoDatabase($insertSQL) //loops through array $insertSQL and executes each one
{
	global $db;
	
	$db->StartTrans();
	foreach($insertSQL as $SQL)
	{			
		$db->Execute($SQL);	
	}
	$db->CompleteTrans(true);
	//}
}
?>
