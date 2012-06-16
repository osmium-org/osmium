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
	$shortlist = \Osmium\State\get_state_trypersist('shortlist_modules', array());
 
	$out = array();
	$rows = array();
	$req = \Osmium\Db\query_params('SELECT typename, invmodules.typeid FROM osmium.invmodules WHERE invmodules.typeid IN ('.implode(',', $typeids = array_merge(array(-1), $shortlist)).')', array());
	while($row = \Osmium\Db\fetch_row($req)) {
		$rows[$row[1]] = array('typename' => $row[0], 'typeid' => $row[1]);
	}

	$modattr = array();
	\Osmium\Fit\get_attributes_and_effects($typeids, $modattr['cache']);
	foreach($rows as &$row) {
		$row['slottype'] = \Osmium\Fit\get_module_slottype($modattr, $row['typeid']);
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
		'dronepresets' => \Osmium\Fit\get_drone_presets($fit),
		'dpid' => $fit['dronepresetid'],
		'dronepresetdesc' => $fit['dronepresetdesc']
		);
}

function get_slot_usage(&$fit) {
	$usage = array();

	$modules = \Osmium\Fit\get_modules($fit);
	$aslots = \Osmium\Fit\get_attr_slottypes();
	foreach(\Osmium\Fit\get_slottypes() as $type) {
		$usage[$type]['total'] = \Osmium\Dogma\get_ship_attribute($fit, $aslots[$type], false);
		$usage[$type]['used'] = isset($modules[$type]) ? count($modules[$type]) : 0;
	}

	return $usage;
}

function get_loadable_fit(&$fit) {
	return array(
		'ship' => $fit['ship'],
		'modules' => \Osmium\Fit\get_modules($fit),
		'attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($fit),
		'slots' => get_slot_usage($fit),
		'states' => get_module_states($fit),
		'presetid' => $fit['modulepresetid'],
		'presets' => \Osmium\Fit\get_presets($fit),
		'presetdesc' => $fit['modulepresetdesc']
		);
}

function get_module_states(&$fit) {
	$astates = \Osmium\Fit\get_state_names();
	$states = array();

	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $m) {
			list($name, $image) = $astates[$m['state']];
			$states[$type][$index] = array('state' => $m['state'], 'name' => $name, 'image' => $image);
		}
	}

	return $states;
}

function get_fittable_charges(&$fit) {
	$out = array();
	$allowed = array();
	$typeids = array();

	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $module) {
			$chargeid = isset($fit['charges'][$type][$index]) ?
				$fit['charges'][$type][$index]['typeid'] : 0;

			$typeids[$module['typeid']][] = array('type' => $type,
			                                      'index' => $index,
			                                      'typename' => $module['typename'],
			                                      'typeid' => $module['typeid'],
			                                      'chargeid' => $chargeid);
		}
	}

	$in = implode(',', array_keys($typeids));
	if(empty($in)) $in = '-1';
	$chargesq = \Osmium\Db\query('SELECT moduleid, chargeid, chargename FROM osmium.invcharges WHERE moduleid IN ('.$in.') ORDER BY moduleid ASC, chargename ASC');
	while($row = \Osmium\Db\fetch_row($chargesq)) {
		$allowed[$row[0]][] = array('typeid' => $row[1],
		                            'typename' => $row[2]);
	}

	$groups = array();
	$z = 0;
	foreach($allowed as $moduleid => $a) {
		$key = serialize($a);
		if(!isset($groups[$key])) {
			$groups[$key] = $z;
			$index = $z;
			++$z;

			$out[$index]['charges'] = $a;
		} else {
			$index = $groups[$key];
		}

		foreach($typeids[$moduleid] as $moduleattrs) {
			$out[$index]['modules'][] = $moduleattrs;
			
		}
	}

	return $out;
}

function get_loadable_charges(&$fit) {
	return array(
		'attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($fit),
		'presetid' => $fit['modulepresetid'],
		'presets' => \Osmium\Fit\get_presets($fit),
		'cpid' => $fit['chargepresetid'],
		'chargepresets' => \Osmium\Fit\get_charge_presets($fit),
		'chargepresetdesc' => $fit['chargepresetdesc'],
		'charges' => get_fittable_charges($fit)
		);
}