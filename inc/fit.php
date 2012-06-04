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

/* KEEP THIS NAMESPACE PURE. */

const VIEW_EVERYONE = 0;
const VIEW_PASSWORD_PROTECTED = 1;
const VIEW_ALLIANCE_ONLY = 2;
const VIEW_CORPORATION_ONLY = 3;
const VIEW_OWNER_ONLY = 4;

const EDIT_OWNER_ONLY = 0;
const EDIT_OWNER_AND_FITTING_MANAGER_ONLY = 1;
const EDIT_CORPORATION_ONLY = 2;
const EDIT_ALLIANCE_ONLY = 3;

const VISIBILITY_PUBLIC = 0;
const VISIBILITY_PRIVATE = 1;

const STATE_OFFLINE = 0;
const STATE_ONLINE = 1;
const STATE_ACTIVE = 2;
const STATE_OVERLOADED = 3;

/* ----------------------------------------------------- */

function get_slottypes() {
	return array('high', 'medium', 'low', 'rig', 'subsystem');
}

function get_stateful_slottypes() {
	/* Rigs and subsystems cannot be offlined/activated/overloaded */
	return array('high', 'medium', 'low');
}

function get_attr_slottypes() {
	return array(
		'high' => 'hiSlots',
		'medium' => 'medSlots',
		'low' => 'lowSlots',
		'rig' => 'upgradeSlotsLeft',
		'subsystem' => 'maxSubSystems'
		);
}

function get_state_names() {
	return array(
		STATE_OFFLINE => array('Offline', 'offline.png'),
		STATE_ONLINE => array('Online', 'online.png'),
		STATE_ACTIVE => array('Active', 'active.png'),
		STATE_OVERLOADED => array('Overloaded', 'overloaded.png'),
		);
}

function get_state_categories() {
	return array(
		null => array(),
		STATE_OFFLINE => array(0),
		STATE_ONLINE => array(0, 4),
		STATE_ACTIVE => array(0, 4, 1, 2, 3),
		STATE_OVERLOADED => array(0, 4, 1, 2, 3, 5),
		);
}

function create(&$fit) {
	$fit = array(
		'ship' => array(), /* Ship typeid, typename etc. */
		'modules' => array(), /* Module typeids, typenames etc. */
		'charges' => array(), /* Charge presets */
		'drones' => array(), /* Drone typeids, typenames, volume & count */
		'selectedpreset' => null, /* Default selected preset */
		'dogma' => array(), /* Dogma stuff, see dogma.php */
		'cache' => array(), /* All effect and attribute rows of fitted items */
		);

	/* Apply the default skill modifiers */
	\Osmium\Dogma\eval_skill_preexpressions($fit);
}

function destroy(&$fit) {
	$fit = array();
}

function reset(&$fit) {
	destroy($fit);
	create($fit);
}

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

function add_module(&$fit, $index, $typeid, $state = null) {
	get_attributes_and_effects(array($typeid), $fit['cache']);
	$type = get_module_slottype($fit['cache'][$typeid]['effects']);

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

	list($isactivable, ) = get_module_states($fit, $index, $typeid);
	if($state === null) {
		$state = ($isactivable && in_array($type, get_stateful_slottypes())) ? STATE_ACTIVE : STATE_ONLINE;
	}

	change_module_state($fit, $index, $typeid, $state);
}

function remove_module(&$fit, $index, $typeid) {
	get_attributes_and_effects(array($typeid), $fit['cache']);
	$type = get_module_slottype($fit['cache'][$typeid]['effects']);

	if(!isset($fit['modules'][$type][$index]['typeid'])) {
		trigger_error('remove_module(): trying to remove a nonexistent module!', E_USER_WARNING);
		maybe_remove_cache($fit, $typeid);
		return;
	}

	foreach($fit['charges'] as $name => $preset) {
		if(isset($preset[$type][$index])) {
			/* Remove charge */
			remove_charge($fit, $name, $type, $index);
		}
	}

	change_module_state($fit, $index, $typeid, null);
	unset($fit['dogma']['modules'][$type][$index]);
	unset($fit['modules'][$type][$index]);

	maybe_remove_cache($fit, $typeid);
}

function change_module_state(&$fit, $index, $typeid, $state) {
	$categories = get_state_categories();

	get_attributes_and_effects(array($typeid), $fit['cache']);
	$type = get_module_slottype($fit['cache'][$typeid]['effects']);

	$previous_state = $fit['modules'][$type][$index]['state'];

	$added_groups = array_diff($categories[$state], $categories[$previous_state]);
	$removed_groups = array_diff($categories[$previous_state], $categories[$state]);

	\Osmium\Dogma\eval_module_postexpressions($fit, $type, $index, $removed_groups);
	\Osmium\Dogma\eval_module_preexpressions($fit, $type, $index, $added_groups);

	$fit['modules'][$type][$index]['state'] = $state;
}

function toggle_module_state(&$fit, $index, $typeid, $next = true) {
	get_attributes_and_effects(array($typeid), $fit['cache']);
	$type = get_module_slottype($fit['cache'][$typeid]['effects']);
	$state = $fit['modules'][$type][$index]['state'];

	list($isactivable, $isoverloadable) = get_module_states($fit, $index, $typeid);
	$new_state = $next ? get_next_state($state, $isactivable, $isoverloadable) :
		get_previous_state($state, $isactivable, $isoverloadable);

	change_module_state($fit, $index, $typeid, $new_state);
}

function get_next_state($state, $isactivable, $isoverloadable) {
	if($state === null) {
		/* Should theoratically not happen, but handle it anyway */
		return STATE_OFFLINE;
	} else if($state === STATE_OFFLINE) {
		return STATE_ONLINE;
	} if($state === STATE_ONLINE) {
		return $isactivable ? STATE_ACTIVE : STATE_OFFLINE;
	} else if($state === STATE_ACTIVE) {
		return $isoverloadable ? STATE_OVERLOADED : STATE_OFFLINE;
	} else if($state === STATE_OVERLOADED) {
		return STATE_OFFLINE;
	}

	/* This is serious. We're fucked up. */
	trigger_error('get_next_state(): unknown module state ('.$state.')', E_USER_ERROR);
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

	trigger_error('get_previous_state(): unknown module state ('.$state.')', E_USER_ERROR);
}

function get_module_states(&$fit, $index, $typeid) {
	get_attributes_and_effects(array($typeid), $fit['cache']);
	$type = get_module_slottype($fit['cache'][$typeid]['effects']);

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

function add_charge(&$fit, $presetname, $slottype, $index, $typeid) {
	if(!isset($fit['modules'][$slottype][$index])) {
		trigger_error('add_charge(): cannot add charge to an empty module!', E_USER_WARNING);
		return;
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

function remove_charge(&$fit, $presetname, $slottype, $index) {
	if(!isset($fit['charges'][$presetname][$slottype][$index]['typeid'])) {
		trigger_error('remove_charge(): cannot remove a nonexistent charge.', E_USER_WARNING);
		return;
	}

	$typeid = $fit['charges'][$presetname][$slottype][$index]['typeid'];

	if($fit['selectedpreset'] === $presetname) {
		offline_charge($fit, $presetname, $slottype, $index);
	}

	unset($fit['charges'][$presetname][$slottype][$index]);

	maybe_remove_cache($fit, $typeid);
}

function offline_charge(&$fit, $presetname, $slottype, $index) {
		\Osmium\Dogma\eval_charge_postexpressions($fit, $presetname, $slottype, $index);
		unset($fit['dogma']['charges'][$presetname][$slottype][$index]);
}

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

function use_preset(&$fit, $presetname) {
	if($presetname !== null && !isset($fit['charges'][$presetname])) {
		trigger_error('use_preset(): no such preset', E_USER_WARNING);
		return;
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

function add_drones_batch(&$fit, $drones) {
	get_attributes_and_effects(array_keys($drones), $fit['cache']);

	foreach($drones as $typeid => $count) {
		add_drone($fit, $typeid, $count);
	}
}

function add_drone(&$fit, $typeid, $quantity = 1) {
	get_attributes_and_effects(array($typeid), $fit['cache']);

	if(!isset($fit['drones'][$typeid])) {
		$fit['drones'][$typeid] = array(
			'typeid' => $typeid,
			'typename' => $fit['cache'][$typeid]['typename'],
			'volume' => $fit['cache'][$typeid]['volume'],
			'count' => 0,
			);
	}

	$fit['drones'][$typeid]['count'] += $quantity;
}

function remove_drone(&$fit, $typeid, $quantity = 1) {
	if(!isset($fit['drones'][$typeid]) || $fit['drones'][$typeid]['count'] < $quantity) {
		trigger_error('remove_drone(): not enough drones to remove', E_USER_ERROR);
		unset($fit['drones'][$typeid]);
		maybe_remove_cache($fit, $typeid);
		return;
	}

	$fit['drones'][$typeid]['count'] -= $quantity;
	if($fit['drones'][$typeid]['count'] == 0) {
		unset($fit['drones'][$typeid]);
		maybe_remove_cache($fit, $typeid);
	}
}

/* ----------------------------------------------------- */

function get_capacitor_stability(&$fit) {
	static $step = 250;
	$categories = get_state_categories();

	/* Base formula taken from: http://wiki.eveuniversity.org/Capacitor_Recharge_Rate */
	$capacity = \Osmium\Dogma\get_ship_attribute($fit, 'capacitorCapacity');
	$tau = \Osmium\Dogma\get_ship_attribute($fit, 'rechargeRate') / 5.0;

	$usage_rate = 0;
	foreach($fit['modules'] as $type => $a) {
		foreach($a as $index => $module) {
			foreach($fit['cache'][$module['typeid']]['effects'] as $effect) {
				$effectdata = $fit['cache']['__effects'][$effect['effectname']];

				if(!in_array($effectdata['effectcategory'], $categories[$module['state']])) continue;
				if(!isset($effectdata['durationattributeid'])) continue;

				$duration = \Osmium\Dogma\get_module_attribute($fit, $type, $index, 
					$fit['cache']['__attributes'][$effectdata['durationattributeid']]['attributename']);

				if($effect['effectname'] == 'powerBooster') {
					/* Special case must be hardcoded (eg. cap boosters) */
					if(!isset($fit['charges'][$fit['selectedpreset']][$type][$index])) {
						continue;
					}

					$restored = \Osmium\Dogma\get_charge_attribute($fit, $fit['selectedpreset'],
					                                               $type, $index, 'capacitorBonus', false);

					$usage_rate -= $restored / $duration;
					continue;
				}

				if(!isset($effectdata['dischargeattributeid'])) continue;

				$discharge = \Osmium\Dogma\get_module_attribute($fit, $type, $index, 
					$fit['cache']['__attributes'][$effectdata['dischargeattributeid']]['attributename']);

				$usage_rate += $discharge / $duration;
			}
		}
	}

	$X = max(0, $usage_rate);
	/* I got the solution for cap stability by solving the quadratic equation:
	   dC   /       C        C   \   2Cmax
	   -- = |sqrt(-----) - ----- | x -----
	   dt   \     Cmax     Cmax  /    Tau

	           Cmax - X*Tau + sqrt(Cmax^2 - 2X*Tau*Cmax)          dC
	   ==> C = ----------------------------------------- with X = --
	                             2                                dt
	   
	   A simple check is that, for dC/dt = 0, the two solutions should be 0 and Cmax. */
	$delta = $capacity * $capacity - 2 * $tau * $X * $capacity;
	if($delta < 0) {
		$t = 0;
		$capacitor = $capacity; /* Start with full capacitor */

		/* Simulate what happens with the Runge-Kutta method (RK4) */
		$f = function($t, $c) use($capacity, $tau, $X) {
			return (sqrt($c / $capacity) - $c / $capacity) * 2 * $capacity / $tau - $X;
		};

		while($capacitor > 0) {
			$k1 = $f($t, $capacitor);
			$k2 = $f($t + 0.5 * $step, $capacitor + 0.5 * $step * $k1);
			$k3 = $f($t + 0.5 * $step, $capacitor + 0.5 * $step * $k2);
			$k4 = $f($t + $step, $capacitor + $step * $k3);
			$capacitor += $step * ($k1 + 2 * $k2 + 2 * $k3 + $k4) / 6;
			$t += $step;
		}

		return array($usage_rate, false, $t / 1000);
	} else {
		$C = 0.5 * ($capacity - $tau * $X + sqrt($delta));
		return array($usage_rate, true, 100 * $C / $capacity);
	}
}

/* ----------------------------------------------------- */

function prune_cache(&$fit) {
	foreach($fit['cache'] as $typeid => $bla) {
		maybe_remove_cache($fit, $typeid);
	}
}

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

function get_module_slottype($effects) {
	if(isset($effects['loPower'])) return 'low';
	if(isset($effects['medPower'])) return 'medium';
	if(isset($effects['hiPower'])) return 'high';
	if(isset($effects['rigSlot'])) return 'rig';
	if(isset($effects['subSystem'])) return 'subsystem';
	return false;
}

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

	$attribsq = \Osmium\Db\query('SELECT dgmtypeattribs.typeid, attributename, dgmattribs.attributeid, highisgood, stackable, value
  FROM eve.dgmattribs 
  JOIN eve.dgmtypeattribs ON dgmattribs.attributeid = dgmtypeattribs.attributeid
  WHERE dgmtypeattribs.typeid IN ('.$typeidIN.')');
	while($row = \Osmium\Db\fetch_assoc($attribsq)) {
		$tid = $row['typeid'];

		$out['__attributes'][$row['attributename']]['attributename'] = $row['attributename'];
		$out['__attributes'][$row['attributename']]['stackable'] = $row['stackable'];
		$out['__attributes'][$row['attributename']]['highisgood'] = $row['highisgood'];
		$out['__attributes'][$row['attributeid']] =& $out['__attributes'][$row['attributename']];

		unset($row['typeid']);
		unset($row['stackable']);
		unset($row['highisgood']);

		$out[$tid]['attributes'][$row['attributename']] = $row;
	}
}

/* ----------------------------------------------------- */

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

/* BE VERY CAREFUL WHEN CHANGING THIS FUNCTION.
 * ALL THE FITTINGHASHS DEPEND ON ITS RESULT. */
function get_unique($fit) {
	$unique = array(
		'metadata' => array(
			'name' => $fit['metadata']['name'],
			'description' => $fit['metadata']['description'],
			'tags' => $fit['metadata']['tags'],
			),
		'ship' => array(
			'typeid' => $fit['ship']['typeid'],
			),
		);

	foreach($fit['modules'] as $type => $d) {
		foreach($d as $index => $module) {
			$unique['modules'][$type][$index] = array($module['typeid'], $module['state']);
		}
	}

	foreach($fit['charges'] as $name => $preset) {
		foreach($preset as $type => $charges) {
			foreach($charges as $index => $charge) {
				$unique['charges'][$name][$type][$index] = $charge['typeid'];
			}
		}
	}

	foreach($fit['drones'] as $typeid => $drone) {
		$count = $drone['count'];
		$unique['drones'][$typeid] = $count;
	}

	return $unique;
}

function ksort_rec(array &$array) {
	ksort($array);
	foreach($array as &$v) {
		if(is_array($v)) ksort_rec($v);
	}
}

function get_hash($fit) {
	$unique = get_unique($fit);

	/* Ensure equality if key ordering is different */
	ksort_rec($unique);
	sort($unique['metadata']['tags']); /* tags should be ordered by value */

	return sha1(serialize($unique));
}

function commit_fitting(&$fit) {
	$fittinghash = get_hash($fit);

	$fit['metadata']['hash'] = $fittinghash;
  
	list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(fittinghash) FROM osmium.fittings WHERE fittinghash = $1', array($fittinghash)));
	if($count == 1) {
		/* Do nothing! */
		return;
	}

	/* Insert the new fitting */
	\Osmium\Db\query('BEGIN;');
	\Osmium\Db\query_params('INSERT INTO osmium.fittings (fittinghash, name, description, hullid, creationdate) VALUES ($1, $2, $3, $4, $5)', 
	                        array(
		                        $fittinghash,
		                        $fit['metadata']['name'],
		                        $fit['metadata']['description'],
		                        $fit['ship']['typeid'],
		                        time(),
		                        ));
  
	foreach($fit['metadata']['tags'] as $tag) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingtags (fittinghash, tagname) VALUES ($1, $2)', 
		                        array($fittinghash, $tag));
	}
  
	$module_order = array();
	foreach($fit['modules'] as $type => $data) {
		$z = 0;
		foreach($data as $index => $module) {
			$module_order[$type][$index] = $z;
			\Osmium\Db\query_params('INSERT INTO osmium.fittingmodules (fittinghash, slottype, index, typeid, state) VALUES ($1, $2, $3, $4, $5)', 
			                        array($fittinghash, $type, $z, $module['typeid'], $module['state']));
			++$z;
		}
	}
  
	foreach($fit['charges'] as $name => $preset) {
		foreach($preset as $type => $d) {
			foreach($d as $index => $charge) {
				if(!isset($module_order[$type][$index])) continue;
				$z = $module_order[$type][$index];

				\Osmium\Db\query_params('INSERT INTO osmium.fittingcharges (fittinghash, presetname, slottype, index, typeid) VALUES ($1, $2, $3, $4, $5)', 
				                        array($fittinghash, $name, $type, $z, $charge['typeid']));
			}
		}
	}
  
	foreach($fit['drones'] as $drone) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingdrones (fittinghash, typeid, quantity) VALUES ($1, $2, $3)',
		                        array($fittinghash, $drone['typeid'], $drone['count']));
	}
  
	\Osmium\Db\query('COMMIT;');
}

function commit_loadout(&$fit, $ownerid, $accountid) {
	commit_fitting($fit);

	$loadoutid = null;
	$password = ($fit['metadata']['view_permission'] == VIEW_PASSWORD_PROTECTED) ? $fit['metadata']['password'] : '';

	if(!isset($fit['metadata']['loadoutid'])) {
		/* Insert a new loadout */
		list($loadoutid) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('INSERT INTO osmium.loadouts (accountid, viewpermission, editpermission, visibility, passwordhash) VALUES ($1, $2, $3, $4, $5) RETURNING loadoutid', array($ownerid, $fit['metadata']['view_permission'], $fit['metadata']['edit_permission'], $fit['metadata']['visibility'], $password)));

		$fit['metadata']['loadoutid'] = $loadoutid;
	} else {
		/* Update a loadout */
		$loadoutid = $fit['metadata']['loadoutid'];

		\Osmium\Db\query_params('UPDATE osmium.loadouts SET accountid = $1, viewpermission = $2, editpermission = $3, visibility = $4, passwordhash = $5 WHERE loadoutid = $6', array($ownerid, $fit['metadata']['view_permission'], $fit['metadata']['edit_permission'], $fit['metadata']['visibility'], $password, $loadoutid));
	}

	/* If necessary, insert the appropriate history entry */
	$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT fittinghash, loadoutslatestrevision.latestrevision 
  FROM osmium.loadoutslatestrevision 
  JOIN osmium.loadouthistory ON (loadoutslatestrevision.loadoutid = loadouthistory.loadoutid 
                             AND loadoutslatestrevision.latestrevision = loadouthistory.revision) 
  WHERE loadoutslatestrevision.loadoutid = $1', array($loadoutid)));
	if($row === false || $row[0] != $fit['metadata']['hash']) {
		$nextrev = ($row === false) ? 1 : ($row[1] + 1);
		\Osmium\Db\query_params('INSERT INTO osmium.loadouthistory 
    (loadoutid, revision, fittinghash, updatedbyaccountid, updatedate) 
    VALUES ($1, $2, $3, $4, $5)', array($loadoutid, $nextrev, $fit['metadata']['hash'], $accountid, time()));
	}

	$fit['metadata']['accountid'] = $ownerid;

	\Osmium\Search\index(
		\Osmium\Db\fetch_assoc(
			\Osmium\Search\query_select_searchdata('WHERE loadoutid = $1', 
			                                       array($loadoutid))));
}

function get_fit($loadoutid, $revision = null) {
	if($revision === null && ($cache = \Osmium\State\get_cache('loadout-'.$loadoutid, null)) !== null) {
		return $cache;
	}

	if($revision === null) {
		/* Use latest revision */
		$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT latestrevision FROM osmium.loadoutslatestrevision WHERE loadoutid = $1', array($loadoutid)));
		if($row === false) return false;
		$revision = $row[0];
	}

	$loadout = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT accountid, viewpermission, editpermission, visibility, passwordhash FROM osmium.loadouts WHERE loadoutid = $1', array($loadoutid)));

	if($loadout === false) return false;

	$fitting = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT fittings.fittinghash AS hash, name, description, hullid, creationdate, revision FROM osmium.loadouthistory JOIN osmium.fittings ON loadouthistory.fittinghash = fittings.fittinghash WHERE loadoutid = $1 AND revision = $2', array($loadoutid, $revision)));

	if($fitting === false) return false;

	create($fit);
	select_ship($fit, $fitting['hullid']);

	$fit['metadata']['loadoutid'] = $loadoutid;
	$fit['metadata']['hash'] = $fitting['hash'];
	$fit['metadata']['name'] = $fitting['name'];
	$fit['metadata']['description'] = $fitting['description'];
	$fit['metadata']['view_permission'] = $loadout['viewpermission'];
	$fit['metadata']['edit_permission'] = $loadout['editpermission'];
	$fit['metadata']['visibility'] = $loadout['visibility'];
	$fit['metadata']['password'] = $loadout['passwordhash'];
	$fit['metadata']['revision'] = $fitting['revision'];
	$fit['metadata']['creation_date'] = $fitting['creationdate'];
	$fit['metadata']['accountid'] = $loadout['accountid'];

	$fit['metadata']['tags'] = array();
	$tagq = \Osmium\Db\query_params('SELECT tagname FROM osmium.fittingtags WHERE fittinghash = $1', array($fit['metadata']['hash']));
	while($r = \Osmium\Db\fetch_row($tagq)) {
		$fit['metadata']['tags'][] = $r[0];
	}

	$modules = array();
	$mq = \Osmium\Db\query_params('SELECT slottype, index, typeid, state FROM osmium.fittingmodules WHERE fittinghash = $1 ORDER BY index ASC', array($fit['metadata']['hash']));
	while($row = \Osmium\Db\fetch_row($mq)) {
		$modules[$row[0]][$row[1]] = array($row[2], (int)$row[3]);
	}

	add_modules_batch($fit, $modules);

	$charges = array();
	$cq = \Osmium\Db\query_params('SELECT presetname, slottype, index, fittingcharges.typeid FROM osmium.fittingcharges JOIN eve.invtypes ON fittingcharges.typeid = invtypes.typeid WHERE fittinghash = $1 ORDER BY index ASC', array($fit['metadata']['hash']));
	while($row = \Osmium\Db\fetch_row($cq)) {
		$charges[$row[0]][$row[1]][$row[2]] = $row[3];
	}

	foreach($charges as $presetname => $preset) {
		add_charges_batch($fit, $presetname, $preset);
	}
	
	$dq = \Osmium\Db\query_params('SELECT typeid, quantity FROM osmium.fittingdrones WHERE fittinghash = $1', array($fit['metadata']['hash']));
	$drones = array();
	while($row = \Osmium\Db\fetch_row($dq)) {
		$drones[$row[0]] = $row[1];
	}
  
	add_drones_batch($fit, $drones);
  
	\Osmium\State\put_cache('loadout-'.$loadoutid, $fit);
	return $fit;
}

function try_parse_fit_from_eve_xml(\SimpleXMLElement $e, &$errors) {
	create($fit);

	if(!isset($e['name'])) {
		$errors[] = 'Expected a name attribute in <fitting> tag, none found. Stopping.';
		return false;
	} else {
		$name = (string)$e['name'];
	}

	if(!isset($e->description) || !isset($e->description['value'])) {
		$errors[] = 'Expected <description> tag with value attribute, none found. Using empty description.';
		$description = '';
	} else {
		$description = (string)$e->description['value'];
	}
	$description = '(Imported from EVE XML format.)'."\n\n".$description;

	if(!isset($e->shipType) || !isset($e->shipType['value'])) {
		$errors[] = 'Expected <shipType> tag with value attribute, none found. Stopping.';
		return false;
	} else {
		$shipname = (string)$e->shipType['value'];
	}

	$row = \Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT typeid FROM osmium.invships WHERE typename = $1',
			array($shipname)));
	if($row === false) {
		$errors[] = 'Could not fetch typeID of "'.$shipname.'". Obsolete/unpublished ship? Stopping.';
		return false;
	}
	select_ship($fit, $row[0]);

	if(!isset($e->hardware)) {
		$errors[] = 'No <hardware> element found. Expected at least 1. Stopping.';
		return false;
	}

	$typenames = array();
	$drones = array();
	$modules = array();
	$recover_modules = array();

	static $modtypes = array(
		'low' => 'low',
		'med' => 'medium',
		'hi' => 'high',
		'rig' => 'rig',
		'subsystem' => 'subsystem',
		);

	foreach($e->hardware as $hardware) {
		if(!isset($hardware['type'])) {
			$errors[] = 'Tag <hardware> has no type attribute. Discarded.';
			continue;
		}
		$type = (string)$hardware['type'];
		$typenames[$type] = true;

		if(!isset($hardware['slot'])) {
			$errors[] = 'Tag <hardware> has no slot attribute. (Recoverable error.)';
			$slot = '';
		} else {
			$slot = (string)$hardware['slot'];
		}

		if($slot === "drone bay") {
			if(!isset($hardware['qty'])) $qty = 1;
			else $qty = (int)$hardware['qty'];
			if($qty <= 0) continue;

			$drones[] = array('count' => $qty, 'typename' => $type);
		} else {
			$p_slot = $slot;
			$slot = explode(' ', $slot);
			if(count($slot) != 3
			   || $slot[1] != 'slot'
			   || !in_array($slot[0], array_keys($modtypes))
			   || !is_numeric($slot[2])
			   || (int)$slot[2] < 0
			   || (int)$slot[2] > 7) {

				$errors[] = 'Could not parse slot attribute "'.$p_slot.'". (Recoverable error.)';
				$recover_modules[] = $type;
			} else {
				$slottype = $modtypes[$slot[0]];
				$index = $slot[2];
				$modules[$slottype][$index] = $type;
			}
		}
	}

	$typenames['OsmiumSentinel'] = true; /* Just in case $typenames were to be empty */
	$typename_to_id = array();
	/* That's a pretty dick move from CCP to NOT include
	 * typeIDs. Whoever had that idea should be kicked in the nuts. */
	$req = \Osmium\Db\query('SELECT typeid, typename FROM eve.invtypes WHERE typename IN ('
	                        .implode(',', array_map(function($name) {
				                        return "'".\Osmium\Db\escape_string($name)."'";
			                        }, array_keys($typenames))).')');
	while($row = \Osmium\Db\fetch_row($req)) {
		$typename_to_id[$row[1]] = $row[0];
	}

	$realmodules = array();
	foreach($modules as $type => $m) {
		foreach($m as $i => $typename) {
			if(!isset($typename_to_id[$typename])) {
				$errors[] = 'Could not find typeID of "'.$typename.'". Skipped.';
				continue;
			}
			$realmodules[$type][$i] = $typename_to_id[$typename];
		}
	}
	foreach($recover_modules as $typename) {
		if(!isset($typename_to_id[$typename])) {
			$errors[] = 'Could not find typeID of "'.$typename.'". Skipped.';
			continue;
		}
		/* "low" does not matter here, it will be corrected in update_modules later. */
		$realmodules['low'][] = $typename_to_id[$typename];
	}

	$realdrones = array();
	foreach($drones as $drone) {
		if(!isset($typename_to_id[$drone['typename']])) {
			$errors[] = 'Could not find typeID of "'.$drone['typename'].'". Skipped.';
			continue;
		}
		
		$typeid = $typename_to_id[$drone['typename']];
		if(!isset($realdrones[$typeid])) $realdrones[$typeid] = 0;
		$realdrones[$typeid] += $drone['count'];
	}

	add_modules_batch($fit, $realmodules);
	add_drones_batch($fit, $realdrones);

	$fit['metadata']['name'] = $name;
	$fit['metadata']['description'] = $description;
	$fit['metadata']['tags'] = array();
	$fit['metadata']['view_permission'] = VIEW_OWNER_ONLY;
	$fit['metadata']['edit_permission'] = EDIT_OWNER_ONLY;
	$fit['metadata']['visibility'] = VISIBILITY_PUBLIC;

	return $fit;
}
