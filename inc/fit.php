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

/*
 * KEEP THIS NAMESPACE PURE.
 * 
 * Functions in \Osmium\Fit must not affect or rely on the global
 * state at all. This makes it easier to test and debug.
 */

require __DIR__.'/fit-names.php';
require __DIR__.'/fit-presets.php';
require __DIR__.'/fit-attributes.php';
require __DIR__.'/fit-tags.php';
require __DIR__.'/fit-db.php';
require __DIR__.'/fit-db-versions.php';
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

/** Get the names of the module slot categories. */
function get_slottypes_names() {
	return array(
		'high' => 'High slots',
		'medium' => 'Medium slots',
		'low' => 'Low slots',
		'rig' => 'Rig slots',
		'subsystem' => 'Subsystems',
		);
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
 *
 * @returns [ STATE_* => [PrettyName, Icon, CLFName], … ]
 */
function get_state_names() {
	return array(
		STATE_OFFLINE => array('Offline', 'offline.png', 'offline'),
		STATE_ONLINE => array('Online', 'online.png', 'online'),
		STATE_ACTIVE => array('Active', 'active.png', 'active'),
		STATE_OVERLOADED => array('Overloaded', 'overloaded.png', 'overloaded'),
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
 * modulepresetid => (integer) 
 * modulepresetname => (string)
 * modulepresetdesc => (string)
 * modules => array(<slot_type> => array(<index> => array(typeid, typename, dogma_index, state)))
 *
 * chargepresetid => (integer) 
 * chargepresetname => (string)
 * chargepresetdesc => (string)
 * charges => array(<slot_type> => array(<index> => array(typeid, typename)))
 *
 * dronepresetid => (integer)
 * dronepresetname => (string)
 * dronepresetdesc => (string)
 * drones => array(<typeid> => array(typeid, typename, volume, bandwidth, quantityin{bay, space})))
 *
 * presets => array(<presetid> => array(name, description, modules, chargepresets))
 *
 * chargepresets => array(<presetid> => array(name, description, charges))
 *
 * dronepresets => array(<presetid> => array(name, description, drones))
 *
 * metadata => array(name, description, tags, evebuildnumber,
 *					 view_permission, edit_permission, visibility,
 *					 password, loadoutid, hash, revision,
 *					 privatetoken, skillset)
 *
 * __dogma_context => (Dogma context resource)
 */
function create(&$fit) {
	$fit = array(
		'ship' => array(),
		'dogma' => array(),
		'presets' => array(),
		'dronepresets' => array(),
		'metadata' => array(
			'name' => 'Unnamed loadout',
			'description' => '',
			'tags' => array(),
			'evebuildnumber' => get_latest_eve_db_version()['build'],
			'view_permission' => VIEW_EVERYONE,
			'edit_permission' => EDIT_OWNER_ONLY,
			'visibility' => VISIBILITY_PUBLIC,
			'skillset' => 'All V',
			)
		);

	$presetid = create_preset($fit, 'Default preset', '');
	use_preset($fit, $presetid);

	$dpid = create_drone_preset($fit, 'Default drone preset', '');
	use_drone_preset($fit, $dpid);

	dogma_init_context($fit['__dogma_context']);
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
	$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT invships.typename FROM osmium.invships
		JOIN eve.invtypes ON invtypes.typeid = invships.typeid
		WHERE invships.typeid = $1', 
		array($new_typeid)
	));
	if($row === false) return false;

	$fit['ship'] = array(
		'typeid' => $new_typeid,
		'typename' => $row[0],
	);

	dogma_set_ship($fit['__dogma_context'], $new_typeid);

	return true;
}

/**
 * Add several modules to the fit.
 *
 * @param $modules array of modules to add, should be of the form
 * array(slot_type => array(index => typeid)), or array(slot_type =>
 * array(index => array(typeid, modulestate))).
 */
function add_modules_batch(&$fit, $modules) {
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
            if($state !== null) {
                /* Module is already installed, but state still needs an update */
	            change_module_state_by_typeid($fit, $index, $typeid, $state);
            }

			return;
		}

		remove_module($fit, $index, $fit['modules'][$type][$index]['typeid']);
	}

	list($isactivable, ) = get_module_states($fit, $typeid);
	if($state === null) {
		$state = ($isactivable && in_array($type, get_stateful_slottypes())) ? STATE_ACTIVE : STATE_ONLINE;
	}

	$fit['modules'][$type][$index] = array(
		'typeid' => $typeid,
		'typename' => get_typename($typeid),
		'state' => $state
	);

	dogma_add_module_s(
		$fit['__dogma_context'],
		$typeid,
		$fit['modules'][$type][$index]['dogma_index'],
		\Osmium\Dogma\get_dogma_states()[$state]
	);
}

/**
 * Remove a certain module located at a specific index from the
 * current preset. This will also remove charges of this module in all
 * charge presets.
 */
function remove_module(&$fit, $index, $typeid) {
	$type = get_module_slottype($fit, $typeid);

	if(!isset($fit['modules'][$type][$index]['typeid'])) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_module(): trying to remove a nonexistent module!', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['charges'][$type][$index])) {
		/* Remove charge */
		remove_charge($fit, $type, $index);
	}

	foreach($fit['chargepresets'] as $cpid => &$chargepreset) {
		if(isset($chargepreset['charges'][$type][$index])) {
			/* Remove charge in other presets */
			unset($chargepreset['charges'][$type][$index]);
		}
	}

	dogma_remove_module($fit['__dogma_context'], $fit['modules'][$type][$index]['dogma_index']);
	unset($fit['modules'][$type][$index]);
}

/**
 * Sort fitted modules with a given order. Modules of each type will
 * be sorted by ascending order_position.
 *
 * FIXME: this will NOT sort accociated charges.
 *
 * @param $order array of type array(<slot_type> => array(<index> =>
 * <order_position>)).
 */
function sort_modules(&$fit, $order) {
	foreach($fit['modules'] as $type => &$modules) {
		uksort($modules, function($a, $b) use($type, $order) {
				if(!isset($order[$type][$a])) return -1;
				if(!isset($order[$type][$b])) return 1;

				return $order[$type][$a] - $order[$type][$b];
			});
	}
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
	$m =& $fit['modules'][$type][$index];
	if($m['state'] === $state) return;

	dogma_set_module_state(
		$fit['__dogma_context'],
	    $m['dogma_index'],
		\Osmium\Dogma\get_dogma_states()[$state]
	);
	$m['state'] = $state;
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
	dogma_type_has_overload_effects($typeid, $overloadable);
	if($overloadable) return array(true, true);

	dogma_type_has_active_effects($typeid, $activable);
	return array($activable, false);
}

/**
 * Add several charges at once to the current charge preset.
 *
 * @param $charges an array of the form array(<slot_type> =>
 * array(<index> => <typeid>))
 */
function add_charges_batch(&$fit, $charges) {
	foreach($charges as $slottype => $a) {
		foreach($a as $index => $typeid) {
			add_charge($fit, $slottype, $index, $typeid);
		}
	}
}

/**
 * Add a charge to the currently selected charge preset.
 */
function add_charge(&$fit, $slottype, $index, $typeid) {
	if(!isset($fit['modules'][$slottype][$index])) {
		// @codeCoverageIgnoreStart
		trigger_error('add_charge(): cannot add charge to an empty module!', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['charges'][$slottype][$index])) {
		if($fit['charges'][$slottype][$index]['typeid'] == $typeid) {
			return;
		}

		remove_charge($fit, $slottype, $index);
	}

	$fit['charges'][$slottype][$index] = array(
		'typeid' => $typeid,
		'typename' => get_typename($typeid)
	);

	dogma_add_charge(
		$fit['__dogma_context'],
		$fit['modules'][$slottype][$index]['dogma_index'],
		$typeid
	);
}

/**
 * Remove a charge from the currently selected charge preset.
 */
function remove_charge(&$fit, $slottype, $index) {
	if(!isset($fit['charges'][$slottype][$index]['typeid'])) {
		// @codeCoverageIgnoreStart
		trigger_error('remove_charge(): cannot remove a nonexistent charge.', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	dogma_remove_charge(
		$fit['__dogma_context'],
		$fit['modules'][$slottype][$index]['dogma_index']
	);
	unset($fit['charges'][$slottype][$index]);
}

/**
 * Add several different drones to a fitting. If drones with the same
 * typeid are already present on the fitting, this will add them to
 * the existing drones instead of replacing them.
 *
 * @param $drones array of structure array(<typeid> =>
 * array(quantityinbay, quantityinspace))
 */
function add_drones_batch(&$fit, $drones) {
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

	if(!isset($fit['drones'][$typeid])) {
		$fit['drones'][$typeid] = array(
			'typeid' => $typeid,
			'typename' => get_typename($typeid),
			'volume' => get_volume($typeid),
			'quantityinbay' => 0,
			'quantityinspace' => 0,
		);
	}

	$fit['drones'][$typeid]['quantityinbay'] += $quantityinbay;
	$fit['drones'][$typeid]['quantityinspace'] += $quantityinspace;

	dogma_add_drone(
		$fit['__dogma_context'],
		$typeid,
		$quantityinspace
	);
}

/**
 * Add a drone to a fitting.
 *
 * The drones will be added in priority in space as long as it is
 * possible (in respect to bandwidth and maximum number of drones in
 * space); if not then the drone is added in the bay.
 */
function add_drone_auto(&$fit, $typeid, $quantity) {
	if($quantity == 0) return;

	$available = 
		\Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth')
		- get_used_drone_bandwidth($fit);

	/* Add drone to bay first */
	add_drone($fit, $typeid, $quantity, 0);

	$usedbw = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'droneBandwidthUsed');

	/* How many drones can fit in the remaining bandwidth? */
	$totransfer = min(
		$usedbw > 0 ? (int)floor($available / $usedbw) : $quantity,
		$quantity
		);

	/* How many more drones can be in space? */
	$remainingslots = \Osmium\Dogma\get_char_attribute($fit, 'maxActiveDrones');
	foreach($fit['drones'] as $d) {
		$remainingslots -= $d['quantityinspace'];
	}
	$totransfer = min($totransfer, max($remainingslots, 0));

	transfer_drone($fit, $typeid, 'bay', $totransfer);
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
		if($from === 'space') {
			dogma_remove_drone($fit['__dogma_context'], $typeid);
		}
		// @codeCoverageIgnoreEnd
	} else {
		$fit['drones'][$typeid]['quantityin'.$from] -= $quantity;
		if($from === 'space') {
			dogma_remove_drone_partial($fit['__dogma_context'], $typeid, $quantity);
		}
	}

	if($fit['drones'][$typeid]['quantityinbay'] == 0
		&& $fit['drones'][$typeid]['quantityinspace'] == 0) {
		unset($fit['drones'][$typeid]);
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

	if($from === 'space') {
		dogma_remove_drone_partial($fit['__dogma_context'], $typeid, $quantity);
	} else {
		dogma_add_drone($fit['__dogma_context'], $typeid, $quantity);
	}

	$fit['drones'][$typeid]['quantityin'.$from] -= $quantity;
	$fit['drones'][$typeid]['quantityin'.$to] += $quantity;
}

/* ----------------------------------------------------- */

/**
 * Get all the fitted modules of the current preset.
 *
 * The returned array is guaranteed to have this structure:
 * array(<slot_type> => array(<index> => array(typeid, typename,
 * state))).
 */
function get_modules($fit) {
	return $fit['modules'];
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
 *
 * __deprecated__
 */
function get_module_slottype(&$fit, $typeid) {
	dogma_type_has_effect($typeid, DOGMA_STATE_Offline, EFFECT_LoPower, $t);
	if($t) return 'low';

	dogma_type_has_effect($typeid, DOGMA_STATE_Offline, EFFECT_MedPower, $t);
	if($t) return 'medium';

	dogma_type_has_effect($typeid, DOGMA_STATE_Offline, EFFECT_HiPower, $t);
	if($t) return 'high';

	dogma_type_has_effect($typeid, DOGMA_STATE_Offline, EFFECT_RigSlot, $t);
	if($t) return 'rig';

	dogma_type_has_effect($typeid, DOGMA_STATE_Offline, EFFECT_SubSystem, $t);
	if($t) return 'subsystem';

	return false;
}

/* ----------------------------------------------------- */

/**
 * Try to auto-magically fix issues of a loadout.
 *
 * Potentially destructive operation! Use with caution.
 *
 * @param $errors an array of errors which were corrected
 * 
 * @param $interactive If true, some destructive operations will not
 * be performed, instead an error will be added.
 */
function sanitize(&$fit, &$errors = null, $interactive = false) {
	/* Unset any extra charges of nonexistent modules. */
	foreach($fit['presets'] as &$p) {
		foreach($p['chargepresets'] as &$cp) {
			foreach($cp['charges'] as $type => &$a) {
				foreach($a as $index => &$charge) {
					if(!isset($p['modules'][$type][$index])) {
						$errors[] = 'Removed charge '.$charge['typeid'].' from '.$type.' module '.$index;
						unset($a[$index]);
					}
				}
			}
		}
	}

	/* Enforce tag consistency */
	sanitize_tags($fit, $errors, $interactive);

	/* Enforce permissions consistency */
	if(!in_array($fit['metadata']['view_permission'], array(
		             VIEW_EVERYONE,
		             VIEW_PASSWORD_PROTECTED,
		             VIEW_ALLIANCE_ONLY,
		             VIEW_CORPORATION_ONLY,
		             VIEW_OWNER_ONLY,
		             ))) {
		$errors[] = 'Incorrect view permission, reset to viewable by everyone.';
		$fit['metadata']['view_permission'] = VIEW_EVERYONE;
	}
	if(!in_array($fit['metadata']['edit_permission'], array(
		             EDIT_OWNER_ONLY,
		             EDIT_OWNER_AND_FITTING_MANAGER_ONLY,
		             EDIT_CORPORATION_ONLY,
		             EDIT_ALLIANCE_ONLY,
		             ))) {
		$errors[] = 'Incorrect edit permission, reset to editable by owner only.';
		$fit['metadata']['edit_permission'] = EDIT_OWNER_ONLY;
	}
	if(!in_array($fit['metadata']['visibility'], array(
		             VISIBILITY_PUBLIC,
		             VISIBILITY_PRIVATE,
		             ))) {
		$errors[] = 'Incorrect visibility, reset to public visibility.';
		$fit['metadata']['visibility'] = VISIBILITY_PUBLIC;
	}
	if($fit['metadata']['view_permission'] == VIEW_PASSWORD_PROTECTED) {
		$fit['metadata']['visibility'] = VISIBILITY_PRIVATE;

		if(!isset($fit['metadata']['password']) || !$fit['metadata']['password']) {
			$errors[] = 'Loadout is password-protected but does not have a password, view permission reset to viewable by everyone.';
			$fit['metadata']['view_permission'] = VIEW_EVERYONE;
		}
	}

	/* Sanitize title */
	$origtitle = $fit['metadata']['name'];
	$title =& $fit['metadata']['name'];
	$title = preg_replace('%\p{C}%u', '', $title); /* Remove control chars and other unused codes */
	$title = \Osmium\Chrome\trim($title);
	if($title !== $origtitle) {
		$errors[] = 'Removed blanks and control characters from title.';
	}
}

/**
 * Generate a delta (diff) between two fittings.
 *
 * @returns a string, containing HTML code (safe to print as-is,
 * delta() already does some escaping), or null if a diff could not be
 * generated
 */
function delta($old, $new) {
	@include_once 'Horde/Autoloader/Default.php';
	if(!class_exists('Horde_Text_Diff')) return null;

	$oldt = \Osmium\Fit\export_to_markdown($old, false);
	$newt = \Osmium\Fit\export_to_markdown($new, false);

	$diff = new \Horde_Text_Diff('auto', array(explode("\n", $oldt), 
	                                           explode("\n", $newt)));
	$renderer = new \Horde_Text_Diff_Renderer_Inline();
	return $renderer->render($diff);
}

/**
 * Use a custom skillset for this fitting (default is all skills to
 * V).
 *
 * @param $skillset array(skilltypeid => skilllevel)
 * @param $defaultlevel level to use for skills not in $skillset
 */
function use_skillset(&$fit, array $skillset = array(), $defaultlevel = 5) {
	dogma_reset_skill_levels($fit['__dogma_context']);
	dogma_set_default_skill_level($fit['__dogma_context'], (int)$defaultlevel);

	foreach($skillset as $typeid => $level) {
		dogma_set_skill_level($fit['__dogma_context'], (int)$typeid, (int)$level);
	}
}

/**
 * Get the URI of this loadout (relative to the main page).
 *
 * @warning The rest of the code assumes this function returns a "base
 * path", that is, no GET parameters (so it is possible to append
 * "?foo=bar" after the returned value of get_fit_uri() in all cases).
 */
function get_fit_uri($loadoutid, $visibility, $privatetoken, $revision = null) {
	if($revision !== null) {
		$loadoutid = $loadoutid.'R'.$revision;
	}

	if($visibility == VISIBILITY_PRIVATE) {
		return 'loadout/private/'.$loadoutid.'/'.$privatetoken;
	}

	return 'loadout/'.$loadoutid;
}

/**
 * Get the path needed to go back the root from this loadout's URI.
 */
function get_fit_relative($loadoutid, $visibility) {
	if($visibility == VISIBILITY_PRIVATE) {
		/* Loadout URI looks like /loadout/private/id/tok */
		return '../../..';
	} else {
		/* Loadout URI looks like /loadout/id */
		return '..';
	}
}
