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

function ship_select() {
	$fit = \Osmium\State\get_state('new_fit', array());

	print_h1('select ship hull');
	echo "<div id='selectship'>\n";

	$hierarchy = \Osmium\State\get_cache('new_fit_step1_ship_hierarchy', null);
	if($hierarchy === null) {
		$hierarchy = get_ship_hierarchy();
		\Osmium\State\put_cache('new_fit_step1_ship_hierarchy', $hierarchy);
	}

	\Osmium\Forms\print_form_begin();
	\Osmium\Forms\print_generic_row(
		'hullid',
		"<input type='hidden' name='hullid' id='hullidhidden' value='"
		.(isset($fit['ship']['typeid']) ? $fit['ship']['typeid'] : -1)."' />",
		$hierarchy);

	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";

	\Osmium\Chrome\print_js_snippet('new_fitting-step1');
}

function ship_select_pre() { /* Unreachable code for the 1st step */ }

function ship_select_post() {
	$fit = \Osmium\State\get_state('new_fit', array());
	if($fit === array()) {
		\Osmium\Fit\create($fit);
	}

	if(isset($_POST['hullid'])) {
		$shipid = isset($_POST['next_step']) && is_array($_POST['next_step']) ?
			key($_POST['next_step']) : $_POST['hullid'];

		if(!\Osmium\Fit\select_ship($fit, $shipid)) {
			\Osmium\Forms\add_field_error('hullid', "Please select a ship first. (You can still change your mind later!)");
			return false;
		}
	}

	\Osmium\State\put_state('new_fit', $fit);
	return true;
}

function get_ship_hierarchy() {
	$groups = array();
	$fetchparentsof = array();

	$q = \Osmium\Db\query('SELECT typeid, typename, marketgroupid, marketgroupname FROM osmium.invships ORDER BY marketgroupname ASC, typename ASC');
	while($ship = \Osmium\Db\fetch_assoc($q)) {
		$groups[$ship['marketgroupid']]['groupname'] = $ship['marketgroupname'];
		$groups[$ship['marketgroupid']]['types'][$ship['typeid']] = $ship['typename'];

		if($ship['marketgroupid'] !== null) $fetchparentsof[$ship['marketgroupid']] = true;
	}

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

	/* Fix uncategorized ships */
	$groups[null]['groupname'] = 'Uncategorized ships';
	$groups[null]['parent'] = 4;
	$groups[4]['subgroups'][null] = true;

	ob_start();

	\Osmium\Chrome\print_market_group_with_children($groups, 4, 1, function($typeid, $typename) {
			echo "<li data-typeid='".$typeid."'><img src='http://image.eveonline.com/Render/{$typeid}_256.png' alt='' />".htmlspecialchars($typename)." <input type='submit' name='next_step[".$typeid."]' value='select' /></li>\n";
		});

	return ob_get_clean();
}
