<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Parser Class for ExpressionEngine2
 *
 * @package  ExpressionEngine
 * @subpackage Fieldtypes
 * @category Fieldtypes
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2011 Max Lazar
 * @Commercial - please see LICENSE file included with this distribution
 */
 

class Parser
{
	/**
	 * @var string
	 */
	protected $_source;

	/**
	 * @var array
	 */
	protected $_tree;

	/**
	 * @var array
	 */
	protected $_tagData = array();

	/**
	 * @var string|array
	 */
	protected $_callback;

	/**
	 * @param string $tplSource
	 * @return Parser
	 */
	public function setTemplate($tplSource)
	{
		$this->_source = $tplSource;
		$this->_tree   = NULL;

		return $this;
	}

	/**
	 * @param array $data
	 * @return Parser
	 */
	public function setData(array $tagData)
	{
		// If tags were changed ...
		if (array_diff_key($tagData, $this->_tagData) !== array_diff_key($this->_tagData, $tagData)) {
			$this->_tree = NULL;
		}

		$this->_tagData = $tagData;

		return $this;
	}

	/**
	 * @param string|array $callback
	 * @return Parser
	 */
	public function setCallback($callback)
	{
		$this->_callback = $callback;

		return $this;
	}


	/**
	 * @return string
	 */
	public function parse(array $tagData = NULL)
	{
		if ($tagData !== NULL) {
			$this->setData($tagData);
		}

		if ($this->_tree === NULL) {
			$lexer = new Lexer;
			$lexer->setSourceCode($this->_source)->setTags(array_keys($this->_tagData));
			$this->_tree = $this->_getTree($lexer->getSyntax());


		}

		return $this->_getOutput($this->_tree);
	}

	/**
	 * @param array $syntax
	 * @return array
	 */
	protected function _getTree(array $syntax)
	{
		$structure = $this->_normilizeBracket($syntax);

		$result     = array();
		$resultKey  = -1;
		$openedTags = array();
		$valKey     = -1;

		foreach ($structure as $val) {
			switch ($val['type']) {
			case 'text':
				if (!$val['level']) {
					$result[++$resultKey] = array(
						'type' => 'text',
						'str' => $val['str']
					);
					break;
				}
				$openedTags[$val['level'] - 1]['val'][] = array(
					'type' => 'text',
					'str' => $val['str']
				);
				break;
			case 'open/close':
				if (!$val['level']) {
					$result[++$resultKey] = array(
						'type' => 'tag',
						'name' => $val['name'],
						'sub' => $val['sub'],
						'attrib' => $val['attrib'],
						'val' => array()
					);
					break;
				}
				$openedTags[$val['level'] - 1]['val'][] = array(
					'type' => 'tag',
					'name' => $val['name'],
					'sub' => $val['sub'],
					'attrib' => $val['attrib'],
					'val' => array()
				);
				break;
			case 'open':
				$openedTags[$val['level']] = array(
					'type' => 'tag',
					'name' => $val['name'],
					'sub' => $val['sub'],
					'attrib' => $val['attrib'],
					'val' => array()
				);
				break;
			case 'close':
				if (!$val['level']) {
					$result[++$resultKey] = $openedTags[0];
					unset($openedTags[0]);
					break;
				}
				$openedTags[$val['level'] - 1]['val'][] = $openedTags[$val['level']];
				unset($openedTags[$val['level']]);
				break;
			}
		}

		return $result;
	}

	/**
	 * @param array $syntax
	 * @return array
	 */
	protected function _normilizeBracket(array $syntax)
	{
		$structure    = array();
		$structureKey = -1;
		$level        = 0;
		$openedTags   = array();

		foreach ($syntax as $val) {
			$type = ($structureKey >= 0) ? $structure[$structureKey]['type'] : false;

			switch ($val['type']) {
			case 'text':
				if ($type === 'text') {
					$structure[$structureKey]['str'] .= $val['str'];
				} else {
					$structure[++$structureKey]        = $val;
					$structure[$structureKey]['level'] = $level;
				}
				break;

			case 'open':
				$structure[++$structureKey]        = $val;
				$structure[$structureKey]['level'] = $level++;
				$openedTags[$structureKey]         = $val['name'];
				break;

			case 'close':
				if (count($openedTags) === 0) {
					if ($type === 'text') {
						$structure[$structureKey]['str'] .= $val['str'];
					} else {
						$structure[++$structureKey] = array(
							'type' => 'text',
							'str' => $val['str'],
							'level' => 0
						);
					}
					break;
				}

				if (!in_array($val['name'], $openedTags)) {
					if ($type === 'text') {
						$structure[$structureKey]['str'] .= $val['str'];
					} else {
						$structure[++$structureKey] = array(
							'type' => 'text',
							'str' => $val['str'],
							'level' => $level
						);
					}
					break;
				}

				$rev = array_reverse($openedTags, true);

				foreach ($rev as $k => $v) {
					if ($v === $val['name']) {
						break;
					}

					$structure[$k]['type'] = 'open/close';
					--$level;
				}

				unset($openedTags[$k]);

				$correction = 0;

				for ($i = $k + 1; $i < count($structure); $i++) {
					$structure[$i]['level'] -= $correction;

					if (isset($openedTags[$i])) {
						$correction++;
						$structure[$i]['type'] = 'open/close';
						unset($openedTags[$i]);
					}
				}

				$structure[++$structureKey]        = $val;
				$structure[$structureKey]['level'] = --$level;

				break;
			}
		}

		$correction = 0;

		for ($i = 0; $i < count($structure); $i++) {
			$structure[$i]['level'] -= $correction;

			if (isset($openedTags[$i])) {
				$correction++;
				$structure[$i]['type'] = 'open/close';
			}
		}

		return $structure;
	}

	protected function _getOutput(array $subtree)
	{
		$result = '';

		foreach ($subtree as $val) {
			if ($val['type'] === 'text') {
				$result .= $val['str'];
			} else {
				if (!empty($val['val'])) {
					$data = $this->_getOutput($val['val']);
				} else {
					$data = '';
				}

				$result .= call_user_func_array($this->_callback, array(
						$val['name'],
						$val['sub'],
						$val['attrib'],
						$data,
						$this->_tagData[$val['name']]
					));
			}
		}

		return $result;
	}
}

class Lexer
{
	/**
	 * @var string
	 */
	protected $_source;

	/**
	 *
	 * @var array
	 */
	protected $_syntax = array();

	/**
	 * @var int
	 */
	protected $_cursor;

	/**
	 * @var array
	 */
	protected $_tags = array();

	/**
	 * @var int
	 */
	protected $_mode;

	/**
	 * @param string
	 *
	 * @return Lexer
	 */
	public function setSourceCode($source)
	{
		$this->_source = $source;

		return $this;
	}

	/**
	 * @param array
	 *
	 * @return Lexer
	 */
	public function setTags(array $tags)
	{
		$this->_tags = $tags;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getSyntax()
	{
		$faMatrix = array(
			//  0   1   2   3   4   5   6   7   8
			0 => array(
				1,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				0
			),
			1 => array(
				3,
				2,
				4,
				2,
				2,
				2,
				2,
				2,
				5
			),
			2 => array(
				1,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				0
			),
			3 => array(
				3,
				2,
				4,
				2,
				2,
				2,
				2,
				2,
				5
			),
			4 => array(
				3,
				2,
				2,
				2,
				2,
				2,
				2,
				2,
				7
			),
			5 => array(
				3,
				6,
				2,
				2,
				8,
				2,
				9,
				2,
				2
			),
			6 => array(
				1,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				0
			),
			7 => array(
				3,
				6,
				2,
				2,
				2,
				2,
				2,
				2,
				2
			),
			8 => array(
				3,
				6,
				2,
				2,
				2,
				2,
				2,
				15,
				15
			),
			9 => array(
				3,
				2,
				2,
				2,
				2,
				2,
				2,
				10,
				2
			),
			10 => array(
				3,
				2,
				2,
				12,
				2,
				2,
				11,
				2,
				2
			),
			11 => array(
				3,
				2,
				2,
				12,
				2,
				2,
				2,
				2,
				2
			),
			12 => array(
				3,
				2,
				2,
				2,
				2,
				14,
				13,
				2,
				2
			),
			13 => array(
				3,
				2,
				2,
				2,
				2,
				14,
				2,
				2,
				2
			),
			14 => array(
				3,
				6,
				2,
				2,
				2,
				2,
				9,
				2,
				2
			),
			15 => array(
				3,
				6,
				2,
				2,
				2,
				2,
				9,
				2,
				2
			)
		);

		$this->_cursor = 0;
		$this->_mode   = 0;
		$tokenKey      = -1;
		$decomposition = array();

		while ($token = $this->_getToken()) {
			$this->_mode = $faMatrix[$this->_mode][$token['type']];

			$type = ($tokenKey >= 0) ? $this->_syntax[$tokenKey]['type'] : false;

			switch ($this->_mode) {
			case 0:
				if ($type === 'text') {
					$this->_syntax[$tokenKey]['str'] .= $token['str'];
				} else {
					$this->_syntax[++$tokenKey] = array(
						'type' => 'text',
						'str' => $token['str']
					);
				}
				break;

			case 1:
				$decomposition = array(
					'name' => '',
					'sub' => '',
					'type' => '',
					'str' => '{',
					'attrib' => array()
				);
				break;

			case 2:
				if ($type === 'text') {
					$this->_syntax[$tokenKey]['str'] .= $decomposition['str'] . $token['str'];
				} else {
					$this->_syntax[++$tokenKey] = array(
						'type' => 'text',
						'str' => $decomposition['str'] . $token['str']
					);
				}
				$decomposition = array();
				break;

			case 3:
				if ($type === 'text') {
					$this->_syntax[$tokenKey]['str'] .= $decomposition['str'];
				} else {
					$this->_syntax[++$tokenKey] = array(
						'type' => 'text',
						'str' => $decomposition['str']
					);
				}

				$decomposition = array(
					'name' => '',
					'sub' => '',
					'type' => '',
					'str' => '{'
				);
				break;

			case 4:
				$decomposition['type'] = 'close';
				$decomposition['str'] .= '/';
				break;

			case 5:
				$decomposition['type'] = 'open';
				$decomposition['name'] = strtolower($token['str']);
				$decomposition['str'] .= $token['str'];
				break;

			case 6:
				$decomposition['str'] .= '}';
				$this->_syntax[++$tokenKey] = $decomposition;
				$decomposition              = array();
				break;

			case 7:
				$decomposition['name'] = strtolower($token['str']);
				$decomposition['str'] .= $token['str'];
				break;

			case 8:
				$decomposition['str'] .= ':';
				break;

			case 9:
				$decomposition['str'] .= $token['str'];
				break;

			case 10:
				$name = strtolower($token['str']);
				$decomposition['str'] .= $token['str'];
				$decomposition['attrib'][$name] = '';
				break;

			case 11:
			case 12:
			case 13:
				$decomposition['str'] .= $token['str'];
				break;

			case 14:
				$decomposition['str'] .= $token['str'];
				$quotationMark                  = $token['str'][0];
				$decomposition['attrib'][$name] = strtr(substr($token['str'], 1, -1), array(
						'\\\\' => '\\',
						'\\' . $quotationMark => $quotationMark
					));
				break;

			case 15:
				$decomposition['sub'] = $token['str'];
				$decomposition['str'] .= $token['str'];
				break;
			}
		}

		if (count($decomposition)) {
			if ($type === 'text') {
				$this->_syntax[$tokenKey]['str'] .= $decomposition['str'];
			} else {
				$this->_syntax[++$tokenKey] = array(
					'type' => 'text',
					'str' => $decomposition['str']
				);
			}
		}

		return $this->_syntax;
	}

	/**
	 * @return array
	 */
	protected function _getToken()
	{
		$token    = array(
			'str' => '',
			'type' => false
		);
		$charType = false;

		while (true) {
			$token['type'] = $charType;

			if (!isset($this->_source[$this->_cursor])) {
				if ($token['type'] === false) {
					return false;
				} else {
					break;
				}
			}

			switch ($this->_source[$this->_cursor]) {
			case '{':
				$charType = 0;
				break;
			case '}':
				$charType = 1;
				break;
			case '/':
				$charType = 2;
				break;
			case '=':
				$charType = 3;
				break;
			case ':':
				$charType = 4;
				break;

			case '"':
			case '\'':

				if ($this->_isOutOfTag()) {
					$charType = 7;
					break;
				}

				if ($token['type'] !== false) {
					$charType = 5;
					break;
				}

				if ($this->_source[$this->_cursor] === '"') {
					$pattern = '/"(\\\\.|[^"\\\\])*"/';
				} else {
					$pattern = "/'(\\\\.|[^'\\\\])*'/";
				}

				// Cheating
				if (preg_match($pattern, substr($this->_source, $this->_cursor), $matches)) {
					$this->_cursor += strlen($matches[0]);

					return array(
						'str' => $matches[0],
						'type' => 5
					);
				} else {
					$charType = 7;
				}

				break;

			case "\n":
			case ' ':
			case "\t":
			case "\r":
				$charType = 6;
				break;

			default:
				$charType = 7;
				break;
			}

			if ($token['type'] === false) {
				$token['str'] = $this->_source[$this->_cursor];
			} elseif ($this->_isOutOfTag() && $token['type'] > 0 && $charType > 0) {
				$token['str'] .= $this->_source[$this->_cursor];
				$charType = 7;
			} elseif ($token['type'] < 6) {
				break;
			} elseif ($token['type'] === $charType) {
				$token['str'] .= $this->_source[$this->_cursor];
			} else {
				break;
			}

			$this->_cursor++;
		}

		if (in_array(strtolower($token['str']), $this->_tags)) {
			$token['type'] = 8;
		}

		return $token;
	}

	protected function _isOutOfTag()
	{
		return $this->_mode === 0;
	}
}