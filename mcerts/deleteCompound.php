<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
$db->debug = false;

if ((isset($_GET['CompoundID'])) && ($_GET['CompoundID'] != "")) {
	$deleteSQL = sprintf(
		"DELETE FROM compounds WHERE CompoundID=%s",
		GetSQLValueString($_GET['CompoundID'], "int")
	);

	$db->Execute($deleteSQL);


	$deleteGoTo = "compoundsInSample.php?ReferenceMaterialID=" . $_GET['ReferenceMaterialID'];
	if (isset($_SERVER['QUERY_STRING'])) {
		$deleteGoTo .= (strpos($deleteGoTo, '?')) ? "&" : "?";
		$deleteGoTo .= $_SERVER['QUERY_STRING'];
	}
	header(sprintf("Location: %s", $deleteGoTo));
}
