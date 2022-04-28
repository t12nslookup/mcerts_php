<?php
$months=array("Jan","Feb","Mar","Apr","May","June","July","Aug","Sep","Oct","Nov","Dec");
function convertToDBDate($day,$month,$year)  //converts to yyyy-mm-dd as required by MYSQL
{
	return $year."-".$month."-".$day;
}

function createDateArray($date)  //converts yyyy-mm-dd to array, where [0]=dd [1]=mm [2]=yyyy
{
	if(strlen($date)>9)
		return array(substr($date,8,2),substr($date,5,2),substr($date,0,4));
	else return array("","","");
}

function getStringDate($date) //converts yyyy-mm-dd to dd-mmm(string)-yyy
{
//	echo $date;//2001-01-01 00:00:00
	global $months;
	if($date=="") return "";
	$dateArray=createDateArray($date);
	if($dateArray[1]-1<0)
	{
		return "";
	}
	else
	{
	return $dateArray[0]."-".$months[$dateArray[1]-1]."-".$dateArray[2];
	}
}

function getStringTime($date)
{
	return getStringDate($date).substr($date,10);
}

function getStringDateTime($datetime) //converts yyyy-mm-dd hh:mm:ss to dd-mmm-yyyy hh:mm
{
	if (strlen($datetime)>12)
		return  getStringDate(substr($datetime,0,10))." ".substr($datetime,11,5);
	else return "";
}


function formatDate($date) //converts any date string into format required by DB
{
	return date("Y-m-d H:i:s" ,strtotime($date));	
}

function getSQLDate($date)
{
	$newDate=date("Y-m-d H:i:s" ,strtotime($date));
	$sqlDate="to_date('".$newDate."','RRRR-MM-DD HH24:MI:SS')";
	return $sqlDate;
}

?>
