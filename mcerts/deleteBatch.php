<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
$db->debug = false;

if (isset($_GET['BatchID'])) {
	$deleteSQL = sprintf("DELETE FROM batches WHERE BatchID=%s", $_GET['BatchID']);

	$db->Execute($deleteSQL);

	header(sprintf("Location: %s", $_SERVER['HTTP_REFERER']));
}
