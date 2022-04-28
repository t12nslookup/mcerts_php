<?php
$type = $_GET['type']
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
<form enctype="multipart/form-data" action="uploadDocument.php" method=
"post">
<h4><input type="hidden" name="MAX_FILE_SIZE" value="2000000">  
<input type="hidden" value="<?=type?>"
  <input name="userfile" type="file" size="60"> 
  <input type=
"submit" value="Upload File"></h4>				
</form>
</body>
</html>
