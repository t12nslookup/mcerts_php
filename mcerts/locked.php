<?php
$validUsers = array('dwood', 'chrisr', 'ajones', 'root', 'pgeri');
if (isset($_SERVER['PHP_AUTH_USER']) && array_search($_SERVER['PHP_AUTH_USER'], $validUsers) > -1)
	$locked = false;
else $locked = true;




$locked = false;
if (isset($_GET['locked']) && $_GET['locked'] == "true") {
	$locked = true;
} else if (isset($_GET['locked']) && $_GET['locked'] == "false") {
	$locked = false;
}

$locked = false;
