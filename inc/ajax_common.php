<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

function get_green_fit(&$fit, &$cachename, &$loadoutid, &$revision) {
	$loadoutid = isset($_GET['loadoutid']) ? intval($_GET['loadoutid']) : 0;
	$revision = isset($_GET['revision']) ? intval($_GET['revision']) : 0;
	$cachename = session_id().'_view_fit_'.$loadoutid.'_'.$revision;

	if(!\Osmium\State\is_fit_green($loadoutid)) {
		return false;
	}

	$fit = \Osmium\State\get_cache($cachename, null);
	if($fit === null) {
		$fit = \Osmium\Fit\get_fit($_GET['loadoutid'], $revision);
	}

	if($fit === false) {
		/* Invalid revision queried? */
		return false;
	}

	\Osmium\Fit\use_preset($fit, $_GET['pid']);
	\Osmium\Fit\use_charge_preset($fit, $_GET['cpid']);
	\Osmium\Fit\use_drone_preset($fit, $_GET['dpid']);

	if(isset($_GET['skillset'])) {
		$a = \Osmium\State\get_state('a', null);
		\Osmium\Fit\use_skillset_by_name($fit, $_GET['skillset'], $a);
	}

	return true;
}

function get_module_shortlist() {
    return \Osmium\State\get_state_trypersist('shortlist_modules', array());
}

function get_data_step_drone_select($fit) {
	return array(
		'drones' => array_values($fit['drones']),
		'attributes' => array(
			'dronecapacity' => \Osmium\Dogma\get_ship_attribute($fit, 'droneCapacity'),
			'dronebandwidth' => \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth'),
			),
		'usedbandwidth' => \Osmium\Fit\get_used_drone_bandwidth($fit),
		'computed_attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($fit),
		'dronepresets' => \Osmium\Fit\get_drone_presets($fit),
		'dpid' => $fit['dronepresetid'],
		'dronepresetdesc' => $fit['dronepresetdesc']
		);
}

function get_slot_usage(&$fit) {
	$usage = array();

	foreach(\Osmium\Fit\get_attr_slottypes() as $type => $attr) {
		$usage[$type] = (int)\Osmium\Dogma\get_ship_attribute($fit, $attr, false);
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
		'ranges' => get_module_ranges($fit),
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
	$chargesq = \Osmium\Db\query(
		'SELECT moduleid, chargeid, chargename, metagroupname,
		thdmg.value AS th, kidmg.value AS ki, exdmg.value AS ex, emdmg.value AS em
		FROM osmium.invcharges
		LEFT JOIN eve.dgmtypeattribs thdmg ON thdmg.typeid = chargeid AND thdmg.attributeid = 118
		LEFT JOIN eve.dgmtypeattribs kidmg ON kidmg.typeid = chargeid AND kidmg.attributeid = 117
		LEFT JOIN eve.dgmtypeattribs exdmg ON exdmg.typeid = chargeid AND exdmg.attributeid = 116
		LEFT JOIN eve.dgmtypeattribs emdmg ON emdmg.typeid = chargeid AND emdmg.attributeid = 114
		LEFT JOIN eve.invmetatypes ON invmetatypes.typeid = chargeid
		LEFT JOIN eve.invmetagroups ON invmetagroups.metagroupid = COALESCE(invmetatypes.metagroupid, 1)
		WHERE moduleid IN ('.$in.')
		ORDER BY moduleid ASC, COALESCE(invmetatypes.metagroupid, 1) ASC,
		(COALESCE(thdmg.value, 0) + COALESCE(kidmg.value, 0) + COALESCE(exdmg.value, 0)
			+ COALESCE(emdmg.value, 0)) DESC,
		chargename ASC');
	while($row = \Osmium\Db\fetch_assoc($chargesq)) {
		$damage = array_filter(array('Th' => $row['th'],
		                             'Kin' => $row['ki'],
		                             'Exp' => $row['ex'],
		                             'EM' => $row['em']));
		arsort($damage);
		$fdamage = implode('/', array_keys($damage));

		$allowed[$row['moduleid']][$row['metagroupname']][] =
			array('typeid' => $row['chargeid'],
			      'typename' => $row['chargename'],
			      'damagetypes' => $fdamage,
			      );
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

/** @deprecated */
function get_module_ranges($fit) {
	$ranges = array();
	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $m) {
			$r = \Osmium\Fit\get_module_interesting_attributes($fit, $type, $index);
			if($r === array()) {
				$ranges[$type][$index] = array('', '');
				continue;
			}

			$ranges[$type][$index] = array(
				\Osmium\Chrome\format_short_range($r),
				\Osmium\Chrome\format_long_range($r)
				);
		}
	}

	return $ranges;
}

/**
 * Generate formatted interesting attributes for all the modules in
 * the fit.
 *
 * @returns an array of array(slottype, index, shortformat,
 * longformat).
 */
function get_modules_interesting_attributes(&$fit) {
	$attrs = array();
	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $m) {
			$a = \Osmium\Fit\get_module_interesting_attributes($fit, $type, $index);
			$fashort = \Osmium\Chrome\format_short_range($a);

			if(empty($fashort)) continue;
			$falong = \Osmium\Chrome\format_long_range($a);

			$attrs[] = array(
				$type, $index,
				$fashort, $falong,
			);
		}
	}

	return $attrs;
}
