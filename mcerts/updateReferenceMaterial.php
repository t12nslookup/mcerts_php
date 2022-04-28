<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
$db->debug=false;

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
	$updateSQL = sprintf("UPDATE reference_materials SET ReferenceMaterial=%s, TrivialName=%s, DEFAULTTARGETSTANDARDDEVIATION=%s, DefaultTargetBias=%s, IncludeInReport=%s WHERE ReferenceMaterialID=%s",
					   GetSQLValueString($_POST['ReferenceMaterial'], "text"),
					   GetSQLValueString($_POST['TrivialName'], "text"),
					   GetSQLValueString($_POST['DefaultTargetStandardDeviation'], "double"),
					   GetSQLValueString($_POST['DefaultTargetBias'], "double"),
					   GetSQLValueString(isset($_POST['IncludeInReport']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($_POST['ReferenceMaterialID'], "int"));
	$db->Execute($updateSQL);
	
	
	/*if (isset($_SERVER['QUERY_STRING'])) {
		$updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
		$updateGoTo .= $_SERVER['QUERY_STRING'];
	}*/
	//header(sprintf("Location: %s", $updateGoTo));
	header(sprintf("Location: %s",$_POST['previous']));
}

$colname_ReferenceMaterial = "1";
if (isset($_GET['ReferenceMaterialID'])) {
  $ReferenceMaterialID = (get_magic_quotes_gpc()) ? $_GET['ReferenceMaterialID'] : addslashes($_GET['ReferenceMaterialID']);
}

$query_ReferenceMaterial = sprintf("SELECT * FROM reference_materials WHERE ReferenceMaterialID = %s", $ReferenceMaterialID);
$row_ReferenceMaterial = $db->GetRow($query_ReferenceMaterial);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">@import url(../css/stylesheet.css);</style>
</head>
<body>
<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">[return to previous page]</a>
	<h1>Edit Reference Material </h1>
<form method="post" name="form1" action="<?php echo $editFormAction; ?>">
	  <table border="0" cellpadding="0" cellspacing="0">
		<tr valign="baseline">
		  <td nowrap align="right">ReferenceMaterial:</td>
		  <td><input type="hidden" name="ReferenceMaterial" value="<?php echo $row_ReferenceMaterial['REFERENCEMATERIAL']; ?>" size="32"><?php echo $row_ReferenceMaterial['REFERENCEMATERIAL']; ?></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">TrivialName:</td>
		  <td><input type="text" name="TrivialName" value="<?php echo $row_ReferenceMaterial['TRIVIALNAME']; ?>" size="32"></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">Default Target Standard Deviation :</td>
		  <td><input name="DefaultTargetStandardDeviation" type="text" id="DefaultTargetStandardDeviation" value="<?php echo $row_ReferenceMaterial['DEFAULTTARGETSTANDARDDEVIATION']; ?>" size="32"></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">DefaultTargetBias:</td>
		  <td><input type="text" name="DefaultTargetBias" value="<?php echo $row_ReferenceMaterial['DEFAULTTARGETBIAS']; ?>" size="32"></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">IncludeInReport:</td>
		  <td><input type="checkbox" name="IncludeInReport" value="1"  <?php if (!(strcmp($row_ReferenceMaterial['INCLUDEINREPORT'],"1"))) {echo "checked";} ?>></td>
		</tr>
		<tr valign="baseline">
		  <td nowrap align="right">&nbsp;</td>
		  <td><input type="submit" value="Update Reference Material"></td>
		</tr>
	  </table>
	  <input type="hidden" name="MM_update" value="form1">
	  <input type="hidden" name="ReferenceMaterialID" value="<?php echo $row_ReferenceMaterial['REFERENCEMATERIALID']; ?>">
	  <input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER']?>">
	</form>
</body>
</html>