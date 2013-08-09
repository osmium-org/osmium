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

namespace Osmium\Fit;

/**
 * Switch to a specific preset.
 */
function use_preset(&$fit, $presetid, $createdefaultchargepreset = true) {
	if(!isset($fit['presets'][$presetid])) {
		// @codeCoverageIgnoreStart
		trigger_error('use_preset(): no such preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['modulepresetid'])) {
		if($presetid === $fit['modulepresetid']) return;

		if(\Osmium\Dogma\has_context($fit)) {
			foreach($fit['modules'] as $type => $a) {
				foreach($a as $index => $module) {
					dogma_remove_module(
						$fit['__dogma_context'],
						$module['dogma_index']
					);
				}
			}

			foreach($fit['implants'] as $typeid => $i) {
				dogma_remove_implant(
					$fit['__dogma_context'],
					$i['dogma_index']
				);
			}
		}
	}

	unset($fit['chargepresetid']);

	$fit['modulepresetid'] = $presetid;
	$fit['modulepresetname'] =& $fit['presets'][$presetid]['name'];
	$fit['modulepresetdesc'] =& $fit['presets'][$presetid]['description'];
	$fit['modules'] =& $fit['presets'][$presetid]['modules'];
	$fit['chargepresets'] =& $fit['presets'][$presetid]['chargepresets'];
	$fit['implants'] =& $fit['presets'][$presetid]['implants'];

	if(count($fit['chargepresets']) == 0 && $createdefaultchargepreset) {
		/* Add an empty preset */
		create_charge_preset($fit, 'Default charge preset', '');
	}

	if(\Osmium\Dogma\has_context($fit)) {
		foreach($fit['modules'] as $type => &$a) {
			foreach($a as $index => &$module) {
				dogma_add_module_s(
					$fit['__dogma_context'],
					$module['typeid'],
					$module['dogma_index'],
					\Osmium\Dogma\get_dogma_states()[$module['state']]
				);
			}
		}
	}

	if($createdefaultchargepreset) {
		/* The backslash here is important, because
		 * \Osmium\Fit\reset() is a totally different function! */
		\reset($fit['chargepresets']);
		use_charge_preset($fit, key($fit['chargepresets']));
	}

	if(\Osmium\Dogma\has_context($fit)) {
		foreach($fit['implants'] as $typeid => &$i) {
			dogma_add_implant(
				$fit['__dogma_context'],
				$i['typeid'],
				$i['dogma_index']
			);
		}
	}
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

		if(\Osmium\Dogma\has_context($fit)) {
			foreach($fit['charges'] as $type => $a) {
				foreach($a as $index => $charge) {
					dogma_remove_charge(
						$fit['__dogma_context'],
						$fit['modules'][$type][$index]['dogma_index']
					);
				}
			}
		}
	}

	$fit['chargepresetid'] = $cpid;
	$fit['chargepresetname'] =& $fit['chargepresets'][$cpid]['name'];
	$fit['chargepresetdesc'] =& $fit['chargepresets'][$cpid]['description'];
	$fit['charges'] =& $fit['chargepresets'][$cpid]['charges'];

	if(\Osmium\Dogma\has_context($fit)) {
		foreach($fit['charges'] as $type => $a) {
			foreach($a as $index => $charge) {
				dogma_add_charge(
					$fit['__dogma_context'],
					$fit['modules'][$type][$index]['dogma_index'],
					$charge['typeid']
				);
			}
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

	if(isset($fit['drones']) && \Osmium\Dogma\has_context($fit)) {
		foreach($fit['drones'] as $typeid => $d) {
			dogma_remove_drone($fit['__dogma_context'], $typeid);
		}
	}

	$fit['dronepresetid'] = $dpid;
	$fit['dronepresetname'] =& $fit['dronepresets'][$dpid]['name'];
	$fit['dronepresetdesc'] =& $fit['dronepresets'][$dpid]['description'];
	$fit['drones'] =& $fit['dronepresets'][$dpid]['drones'];

	if(\Osmium\Dogma\has_context($fit)) {
		foreach($fit['drones'] as $typeid => $d) {
			if($d['quantityinspace'] == 0) continue;
			dogma_add_drone($fit['__dogma_context'], $typeid, $d['quantityinspace']);
		}
	}
}

/** @internal */
function create_preset_generic(&$fit, $presettype, $preset, $newid = null) {
	foreach($fit[$presettype] as $presetid => $p) {
		if($p['name'] == $preset['name']) {
			// @codeCoverageIgnoreStart
			trigger_error(__FUNCTION__.'(): another preset with the same name already exists', E_USER_WARNING);
			return false;
			// @codeCoverageIgnoreEnd
		}
	}

	if($newid === null) {
		$fit[$presettype][] = $preset;
		end($fit[$presettype]);
		$newid = key($fit[$presettype]);
	} else {
		if(isset($fit[$presettype][$newid])) {
			// @codeCoverageIgnoreStart
			trigger_error(__FUNCTION__.'(): there is already a preset with given new id', E_USER_WARNING);
			return false;
			// @codeCoverageIgnoreEnd
		} else {
			$fit[$presettype][$newid] = $preset;
		}
	}

	return $newid;
}

/**
 * Create a new preset. The name must not coincide with another
 * preset.
 *
 * @returns the preset id of the new preset.
 */
function create_preset(&$fit, $name, $description, $newid = null) {
	$preset = array(
		'name' => $name,
		'description' => $description,
		'modules' => array(),
		'chargepresets' => array(),
		'implants' => array(),
		);

	return create_preset_generic($fit, 'presets', $preset, $newid);
}

/**
 * Create a new charge preset in the current preset. The name must not
 * coincide with another charge preset in the same preset.
 *
 * @returns the charge preset id (cpid) of the new charge preset.
 */
function create_charge_preset(&$fit, $name, $description, $newid = null) {
	$chargepreset = array(
		'name' => $name,
		'description' => $description,
		'charges' => array()
		);

	return create_preset_generic($fit, 'chargepresets', $chargepreset, $newid);
}

/**
 * Create a new drone preset. The name must not coincide with another
 * drone preset.
 *
 * @returns the preset id of the new drone preset.
 */
function create_drone_preset(&$fit, $name, $description, $newid = null) {
	$dronepreset = array(
		'name' => $name,
		'description' => $description,
		'drones' => array()
		);

	return create_preset_generic($fit, 'dronepresets', $dronepreset, $newid);
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

	unset($fit['presets'][$presetid]);
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

	unset($fit['chargepresets'][$cpid]);
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

	unset($fit['dronepresets'][$dpid]);
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

/** @internal */
function get_presets_generic($presets_array) {
	$ret = array();
	foreach($presets_array as $id => $preset) {
		$ret[] = array($id, $preset['name']);
	}
	return $ret;
}

/**
 * Get an array of presets, each element being an array(<presetid>,
 * <presetname>).
 */
function get_presets($fit) {
	return get_presets_generic($fit['presets']);
}

/** @see get_presets() */
function get_charge_presets($fit) {
	return get_presets_generic($fit['chargepresets']);
}

/** @see get_presets() */
function get_drone_presets($fit) {
	return get_presets_generic($fit['dronepresets']);
}
