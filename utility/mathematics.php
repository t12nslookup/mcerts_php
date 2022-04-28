<?php 

function rangesOverlap($l1,$h1,$l2,$h2)
{

	if( ($l1 >= $l2 && $l1<=$h2)
			|| ($h1 >= $l2 && $h1 <= $h2)
				|| ($l1<=$l2 && $h1>=$h2) )
		return true;
	else
		return false;
}
//maths functions

function standard_deviation($array) { //returns SD of array items
    $total=0;
	$sum=0;
	
	if (!count($array) || count($array)==1) return 0;
    //Get sum of array values
	for ($i = 0; $i < count($array); $i++)
		$total += $array[$i];
	
    $mean = $total/count($array);
    	
	for ($i = 0; $i < count($array); $i++)
		$sum += pow(($array[$i]-$mean),2);;
	   
    $stDeviation = sqrt($sum/(count($array)-1));
    
    return $stDeviation;
}

function average($array) //returns average of array items
{
   if (!count($array)) return 0;
   $sum = 0;
   for ($i = 0; $i < count($array); $i++)
       $sum += $array[$i];
	   
   return $sum / count($array);
}

function variance($array) //variance of array items
{
   if (!count($array) || count($array)==1) return 0;
   $mean = average($array);
   $sos = 0;    // Sum of squares
   for ($i = 0; $i < count($array); $i++)
       $sos += ($array[$i] - $mean) * ($array[$i] - $mean);
   return $sos / (count($array)-1); 
}

function sigfigs( $Number,$Significant, $Decimals)
{
  /* This function will create a static string and write into it
  ** the representation of a $Number with the $Number of significant
  ** figures and decimal places fixed
  */

         /* Stu. Note the fairly conservative allocation for string space */
#ifdef SUNVERSION
  $sign = $Number <0.0;

  if ($sign) $Number = abs($Number);
	  if ($Number == 0)
		  $CharsBeforePoint = 1;
	  else
  $CharsBeforePoint = floor( log10($Number)+1);
#else
//  ecvt($Number,WSDCVT_BUF-1,&$CharsBeforePoint ,&sign);
#endif

  /* Generate a $Number which is rounded to the correct 
  ** $Number of significant figures
  */
  $PowFactor = pow(10, $CharsBeforePoint - $Significant);

  $SignificantNumber = $Number / $PowFactor;

  //$FractionalPart = modf($SignificantNumber,$IntegerPart);

	$IntegerPart =intval($SignificantNumber);
	$FractionalPart =$SignificantNumber -$IntegerPart;
  /* round for figures beyond Least Significant Figure*/
  $IntegerPart += floor($FractionalPart+0.5);

  /* Explode to full $Number again */
  $SignificantNumber = $IntegerPart * $PowFactor;

  $DecimalPlaces = $Significant - $CharsBeforePoint;
  if ($DecimalPlaces <0) $DecimalPlaces = 0;


  /* stop underflow of Significant Figures from resulting in 
  ** decimal place expansion 
  */
  if ($DecimalPlaces > $Decimals) 
  	$DecimalPlaces = $Decimals;

  $NumberString=sprintf("%". -($CharsBeforePoint + $Decimals + $sign +1) . "." .
                                  $DecimalPlaces . "f",
                                  $SignificantNumber);
  return($NumberString);
}

?>