<?php // %W% %G% ?>
<?php 
include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
require_once('functionQC.php');
require_once('../utility/stats.php');
require_once('../utility/mathematics.php');
require_once('datefunctions.php');
$db->debug=false;


function getPasses($RMID,$theEID,$selectedBatches)
{
	global $db;
	
	$query_Compounds = sprintf("select distinct Compound from compounds 
								where compounds.ReferenceMaterialID = ( 
										select ReferenceMaterialID 
										from validation_reference_materials vrm
										where vrm.EID = '%s'
										and vrm.ReferenceMaterialID = compounds.ReferenceMaterialID
										and exists
											(select referencematerial from reference_materials
											where referencematerialid=vrm.referencematerialid
											and includeinreport=1
											)
										)		
								and compounds.IncludeInReport=1
								ORDER BY upper(Compound) ASC",
								$theEID
								);
								
	$Compounds=$db->GetAll($query_Compounds);
	
	$compoundCounter=0;
	foreach($Compounds as $row_Compounds)
	{
		$compounds[$compoundCounter]=$row_Compounds['COMPOUND'];
		$compoundCounter++;
	}
	
	$query_CRM = sprintf("select reference_materials.ReferenceMaterialID, reference_materials.ReferenceMaterial
						from reference_materials, validation_reference_materials 
						where validation_reference_materials.ReferenceMaterialID=reference_materials.ReferenceMaterialID
						and reference_materials.IncludeInReport=1
						and validation_reference_materials.EID='%s'
						and reference_materials.referencematerialid=%s
						",
						$theEID,$RMID);
	$theCRM=$db->GetAll($query_CRM);
	$a= count($theCRM);
	
	$crmCounter=0;
	$nonCRMReferenceMaterial="";
	foreach($theCRM as $row_CRM)
	{
		$ReferenceMaterial[$crmCounter]=$row_CRM['REFERENCEMATERIAL'];
		
		if(substr_count(strtoupper($ReferenceMaterial[$crmCounter]),"CRM")==0)
			$nonCRMReferenceMaterial=$ReferenceMaterial[$crmCounter];
			
		$ReferenceMaterialID[$crmCounter]=$row_CRM['REFERENCEMATERIALID'];
		$crmCounter++;
	}
	
	if($nonCRMReferenceMaterial=="")
		$nonCRMReferenceMaterial=$ReferenceMaterial[0];
	
	$sqlUnits = sprintf("select distinct top,bottom 
						from results 
						where sample='%s'",$nonCRMReferenceMaterial);
	$units=$db->GetRow($sqlUnits);					
	
	$topUnit=$units['TOP'];
	$bottomUnit=$units['BOTTOM'];
	$equipmentSQL = "select equipmentid,name from validation_reports where EID=".$theEID;
	
	$equipmentRow=$db->GetRow($equipmentSQL);
	$equipmentID=$equipmentRow['EQUIPMENTID'];
	$validationName=$equipmentRow['NAME'];
	
	$biasPass=$biasFail=$precisionPass=$precisionFail=0;
			
	
	for($i=0;$i<$compoundCounter;$i++) { 
		for($x=0;$x<$crmCounter;$x++) { 
			$resultArray=getAssessment($ReferenceMaterialID[$x],$compounds[$i],false,$selectedBatches,true); 
			$precision=$resultArray['precisiontest'];
			if($precision=="FAIL")
				$precisionFail++;
			else if($precision=="PASS")
				$precisionPass++;
				
			$bias=$resultArray['biastest'];
			if($bias=="FAIL")
				$biasFail++;
			else if($bias=="PASS" || $bias=="SPASS")
				$biasPass++;
		}
	}
			
	return $biasPass;				
}					


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





//get number of batches for ref mat id
$queryReplicate1AllBatches = sprintf("select * from batches where referencematerialid=57");
$Replicates1All= $db->GetAll($queryReplicate1AllBatches);	
$noOfBatches=count($Replicates1All);

$runs = $noOfBatches>13 ? 12 : $noOfBatches;

$final = array();//if you want the result in a multidimensional array

//$numbers = array_merge(range(0,10),range(13,14)); //$runs-1);
$numbers=range(0,11);
$count = sizeof($numbers);//count the elements in the array

count_array_stuff(11, 0, array());//run the function

//echo sizeof($final)."\n";//if putting results into an array, echo the number of posible combos
//print_r($final);//display the whole array if putting the results into an array
?>
</html>
<body>
<h3>No of iterations : <?php echo sizeof($final)."\n"; ?></h3>
<h3>No of batches : <?php echo $noOfBatches ?></h3>
<?php
$result = array();
$currentMax=-1;
$currentMaxValue=-1;

$start=time();
echo "Size final : ".$final;
for($x=1;$x<=sizeof($final);$x++)
{
	//echo "<h3>Run".$x."</h3>";
	$result[$x]=getPasses(229,1014,$final[$x]);

	if($result[$x]>$currentMaxValue)
	{
		$currentMaxValue=$result[$x];
		$currentMax=$x;
		$optimumBatches=$final[$x];
	}
	
	/*echo "<h3>Set :  ";
	foreach($final[$x] as $batchNo)
	{
		print $batchNo.", ";
	}
	echo "</h3>";
	echo "<h3>Passes : ".$result[$x]."</h3>";*/
		

}
$end=time();
$total=strftime("%M,%S", $end-$start);
echo "<h3>Time taken : ".$total."</h3>";
echo "<h3>".strftime("%M,%S", ($end-$start)/sizeof($final))." per iteration</h3>";
echo "<h3>Passes : ".$currentMaxValue."</h3>";
echo "<h3>Max Set :  ";
foreach($final[$currentMax] as $batchNo)
{
	print $batchNo.", ";
}
echo "</h3>";
echo "  Index : ".$x;

?>
</body>
</html>

