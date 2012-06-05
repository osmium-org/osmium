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

namespace Osmium\AjaxCommon;


function get_module_shortlist($shortlist = null) {
	if(!\Osmium\State\is_logged_in()) return array();

	if($shortlist === null) {
		$shortlist = unserialize(\Osmium\State\get_setting('shortlist_modules', serialize(array())));
	}
 
	$out = array();
	$rows = array();
	$req = \Osmium\Db\query_params('SELECT typename, invmodules.typeid FROM osmium.invmodules WHERE invmodules.typeid IN ('.implode(',', $typeids = array_merge(array(-1), $shortlist)).')', array());
	while($row = \Osmium\Db\fetch_row($req)) {
		$rows[$row[1]] = array('typename' => $row[0], 'typeid' => $row[1]);
	}

	$modattr = array();
	\Osmium\Fit\get_attributes_and_effects($typeids, $modattr);
	foreach($rows as &$row) {
		$row['slottype'] = \Osmium\Fit\get_module_slottype($modattr[$row['typeid']]['effects']);
	}

	foreach($shortlist as $typeid) {
		if(!isset($rows[$typeid])) continue;
		$out[] = $rows[$typeid];
	}

	return $out;
}

function get_data_step_drone_select($fit) {
	return array(
		'drones' => array_values($fit['drones']),
		'attributes' => array(
			'dronecapacity' => \Osmium\Dogma\get_ship_attribute($fit, 'droneCapacity'),
			'dronebandwidth' => \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth'),
			),
		'computed_attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($fit),
		);
}

function get_slot_usage(&$fit) {
	$usage = array();

	$aslots = \Osmium\Fit\get_attr_slottypes();
	foreach(\Osmium\Fit\get_slottypes() as $type) {
		$usage[$type]['total'] = \Osmium\Dogma\get_ship_attribute($fit, $aslots[$type], false);
		$usage[$type]['used'] = isset($fit['modules'][$type]) ?
			count($fit['modules'][$type]) : 0;
	}

	return $usage;
}

function get_loadable_fit(&$fit) {
	return array(
		'ship' => $fit['ship'], 
		'modules' => $fit['modules'],
		'attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($fit),
		'slots' => get_slot_usage($fit),
		'states' => get_module_states($fit),
		);
}

function get_module_states(&$fit) {
	$astates = \Osmium\Fit\get_state_names();
	$states = array();

	foreach(\Osmium\Fit\get_stateful_slottypes() as $type) {
		if(!isset($fit['modules'][$type])) continue;
		
		foreach($fit['modules'][$type] as $index => $m) {
			list($name, $image) = $astates[$m['state']];
			$states[$type][$index] = array('state' => $m['state'], 'name' => $name, 'image' => $image);
		}
	}

	return $states;
}