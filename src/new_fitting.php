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

require __DIR__.'/../inc/root.php';
require __DIR__.'/../inc/ajax_common.php';

const FINAL_STEP = 5;
const DO_NOT_HASH_SENTINEL = '························';
const MAXIMUM_TAGS = 5;

if(!\Osmium\State\is_logged_in()) {
	\Osmium\fatal(403, 'Sorry, anonymous users cannot create fittings. Please login first and retry.');
}

$step = \Osmium\State\get_state('create_fit_step', 1);

$steps = array(
	1 => 'ship_select',
	2 => 'modules_select',
	3 => 'charges_select',
	4 => 'drones_select',
	5 => 'final_settings',
	);

if(isset($_POST['prev_step'])) {
	if(call_local($steps[$step].'_pre')) --$step;
} else if(isset($_POST['next_step'])) {
	if(call_local($steps[$step].'_post')) ++$step;
} else if(isset($_POST['finalize'])) {
	if(call_local($steps[FINAL_STEP].'_post')) finalize();
} else if(isset($_POST['reset_fit'])) {
	$step = 1;

	$fit = \Osmium\State\get_state('new_fit');
	\Osmium\Fit\destroy($fit);
	\Osmium\State\put_state('new_fit', $fit);
}

if($step < 1) $step = 1;
if($step > FINAL_STEP) $step = FINAL_STEP;

$fit = \Osmium\State\get_state('new_fit', array());
if(isset($fit['metadata']['revision'])) {
	\Osmium\Chrome\print_header('Edit loadout #'.$fit['metadata']['loadoutid'], '.');
	$g_title = 'Edit loadout <a href="./loadout/'.$fit['metadata']['loadoutid'].'">#'.$fit['metadata']['loadoutid'].'</a>';
} else {
	\Osmium\Chrome\print_header('Create a new fitting', '.');
	$g_title = 'New fitting';
}

echo "<script>\nvar osmium_tok = '".\Osmium\State\get_token()."';\n";
echo "var osmium_slottypes = ".json_encode(\Osmium\Fit\get_slottypes()).";\n</script>\n";

call_local($steps[$step]);

echo "<script>$(function() { $('input[name=\"reset_fit\"]').click(function() { return confirm('This will reset all the changes you made. Continue?'); }); });</script>\n";

\Osmium\State\put_state('create_fit_step', $step);
\Osmium\Chrome\print_footer();

/* ----------------------------------------------------- */

function call_local($name) {
	$func_name = __NAMESPACE__.'\\'.$name;
	if(is_callable($func_name)) {
		return call_user_func($func_name);
	}

	return false;
}

function print_form_prevnext() {
	global $step;

	$prevd = $step == 1 ? "disabled='disabled' " : '';

	if($step == FINAL_STEP) {
		$next = "<input type='submit' name='finalize' value='Finalize fitting' class='final_step' />";
	} else {
		$next = "<input type='submit' name='next_step' value='Next step &gt;' class='next_step' />";
	}

	echo "<tr>\n<td></td>\n<td>\n";
	echo "<div style='float: right;'>$next</div>\n";
	echo "<input type='submit' name='reset_fit' value='« Reset all state' class='prev_step dangerous' />\n";
	echo "<input type='submit' name='prev_step' value='&lt; Previous step' class='prev_step' $prevd/>\n";
	echo "</td>\n</tr>\n";
}

function print_h1($name) {
	global $step;
	global $g_title;
	echo "<h1 id='newloadout'>$g_title, step $step of ".FINAL_STEP.": $name</h1>\n";
}

/* ----------------------------------------------------- */

function ship_select() {
	$fit = \Osmium\State\get_state('new_fit', array());

	print_h1('select ship hull');
	\Osmium\Forms\print_form_begin();

	$q = \Osmium\Db\query_params('SELECT typeid, typename, groupname FROM osmium.invships ORDER BY groupname ASC, typename ASC', array());
	$o = array();
	while($row = \Osmium\Db\fetch_row($q)) {
		$o[$row[2]][$row[0]] = $row[1];
	}

	if(isset($fit['hull']['typeid'])) $_POST['hullid'] = $fit['hull']['typeid'];
	\Osmium\Forms\print_select('', 'hullid', $o, 16, null, 
	                           \Osmium\Forms\HAS_OPTGROUPS | \Osmium\Forms\FIELD_REMEMBER_VALUE);

	print_form_prevnext();
	\Osmium\Forms\print_form_end();
}

function ship_select_pre() { /* Unreachable code for the 1st step */ }
function ship_select_post() {
	$fit = \Osmium\State\get_state('new_fit', array());
	if($fit === array()) {
		\Osmium\Fit\create($fit);
	}
	if(!isset($_POST['hullid']) || !\Osmium\Fit\select_ship($fit, $_POST['hullid'])) {
		\Osmium\Forms\add_field_error('hullid', "Please select a ship first. (You can still change your mind later!)");
		return false;
	}

	\Osmium\State\put_state('new_fit', $fit);
	return true;
}

/* ----------------------------------------------------- */

function get_slot_fnames() {
	static $categories = null;
	if($categories === null) {
		$categories = array();
		foreach(\Osmium\Fit\get_slottypes() as $type) {
			$categories[$type] = ucfirst($type);
		}
	}
	return $categories;
}

function print_attributes($before = '', $after = '') {
	echo "<div id='metadatabox'>\n$before<h2>Attributes</h2>\n<ul class='compact computed_attributes'>\n";
	echo "</ul>\n$after</div>\n";
}

function print_modules_searchbox() {
	echo "<div id='searchbox'>\n<h2 class='has_spinner'>Search modules";
	echo "<img src='./static/icons/spinner.gif' id='searchbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to fit)</em>\n</h2>\n";
	echo "<form action='".$_SERVER['REQUEST_URI']."' method='get'>\n";
	echo "<input type='search' placeholder='Search by name or category...' autofocus='autofocus' />\n";
	echo "<input type='submit' value='Search' />\n<br />\n";
	$filters = unserialize(\Osmium\State\get_setting('module_search_filter', serialize(array())));
	$filters = array_combine($v = array_values($filters), $v);
	$req = \Osmium\Db\query_params('SELECT metagroupname, metagroupid FROM osmium.invmetagroups ORDER BY metagroupname ASC', array());
	echo "<p id='search_filters'>\nFilter modules: ";
	while($row = \Osmium\Db\fetch_row($req)) {
		list($name, $id) = $row;
		if(isset($filters[$id])) {
			$nods = ' style="display:none;"';
			$ds = '';
		} else {
			$ds = ' style="display:none;"';
			$nods = '';
		}

		echo "<img src='./static/icons/metagroup_$id.png' alt='Show $name modules' title='Show $name modules' class='meta_filter' id='meta_filter_$id' data-metagroupid='$id' data-toggle='meta_filter_{$id}_disabled' data-filterval='0' $nods/>";
		echo "<img src='./static/icons/metagroup_{$id}_ds.png' alt='Hide $name modules' title='Hide $name modules' class='meta_filter ds' id='meta_filter_{$id}_disabled' data-metagroupid='$id' data-toggle='meta_filter_$id' data-filterval='1' $ds/>\n";
	}

	echo "</p>\n</form>\n<ul id='search_results'></ul>\n</div>\n";

	foreach($filters as &$val) $val = "$val: 0";
	echo "<script>var search_params = {".implode(',', $filters)."};</script>\n";
}

function print_modulelist() {
	echo "<div id='loadoutbox'>\n<h2 class='has_spinner'>Loadout";
	echo "<img src='./static/icons/spinner.gif' id='loadoutbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to remove)</em>\n</h2>\n";
  
	foreach(get_slot_fnames() as $type => $fname) {
		if(in_array($type, \Osmium\Fit\get_stateful_slottypes())) $class = ' stateful';
		else $class = '';

		echo "<div id='{$type}_slots' class='loadout_slot_cat$class'>\n<h3>$fname slots <strong id='{$type}_count'></strong></h3>";
		echo "<ul></ul>\n";
		echo "</div>\n";
	}
	\Osmium\Forms\print_form_begin();
	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";
}

function print_modules_shortlist() {
	echo "<div id='shortlistbox'>\n<h2 class='has_spinner'>Shortlist";
	echo "<img src='./static/icons/spinner.gif' id='shortlistbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to fit)</em>\n</h2>\n";
	echo "<ul id='modules_shortlist'>\n";
	echo "</ul>\n</div>\n";
}

function modules_select() {
	print_h1('select modules');

	ob_start();
	print_modules_searchbox();
	$search = ob_get_clean();

	print_attributes('', $search);
	print_modules_shortlist();
	print_modulelist();
	\Osmium\Chrome\print_js_snippet('new_fitting');

	$fit = \Osmium\State\get_state('new_fit', array());
	echo "<script>\n$(function() {\n";
	echo "osmium_shortlist_load(".json_encode(\Osmium\AjaxCommon\get_module_shortlist()).");\n";
	echo "osmium_loadout_load(".json_encode(\Osmium\AjaxCommon\get_loadable_fit($fit)).");\n";
	echo "});\n</script>\n";
}

function modules_select_pre() { return true; }
function modules_select_post() { return true; }

/* ----------------------------------------------------- */

function print_charge_presetsbox() {
	echo "<div id='presetsbox'>\n<h2 class='has_spinner'>Presets ";
	echo "<a href='javascript:void(0);' id='new_preset'><img src='./static/icons/add.png' alt='Create a new preset' title='New preset' /></a>";
	echo "<img src='./static/icons/spinner.gif' id='presetsbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Click to change active preset)</em></h2>\n";
	echo "<ul id='presets'>\n";
	echo "</ul>\n";
	echo "</div>\n";
}

function print_charge_groups() {
	echo "<div id='chargegroupsbox'>\n<h2 class='has_spinner'>Charge groups";
	echo "<img src='./static/icons/spinner.gif' id='chargegroupsbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Select multiple items by dragging or using <kbd>Ctrl</kbd>)</em>\n</h2>\n";
  
	\Osmium\Forms\print_form_begin();
	echo "<tr><td colspan='2'>\n<ul id='chargegroups'>\n";

	foreach(get_charges() as $i => $charges) {
		echo "<li id='group_$i'>\n";
		print_chargegroup($i, $charges['typeids'], $charges['charges']);
		echo "</li>\n";
	}

	echo "</ul>\n</td></tr>\n";
	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";
}

function get_charges() {
	$fit = \Osmium\State\get_state('new_fit', array());
	$typeids = array();
	foreach($fit['modules'] as $type => $a) {
		foreach($a as $k) {
			$typeids[$k['typeid']] = true;
		}
	}

	$groups = array();
	$typetogroups = array();
	$keystonumbers = array();
	$z = 0;

	foreach($typeids as $typeid => $val) {
		$chargeids = array();
		$req = \Osmium\Db\query_params('SELECT chargeid, chargename FROM osmium.invcharges WHERE moduleid = $1 ORDER BY chargename ASC', array($typeid));
		while($row = \Osmium\Db\fetch_row($req)) {
			$chargeids[$row[0]] = array('typeid' => $row[0], 'typename' => $row[1]);
		}

		if(count($chargeids) == 0) continue;

		$keys = array_keys($chargeids);
		sort($keys);
		$key = implode(' ', $keys);
		if(!isset($keystonumbers[$key])) {
			$keystonumbers[$key] = $z;
			$groups[$z] = array_values($chargeids);
			++$z;
		}
		$typetogroups[$typeid] = $keystonumbers[$key];
	}

	$result = array();
	foreach($typetogroups as $typeid => $i) {
		$result[$i]['typeids'][] = $typeid;
	}
	foreach($groups as $i => $group) {
		$result[$i]['charges'] = $group;
	}

	return $result;
}

function print_chargegroup($groupid, $typeids, $charges) {
	$fit = \Osmium\State\get_state('new_fit', array());
	echo "<ul class='chargegroup'>\n";
	foreach($fit['modules'] as $type => $a) {
		foreach($a as $i => $module) {
			$id = $module['typeid'];
			if(!in_array($id, $typeids)) continue;

			$name = $module['typename'];
			echo "<li id='{$type}_$i'><img src='http://image.eveonline.com/Type/{$id}_32.png' alt='$name' title='$name' class='module_icon' />";
			echo "<img src='./static/icons/no_charge.png' alt='(No charge)' title='(No charge)' class='charge_icon' />\n";
			echo "<select name='charge_{$groupid}_$i' data-slottype='$type' data-index='$i'>\n";
			echo "<option value='-1'>(No charge)</option>\n";
			foreach($charges as $charge) {
				echo "<option value='".$charge['typeid']."'>".$charge['typename']."</option>\n";
			}
			echo "</select>\n";
			echo "</li>\n";
		}
	}
	echo "</ul>\n";
}

function charges_select() {
	print_h1('select charges');
  
	ob_start();
	print_charge_presetsbox();
	print_attributes('', ob_get_clean());
	print_charge_groups();

	$fit = \Osmium\State\get_state('new_fit', array());
	echo "<script>\n$(function() { $('ul.computed_attributes').html(".json_encode(\Osmium\Chrome\get_formatted_loadout_attributes($fit))."); });\n"
		."var charge_presets = ".json_encode($fit['charges'], JSON_FORCE_OBJECT)
		.";\nvar selected_preset = ".json_encode($fit['selectedpreset'])
		.";\n var osmium_preset_num = ".(count($fit['charges']) + 1).";\n</script>\n";

	\Osmium\Chrome\print_js_snippet('new_fitting_charges');
}

function charges_select_pre() { return true; }
function charges_select_post() { return true; }

/* ----------------------------------------------------- */

function print_drone_searchbox() {
	echo "<div id='dronelistbox'>\n<h2 class='has_spinner'>Search drones";
	echo "<img src='./static/icons/spinner.gif' id='dronelistbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to add to bay)</em>\n</h2>\n";
	echo "<form action='".$_SERVER['REQUEST_URI']."' method='get'>\n";
	echo "<input type='search' placeholder='Search by name or category...' />\n";
	echo "<input type='submit' value='Search' />\n";
	echo "</form>\n<ul id='search_results'></ul>\n</div>\n";
}

function print_dronebay() {
	echo "<div id='dronebay'>\n<h2 class='has_spinner'>Drones";
	echo "<img src='./static/icons/spinner.gif' id='dronebay_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to remove)</em>\n</h2>\n";
	echo "<p id='dronecapacity'><img src='./static/icons/dronecapacity.png' alt='Drone capacity' title='Drone capacity' /><strong></strong> m<sup>3</sup></p>\n<p id='dronebandwidth'><img src='./static/icons/bandwidth.png' alt='Drone bandwidth' title='Drone bandwidth' /><strong></strong> Mbit/s</p>\n";

	foreach(array('bay', 'space') as $v) {
		echo "<div id='in$v'>\n<h4>In $v</h4>\n<ul></ul>\n</div>\n";
	}

	\Osmium\Forms\print_form_begin();
	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";
}

function drones_select() {
	print_h1('select drones');
	$fit = \Osmium\State\get_state('new_fit', array());

	ob_start();
	print_drone_searchbox();
	print_attributes('', ob_get_clean());
	print_dronebay();
	\Osmium\Chrome\print_js_snippet('new_fitting_drones');
	echo "<script>osmium_load_drones(".json_encode(\Osmium\AjaxCommon\get_data_step_drone_select($fit)).");</script>\n";
}

function drones_select_pre() { return true; }
function drones_select_post() { return true; }

/* ----------------------------------------------------- */

function final_settings() {
	print_h1('final adjustments');
	load_metadata();

	\Osmium\Forms\print_form_begin();
	\Osmium\Forms\print_text('<h2>Metadata</h2>');
	\Osmium\Forms\print_generic_field('Fitting name', 'text', 'name', 'name', 
	                                  \Osmium\Forms\FIELD_REMEMBER_VALUE);
	\Osmium\Forms\print_textarea('Description<br /><small>(optional)</small>', 'description', 'description',
	                             \Osmium\Forms\FIELD_REMEMBER_VALUE);
	\Osmium\Forms\print_generic_field('Tags<br /><small>(space-separated,<br />'.MAXIMUM_TAGS.' maximum)</small>', 'text', 'tags', 'tags',
	                                  \Osmium\Forms\FIELD_REMEMBER_VALUE);

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

	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	\Osmium\Chrome\print_js_snippet('new_fitting_metadata');
}

function load_metadata() {
	$fit = \Osmium\State\get_state('new_fit', array());

	isset($fit['metadata']['name']) && $_POST['name'] = $fit['metadata']['name'];
	isset($fit['metadata']['description']) && $_POST['description'] = $fit['metadata']['description'];
	isset($fit['metadata']['tags']) && $_POST['tags'] = implode(' ', $fit['metadata']['tags']);
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

function update_metadata() {
	if(!isset($_POST['name'])) return false;
	$fit = \Osmium\State\get_state('new_fit', array());
	if(!isset($fit['metadata'])) $fit['metadata'] = array();
  
	$errors = 0;
  
	$fname = trim($_POST['name']);
	if(empty($fname)) {
		\Osmium\Forms\add_field_error('name', 'You must choose a name. Any name will do. What about what your fit was designed for?');
		++$errors;
	}

	$fdesc = trim($_POST['description']);
	$tags = preg_split('/\s/', $_POST['tags'], -1, PREG_SPLIT_NO_EMPTY);
	$tags = array_map(function($tag) {
			$tag = str_replace('_', '-', $tag);
			return preg_replace('%[^A-Za-z0-9-]+%', '', $tag);
		}, $tags);
	$tags = array_unique(array_filter($tags, function($tag) { return strlen($tag) > 0; }));
	$tags = array_slice($tags, 0, MAXIMUM_TAGS);
 
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


	$fit['metadata']['name'] = $fname;
	$fit['metadata']['description'] = $fdesc;
	$fit['metadata']['tags'] = $tags;
	$fit['metadata']['view_permission'] = $view_perm;
	$fit['metadata']['edit_permission'] = $edit_perm;
	$fit['metadata']['visibility'] = $visibility;

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

/* ----------------------------------------------------- */

function finalize() {
	$fit = \Osmium\State\get_state('new_fit', array());
	\Osmium\Fit\sanitize($fit);

	if(isset($fit['metadata']['accountid'])) {
		$accountid = $fit['metadata']['accountid'];
	} else {
		$accountid = \Osmium\State\get_state('a')['accountid'];
	}

	\Osmium\Fit\commit_loadout($fit, $accountid, $accountid);
	$loadoutid = $fit['metadata']['loadoutid'];
	\Osmium\Fit\reset($fit);
	\Osmium\State\put_state('new_fit', $fit);
	\Osmium\State\put_state('create_fit_step', 1);
	\Osmium\State\invalidate_cache('loadout-'.$loadoutid);

	header('Location: ./loadout/'.$loadoutid);
	die();
}
