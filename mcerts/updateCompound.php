<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
$db->debug = false;

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
	$editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

$updateGoTo = "compoundsInSample.php";
if (isset($_SERVER['QUERY_STRING'])) {
	$updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
	$updateGoTo .= $_SERVER['QUERY_STRING'];
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
	$updateSQL = sprintf(
		"UPDATE compounds SET Compound=%s, TargetConcentration=%s, TargetStandardDeviation=%s, TargetBias=%s, IncludeInReport=%s WHERE CompoundID=%s",
		GetSQLValueString($_POST['Compound'], "text"),
		GetSQLValueString($_POST['TargetConcentration'], "double"),
		GetSQLValueString($_POST['TargetStandardDeviation'], "double"),
		GetSQLValueString($_POST['TargetBias'], "double"),
		GetSQLValueString(isset($_POST['IncludeInReport']) ? "1" : "0", "int"),
		GetSQLValueString($_POST['CompoundID'], "int")
	);
	$db->Execute($updateSQL);
	header(sprintf("Location: %s", $_POST['previous']));
}

$colname_Compound = "1";
if (isset($_GET['CompoundID'])) {
	$colname_Compound = (get_magic_quotes_gpc()) ? $_GET['CompoundID'] : addslashes($_GET['CompoundID']);
}

$query_Compound = sprintf("SELECT * FROM compounds WHERE CompoundID = %s", $colname_Compound);
$row_Compound = $db->GetRow($query_Compound);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title>Untitled Document</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<style type="text/css">
		@import url(../css/stylesheet.css);
	</style>
</head>

<body>
	<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">[return to previous page]</a>
	<h1>Edit Compound Details </h1>
	<form method="post" name="form1" action="<?php echo $editFormAction; ?>">
		<table border="0" cellpadding="0" cellspacing="0" class="InputForm">
			<tr valign="baseline">
				<td nowrap align="right">Compound:</td>
				<td><input type="hidden" name="Compound" value="<?php echo $row_Compound['COMPOUND']; ?>" size="32"><?= $row_Compound['COMPOUND'] ?></td>
			</tr>
			<tr valign="baseline">
				<td nowrap align="right">TargetConcentration:</td>
				<td><input type="text" name="TargetConcentration" value="<?php echo $row_Compound['TARGETCONCENTRATION']; ?>" size="32"></td>
			</tr>
			<tr valign="baseline">
				<td nowrap align="right">TargetStandardDeviation:</td>
				<td><input type="text" name="TargetStandardDeviation" value="<?php echo $row_Compound['TARGETSTANDARDDEVIATION']; ?>" size="32"></td>
			</tr>
			<tr valign="baseline">
				<td nowrap align="right">TargetBias:</td>
				<td><input type="text" name="TargetBias" value="<?php echo $row_Compound['TARGETBIAS']; ?>" size="32"></td>
			</tr>
			<tr valign="baseline">
				<td nowrap align="right">Include In Report:</td>
				<td><input type="checkbox" name="IncludeInReport" value="1" <?php if (!(strcmp($row_Compound['INCLUDEINREPORT'], "1"))) {
																																			echo "checked";
																																		} ?>></td>
			</tr>
			<tr valign="baseline">
				<td nowrap align="right">&nbsp;</td>
				<td><input type="submit" value="Update Compound"></td>
			</tr>
		</table>
		<p><input type="hidden" name="MM_update" value="form1">
			<input type="hidden" name="CompoundID" value="<?php echo $row_Compound['COMPOUNDID']; ?>">
			<input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER'] ?>">
		</p>
	</form>
</body>

</html>