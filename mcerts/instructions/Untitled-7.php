<?php 


function processDate($date)
{
	return $date;
}

function parseICPScotland($fileContents) 
{
	$fileContents=removeQuotes($fileContents);
	preg_match_all('/.*\r/', $fileContents, $lines); //get array of lines
	$lines=$lines[0];
	
	$elements=array();
	
	foreach ($lines as $fullLine)
	{
		$line=preg_split("/\t/",$fullLine);
		if($line[4]=="Sample Name")
		{
			$elements=array();
			$colno=10;
			while($line[$colno]!="")
			{
				$element=array();
				$element['column']=colno;
				$element['name']=$line[colno];
				array_push($elements,$element);
				$colno+=2;
			}
		}
		
		if($line[0]=="Average")
		{
			$date=processDate($line[1]);
			$method=$line[2];
			$sample=$line[4];
			$mass=$line[5];
			$volume=$line[6];
			$dilution=$line[7];
			$operator=$line[8];
			
			foreach($elements as $element)
			{
				if($line[$element['column']-1]=="")
				{
					$SQL=sprintf("insert into results (acqon,concentration,sample,compound, DF,operator)
								values (to_date('%s','YYYY-MM-DD HH24:MI:SS'),%s,'%s','%s',%s,'%s')",
								date("Y-m-d H:i:s" ,strtotime($date)),
								$line[$element['column']],
								$sample,
								$element['name'],
								$dilution,
								$operator);
					echo($SQL);
					array_push($SQLArray, $SQL);		
				}
			}
		}
		
		$line=preg_split("/\t/",$lines[$i]);
	}
	return $SQLArray;
}
	   
?>