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

function get_attributename($attributeid) {
	static $cache = null;
	if($cache === null) {
		$cache = \Osmium\State\get_cache_memory('dogma_attribute_map', null);
		if($cache === null) {
			$cache = array();
			$q = \Osmium\Db\query('SELECT attributename, attributeid FROM eve.dgmattribs');
			while($r = \Osmium\Db\fetch_row($q)) {
				$cache[$r[1]] = $r[0];
			}
			\Osmium\State\put_cache_memory('dogma_attribute_map', $cache);
		}
	}

	if(!isset($cache[$attributeid])) {
		// @codeCoverageIgnoreStart
		trigger_error('get_attributename(): unknown attributeid "'.$attributeid.'"', E_USER_ERROR);
		// @codeCoverageIgnoreEnd
	}

	return $cache[$attributeid];
}

function get_attributeid($attributename) {
	static $cache = null;
	if($cache === null) {
		$cache = \Osmium\State\get_cache_memory('dogma_attribute_map_flipped', null);
		if($cache === null) {
			$cache = array();
			$q = \Osmium\Db\query('SELECT attributename, attributeid FROM eve.dgmattribs');
			while($r = \Osmium\Db\fetch_row($q)) {
				$cache[$r[0]] = $r[1];
			}
			\Osmium\State\put_cache_memory('dogma_attribute_map_flipped', $cache);
		}
	}

	if(!isset($cache[$attributename])) {
		// @codeCoverageIgnoreStart
		trigger_error('get_attributeid(): unknown attributename "'.$attributename.'"', E_USER_ERROR);
		// @codeCoverageIgnoreEnd
	}

	return (int)$cache[$attributename];
}

/* @internal __deprecated__ */
function get_att($att) {
	if(is_string($att) && ctype_digit($att)) {
		return (int)$att;
	} else if(!is_int($att)) {
		return get_attributeid($att);
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
	if($att === 'upgradeLoad') {
		$load = 0; /* XXX */
		if(!isset($fit['modules']['rig'])) return 0;

		foreach($fit['modules']['rig'] as $index => $m) {
			$load += get_module_attribute($fit, 'rig', $index, 'upgradeCost'); 
		}

		return $load;
	} else if(in_array($att, array('hiSlots', 'medSlots', 'lowSlots'))) {
		dogma_get_ship_attribute($fit['__dogma_context'], get_att($att), $base);
		$att = substr($att, 0, -1);

		if(isset($fit['dogma']['modules']['subsystem'])) {
			foreach($fit['dogma']['modules']['subsystem'] as $k => $s) {
				$base += get_module_attribute($fit, 'subsystem', $k, $att.'Modifier');
			}
		}

		return $base;
	} else if(in_array($att, array('turretSlots', 'launcherSlots'))) {
		dogma_get_ship_attribute($fit['__dogma_context'], get_att($att.'Left'), $base);
		$att = substr($att, 0, -5);

		if(isset($fit['dogma']['modules']['subsystem'])) {
			foreach($fit['dogma']['modules']['subsystem'] as $k => $subsystem) {
				$base += get_module_attribute($fit, 'subsystem', $k, $att.'HardPointModifier');
			}
		}

		return $base;
	}

	$ret = dogma_get_ship_attribute(
		$fit['__dogma_context'],
		get_att($att),
		$val
	);

	return $ret === DOGMA_OK ? $val : false;
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
