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

define ( 'UQL_VERSION', '1.0.0' );
define ( 'UQL_VERSION_ID', 20120512 );

//define('UQL_VERSION_CODE_NAME','Eid');
define ( 'UQL_DIR_FILTER', 'filters/' );
define ( 'UQL_DIR_FILTER_API', 'filters_api/' );

define ( 'UQL_DIR_RULE', 'rules/' );
define ( 'UQL_DIR_RULE_API', 'rules_api/' );

define ( 'UQL_DIR_MODULE', 'modules/' );
//%s represents the table name
//define ('UQL_ABSTRACT_E_OBJECT_SYNTAX','the_%s_abstract');


define ( 'UQL_FILTER_IN', 0xA );
define ( 'UQL_FILTER_OUT', 0xC );

//%s represents table name
define ( 'UQL_FILTER_OBJECT_SYNTAX', '%s_filter' );
define ( 'UQL_FILTER_FUNCTION_NAME', 'ufilter_%s' );
//define ( 'UQL_FILTER_FILE_NAME', 'ufilter_%s' );

//%s represents table name
define ( 'UQL_RULE_OBJECT_SYNTAX', '%s_rule' );
define ( 'UQL_RULE_FUNCTION_NAME', 'urule_%s' );
//define ( 'UQL_RULE_FILE_NAME', 'urule_%s' );

define ( 'UQL_RULE_SUCCESS', 0x0D );

define ( 'UQL_ENTITY_OBJECT_SYNTAX', '%s' );

// %s represents module name
define ( 'UQL_MODULE_OBJECT_SYNTAX', '%s_module' );
define ( 'UQL_MODULE_CLASS_NAME', 'umodule_%s' );

/* Database connection information */
define ( 'UQL_DB_HOST', 'localhost' );
define ( 'UQL_DB_USER', 'root' );
define ( 'UQL_DB_PASSWORD', 'root' );
define ( 'UQL_DB_NAME', 'my' );
define ( 'UQL_DB_CHARSET', 'utf8' );

define ( 'UQL_CONFIG_USE_INVOKE_CALL', true );
// to use __invoke magic method

interface IUQLModule {

    public function init();
    public function in(&$values,$is_insert = true);
    public function out(&$path);
    public function shutdown();
}


class UQLBase {

    public static function underql_error($message) {
        die ( '<h3><code><b style = "color:#FF0000">UnderQL Error: </b>' . $message . '</h3> <br />' );
    }

    public static function underql_warning($message) {
        echo '<h3><code><b style = "color:#0000FF">UnderQL Warning: </b>' . $message . '</h3> <br />';
    }

    public function _() {

        $params_count = func_num_args ();
        if ($params_count < 1)
            UQLBase::underql_error ( '_ method accepts one parameter at least' );

        $params = func_get_args ();
        $func_name = 'underql_' . $params [0];
        if (! method_exists ( $this, $func_name ))
            UQLBase::underql_error ( $params [0] . ' is not a valid method' );
        $params = array_slice ( $params, 1 );
        return call_user_func_array ( array ($this, $func_name ), $params );
    }
}


class UQLModuleEngine extends UQLBase {


    public static function underql_module_run_input(&$values,$is_insert = true) {
        /* run modules */
        if(!$values || !is_array($values) || @count($values) == 0)
            return;

        if(isset($GLOBALS['uql_global_loaded_modules']) &&
                @count($GLOBALS['uql_global_loaded_modules']) != 0) {
            foreach($GLOBALS['uql_global_loaded_modules'] as $key => $module_name) {
                if(isset($GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)])
                        && $GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]->isActive()
                        && $GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]->isInput())
                    $GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]->in($values,$is_insert);
                //$this->um_values_map->underql_set_map($current_vals);
            }
        }
    }

    public static function underql_module_run_output(&$path) {
        /* run modules */
        if(!$path || ($path instanceof UQLQueryPath && $path->_('count') == 0))
            return;

        if(isset($GLOBALS['uql_global_loaded_modules']) &&
                @count($GLOBALS['uql_global_loaded_modules']) != 0) {
            foreach($GLOBALS['uql_global_loaded_modules'] as $key => $module_name) {
                if(isset($GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)])
                        && $GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]->isActive()
                        && $GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]->isOutput()) {
                    $GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]->out($path);
                    $path->_('reset');
                }
            }
        }
    }

    public static function underql_module_shutdown() {
        if(isset($GLOBALS['uql_global_loaded_modules']) &&
                @count($GLOBALS['uql_global_loaded_modules']) != 0) {
            foreach($GLOBALS['uql_global_loaded_modules'] as $key => $module_name) {
                if(isset($GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]))
                    $GLOBALS[sprintf(UQL_MODULE_OBJECT_SYNTAX,$module_name)]->shutdown();
            }
        }
    }
}


class UQLModule extends UQLBase {

    protected $um_module_name;
    protected $um_is_active;
    protected $um_is_input;
    protected $um_is_output;

    public function __construct($module_name,$is_active) {
        $this->um_module_name = $module_name;
        $this->um_is_active = $is_active;
        $this->um_is_input = true; // run (in) method if true
        $this->um_is_output = true; // run (out) method if true
    }

    public function stopModule() {
        $this->um_is_active = false;
    }

    public function restartModule() {
        $this->um_is_active = true;
    }

    public function isActive() {
        return $this->um_is_active;
    }

    public function useInput($use) {
        $this->um_is_input = $use;
    }

    public function useOutput($use) {
        $this->um_is_output = $use;
    }

    public function isInput() {
        return $this->um_is_input;
    }

    public function isOutput() {
        return $this->um_is_output;
    }

    public function __destruct() {
        $this->um_module_name = null;
        $this->um_is_active = false;
        $this->um_is_input = false;
        $this->um_is_output = false;
    }
}


class UQLConnection extends UQLBase {

    private $um_connection_handle;
    private $um_database_host;
    private $um_database_user_name;
    private $um_database_password;
    private $um_database_name;
    private $um_operations_charset;

    public function __construct($host, $database_name, $user = 'root', $password = '', $charset = 'utf8') {

        $this->um_database_host = $host;
        $this->um_database_name = $database_name;
        $this->um_database_user_name = $user;
        $this->um_database_password = $password;
        $this->um_operations_charset = $charset;
        $this->um_connection_handle = null;
        $this->underql_start_connection ();
    }

    public function underql_start_connection() {
        $this->um_connection_handle = mysql_connect ( $this->um_database_host, $this->um_database_user_name, $this->um_database_password );
        if (! $this->um_connection_handle) {
            UQLBase::underql_error ( 'Unable to connect' );
            return false;
        }

        $this->underql_set_database_name ( $this->um_database_name );

        $charset_query = sprintf ( "SET NAMES '%s'", $this->um_operations_charset );
        mysql_query ( $charset_query );
        return $this->um_connection_handle;
    }

    public function underql_restart_connection() {
        $this->underql_close_connection ();
        $this->underql_start_connection ();
    }

    public function underql_get_connection_handle() {
        return $this->um_connection_handle;
    }

    public function underql_set_database_host($host) {
        $this->um_database_host = $host;
    }

    public function underql_get_database_host() {
        return $this->um_database_host;
    }

    public function underql_set_database_name($db_name) {
        $this->um_database_name = $db_name;
        $result = mysql_select_db ( $this->um_database_name );
        if (! $result) {
            $this->underql_close_connection ();
            $this->underql_error ( 'Unable to select database' );
            return false;
        }

        return true;
    }

    public function underql_get_database_name() {
        return $this->um_database_name;
    }

    public function underql_set_database_user_name($user) {
        $this->um_database_user_name = $user;
    }

    public function underql_get_database_user_name() {
        return $this->um_database_user_name;
    }

    public function underql_set_database_password($password) {
        $this->um_database_password = $password;
    }

    public function underql_get_database_password() {
        return $this->um_database_password;
    }

    public function underql_set_database_charset($charset, $without_restart = false) {
        /* $without_restart : if true, run a query to change charset without need to restarting the connection*/
        $this->um_operations_charset = $charset;
        if ($without_restart) {
            $charset_query = sprintf ( "SET NAMES '%s'", $this->um_operations_charset );
            mysql_query ( $charset_query );
        }

    }

    public function underql_get_database_charset() {
        return $this->um_operations_charset;
    }

    public function underql_close_connection() {
        if ($this->um_connection_handle)
            mysql_close ( $this->um_connection_handle );

        $this->um_connection_handle = false;
    }

    public function __destruct() {
        $this->um_database_host = null;
        $this->um_database_name = null;
        $this->um_database_user_name = null;
        $this->um_database_password = null;
        $this->um_operations_charset = null;
        $this->um_connection_handle = null;
    }

}



class UQLMap extends UQLBase {

    private $um_map_list;
    private $um_elements_count;

    public function __construct() {
        $this->um_map_list = array ();
        $this->um_elements_count = 0;
    }

    public function underql_add_element($key, $value) {

        if ($this->underql_find_element ( $key ) == null)
            $this->um_elements_count ++;

        $this->um_map_list [$key] = $value;
    }

    public function underql_find_element($key) {
        if ($this->underql_is_element_exist ( $key ))
            return $this->um_map_list [$key];

        return null;
    }

    public function underql_is_element_exist($key) {
        if ($this->um_elements_count <= 0)
            return false;

        if (@array_key_exists ( $key, $this->um_map_list ))
            return true;

        return false;
    }

    public function underql_get_count() {
        return count ( $this->um_map_list );
    }

    public function underql_remove_element($key) {

        if ($this->underql_is_element_exist ( $key )) {
            unset ( $this->um_map_list [$key] );
            $this->um_elements_count --;
        }
    }

    public function underql_is_empty() {
        return $this->um_elements_count == 0;
    }

    public function underql_map_callback($callback) {
        if (! $this->underql_is_empty ())
            return array_map ( $callback, $this->map_list );
    }

    public function underql_get_map() {
        return $this->um_map_list;
    }

    public function underql_set_map($the_map) {
        if(is_array($the_map))
            $this->um_map_list = $the_map;
    }

    public function __destruct() {

        $this->um_map_list = null;
        $this->um_elements_count = 0;
    }

}


class UQLAbstractEntity extends UQLBase {

    private $um_entity_name;
    private $um_fields;
    private $um_fields_count;

    public function __construct($entity_name, &$database_handle) {

        $this->um_entity_name = null;
        $this->um_fields = null;
        $this->um_fields_count = 0;

        $this->underql_set_entity_name ( $entity_name, $database_handle );
    }

    public function underql_set_entity_name($entity_name, &$database_handle) {

        if (($database_handle instanceof UQLConnection)) {
            $this->um_entity_name = $entity_name;
            $string_query = sprintf ( "SHOW COLUMNS FROM `%s`", $this->um_entity_name );
            $query_result = mysql_query ( $string_query );
            if ($query_result) {
                $this->um_fields_count = mysql_num_rows ( $query_result );
                @mysql_free_result ( $query_result );

                $fields_list = mysql_list_fields ( $database_handle->underql_get_database_name (), $this->um_entity_name );
                $this->um_fields = array ();

                $i = 0;
                while ( $i < $this->um_fields_count ) {
                    $field = mysql_fetch_field ( $fields_list );
                    $this->um_fields [$field->name] = $field;
                    $i++;
                }

                @mysql_free_result ( $fields_list );
            } else {
                UQLBase::underql_error( mysql_error() );
            }
        }
    }

    public function underql_get_entity_name() {
        return $this->um_entity_name;
    }

    public function underql_is_field_exist($name) {
        return (($this->um_fields != null) && (array_key_exists ( $name, $this->um_fields )));
    }

    public function underql_get_field_object($name) {
        if ($this->underql_is_field_exist ( $name ))
            return $this->um_fields [$name];
        return null;
    }

    public function underql_get_all_fields() {
        return $this->um_fields;
    }

    public function underql_get_fields_count() {
        return $this->um_fields_count;
    }

    public function __destruct() {
        $this->um_entity_name = null;
        $this->um_fields = null;
        $this->um_fields_count = 0;
    }

}


class UQLFilter extends UQLBase {

    private $um_entity_name;
    private $um_filters_map;

    public function __construct($entity_name) {
        $this->um_entity_name = $entity_name;
        $this->um_filters_map = new UQLMap ();
    }

    public function __call($function_name, $parameters) {
        $local_params_count = count ( $parameters );
        if ($local_params_count < 1 /*filter_name [AT LEAST]*/)
            UQLBase::underql_error ( $function_name . ' filter accepts one parameter at lest' );

        if ($local_params_count == 1)
            $this->underql_add_filter ( $function_name, array ($parameters [0], 'inout' ) );
        else
            $this->underql_add_filter ( $function_name, $parameters );

        return $this;
    }

    protected function underql_add_filter($field, $filter) {
        if (! $this->um_filters_map->underql_is_element_exist ( $field ))
            $this->um_filters_map->underql_add_element ( $field, new UQLMap () );

        $local_filter = $this->um_filters_map->underql_find_element ( $field );
        $local_filter->underql_add_element ( $local_filter->underql_get_count (), array ('filter' => $filter, 'is_active' => true ) );
        $this->um_filters_map->underql_add_element ( $field, $local_filter );
    }

    protected function underql_set_filter_activation($field_name, $filter_name, $activation) {
        $local_filter = $this->um_filters_map->underql_find_element ( $field_name );
        if (! $local_filter)
            UQLBase::underql_error ( 'You can not stop a filter for unknown field (' . $field_name . ')' );

        for($i = 0; $i < $local_filter->underql_get_count (); $i ++) {
            $target_filter = $local_filter->underql_find_element ( $i );
            if (strcmp ( $target_filter ['filter'] [0], $filter_name ) == 0) {
                $target_filter ['is_active'] = $activation;
                $local_filter->underql_add_element ( $i, array ('filter' => $target_filter ['filter'], 'is_active' => $activation ) );
                $this->um_filters_map->underql_add_element ( $field_name, $local_filter );
            }
        }

    }

    public function underql_start_filters(/*$field_name,$filter_name*/) {
        $params_count = func_num_args ();
        if ($params_count < 2)
            UQLBase::underql_error ( 'start_filters accepts two parameters at least' );

        $filters_counts = $params_count - 1; // remove field name
        $parameters = func_get_args ();
        if ($filters_counts == 1) {
            $this->underql_set_filter_activation ( $parameters [0], $parameters [1], true );
            return;
        } else {
            for($i = 0; $i < $filters_counts - 1; $i ++)
                $this->underql_set_filter_activation ( $parameters [0], $parameters [$i + 1], true );
        }
    }

    public function underql_stop_filters(/*$field_name,$filter_name*/) {
        $params_count = func_num_args ();
        if ($params_count < 2)
            UQLBase::error ( 'stop_filters accepts two parameters at least' );

        $filters_counts = $params_count - 1; // remove field name
        $parameters = func_get_args ();
        if ($filters_counts == 1) {
            $this->underql_set_filter_activation ( $parameters [0], $parameters [1], false );
            return;
        } else {
            for($i = 0; $i < $filters_counts - 1; $i ++)
                $this->underql_set_filter_activation ( $parameters [0], $parameters [$i + 1], false );
        }
    }

    public function underql_get_filters_by_field_name($field_name) {
        return $this->um_filters_map->underql_find_element ( $field_name );
    }

    public function underql_get_filters() {
        return $this->um_filters_map;
    }

    public function underql_get_entity_name() {
        return $this->um_entity_name;
    }

    public static function underql_find_filter_object($entity) {
        $filter_object_name = sprintf ( UQL_FILTER_OBJECT_SYNTAX, $entity );
        if (isset ( $GLOBALS [$filter_object_name] ))
            $filter_object = $GLOBALS [$filter_object_name];
        else
            $filter_object = null;

        return $filter_object;
    }

    public function __destruct() {
        $this->um_entity_name = null;
        $this->um_filters_map = null;
    }
}


class UQLFilterEngine extends UQLBase {

    private $um_filter_object;
    private $um_values_map;
    //current inserted | updated $key => $value pairs
    private $um_in_out_flag;
    // specify if the engine for input or output


    public function __construct(&$filter_object, $in_out_flag) {
        $this->um_filter_object = $filter_object;
        $this->um_values_map = null;
        $this->um_in_out_flag = $in_out_flag;
    }

    public function underql_set_values_map(&$values_map) {
        $this->um_values_map = $values_map;
    }

    public function underql_apply_filter($field_name, $value) {
        if ($this->um_filter_object != null)
            $filters = $this->um_filter_object->underql_get_filters_by_field_name ( $field_name );
        else
            return $value;

        if ($filters == null)
            return $value;

        $tmp_value = $value;

        foreach ( $filters->underql_get_map () as $filter_id => $filter_value ) {
            $filter_name = $filter_value ['filter'] [0];
            $filter_flag = $filter_value ['filter'] [1];
            // echo $filter_flag;
            if (strcmp ( strtolower ( $filter_flag ), 'in' ) == 0)
                $filter_flag = UQL_FILTER_IN;
            else if (strcmp ( strtolower ( $filter_flag ), 'out' ) == 0)
                $filter_flag = UQL_FILTER_OUT;
            else
                $filter_flag = UQL_FILTER_IN | UQL_FILTER_OUT;

            if ((! $filter_value ['is_active']) || (($filter_flag != $this->um_in_out_flag) &&($filter_flag != (UQL_FILTER_IN | UQL_FILTER_OUT))))
                continue;

            $include_filter_api = 'include_filters';
            $include_filter_api ( $filter_name );

            $filter_api_function = sprintf ( UQL_FILTER_FUNCTION_NAME, $filter_name );

            if (! function_exists ( $filter_api_function ))
                die ( $filter_name . ' is not a valid filter' );

            if (@count ( $filter_value ['filter'] ) == 2) // the filter has no parameter(s)
                $tmp_value = $filter_api_function ( $field_name, $tmp_value, $filter_flag );
            else {
                $params = array_slice ( $filter_value ['filter'], 2 );
                $tmp_value = $filter_api_function ( $field_name, $tmp_value, $filter_flag, $params );
            }
        }
        return $tmp_value;
    }

    public function underql_run_engine() {
        if (! $this->um_values_map || $this->um_values_map->underql_get_count () == 0)
            return null;

        foreach ( $this->um_values_map->underql_get_map () as $name => $value ) {
            $this->um_values_map->underql_add_element ( $name, $this->underql_apply_filter ( $name, $value ) );
        }
        return $this->um_values_map;
    }

    public function __destruct() {
        $this->um_values_map = null;
        $this->um_filter_object = null;
    }

}


class UQLRule extends UQLBase {

    private $um_entity_name;
    private $um_alises_map;
    private $um_rules_map;

    public function __construct($entity_name) {

        $this->um_entity_name = $entity_name;
        $this->um_alises_map = new UQLMap ();
        $this->um_rules_map = new UQLMap ();
    }

    public function __call($function_name, $parameters) {

        $local_params_count = count ( $parameters );
        if ($local_params_count == 0)
            return;

        $this->underql_add_rule ( $function_name, $parameters );
        return $this;
    }

    protected function underql_add_rule($field, $rule) {

        if (! $this->um_rules_map->underql_is_element_exist ( $field ))
            $this->um_rules_map->underql_add_element ( $field, new UQLMap () );

        $local_rule = $this->um_rules_map->underql_find_element ( $field );
        $local_rule->underql_add_element ( $local_rule->underql_get_count (), array ('rule' => $rule, 'is_active' => true ) );

        $this->um_rules_map->underql_add_element ( $field, $local_rule );
    }

    protected function underql_set_rule_activation($field_name, $rule_name, $activation) {
        $local_rule = $this->um_rules_map->underql_find_element ( $field_name );

        if (! $local_rule)
            UQLBase::underql_error ( 'You can not stop a rule for unknown field (' . $field_name . ')' );

        for($i = 0; $i < $local_rule->underql_get_count (); $i ++) {
            $target_rule = $local_rule->underql_find_element ( $i );
            if (strcmp ( $target_rule ['rule'] [0], $rule_name ) == 0) {
                $target_rule ['is_active'] = $activation;
                $local_rule->underql_add_element ( $i, array ('rule' => $target_rule ['rule'], 'is_active' => $activation ) );
                $this->um_rules_map->underql_add_element ( $field_name, $local_rule );
            }
        }

    }

    public function underql_start_rules(/*$field_name,$rule_name*/) {
        $params_count = func_num_args ();
        if ($params_count < 2)
            UQLBase::underql_error ( 'start_rules accepts two parameters at least' );

        $rules_counts = $params_count - 1;
        // remove field name
        $parameters = func_get_args ();
        if ($rules_counts == 1) {
            $this->underql_set_rule_activation ( $parameters [0], $parameters [1], true );
            return;
        } else {
            for($i = 0; $i < $rules_counts - 1; $i ++)
                $this->underql_set_rule_activation ( $parameters [0], $parameters [$i + 1], true );
        }
    }

    public function underql_stop_rules(/*$field_name,$rule_name*/) {
        $params_count = func_num_args ();
        if ($params_count < 2)
            UQLBase::underql_error ( 'stop_rules accepts two parameters at least' );

        $rules_counts = $params_count - 1;
        $parameters = func_get_args ();
        if ($rules_counts == 1) {
            $this->underql_set_rule_activation ( $parameters [0], $parameters [1], false );
            return;
        } else {
            for($i = 0; $i < $rules_counts - 1; $i ++)
                $this->underql_set_rule_activation ( $parameters [0], $parameters [$i + 1], false );
        }
    }

    public function underql_get_rules_by_field_name($field_name) {

        return $this->um_rules_map->underql_find_element ( $field_name );
    }

    public function underql_add_alias($key, $value) {

        $this->um_alises_map->underql_add_element ( $key, $value );
        return $this;
    }

    public function underql_get_alias($key) {

        return $this->um_alises_map->underql_find_element ( $key );
    }

    public function underql_get_rules() {
        return $this->um_alises_map;
    }

    public function underql_get_entity_name() {
        return $this->um_entity_name;
    }

    public function underql_get_aliases() {
        return $this->um_alises_map;
    }

    public static function underql_find_rule_object($entity) {

        $rule_object_name = sprintf ( UQL_RULE_OBJECT_SYNTAX, $entity );

        if (isset ( $GLOBALS [$rule_object_name] ))
            $rule_object = $GLOBALS [$rule_object_name];
        else
            $rule_object = null;

        return $rule_object;

    }

    public function __destruct() {

        $this->um_entity_name = null;
        $this->um_rules_map = null;
        $this->um_alises_map = null;
    }

}



class UQLRuleMessagesHandler extends UQLBase {

    private $um_messages;

    public function __construct(&$the_msgs_list) {
        if (! $the_msgs_list)
            $this->um_messages = array ();
        else
            $this->um_messages = $the_msgs_list;
    }

    public function in($field_name) {
        return isset ( $this->um_messages [$field_name] );
    }

    public function at($field_name, $rule_name) {
        return isset ( $this->um_messages [$field_name] [$rule_name] ) ;
    }

    public function get($field_name, $rule_name) {
        return $this->um_messages [$field_name] [$rule_name];
    }

    public function __destruct() {
        $this->um_messages = null;
    }

}


class UQLRuleEngine extends UQLBase {

    private $um_rule_object;
    private $um_values_map;
    //current inserted | updated $key => $value pairs
    private $um_false_rule_flag;
    // true if there is at least one rule failed.
    private $um_fail_rules_list;
    // list of error messages about each field that fail in one or more rules


    public function __construct(&$rule_object, &$values_map) {

        $this->um_rule_object = $rule_object;
        $this->um_values_map = $values_map;
        $this->um_false_rule_flag = false;
        $this->um_fail_rules_list = new UQLMap ();
    }

    protected function underql_apply_rule($field_name, $value) {

        $rules = $this->um_rule_object->underql_get_rules_by_field_name ( $field_name );

        $the_results = array ();

        if ($rules == null)
		{
			$the_results[0] = true; /* There is no rules*/
            return $the_results;
		} 

        foreach ( $rules->underql_get_map () as $rule_id => $rule_value ) {

            if (! $rule_value ['is_active'])
                continue;

            $rule_name = $rule_value ['rule'] [0];
            $include_rule_api = 'include_rules';
            $include_rule_api ( $rule_name );

            $rule_api_function = sprintf ( UQL_RULE_FUNCTION_NAME, $rule_name );

            if (! function_exists ( $rule_api_function ))
                $this->underql_error ( $rule_name . ' is not a valid rule' );

            $alias = $this->um_rule_object->underql_get_alias ( $field_name );

            if (@count ( $rule_value ['rule'] ) == 1) // the rule has no parameter(s)
                $result = $rule_api_function ( $field_name, $value, $alias );
            else {
                $params = array_alice ( $rule_value ['rule'] );
                // remove rule name
                $result = $rule_api_function ( $field_name, $value, $alias, $params );
            }

            if ($result != UQL_RULE_SUCCESS) {
                $the_results [$rule_name] = $result;
                // message
                $this->um_false_rule_flag = true;
            } else
                $the_results [$rule_name] = true;//$result;

            // OK
        }

        return $the_results;
    }

    public function underql_are_rules_passed() {
        return $this->um_false_rule_flag == false;
    }

    public function underql_run_engine() {

        if (! $this->um_values_map || $this->um_values_map->underql_get_count () == 0)
            return true;

        $result = true;

        foreach ( $this->um_values_map->underql_get_map () as $name => $value ) {

            $result = $this->underql_apply_rule ( $name, $value );
		   
			foreach($result as $key => $val){
                	//echo $key. '=>'. $val. '<br />';
                if ($val == 1 /*!= UQL_RULE_SUCCESS*/)
				  continue;
		        
		        $this->um_fail_rules_list->underql_add_element ( $name, $result );
			}
        }

        if ($this->underql_are_rules_passed ())
            return true;

        $the_map = $this->um_fail_rules_list->underql_get_map ();
		//echo '<pre>'.var_dump($the_map).'</pre>';
        return new UQLRuleMessagesHandler ( $the_map );
    }

    public function __destruct() {
        $this->um_values_map = null;
        $this->um_rule_object = null;
    }

}


class UQLQuery extends UQLBase {

    private $um_database_handle;
    private $um_query_result;
    private $um_current_row_object;
    private $um_current_query_fields;

    public function __construct(&$database_handle) {
        $this->um_database_handle = (($database_handle instanceof UQLConnection) ? $database_handle : null);
        $this->um_query_result = null;
        $this->um_current_row_object = null;
        $this->um_current_query_fields = array ();
    }

    public function underql_set_database_handle($database_handle) {
        $this->underql_database_handle ( ($database_handle instanceof UQLConnection) ? $database_handle : null );
    }

    public function underql_get_database_handle() {
        return $this->um_database_handle;
    }

    public function underql_execute_query($query) {
        if ($this->um_database_handle instanceof UQLConnection) {
            $this->um_query_result = mysql_query ( $query /*,$this -> database_handle*/);

            $this->underql_is_there_any_error ();

            if (! $this->um_query_result)
                return false;

            return true;
        }

        return false;
    }

    public function underql_get_current_query_fields() {
        if (! $this->um_query_result)
            return null;

        $local_fields_count = @mysql_num_fields ( $this->um_query_result );
        if ($local_fields_count == 0)
            return null;

        for($local_i = 0; $local_i < $local_fields_count; $local_i ++)
            $this->um_current_query_fields [$local_i] = mysql_field_name ( $this->um_query_result, $local_i );

        return $this->um_current_query_fields;
    }

    public function underql_fetch_row() {
        if ($this->um_query_result) {
            $this->um_current_row_object = mysql_fetch_object ( $this->um_query_result );
            return $this->um_current_row_object;
        }

        return false;
    }

    public function underql_reset_result() {
        if ($this->um_query_result)
            return mysql_data_seek ( $this->um_query_result, 0 );

        return false;
    }

    public function underql_get_current_row() {
        return $this->um_current_row_object;
    }

    public function underql_get_count() {
        if ($this->um_query_result)
            return mysql_num_rows ( $this->um_query_result );

        return 0;
    }

    public function underql_get_affected_rows() {
        if (($this->um_database_handle instanceof UQLConnection) && ($this->um_query_result))
            return mysql_affected_rows ( $this->um_database_handle );

        return 0;
    }

    public function underql_get_last_inserted_id() {
        if (($this->um_database_handle instanceof UQLConnection) && ($this->um_query_result))
            return mysql_insert_id ( $this->um_database_handle );

        return 0;
    }

    public function underql_free_result() {
        if ($this->um_query_result)
            @mysql_free_result ( $this->um_query_result );

        $this->um_current_row_object = null;
        $this->um_query_result = null;
        $this->um_current_query_fields = array ();
    }

    public function underql_is_there_any_error() {
        if (mysql_errno () != 0)
            UQLBase::underql_error ( '[MySQL EROROR - ' . mysql_errno () . '] - ' . mysql_error () );
    }

    public function __destruct() {
        $this->underql_free_result ();
        $this->um_query_result = null;
        $this->um_current_query_fields = null;
        $this->um_current_row_object = null;
        $this->um_database_handle = null;
    }

}



class UQLQueryPath extends UQLBase {

    public $um_abstract_entity;
    public $um_query_object;
    public $um_filter_engine;

    public function __construct(&$database_handle, &$abstract_entity) {

        if ($abstract_entity instanceof UQLAbstractEntity)
            $this->um_abstract_entity = $abstract_entity;
        else
            UQLBase::underql_error ( 'You must provide a appropriate value for abstract_entity parameter' );

        $this->um_query_object = new UQLQuery ( $database_handle );
        $filter_object = UQLFilter::underql_find_filter_object ( $this->um_abstract_entity->underql_get_entity_name () );
        $this->um_filter_engine = new UQLFilterEngine ( $filter_object, UQL_FILTER_OUT );
    }

    public function underql_execute_query($query) {

        if ($this->um_query_object->underql_execute_query ( $query )) {
            UQLModuleEngine::underql_module_run_output($this);
            // $this->underql_reset();
            return true;
        }

        return false;
    }

    public function underql_fetch() {
        return $this->um_query_object->underql_fetch_row ();
    }

    public function underql_reset() {
        return $this->um_query_object->underql_reset_result();
    }

    public function underql_count() {
        return $this->um_query_object->underql_get_count ();
    }

    public function underql_query_object() {
        return $this->um_query_object;
    }

    public function underql_abstract_entity() {
        return $this->um_abstract_entity;
    }

    public function underql_fields() {
        return $this->um_query_object->underql_get_current_query_fields ();
    }

    public function underql_field_info($field_name) {
        return $this->um_abstract_entity->underql_get_field_object($field_name);
    }

    public function underql_entity_name() {
        return $this->um_abstract_entity->underql_get_entity_name();
    }

    public function __get($key) {

        if (! $this->um_abstract_entity->underql_is_field_exist ( $key ))
            UQLBase::underql_error ( "[$key] does not exist in ".$this->um_abstract_entity->underql_get_entity_name());

        $current_query_fields = $this->underql_fields();//$this->um_query_object->underql_get_current_query_fields ();
        if ($current_query_fields == null)
            UQLBase::underql_error ( "[$key] does not exist in the current query fields" );

        foreach ( $current_query_fields as $field_name ) {
            if (strcmp ( $key, $field_name ) == 0) {
                $current_row = $this->um_query_object->underql_get_current_row ();
                if ($current_row == null)
                    return null;
                else {
                    return $this->um_filter_engine->underql_apply_filter ( $key, $current_row->$key );
                }
            }
        }

        return null;
    }

    public function __destruct() {

        $this->um_abstract_entity = null;
        $this->um_query_object = null;

        //$this->plugin = null;
    }

}



class UQLChangeQuery extends UQLBase {

    private $um_query;
    private $um_abstract_entity;
    private $um_values_map;
    private $um_rule_engine;
    private $um_rule_engine_results;

    public function __construct(&$database_handle, &$abstract_entity) {
        if ((! $database_handle instanceof UQLConnection) || (! $abstract_entity instanceof UQLAbstractEntity))
            UQLBase::underql_error ( 'Invalid database handle' );

        $this->um_query = new UQLQuery ( $database_handle );
        $this->um_abstract_entity = $abstract_entity;
        $this->um_values_map = new UQLMap ();
        $this->um_rule_engine = null;
        $this->um_rule_engine_results = null;
    }

    public function __set($name, $value) {
        if (! $this->um_abstract_entity->underql_is_field_exist ( $name ))
            UQLBase::underql_error ( $name . ' is not a valid column name' );

        $this->um_values_map->underql_add_element ( $name, $value );
    }

    public function __get($name) {

        if (! $this->um_abstract_entity->underql_is_field_exist ( $name ))
            UQLBase::underql_error ( $name . ' is not a valid column name' );

        if (! $this->um_values_map->underql_is_element_exist ( $name ))
            return null;
        else
            return $this->um_values_map->underql_find_element ( $name );

    }

    public function underql_are_rules_passed() {
        if ($this->um_rule_engine != null)
            return $this->um_rule_engine->underql_are_rules_passed ();

        return true;
    }

    public function underql_get_messages_list() {
        if (($this->um_rule_engine != null) || ($this->um_rule_engine_results == true))
            return $this->um_rule_engine_results;

        return null;

    }

    protected function underql_format_insert_query() {
        $values_count = $this->um_values_map->underql_get_count ();
        if ($values_count == 0)
            return "";

        $insert_query = 'INSERT INTO `' . $this->um_abstract_entity->underql_get_entity_name () . '` (';

        $fields = '';
        $values = 'VALUES(';

        $all_values = $this->um_values_map->underql_get_map ();
        $comma = 0;
        // for last comma in a string


        foreach ( $all_values as $key => $value ) {
            $fields .= "`$key`";
            $field_object = $this->um_abstract_entity->underql_get_field_object ( $key );
            if ($field_object->numeric)
                $values .= $value;
            else // string quote
                $values .= "'$value'";

            $comma ++;

            if (($comma) < $values_count) {
                $fields .= ',';
                $values .= ',';
            }
        }

        $values .= ')';

        $insert_query .= $fields . ') ' . $values;
        return $insert_query;
    }

    public function underql_check_rules() {
        $rule_object = UQLRule::underql_find_rule_object ( $this->um_abstract_entity->underql_get_entity_name () );

        if ($rule_object != null) {
            $this->um_rule_engine = new UQLRuleEngine ( $rule_object, $this->um_values_map );

            $this->um_rule_engine_results = $this->um_rule_engine->underql_run_engine ();

            return $this->um_rule_engine->underql_are_rules_passed ();
        }

        return true; // No rules applied
    }

    protected function underql_insert_or_update($is_save = true, $extra = '') {
        $values_count = $this->um_values_map->underql_get_count ();
        if ($values_count == 0)
            return false;

        if(!$this->underql_check_rules())
            return false;

        $filter_object = UQLFilter::underql_find_filter_object ( $this->um_abstract_entity->underql_get_entity_name () );

        if ($filter_object != null) {
            $fengine = new UQLFilterEngine ( $filter_object, UQL_FILTER_IN );
            $fengine->underql_set_values_map ( $this->um_values_map );
            $this->um_values_map = $fengine->underql_run_engine ();
        }

        if ($is_save) {
            $vals = $this->um_values_map->underql_get_map();
            UQLModuleEngine::underql_module_run_input($vals);
            $this->um_values_map->underql_set_map($vals);
            $query = $this->underql_format_insert_query ();

        }
        else {
            $vals = $this->um_values_map->underql_get_map();
            UQLModuleEngine::underql_module_run_input($vals,false);
            $this->um_values_map->underql_set_map($vals);
            $query = $this->underql_format_update_query ( $extra );
        }

        // clear values
        $this->um_values_map = new UQLMap ();

        return $this->um_query->underql_execute_query ( $query );
    }

    public function underql_insert() {
        return $this->underql_insert_or_update ();
    }

    protected function underql_format_update_query($extra = '') {
        $values_count = $this->um_values_map->underql_get_count ();
        if ($values_count == 0)
            return "";

        $update_query = 'UPDATE `' . $this->um_abstract_entity->underql_get_entity_name () . '` SET ';

        $fields = '';

        $all_values = $this->um_values_map->underql_get_map ();
        $comma = 0;
        // for last comma in a string


        foreach ( $all_values as $key => $value ) {
            $fields .= "`$key` = ";
            $field_object = $this->um_abstract_entity->underql_get_field_object ( $key );
            if ($field_object->numeric)
                $fields .= $value;
            else // string quote
                $fields .= "'$value'";

            $comma ++;

            if (($comma) < $values_count) {
                $fields .= ',';
            }
        }

        $update_query .= $fields . ' ' . $extra;

        return $update_query;
    }

    public function underql_update($extra = '') {
        return $this->underql_insert_or_update ( false, $extra );
    }

    public function underql_update_where_n($field_name,$value) {
        $field_object = $this->um_abstract_entity->underql_get_field_object($field_name);
        if($field_object != null) {
            if($field_object->numeric)
                return $this->underql_update("WHERE `$field_name` = $value");
            else
                return $this->underql_update("WHERE `$field_name` = '$value'");
        }

        return false;
    }
	
	public function underql_get_last_inserted_id()
	{
		return $this->um_query->underql_get_last_inserted_id();
	}
	
	public function underql_get_affected_rows() {
        return $this->um_query->underql_get_affected_rows();
    }
	
    public function __destruct() {
        $this->um_query = null;
        $this->um_abstract_entity = null;
        $this->um_values_map = null;
        $this->um_rule_engine = null;
        $this->um_rule_engine_results = null;
    }

}



class UQLDeleteQuery extends UQLBase {

    private $um_query;
    private $um_abstract_entity;

    public function __construct(&$database_handle, &$abstract_entity) {
        if ((! $database_handle instanceof UQLConnection) || (! $abstract_entity instanceof UQLAbstractEntity))
            UQLBase::underql_error ( 'Invalid database handle' );

        $this->um_query = new UQLQuery ( $database_handle );
        $this->um_abstract_entity = $abstract_entity;
    }

    protected function underql_format_delete_query($extra = null) {

        $delete_query = 'DELETE FROM `' . $this->um_abstract_entity->underql_get_entity_name () . '`';
        if ($extra != null)
            $delete_query .= ' ' . $extra;

        return $delete_query;
    }

    public function underql_delete($extra = '') {
        $query = $this->underql_format_delete_query ( $extra );
        return $this->um_query->underql_execute_query ( $query );
    }

    public function underql_delete_where_n($field_name,$value) {
        $field_object = $this->um_abstract_entity->underql_get_field_object($field_name);
        if($field_object != null) {
            if($field_object->numeric)
                return $this->underql_delete("WHERE `$field_name` = $value");
            else
                return $this->underql_delete("WHERE `$field_name` = '$value'");
        }

        return false;
    }

    public function __destruct() {
        $this->um_query = null;
        $this->um_abstract_entity = null;
    }

}



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
			if ($field_object -> numeric)
				return $this -> underql_select($fields, "WHERE `$field_name` = $value");
			else
				return $this -> underql_select($fields, "WHERE `$field_name` = '$value'");
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



function include_filters() {
    $params = func_get_args ();

    if (func_num_args () == 0)
        UQLBase::underql_error ( 'You must pass one filter at least to include_filters' );

    foreach ( $params as $key => $filter )
        require_once (__DIR__ . '/' . UQL_DIR_FILTER . UQL_DIR_FILTER_API . 'ufilter_' . $filter . '.php');
}

function include_rules() {
    $params = func_get_args ();

    if (func_num_args () == 0)
        UQLBase::underql_error ( 'You must pass one rule at least to include_rules' );

    foreach ( $params as $key => $rule )
        require_once (__DIR__ . '/' . UQL_DIR_RULE . UQL_DIR_RULE_API . 'urule_' . $rule . '.php');
}

function include_modules() {
    $params = func_get_args ();

    if (func_num_args () == 0)
        UQLBase::underql_error ( 'You must pass one module at least to include_modules' );

    foreach ( $params as $key => $module_name ) {
        if(!isset($GLOBALS [sprintf ( UQL_MODULE_OBJECT_SYNTAX, $module_name )])) {
            require_once (__DIR__ . '/' . UQL_DIR_MODULE .$module_name.'/'. 'umodule_' . $module_name . '.php');
            _m($module_name);
        }
    }
}

function _f($entity_name) {

    if(isset($GLOBALS [sprintf ( UQL_FILTER_OBJECT_SYNTAX, $entity_name )]))
        return $GLOBALS [sprintf ( UQL_FILTER_OBJECT_SYNTAX, $entity_name )];

    $GLOBALS [sprintf ( UQL_FILTER_OBJECT_SYNTAX, $entity_name )] = new UQLFilter ( $entity_name );
    return $GLOBALS [sprintf ( UQL_FILTER_OBJECT_SYNTAX, $entity_name )];
}

function _r($entity_name) {
    if(isset($GLOBALS [sprintf ( UQL_RULE_OBJECT_SYNTAX, $entity_name )]))
        return $GLOBALS [sprintf ( UQL_RULE_OBJECT_SYNTAX, $entity_name )];

    $GLOBALS [sprintf ( UQL_RULE_OBJECT_SYNTAX, $entity_name )] = new UQLRule ( $entity_name );
    return $GLOBALS [sprintf ( UQL_RULE_OBJECT_SYNTAX, $entity_name )];
}

function _m($module_name) {

    if(isset($GLOBALS [sprintf ( UQL_MODULE_OBJECT_SYNTAX, $module_name )]))
        return $GLOBALS [sprintf ( UQL_MODULE_OBJECT_SYNTAX, $module_name )];

    $module_class_name = sprintf(UQL_MODULE_CLASS_NAME,$module_name);
    $GLOBALS [sprintf ( UQL_MODULE_OBJECT_SYNTAX, $module_name )] = new $module_class_name ($module_name,true);
    $GLOBALS [sprintf ( UQL_MODULE_OBJECT_SYNTAX, $module_name )]->init();

    /* used to shutdown all modules */
    $GLOBALS ['uql_global_loaded_modules'][] = $module_name;

    return $GLOBALS [sprintf ( UQL_MODULE_OBJECT_SYNTAX, $module_name )];
}


class underQL extends UQLBase {

    private $um_database_handle;
    private $um_entity_list;
    // load all tables' names from current database
    private $um_loaded_entity_list;

    public function __construct($host = UQL_DB_HOST, $database_name = UQL_DB_NAME, $user = UQL_DB_USER, $password = UQL_DB_PASSWORD, $charset = UQL_DB_CHARSET) {

        /* check if we could use __invoke method syntax with underQL object or not */
        if (UQL_CONFIG_USE_INVOKE_CALL) {
            $php_ver = floatval ( PHP_VERSION );
            if ($php_ver < 5.3)
                UQLBase::underql_error ( 'underQL needs at least PHP 5.3' );
        }

        $this->um_database_handle = new UQLConnection ( $host, $database_name, $user, $password, $charset );
        $this->underql_entity_list_init ();
        $this->um_loaded_entity_list = array ();
    }

    public function underql_get_database() {
        return $this->um_database_handle;
    }

    /* read all tables(entities) from current database and store them into array */
    protected function underql_entity_list_init() {

        $local_string_query = sprintf ( "SHOW TABLES FROM `%s`", $this->um_database_handle->underql_get_database_name () );
        $local_query_result = mysql_query ( $local_string_query/*, $this->um_database_handle -> getConnectionHandle()*/);
        if ($local_query_result) {
            $tables_count = mysql_num_rows ( $local_query_result );

            while ( $local_entity = mysql_fetch_row ( $local_query_result ) ) {
                $this->um_entity_list [] = $local_entity [0];
            }

            @mysql_free_result ( $local_query_result );

        } else {
            UQLBase::underql_error ( mysql_error(/*$this->um_database_handle -> getConnectionHandle()*/) );
        }
    }

    /* create UQLEntity object and load all information about
     $entity_name table within it */
    public function underql_load_entity($entity_name) {

        if (strcmp ( $entity_name, '*' ) == 0) {
            $this->underql_load_all_entities ();
            return;
        }

        if (! in_array ( $entity_name, $this->um_entity_list ))
            UQLBase::underql_error ( $entity_name . ' is not a valid table name' );

        if (in_array ( $entity_name, $this->um_loaded_entity_list ))
            return;

        // no action


        /* Create a global entity object. This part helps underQL to know
         the entity's object name for any loaded entity(table), therefore, underQL
         could automatically use it in its own operations. */

        //sprintf ( UQL_ENTITY_OBJECT_SYNTAX, $entity_name );
        $GLOBALS [sprintf ( UQL_ENTITY_OBJECT_SYNTAX, $entity_name )] = new UQLEntity ( $entity_name, $this->um_database_handle );

    }

    /* You can load all tables as objects at once by use * symbol. This function
     used to do that. */
    public function underql_load_all_entities() {
        $entity_count = @count ( $this->um_entity_list );
        for($i = 0; $i < $entity_count; $i ++)
            $this->underql_load_entity ( $this->um_entity_list [$i] );
    }

    /* Helps underQL to use (object as function) syntax. However, this method used
     only with PHP 5.3.x and over */
    public function __invoke($entity_name) {
        $this->underql_load_entity ( $entity_name );
    }

    public function underql_shutdown() {
        UQLModuleEngine::underql_module_shutdown();
        $this->um_database_handle->underql_close_connection();
    }

    public function __destruct() {
        $this->um_database_handle = null;
        $this->um_entity_list = null;
        $this->um_loaded_entity_list = null;
    }

}

/* Create underQL (this object called 'under') object. This is the default object, but
 you can create another instance if you would like to deal with another database
 by specifying the parameters for that database. However, you can change the name
 of the ($_) 'under' object, but it is unpreferable(might be for future purposes).
*/
$_ = new underQL ();
?>