<?php
include_once('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');

$type = $_GET['type'];
if ($type == "vf") {
	$header = "Upload Validation Form";
	$folder = "reviews";
} else if ($type == "rd") {
	$header = "Upload Review Document";
	$folder = "reviews";
} else if ($type == "crm") {
	$header = "Upload CRM Certificate";
	$folder = "certificates";
} else exit;

//check if form has been submitted
if (isset($_POST['submit']) && $_POST['submit'] == "true") {
	$uploadTo = "../" . $folder . "/" . $_FILES['userfile']['name'];
	$uploadFrom = $_FILES['userfile']['tmp_name'];
	//upload file
	if (uploadFile($uploadFrom, $uploadTo)) {
		if ($type == "vf") {
			$sql = sprintf(
				"update validation_reports set validationform='%s' where EID=%s",
				$_FILES['userfile']['name'],
				$_POST['EID']
			);
			$header = "index.php";
		} else if ($type == "rd") {
			$sql = sprintf(
				"update validation_reports set review='%s' where EID=%s",
				$_FILES['userfile']['name'],
				$_POST['EID']
			);
			$header = "index.php";
		} else if ($type == "crm") {
			$sql = sprintf(
				"update reference_materials set certificatefilename='%s' where referencematerialid=%s",
				$_FILES['userfile']['name'],
				$_GET['ReferenceMaterialID']
			);
			$header = "viewReferenceMaterials.php?EID=" . $_POST['EID'];
		} else exit;
		$db->Execute($sql);
		header("Location:" . $header);
	}
	echo $header;
}



function uploadFile($uploadSource, $uploadDestination) //uploads file to $uploadDir
{
	if (move_uploaded_file($uploadSource, $uploadDestination))
		return true;
	else {
		print "File failed to Upload!  Here's some debugging info:\n";
		print_r($_FILES);
		return false;
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title><?= $header ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link href="../css/stylesheet.css" rel="stylesheet" type="text/css">
</head>

<body>
	<div class="box">
		<h1><?= $header ?></h1>
		<form enctype="multipart/form-data" action="#" method="post">
			<h4><input type="hidden" name="MAX_FILE_SIZE" value="10000000">
				<input name="submit" type="hidden" value="true">
				<input name="userfile" type="file" size="60">
				<input type="submit" value="Upload File">
			</h4>
			<input type="hidden" name="EID" value="<?php echo $_GET['EID'] ?>" />
		</form>
	</div>
</body>

</html>