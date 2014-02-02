<?php

/**
 * Template system
 */
class Template
{
	// Declaring core variables
		
	public $data = array();

	protected $_vars = array();
	protected $_bvars = array();
	protected $_files = array();

	// Constructor
	public function __construct($name, $dir)
	{
		// CONFIGURATION HIS HERE
		$this->data['cache'] = __DIR__.'/cache/';
		// END CONFIGURATION

		// Setting core variables
		$this->data['name'] = $name;
		$this->data['dir'] = $dir;
	}

	// Assign a variable in the template system
	public function assign_var($name, $value)
	{
		$this->_vars[$name] = $value;

		return true;
	}

	// Assign variables in the template system
	public function assign_vars(array $vars)
	{
		foreach ($vars as $name => $value)
		{
			$this->_vars[$name] = $value;
		}

		return true;
	}

	// Assign a block of variables in the template system
	public function block_assign_vars($block, array $vars)
	{
		if(!isset($this->_bvars[$block]))
		{
			$this->_bvars[$block] = array();
		}

		$this->_bvars[$block][] = $vars;

		return true;
	}

	public function set_name(array $value)
	{
		$this->_files = $value + $this->_files;

		return true;
	}

	public function display($name)
	{
		ob_start();
		$state = $this->_tpl_load($this->_files[$name]);
		ob_end_flush();
		return $state;
	}

	protected function _tpl_load($name)
	{
		$in_tpl_sys = true;

		if(file_exists($this->data['cache'].'tpl-'.$this->data['name'].'-'.$name.'.php')) {
			require($this->data['cache'].'tpl-'.$this->data['name'].'-'.$name.'.php');

			return true;
		}

		$tpl_data = $this->_tpl_compile($name);

		if($this->_tpl_cache($name, $tpl_data)) {
			require($this->data['cache'].'tpl-'.$this->data['name'].'-'.$name.'.php');
		}
		else
		{
			// echo $tpl_data;
			eval('?>'.$tpl_data);
		}

		return true;
	}

	protected function _tpl_compile($name)
	{
		if(!is_readable($this->data['dir'].$name))
		{
			trigger_error('Template->_tpl_compile(): File '.$this->data['dir'].$name.' is not found or not readable', E_USER_ERROR);
		}

		$tpl_data = file_get_contents($this->data['dir'].$name);

		if(preg_match('#<\?(php)?.+\?>#isU', $tpl_data))
		{
			$tpl_data = preg_replace('#<\?(php)?.+\?>#isU', '', $tpl_data);
			trigger_error('Template->_tpl_compile(): PHP code has been detected in the template and was removed during the compilation', E_USER_WARNING);
		}

		$tpl_data = '<?php if(!isset($in_tpl_sys))exit;?>'.$tpl_data;

		$tpl_data = preg_replace_callback('#\{(.+)\}#', array($this, '_compile_var_tags'), $tpl_data);

		$tpl_data = preg_replace('#<!-- INCLUDE ([a-zA-Z0-9._-]+) -->#U', '<?php $this->_tpl_include(\'$1\');?>', $tpl_data);

		$tpl_data = preg_replace_callback('#<!-- IF (.*?)? -->#', array($this, '_tpl_compile_if'), $tpl_data);
		$tpl_data = preg_replace_callback('#<!-- ELSE ?IF (.*?)? -->#', array($this, '_tpl_compile_elseif'), $tpl_data);

		$tpl_data = preg_replace('#<!-- ELSE -->#U', '<?php }else{?>', $tpl_data);
		$tpl_data = preg_replace('#<!-- ENDIF -->#U', '<?php }?>', $tpl_data);

		$tpl_data = preg_replace('#<!-- BEGIN ([a-zA-Z0-9._-]+) -->#U', '<?php $_$1_count=(isset($this->_bvars[\'$1\'])) ? sizeof($this->_bvars[\'$1\']) : 0;if ($_$1_count){for($_$1_i = 0; $_$1_i < $_$1_count; ++$_$1_i){$_$1_val=$this->_bvars[\'$1\'][$_$1_i];?>', $tpl_data);
		$tpl_data = preg_replace_callback('#<!-- BEGIN ?ELSE ?IF (.*?)? -->#', array($this, '_tpl_compile_beginelseif'), $tpl_data);
		$tpl_data = preg_replace('#<!-- BEGIN ?ELSE -->#U', '<?php }}else{{?>', $tpl_data);
		$tpl_data = preg_replace('#<!-- END ([a-zA-Z0-9._-]+) -->#U', '<?php }}?>', $tpl_data);

		$tpl_data = str_replace('?><?php ', '', $tpl_data);
		return preg_replace('#\?\>([\r\n])#', '?>\1\1', $tpl_data);
	}

	protected function _tpl_cache($name, $data)
	{
		if(!is_writable($this->data['cache']))
		{
			return false;
		}

		file_put_contents($this->data['cache'].'tpl-'.$this->data['name'].'-'.$name.'.php', $data, LOCK_EX);

		return true;
	}

	protected function _tpl_include($name)
	{
		if(array_key_exists($name, $this->_files))
		{
			return $this->_tpl_load($this->_files[$name]);
		}

		return $this->_tpl_load($name);
	}

	/*
	 * Fonction qui permet de compiler les tags-variables.
	 * Basé sur la fonction Template::compile_var_tags() de phpBB 3.0.11
	 */
	protected function _compile_var_tags($text_blocks)
	{
		$text_blocks = $text_blocks[0];

		// change template varrefs into PHP varrefs
		$varrefs = array();

		// This one will handle varrefs WITH namespaces
		preg_match_all('#\{((?:[a-z0-9\-_]+\.)+)(\$)?([A-Z0-9\-_]+)\}#', $text_blocks, $varrefs, PREG_SET_ORDER);

		foreach ($varrefs as $var_val)
		{
			$namespace = $var_val[1];
			$varname = $var_val[3];
			$new = $this->_generate_block_varref($namespace, $varname, true, $var_val[2]);

			$text_blocks = str_replace($var_val[0], $new, $text_blocks);
		}

		// Handle remaining varrefs
		$text_blocks = preg_replace('#\{([A-Z0-9\-_]+)\}#', "<?php echo (isset(\$this->_vars['\\1'])) ? \$this->_vars['\\1'] : '\\0';?>", $text_blocks);
		$text_blocks = preg_replace('#\{\$([A-Z0-9\-_]+)\}#', "<?php echo (isset(\$this->_bvars['DEFINE']['.']['\\1'])) ? \$this->_bvars['DEFINE']['.']['\\1'] : '\\0'; ?>", $text_blocks);

		return $text_blocks;
	}

	/*
	 * Generates a reference to the given variable inside the given (possibly nested)
	 * block namespace. This is a string of the form:
	 * ' . $this->_tpldata['parent'][$_parent_i]['$child1'][$_child1_i]['$child2'][$_child2_i]...['varname'] . '
	 *
	 * Based on function Template::generate_block_varref() of phpBB 3.0.11
	 */
	protected function _generate_block_varref($namespace, $varname, $echo = true, $defop = false)
	{
		// Strip the trailing period.
		$namespace = substr($namespace, 0, -1);

		// Get a reference to the data block for this namespace.
		$varref = $this->_generate_block_data_ref($namespace, true, $defop);
		// Prepend the necessary code to stick this in an echo line.

		// Append the variable reference.
		$varref .= "['$varname']";
		$varref = ($echo) ? "<?php echo (isset($varref)) ? $varref : ".'\'{'.$namespace.'.'.$varname.'}\';?>' : ((isset($varref)) ? $varref : '');

		return $varref;
	}

	/*
	 * Compile the tags IF and ELSEIF.
	 * Based on function Template::compile_tag_if() of phpBB 3.0.11
	 */
	protected function _tpl_compile_if(array $tag_args, $elseif = false)
	{
		// Tokenize args for 'if' tag.
		preg_match_all('/(?:
			"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"         |
			\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'     |
			[(),]                                  |
			[^\s(),]+)/x', $tag_args[1], $match);

		$tokens = $match[0];
		$is_arg_stack = array();

		for ($i = 0, $size = sizeof($tokens); $i < $size; $i++)
		{
			$token = &$tokens[$i];

			switch ($token)
			{
				case '!==':
				case '===':
				case '<<':
				case '>>':
				case '|':
				case '^':
				case '&':
				case '~':
				case ')':
				case ',':
				case '+':
				case '-':
				case '*':
				case '/':
				case '@':
				break;

				case '==':
				case 'eq':
					$token = '==';
				break;

				case '!=':
				case '<>':
				case 'ne':
				case 'neq':
					$token = '!=';
				break;

				case '<':
				case 'lt':
					$token = '<';
				break;

				case '<=':
				case 'le':
				case 'lte':
					$token = '<=';
				break;

				case '>':
				case 'gt':
					$token = '>';
				break;

				case '>=':
				case 'ge':
				case 'gte':
					$token = '>=';
				break;

				case '&&':
				case 'and':
					$token = '&&';
				break;

				case '||':
				case 'or':
					$token = '||';
				break;

				case '!':
				case 'not':
					$token = '!';
				break;

				case '%':
				case 'mod':
					$token = '%';
				break;

				case '(':
					array_push($is_arg_stack, $i);
				break;

				case 'is':
					$is_arg_start = ($tokens[$i-1] == ')') ? array_pop($is_arg_stack) : $i-1;
					$is_arg	= implode('	', array_slice($tokens,	$is_arg_start, $i -	$is_arg_start));

					$new_tokens	= $this->_parse_is_expr($is_arg, array_slice($tokens, $i+1));

					array_splice($tokens, $is_arg_start, sizeof($tokens), $new_tokens);

					$i = $is_arg_start;

				// no break

				default:
					if (preg_match('#^((?:[a-z0-9\-_]+\.)+)?(\$)?(?=[A-Z])([A-Z0-9\-_]+)#s', $token, $varrefs))
					{
						$token = (!empty($varrefs[1])) ? $this->_generate_block_data_ref(substr($varrefs[1], 0, -1), true, $varrefs[2]) . '[\'' . $varrefs[3] . '\']' : (($varrefs[2]) ? '$this->_bvars[\'DEFINE\'][\'.\'][\'' . $varrefs[3] . '\']' : '(isset($this->_vars[\'' . $varrefs[3] . '\']) && $this->_vars[\'' . $varrefs[3] . '\'])');
					}
					else if (preg_match('#^\.((?:[a-z0-9\-_]+\.?)+)$#s', $token, $varrefs))
					{
						// Allow checking if loops are set with .loopname
						// It is also possible to check the loop count by doing <!-- IF .loopname > 1 --> for example
						$blocks = explode('.', $varrefs[1]);

						// If the block is nested, we have a reference that we can grab.
						// If the block is not nested, we just go and grab the block from _tpldata
						if (sizeof($blocks) > 1)
						{
							$block = array_pop($blocks);
							$namespace = implode('.', $blocks);
							$varref = $this->_generate_block_data_ref($namespace, true);

							// Add the block reference for the last child.
							$varref .= "['" . $block . "']";
						}
						else
						{
							$varref = '$this->_bvars';

							// Add the block reference for the last child.
							$varref .= "['" . $blocks[0] . "']";
						}
						$token = "(isset($varref) && sizeof($varref))";
					}
					else if (!empty($token))
					{
						$token = '(' . $token . ')';
					}

				break;
			}
		}

		// If there are no valid tokens left or only control/compare characters left, we do skip this statement
		if (!sizeof($tokens) || str_replace(array(' ', '=', '!', '<', '>', '&', '|', '%', '(', ')'), '', implode('', $tokens)) == '')
		{
			$tokens = array('false');
		}
		return '<?php '.(($elseif) ? '}elseif(' : 'if(') . (implode(' ', $tokens) . '){').'?>';
	}

	protected function _tpl_compile_elseif($tag_args)
	{
		return $this->_tpl_compile_if($tag_args, true);
	}

	protected function _tpl_compile_beginelseif($tag_args)
	{
		return str_replace('}elseif(', '}}elseif(', $this->_tpl_compile_if($tag_args, true));
	}

	/*
	 * Based on function Template::_parse_is_expr() of phpBB 3.0.11
	 */
	protected function _parse_is_expr($is_arg, $tokens)
	{
		$expr_end = 0;
		$negate_expr = false;

		if (($first_token = array_shift($tokens)) == 'not')
		{
			$negate_expr = true;
			$expr_type = array_shift($tokens);
		}
		else
		{
			$expr_type = $first_token;
		}

		switch ($expr_type)
		{
			case 'even':
				if (@$tokens[$expr_end] == 'by')
				{
					$expr_end++;
					$expr_arg = $tokens[$expr_end++];
					$expr = "!(($is_arg / $expr_arg) % $expr_arg)";
				}
				else
				{
					$expr = "!($is_arg & 1)";
				}
			break;

			case 'odd':
				if (@$tokens[$expr_end] == 'by')
				{
					$expr_end++;
					$expr_arg = $tokens[$expr_end++];
					$expr = "(($is_arg / $expr_arg) % $expr_arg)";
				}
				else
				{
					$expr = "($is_arg & 1)";
				}
			break;

			case 'div':
				if (@$tokens[$expr_end] == 'by')
				{
					$expr_end++;
					$expr_arg = $tokens[$expr_end++];
					$expr = "!($is_arg % $expr_arg)";
				}
			break;
		}

		if ($negate_expr)
		{
			$expr = "!($expr)";
		}

		array_splice($tokens, 0, $expr_end, $expr);

		return $tokens;
	}

	/*
	 * Generates a reference to the array of data values for the given
	 * (possibly nested) block namespace. This is a string of the form:
	 * $this->_bvars['parent'][$_parent_i]['$child1'][$_child1_i]['$child2'][$_child2_i]...['$childN']
	 *
	 * Based on function Template::generate_block_data_ref() of phpBB 3.0.11
	 */
	protected function _generate_block_data_ref($blockname, $include_last_iterator, $defop = false)
	{
		// Get an array of the blocks involved.
		$blocks = explode('.', $blockname);
		$blockcount = sizeof($blocks) - 1;

		// DEFINE is not an element of any referenced variable, we must use _bvars to access it
		if ($defop)
		{
			$varref = '$this->_bvars[\'DEFINE\']';
			// Build up the string with everything but the last child.
			for ($i = 0; $i < $blockcount; $i++)
			{
				$varref .= "['" . $blocks[$i] . "'][\$_" . $blocks[$i] . '_i]';
			}
			// Add the block reference for the last child.
			$varref .= "['" . $blocks[$blockcount] . "']";
			// Add the iterator for the last child if requried.
			if ($include_last_iterator)
			{
				$varref .= '[$_' . $blocks[$blockcount] . '_i]';
			}
			return $varref;
		}
		else if ($include_last_iterator)
		{
			return '$_'. $blocks[$blockcount] . '_val';
		}
		else
		{
			return '$_'. $blocks[$blockcount - 1] . '_val[\''. $blocks[$blockcount]. '\']';
		}
	}
}
