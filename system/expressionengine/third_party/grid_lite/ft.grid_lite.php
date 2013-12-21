<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Grid Lite Class for ExpressionEngine2
 *
 * @package  ExpressionEngine
 * @subpackage Fieldtypes
 * @category Fieldtypes
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2011 Max Lazar
 *
 */

class Grid_lite_ft extends EE_Fieldtype
{
	var $info = array('name' => 'Grid Lite', 'version' => '1.7.1');

	var $prefix = 'grid_lite_';

	var $column_fields = array();

	var $ft;

	var $_tmp_columns_settings = array();

	var $has_array_data = TRUE;



	public function __construct()
	{
		parent::__construct();


		require_once(PATH_THIRD . '/grid_lite/library/grid_custom_ft' . EXT);
		require_once(PATH_THIRD . '/grid_lite/library/parser' . EXT);

		$this->ft = new Grid_custom_ft();
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field on Publish
	 *
	 * @access public
	 * @param existing data
	 * @return field html
	 *
	 */

	function display_field($data)
	{
		$this->EE->lang->loadfile('grid_lite');


		$columns = $this->_column_fields();

		if (!isset($this->EE->session->cache[__CLASS__]['js']))
		{
			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/css/main.css" />');
			$this->EE->cp->add_to_head('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/js/jquery.main.js"></script>');


			/* !DELETE OLD VERSION SUPPORT */
			if (version_compare(APP_VER, '2.2', '>='))
			{
				$this->EE->cp->add_to_head('<script type="text/javascript"> EE22 = true; </script>');
			};

			$this->EE->session->cache[__CLASS__]['js'] = TRUE;
		}

		if(isset($this->EE->safecracker_lib)) {
			$this->EE->cp->add_js_script(array('ui' => array('sortable', 'tabs')));
		}

		$data = ($data != "") ? $this->_unserialize($data, TRUE) : FALSE;

		$out = '';


		$out .= ' <table class="grid_lite" border="0" cellpadding="0" cellspacing="0" id ="' . $this->field_name . '-table" data-limit="'.((empty($this->settings[$this->prefix . 'max_row']) or $this->settings[$this->prefix . 'max_row'] == '') ? '' : $this->settings[$this->prefix . 'max_row']).'">
							<thead>
								<tr class="grid_lite-first grid_lite-last"><th class="index"></th>';
		for ($i = 0; $i < $columns; $i++)
		{
			$out .= '<th width="' . ((isset($this->column_fields[$i]['width'])) ? $this->column_fields[$i]['width'] : '') . '">' . ((isset($this->column_fields[$i]['label'])) ? $this->column_fields[$i]['label'] : 'col_' . $i) . '</th>';
		}


		$out .= '</tr></thead><tbody  class="dand" rel="' . $this->field_name . '">';


		$row_id = 1;

		if ($data)
		{
			foreach ($data as $row)
			{
				$out .= '<tr class="gl_rows"> <td class="first dand-target grid_cell" data-type="index">' . $row_id . '</td>';

				for ($i = 0; $i < $columns; $i++)
				{
					$out .= '<td class="grid_cell" data-type="'.$this->column_fields[$i]['type'].'">' . $this->ft->fieldtype($this->field_name, $this->column_fields[$i], $row, $i, $row_id, $this->column_fields[$i]['key']) . '</td>';
				}

				$out .= '	</tr>';
				$row_id = $row_id + 1;
			}
		} else
		{
			$out .= '<tr><td colspan="' . ($columns + 1) . '">' . $this->EE->lang->line('no_rows') . '</td></tr>';
		}

		$out .= '</tbody></table>';
		$row      = array();
		$template = '	<tr class="gl_rows"> <td class="first dand-target grid_cell" data-type="index">{row_id}</td>';

		for ($i = 0; $i < $columns; $i++)
		{
			$template .= '<td class="grid_cell" data-type="'.$this->column_fields[$i]['type'].'">' . $this->ft->fieldtype($this->field_name, $this->column_fields[$i], $row, $i, "{row_id}", $this->column_fields[$i]['key']) . '</td>';
		}

		$template .= '	</tr>';

		$template = preg_replace('/[\r\n\t]/', '', $template);
		$template = str_replace("'", '"', $template);

		$js = '
			jQuery("#' . $this->field_name . '-table").data("template", \'' . $template . '\');
			jQuery("#' . $this->field_name . '-table").data("rows",  ' . $row_id . ');

		';

		$this->EE->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');

		$out .= '<a class="grid_lite-btn grid_lite-add" title="' . $this->EE->lang->line('add_row') . '" rel="' . $this->field_name . '"><img src="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/images/add.png">  ' . $this->EE->lang->line('add_row') . '</a>';

		$out .= '
<div id="menu_gl" class="GridLiteMenu">
	<li class="edit">
        <a href="#insert_above">Insert 1 above</a>
    </li>
	<li class="edit">
        <a href="#insert_below">Insert 1 below</a>
    </li>

    <li class="cut separator">
        <a href="#delete">Delete</a>
    </li>
</div>';

		return $out;
	}


	/**
	 * _column_fields function.
	 *
	 * @access private
	 * @return void
	 */
	function _column_fields()
	{
		if (isset($this->settings[$this->prefix . 'col']))
		{
			$this->settings[$this->prefix . 'col'] = unserialize($this->settings[$this->prefix . 'col']);

			$i = 0;
			foreach ($this->settings[$this->prefix . 'col'] as $key => $val)
			{
				$this->column_fields[$i]['key']      = $key;
				$this->column_fields[$i]['label']    = $val['label'];
				$this->column_fields[$i]['type']     = $val['type'];
				$this->column_fields[$i]['width']    = $val['width'];
				$this->column_fields[$i]['settings'] = (isset($val['settings'])) ? $val['settings'] : '';
				$i++;
			}
			;
		}
		return $i;
	}



	/**
	 * save function.
	 *
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	function save($data)
	{
		$out = '';
		if ($data)
		{
			$columns = $this->_column_fields();

			foreach ($data as $key => $row)
			{
				for ($i = 0; $i < $columns; $i++)
				{
					$cell_data                                   = $row[$this->column_fields[$i]['key']];
					$data[$key][$this->column_fields[$i]['key']] = $this->ft->ft_save($this->column_fields[$i]['type'], (($cell_data)));
				}
			}

			if (is_array($data))
			{
				$out = base64_encode(serialize($data));
			}
		}
		return $out;
	}





	/**
	 * _unserialize function.
	 *
	 * @access private
	 * @param mixed $data
	 * @return void
	 */
	function _unserialize($data)
	{
		if (is_array($data))
		{
			return $data;
		}

		$data = base64_decode($data);

		return (FALSE === ($data = @unserialize($data))) ? array() : $data;
	}


	// --------------------------------------------------------------------



	/**
	 * replace_total_rows function.
	 *
	 * @access public
	 * @param mixed $data
	 * @param array $params (default: array())
	 * @param mixed $tagdata (default: FALSE)
	 * @return void
	 */
	function replace_total_rows($data, $params = array(), $tagdata = FALSE)
	{
		$data = ($data != "") ? $this->_unserialize($data, TRUE) : array('');

		$r = count($data);

		return $r;
	}


	/**
	 * Replace tag
	 *
	 * @access public
	 * @param field contents
	 * @return replacement text
	 *
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if (!$tagdata) return;

		$r = '';

		$absolute_count = 0;

		$limit			= (isset($params['limit'])) ? $params['limit'] : '9999';
		$offset			= (isset($params['offset'])) ? $params['offset'] : '0';
		$sort			= (isset($params['sort'])) ? $params['sort'] : FALSE;
		$row_ids		= (isset($params['row_id'])) ? $params['row_id'] : FALSE;
		$row_id			= 0;

		if ($tagdata !== FALSE and !empty($data))
		{
			$data = ($data != "") ? $this->_unserialize($data, TRUE) : array('');

			$columns_settings = unserialize($this->settings['grid_lite_col']);

			foreach ($columns_settings as $key => $val)
			{
				$column[$val['name']]                      = $key;
				$this->_tmp_columns_settings[$val['name']] = $val;
			}

			$this->EE->load->library('Parser');

			$parser = new Parser;

			$parser->setTemplate($tagdata)
			->setCallback(array(
					$this,
					'tags_p'
				));


			$row_data = array();


			if ($sort and $sort == "asc")
			{
				krsort($data);
			};

			if ($sort and $sort == "random")
			{
				shuffle($data);
			};

			if ($row_ids)
			{
				$row_ids = explode('|', trim($row_ids));

				$allowed = 'positive';

				if (strncmp($row_ids[0], 'not ', 4) == 0)
				{
					$allowed = 'negative';
					$row_ids[0] = trim(substr($row_ids[0], 3));
				}
			}



			foreach ($data as $row)
			{

				$absolute_count++;

				if ($row_id and $limit < ($row_id+1))
				{
					break;
				};

				if (($offset) >= $absolute_count)
				{
					continue;
				};

				if ($row_ids)
				{
					if (($allowed == 'negative' && in_array($absolute_count , $row_ids)) or ($allowed == 'positive' and !in_array($absolute_count, $row_ids)))
					{
						continue;
					}
				}

				$row_id++;

				$row_data['row_id']         = $row_id;
				$row_data['absolute_count'] = $absolute_count;

				foreach ($column as $key => $val)
				{
					if (isset($row[$val]))
						{$row_data[$key] = $row[$val];};

				};

				$r .= $parser->parse($row_data);

				unset($row_data);
			}

			$r = $this->EE->functions->var_swap($r, array("total_rows" => $row_id));


			if (isset($params['backspace']))
			{
				if (is_numeric($params['backspace']))
				{
					$r = substr($r, 0, -$params['backspace']);
				}

			}

		}

		return $r;
	}



	/**
	 * tags_p function.
	 *
	 * @access public
	 * @param mixed $tag_name
	 * @param mixed $tagdata (default: FALSE)
	 * @param array $params (default: array())
	 * @param mixed $subtags (default: FALSE)
	 * @param mixed $data
	 * @return void
	 */
	public function tags_p($tag_name, $tagdata = FALSE, $params = array(), $subtags = FALSE, $data)
	{
		return $this->ft->ft_processing($data, ((isset($this->_tmp_columns_settings[$tag_name]['type'])) ? $this->_tmp_columns_settings[$tag_name]['type'] : ''), $params, $tagdata = FALSE, $subtags);
	}



	// --------------------------------------------------------------------

	/**
	 * Save Global Settings
	 *
	 * @access public
	 * @return global settings
	 *
	 */
	function save_global_settings()
	{
		return array_merge($this->settings, $_POST);
	}


	// --------------------------------------------------------------------

	/**
	 * Display Settings Screen
	 *
	 * @access public
	 * @return default global settings
	 *
	 */

	function display_settings($data)
	{
		$this->EE->lang->loadfile('grid_lite');

		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/css/cp.css" />');

		/*$this->EE->cp->add_to_head('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/js/jquery.tablednd_0_5.js"></script>'); */

		$this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/js/jquery.grid_lite.js"></script>');


		$max_row = (empty($data[$this->prefix . 'max_row']) or $data[$this->prefix . 'max_row'] == '') ? '' : $data[$this->prefix . 'max_row'];
		$mode_v  = (empty($data[$this->prefix . 'mode_v']) or $data[$this->prefix . 'mode_v'] == '') ? 'r' : $data[$this->prefix . 'mode_v'];

		$columns = array(
			"header" => "",
			"name" => "",
			"label" => "",
			"width" => "",
			"type" => "",
			"settings" => "",
			"footer" => "",
			"index" => 1
		);

		$mode = array(
			"r" => $this->EE->lang->line('rows'),
			"c" => $this->EE->lang->line('columns')
		);

		$CellTypes_out = "{";

		foreach ($this->ft->fields_sel as $key => $val)
		{
			$CellTypes_out .= '"' . $key . '" : "' . str_replace('"', '\"', $this->ft->field_settings(array(
						'type' => $key
					), '{COL_ID}', $this->prefix)) . '", ';
		}

		$CellTypes_out = rtrim(preg_replace('/[\r\n\t]/', '', $CellTypes_out), ",") . "}";

		$this->EE->table->add_row($this->EE->lang->line('max_row'), form_input(array(
					'id' => $this->prefix . 'max_row',
					'name' => $this->prefix . 'max_row',
					'size' => 4,
					'value' => $max_row
				)));

		$rows = "";

		if (isset($data[$this->prefix . 'col']))
		{
			$data[$this->prefix . 'col'] = unserialize($data[$this->prefix . 'col']);
			$columns["index"]            = 0;
			foreach ($data[$this->prefix . 'col'] as $key => $val)
			{
				$index = substr($key, 5);
				if ($index > $columns["index"])
				{
					$columns["index"] = $index;
				}

				$rows .= '<tr class="column-line"><td><table class="mainTable grid_table"  border="0" cellpadding="0" cellspacing="0" style="margin:10px 0 10px 0"><tr><th style="width:20px;padding:0px;margin:0px;"><span class="grid_lite-sort"></span></th><th>Label</th><th>Name</th><th>Type</th><th style="width:50px;">Width</th><th style="width:10px;padding:0px;margin:0px;"></th></tr><tr class="' . $key . '" style="border-top:2px solid; margin-top:20px;"><td class="dand-target"><a style="text-decoration: none;" href="#" class="settings_open">+/-</a></td><td><input name="' . $this->prefix . '[col][' . $key . '][label]" value="' . $val['label'] . '" class="label" type="text"></td><td><input name="' . $this->prefix . '[col][' . $key . '][name]" value="' . $val['name'] . '" type="text"><input type="hidden" name="' . $this->prefix . '[col][' . $key . '][order]" value="' . $key . '" /><input type="hidden" name="' . $this->prefix . '[col][' . $key . '][col_id]" value="' . $val['col_id'] . '" /></td><td>' . form_dropdown($this->prefix . '[col][' . $key . '][type]', $this->ft->fields_sel, $val['type'], 'class="celltype"') . '</td><td><input name="' . $this->prefix . '[col][' . $key . '][width]" value="' . ((isset($val['width'])) ? $val['width'] : '') . '"  value="' . $val['label'] . '" type="text"></td><td><span class="grid_delete"></span></td></tr> <tr id="' . $key . '"><td colspan="6" style="background-color:#ffffff;"><table class="padTable"  border="0" cellpadding="0" cellspacing="0" style="width:100%;background-color:#EFF3F6;"><tr class=\"header\"><th width="40%">' . $this->EE->lang->line('custom_fo') . '</th><th></th></tr><tbody>' . $this->ft->field_settings($val, $key, $this->prefix) . '</tbody></table>	</td></tr> </table> </td></tr>';

			}
			;
		}


		$this->EE->table->add_row(array(
				'data' => $this->EE->load->view('settings', array(
						'rows' => $rows,
						'options' => ''
					), TRUE),
				'colspan' => 2
			));

		$this->EE->javascript->output('	field =  ' . $CellTypes_out . ';
		last_index = ' . $columns["index"]);

	}



	// --------------------------------------------------------------------

	/**
	 * Save Settings
	 *
	 * @access public
	 * @return field settings
	 *
	 */

	function save_settings($data)
	{
		$out = array();


		if (isset($data[$this->prefix]['col']))
		{
			foreach ($data[$this->prefix]['col'] as $key => $val)
			{
				if (!empty($val['name']))
				{
					$out[$key] = $val;
				}
			}
			;

			return array(
				$this->prefix . 'max_row' => is_numeric($data[$this->prefix . 'max_row']) ? $data[$this->prefix . 'max_row'] : '',
				// $this->prefix . 'mode' => $data[$this->prefix . 'mode'],
				$this->prefix . 'col' => serialize($out)
			);
		}
	}


	// --------------------------------------------------------------------

	/**
	 * Install Fieldtype
	 *
	 * @access public
	 * @return default global settings
	 *
	 */
	function install()
	{
		return array(
			'columns' => '3'
		);
	}


	// --------------------------------------------------------------------

	/**
	 * Control Panel Javascript
	 *
	 * @access public
	 * @return void
	 *
	 */
	function _cp_js()
	{
		// This js is used on the global and regular settings
		// pages, but on the global screen the map takes up almost
		// the entire screen. So scroll wheel zooming becomes a hindrance.

		//$this->EE->cp->load_package_js('cp');
	}


}




/* End of file ft.grid_lite.php */
/* Location: ./system/expressionengine/third_party/grid_lite/ft.grid_lite.php */
