<?php

/****************************************************************************************
 * Copyright (c) 2012, Abdullah E. Almehmadi - www.abdullaheid.net                      *
 * All rights reserved.                                                                 *
 ****************************************************************************************
 Redistribution and use in source and binary forms, with or without modification,
 are permitted provided that the following conditions are met:

 Redistributions of source code must retain the above copyright notice, this list of
 conditions and the following disclaimer.

 Redistributions in binary form must reproduce the above copyright notice, this list
 of conditions and the following disclaimer in the documentation and/or other materials
 provided with the distribution.

 Neither the name of the underQL nor the names of its contributors may be used to
 endorse or promote products derived from this software without specific prior written
 permission.

 THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL
 THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT
 OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *****************************************************************************************/

class UQLEntity extends UQLBase {

	private $um_abstract_entity;
	private $um_database_handle;
	private $um_path;
	private $um_change;
	private $um_delete;

	public function __construct($entity_name, &$database_handle) {

		$this -> um_abstract_entity = new UQLAbstractEntity($entity_name, $database_handle);
		$this -> um_database_handle = $database_handle;
		$this -> um_path = null;
		$this -> um_change = new UQLChangeQuery($database_handle, $this -> um_abstract_entity);
		$this -> um_delete = new UQLDeleteQuery($database_handle, $this -> um_abstract_entity);
	}

	public function __set($name, $value) {
		$this -> um_change -> $name = $value;

	}

	public function __get($name) {
		return $this;
	}

	public function _() {

		$params_count = func_num_args();
		if ($params_count < 1)
			UQLBase::underql_error('_ method accepts one parameter at least');

		$params = func_get_args();
		$func_name = 'underql_' . $params[0];
		if (!method_exists($this, $func_name)) {

			foreach ($this->um_abstract_entity->_('get_all_fields') as $field_name => $info_object) {
				$select_method_name = 'select_where_' . $field_name;
				$delete_method_name = 'delete_where_' . $field_name;
				$update_method_name = 'update_where_' . $field_name;
				$update_from_array_method_name = 'update_from_array_where_' . $field_name;

				$function_name = $params[0];
				if (strcmp($function_name, $update_method_name) == 0) {
					$params = array_slice($params, 1);
					if (!is_array($params) || count($params) != 1)
						UQLBase::underql_error("$function_name accepts one parameter");

					return $this -> um_change -> underql_update_where_n($field_name, $params[0]);
				} else if (strcmp($function_name, $delete_method_name) == 0) {
					$params = array_slice($params, 1);
					if (!is_array($params) || count($params) != 1)
						UQLBase::underql_error("$function_name accepts one parameter");

					return $this -> um_delete -> underql_delete_where_n($field_name, $params[0]);
				} else if (strcmp($function_name, $select_method_name) == 0) {
					$params = array_slice($params, 1);
					if (is_array($params) && count($params) == 1)
						return $this -> underql_select_where_n($field_name, $params[0]);
					else if (is_array($params) && count($params) == 2)
						return $this -> underql_select_where_n($field_name, $params[0], $params[1]);

					UQLBase::underql_error("$function_name accepts one parameter");

				} else if (strcmp($function_name, $update_from_array_method_name) == 0) {
					$params = array_slice($params, 1);
					if (!is_array($params) || count($params) != 2)
						UQLBase::underql_error("$function_name accepts two parameters");

					return $this -> underql_update_from_array_where_n($params[0], $field_name, $params[1]);
				}
			}

			UQLBase::underql_error($params[0] . ' is not a valid method');
		}
		$params = array_slice($params, 1);
		return call_user_func_array(array($this, $func_name), $params);
	}

	public function underql_insert() {
		return $this -> um_change -> underql_insert();
	}

	public function underql_check_rules() {
		return $this -> um_change -> underql_check_rules();
	}

	public function underql_insert_or_update_from_array($the_array, $extra = '', $is_save = true) {

		foreach ($the_array as $key => $value) {
			if ($this -> um_abstract_entity -> underql_is_field_exist($key))
				$this -> $key = $value;
		}

		if ($is_save)
			return $this -> underql_insert();
		else
			return $this -> underql_update($extra);
	}

	public function underql_insert_from_array($the_array) {
		return $this -> underql_insert_or_update_from_array($the_array, null);
	}

	public function underql_update_from_array($the_array, $extra = '') {
		return $this -> underql_insert_or_update_from_array($the_array, $extra, false);
	}

	public function underql_update_from_array_where_n($the_array, $field_name, $value) {
		$field_object = $this -> um_abstract_entity -> underql_get_field_object($field_name);
		if ($field_object != null) {
			if ($field_object -> numeric)
				return $this -> underql_insert_or_update_from_array($the_array, "WHERE `$field_name` = $value", false);
			else
				return $this -> underql_insert_or_update_from_array($the_array, "WHERE `$field_name` = '$value'", false);
		}

		return false;
	}

	public function underql_update($extra = '') {
		return $this -> um_change -> underql_update($extra);
	}

	public function underql_delete($extra = '') {
		return $this -> um_delete -> underql_delete($extra);
	}

	public function underql_query($query) {

		$this -> um_path = new UQLQueryPath($this -> um_database_handle, $this -> um_abstract_entity);
		if ($this -> um_path -> underql_execute_query($query))
			return $this -> um_path;

		return false;
	}

	public function underql_select($fields = '*', $extra = '') {
		$query = sprintf("SELECT %s FROM `%s` %s", $fields, $this -> um_abstract_entity -> underql_get_entity_name(), $extra);

		return $this -> underql_query($query);
	}

	protected function underql_select_where_n($field_name, $value, $fields = '*') {
		$field_object = $this -> um_abstract_entity -> underql_get_field_object($field_name);
		if ($field_object != null) {
			
			  $val = strip_tags($value);
			  $val = @mysql_real_escape_string($val);
			  
			if ($field_object -> numeric)
				return $this -> underql_select($fields, "WHERE `$field_name` = $val");
			else
				return $this -> underql_select($fields, "WHERE `$field_name` = '$val'");
		}

		return false;
	}

	public function underql_are_rules_passed() {
		return $this -> um_change -> underql_are_rules_passed();
	}

	public function underql_get_messages_list() {
		return $this -> um_change -> underql_get_messages_list();
	}

	public function underql_get_abstract_entity() {
		return $this -> um_abstract_entity;
	}

	public function underql_get_last_inserted_id() {
		return $this -> um_change -> underql_get_last_inserted_id();
	}

	public function underql_get_affected_rows() {
		return $this -> um_change -> underql_get_affected_rows();
	}

	public function __destruct() {
		$this -> um_abstract_entity = null;
		$this -> um_database_handle = null;
		$this -> um_path = null;
		$this -> um_change = null;
		$this -> um_delete = null;
	}

}
?>