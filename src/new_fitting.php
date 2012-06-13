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
