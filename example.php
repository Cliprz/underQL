<?php ini_set('display_errors',1);

require_once('underQL.php');


_f('users')->email('md5');

$_('users');

$result = $users->_('select','*');
//$users->_('select_where_id')

//$select('id,name')->from->users->where->id('=',10)->and->limit->asis('1,2')->

//$smart->select('*')->from('student')->where->id('> 10')->and->name("!= 'salem'")

$_->_('shutdown');


?>