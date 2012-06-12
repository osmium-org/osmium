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

namespace Osmium\Fit;

/**
 * Switch to a specific preset.
 */
function use_preset(&$fit, $presetid) {
	if(!isset($fit['presets'][$presetid])) {
		// @codeCoverageIgnoreStart
		trigger_error('use_preset(): no such preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['modulepresetid'])) {
		if($presetid === $fit['modulepresetid']) return;

		foreach($fit['modules'] as $type => $a) {
			foreach($a as $index => $module) {
				offline_module($fit, $type, $index);
			}
		}
	}

	unset($fit['chargepresetid']);
	unset($fit['chargepresetname']);
	unset($fit['chargepresetdesc']);
	unset($fit['charges']);

	$fit['modulepresetid'] = $presetid;
	$fit['modulepresetname'] =& $fit['presets'][$presetid]['name'];
	$fit['modulepresetdesc'] =& $fit['presets'][$presetid]['description'];
	$fit['modules'] =& $fit['presets'][$presetid]['modules'];
	$fit['chargepresets'] =& $fit['presets'][$presetid]['chargepresets'];

	if(count($fit['chargepresets']) == 0) {
		/* Add an empty preset */
		$fit['chargepresets'][0] = array(
			'name' => '',
			'description' => '',
			'charges' => array()
			);
	}

	\reset($fit['chargepresets']);
	use_charge_preset($fit, key($fit['chargepresets']));
}

/**
 * Switch to a specific charge preset.
 *
 * @param $cpid the charge preset id to switch to.
 */
function use_charge_preset(&$fit, $cpid) {
	if(!isset($fit['chargepresets'][$cpid])) {
		// @codeCoverageIgnoreStart
		trigger_error('use_charge_preset(): no such preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['chargepresetid'])) {
		if($cpid === $fit['chargepresetid']) return;

		foreach($fit['charges'] as $type => $a) {
			foreach($a as $index => $charge) {
				offline_charge($fit, $type, $index);
			}
		}
	}

	$fit['chargepresetid'] = $cpid;
	$fit['chargepresetname'] =& $fit['chargepresets'][$cpid]['name'];
	$fit['chargepresetdesc'] =& $fit['chargepresets'][$cpid]['description'];
	$fit['charges'] =& $fit['chargepresets'][$cpid]['charges'];

	foreach($fit['charges'] as $type => $a) {
		foreach($a as $index => $charge) {
			online_charge($fit, $type, $index);
		}
	}
}

function use_drone_preset(&$fit, $dpid) {
	if(!isset($fit['dronepresets'][$dpid])) {
		// @codeCoverageIgnoreStart
		trigger_error('use_drone_preset(): no such preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	$fit['dronepresetid'] = $dpid;
	$fit['dronepresetname'] =& $fit['dronepresets'][$dpid]['name'];
	$fit['dronepresetdesc'] =& $fit['dronepresets'][$dpid]['description'];
	$fit['drones'] =& $fit['dronepresets'][$dpid]['drones'];
}

/**
 * Remove a preset, including any charge preset it contains. If the
 * preset being removed is the current preset, this function will try
 * to switch to another preset, or throw an error.
 */
function remove_preset(&$fit, $presetid) {
	if(!isset($fit['presets'][$presetid])) return;

	if(count($fit['presets']) == 1) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_preset(): cannot remove the last preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['modulepresetid']) && $fit['modulepresetid'] == $presetid) {
		/* Switch to another preset */
		foreach($fit['presets'] as $id => $preset) {
			if($id === $presetid) continue;

			use_preset($fit, $id);
			break;
		}
	}

	/* Collect the typeIDs of modules but also charges in all the charge presets */
	$typeids = array();
	foreach($fit['presets'][$presetid]['modules'] as $type => $a) {
		foreach($a as $index => $module) {
			$typeids[$module['typeid']] = true;
		}
	}
	foreach($fit['presets'][$presetid]['chargepresets'] as $chargepreset) {
		foreach($chargepreset['charges'] as $type => $a) {
			foreach($a as $index => $charge) {
				$typeids[$charge['typeid']] = true;
			}
		}
	}

	unset($fit['presets'][$presetid]);

	foreach($typeids as $tid => $true) {
		maybe_remove_cache($fit, $tid);
	}
}

/**
 * Completely remove a charge preset. If the preset being removed is
 * the currently selected charge preset, this function will switch to
 * another charge preset, or throw an error if it there is no other
 * charge preset to switch to.
 */
function remove_charge_preset(&$fit, $cpid) {
	if(!isset($fit['chargepresets'][$cpid])) return;

	if(count($fit['chargepresets']) == 1) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_charge_preset(): cannot remove the last charge preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['chargepresetid']) && $fit['chargepresetid'] === $cpid) {
		/* Use the first charge presets that is not the one being removed */
		foreach($fit['chargepresets'] as $id => $preset) {
			if($id === $cpid) continue;

			use_charge_preset($fit, $id);
			break;
		}
	}

	/* Collect the typeIDs of charges we are about to remove */
	$typeids = array();
	foreach($fit['chargepresets'][$cpid]['charges'] as $type => $a) {
		foreach($a as $index => $charge) {
			$typeids[$charge['typeid']] = true;
		}
	}

	unset($fit['chargepresets'][$cpid]);

	foreach($typeids as $tid => $true) {
		maybe_remove_cache($fit, $tid);
	}
}