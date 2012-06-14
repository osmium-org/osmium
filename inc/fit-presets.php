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

	$fit['modulepresetid'] = $presetid;
	$fit['modulepresetname'] =& $fit['presets'][$presetid]['name'];
	$fit['modulepresetdesc'] =& $fit['presets'][$presetid]['description'];
	$fit['modules'] =& $fit['presets'][$presetid]['modules'];
	$fit['chargepresets'] =& $fit['presets'][$presetid]['chargepresets'];

	if(count($fit['chargepresets']) == 0) {
		/* Add an empty preset */
		create_charge_preset($fit, 'Default charge preset', '');
	}

	foreach($fit['modules'] as $type => $a) {
		foreach($a as $index => $module) {
			online_module($fit, $type, $index);
		}
	}

	/* The backslash here is important, because \Osmium\Fit\reset() is
	 * a totally different function! */
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

/**
 * Switch to a specific drone preset.
 */
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
 * Create a new preset. The name must not coincide with another
 * preset.
 *
 * @returns the preset id of the new preset.
 */
function create_preset(&$fit, $name, $description) {
	foreach($fit['presets'] as $presetid => $preset) {
		if($preset['name'] === $name) {
			// @codeCoverageIgnoreStart
			trigger_error('create_preset(): another preset with the same name already exists', E_USER_WARNING);
			return false;
			// @codeCoverageIgnoreEnd
		}
	}

	$preset = array(
		'name' => $name,
		'description' => $description,
		'modules' => array(),
		'chargepresets' => array()
		);

	$fit['presets'][] = $preset;
	end($fit['presets']);
	return key($fit['presets']);
}

/**
 * Create a new charge preset in the current preset. The name must not
 * coincide with another charge preset in the same preset.
 *
 * @returns the charge preset id (cpid) of the new charge preset.
 */
function create_charge_preset(&$fit, $name, $description) {
	foreach($fit['chargepresets'] as $cpid => $chargepreset) {
		if($chargepreset['name'] === $name) {
			// @codeCoverageIgnoreStart
			trigger_error('create_charge_preset(): another charge preset with the same name already exists in the current preset', E_USER_WARNING);
			return false;
			// @codeCoverageIgnoreEnd
		}
	}

	$chargepreset = array(
		'name' => $name,
		'description' => $description,
		'charges' => array()
		);

	$fit['chargepresets'][] = $chargepreset;
	end($fit['chargepresets']);
	return key($fit['chargepresets']);
}

function create_drone_preset(&$fit, $name, $description) {
	foreach($fit['dronepresets'] as $dpid => $dronepreset) {
		if($dronepreset['name'] === $name) {
			// @codeCoverageIgnoreStart
			trigger_error('create_drone_preset(): another drone preset with the same name already exists', E_USER_WARNING);
			return false;
			// @codeCoverageIgnoreEnd
		}
	}

	$dronepreset = array(
		'name' => $name,
		'description' => $description,
		'drones' => array()
		);

	$fit['dronepresets'][] = $dronepreset;
	end($fit['dronepresets']);
	return key($fit['dronepresets']);
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

/**
 * Removes a drone preset. If it is being used, this function will try
 * to switch to another preset if possible, or throw an error.
 */
function remove_drone_preset(&$fit, $dpid) {
	if(!isset($fit['dronepresets'][$dpid])) return;

	if(count($fit['dronepresets']) == 1) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_drone_preset(): cannot remove the last drone preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['dronepresetid']) && $fit['dronepresetid'] === $dpid) {
		/* Switch to another drone preset */
		foreach($fit['dronepresets'] as $id => $dronepreset) {
			if($id === $dpid) continue;

			use_drone_preset($fit, $id);
			break;
		}
	}

	$typeids = array();
	foreach($fit['dronepresets'][$dpid]['drones'] as $drone) {
		$typeids[$drone['typeid']] = true;
	}

	unset($fit['dronepresets'][$dpid]);

	foreach($typeids as $tid => $true) {
		maybe_remove_charge($fit, $tid);
	}
}

/** @internal */
function rename_preset_generic(&$presets_array, $id, $newname) {
	foreach($presets_array as $presetid => &$preset) {
		if($presetid === $id) continue;

		if($preset['name'] === $newname) {
			// @codeCoverageIgnoreStart
			trigger_error('rename_preset_generic(): new preset name conflicts with another preset', E_USER_WARNING);
			return false;
			// @codeCoverageIgnoreEnd
		}
	}

	$presets_array[$id]['name'] = $newname;
	return true;
}

/**
 * Rename the current preset. Will throw an error if the new names
 * conflicts with another preset name.
 */
function rename_preset(&$fit, $newname) {
	return rename_preset_generic($fit['presets'], $fit['modulepresetid'], $newname);
}

/** @see rename_preset() */
function rename_charge_preset(&$fit, $newname) {
	return rename_preset_generic($fit['chargepresets'], $fit['chargepresetid'], $newname);
}

/** @see rename_preset() */
function rename_drone_preset(&$fit, $newname) {
	return rename_preset_generic($fit['dronepresets'], $fit['dronepresetid'], $newname);
}

/** @internal */
function clone_preset_generic(&$presets_array, $id, $newname) {
	foreach($presets_array as $presetid => &$preset) {
		if($preset['name'] === $newname) {
			// @codeCoverageIgnoreStart
			trigger_error('clone_preset_generic(): preset name of clone conflicts with another preset', E_USER_WARNING);
			return false;
			// @codeCoverageIgnoreEnd
		}
	}

	/* A deep copy need to be made, because a simple copy will just reuse the same references. */
	/* Yes, this is a hack, and it's (very slightly) faster than unserialize(serialize(...)). */
	$clone = json_decode(json_encode($presets_array[$id]), true);
	$clone['name'] = $newname;

	/* Modules of the cloned presets should be offline */
	foreach($clone['modules'] as &$a) {
		foreach($a as &$module) {
			$module['old_state'] = $module['state'];
			$module['state'] = null;
		}
	}

	$presets_array[] = $clone;

	end($presets_array);
	return key($presets_array);
}

/**
 * Clone the current preset. Will throw an error if the clone name
 * conflicts with another preset name.
 *
 * @returns false on error, or the presetid of the clone.
 */
function clone_preset(&$fit, $newname) {
	return clone_preset_generic($fit['presets'], $fit['modulepresetid'], $newname);
}

/** @see clone_preset() */
function clone_charge_preset(&$fit, $newname) {
	return clone_preset_generic($fit['chargepresets'], $fit['chargepresetid'], $newname);
}

/** @see clone_preset() */
function clone_drone_preset(&$fit, $newname) {
	return clone_preset_generic($fit['dronepresets'], $fit['dronepresetid'], $newname);
}
