<?php

function urule_isurl($name, $value, $alias = null, $params = null) {
	if (! filter_var ( $value, FILTER_VALIDATE_URL ))
		return "$value is not a valid URL";
	
	return UQL_RULE_SUCCESS;
}
?>