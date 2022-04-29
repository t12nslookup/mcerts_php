<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
$db->debug = false;

$query_deleteRM = "update reference_materials set IncludeInReport=0 where ReferenceMaterialID=" . $_GET['ReferenceMaterialID'];
$db->Execute($query_deleteRM);

$deleteGoTo = "index.php";
//header(sprintf("Location: %s", $deleteGoTo));
header(sprintf("Location: %s", $_SERVER['HTTP_REFERER']));
