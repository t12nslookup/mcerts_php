<?php // @(#)show.php	1.8 08/05/05 
?>
<?php
include('adodb/adodb.inc.php');
require_once('Connections/mysql.php');
//$db->debug=true;

if (isset($_GET['Time'])) {
  $Time_NormalResults = (get_magic_quotes_gpc()) ? $_GET['Time'] : addslashes($_GET['Time']);
}

//get normal results array
$query_NormalResults = sprintf("SELECT * 
									FROM results 
									WHERE to_date(AcqOn,'yyyy-mm-dd HH24:MI:SS') = '%s' 
									AND (Flag != 'I' OR Flag IS NULL) 
									ORDER BY LineNo", $Time_NormalResults);
$NormalResults = $db->GetAll($query_NormalResults);
$row_NormalResults = $NormalResults[0];

//get internal standards array
if (isset($_GET['Time'])) {
  $Time_InternalStandards = (get_magic_quotes_gpc()) ? $_GET['Time'] : addslashes($_GET['Time']);
}

$query_InternalStandards = sprintf("SELECT * 
									FROM results 
									WHERE Flag LIKE 'I%%' 
									AND AcqOn='%s' 
									ORDER BY LineNo", $Time_InternalStandards);
$InternalStandards = $db->GetAll($query_InternalStandards);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <title>Quantitation Report</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <link href="css/stylesheet.css" rel="stylesheet" type="text/css">
</head>

<body>
  <div class="box">
    <h3 class="title"><a href="index.php">[upload report screen]</a> <a href="mcerts/newindex.php">[mcerts]</a> </h3>
    <h2>Report</h2>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td class="fieldName">Machine : </td>
        <td><?php echo $row_NormalResults['MACHINE']; ?></td>
      </tr>
      <tr>
        <td width="202" class="fieldName">Data Path : </td>
        <td width="562"><?php echo $row_NormalResults['DATAPATH']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Data File : </td>
        <td><?php echo $row_NormalResults['DATAFILE']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Acq On : </td>
        <td><?php echo $row_NormalResults['ACQON']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Operator : </td>
        <td><?php echo $row_NormalResults['OPERATOR']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Sample : </td>
        <td><?php echo $row_NormalResults['SAMPLE']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Misc : </td>
        <td><?php echo $row_NormalResults['MISC']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">ALS Vial : </td>
        <td><?php echo $row_NormalResults['ALSVIAL']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Sample Multiplier : </td>
        <td><?php echo $row_NormalResults['SAMPLEMULTIPLIER']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Quant Time : </td>
        <td><?php echo $row_NormalResults['QUANTTIME']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Quant Method : </td>
        <td><?php echo $row_NormalResults['QUANTMETHOD']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Quant Title : </td>
        <td><?php echo $row_NormalResults['QUANTTITLE']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Quant Update : </td>
        <td><?php echo $row_NormalResults['QUANTUPDATE']; ?></td>
      </tr>
      <tr>
        <td class="fieldName">Response Via : </td>
        <td><?php echo $row_NormalResults['RESPONSEVIA']; ?></td>
      </tr>
    </table>
    <br>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr class="tableheader">
        <td width="20">&nbsp;</td>
        <td width="170">Internal Standards </td>
        <td>
          <div align="center">TYPE</div>
        </td>
        <td>
          <div align="center">R.T.</div>
        </td>
        <td width="120">
          <div align="center">Observed Parameter</div>
        </td>
        <td>
          <div align="center">Response</div>
        </td>
        <td>
          <div align="center">Conc</div>
        </td>
        <td>
          <div align="center">Units</div>
        </td>
        <td>
          <div align="center">Dev (Min)</div>
        </td>
        <td>Flag</td>
      </tr>
      <?php foreach ($InternalStandards as $row_InternalStandards) { ?>
        <tr>
          <td width="20"><?php echo $row_InternalStandards['LINENO']; ?></td>
          <td width="170"><?php echo $row_InternalStandards['COMPOUND']; ?></td>
          <td><?php echo $row_InternalStandards['TYPE']; ?></td>
          <td><?php echo $row_InternalStandards['RT']; ?></td>
          <td width="120"><?php echo $row_InternalStandards['QION']; ?></td>
          <td><?php echo $row_InternalStandards['RESPONSE']; ?></td>
          <td><?php echo $row_InternalStandards['CONCENTRATION']; ?>
          </td>
          <td><?php if ($row_InternalStandards['TOP'] > "") {
                echo $row_InternalStandards['TOP']; ?>
              /<?php echo $row_InternalStandards['BOTTOM'];
              } ?>
          </td>
          <td><?php echo $row_InternalStandards['DEVIATION']; ?></td>
          <td><?php echo $row_InternalStandards['FLAG']; ?></td>
        </tr>
      <?php } ?>
      <tr class="tableheader">
        <td width="20">&nbsp;</td>
        <td width="170">Target Compounds</td>
        <td>
          <div align="center">TYPE</div>
        </td>
        <td>
          <div align="center">R.T.</div>
        </td>
        <td width="120">
          <div align="center">Observed Parameter</div>
        </td>
        <td>
          <div align="center">Response</div>
        </td>
        <td>
          <div align="center">Conc</div>
        </td>
        <td>
          <div align="center">Units</div>
        </td>
        <td>
          <div align="center">QValue</div>
        </td>
        <td>&nbsp;</td>
      </tr>
      <?php foreach ($NormalResults as $row_NormalResults) { ?>
        <tr>
          <td width="20"><?php echo $row_NormalResults['LINENO']; ?></td>
          <td width="170"><?php echo $row_NormalResults['COMPOUND']; ?></td>
          <td><?php echo $row_NormalResults['TYPE']; ?></td>
          <td><?php echo $row_NormalResults['RT']; ?></td>
          <td width="120"><?php echo $row_NormalResults['QION']; ?></td>
          <td><?php echo $row_NormalResults['RESPONSE']; ?></td>
          <td><?php echo $row_NormalResults['CONCENTRATION']; ?>
          </td>
          <td><?php if ($row_NormalResults['TOP'] > "") {
                echo $row_NormalResults['TOP']; ?>
              /<?php echo $row_NormalResults['BOTTOM'];
              } ?>
          </td>
          <td><?php echo $row_NormalResults['QVALUE']; ?></td>
          <td><?php echo $row_NormalResults['FLAG']; ?></td>
        </tr>
      <?php } ?>
    </table>
  </div>
</body>

</html>