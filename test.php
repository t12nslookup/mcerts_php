<?
include('adodb/adodb.inc.php');
require_once('Connections/mysql.php');
require('mcerts/datefunctions.php');
//$db->debug=true;


//first set the values
/*
$final = array();//if you want the result in a multidimensional array
$numbers = range(1,13);

$count = sizeof($numbers);//count the elements in the array
count_array_stuff(11, 0, array());//run the function
echo sizeof($final)."\n";//if putting results into an array, echo the number of posible combos
print_r($final);//display the whole array if putting the results into an array
*/

function count_array_stuff($counter, $start_pos, $array_of_keys){
    //$counter is how many times to do a for loop (how many items to pick from the array)
    //$start_pos is the starting position to start the for loop
    //$array_of_keys is an array containing the keys of the items picked from the array

    //in order to use a variables outside a function you must make them global
    global $final;
    global $numbers;
    global $count;
    if ($counter <= 0 ){
        //this is the last iteration, echo the numbers/put into an array
        $count_of_final = sizeof($final)+1;
        foreach ($array_of_keys as $value){
            $final[$count_of_final][] = $numbers[$value];
            //echo "{$numbers[$value]}<br />";
        }
       echo '<br /><br />';
    } else {
        for ($i=$start_pos; $i<$count; $i++){
            //loop through array starting at $start_pos
            //create a new array witch has the keys from $array_of_keys with $i added to it
            $new_array_of_keys = array($i);
            $new_array_of_keys = array_merge($array_of_keys, $new_array_of_keys);
            //run the function again
            count_array_stuff($counter -1, $i +1 ,$new_array_of_keys);
        }
    }
} 

/*
$rows=$db->GetAll("select * from compounds where referencematerialid=230");

foreach($rows as $row)
{
	$insert=sprintf("insert into compounds (compound,referencematerialid,targetconcentration,targetstandarddeviation,targetbias,includeinreport)
			values ('%s',%s,%s,%s,%s,1)",
			$row['COMPOUND'],
			244,
			$row['TARGETCONCENTRATION'],
			$row['TARGETSTANDARDDEVIATION'],
			$row['TARGETBIAS']);
	$db->Execute($insert);		
}*/



?>