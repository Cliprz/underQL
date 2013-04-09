<?php ini_set('display_errors',1);

require_once('underQL.mini.php');


require_once('users_entity.php');
require_once('categories_entity.php');
require_once('videos_entity.php');

_f('users')->name('md5')
           ->name('trim')
           ->name('sqli')
		   ->password('md5','in');
		   
		   
_r('users')->name('ishex')
           ->name('isurl')
		   ->password('md5');
		   
$users_rule   ->_('add_alias','name','اسم المستخدم')
		      ->_('add_alias','password','كلمة المرور');
		   		   
		   
$_('users');

$users->name = 'Saleh';
$users->password = 'lrkjq43';
$users->email = 'a@b.c';

$result = $users->_('update_where_id',$_GET['id']);

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