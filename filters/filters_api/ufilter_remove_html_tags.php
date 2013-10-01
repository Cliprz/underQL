<?php

function ufilter_remove_html_tags($name, $value, $in_out, $params = null) {
	if ($in_out == UQL_FILTER_IN) {
		if ($params != null)
			return strip_tags ( $value, $params [0] );
		else
			return strip_tags ( $value );
	} else
		return $value;
}
?>