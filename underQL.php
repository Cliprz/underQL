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

require_once ('UQL.php');
require_once ('IUQLModule.php');
require_once ('UQLBase.php');
require_once ('UQLModuleEngine.php');
require_once ('UQLModule.php');
require_once ('UQLConnection.php');
require_once ('UQLMap.php');
require_once ('UQLAbstractEntity.php');
require_once ('UQLFilter.php');
require_once ('UQLFilterEngine.php');
require_once ('UQLRule.php');
require_once ('UQLRuleMessagesHandler.php');
require_once ('UQLRuleEngine.php');
require_once ('UQLQuery.php');
require_once ('UQLQueryPath.php');
require_once ('UQLChangeQuery.php');
require_once ('UQLDeleteQuery.php');
require_once ('UQLEntity.php');
require_once ('utilities.php');

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