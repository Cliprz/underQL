<?php

function urule_isemail($name, $value, $alias = null, $params = null) {
	if (! filter_var ( $value, FILTER_VALIDATE_EMAIL ))
		 return $value.' لا يمثل بريد إلكتروني صحيح';
	return UQL_RULE_SUCCESS;
}

?>