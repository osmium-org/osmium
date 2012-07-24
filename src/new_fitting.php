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

for($i = 1; $i <= FINAL_STEP; ++$i) {
	require __DIR__.'/new_fitting-step'.$i.'.php';
}

$anonymous = !\Osmium\State\is_logged_in();
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

	$fit = \Osmium\State\get_new_fit();
	\Osmium\Fit\destroy($fit);
	\Osmium\State\put_new_fit($fit);
}

if($step < 1) $step = 1;
if($step > FINAL_STEP) $step = FINAL_STEP;

$fit = \Osmium\State\get_new_fit();

if(isset($fit['metadata']['loadoutid'])) {
	\Osmium\Chrome\print_header('Edit loadout #'.$fit['metadata']['loadoutid'], '.');
	$g_title = 'Edit loadout <a href="./loadout/'.$fit['metadata']['loadoutid'].'">#'.$fit['metadata']['loadoutid'].'</a>';
} else {
	\Osmium\Chrome\print_header('Create a new fitting', '.');
	$g_title = 'New fitting';
}

\Osmium\Chrome\print_js_code("osmium_tok = '".\Osmium\State\get_token()."';\nosmium_slottypes = ".json_encode(\Osmium\Fit\get_slottypes()).";");

call_local($steps[$step]);

\Osmium\Chrome\print_js_code("$('input[name=\"reset_fit\"]').click(function() { return confirm('This will reset all the changes you made. Continue?'); });");

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
	global $step, $anonymous;

	$prevd = $step == 1 ? "disabled='disabled' " : '';

	if($step == FINAL_STEP) {
		if($anonymous) {
			$next = "<input type='submit' name='finalize' value='Export fitting' class='final_step' />";
		} else {
			$next = "<input type='submit' name='finalize' value='Finalize fitting' class='final_step' />";
		}
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
	echo "<div id='metadatabox'>\n$before<h2>Attributes</h2>\n<div class='compact' id='computed_attributes'>\n";
	echo "</div>\n$after</div>\n";
	\Osmium\Chrome\print_js_snippet('formatted_attributes');
}

function finalize() {
	global $anonymous;
	$fit = \Osmium\State\get_new_fit();

	if($anonymous) {
		$formats = \Osmium\Fit\get_export_formats();
		$format = $_POST['export_format'];

		if(isset($formats[$format])) {
			header('Content-Type: '.$formats[$format][1]);
			echo $formats[$format][2]($fit);
		}

		die();
	}

	$accountid = \Osmium\State\get_state('a')['accountid'];
	if(isset($fit['metadata']['accountid'])) {
		$ownerid = $fit['metadata']['accountid'];
	} else {
		$ownerid = $accountid;
	}

	\Osmium\Fit\commit_loadout($fit, $ownerid, $accountid);
	$loadoutid = $fit['metadata']['loadoutid'];
	$revision = $fit['metadata']['revision'];
	\Osmium\Fit\reset($fit);
	\Osmium\State\put_new_fit($fit);
	\Osmium\State\put_state('create_fit_step', 1);
	\Osmium\State\invalidate_cache('loadout-'.$loadoutid);
	\Osmium\State\invalidate_cache('loadout-'.$loadoutid.'-'.$revision);

	/* FIXME: make sure commit_loadout() succeeded before doing this */

	\Osmium\Fit\insert_fitting_delta_against_previous_revision(\Osmium\Fit\get_fit($loadoutid));

	$type = ($revision == 1) ? \Osmium\Log\LOG_TYPE_CREATE_LOADOUT : \Osmium\Log\LOG_TYPE_UPDATE_LOADOUT;
	\Osmium\Log\add_log_entry($type, null, $loadoutid, $revision);

	if($revision > 1 && $ownerid != $accountid) {
		\Osmium\Notification\add_notification(
			\Osmium\Notification\NOTIFICATION_TYPE_LOADOUT_EDITED,
			$accountid, $ownerid, $loadoutid, $revision);
	}

	header('Location: ./loadout/'.$loadoutid);
	die();
}

function fetch_parents_and_sort(array &$groups, array $fetchparentsof) {
	while($fetchparentsof !== array()) {
		/* Query in a loop, like a pro! */
		$q = \Osmium\Db\query('SELECT c.parentgroupid, p.marketgroupname AS parentname, c.marketgroupid, c.marketgroupname FROM eve.invmarketgroups AS c LEFT JOIN eve.invmarketgroups AS p ON p.marketgroupid = c.parentgroupid WHERE c.marketgroupid IN ('.implode(',', array_keys($fetchparentsof)).') ORDER BY marketgroupname ASC');

		$fetchparentsof = array();
		while($group = \Osmium\Db\fetch_assoc($q)) {
			if($group['parentgroupid'] === null) continue;

			$groups[$group['marketgroupid']]['parent'] = $group['parentgroupid'];
			$groups[$group['parentgroupid']]['groupname'] = $group['parentname'];
			$groups[$group['parentgroupid']]['subgroups'][$group['marketgroupid']] = true;
			$fetchparentsof[$group['parentgroupid']] = true;
		}
	}

	foreach($groups as &$g) {
		if(!isset($g['subgroups'])) continue;

		/* Sort subgroups alphabetically */
		uksort($g['subgroups'], function($a, $b) use($groups) {
				return strcmp($groups[$a]['groupname'], $groups[$b]['groupname']);
			});
	}
}