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
 * modulepresetid => (integer) 
 * modulepresetname => (string)
 * modulepresetdesc => (string)
 * modules => array(<slot_type> => array(<index> => array(typeid, typename, state)))
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
 * dogma => array({char,
 *                 ship,
 *                 modules => <slot_type> => <index>,
 *                 charges => <slot_type> => <index>,
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
 *
 * metadata => array(name, description, tags, evebuildnumber,
 *					 view_permission, edit_permission, visibility,
 *					 password, loadoutid, hash, revision,
 *					 privatetoken, skillset)
 */
function create(&$fit) {
	$fit = array(
		'ship' => array(),
		'dogma' => array(),
		'cache' => array(),
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
			'SELECT invships.typename FROM osmium.invships JOIN eve.invtypes ON invtypes.typeid = invships.typeid WHERE invships.typeid = $1', 
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
	$fit['dogma']['ship']['typeid'] =& $fit['ship']['typeid'];

	/* Mass is in invtypes, not dgmtypeattribs, so it has to be hardcoded here */
	$fit['cache']['__attributes']['mass'] = array(
		'attributename' => 'mass',
		'stackable' => 0,
		'highisgood' => 1,
		);
	
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
            if($state !== null) {
                /* Module is already installed, but state still needs an update */
                change_module_state_by_location($fit, $type, $index, $state);
            }

			return;
		}

		remove_module($fit, $index, $fit['modules'][$type][$index]['typeid']);
	}

	$fit['modules'][$type][$index] = array(
		'typeid' => $typeid,
		'typename' => $fit['cache'][$typeid]['typename'],
		'state' => null
		);

	list($isactivable, ) = get_module_states($fit, $typeid);
	if($state === null) {
		$state = ($isactivable && in_array($type, get_stateful_slottypes())) ? STATE_ACTIVE : STATE_ONLINE;
	}
	$fit['modules'][$type][$index]['old_state'] = $state;

	online_module($fit, $type, $index);
}

/** @internal */
function online_module(&$fit, $slottype, $index, $onlinecharge = true) {
	if(isset($fit['dogma']['modules'][$slottype][$index])) {
		// @codeCoverageIgnoreStart
		trigger_error('online_module(): module already appears to be online', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	$typeid = $fit['modules'][$slottype][$index]['typeid'];
	$fit['dogma']['modules'][$slottype][$index] = array();
	foreach($fit['cache'][$typeid]['attributes'] as $attr) {
		$fit['dogma']['modules'][$slottype][$index][$attr['attributename']] = $attr['value'];
	}
	$fit['dogma']['modules'][$slottype][$index]['typeid'] =& $fit['modules'][$slottype][$index]['typeid'];

	$state = $fit['modules'][$slottype][$index]['old_state'];
	change_module_state_by_location($fit, $slottype, $index, $state);
	unset($fit['modules'][$slottype][$index]['old_state']);

	/* This may seem logical, but no. online_module() will be called
	 * by add_module() (in which case there is nocharge anyway), or by
	 * use_preset(), which will call use_charge_preset() immediately
	 * after. Onlining the charge here would cause the charge to be
	 * onlined twice while switching between (module) presets. */
	//if(isset($fit['charges'][$slottype][$index])) {
	//	online_charge($fit, $slottype, $index);
	//}

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
		maybe_remove_cache($fit, $typeid);
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

	offline_module($fit, $type, $index);
	unset($fit['modules'][$type][$index]);

	maybe_remove_cache($fit, $typeid);
}

/** @internal */
function offline_module(&$fit, $slottype, $index) {
	if(!isset($fit['dogma']['modules'][$slottype][$index])) {
		// @codeCoverageIgnoreStart
		trigger_error('offline_module(): module already appears to be offline', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	if(isset($fit['charges'][$slottype][$index])) {
		offline_charge($fit, $slottype, $index);
	}

	$state = get_module_state_by_location($fit, $slottype, $index);
	change_module_state_by_location($fit, $slottype, $index, null);
	$fit['modules'][$slottype][$index]['old_state'] = $state;

	unset($fit['dogma']['modules'][$slottype][$index]);
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
	$previous_state = get_module_state_by_location($fit, $type, $index);
    if($previous_state === $state) {
        /* Be lazy */
        return;
    }

	$categories = get_state_categories();

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
 * Add several charges at once to the current charge preset. This is
 * more efficient than calling add_charge() multiple times.
 *
 * @param $charges an array of the form array(<slot_type> =>
 * array(<index> => <typeid>))
 */
function add_charges_batch(&$fit, $charges) {
	$typeids = array();
	foreach($charges as $slot => $a) {
		foreach($a as $index => $typeid) {
			$typeids[$typeid] = true;
		}
	}

	get_attributes_and_effects(array_keys($typeids), $fit['cache']);

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

	get_attributes_and_effects(array($typeid), $fit['cache']);

	if(isset($fit['charges'][$slottype][$index])) {
		if($fit['charges'][$slottype][$index]['typeid'] == $typeid) {
			return;
		}

		remove_charge($fit, $slottype, $index);
	}

	$fit['charges'][$slottype][$index] = array(
		'typeid' => $typeid,
		'typename' => $fit['cache'][$typeid]['typename'],
		);

	online_charge($fit, $slottype, $index);
}

/** @internal */
function online_charge(&$fit, $slottype, $index) {
	if(isset($fit['dogma']['charges'][$slottype][$index])) {
		// @codeCoverageIgnoreStart
		trigger_error('online_charge(): charge already appears to be online', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	$typeid = $fit['charges'][$slottype][$index]['typeid'];
	$fit['dogma']['charges'][$slottype][$index] = array();
	foreach($fit['cache'][$typeid]['attributes'] as $attr) {
		$fit['dogma']['charges'][$slottype][$index][$attr['attributename']]
			= $attr['value'];
	}
	$fit['dogma']['charges'][$slottype][$index]['typeid']
		=& $fit['charges'][$slottype][$index]['typeid'];
	
	\Osmium\Dogma\eval_charge_preexpressions($fit, $slottype, $index);
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

	offline_charge($fit, $slottype, $index);

	$typeid = $fit['charges'][$slottype][$index]['typeid'];
	unset($fit['charges'][$slottype][$index]);
	maybe_remove_cache($fit, $typeid);
}

/** @internal */
function offline_charge(&$fit, $slottype, $index) {
	if(!isset($fit['dogma']['charges'][$slottype][$index])) {
		// @codeCoverageIgnoreStart
		trigger_error('offline_charge(): charge already appears to be offline', E_USER_WARNING);
		return;
		// @codeCoverageIgnoreEnd
	}

	\Osmium\Dogma\eval_charge_postexpressions($fit, $slottype, $index);
	unset($fit['dogma']['charges'][$slottype][$index]);
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

	foreach($fit['presets'] as $presetid => $preset) {
		foreach($preset['modules'] as $submods) {
			foreach($submods as $mod) {
				if($mod['typeid'] == $deleted_typeid) return;
			}
		}

		foreach($preset['chargepresets'] as $chargepreset) {
			foreach($chargepreset['charges'] as $subcharges) {
				foreach($subcharges as $charge) {
					if($charge['typeid'] == $deleted_typeid) return;
				}
			}
		}
	}
	
	foreach($fit['dronepresets'] as $dronepreset) {
		foreach($dronepreset['drones'] as $drone) {
			if($drone['typeid'] == $deleted_typeid) return;
		}
	}

	unset($fit['cache'][$deleted_typeid]);
}

/** @internal */
function get_attribute_in_cache($attributenameorid, &$out) {
	if(isset($out['__attributes'][$attributenameorid])) return;

	$name = is_numeric($attributenameorid) ?
		\Osmium\Dogma\get_attributename($attributenameorid) : $attributenameorid;

	$attribsq = \Osmium\Db\query_params('SELECT attributename, attributeid, highisgood, stackable, defaultvalue
  FROM eve.dgmattribs 
  WHERE attributename = $1', array($name));

	$row = \Osmium\Db\fetch_assoc($attribsq);

	$row['stackable'] = $row['stackable'] === 't';
	$row['highisgood'] = $row['highisgood'] === 't';
	$out['__attributes'][$row['attributename']] = $row;
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
  
	$metaq = \Osmium\Db\query_params('SELECT invtypes.typeid, typename, groupid, volume, mass, averageprice
	FROM eve.invtypes
	LEFT JOIN eve.averagemarketprices ON invtypes.typeid = averagemarketprices.typeid 
	WHERE invtypes.typeid IN ('.$typeidIN.')', array());
	while($row = \Osmium\Db\fetch_assoc($metaq)) {
		$out[$row['typeid']]['typename'] = $row['typename'];
		$out[$row['typeid']]['groupid'] = $row['groupid'];
		$out[$row['typeid']]['volume'] = $row['volume'];
		$out[$row['typeid']]['averageprice'] = $row['averageprice'];

		$out[$row['typeid']]['attributes']['mass'] = array(
			'attributename' => 'mass',
			'attributeid' => '4',
			'value' => $row['mass'],
			);
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
		$out['__attributes'][$row['attributename']]['stackable'] = $row['stackable'] === 't';
		$out['__attributes'][$row['attributename']]['highisgood'] = $row['highisgood'] === 't';
		$out['__attributes'][$row['attributename']]['defaultvalue'] = $row['defaultvalue'];

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
	foreach($fit['dogma']['skills'] as $typeid => &$s) {
		$s['skillLevel'] = $defaultlevel;
	}

	foreach($skillset as $typeid => $level) {
		if(!isset($fit['dogma']['skills'][$typeid])) continue;
		$fit['dogma']['skills'][$typeid]['skillLevel'] = $level;
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
