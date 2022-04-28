<?php
include('../../adodb/adodb.inc.php');
require_once('../../Connections/mysql.php');

if(isset($_POST['questionSubmit']) && $_POST['questionSubmit']=="true")
{
	$sql=sprintf("insert into questions (question) values ('%s')",$_POST['question']);
	$db->Execute($sql);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../css/stylesheet.css" rel="stylesheet" type="text/css">
</head>

<body>
<a href="../newindex.php">[MCERTS main menu]</a>
<h1>MCERTS Instructions</h1>
<p>Before you add any data to the MCERTS system it is <strong>vitally important</strong> that the sample names that you use adhere to the guidelines as described in this document : </p>
<p><a href="mcerts%20sample%20names.htm">Correct format of mcerts sample names </a></p>
<p>There are 2 ways to enter data into the MCERTS system :</p>
<h3>1. <strong>Manually add the data</strong></h3>
<p>This involves manually entering the 11x2 matrix values for each analyte. </p>
<p>For instructions on how to manually add data, click here :</p>
<p> <a href="manuallyAddingBatches.htm">How to manually add batches to the MCERTS system</a></p>
<p>After you have manually added data, the validation name you will have chosen will be visible on the main MCERTS screen.</p>
<p>After adding data you will  want to know : </p>
<p><a href="viewValidationSummaryReport.htm">How to view validation summary report (pass/fail report) </a></p>
<p>and</p>
<p><a href="viewStatisticalAnalysisReport.htm">How to view a statistical analysis report</a></p>
<p>and </p>
<p><a href="selectBatches.htm">How to select / alter batches</a></p>
<h3>2.<strong> Upload the data from data files produced by machines</strong></h3>
<p>The machines produce their own data file reports. </p>
<p>These data file reports need to be uploaded into the MCERTS system.</p>
<p>The first step is to Export the data from the machines : </p>
<p> <a href="ciros%20icp%20software.htm">Using the CIROS ICP Software</a></p>
<p><a href="icp%20expert%20software.htm">Using the Vista Pro and Vista MPX software (ICP Expert Software, versions 4.0 and 4.1.0)</a></p>
<p><a href="chemstation%20software.htm">Using the Chemstation Software (inorganics)</a></p>
<p>After you have exported the data from the machine you need to upload the data to the MCERTS system : </p>
<p><a href="uploadReports.htm">How to upload reports to the system</a></p>
<p>After you have uploaded your reports you will need to add a validation report to the system : </p>
<p><a href="addValidationSet.htm">How to add a validation report</a></p>
<p>You will then need to add a reference material (sample) to the validation :</p>
<p><a href="addReferenceMaterial.htm">How to add a reference material to a validation report </a></p>
<p>You will then need to know how to add analytes to the reference materials that you add :</p>
<p><a href="addAnalyte.htm">How to add an analyte to a reference material</a></p>
<p>After you have added the validation, reference materials and analytes to the system, you will want to know :</p>
<p><a href="viewValidationSummaryReport.htm">How to view validation summary report (pass/fail report) </a></p>
<p>and </p>
<p><a href="viewStatisticalAnalysisReport.htm">How to view a statistical analysis report</a></p>
<p>and </p>
<p><a href="selectBatches.htm">How to select / alter batches</a> </p>
<h3>&nbsp;</h3>
<p>If you have any problems/questions  email <a href="mailto:chrisr@saiman.co.uk">chrisr@saiman.co.uk </a></p>
</body>
</html>
