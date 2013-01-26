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
            return true;

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
			
			foreach($result as $key => $value){
            if ($value != true /*!= UQL_RULE_SUCCESS*/)
                $this->um_fail_rules_list->underql_add_element ( $name, $result );
			}
        }

        if ($this->underql_are_rules_passed ())
            return true;

        $the_map = $this->um_fail_rules_list->underql_get_map ();
        return new UQLRuleMessagesHandler ( $the_map );
    }

    public function __destruct() {
        $this->um_values_map = null;
        $this->um_rule_object = null;
    }

}
?>