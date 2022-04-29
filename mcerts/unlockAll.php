<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');

//unlock all validation reports
$sql="update validation_reports set locked=0";
$db->Execute($sql);
header(sprintf("Location: %s","newindex.php"));
