<?php

function urule_required($name, $value, $alias = null, $params = null) {
	if(strlen(trim($value)) == 0)
		return "$value is required";
		
	return UQL_RULE_SUCCESS;
}
?>