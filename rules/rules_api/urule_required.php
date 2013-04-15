<?php

function urule_required($name, $value, $alias = null, $params = null) {
	if(strlen(trim($value)) == 0)
	{
		if($alias != null)
		 $message = $alias.' قيمة مطلوبة ويجب إدخالها';
		else
	     $message = $name.' قيمة مطلوبة ويجب إدخالها';
		return $message;
	}
		
		
	return UQL_RULE_SUCCESS;
}
?>