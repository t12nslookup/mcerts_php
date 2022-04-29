<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
//$db->debug = true;

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form") && isset($_POST["EID"])) {
  $EID = (get_magic_quotes_gpc()) ? $_POST['EID'] : addslashes($_POST['EID']);
  $sqlEquipmentID = "select equipmentid from validation_reports where EID=" . $EID;
  $EquipmentID = $db->GetOne($sqlEquipmentID);
  $search = $_POST['SEARCH'];


  $query_SampleList = "
      SELECT distinct results.Sample
      FROM results
      where results.Machine='" . $EquipmentID . "' and upper(results.sample) like upper('%" . $search . "%') order by sample";

  $SampleList = $db->GetAll($query_SampleList);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title></title>
</head>

<body>


  <form method="post" action="#" name="form">
    Search for an existing reference material : <input type="text" name="SEARCH" />


    <input type="submit" name="butt" id="butt" value="Search" />
    <input type="hidden" name="MM_insert" value="form">
    <input type="hidden" name="EID" value="<?php echo $_GET['EID'] ?>">

    <br />

    <br />


    If reference material results have not been upload into LIMS then click
    <a href="addReferenceMaterial.php?EID=<?php echo $_GET['EID'] ?>">here</a> to add the name manually.
  </form>

  <?php
  if (isset($SampleList)) {
    foreach ($SampleList as $sample) {
  ?>

      <a href="addReferenceMaterial.php?EID=<?php echo $_GET["EID"]; ?>&SAMPLE=<?php echo $sample['SAMPLE']; ?>"><?php echo $sample['SAMPLE']; ?></a>
      <br />

  <?php
    }
  }
  ?>

</body>

</html>