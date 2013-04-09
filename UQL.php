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
define ( 'UQL_DB_PASSWORD', '' );
define ( 'UQL_DB_NAME', 'my' );
define ( 'UQL_DB_CHARSET', 'utf8' );

define ( 'UQL_CONFIG_USE_INVOKE_CALL', true );
// to use __invoke magic method
?>