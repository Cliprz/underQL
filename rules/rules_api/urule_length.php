<?php

function urule_length($name, $value, $alias = null, $params = null) {
	
	//var_dump($params);
	if($params == null)
	 return 'يجب عليك تحديد الطول المطلوب قبل استخدامه مع الحقل '.$name;
	
	if(!is_int($params[0]) || $params[0] <= 0)
	{
		if($alias != null)
	 	    return 'يجب عليك تحديد قيمة مناسبة للطول لحقل '.$alias;
		else 
			return 'يجب عليك تحديد قيمة مناسبة للطول للحقل '.$name;
	}
	if(mb_strlen($value,'utf8') > $params[0])
	  {
	  	if($alias != null)
		 return 'يجب أن لا يتجاوز طول '.$alias.' '.$params[0].' حرف/أحرف';
		else
         return 'يجب أن لا يتجاوز الطول '.$name.' '.$params[0].' حرف/أحرف';
	  }
	return UQL_RULE_SUCCESS;
}

?>