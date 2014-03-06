<?php

require_once dirname(__FILE__) . '/template_test_base.php';

class VarsTest extends PHPUnit_Framework_TestCase
{
	public function test_assign_var()
	{
		$tpl = new Template_test_base;
		
		$tpl->assign_var('test', 'test3');
		$tpl->assign_var('test2', 'test');
		$tpl->assign_var('test', 'test1');
		
		$this->assertEquals($tpl->_vars, array(
			'test2'	=> 'test',
			'test'	=> 'test1'
		));
	}
	
	public function test_assign_vars()
	{
		$tpl = new Template_test_base;
		
		$tpl->assign_vars(array('test' => 'test3', 'test2' => 'test', 'test' => 'test1'));
		$tpl->assign_vars(array('test' => 'test6', 'test4' => 'test5'));
		
		$this->assertEquals($tpl->_vars, array(
			'test2'	=> 'test',
			'test'	=> 'test6',
			'test4'	=> 'test5'
		));
		
		return $tpl;
	}

	/**
	 * @depends test_assign_vars
	 */
	public function test_destroy_var(Template_test_base $tpl)
	{
		$tpl->destroy_var('test2');
		$this->assertEquals($tpl->_vars, array(
			'test'	=> 'test6',
			'test4'	=> 'test5'
		));
		
		$tpl->destroy_var('test');
		$this->assertEquals($tpl->_vars, array(
			'test4'	=> 'test5'
		));
		
		$tpl->destroy_var('test4');
		$this->assertEquals($tpl->_vars, array());
	}
	
	public function test_block_assign_vars()
	{
		$tpl = new Template_test_base;
		
		$tpl->block_assign_vars('test', array(
			'test2'	=> 'test',
			'test'	=> 'test6',
			'test4'	=> 'test5'
		));
		$this->assertEquals($tpl->_bvars, array(
			'test'	=> array(
					0	=> array(
						'test2'			=> 'test',
						'test'			=> 'test6',
						'test4'			=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true,
						'S_LAST_ROW'	=> true
					)
			)
		));
		
		$tpl->block_assign_vars('test', array(
			'test2'	=> 'test1',
			'test'	=> 'test6',
			'test4'	=> 'test5'
		));
		$this->assertEquals($tpl->_bvars, array(
			'test'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true
					),
					1	=> array(
						'test2'	=> 'test1',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 1,
						'S_LAST_ROW'	=> true
					)
			)
		));
		
		$tpl->block_assign_vars('test2', array(
			'test2'	=> 'test',
			'test'	=> 'test6',
			'test4'	=> 'test5'
		));
		$this->assertEquals($tpl->_bvars, array(
			'test'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true
					),
					1	=> array(
						'test2'	=> 'test1',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 1,
						'S_LAST_ROW'	=> true
					)
			),
			'test2'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true,
						'S_LAST_ROW'	=> true
					)
			)
		));
		
		$tpl->block_assign_vars('test.test8', array(
			'test2'	=> 'test',
			'test'	=> 'test6',
			'test4'	=> 'test5'
		));
		$this->assertEquals($tpl->_bvars, array(
			'test'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true
					),
					1	=> array(
						'test2'	=> 'test1',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 1,
						'S_LAST_ROW'	=> true,
						'test8'	=> array(
								0	=> array(
									'test2'	=> 'test',
									'test'	=> 'test6',
									'test4'	=> 'test5',
									'S_ROW_COUNT'	=> 0,
									'S_FIRST_ROW'	=> true,
									'S_LAST_ROW'	=> true
								)
						)
					)
			),
			'test2'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true,
						'S_LAST_ROW'	=> true
					)
			)
		));
		
		return $tpl;
	}
	
	/**
	 * @depends test_block_assign_vars
	 */
	public function test_destroy_block_vars(Template_test_base $tpl)
	{
		$tpl->destroy_block_vars('test.test8');
		$this->assertEquals($tpl->_bvars, array(
			'test'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true
					),
					1	=> array(
						'test2'	=> 'test1',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 1,
						'S_LAST_ROW'	=> true
					)
			),
			'test2'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true,
						'S_LAST_ROW'	=> true
					)
			)
		));
		
		$tpl->destroy_block_vars('test');
		$this->assertEquals($tpl->_bvars, array(
			'test2'	=> array(
					0	=> array(
						'test2'	=> 'test',
						'test'	=> 'test6',
						'test4'	=> 'test5',
						'S_ROW_COUNT'	=> 0,
						'S_FIRST_ROW'	=> true,
						'S_LAST_ROW'	=> true
					)
			)
		));
		
		$tpl->destroy_block_vars('test2');
		$this->assertEquals($tpl->_bvars, array());
	}
}