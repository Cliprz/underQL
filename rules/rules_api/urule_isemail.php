<?php

function urule_isemail($name, $value, $alias = null, $params = null) {
	if (! filter_var ( $value, FILTER_VALIDATE_EMAIL ))
		return "$value is not a valid email";
	
	return UQL_RULE_SUCCESS;
}

?>