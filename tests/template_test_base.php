<?php

require_once dirname(__FILE__) . '/../template.class.php';

class Template_test_base extends Template
{
	public function __construct()
	{
		parent::__construct('test', dirname(__FILE__) . '/templates/');
	}
	
	public function __get($name)
	{
		return $this->{$name};
	}
}