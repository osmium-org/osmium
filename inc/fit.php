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

/*
 * KEEP THIS NAMESPACE PURE.
 * 
 * Functions in \Osmium\Fit must not affect or rely on the global
 * state at all. This makes it easier to test and debug.
 */

require __DIR__.'/fit-attributes.php';
require __DIR__.'/fit-db.php';
require __DIR__.'/fit-importexport.php';


/** The loadout can be viewed by everyone. */
const VIEW_EVERYONE = 0;

/** The loadout can be viewed by everyone, provided they have the
 * password. This mode implies VISIBILITY_PRIVATE.*/
const VIEW_PASSWORD_PROTECTED = 1;

/** The loadout can only be viewed by characters in the same alliance
 * than the author. */
const VIEW_ALLIANCE_ONLY = 2;

/** The loadout can only be viewed by characters in the same
 * corporation than the author. */
const VIEW_CORPORATION_ONLY = 3;

/** The loadout can only be viewed by its author. */
const VIEW_OWNER_ONLY = 4;


/** The loadout can only be edited by its author. */
const EDIT_OWNER_ONLY = 0;

/** The loadout can only be edited by its author and people in the
 * same corporation with the "Fitting Manager" role (or directors). */
const EDIT_OWNER_AND_FITTING_MANAGER_ONLY = 1;

/** The loadout can be edited by its author and anyone in the same
 * corporation. */
const EDIT_CORPORATION_ONLY = 2;

/** The loadout can be edited by its author and everyone in the same
 * alliance. */
const EDIT_ALLIANCE_ONLY = 3;


/** The loadout can be indexed by the Osmium search engine and other
 * search engines, and will appear on search results when appropriate
 * (conforming with the view permission). */
const VISIBILITY_PUBLIC = 0;

/** The loadout can never appear on any search results and will never
 * be indexed. It is still accessible to anyone (conforming with the
 * view permission) provided they have manually been given the URI. */
const VISIBILITY_PRIVATE = 1;


/** Offline module. (Such modules do not use CPU/Power.) */
const STATE_OFFLINE = 0;

/** Online module. */
const STATE_ONLINE = 1;

/** Active module (assumes online). */
const STATE_ACTIVE = 2;

/** Overloaded module (assumes active). */
const STATE_OVERLOADED = 3;


/* ----------------------------------------------------- */

/** Get the different module slot categories. */
function get_slottypes() {
	return array('high', 'medium', 'low', 'rig', 'subsystem');
}

/**
 * Get all the module slot categories which contain stateful modules,
 * ie modules that can be activated, overloaded, offlined etc.
 */
function get_stateful_slottypes() {
	/* Rigs and subsystems cannot be offlined/activated/overloaded */
	return array('high', 'medium', 'low');
}

/**
 * Get an array of all the attribute names defining the number of
 * slots available on a ship.
 */
function get_attr_slottypes() {
	return array(
		'high' => 'hiSlots',
		'medium' => 'medSlots',
		'low' => 'lowSlots',
		'rig' => 'upgradeSlotsLeft',
		'subsystem' => 'maxSubSystems'
		);
}

/**
 * Get an array of formatted state names, with the name of the picture
 * represting the state.
 */
function get_state_names() {
	return array(
		STATE_OFFLINE => array('Offline', 'offline.png'),
		STATE_ONLINE => array('Online', 'online.png'),
		STATE_ACTIVE => array('Active', 'active.png'),
		STATE_OVERLOADED => array('Overloaded', 'overloaded.png'),
		);
}

/**
 * Get an array of categories of effects that should be activated on a
 * per-state basis.
 */
function get_state_categories() {
	return array(
		null => array(),
		STATE_OFFLINE => array(0),
		STATE_ONLINE => array(0, 4),
		STATE_ACTIVE => array(0, 4, 1, 2, 3),
		STATE_OVERLOADED => array(0, 4, 1, 2, 3, 5),
		);
}

/**
 * Initialize a new fitting in variable $fit.
 *
 * FIXME: do not rely on this structure anywhere outside this
 * namespace (use proper accessors/mutators, especially in src/)
 * 
 * Structure of the created array:
 * 
 * ship => array(typeid, typename)
 * 
 * modules => array(<slot_type> => array(<index> => array(typeid, typename, state)))
 * 
 * charges => array(<preset_name> => array(<slot_type> => array(index => array(typeid, typename))))
 * 
 * selectedpreset => (string)
 * 
 * drones => array(<typeid> => array(typeid, typename, volume, bandwidth, quantityin{bay, space}))
 *
 * dogma => array({char,
 *                 ship,
 *                 modules => <slot_type> => <index>,
 *                 charges => <preset_name> => <slot_type> => <index>,
 *                 drones => <typeid>,
 *                 skills => <typeid>}
 *                 => array(<attributename> => (base value),
 *                          __modifiers => array(
 *                              <attributename> =>
 *                                  array(<action> =>
 *                                      array(<group> =>
 *                                          array(array(name, source => [modifier source])))))))
 *
 * cache => array(__attributes => array({<attributename>, <attributeid>} =>
 *                                          array(attributename, defaultvalue, stackable, highisgood))
 *                __effects => array({<effectname>, <effectid>} =>
 *                                       array(effectcategory,
 *                                             {duration, trackingspeed, discharge, range, falloff}attributeid))
 *                <typeid> => array(attributes =>
 *                                      array(<attributename> =>
 *                                          array(attributename, attributeid, value)),
 *                                  effects =>
 *                                      array(<effectname> =>
 *                                          array(effectid, effectname, preexp, postexp)))
 */
function create(&$fit) {
	$fit = array(
		'ship' => array(),
		'modules' => array(),
		'charges' => array(),
		'drones' => array(),
		'selectedpreset' => null,
		'dogma' => array(),
		'cache' => array(),
		);

	/* Apply the default skill modifiers */
	\Osmium\Dogma\eval_skill_preexpressions($fit);
}

/**
 * Delete a fitting so that memory can be claimed back by the garbage
 * collector.
 */
function destroy(&$fit) {
	$fit = array();
}

/**
 * Reset a fitting. This is the same as calling destroy() then
 * create() immediately after.
 */
function reset(&$fit) {
	destroy($fit);
	create($fit);
}

/**
 * Change the ship of a fitting. This can be done anytime, even when
 * there are fitted modules and whatnot.
 */
function select_ship(&$fit, $new_typeid) {
	if(isset($fit['ship']['typeid'])) {
		/* Switching hull */
		\Osmium\Dogma\eval_ship_postexpressions($fit);
		$old_typeid = $fit['ship']['typeid'];
		$fit['ship'] = array();
		if($old_typeid != $new_typeid) {
			maybe_remove_cache($fit, $old_typeid);
		}
	}

	foreach($fit['dogma']['ship'] as $key => $value) {
		if($key === '__modifiers') continue;
		unset($fit['dogma']['ship'][$key]);
	}

	$row = \Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT invships.typename, mass FROM osmium.invships JOIN eve.invtypes ON invtypes.typeid = invships.typeid WHERE invships.typeid = $1', 
			array($new_typeid)));
	if($row === false) return false;

	$fit['ship'] = array(
		'typeid' => $new_typeid,
		'typename' => $row[0],
		);
	
	get_attributes_and_effects(array($new_typeid), $fit['cache']);

	foreach($fit['cache'][$fit['ship']['typeid']]['attributes'] as $attr) {
		$fit['dogma']['ship'][$attr['attributename']] = $attr['value'];
	}
	$fit['dogma']['ship']['mass'] = $row[1];
	$fit['dogma']['ship']['typeid'] =& $fit['ship']['typeid'];

	/* Mass is in invtypes, not dgmtypeattribs, so it has to be hardcoded here */
	$fit['cache']['__attributes']['mass'] = array(
		'attributename' => 'mass',
		'stackable' => 0,
		'highisgood' => 1,
		);
	$fit['cache']['__attributes']['4'] =& $fit['cache']['__attributes']['mass'];
	
	\Osmium\Dogma\eval_ship_preexpressions($fit);

	return true;
}

/**
 * Add several modules to the fit. This is more efficient than calling
 * add_module() multiple times.
 *
 * @param $modules array of modules to add, should be of the form
 * array(slot_type => array(index => typeid)), or array(slot_type =>
 * array(index => array(typeid, modulestate))).
 */
function add_modules_batch(&$fit, $modules) {
	$typeids = array();
	foreach($modules as $type => $a) {
		foreach($a as $index => $magic) {
			if(is_array($magic)) $typeid = $magic[0];
			else $typeid = $magic;

			$typeids[$typeid] = true;
		}
	}

	get_attributes_and_effects(array_keys($typeids), $fit['cache']);

	foreach($modules as $type => $a) {
		foreach($a as $index => $magic) {
			if(is_array($magic)) {
				/* Got a typeid + state */
				list($typeid, $state) = $magic;
				add_module($fit, $index, $typeid, $state);
			} else {
				/* Got a typeid */
				add_module($fit, $index, $magic);
			}
		}
	}
}

/**
 * Add a module to the fit at a specific index. This will gracefully
 * overwrite any existing module of the same type at the same index if
 * there is one.
 *
 * @param $state state of the module (on of the STATE_* constants). If
 * unspecified, the default is STATE_ACTIVE or STATE_ONLINE for
 * modules which cannot be activated.
 */
function add_module(&$fit, $index, $typeid, $state = null) {
	$type = get_module_slottype($fit, $typeid);

	if(isset($fit['modules'][$type][$index])) {
		if($fit['modules'][$type][$index]['typeid'] == $typeid) {
			return; /* Job already done */
		}

		remove_module($fit, $index, $fit['modules'][$type][$index]['typeid']);
	}

	$fit['modules'][$type][$index] = array(
		'typeid' => $typeid,
		'typename' => $fit['cache'][$typeid]['typename'],
		'state' => null
		);

	$fit['dogma']['modules'][$type][$index] = array();
	foreach($fit['cache'][$typeid]['attributes'] as $attr) {
		$fit['dogma']['modules'][$type][$index][$attr['attributename']]
			= $attr['value'];
	}
	$fit['dogma']['modules'][$type][$index]['typeid']
		=& $fit['modules'][$type][$index]['typeid'];

	list($isactivable, ) = get_module_states($fit, $typeid);
	if($state === null) {
		$state = ($isactivable && in_array($type, get_stateful_slottypes())) ? STATE_ACTIVE : STATE_ONLINE;
	}

	change_module_state_by_location($fit, $type, $index, $state);
}

/**
 * Remove a certain module located at a specific index.
 */
function remove_module(&$fit, $index, $typeid) {
	$type = get_module_slottype($fit, $typeid);

	if(!isset($fit['modules'][$type][$index]['typeid'])) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_module(): trying to remove a nonexistent module!', E_USER_WARNING);
		maybe_remove_cache($fit, $typeid);
		return;
		// @codeCoverageIgnoreEnd
	}

	foreach($fit['charges'] as $name => $preset) {
		if(isset($preset[$type][$index])) {
			/* Remove charge */
			remove_charge($fit, $name, $type, $index);
		}
	}

	change_module_state_by_location($fit, $type, $index, null);
	unset($fit['dogma']['modules'][$type][$index]);
	unset($fit['modules'][$type][$index]);

	maybe_remove_cache($fit, $typeid);
}

/**
 * Change the state of a module located by its position. Be
 * careful, the new state is not checked, so if you pass a nonsensical
 * value (like STATE_ACTIVE for a passive-only module), weird things
 * may happen (but probably not!).
 *
 * @param $state one of the STATE_* constants.
 */
function change_module_state_by_location(&$fit, $type, $index, $state) {
	$categories = get_state_categories();

	$previous_state = get_module_state_by_location($fit, $type, $index);

	$added_groups = array_diff($categories[$state], $categories[$previous_state]);
	$removed_groups = array_diff($categories[$previous_state], $categories[$state]);

	\Osmium\Dogma\eval_module_postexpressions($fit, $type, $index, $removed_groups);
	\Osmium\Dogma\eval_module_preexpressions($fit, $type, $index, $added_groups);

	$fit['modules'][$type][$index]['state'] = $state;
}

/**
 * Change the state of a module located by its typeid. See
 * change_module_state_by_location() for the full caveat.
 */
function change_module_state_by_typeid(&$fit, $index, $typeid, $state) {
	return change_module_state_by_location($fit, get_module_slottype($fit, $typeid), $index, $state);
}

/**
 * Toggle the state of a module located at a specific index. The order
 * of toggling is as follows:
 *
 * offline -> online -> active -> overloaded -> offline
 *
 * If it is not possible to go to the next state (for example if a
 * module cannot be activated or overloaded), it will be toggled back
 * to the offline state.
 *
 * @param $next If set to false, toggle state in the opposite order.
 */
function toggle_module_state(&$fit, $index, $typeid, $next = true) {
	$state = get_module_state_by_typeid($fit, $index, $typeid);

	list($isactivable, $isoverloadable) = get_module_states($fit, $typeid);
	$new_state = $next ? get_next_state($state, $isactivable, $isoverloadable) :
		get_previous_state($state, $isactivable, $isoverloadable);

	change_module_state_by_typeid($fit, $index, $typeid, $new_state);
}

function get_next_state($state, $isactivable, $isoverloadable) {
	if($state === null) {
		// @codeCoverageIgnoreStart
		/* Should theoratically not happen, but handle it anyway */
		return STATE_OFFLINE;
		// @codeCoverageIgnoreEnd
	} else if($state === STATE_OFFLINE) {
		return STATE_ONLINE;
	} if($state === STATE_ONLINE) {
		return $isactivable ? STATE_ACTIVE : STATE_OFFLINE;
	} else if($state === STATE_ACTIVE) {
		return $isoverloadable ? STATE_OVERLOADED : STATE_OFFLINE;
	} else if($state === STATE_OVERLOADED) {
		return STATE_OFFLINE;
	}

	// @codeCoverageIgnoreStart
	/* This is serious. We're fucked up. */
	trigger_error('get_next_state(): unknown module state ('.$state.')', E_USER_ERROR);
	// @codeCoverageIgnoreEnd
}

function get_previous_state($state, $isactivable, $isoverloadable) {
	if($state === STATE_OVERLOADED) {
		return STATE_ACTIVE;
	} else if($state === STATE_ACTIVE) {
		return STATE_ONLINE;
	} if($state === STATE_ONLINE) {
		return STATE_OFFLINE;
	} else if($state === STATE_OFFLINE || $state === null) {
		if($isoverloadable) return STATE_OVERLOADED;
		if($isactivable) return STATE_ACTIVE;
		return STATE_ONLINE;
	}

	// @codeCoverageIgnoreStart
	trigger_error('get_previous_state(): unknown module state ('.$state.')', E_USER_ERROR);
	// @codeCoverageIgnoreEnd
}

/**
 * Get possible states of a module.
 *
 * Returns an array of two booleans, the first one is true iff the
 * module is activable, and the second one is true iff the module is
 * overloadable.
 */
function get_module_states(&$fit, $typeid) {
	get_attributes_and_effects(array($typeid), $fit['cache']);
	$type = get_module_slottype($fit, $typeid);

	$isactivable = false;
	$isoverloadable = false;

	foreach($fit['cache'][$typeid]['effects'] as $effect) {
		$name = $effect['effectname'];

		if($fit['cache']['__effects'][$name]['effectcategory'] == 1
		   || $fit['cache']['__effects'][$name]['effectcategory'] == 2
		   || $fit['cache']['__effects'][$name]['effectcategory'] == 3) {
			$isactivable = true;
			continue;
		}

		if($fit['cache']['__effects'][$name]['effectcategory'] == 5) {
			$isoverloadable = true;
			$isactivable = true;
			break;
		}
	}

	return array($isactivable, $isoverloadable);
}

/**
 * Add several charges at once. This is more efficient than calling
 * add_charge() multiple times.
 *
 * @param $charges an array of the form array(<slot_type> =>
 * array(<index> => <typeid>))
 */
function add_charges_batch(&$fit, $presetname, $charges) {
	$typeids = array();
	foreach($charges as $slot => $a) {
		foreach($a as $index => $typeid) {
			$typeids[$typeid] = true;
		}
	}

	get_attributes_and_effects(array_keys($typeids), $fit['cache']);

	foreach($charges as $slottype => $a) {
		foreach($a as $index => $typeid) {
			add_charge($fit, $presetname, $slottype, $index, $typeid);
		}
	}
}

/**
 * Add a charge to a given preset.
 */
function add_charge(&$fit, $presetname, $slottype, $index, $typeid) {
	if(!isset($fit['modules'][$slottype][$index])) {
		// @codeCoverageIgnoreStart
		trigger_error('add_charge(): cannot add charge to an empty module!', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	get_attributes_and_effects(array($typeid), $fit['cache']);

	if(isset($fit['charges'][$presetname][$slottype][$index])) {
		if($fit['charges'][$presetname][$slottype][$index]['typeid'] == $typeid) {
			return;
		}

		remove_charge($fit, $presetname, $slottype, $index);
	}

	$fit['charges'][$presetname][$slottype][$index] = array(
		'typeid' => $typeid,
		'typename' => $fit['cache'][$typeid]['typename'],
		);

	if($fit['selectedpreset'] === $presetname) {
		online_charge($fit, $presetname, $slottype, $index);
	}
}

/** @internal */
function online_charge(&$fit, $presetname, $slottype, $index) {
	$typeid = $fit['charges'][$presetname][$slottype][$index]['typeid'];
	$fit['dogma']['charges'][$presetname][$slottype][$index] = array();
	foreach($fit['cache'][$typeid]['attributes'] as $attr) {
		$fit['dogma']['charges'][$presetname][$slottype][$index][$attr['attributename']]
			= $attr['value'];
	}
	$fit['dogma']['charges'][$presetname][$slottype][$index]['typeid']
		=& $fit['charges'][$presetname][$slottype][$index]['typeid'];
	
	\Osmium\Dogma\eval_charge_preexpressions($fit, $presetname, $slottype, $index);
}

/**
 * Remove a charge from a given preset.
 */
function remove_charge(&$fit, $presetname, $slottype, $index) {
	if(!isset($fit['charges'][$presetname][$slottype][$index]['typeid'])) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_charge(): cannot remove a nonexistent charge.', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	$typeid = $fit['charges'][$presetname][$slottype][$index]['typeid'];

	if($fit['selectedpreset'] === $presetname) {
		offline_charge($fit, $presetname, $slottype, $index);
	}

	unset($fit['charges'][$presetname][$slottype][$index]);

	maybe_remove_cache($fit, $typeid);
}

/** @internal */
function offline_charge(&$fit, $presetname, $slottype, $index) {
		\Osmium\Dogma\eval_charge_postexpressions($fit, $presetname, $slottype, $index);
		unset($fit['dogma']['charges'][$presetname][$slottype][$index]);
}

/**
 * Completely remove a charge preset. If the preset being removed is
 * the currently selected preset, this function will switch to the
 * null preset.
 */
function remove_charge_preset(&$fit, $presetname) {
	if(!isset($fit['charges'][$presetname])) return;

	if($fit['selectedpreset'] === $presetname) {
		use_preset($fit, null); /* Don't use any preset at all */
	}

	foreach($fit['charges'][$presetname] as $type => $a) {
		foreach($a as $index => $charge) {
			remove_charge($fit, $presetname, $type, $index);
		}
	}

	unset($fit['charges'][$presetname]);
	unset($fit['dogma']['charges'][$presetname]);
}

/**
 * Switch to a given preset.
 *
 * @param $presetname preset name to switch to; if null, switch to the
 * null preset. The null preset is a special state with no charges.
 */
function use_preset(&$fit, $presetname) {
	if($presetname !== null && !isset($fit['charges'][$presetname])) {
		// @codeCoverageIgnoreStart
		trigger_error('use_preset(): no such preset', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if($fit['selectedpreset'] === $presetname) return;

	if($fit['selectedpreset'] !== null) {
		foreach($fit['charges'][$fit['selectedpreset']] as $type => $a) {
			foreach($a as $index => $charge) {
				offline_charge($fit, $fit['selectedpreset'], $type, $index);
			}
		}
	}

	$fit['selectedpreset'] = $presetname;

	if($presetname !== null) {
		foreach($fit['charges'][$fit['selectedpreset']] as $type => $a) {
			foreach($a as $index => $charge) {
				online_charge($fit, $presetname, $type, $index);
			}
		}
	}
}

/**
 * Add several different drones to a fitting. This is more efficient
 * than multiple calls to add_drone(). If drones with the same typeid
 * are already present on the fitting, this will add them to the
 * existing drones instead of replacing them.
 *
 * @param $drones array of structure array(<typeid> =>
 * array(quantityinbay, quantityinspace))
 */
function add_drones_batch(&$fit, $drones) {
	get_attributes_and_effects(array_keys($drones), $fit['cache']);

	foreach($drones as $typeid => $quantities) {
		$quantityinbay = isset($quantities['quantityinbay']) ? $quantities['quantityinbay'] : 0;
		$quantityinspace = isset($quantities['quantityinspace']) ? $quantities['quantityinspace'] : 0;

		if($quantityinbay > 0 || $quantityinspace > 0) {
			add_drone($fit, $typeid, $quantityinbay, $quantityinspace);
		}
	}
}

/**
 * Add a drone type to a fitting. If drones of the same typeid already
 * are in the fitting, increase the quantities with the supplied
 * values instead.
 */
function add_drone(&$fit, $typeid, $quantityinbay = 1, $quantityinspace = 0) {
	if($quantityinbay == 0 && $quantityinspace == 0) return;

	get_attributes_and_effects(array($typeid), $fit['cache']);

	if(!isset($fit['drones'][$typeid])) {
		$fit['drones'][$typeid] = array(
			'typeid' => $typeid,
			'typename' => $fit['cache'][$typeid]['typename'],
			'volume' => $fit['cache'][$typeid]['volume'],
			'bandwidth' => $fit['cache'][$typeid]['attributes']['droneBandwidthUsed']['value'], /* FIXME it may have modifiers */
			'quantityinbay' => 0,
			'quantityinspace' => 0,
			);

		$fit['dogma']['drones'][$typeid] = array();
		foreach($fit['cache'][$typeid]['attributes'] as $attr) {
			$fit['dogma']['drones'][$typeid][$attr['attributename']] = $attr['value'];
		}
		$fit['dogma']['drones'][$typeid]['typeid'] =& $fit['drones'][$typeid]['typeid'];
	}

	$fit['drones'][$typeid]['quantityinbay'] += $quantityinbay;
	$fit['drones'][$typeid]['quantityinspace'] += $quantityinspace;
}

/**
 * Remove drones from a fitting.
 *
 * @param $typeid the typeid of the drones to remove
 * @param $from either "space" or "bay", where to remove the drones from
 * @param $quantity number of drones to remove; defaults to 1
 */
function remove_drone(&$fit, $typeid, $from, $quantity = 1) {
	if($from !== 'bay' && $from !== 'space') {
		// @codeCoverageIgnoreStart
		trigger_error('remove_drone(): unknown origin '.$from, E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(!isset($fit['drones'][$typeid]) || $fit['drones'][$typeid]['quantityin'.$from] < $quantity) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_drone(): not enough drones to remove', E_USER_WARNING);
		$fit['drones'][$typeid]['quantityin'.$from] = 0;
		// @codeCoverageIgnoreEnd
	} else {
		$fit['drones'][$typeid]['quantityin'.$from] -= $quantity;
	}

	if($fit['drones'][$typeid]['quantityinbay'] == 0
		&& $fit['drones'][$typeid]['quantityinspace'] == 0) {
		unset($fit['drones'][$typeid]);
		unset($fit['dogma']['drones'][$typeid]);
		maybe_remove_cache($fit, $typeid);
	}
}

/**
 * Toggle state of drones.
 *
 * @param $typeid typeid of the drone to transfer
 *
 * @param $from either "space" or "bay"
 *
 * @param $quantity the amount of drone to move from $from (to the
 * other state). If negative, the transfer occurs in the opposite
 * direction (drones are moved to $from).
 */
function transfer_drone(&$fit, $typeid, $from, $quantity = 1) {
	if($from !== 'bay' && $from !== 'space') {
		// @codeCoverageIgnoreStart
		trigger_error('transfer_drone(): unknown origin '.$from, E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	$to = ($from === 'bay') ? 'space' : 'bay';
	if($quantity < 0) {
		/* "Reverse" transfer: swap $from and $to */
		$quantity = -$quantity;

		$old_to = $to;
		$to = $from;
		$from = $old_to;
	}
	
	if(!isset($fit['drones'][$typeid]) || $fit['drones'][$typeid]['quantityin'.$from] < $quantity) {
		// @codeCoverageIgnoreStart
		trigger_error('transfer_drone(): not enough drones to move', E_USER_ERROR);
		return;
		// @codeCoverageIgnoreEnd
	}

	$fit['drones'][$typeid]['quantityin'.$from] -= $quantity;
	$fit['drones'][$typeid]['quantityin'.$to] += $quantity;
}

/**
 * Balance drones in space and in bay according to their total number,
 * and the number of drones in either state.
 *
 * @param $typeid typeid of the drone to balance
 * 
 * @param $location either "space" or "bay"
 *
 * @param $knownquantity the number of drones that must be in
 * $location after this call
 */
function dispatch_drones(&$fit, $typeid, $location, $knownquantity) {
	if($location !== 'bay' && $location !== 'space') {
		// @codeCoverageIgnoreStart
		trigger_error('dispatch_drones(): unknown location '.$location, E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(!isset($fit['drones'][$typeid]) 
	   || $fit['drones'][$typeid]['quantityinspace'] 
	   + $fit['drones'][$typeid]['quantityinbay'] < $knownquantity) {
		// @codeCoverageIgnoreStart
		trigger_error('dispatch_drones(): not enough drones to dispatch', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	$currentquantity = $fit['drones'][$typeid]['quantityin'.$location];
	if($currentquantity == $knownquantity) {
		/* Just perfect! */
		return;
	} else {
		/* Let transfer_drone figure out the direction of the transfer */
		transfer_drone($fit, $typeid, $location, $currentquantity - $knownquantity);
	}
}


/**
 * Get the state of a module located by its slot type and
 * index. Returns one of the STATE_* constants.
 */
function get_module_state_by_location($fit, $type, $index) {
	return $fit['modules'][$type][$index]['state'];
}

/**
 * Get the state of a module located by its typeid and index. Returns
 * one of the STATE_* constants.
 */
function get_module_state_by_typeid(&$fit, $index, $typeid) {
	return get_module_state_by_location($fit, get_module_slottype($fit, $typeid), $index);
}

/**
 * Get the type of slot a module occupies.
 */
function get_module_slottype(&$fit, $typeid) {
	get_attributes_and_effects(array($typeid), $fit['cache']);
	$effects = $fit['cache'][$typeid]['effects'];

	if(isset($effects['loPower'])) return 'low';
	if(isset($effects['medPower'])) return 'medium';
	if(isset($effects['hiPower'])) return 'high';
	if(isset($effects['rigSlot'])) return 'rig';
	if(isset($effects['subSystem'])) return 'subsystem';
	return false;
}

/* ----------------------------------------------------- */

/**
 * Try to remove any leftover cached attributes/effects no longer used
 * by any fitted entity.
 *
 * Usually not needed, as some housekeeping is already done internally
 * when removing entities (such as modules, drones, etc.).
 */
function prune_cache(&$fit) {
	/* TODO: also prune __effects and __attributes (hard). Maybe use a
	 * counter for every effect/attribute represting the number of
	 * entities using it? */

	foreach($fit['cache'] as $typeid => $bla) {
		maybe_remove_cache($fit, $typeid);
	}
}

/** @internal */
function maybe_remove_cache(&$fit, $deleted_typeid) {
	if(!isset($fit['cache'][$deleted_typeid])) return;
	if(isset($fit['ship']['typeid']) && $fit['ship']['typeid'] == $deleted_typeid) return;
	foreach($fit['modules'] as $submods) {
		foreach($submods as $mod) {
			if($mod['typeid'] == $deleted_typeid) return;
		}
	}
	foreach($fit['charges'] as $preset) {
		foreach($preset as $subcharges) {
			foreach($subcharges as $charge) {
				if($charge['typeid'] == $deleted_typeid) return;
			}
		}
	}
	foreach($fit['drones'] as $drone) {
		if($drone['typeid'] == $deleted_typeid) return;
	}

	unset($fit['cache'][$deleted_typeid]);
}

/** @internal */
function get_attribute_in_cache($attributenameorid, &$out) {
	if(isset($out['__attributes'][$attributenameorid])) return;

	$column = is_numeric($attributenameorid) ? 'attributeid' : 'attributename';

	$attribsq = \Osmium\Db\query_params('SELECT attributename, attributeid, highisgood, stackable, defaultvalue
  FROM eve.dgmattribs 
  WHERE dgmattribs.'.$column.' = $1', array($attributenameorid));

	$row = \Osmium\Db\fetch_assoc($attribsq);
		
	$out['__attributes'][$row['attributename']] = $row;
	$out['__attributes'][$row['attributeid']] =& $out['__attributes'][$row['attributename']];
}

/** @internal */
function get_attributes_and_effects($typeids, &$out) {
	static $hardcoded_effectcategories = array(
		'online' => 4,
		);

	foreach($typeids as $i => $tid) {
		if(isset($out[$tid])) {
			unset($typeids[$i]);
			continue;
		}

		$out[$tid]['effects'] = array();
		$out[$tid]['attributes'] = array();
	}

	if(count($typeids) == 0) return; /* Everything is already cached, yay! */

	$typeidIN = implode(',', $typeids);
  
	$metaq = \Osmium\Db\query_params('SELECT typeid, typename, groupid, volume
  FROM eve.invtypes WHERE typeid IN ('.$typeidIN.')', array());
	while($row = \Osmium\Db\fetch_row($metaq)) {
		$out[$row[0]]['typename'] = $row[1];
		$out[$row[0]]['groupid'] = $row[2];
		$out[$row[0]]['volume'] = $row[3];
	}

	/* Effect categories (maybe):
	   0 -> passive
	   1 -> activation
	   2 -> target
	   3 -> area
	   4 -> online
	   5 -> overload
	   6 -> dungeon
	   7 -> system
	   http://pastie.org/pastes/2768807/text */
	$effectsq = \Osmium\Db\query('SELECT typeid, effectname, dgmeffects.effectid, preexpr.exp AS preexp, postexpr.exp AS postexp, effectcategory,
  durationattributeid, trackingspeedattributeid, dischargeattributeid, rangeattributeid, falloffattributeid
  FROM eve.dgmeffects 
  JOIN eve.dgmtypeeffects ON dgmeffects.effectid = dgmtypeeffects.effectid 
  LEFT JOIN eve.dgmcacheexpressions AS preexpr ON preexpr.expressionid = preexpression
  LEFT JOIN eve.dgmcacheexpressions AS postexpr ON postexpr.expressionid = postexpression
  WHERE typeid IN ('.$typeidIN.')');
	while($row = \Osmium\Db\fetch_assoc($effectsq)) {
		$tid = $row['typeid'];

		if(isset($hardcoded_effectcategories[$row['effectname']])) {
			$row['effectcategory'] = $hardcoded_effectcategories[$row['effectname']];
		}

		$out['__effects'][$row['effectname']]['durationattributeid'] = $row['durationattributeid'];
		$out['__effects'][$row['effectname']]['trackingspeedattributeid'] = $row['trackingspeedattributeid'];
		$out['__effects'][$row['effectname']]['dischargeattributeid'] = $row['dischargeattributeid'];
		$out['__effects'][$row['effectname']]['rangeattributeid'] = $row['rangeattributeid'];
		$out['__effects'][$row['effectname']]['falloffattributeid'] = $row['falloffattributeid'];
		$out['__effects'][$row['effectname']]['effectcategory'] = $row['effectcategory'];
		$out['__effects'][$row['effectid']] =& $out['__effects'][$row['effectname']];

		unset($row['typeid']);
		unset($row['durationattributeid']);
		unset($row['trackingspeedattributeid']);
		unset($row['dischargeattributeid']);
		unset($row['rangeattributeid']);
		unset($row['falloffattributeid']);
		unset($row['effectcategory']);

		$out[$tid]['effects'][$row['effectname']] = $row;
	}

	$attribsq = \Osmium\Db\query('SELECT dgmtypeattribs.typeid, attributename, dgmattribs.attributeid, highisgood, stackable, value, defaultvalue
  FROM eve.dgmattribs 
  JOIN eve.dgmtypeattribs ON dgmattribs.attributeid = dgmtypeattribs.attributeid
  WHERE dgmtypeattribs.typeid IN ('.$typeidIN.')');
	while($row = \Osmium\Db\fetch_assoc($attribsq)) {
		$tid = $row['typeid'];

		$out['__attributes'][$row['attributename']]['attributename'] = $row['attributename'];
		$out['__attributes'][$row['attributename']]['stackable'] = $row['stackable'];
		$out['__attributes'][$row['attributename']]['highisgood'] = $row['highisgood'];
		$out['__attributes'][$row['attributename']]['defaultvalue'] = $row['defaultvalue'];
		$out['__attributes'][$row['attributeid']] =& $out['__attributes'][$row['attributename']];

		unset($row['typeid']);
		unset($row['stackable']);
		unset($row['highisgood']);
		unset($row['defaultvalue']);

		$out[$tid]['attributes'][$row['attributename']] = $row;
	}
}

/**
 * Try to auto-magically fix issues of a loadout.
 *
 * Potentially destructive operation! Use with caution.
 */
function sanitize(&$fit) {
	/* Unset any extra charges of nonexistent modules. */
	foreach($fit['charges'] as $name => $preset) {
		foreach($preset as $type => $a) {
			foreach($a as $index => $charge) {
				if(!isset($fit['modules'][$type][$index])) {
					unset($fit['charges'][$name][$type][$index]);
				}
			}
		}
	}
}
