<?php ini_set('display_errors',1);

require_once('underQL.mini.php');

$_('users');

$users->name = 'Saleh';
$users->password = 'lrkjq43';
$users->email = 'a@b.c';

$result = $users->_('insert');

if(!$result && $users->_('are_rules_passed'))
{
	$e = $users->_('get_error_messages');
	if($e->in('name'))
	{
		if($e->at('name','length'))
		 die($e->get('name','length'));
	}
}

$_->_('shutdown');

?>