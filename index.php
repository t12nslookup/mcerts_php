<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php // @(#)index.php  1.3 07/22/05 
?>
<?php
//if page is in simple mode then only upload box is shown
if (isset($_GET['simple']) && $_GET['simple'] == "true")
  $simple = 1;
else
  $simple = 0;

include('adodb/adodb.inc.php');
require_once('Connections/mysql.php');
$sqlPreviousReports = "select distinct sample, machine, acqon from results where
	   resultid > (select max(resultid) from results)-1000 order by acqon desc";
$previousReports = $db->GetAll($sqlPreviousReports);
?>
<html>

<head>
  <meta name="generator" content="HTML Tidy, see www.w3.org">
  <link href="css/stylesheet.css" rel="stylesheet" type="text/css">
  <title></title>
  <link rel="shortcut icon" href="/images/favicon.ico">
  <style type="text/css">
    <!--
    .style1 {
      color: #FF0000
    }
    -->
  </style>
</head>

<body>
  <?php if ($simple == 1) { ?>
    <form enctype="multipart/form-data" action="upload.php" method="post">
      <h4>
        Upload Instrument Data:
        <input name="userfile" type="file" size="40">
        <input type="submit" value="Upload File">
        <input name="QC" type="hidden" id="QC" value="true">
    </form>
    <?php if (isset($_GET['Message'])) {
      echo "<h3>" . $_GET['Message'] . "</h3>";
    } ?>
  <?php } else { ?>
    <div class="box">
      <p class="title"><a href="mcerts/newindex.php">[mcerts]</a></p>
      <?php if (isset($_GET['Message'])) {
        echo "<h3>" . $_GET['Message'] . "</h3>";
      } ?>
      <h1>Upload Report:</h1>
      <form enctype="multipart/form-data" action="upload.php" method="post">
        <h4>File
          Path :
          <input name="userfile" type="file" size="60">
          <input type="submit" value="Upload File">
        </h4>
      </form>
      <h3>Previously Uploaded Reports : </h3>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr class="tableheader">
          <td width="11%">Report</td>
          <td width="14%">Machine</td>
          <td width="29%">Sample</td>
          <td width="46%">Date Acquired</td>
        </tr>

        <?php foreach ($previousReports as $report) { ?>
          <tr>
            <td height="23">
              <div align="center"><a href="show.php?Time=<?php echo $report['ACQON'] ?>">View</a></div>
            </td>
            <td><?php echo $report['MACHINE'] ?></td>
            <td><?php echo $report['SAMPLE'] ?></td>
            <td><?php echo $report['ACQON'] ?></td>
          </tr>
        <?php }  ?>
      </table>
    </div>
  <?php } ?>
</body>

</html>