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

$__osmium_form_errors = array();

function post_redirect_get() {
  $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '__cli';

  if(isset($_POST) && count($_POST) > 0) {
    \Osmium\State\put_state('prg_data', array($uri, $_POST));
    session_commit();
    header('HTTP/1.1 303 See Other', true, 303);
    header('Location: '.$uri, true, 303);
    die();
  }

  $prg = \Osmium\State\get_state('prg_data', null);
  if($prg !== null) {
    list($from_uri, $prg_data) = $prg;
    if($from_uri === $uri) $_POST = $prg_data;

    \Osmium\State\put_state('prg_data', null);
  }
}

function print_form_begin($action = null, $id = '') {
  if($action === null) $action = $_SERVER['REQUEST_URI'];
  if($id !== '') $id = " id='$id'";

  echo "<form method='post' action='$action'$id>\n<table>\n<tbody>\n";
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
      echo "<tr class='error_message'>\n<td colspan='2'><p>".htmlspecialchars($msg, ENT_QUOTES)."</p></td>\n</tr>\n";
    }
  }

  if($class !== '') {
    $class = " class='$class' ";
  }

  echo "<tr$class>\n";
  echo "<td>$td1</td>\n";
  echo "<td>$td2</td>\n";
  echo "</tr>\n";
}

function print_generic_field($label, $type, $name, $id = null, $flags = 0) {
  if($id === null) $id = $name;
  if($flags & FIELD_REMEMBER_VALUE && isset($_POST[$name])) {
    $value = "value='".htmlspecialchars($_POST[$name], ENT_QUOTES)."' ";
  } else $value = '';

  print_generic_row($name, "<label for='$id'>".$label."</label>", "<input type='$type' name='$name' id='$id' $value/>");
}

function print_submit($value = '') {
  if($value !== '') {
    $value = "value='".htmlspecialchars($value, ENT_QUOTES)."' ";
  }

  echo "<tr>\n<td></td>\n";
  echo "<td><input type='submit' $value/></td>\n</tr>\n";
}

function print_separator() {
  echo "<tr>\n<td colspan='2'><hr /></td>\n</tr>\n";
}

function print_text($text) {
  echo "<tr>\n<td colspan='2'>".$text."</td>\n</tr>\n";
}

function print_select($label, $name, $options, $size = null, $id = null, $flags = 0) {
  if($id === null) $id = $name;

  if($flags & ALLOW_MULTISELECT) $multiselect = ' multiple="multiple"';
  else $multiselect = '';

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

  print_generic_row($name, "<label for='$id'>".$label."</label>", "\n<select id='$id' name='$name'$size$multiselect>\n$fOptions\n</select>\n");
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