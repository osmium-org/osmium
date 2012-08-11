<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Osmium\Forms;

const FIELD_REMEMBER_VALUE = 1;
const ALLOW_MULTISELECT = 2;
const HAS_OPTGROUPS = 4;
const FIELD_DISABLED = 8;

$__osmium_form_errors = array();

function post_redirect_get() {
	$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '__cli';

	if(isset($_POST) && count($_POST) > 0) {
		if(!isset($_FILES)) $_FILES = array();
		else {
			foreach($_FILES as &$file) {
				if($file['error'] != UPLOAD_ERR_OK) continue;

				$temp = tempnam(\Osmium\ROOT.'/cache', 'upload');
				move_uploaded_file($file['tmp_name'], $temp);
				$file['tmp_name'] = $temp;
			}
		}

		\Osmium\State\put_state('prg_data', array($uri, $_POST, $_FILES));
		session_commit();
		header('HTTP/1.1 303 See Other', true, 303);
		header('Location: '.$uri, true, 303);
		die();
	}

	$prg = \Osmium\State\get_state('prg_data', null);
	if($prg !== null) {
		list($from_uri, $prg_post, $prg_files) = $prg;
		if($from_uri === $uri) {
			$_POST = $prg_post;
			$_FILES = $prg_files;
			foreach($_FILES as $file) {
				if($file['error'] != UPLOAD_ERR_OK) continue;
				register_shutdown_function(function() use($file) {
						@unlink($file['tmp_name']);
					});
			}
		}

		\Osmium\State\put_state('prg_data', null);
	}
}

function print_form_begin($action = null, $id = '', $enctype = 'application/x-www-form-urlencoded') {
	if($action === null) $action = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES);
	if($id !== '') $id = " id='$id'";

	echo "<form method='post' accept-charset='utf-8' enctype='$enctype' action='$action'$id>\n<table>\n<tbody>\n";
}

function print_form_end() {
	echo "</tbody>\n</table>\n</form>\n";
}

function add_field_error($name, $error) {
	global $__osmium_form_errors;
	$__osmium_form_errors[$name][] = $error;
}

function print_generic_row($name, $td1, $td2) {
	$class = '';

	global $__osmium_form_errors;
	if(isset($__osmium_form_errors[$name]) && count($__osmium_form_errors[$name]) > 0) {
		$class = 'error';
		foreach($__osmium_form_errors[$name] as $msg) {
			echo "<tr class='error_message'>\n<td colspan='2'><p>".$msg."</p></td>\n</tr>\n";
		}
	}

	if($class !== '') {
		$class = " class='$class' ";
	}

	echo "<tr$class>\n";
	echo "<th>$td1</th>\n";
	echo "<td>$td2</td>\n";
	echo "</tr>\n";
}

function print_generic_field($label, $type, $name, $id = null, $flags = 0) {
	if($id === null) $id = $name;
	if($flags & FIELD_REMEMBER_VALUE && isset($_POST[$name])) {
		$value = "value='".htmlspecialchars($_POST[$name], ENT_QUOTES)."' ";
	} else $value = '';
	if($flags & FIELD_DISABLED) {
		$disabled = "disabled='disabled' ";
	} else $disabled = '';

	print_generic_row($name, "<label for='$id'>".$label."</label>", "<input type='$type' name='$name' id='$id' {$value}{$disabled}/>");
}

function print_textarea($label, $name, $id = null, $flags = 0, $placeholder = '') {
	if($id === null) $id = $name;
	if($flags & FIELD_REMEMBER_VALUE && isset($_POST[$name])) {
		$value = htmlspecialchars($_POST[$name]);
	} else $value = '';
	if($flags & FIELD_DISABLED) {
		$disabled = " disabled='disabled'";
	} else $disabled = '';

	print_generic_row($name, "<label for='$id'>$label</label>", "<textarea placeholder='".htmlspecialchars($placeholder, ENT_QUOTES)."' name='$name' id='$id'{$disabled}>$value</textarea>");
}

function print_file($label, $name, $maxsize, $id = null) {
	static $hasMAX_FILE_SIZE = false;
	if(!$hasMAX_FILE_SIZE) {
		$hasMAX_FILE_SIZE = true;
		$hidden = "<input type='hidden' name='MAX_FILE_SIZE' value='$maxsize' />";
	} else $hidden = '';

	if($id === null) $id = $name;
	print_generic_row($name, "<label for='$id'>$label</label>", $hidden."<input type='file' name='$name' id='$id' />");
}

function print_submit($value = '', $name = '') {
	if($value !== '') {
		$value = "value='".htmlspecialchars($value, ENT_QUOTES)."' ";
	}
	if($name !== '') {
		$name = "name='$name' ";
	}

	echo "<tr>\n<td></td>\n";
	echo "<td><input type='submit' $name$value/></td>\n</tr>\n";
}

function print_separator() {
	echo "<tr class='separator'>\n<td colspan='2'><hr /></td>\n</tr>\n";
}

function print_text($text) {
	echo "<tr>\n<td colspan='2'>".$text."</td>\n</tr>\n";
}

function print_select($label, $name, $options, $size = null, $id = null, $flags = 0) {
	if($id === null) $id = $name;

	if($flags & ALLOW_MULTISELECT) $multiselect = ' multiple="multiple"';
	else $multiselect = '';
	if($flags & FIELD_DISABLED) {
		$disabled = " disabled='disabled'";
	} else $disabled = '';

	if($size === null) $size = '';
	else $size = " size='$size'";

	$fOptions = '';
	if($flags & HAS_OPTGROUPS) {
		foreach($options as $category => $group) {
			$fOptions .= "<optgroup label='$category'>\n";
			$fOptions .= format_optgroup($name, $group, $flags);
			$fOptions .= "</optgroup>\n";
		}
	} else $fOptions = format_optgroup($name, $options, $flags);

	if($flags & ALLOW_MULTISELECT) {
		$name = $name.'[]';
	}

	print_generic_row($name, "<label for='$id'>".$label."</label>", "\n<select id='$id' name='$name'{$size}{$multiselect}{$disabled}>\n$fOptions\n</select>\n");
}

function format_optgroup($name, $options, $flags) {
	$f = '';
	foreach($options as $value => $label) {
		$selected = '';
		if($flags & FIELD_REMEMBER_VALUE) {
			if(isset($_POST[$name]) 
			   && (
				   (($flags & ALLOW_MULTISELECT) && in_array($value, $_POST[$name]))
				   || (!($flags & ALLOW_MULTISELECT) && $_POST[$name] == $value))) {
				$selected = ' selected="selected"';
			}
		}
		$f .= "<option value='$value'$selected>$label</option>\n";
	}
  
	return $f;
}

function print_checkbox_or_radio($type, $label, $name, $id = null, $checked = null, $value = null, $flags = 0) {
	if($id === null) $id = $name;
	if($checked === true || ($flags & FIELD_REMEMBER_VALUE && isset($_POST[$name]) && $_POST[$name] == $value)) {
		$checked = 'checked="checked" ';
	} else $checked = '';
	if($flags & FIELD_DISABLED) {
		$disabled = "disabled='disabled' ";
	} else $disabled = '';

	if($value !== null) {
		$value = 'value="'.htmlspecialchars($value, ENT_QUOTES).'" ';
	} else $value = '';

	print_generic_row($name, "", "<input type='$type' name='$name' id='$id' {$value}{$checked}{$disabled}/> <label for='$id'>$label</label>");
}

function print_checkbox($label, $name, $id = null, $checked = null, $flags = 0) {
	print_checkbox_or_radio('checkbox', $label, $name, $id, $checked, null, $flags);
}

function print_radio($label, $name, $value, $id = null, $checked = null, $flags = 0) {
	static $idcnt = 0;

	if($id === null) {
		/* Several radio buttons typically share the same name, but
		 * must have different ids. */
		$id = $name.($idcnt++);
	}

	print_checkbox_or_radio('radio', $label, $name, $id, $checked, $value, $flags);	
}
