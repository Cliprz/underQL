<?php

function urule_ishexa($name, $value, $alias = null, $params = null) {
	if(!ctype_xdigit($value))
		return "$value is not a proper hexadecimal value";
		//return UnderQL.rules.errors['ishexa'];
		//UQLRoot.rules.errors.ishexa
	return UQL_RULE_SUCCESS;
}
?>