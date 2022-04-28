<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require('datefunctions.php');
//$db->debug = true;

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

$colname_ValidationReport = "99999999999";
if (isset($_GET['EID'])) {
  $colname_ValidationReport = (get_magic_quotes_gpc()) ? $_GET['EID'] : addslashes($_GET['EID']);
  $update=true;
} else {$update=false;}

if (isset($_POST["submitted"])) {
	$approvalDate=convertToDBDate($_POST['Aday'],$_POST['Amonth'],$_POST['Ayear']);
}

if ($update==false && (isset($_POST["submitted"]))) {
	if($_POST['EID']==""){
		$useEID=$_POST['EID2']; //user has manually entered EID
		$manual=1;
	} 
	else {
		$useEID=$_POST['EID']; //user has selected EID from drop down list
		$manual=0;
	}
		
	$insertSQL = sprintf("INSERT INTO validation_reports (EquipmentID, DateOfApproval, ApprovedBy, Expired,Name,manuallyadded) VALUES (%s,%s, %s, %s, %s,%s)",
					   GetSQLValueString($useEID, "text"),
					   GetSQLValueString($approvalDate, "date"),					   
					   GetSQLValueString($_POST['ApprovedBy'], "text"),
					   GetSQLValueString(isset($_POST['Expired']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($_POST['Name'], "text"),
					   $manual);
	
	$db->Execute($insertSQL);

	createEmptySummary($_POST['EID']);
	header(sprintf("Location: %s",$_POST['previous']));
        exit;
}

function createEmptySummary($EID)
{
//	$content="<html><body><h1>Summary has not been calculated!</h1></body></html>";
//	$myFile="../reports/".$EID.".htm";
//	$newFile = @fopen($myFile, 'w+') or die("can't open file");
//	fwrite($newFile, $content);
//	fclose($newFile);
}

if ($update==true && (isset($_POST["submitted"]))) {
	$updateSQL = sprintf("UPDATE validation_reports SET DateOfApproval=%s, ApprovedBy=%s, Expired=%s, Name=%s, Locked=%s WHERE EID=%s",
					   GetSQLValueString($approvalDate, "date"),
					   GetSQLValueString($_POST['ApprovedBy'], "text"),
					   GetSQLValueString(isset($_POST['Expired']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($_POST['Name'], "text"),
					   GetSQLValueString(isset($_POST['Locked']) ? "true" : "", "defined","1","0"),
					   GetSQLValueString($_GET['EID'], "text")
					   );
	
	$db->Execute($updateSQL);

	header(sprintf("Location: %s",$_POST['previous']));
} else {

$query_ValidationReport = sprintf("SELECT * FROM validation_reports WHERE EID = '%s'", $colname_ValidationReport);
$row_ValidationReport= $db->GetRow($query_ValidationReport);

//split dates into arrays ready for the list boxes
if($update==true) 
{
	$approvalDate=createDateArray($row_ValidationReport['DATEOFAPPROVAL']);
} else { $approvalDate=$startDate=$endDate=$months=array("","","");}
$months=array("Jan","Feb","Mar","Apr","May","June","July","Aug","Sep","Oct","Nov","Dec");



//get unique machine IDs from results table
$query_Machines = "SELECT DISTINCT Machines.eid
  FROM results
  inner join machines on machines.eid = results.machine
  where acqon > (sysdate - 20)";
$machines= $db->GetAll($query_Machines);
$machineCount = count($machines);
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Add/Update Validation Set</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">@import url(../css/stylesheet.css);</style>
</head>
<body>
<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">[return to previous page]</a>
<h1>Add/Update Validation Set </h1>
<?php if($machineCount==0 && $update==false) { ?>
<h3>There are no more machines to add</h3>
<?php exit; } //end if ?>
<form action="<?php echo $editFormAction; ?>" method="POST" name="addValidationSetForm" id="addValidationSetForm">
  <p>
    <?php if($update==false) { ?>
  If you have allready uploaded the data files into the system then choose the Equipment ID from the following drop down list: </p>
  <p>Equipment ID : 
    <select name="EID" id="EID">
      <option value="" selected></option>
      <?php	foreach($machines as $row_Machines) {  	?>
      <option value="<?php echo $row_Machines['MACHINE']?>" <?php if(isset($_GET['EID'])) if (!(strcmp($row_Machines['MACHINE'], $_GET['EID']))) {echo "SELECTED";} ?>><?php echo $row_Machines['MACHINE']?></option>
      <?php	} //foreach ?>
    </select>
</p>
  <p>If you are going to manually enter the data then enter the Equipment id in this text box :</p>
  <p>Equipment ID : 
    <input name="EID2" type="text" id="EID2" size="10"> 
  </p>
  <?php } ?>
  <table border="0" cellpadding="0" cellspacing="0" class="InputForm">
    <?php if($update==true) { ?>
	<tr>
      <td width="143">Equipment ID:</td>
      <td width="244">
		<?php
			$equipmentId=$db->GetOne("select equipmentid from validation_reports where EID=".$_GET['EID']);
			echo $equipmentId; 
			?>
			<input name="EID" id="EID" type="hidden" value="<?php echo $_GET['EID'] ?>">
	  </td>
    </tr>
	<?php } ?>
    <tr>
      <td>Name of Validation : </td>
      <td><input name="Name" type="text" id="Name" value="<?php if(isset($row_ValidationReport['NAME'])) {echo $row_ValidationReport['NAME']; }?>" size="40"></td>
    </tr>
    <tr>
      <td>Date of Approval : </td>
      <td><select name="Aday" id="Aday">
		  <?php for($counter=1;$counter<32;$counter++) { ?>
		  <option value=<?php echo "\"".$counter."\""; if ($counter==$approvalDate[0]) {echo " selected";} ?>><?php echo $counter ?></option>
		  <?php } ?>
        </select>
        <select name="Amonth" id="Amonth">
		  <?php for($counter=1;$counter<13;$counter++) { ?>
		  <option value=<?php echo "\"".$counter."\""; if ($counter==$approvalDate[1]) {echo " selected";} ?>><?php echo $months[$counter-1] ?></option>
		  <?php } ?>
        </select>
        <select name="Ayear" id="Ayear">
		  <?php for($counter=2000;$counter<2030;$counter++) { ?>
		  <option value=<?php echo "\"".$counter."\""; if ($counter==$approvalDate[2]) {echo " selected";} ?>><?php echo $counter ?></option>
		  <?php } ?>
        </select>
      </td>
    </tr>
    <tr>
      <td>Approved By :</td>
      <td><input name="ApprovedBy" type="text" id="ApprovedBy" value="<?php if(isset($row_ValidationReport['APPROVEDBY'])) {echo $row_ValidationReport['APPROVEDBY']; }?>"></td>
    </tr>
    <tr>
      <td>Expired : </td>
      <td><input <?php if($update==true) {if (!(strcmp($row_ValidationReport['EXPIRED'],1))) {echo "checked";}} ?> name="Expired" type="checkbox" id="Expired" value="checkbox"></td>
    </tr>
    <tr>
      <td>Locked : </td>
      <td><input <?php if($update==true) {if (!(strcmp($row_ValidationReport['LOCKED'],1))) {echo "checked";}} ?> name="Locked" type="checkbox" id="Locked" value="true"></td>
    </tr>
  </table>
  <p>
    <input type="submit" name="Submit" value="Submit">
    <input type="hidden" name="submitted" value="addValidationSetForm">
	<input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER']?>">
</p>
</form>
</body>
</html>
