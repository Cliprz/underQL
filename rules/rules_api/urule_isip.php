<?php

function urule_isip($name, $value, $alias = null, $params = null) {
	if (! filter_var ( $value, FILTER_VALIDATE_IP ))
		return "$value is not a valid IP";
	
	return UQL_RULE_SUCCESS;
}
?>