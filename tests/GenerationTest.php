<?php

require_once dirname(__FILE__) . '/template_test_base.php';

class GenerationTest extends PHPUnit_Framework_TestCase
{
	/**
	  @expectedException PHPUnit_Framework_Error_Warning
	 */
	public function test_php()
	{
		$tpl = new Template_test_base;
		$tpl->set_name(array(
			'body'	=> 'php.html'
		));
		$tpl->generate('body');
	}
	
	/**
	  @expectedException PHPUnit_Framework_Error
	 */
	public function test_unreadable_file()
	{
		$tpl = new Template_test_base;
		$tpl->set_name(array(
			'body'	=> 'do_not_exists.html'
		));
		$tpl->generate('body');
	}
	
	public function test_display()
	{
		$tpl = new Template_test_base;
		$tpl->set_name(array(
			'body'	=> 'body.html',
			'page2'	=> 'page2.html'
		));
		$tpl->assign_vars(array(
			'TITLE'	=> 'Page title',
			'DESC'	=> 'Page description'
		));
		
		for($i = 1; $i <= 5; $i++)
		{
			$tpl->block_assign_vars('block', array(
				'SUBJECT'	=> 'Loop ' . $i,
				'MESSAGE'	=> 'This is a loop ' . $i,
				'LOOP'		=> $i
			));
			for($ii = 1; $ii <= 3; $ii++)
			{
				$tpl->block_assign_vars('block.sub', array(
					'SUBJECT'	=> 'Subloop ' . $ii,
					'MESSAGE'	=> 'This is a Subloop ' . $ii . ' of the loop '
				));
			}
		}
		
		$this->expectOutputString(file_get_contents(dirname(__FILE__) . '/GenerationTest_ouput.txt'));
		$tpl->display('body');
	}
}