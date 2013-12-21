<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Grid_custom_ft
{
	var $fields_sel = array("text" => "text", "textarea" => "textarea", "select" => "select", "file" => "file", "date" => "date");

	function Grid_custom_ft()
	{
		$this->EE =& get_instance();
	}

	function fieldtype($field_name, $_column, $row, $i, $row_id, $cell_id)
	{
		$data = (isset($row[$cell_id])) ? $row[$cell_id] : '';

		$field_name = $field_name . '[row_' . $row_id . '][' . $cell_id . ']';

		switch ($_column['type']) {

		case 'text':
			$input_data = array(
				'name' => $field_name,
				'value' => (isset($data)) ? $data : '',
				'maxlength' => (isset($_column['settings']['maxl'])) ? $_column['settings']['maxl'] : '128',
				'type' => 'text'
			);

			$r = form_input($input_data);
			break;

		case 'file':

			$input_data_dir  = array(
				'name' => $field_name . '[dir]',
				'value' => '',
				'maxlength' => (isset($_column['settings']['maxl'])) ? $_column['settings']['maxl'] : '128',
				'type' => 'hidden',
				'class' => 'mx_file'
			);

			$input_data_file = array(
				'name' => $field_name . '[file]',
				'value' => '',
				'maxlength' => (isset($_column['settings']['maxl'])) ? $_column['settings']['maxl'] : '128',
				'type' => 'hidden',
				'class' => 'mx_file'

			);


		/*
			$allowed_file_dirs		=  (string) $_column['settings']['file_allowed_directories'];


			$content_type			= (isset($_column['settings']['content_type'])) ? $_column['settings']['content_type'] : 'all';

			$this->EE->load->library('file_field');
			$this->EE->load->model('file_model');

			*/


			$specified_directory = (string) $_column['settings']['file_allowed_directories'];
			$content_type   = (isset($_column['settings']['content_type'])) ? $_column['settings']['content_type'] : 'all';

			$upload_prefs = $this->EE->file_upload_preferences_model->get_file_upload_preferences($this->EE->session->userdata('group_id'));


			if ($data != "") {

				foreach ($upload_prefs as $val) {
					$upload_dir[$val['id']] = $val;
				}

				if (preg_match('/{filedir_([0-9]+)}/', $data, $matches)) {
					$input_data_dir['value']  = $filedir = $matches[1];
					$input_data_file['value'] = $filename = str_replace($matches[0], '', $data);
				}


				if ($filedir) {
					$thumb     = $upload_dir[$filedir]['server_path'] . '_thumbs/' . $filename;
					$thumb_url = (file_exists($thumb)) ? $upload_dir[$filedir]['url'] . '_thumbs/' . $filename : PATH_CP_GBL_IMG . 'default.png';
				}

				$r = '<div style="display:none"><a class="mx_file" id="ch_' . $field_name . '" rel="' . $field_name . '" data-content-type="'.$content_type.'" data-directory="'.$specified_directory.'" >' . 'Choose file' . '</a>' . form_input($input_data_dir) . form_input($input_data_file) . '</div><div class="grid_file"><a class="remove_item" href="#"><img class="remove" alt="' . $this->EE->lang->line('remove_file') . '" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/images/' . 'remove.png" height="17" width="17"></a><img src="' . $thumb_url . '" title="' . $filename . '" alt="' . $filename . '" class="grid-img"><br/><span class="gr-file-name">' . $filename . '</span></div>';
			} else {
				$r = '<div><a class="mx_file" id="ch_' . $field_name . '" rel="' . $field_name . '" data-content-type="'.$content_type.'" data-directory="'.$specified_directory.'">' . 'Choose file' . '</a>' . form_input($input_data_dir) . form_input($input_data_file) . '</div><div class="grid_file" style="display:none"><a class="remove_item" href="#"><img class="remove" alt="' . $this->EE->lang->line('remove_file') . '" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/grid_lite/images/' . 'remove.png" height="17" width="17"></a><img class="grid-img"><div class="gr-thumb"></div><div class="gr-file-name"></div></div>';
			}

			break;

		case 'textarea':
			$input_data = array(
				'name' => $field_name,
				'value' => (isset($data)) ? $data : '',
				'type' => 'text',
				'rows' => (isset($_column['settings']['maxrows'])) ? $_column['settings']['maxrows'] : '6',
				'style' => ''
			);
			$r          = form_textarea($input_data);
			break;

		case 'select':
			$option = preg_split("/[\r\n]/", trim($_column['settings']['list_items']));

			foreach ($option as $val) {
				$parts              = explode(":", $val);
				$parts[1]           = (isset($parts[1]) === TRUE) ? $parts[1] : $parts[0];
				$options[$parts[0]] = $parts[1];
			}
			$r = form_dropdown($field_name, $options, (isset($data)) ? $data : '');
			break;

		case 'date':
			$input_data = array(
				'name' => $field_name,
				'value' => (isset($data)) ? $this->EE->localize->set_human_time($data) : '',
				'maxlength' => (isset($_column['settings']['maxl'])) ? $_column['settings']['maxl'] : '128',
				'type' => 'text',
				'class' => 'date_input addDate'
			);

			//return
			$r = form_input($input_data);

			break;
		}

		return $r;
	}

	function ft_processing($data, $type = "", $params = array(), $tagdata = FALSE, $subtags = FALSE)
	{
		$r = $data;

		switch ($type) {

		case 'text':

			break;

		case 'textarea':

			break;

		case 'select':

			break;

		case 'file':

			$file_info['path'] = '';

			if (preg_match('/^{filedir_(\d+)}/', $data, $matches)) {

				$path = substr($data, 0, 10 + strlen($matches[1]));

				$file_dirs = $this->EE->functions->fetch_file_paths();

				if (isset($file_dirs[$matches[1]])) {
					$file_info['path'] = str_replace($matches[0], $file_dirs[$matches[1]], $path);
					$data              = str_replace($matches[0], '', $data);
				}
			}

			$file_info['extension'] = substr(strrchr($data, '.'), 1);
			$file_info['filename']  = basename($data, '.' . $file_info['extension']);


			$r = $file_info['path'] . $file_info['filename'] . '.' . $file_info['extension'];
			if ($subtags) {
				$r = $this->EE->functions->var_swap($subtags, $file_info);
			}

			break;

		case 'date':

			$r = (isset($params['format'])) ? $this->EE->localize->decode_date($params['format'], $data) : $this->EE->localize->set_human_time($data);
			break;

		}

		return $r;
	}

	function ft_save($type, $data)
	{
		$r = $data;

		switch ($type) {

		case 'text':

			break;

		case 'textarea':

			break;

		case 'select':

			break;

		case 'file':

			if ($data['file'] != '') {
				$r = '{filedir_' . $data['dir'] . '}' . $data['file'];
			}
			else {
				$r = "";
			}


			break;

		case 'date':
			$r = $this->EE->localize->convert_human_date_to_gmt($data);
			break;
		}

		return $r;
	}

	function field_settings($data, $iRow, $prefix)
	{
		$this->EE->load->model('tools_model');

		$r = "";

		switch ($data['type']) {
		case 'text':


			$r = '<tr><td>' . $this->EE->lang->line('max_length') . '</td><td><input dir="ltr" style="width: 40px;"  name="' . $prefix . '[col][' . $iRow . '][settings][maxl]" id="maxl" size="4" maxlength="5" class="input" type="text" value="' . ((isset($data['settings']['maxl'])) ? $data['settings']['maxl'] : '128') . '"></td></tr>';

			break;

		case 'textarea':
			$r = '<tr><td>' . $this->EE->lang->line('textarea_rows') . '</td><td><input dir="ltr" style="width: 40px;" name="' . $prefix . '[col][' . $iRow . '][settings][maxrows]" id="maxrows"  value="6" size="4" maxlength="5" class="input" type="text" value="' . ((isset($data['settings']['maxrows'])) ? $data['settings']['maxrows'] : '6') . '">&nbsp;</td></tr>';
			break;

		case 'select':
			$r = '<tr><td>' . $this->EE->lang->line('multi_select_options') . '</td><td><textarea spellcheck="false" name="' . $prefix . '[col][' . $iRow . '][settings][list_items]" cols="50" rows="10" id="list_items">' . ((isset($data['settings']['list_items'])) ? $data['settings']['list_items'] : '') . '</textarea>&nbsp; </td></tr>';
			break;

		case 'file':
			$r = '<tr><td>' . $this->EE->lang->line('file_type') . '</td><td>' . form_dropdown($prefix . '[col][' . $iRow . '][settings][content_type]', array(
					'any' => 'Any',
					'image' => 'Image'
				), ((isset($data['settings']['content_type'])) ? $data['settings']['content_type'] : '')) . '
							</td></tr>';

			if (version_compare(APP_VER, '2.2', '>='))
			{

				$directory_options['all'] = lang('all');



				$dirs = $this->EE->file_upload_preferences_model->get_file_upload_preferences(1);

				foreach($dirs as $dir)
				{
					$directory_options[$dir['id']] = $dir['name'];
				}

				$allowed_directories = ( ! isset($data['settings']['file_allowed_directories'])) ? 'all' : $data['settings']['file_allowed_directories'];

				$r .= '<tr><td>' . lang('allowed_dirs_file', 'allowed_dirs_file'). '</td><td>' . form_dropdown($prefix . '[col][' . $iRow . '][settings][file_allowed_directories]', $directory_options, $allowed_directories, 'id="file_allowed_directories"') . '
							</td></tr>';

			}

			break;

		case 'date':
			$r = "";
			break;

		}
		return $r;
	}


}
