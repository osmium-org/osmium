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

function get_attributes_step_modules_select($fit) {
	$attributes = array();
	$aslots = \Osmium\Fit\get_attr_slottypes();
	foreach(\Osmium\Fit\get_slottypes() as $type) {
		$attributes['ship']['slotcount'][$type] = \Osmium\Dogma\get_ship_attribute($fit, $aslots[$type], false);
		$attributes['ship']['usedslots'][$type] = isset($fit['modules'][$type]) ?
			count($fit['modules'][$type]) : 0;
	}

	$attributes['ship']['turretslots'] = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlots');
	$attributes['ship']['usedturretslots'] = $attributes['ship']['turretslots'] 
		- \Osmium\Dogma\get_ship_attribute($fit, 'turretSlotsLeft');

	$attributes['ship']['launcherslots'] = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlots');
	$attributes['ship']['usedlauncherslots'] = $attributes['ship']['launcherslots']
		- \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlotsLeft');
	
	$attributes['ship']['power'] = \Osmium\Dogma\get_ship_attribute($fit, 'powerOutput');
	$attributes['ship']['usedpower'] = \Osmium\Dogma\get_ship_attribute($fit, 'powerLoad');
	
	$attributes['ship']['cpu'] = \Osmium\Dogma\get_ship_attribute($fit, 'cpuOutput');
	$attributes['ship']['usedcpu'] = \Osmium\Dogma\get_ship_attribute($fit, 'cpuLoad');
	
	$attributes['ship']['upgradecapacity'] = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeCapacity');
	$attributes['ship']['usedupgradecapacity'] = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeLoad');

	return $attributes;
}

function get_data_step_drone_select($fit) {
	return array(
		'drones' => array_values($fit['drones']),
		'attributes' => array(
			'dronecapacity' => \Osmium\Dogma\get_ship_attribute($fit, 'droneCapacity')
			),
		);
}