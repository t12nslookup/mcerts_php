<?php 

include('../adodb/adodb.inc.php');
require_once('../Connections/mysql.php');
include('../utility/stats.php');
include('../utility/mathematics.php');

//$db->debug = true;

/*
params : $compound = the compound as a string
return : associative array ['bias'] ['precision'] 
*/

function getAnalytePrecisionAndBias($compound,$referenceMaterialId)
{
    global $db;

    $sqlBiasPrecision = sprintf("select targetbias,targetstandarddeviation 
                                                            from compounds 
                                                            where compound='%s'
                                                            and referencematerialid=%s
                                                            and targetbias>0
                                                            and targetstandarddeviation>0",str_replace("'","''",$compound),$referenceMaterialId);
    $row=$db->GetRow($sqlBiasPrecision);

    $result = array();	
    $result['bias']=isset($row['TARGETBIAS']) ? $row['TARGETBIAS'] : 0;
    $result['precision']=isset($row['TARGETSTANDARDDEVIATION']) ? $row['TARGETSTANDARDDEVIATION'] : 0;
    return $result;
}

function getAssessment($RMID,$compound,$report = false, $batchNumbers=false, $extra=false)
{
	global $db;
	
	$result = array();
	$result['precision']="";
	$result['mean'] = "";
	$result['precisiontest']="";
	$result['biastest']="";
	$result['referenceconcentration']="";
	$result['error']="";
	$result['bias']="";
	$result['passedsimpleprecision']=false;
	$backgroundCorrected=false;
	
	//get compound ID from compound name and reference material id
	$query_CompoundID = sprintf("SELECT Distinct CompoundID FROM compounds where ReferenceMaterialID=%s AND Compound='%s'",$RMID,str_replace("'","''",$compound));	
	$CompoundID=$db->GetOne($query_CompoundID);
	
	
	$q_eid = sprintf("select distinct equipmentid from 
	validation_reports vr, validation_reference_materials vrm where vrm.referencematerialid=%s and vrm.eid = vr.eid",$RMID);
	$eid = $db->GetOne($q_eid);
	if($CompoundID=="")
	{
		$result['error']="stop";
		return $result;
	}
	
	//test that compound exists for reference material
	$query_ExistenceTest = sprintf("SELECT count(*) 
						FROM Compounds 
						where CompoundID=%s 
						and ReferenceMaterialID=%s
						and IncludeInReport=1",
						$CompoundID,
						$RMID);
	if(count($db->GetRow($query_ExistenceTest))==0)
	{
		$result['error']="stop";
		return $result;
	}
			
	//get compound Name from Compound ID
	$query_Compound = sprintf("SELECT Compound, TargetConcentration, TargetStandardDeviation,TargetBias 
								FROM compounds 
								WHERE CompoundID=%s",$CompoundID);
							
	$row_Compound=$db->GetRow($query_Compound);
	$referenceConcentration=$row_Compound['TARGETCONCENTRATION'];

		
	if($referenceConcentration=="" || $referenceConcentration==0) 
	{
		$result['error']="stop";
		$result['precisiontest']=$result['biastest']="NRC";
		return $result;
	}

	$targetSDPercent=$row_Compound['TARGETSTANDARDDEVIATION'];
	$biasTargetPercent=$row_Compound['TARGETBIAS'];
	
	//get adjustment material
	$sql= sprintf("select adjustmentmaterial 
					from reference_materials 
					where referencematerialid=%s",
					$RMID);
	$adjustmentMaterial=$db->GetOne($sql);
		
	if($extra==true)
	{
		$queryReplicate1AllBatches = sprintf("select Concentration,AcqOn FROM batches b1,
  reference_materials rm1,
  compounds c1,
  results r1
WHERE c1.compoundid = %s
 AND r1.compound = c1.compound 
 AND rm1.referencematerialid = %s
 AND r1.sample = rm1.referencematerial
 AND b1.referencematerialid = %s
 AND r1.acqon IN b1.replicate1
 and r1.machine=%s
ORDER BY acqon",$CompoundID,$RMID,$RMID, $eid);
	

		$Replicates1All= $db->GetAll($queryReplicate1AllBatches);	
	  	$noOfBatches=count($Replicates1All);
		
	$queryReplicate2AllBatches = sprintf("select Concentration,AcqOn FROM batches b1,
  reference_materials rm1,
  compounds c1,
  results r1
WHERE c1.compoundid = %s
 AND r1.compound = c1.compound 
 AND rm1.referencematerialid = %s
 AND r1.sample = rm1.referencematerial
 AND b1.referencematerialid = %s
 AND r1.acqon IN b1.replicate2
 and r1.machine=%s
ORDER BY acqon",$CompoundID,$RMID,$RMID,$eid);

  		$Replicates2All= $db->GetAll($queryReplicate2AllBatches);	

		if($noOfBatches<3)
		{
			$result['error']="stop";
			return $result;
		}

		$aConcAll = array();
		foreach($Replicates1All as $row_Replicates1)
		{
			//array_push($aConc,$row_Replicates1['CONCENTRATION']);
			$sql=sprintf("select *
						from batches 
						where to_char(replicate1,'YYYY-MM-DD HH24:MI:SS')='%s'
						and referencematerialid=%s",
						$row_Replicates1['ACQON'],
						$RMID);
			$batchRow=$db->GetRow($sql);
			
			$multiplier=$batchRow['MULTIPLIER'];
			$multiplier= ($multiplier=="") ? 0 : $multiplier;
			
			if(isset($batchRow['ADJUSTMENTTIME']))
			{
				$adjustmentTime=$batchRow['ADJUSTMENTTIME'];
				if($adjustmentTime!="")
					$backgroundCorrected==true;	
				$sql2=sprintf("select concentration from results
								where sample='%s'
								and compound='%s'
								and to_char(acqon,'YYYY-MM-DD HH24:MI:SS')='%s' and machine=%s",
								$adjustmentMaterial,
								str_replace("'","''",$compound),
								$adjustmentTime, $eid);
	
				$adjustmentConcentration=$db->GetOne($sql2);				 
				$adjustmentConcentration = $adjustmentConcentration=="" ? 0 : $adjustmentConcentration;
			} else {
				$adjustmentConcentration=0;
			}
			
			$finalConcentration=$row_Replicates1['CONCENTRATION']-$adjustmentConcentration;
			$finalConcentration*=$multiplier;
			array_push($aConcAll,$finalConcentration);
		}
		
		$bConcAll = array();
		foreach($Replicates2All as $row_Replicates2)
		{
			//array_push($bConc,$row_Replicates2['CONCENTRATION']);
			$sql=sprintf("select *
							from batches 
							where to_char(replicate2,'YYYY-MM-DD HH24:MI:SS')='%s'
							and referencematerialid=%s",
							$row_Replicates2['ACQON'],
							$RMID);
					
			$batchRow=$db->GetRow($sql);

			$multiplier=$batchRow['MULTIPLIER'];
			$multiplier= ($multiplier=="") ? 0 : $multiplier;
			
			if(isset($batchRow['ADJUSTMENTTIME']))
			{
				$adjustmentTime=$batchRow['ADJUSTMENTTIME'];
				if($adjustmentTime!="")
					$backgroundCorrected==true;	
				$sql2=sprintf("select concentration from results
								where sample='%s'
								and compound='%s'
								and to_char(acqon,'YYYY-MM-DD HH24:MI:SS')='%s' and machine=%s",
								$adjustmentMaterial,
								str_replace("'","''",$compound),
								$adjustmentTime, $eid);
	
				$adjustmentConcentration=$db->GetOne($sql2);				 
				$adjustmentConcentration = $adjustmentConcentration=="" ? 0 : $adjustmentConcentration;
			} else {
				$adjustmentConcentration=0;
			}

			$finalConcentration=$row_Replicates2['CONCENTRATION']-$adjustmentConcentration;
			$finalConcentration*=$multiplier2;
			array_push($bConcAll,$finalConcentration);
		}
		
		print_r($aConcAll);
		
		$aConc = array();
		$bConc = array();
		foreach($batchNumbers as $batchNumber)
		{
			array_push($aConc,$aConcAll[$batchNumber]);
			array_push($bConc,$bConcAll[$batchNumber]);
		}
	}								
	else { 
		//get replicates
		$query_Replicates1 = sprintf("select Concentration,AcqOn from results
								where compound= (SELECT Compound
												from compounds
												where CompoundID=%s)
								and Sample=(select ReferenceMaterial
											from reference_materials
										  where ReferenceMaterialID = %s)       
								and AcqOn IN (select Replicate1
										from Batches
										where ReferenceMaterialID= %s
										and selected=1
										)
                                and machine=%s                                             
										ORDER BY AcqOn"
									  ,$CompoundID,$RMID,$RMID, $eid);
		$Replicates1= $db->GetAll($query_Replicates1);	
		
		$noOfBatches=count($Replicates1);
		if($noOfBatches<3)
		{
			$result['error']="stop";
			return $result;
		}
		
			
		$aConc = array();
                
		foreach($Replicates1 as $row_Replicates1)
		{
			//array_push($aConc,$row_Replicates1['CONCENTRATION']);
			$sql=sprintf("select * 
						from batches 
						where to_char(replicate1,'YYYY-MM-DD HH24:MI:SS')='%s'
						and referencematerialid=%s",
						$row_Replicates1['ACQON'],
						$RMID);

			$batchRow=$db->GetRow($sql);
			if(isset($batchRow['MULTIPLIER']))
			{
			$multiplier=$batchRow['MULTIPLIER'];
			}
			else
			{
			$multiplier= 1;
			}
			
			if(isset($batchRow['ADJUSTMENTTIME']))
			{
				$adjustmentTime=$batchRow['ADJUSTMENTTIME'];
				if($adjustmentTime!="")
					$backgroundCorrected==true;	
				$sql2=sprintf("select concentration from results
								where sample='%s'
								and compound='%s'
								and to_char(acqon,'YYYY-MM-DD HH24:MI:SS')='%s' and machine=%s",
								$adjustmentMaterial,
								str_replace("'","''",$compound),
								$adjustmentTime, $eid);
	
				$adjustmentConcentration=$db->GetOne($sql2);				 
				$adjustmentConcentration = $adjustmentConcentration=="" ? 0 : $adjustmentConcentration;
			} else {
				$adjustmentConcentration=0;
			}			 


			$adjustmentConcentration = $adjustmentConcentration=="" ? 0 : $adjustmentConcentration;
			$finalConcentration=$row_Replicates1['CONCENTRATION']-$adjustmentConcentration;
			$finalConcentration*=$multiplier;
			array_push($aConc,$finalConcentration);
		}
		
		
		$query_Replicates2 = sprintf("select Concentration,AcqOn from results
									where compound= (SELECT Compound
													from compounds
													where CompoundID=%s)
									and Sample=(select ReferenceMaterial
												from reference_materials
											  where ReferenceMaterialID = %s)       
									and AcqOn IN (select Replicate2
											from Batches
											where ReferenceMaterialID= %s
											and selected=1
											) and machine=%s
											ORDER BY AcqOn"
										  ,$CompoundID,$RMID,$RMID, $eid);
										  
		$Replicates2 = $db->GetAll($query_Replicates2);
		$bConc = array();

		foreach($Replicates2 as $row_Replicates2)
		{
			//array_push($bConc,$row_Replicates2['CONCENTRATION']);
			$sql=sprintf("select * 
							from batches 
							where to_char(replicate2,'YYYY-MM-DD HH24:MI:SS')='%s'
							and referencematerialid=%s",
							$row_Replicates2['ACQON'],
							$RMID);
			$batchRow=$db->GetRow($sql);
			if(isset($batchRow['MULTIPLIER']))
			{
			$multiplier2=$batchRow['MULTIPLIER'];
			}
			else
			{
			$multiplier2= 1;
			}
			
			if(isset($batchRow['ADJUSTMENTTIME']))
			{
				$adjustmentTime=$batchRow['ADJUSTMENTTIME'];
				if($adjustmentTime!="")
					$backgroundCorrected==true;	
				$sql2=sprintf("select concentration from results
								where sample='%s'
								and compound='%s'
								and to_char(acqon,'YYYY-MM-DD HH24:MI:SS')='%s' and machine=%s",
								$adjustmentMaterial,
								str_replace("'","''",$compound),
								$adjustmentTime, $eid);
	
				$adjustmentConcentration=$db->GetOne($sql2);				 
				$adjustmentConcentration = $adjustmentConcentration=="" ? 0 : $adjustmentConcentration;
			} else {
				$adjustmentConcentration=0;
			}
			$finalConcentration=$row_Replicates2['CONCENTRATION']-$adjustmentConcentration;
			$finalConcentration*=$multiplier2;
			array_push($bConc,$finalConcentration);
		}
		
	} // $extra
	
	//F-test table:
	$valueOfF=array(3.84,3.00,2.60,2.37,2.21,2.10,2.01,1.94,1.88,1.83,1.79,1.75,1.72,1.69,1.67,1.65,1.63,1.61,1.59,1.57, 1.56, 1.54, 1.53, 1.52, 1.51, 1.5, 1.49);
    
	//t-test table:
	$valueOfT=array(6.314,2.920,2.353,2.132,2.015,1.943,1.895,1.860,1.833,1.812,1.796,1.782,1.771,1.761,1.753,1.746,1.740,1.734,1.729,1.725);

	//calculated variables
	for($i=0;$i<count($aConc);$i++)
	{	
		if(isset($aConc[$i]) && isset($bConc[$i])) {
			$batchVariance[$i]=variance(array($aConc[$i],$bConc[$i]));
			$batchAverage[$i]=average(array($aConc[$i],$bConc[$i]));
			$batchMeanRecovery[$i]=$batchAverage[$i]-$referenceConcentration;
		} else {
      return $result;
		}
	}
	
	//initialise variables
	$replicates=2;
	$df=$estimatedDOF=$withinBatchSD=$betweenBatchSD=$square=$totalSD=$relativeSD=$targetSD=$fFromTable=0;
	$fCalculated=$estimatedBias=$estimatedMeanRecovery=$highLimit=$lowLimit=$sdMeanRecovery=$standardErrorOfMeanRecovery=0;
        $studentTValue=$confidenceLimits=$upperConfidenceLevel=$lowerConfidenceLevel=$lowerRecoveryRange=$upperRecoveryRange=$LOD=0;
	
	
	
	$m0 = average($batchVariance);
	$m1 = variance($batchAverage)*2;
	$mean = average(array_merge($aConc,$bConc));
	$biasAssessment="";

	if($m0==0 && $m1==0 && $mean<=0)
	{
		if($mean>0) //all values are equal
		{
			$precisionAssessment="SUSP";
			$biasAssessment="";
		}
		else
		{
			$precisionAssessment="FAIL";
			$biasAssessment="";
		}
	}
	else
	{			
		//precision test
		if($m0==0 && $m1==0)
		{
			
		}
		else
		{
                      $df=($noOfBatches*($noOfBatches-1) * pow($m1+($replicates-1)*$m0,2))
                        /
                        ($noOfBatches*($m1*$m1)+($noOfBatches-1)*($replicates-1)*($m0*$m0));		
                        $estimatedDOF=round($df);
                        if($estimatedDOF>27)
                        {
                            $estimatedDOF=27;
                        }
		}
		$withinBatchSD=sqrt($m0);
		$betweenBatchSD = $m1>$m0 ? sqrt(($m1-$m0)/2) : 0;
		$square = ($m0 + $m1)/2;
		$totalSD=sqrt($square);
		$relativeSD= ($totalSD/$mean) *100;
		$targetSD=$targetSDPercent*$mean/100;
		//$fFromTable=$valueOfF[$noOfBatches-1]; - OLD WAY OF DOING IT!! before 15th May 08
                $fFromTable=$valueOfF[$estimatedDOF-1];                
		$fCalculated=($totalSD/$targetSD)*($totalSD/$targetSD);
		$estimatedBias=($mean/$referenceConcentration*100)-100;
			
		//if($estimatedMeanRecovery<=$highLimit && $estimatedMeanRecovery>=$lowLimit) 
		//{			
		//	//$biasAssessment = "PASS"; //test if mean recovery is within bias target range
		//	//$result['passedsimpleprecision']=true;
		//	crapout;
		//}
		//else
		//{
		$exactBias = (($mean/$referenceConcentration)-1)*$mean;
		//$precisionAssessment= ($relativeSD<=$targetSDPercent) || ($fCalculated<=$fFromTable && $estimatedDOF!=0) ? "PASS" : "FAIL";

		
		if($relativeSD<=$targetSDPercent)
		{
			$precisionAssessment="PASS";
		} else if ($fCalculated<=$fFromTable && $estimatedDOF!=0)
		{
			$precisionAssessment="SPASS";
		} else
			$precisionAssessment="FAIL";


		$studentTValue=$valueOfT[$noOfBatches-2];
		$LOD=2*sqrt(2)*$studentTValue*$withinBatchSD;
		
		if($precisionAssessment=="PASS" || $precisionAssessment=="SPASS") 
		{	
			//bias test
			$estimatedMeanRecovery = 100*($mean/$referenceConcentration);
			
			$result['bias']=$estimatedMeanRecovery;
			
			$highLimit = 100+$biasTargetPercent;
			$lowLimit = 100-$biasTargetPercent;
			$sdMeanRecovery = standard_deviation($batchMeanRecovery);
			$standardErrorOfMeanRecovery = $sdMeanRecovery/sqrt($noOfBatches);
			$confidenceLimits = $studentTValue*$standardErrorOfMeanRecovery;
			$upperConfidenceLevel = $mean+$confidenceLimits;
			$lowerConfidenceLevel = $mean-$confidenceLimits;
			$lowerRecoveryRange = ($lowerConfidenceLevel/$referenceConcentration)*100;
			$upperRecoveryRange = ($upperConfidenceLevel/$referenceConcentration)*100;

			if($estimatedMeanRecovery<=$highLimit && $estimatedMeanRecovery>=$lowLimit)
			{
				$biasAssessment = "PASS";
			} else {				
				$biasAssessment = rangesOverlap($lowerRecoveryRange,$upperRecoveryRange,$lowLimit,$highLimit)
									? "SPASS" : "FAIL";								
			}	
			//$biasAssessment= $estimatedMeanRecovery<=$upperRecoveryRange 
								//&& $estimatedMeanRecovery>=$lowerRecoveryRange ? "SPASS" : "FAIL";
		}
		//} 
		//else $biasAssessment="";
	}
	
	//get machine
		$sqlMachine = sprintf("
						select equipmentid,showexactprecision from validation_reports where eid =
						(
							SELECT EID 
							FROM validation_reference_materials 
							WHERE ReferenceMaterialID=%s
						)",$RMID);

		$machineRow = $db->GetRow($sqlMachine);
		$result['showexactprecision']=$machineRow['SHOWEXACTPRECISION'];
		
	if($report==true)
	{
		//get reference material (Sample) name from Reference Material ID
		$querySample = sprintf("SELECT ReferenceMaterial 
                        FROM reference_materials 
                        WHERE ReferenceMaterialID=%s",$RMID);									
		$result['compound']=$compound;
		$result['machine']=$machineRow['EQUIPMENTID'];		
		$result['sample']=$db->GetOne($querySample);
		$result['variances']=$batchVariance;
		$result['averages']=$batchAverage;
		$result['firstreplicates']=$aConc;
		$result['secondreplicates']=$bConc;	
		$result['estimateddof']=issetNew($estimatedDOF);
		$result['relativesd']=issetNew($relativeSD);
		$result['targetsd']=issetNew($targetSD);
		$result['targetsdpercent']=issetNew($targetSDPercent);
		$result['ffromtable']=issetNew($fFromTable);
		$result['fcalculated']=issetNew($fCalculated);
		$result['biastargetpercent']=issetNew($biasTargetPercent);
		$result['estimatedmeanrecovery']=issetNew($estimatedMeanRecovery);
		$result['m0']=issetNew($m0);
		$result['m1']=issetNew($m1);
		$result['df']=issetNew($df);
		$result['square']=issetNew($square);
		$result['sdmeanrecovery']=issetNew($sdMeanRecovery);
		$result['estimatedmeanrecovery']=issetNew($estimatedMeanRecovery);
		$result['standarderrorofmeanrecovery']=issetNew($standardErrorOfMeanRecovery);
		$result['confidencelimits']=issetNew($confidenceLimits);
		$result['upperconfidencelevel']=issetNew($upperConfidenceLevel);
		$result['lowerconfidencelevel']=issetNew($lowerConfidenceLevel);
		$result['lowerrecoveryrange']=issetNew($lowerRecoveryRange);
		$result['upperrecoveryrange']=issetNew($upperRecoveryRange);
		$result['adjustmentmaterial']=$adjustmentMaterial;
	}
	
	$result['LOD']=$LOD;
	$result['exactbias']= isset($exactBias) ? $exactBias : 0;
	$result['estimatedbias']=$estimatedBias;
	$result['totalsd']=issetNew($totalSD);
	$result['targetsdpercent']=$targetSDPercent;
	$result['bias']=$estimatedMeanRecovery=="" ? "" :$estimatedMeanRecovery-100;		
	$result['precision']= ($relativeSD==0) ? 0 : $relativeSD;
	$result['mean'] = ($mean==0) ? 0 : $mean;
	$result['precisiontest']=$precisionAssessment;	
	$result['biastest']=$biasAssessment;	
	$result['referenceconcentration']=($referenceConcentration==0) ? "" : $referenceConcentration;
	
	$biasPrecisionSQL = sprintf("update compounds set summary_precision=%s,summary_bias=%s,precision_pass='%s',bias_pass='%s'" .
			" where compoundid=%s", $result['totalsd'],$result['exactbias'],$result['precisiontest'],$result['biastest'],$CompoundID);
	$db->Execute($biasPrecisionSQL);
		
	return $result;
	
	if($test=="Precision") 
		return $precisionAssessment;	
	if($test=="Bias")
		return $biasAssessment;
}

function issetNew(&$x)
{
   if (isset($x) && strlen($x) > 0) { return $x; }
   else { return ""; }
}
?>
