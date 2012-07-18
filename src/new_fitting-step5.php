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

namespace Osmium\Page\NewFitting;

function final_settings() {
	global $anonymous;

	print_h1('final adjustments');
	load_metadata();

	if($anonymous) {
		$formats = \Osmium\Fit\get_export_formats();

		echo "<p class='warning_box' style='max-width: 100%;'>You are not logged in. You can still login now (or register) and save your fitting to your account, as long as you don't close your browser window. If you don't want to, you can still export your new loadout in one of the supported export formats.</p>";
	}

	\Osmium\Forms\print_form_begin();
	\Osmium\Forms\print_text('<h2>Metadata</h2>');
	\Osmium\Forms\print_generic_field('Fitting name', 'text', 'name', 'name', 
	                                  \Osmium\Forms\FIELD_REMEMBER_VALUE);
	\Osmium\Forms\print_textarea('Description<br /><small>(optional)</small>', 'description', 'description',
	                             \Osmium\Forms\FIELD_REMEMBER_VALUE);
	\Osmium\Forms\print_generic_field('Tags<br /><small>(space-separated,<br />'.\Osmium\Fit\MAXIMUM_TAGS.' maximum)</small>', 'text', 'tags', 'tags',
	                                  \Osmium\Forms\FIELD_REMEMBER_VALUE);

	if(!$anonymous) {
		\Osmium\Forms\print_text('<h2>Permissions</h2>');
		\Osmium\Forms\print_select('Can be seen by', 'view_perms', 
		                           array(
			                           \Osmium\Fit\VIEW_EVERYONE => 'everyone',
			                           \Osmium\Fit\VIEW_PASSWORD_PROTECTED => 'everyone but require a password',
			                           \Osmium\Fit\VIEW_ALLIANCE_ONLY => 'my alliance only',
			                           \Osmium\Fit\VIEW_CORPORATION_ONLY => 'my corporation only',
			                           \Osmium\Fit\VIEW_OWNER_ONLY => 'only me',
			                           ), null, 'view_perms',
		                           \Osmium\Forms\FIELD_REMEMBER_VALUE);
		\Osmium\Forms\print_select('Can be edited by', 'edit_perms', 
		                           array(
			                           \Osmium\Fit\EDIT_OWNER_ONLY => 'only me',
			                           \Osmium\Fit\EDIT_OWNER_AND_FITTING_MANAGER_ONLY => 'me and anyone in my corporation with the Fitting Manager role',
			                           \Osmium\Fit\EDIT_CORPORATION_ONLY => 'anyone in my corporation',
			                           \Osmium\Fit\EDIT_ALLIANCE_ONLY => 'anyone in my alliance',
			                           ), null, 'edit_perms',
		                           \Osmium\Forms\FIELD_REMEMBER_VALUE);
		\Osmium\Forms\print_select('Visibility', 'visibility', 
		                           array(
			                           \Osmium\Fit\VISIBILITY_PUBLIC => 'public (this fit can appear in search results when appropriate)',
			                           \Osmium\Fit\VISIBILITY_PRIVATE => 'private (you will have to give the URL manually)',
			                           ), null, 'visibility',
		                           \Osmium\Forms\FIELD_REMEMBER_VALUE);
		\Osmium\Forms\print_generic_field('Password', 'password', 'pw', 'pw',
		                                  \Osmium\Forms\FIELD_REMEMBER_VALUE);
	} else {
		$options = array();

		foreach($formats as $name => $format) {
			$options[$name] = $format[0];
		}

		\Osmium\Forms\print_select('Export format', 'export_format', $options, null, 'export_format', \Osmium\Forms\FIELD_REMEMBER_VALUE);
	}

	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	\Osmium\Chrome\print_js_snippet('new_fitting-step5');
}

function load_metadata() {
	global $anonymous;
	$fit = \Osmium\State\get_state('new_fit', array());

	isset($fit['metadata']['name']) && $_POST['name'] = $fit['metadata']['name'];
	isset($fit['metadata']['description']) && $_POST['description'] = $fit['metadata']['description'];
	isset($fit['metadata']['tags']) && $_POST['tags'] = implode(' ', $fit['metadata']['tags']);

	if(!$anonymous) {
		isset($fit['metadata']['view_permission']) && $_POST['view_perms'] = $fit['metadata']['view_permission'];
		isset($fit['metadata']['edit_permission']) && $_POST['edit_perms'] = $fit['metadata']['edit_permission'];
		isset($fit['metadata']['visibility']) && $_POST['visibility'] = $fit['metadata']['visibility'];

		if(isset($fit['metadata']['view_permission']) 
		   && $fit['metadata']['view_permission'] == \Osmium\Fit\VIEW_PASSWORD_PROTECTED 
		   && isset($fit['metadata']['password'])) {
			$_POST['pw'] = DO_NOT_HASH_SENTINEL;
		} else {
			unset($_POST['pw']); /* NEVER display the clear password. */
		}
	}
}

function update_metadata() {
	if(!isset($_POST['name'])) return false;

	global $anonymous;
	$fit = \Osmium\State\get_state('new_fit', array());
	if(!isset($fit['metadata'])) $fit['metadata'] = array();
  
	$errors = 0;
  
	$fname = trim($_POST['name']);
	if(empty($fname)) {
		\Osmium\Forms\add_field_error('name', 'You must choose a name. Any name will do. What about what your fit was designed for?');
		++$errors;
	}

	$fdesc = trim($_POST['description']);
	$tags = preg_split('/\s+/', $_POST['tags'], -1, PREG_SPLIT_NO_EMPTY);

	$fit['metadata']['name'] = $fname;
	$fit['metadata']['description'] = $fdesc;
	$fit['metadata']['tags'] = $tags;
 
	if(!$anonymous) {
		$view_perm = $_POST['view_perms'];
		$edit_perm = $_POST['edit_perms'];
		$visibility = isset($_POST['visibility']) ? $_POST['visibility'] : null;
		$pw = isset($_POST['pw']) ? $_POST['pw'] : '';

		if(!in_array($view_perm, array(
			             \Osmium\Fit\VIEW_EVERYONE,
			             \Osmium\Fit\VIEW_PASSWORD_PROTECTED,
			             \Osmium\Fit\VIEW_ALLIANCE_ONLY,
			             \Osmium\Fit\VIEW_CORPORATION_ONLY,
			             \Osmium\Fit\VIEW_OWNER_ONLY,
			             ))) {
			$view_perm = \Osmium\Fit\VIEW_EVERYONE;
		}
		if(!in_array($edit_perm, array(
			             \Osmium\Fit\EDIT_OWNER_ONLY,
			             \Osmium\Fit\EDIT_OWNER_AND_FITTING_MANAGER_ONLY,
			             \Osmium\Fit\EDIT_CORPORATION_ONLY,
			             \Osmium\Fit\EDIT_ALLIANCE_ONLY,
			             ))) {
			$edit_perm = \Osmium\Fit\EDIT_OWNER_ONLY;
		}
		if(!in_array($visibility, array(
			             \Osmium\Fit\VISIBILITY_PUBLIC,
			             \Osmium\Fit\VISIBILITY_PRIVATE,
			             ))) {
			$visibility = \Osmium\Fit\VISIBILITY_PUBLIC;
		}

		if($view_perm == \Osmium\Fit\VIEW_PASSWORD_PROTECTED) {
			$visibility = \Osmium\Fit\VISIBILITY_PRIVATE; /* Makes sense. */

			if(empty($pw)) {
				\Osmium\Forms\add_field_error('pw', 'If you want your fit to be password-protected, you must enter the password here.');
				++$errors;
				unset($fit['metadata']['password']);
				unset($_POST['pw']);
			} else {
				if(!isset($fit['metadata']['password']) || $pw != DO_NOT_HASH_SENTINEL) {
					$fit['metadata']['password'] = \Osmium\State\hash_password($pw);
				}
			}
		} else {
			unset($fit['metadata']['password']);
		}

		$fit['metadata']['view_permission'] = $view_perm;
		$fit['metadata']['edit_permission'] = $edit_perm;
		$fit['metadata']['visibility'] = $visibility;
	}

	\Osmium\State\put_state('new_fit', $fit);
	return $errors === 0;
}

function final_settings_pre() {
	update_metadata();
	/* Allow going back even if the form has errors. */
	return true;
}

function final_settings_post() {
	return update_metadata();
}
