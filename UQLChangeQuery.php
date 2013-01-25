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
    public function __destruct() {
        $this->um_query = null;
        $this->um_abstract_entity = null;
        $this->um_values_map = null;
        $this->um_rule_engine = null;
        $this->um_rule_engine_results = null;
    }

}
?>