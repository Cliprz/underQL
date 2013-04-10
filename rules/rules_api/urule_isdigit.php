<?php

function urule_isdigit($name, $value, $alias = null, $params = null) {
	if (! ctype_digit($value))
		return "$value must be a valid digit";
	
	return UQL_RULE_SUCCESS;
}

?>