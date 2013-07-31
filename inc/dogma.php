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

namespace Osmium\Dogma;

/** Get a map of STATE_* constants to their DOGMA_STATE_* equivalent. */
function get_dogma_states() {
	return array(
		null => DOGMA_STATE_Unplugged,
		\Osmium\Fit\STATE_OFFLINE => DOGMA_STATE_Offline,
		\Osmium\Fit\STATE_ONLINE => DOGMA_STATE_Online,
		\Osmium\Fit\STATE_ACTIVE => DOGMA_STATE_Active,
		\Osmium\Fit\STATE_OVERLOADED => DOGMA_STATE_Overloaded,
	);
}

/** Clear all dogma contexts of a $fit. Includes fleet boosters. */
function clear(&$fit) {
	unset($fit['__dogma_fleet_context']);
	unset($fit['__dogma_context']);

	if(!isset($fit['fleet'])) return;

	foreach($fit['fleet'] as $k => &$fl) {
		clear($fl);
	}
}

/** Create a new dogma context for $fit with all modules, charges and
 * drones of current presets. */
function late_init(&$fit, $withfleet = true) {
	clear($fit);
	dogma_init_context($fit['__dogma_context']);

	if($withfleet) {
		dogma_init_fleet_context($fit['__dogma_fleet_context']);
		dogma_add_squad_member($fit['__dogma_fleet_context'], 0, 0, $fit['__dogma_context']);
	}

	if(isset($fit['ship']['typeid']) && $fit['ship']['typeid'] > 0) {
		dogma_set_ship($fit['__dogma_context'], $fit['ship']['typeid']);
	}

	foreach($fit['modules'] as $type => &$sub) {
		foreach($sub as $index => &$m) {
			dogma_add_module_s($fit['__dogma_context'], $m['typeid'], $m['dogma_index'],
			                   \Osmium\Dogma\get_dogma_states()[$m['state']]);

			if(isset($fit['charges'][$type][$index])) {
				dogma_add_charge(
					$fit['__dogma_context'], $m['dogma_index'],
					$fit['charges'][$type][$index]['typeid']
				);
			}
		}
	}

	foreach($fit['drones'] as $typeid => $d) {
		if($d['quantityinspace'] == 0) continue;

		dogma_add_drone($fit['__dogma_context'], $typeid, $d['quantityinspace']);
	}

	foreach($fit['implants'] as $typeid => &$i) {
		dogma_add_implant(
			$fit['__dogma_context'],
			$i['typeid'],
			$i['dogma_index']
		);

		if(isset($i['sideeffects'])) {
			foreach($i['sideeffects'] as $effectid) {
				dogma_toggle_chance_based_effect(
					$fit['__dogma_context'],
					[ DOGMA_LOC_Implant, "implant_index" => $i['dogma_index'] ],
					(int)$effectid,
					true
				);
			}
		}
	}

	if($withfleet) {
		foreach(array('fleet' => [], 'wing' => [0], 'squad' => [0, 0]) as $type => $params) {
			if(!isset($fit['fleet'][$type])) continue;
			late_init($fit['fleet'][$type], false);
			array_unshift($params, $fit['__dogma_fleet_context']);
			array_push($params, $fit['fleet'][$type]['__dogma_context']);
			call_user_func_array('dogma_add_'.$type.'_commander', $params);
		}
	}
}



/* @internal */
function get_att($att) {
	if(is_string($att) && ctype_digit($att)) {
		return (int)$att;
	} else if(!is_int($att)) {
		return \Osmium\Fit\get_attributeid($att);
	}
	return $att;
}

/**
 * Get the final value of a chararacter attribute.
 */
function get_char_attribute(&$fit, $att) {
	$ret = dogma_get_character_attribute(
		$fit['__dogma_context'],
		get_att($att),
		$val
	);

	return $ret === DOGMA_OK ? $val : false;
}

/**
 * Get the final value of a ship attribute.
 */
function get_ship_attribute(&$fit, $att) {
	switch($att) {

	case 'upgradeLoad':
	case \Osmium\Fit\ATT_UpgradeLoad:
		$load = 0;
		if(!isset($fit['modules']['rig'])) return 0;
		foreach($fit['modules']['rig'] as $index => $m) {
			$load += get_module_attribute($fit, 'rig', $index, 'upgradeCost'); 
		}
		return $load;

	case 'hiSlots':
	case 'medSlots':
	case 'lowSlots':
	case \Osmium\Fit\ATT_HiSlots:
	case \Osmium\Fit\ATT_LowSlots:
	case \Osmium\Fit\ATT_MedSlots:
		dogma_get_ship_attribute($fit['__dogma_context'], get_att($att), $base);
		$att = substr($att, 0, -1);
		if(isset($fit['modules']['subsystem'])) {
			foreach($fit['modules']['subsystem'] as $k => $s) {
				$base += get_module_attribute($fit, 'subsystem', $k, $att.'Modifier');
			}
		}
		return $base;

	case 'turretSlots':
	case 'launcherSlots':
		if(isset($fit['ship']['typeid'])) {
			dogma_type_base_attribute($fit['ship']['typeid'], get_att($att.'Left'), $base);
		} else $base = 0.0;
		$att = substr($att, 0, -5);

	case 'launcherSlotsLeft':
	case 'turretSlotsLeft':
	case \Osmium\Fit\ATT_LauncherSlotsLeft:
	case \Osmium\Fit\ATT_TurretSlotsLeft:
		if(!isset($base)) {
			dogma_get_ship_attribute($fit['__dogma_context'], get_att($att), $base);
			$att = substr($att, 0, -9);
		}
		if(isset($fit['modules']['subsystem'])) {
			foreach($fit['modules']['subsystem'] as $k => $subsystem) {
				$base += get_module_attribute($fit, 'subsystem', $k, $att.'HardPointModifier');
			}
		}
		return $base;

	default:
		$ret = dogma_get_ship_attribute(
			$fit['__dogma_context'],
			get_att($att),
			$val
		);
		return $ret === DOGMA_OK ? $val : false;

	}
}

/**
 * Get the final value of a module attribute (of the current preset).
 */
function get_module_attribute(&$fit, $slottype, $index, $att) {
	if(!isset($fit['modules'][$slottype][$index]['dogma_index'])) {
		return false;
	}

	$ret = dogma_get_module_attribute(
		$fit['__dogma_context'],
		$fit['modules'][$slottype][$index]['dogma_index'],
		get_att($att),
		$val
	);

	return $ret === DOGMA_OK ? $val : false;
}

/**
 * Get the final value of a charge attribute (of the current charge preset).
 */
function get_charge_attribute(&$fit, $slottype, $index, $att) {
	if(!isset($fit['modules'][$slottype][$index]['dogma_index'])) {
		return false;
	}

	$ret = dogma_get_charge_attribute(
		$fit['__dogma_context'],
		$fit['modules'][$slottype][$index]['dogma_index'],
		get_att($att),
		$val
	);

	return $ret === DOGMA_OK ? $val : false;
}

/**
 * Get the final value of a drone attribute (of the current drone preset).
 */
function get_drone_attribute(&$fit, $typeid, $att) {
	$ret = dogma_get_drone_attribute(
		$fit['__dogma_context'],
		$typeid,
		get_att($att),
		$val
	);

	return $ret === DOGMA_OK ? $val : false;
}

/**
 * Get the final value of an implant attribute (of the current preset).
 */
function get_implant_attribute(&$fit, $typeid, $att) {
	if(!isset($fit['implants'][$typeid]['dogma_index'])) {
		return false;
	}

	$ret = dogma_get_implant_attribute(
		$fit['__dogma_context'],
		$fit['implants'][$typeid]['dogma_index'],
		get_att($att),
		$val
	);

	return $ret === DOGMA_OK ? $val : false;
}
