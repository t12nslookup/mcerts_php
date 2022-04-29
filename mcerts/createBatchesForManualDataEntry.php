<?php
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('datefunctions.php');
//$db->debug=true;

//get all existing defined batches:
$query_ExistingBatches = sprintf("SELECT * FROM batches 
									WHERE ReferenceMaterialID = %s and selected=1 order by replicate1", $_GET['referencematerialid']);
$existingBatches = $db->GetAll($query_ExistingBatches);

//form is submitted
if (isset($_POST['Submit'])) {
  $refMatID = $_GET['referencematerialid'];

  $db->StartTrans(); //start transaction	

  //insert into batches and results table
  for ($i = 1; $i < 12; $i++) {
    $day = $_POST['firstday' . $i];
    $day = strlen($day) == 1 ? "0" . $day  : $day;

    $month = $_POST['firstmonth' . $i];
    $month = strlen($month) == 1 ? "0" . $month  : $month;

    $year = $_POST['firstyear' . $i];

    $hour = $_POST['firsthour' . $i];
    $hour = strlen($hour) == 1 ? "0" . $hour  : $hour;

    $minute = $_POST['firstminute' . $i];
    $minute = strlen($minute) == 1 ? "0" . $minute  : $minute;

    $replicate1 = $year . $month . $day . " " . $hour . ":" . $minute;

    $secondday = $_POST['secondday' . $i];
    $secondday = strlen($secondday) == 1 ? "0" . $secondday : $secondday;

    $secondmonth = $_POST['secondmonth' . $i];
    $secondmonth = strlen($secondmonth) == 1 ? "0" . $secondmonth  : $secondmonth;

    $secondyear = $_POST['secondyear' . $i];

    $secondhour = $_POST['secondhour' . $i];
    $secondhour = strlen($secondhour) == 1 ? "0" . $secondhour : $secondhour;

    $secondminute = $_POST['secondminute' . $i];
    $secondminute = strlen($secondminute) == 1 ? "0" . $secondminute : $secondminute;

    $replicate2 = $secondyear . $secondmonth . $secondday . " " . $secondhour . ":" . $secondminute;

    if ($year != "" && $secondyear != "") {
      $sqlDeleteBatches = sprintf(
        "delete from batches where referencematerialid=%s and
								replicate1=to_date('%s','yyyymmdd HH24:MI') and
								replicate2=to_date('%s','yyyymmdd HH24:MI')",
        $refMatID,
        $replicate1,
        $replicate2
      );
      $db->Execute($sqlDeleteBatches);

      $sqlBatches = sprintf(
        "insert into batches (referencematerialid,replicate1,replicate2,selected,multiplier)
								values(%s,to_date('%s','yyyymmdd HH24:MI'),to_date('%s','yyyymmdd HH24:MI'),1,1)",
        $refMatID,
        $replicate1,
        $replicate2
      );
      $db->Execute($sqlBatches);
    }
  }
  $db->CompleteTrans(true);
  $header = $_POST['previous'];
  header("Location:" . $header);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <title>Create Batches</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <style type="text/css">
    @import url(../css/stylesheet.css);
  </style>
</head>

<body>
  <div class="box">
    <a href="newindex.php">[return to previous page]</a>
    <h1>Create Batches for Reference Material </h1>
    <form name="form1" method="post" action="">
      <table width="600" border="0" cellspacing="0" cellpadding="0">
        <tr class="tableheader">
          <td colspan="2">Replicate 1 </td>
          <td colspan="2">Replicate 2 </td>
        </tr>
        <?php if (count($existingBatches) != 0) { ?>
          <tr>
            <td colspan="4" class="tableheader">Existing Batches </td>
          </tr>
        <?php } ?>
        <?php foreach ($existingBatches as $row_ExistingBatches) { ?>
          <tr>
            <td colspan="2">
              <div align="center"><?php echo getStringDateTime($row_ExistingBatches['REPLICATE1']); ?></div>
            </td>
            <td colspan="2">
              <div align="center"><?php echo getStringDateTime($row_ExistingBatches['REPLICATE2']); ?></div>
            </td>
          </tr>
        <?php } //end for 
        ?>
        <tr class="tableheader">
          <td width="19%">Date</td>
          <td width="13%">Time</td>
          <td width="20%">Date</td>
          <td width="14%">Time</td>
        </tr>
        <?php for ($x = 1; $x < 12; $x++) { ?>
          <tr>
            <td><select name="firstday<?php echo $x ?>" id="select15">
                <option value="" selected></option>
                <?php for ($i = 1; $i < 32; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
              <select name="firstmonth<?php echo $x ?>" id="select16">
                <option value="" selected></option>
                <?php for ($i = 1; $i < 13; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
              <select name="firstyear<?php echo $x ?>" id="select17">
                <option value="" selected></option>
                <?php for ($i = 2002; $i < 2020; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
            </td>
            <td>
              <select name="firsthour<?php echo $x ?>" id="select30">
                <option value="" selected></option>
                <?php for ($i = 0; $i < 25; $i++) { ?>
                  <option value="<?php echo $i ?>" <?php if ($i == 0) {
                                                      echo "selected";
                                                    } ?>> <?php echo $i ?> </option>
                <?php } ?>
              </select>:
              <select name="firstminute<?php echo $x ?>" id="select31">
                <?php for ($i = 0; $i < 61; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
            </td>
            <td> <select name="secondday<?php echo $x ?>" id="select15">
                <option value="" selected></option>
                <?php for ($i = 1; $i < 32; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
              <select name="secondmonth<?php echo $x ?>" id="select16">
                <option value="" selected></option>
                <?php for ($i = 1; $i < 13; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
              <select name="secondyear<?php echo $x ?>" id="select17">
                <option value="" selected></option>
                <?php for ($i = 2002; $i < 2020; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
            </td>
            <td>
              <select name="secondhour<?php echo $x ?>" id="select32">
                <option value="" selected></option>
                <?php for ($i = 0; $i < 25; $i++) { ?>
                  <option value="<?php echo $i ?>" <?php if ($i == 1) {
                                                      echo "selected";
                                                    } ?>> <?php echo $i ?> </option>
                <?php } ?>
              </select>
              :
              <select name="secondminute<?php echo $x ?>" id="select33">
                <?php for ($i = 0; $i < 61; $i++) { ?>
                  <option value="<?php echo $i ?>"> <?php echo $i ?> </option>
                <?php } ?>
              </select>
            </td>
          </tr>
        <?php } //end for 
        ?>
      </table>
      <p>
        <input type="submit" name="Submit" value="Add Batches">
        <input name="previous" type="hidden" value="<?php echo $_SERVER['HTTP_REFERER'] ?>">
      </p>
    </form>
  </div>
</body>

</html>