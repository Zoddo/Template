<?php

require_once 'template.class.php';

$tpl = new Template('test', './template/');

$tpl->assign_vars(array(
	'TEST_VAR'	=> '$value',
	'NUM'		=> '°',
	'TEST'		=> true,
	'TIME'		=> time(),
));

$tpl->block_assign_vars('block', array(
	'TEST1'	=> 1,
	'TEST2'	=> 2,
));

$tpl->block_assign_vars('block', array(
	'TEST1'	=> 5,
	'TEST2'	=> 3,
));

$tpl->set_name(array(
	'test'	=> 'simple.html',
));

$tpl->display('test');
