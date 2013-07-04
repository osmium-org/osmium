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
 * Get capacitor-related attributes of a given fit.
 *
 * Returns an array of length 3: the first value is the capacitor
 * usage rate (minus peak recharge rate) in GJ/ms, the second is a
 * boolean indicating whether the capacitor is stable or not and the
 * third is either time in seconds until the capacitor is depleted, or
 * the stable percentage level (between 0 and 100).
 */
function get_capacitor_stability(&$fit, $reload = true) {
	dogma_get_capacitor($fit['__dogma_context'], $reload, $delta, $stable, $p);
	return array($delta, $stable, $stable ? $p : ($p / 1000.0));
}

/**
 * Get maximum/average/minimum effective hitpoints and hull, armor and
 * shield resonances (1 - resistances).
 *
 * @param $damageprofile array(damagetype => damageamount)
 *
 * @returns array(ehp => {min, avg, max}, {shield, armor, hull} =>
 * {capacity, resonance => {em, thermal, kinetic, explosive}}).
 */
function get_ehp_and_resists(&$fit, $damageprofile) {
	static $layers = array(
		array('shield', 'shieldCapacity', 'shield'),
		array('armor', 'armorHP', 'armor'),
		array('hull', 'hp', ''),
		);

	$out = array(
		'ehp' => array(
			'min' => 0,
			'avg' => 0,
			'max' => 0,
			),
		);

	foreach($layers as $a) {
		list($name, $attributename, $resistprefix) = $a;

		$out[$name]['capacity'] = \Osmium\Dogma\get_ship_attribute($fit, $attributename);
		$out[$name]['resonance']['em'] = \Osmium\Dogma\get_ship_attribute(
			$fit, lcfirst($resistprefix.'EmDamageResonance'));
		$out[$name]['resonance']['thermal'] = \Osmium\Dogma\get_ship_attribute(
			$fit, lcfirst($resistprefix.'ThermalDamageResonance'));
		$out[$name]['resonance']['kinetic'] = \Osmium\Dogma\get_ship_attribute(
			$fit, lcfirst($resistprefix.'KineticDamageResonance'));
		$out[$name]['resonance']['explosive'] = \Osmium\Dogma\get_ship_attribute(
			$fit, lcfirst($resistprefix.'ExplosiveDamageResonance'));

		$out['ehp']['min'] += $out[$name]['capacity'] / max($out[$name]['resonance']);
		$out['ehp']['max'] += $out[$name]['capacity'] / min($out[$name]['resonance']);

		$sum = array_sum($damageprofile);
		if($sum == 0) {
			trigger_error(__FUNCTION__.'(): invalid damage profile', E_USER_WARNING);
			$sum = 1;
		}
		$avgresonance = 0;
		foreach($damageprofile as $type => $damage) {
			$avgresonance += $damage * $out[$name]['resonance'][$type];
		}
		$avgresonance /= $sum;

		$out['ehp']['avg'] += $out[$name]['capacity'] / $avgresonance;
	}

	return $out;
}


/**
 * Get reinforced/sustained tank.
 *
 * @param $ehp array of the same format as the return value of
 * get_ehp_and_resists()
 *
 * @param $capacitor assumes same format as the returned value of
 * get_capacitor_stability()
 *
 * @param $damageprofile array(damagetype => damageamount)
 *
 * @returns array(repair_type => array(reinforced, sustained))
 */
function get_tank(&$fit, $ehp, $capacitor, $damageprofile) {
	static $effects = array(
		'hull_repair' => array('structureRepair', 'structureDamageAmount', 'hull'),
		'armor_repair' => array('armorRepair', 'armorDamageAmount', 'armor'),
		'armor_repair_fueled' => array('fueledArmorRepair', 'armorDamageAmount', 'armor'),
		'shield_boost' =>  array('shieldBoosting', 'shieldBonus', 'shield'),
		'shield_boost_fueled' => array('fueledShieldBoosting', 'shieldBonus', 'shield'),
		'shield_passive' => array(null, null, 'shield'),
		);

	$modules = array();
	$out = array();

	foreach($effects as $key => $effectd) {
		list($effectname, $attributename, $type) = $effectd;

		if(!isset($fit['cache']['__effects'][$effectname])) {
			/* Effect not in cache, there's no point traversing the
			 * modules */
			$out[$key] = array(0, 0);
			continue;
		}

		$durationattributeid = $fit['cache']['__effects'][$effectname]['durationattributeid'];
		$dischargeattributeid = $fit['cache']['__effects'][$effectname]['dischargeattributeid'];
		$durationattributename = \Osmium\Dogma\get_attributename($durationattributeid);
		$dischargeattributename = \Osmium\Dogma\get_attributename($dischargeattributeid);

		foreach(get_modules($fit) as $type => $a) {
			foreach($a as $index => $module) {
				if(!isset($fit['cache'][$module['typeid']]['effects'][$effectname])) {
					continue;
				}

				if($module['state'] !== STATE_ACTIVE && $module['state'] !== STATE_OVERLOADED) {
					continue;
				}

				$amount = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $attributename);
				if($key === 'armor_repair_fueled' && isset($fit['charges'][$type][$index]['typeid'])
				   && $fit['charges'][$type][$index]['typeid'] == 28668) {
					/* XXX: ugly exception for the Ancillary Armor
					 * Repairer. Proper effect override will be in
					 * libdogma. */
					$amount *= \Osmium\Dogma\get_module_attribute($fit, $type, $index,
					                                              'chargedArmorDamageMultiplier');
				}

				$duration = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $durationattributename);
				$discharge = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $dischargeattributename);

				$modules[] = array($key, $amount, $duration, $discharge);				
			}
		}
	}

	/* Sort modules by best EHP repaired per capacitor unit */
	usort($modules, function($b, $a) {
			if($a[3] == 0) return ($b[3] == 0) ? 0 : 1;
			else if($b[3] == 0) return -1;

			$k =  $a[1] / $a[3] - $b[1] / $b[3];
			return $k > 0 ? 1 : ($k < 0 ? -1 : 0);
		});

	$usage = $capacitor[0];

	/* First pass: reinforced tank values */
	foreach($modules as $m) {
		list($key, $amount, $duration, $discharge) = $m;

		if(!isset($out[$key])) {
			$out[$key] = array(0, 0);
		}

		$out[$key][0] += $amount / $duration;
		$usage -= $discharge / $duration;
	}

	/* Second pass: enable best modules while capacitor is available */
	foreach($modules as $m) {
		list($key, $amount, $duration, $discharge) = $m;

		$moduleusage = $discharge / $duration;
		if($moduleusage == 0) $fraction = 1;
		else if($usage > 0) continue;
		else $fraction = min(1, -$usage / $moduleusage);

		$usage += $fraction * $moduleusage;
		$out[$key][1] += $fraction * $amount / $duration;
	}

	$shieldrechargerate = \Osmium\Dogma\get_ship_attribute($fit, 'shieldRechargeRate');
	$passiverate = ($shieldrechargerate > 0) ? 2.5 * $ehp['shield']['capacity'] / $shieldrechargerate : 0;
	$out['shield_passive'] = array($passiverate, $passiverate);

	$sum = array_sum($damageprofile);
	if($sum == 0) {
		trigger_error(__FUNCTION__.'(): invalid damage profile given', E_USER_WARNING);
		$sum = 1;
	}
	$multipliers = array();

	foreach(array('shield', 'armor', 'hull') as $ltype) {
		$resonances = $ehp[$ltype]['resonance'];
		$multipliers[$ltype] = 0;

		foreach($damageprofile as $type => $damage) {
			$multipliers[$ltype] += $damage * $resonances[$type];
		}

		$multipliers[$ltype] /= $sum;
	}

	foreach($effects as $key => $e) {
		list(, , $type) = $e;

		if(!isset($out[$key])) continue;

		$out[$key] = array(
			$out[$key][0] / $multipliers[$type],
			$out[$key][1] / $multipliers[$type],
			);
	}


	return $out;
}

/**
 * Get DPS and volley damage. Returns an array of the two values (dps,
 * volley) in this order.
 *
 * This function assumes the damage comes from the emDamage,
 * thermalDamage, kineticDamage and explosiveDamage of the charge
 * fitted to the turret which has the attacking attribute.
 *
 * Only active turrets are considered, and the charge of the currently
 * selected charge preset is used.
 *
 * @param $attackeffectname name of the effect that does the attacking
 *
 * @param $modulemultiplierattributename optional name of the turret
 * damage multiplier attribute
 *
 * @param $globalmultiplier optional value of a global damage
 * multiplier
 */
function get_damage_from_attack_effect(&$fit, $attackeffectname, $modulemultiplierattributename = null, $globalmultiplier = 1) {
	if(!isset($fit['cache']['__effects'][$attackeffectname])) {
		return array(0, 0);
	}

	$dps = 0;
	$alpha = 0;

	$durationattributename = \Osmium\Dogma\get_attributename(
		$fit['cache']['__effects'][$attackeffectname]['durationattributeid']);

	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $module) {
			if(!isset($fit['cache'][$module['typeid']]['effects'][$attackeffectname])) {
				continue;
			}
			if(!isset($fit['charges'][$type][$index])) {
				continue;
			}
			if($module['state'] !== STATE_ACTIVE && $module['state'] !== STATE_OVERLOADED) {
				continue;
			}

			$duration = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $durationattributename);
			$damage = 
				\Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'emDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'thermalDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'kineticDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'explosiveDamage');

			$multiplier = $modulemultiplierattributename === null ? 1 :
				\Osmium\Dogma\get_module_attribute($fit, $type, $index, $modulemultiplierattributename);

			$dps += $multiplier * $damage / $duration;
			$alpha += $multiplier * $damage;
		}
	}

	return array(1000 * $dps * $globalmultiplier, $alpha * $globalmultiplier);
}

/**
 * Get DPS/volley damage from active missile launchers.
 */
function get_damage_from_missiles(&$fit) {
	return get_damage_from_attack_effect(
		$fit, 'useMissiles', null,
		\Osmium\Dogma\get_char_attribute($fit, 'missileDamageMultiplier')
		);
}

/**
 * Get DPS/volley damage from active turrets (projectile, hybrids and lasers).
 */
function get_damage_from_turrets(&$fit) {
	$projectiles = get_damage_from_attack_effect(
		$fit, 'projectileFired', 'damageMultiplier'
		);

	$lasers = get_damage_from_attack_effect(
		$fit, 'targetAttack', 'damageMultiplier'
		);

	return array(
		$projectiles[0] + $lasers[0],
		$projectiles[1] + $lasers[1],
		);
}

/**
 * Get DPS from active drones (drones "in space").
 */
function get_damage_from_drones(&$fit) {
	$dps = 0;

	if(!isset($fit['cache']['__effects']['targetAttack'])) return 0;

	$durationattributename = 
		\Osmium\Dogma\get_attributename($fit['cache']['__effects']['targetAttack']['durationattributeid']);

	foreach($fit['drones'] as $drone) {
		if($drone['quantityinspace'] == 0) continue;

		if(!isset($fit['cache'][$drone['typeid']]['effects']['targetAttack'])) {
			continue;
		}

		$duration = \Osmium\Dogma\get_drone_attribute($fit, $drone['typeid'], $durationattributename);
		$damage = 
			\Osmium\Dogma\get_drone_attribute($fit, $drone['typeid'], 'emDamage')
			+ \Osmium\Dogma\get_drone_attribute($fit, $drone['typeid'], 'thermalDamage')
			+ \Osmium\Dogma\get_drone_attribute($fit, $drone['typeid'], 'kineticDamage')
			+ \Osmium\Dogma\get_drone_attribute($fit, $drone['typeid'], 'explosiveDamage');

		$multiplier = \Osmium\Dogma\get_drone_attribute($fit, $drone['typeid'], 'damageMultiplier');

		$dps += $drone['quantityinspace'] * $multiplier * $damage / $duration;
	}

	return 1000 * $dps;
}

/**
 * Get "interesting" attributes of a module.
 *
 * @returns an array which can contain any of the following keys:
 * trackingspeed, range (optimal range), falloff, maxrange (precise
 * missile range)
 */
function get_module_interesting_attributes($fit, $type, $index) {
	$categories = get_state_categories();

	$attributes = array();
	$typeid = $fit['modules'][$type][$index]['typeid'];
	$state = $fit['modules'][$type][$index]['state'];

	if($state != STATE_ACTIVE && $state != STATE_OVERLOADED) return $attributes;
	if(!isset($fit['cache'][$typeid]['effects'])) return $attributes;

	foreach($fit['cache'][$typeid]['effects'] as $effect) {
		$effectdata = $fit['cache']['__effects'][$effect['effectname']];

		if(!in_array($effectdata['effectcategory'], $categories[$state])) continue;
		if(!$effectdata['trackingspeedattributeid']
		   && !$effectdata['rangeattributeid']
		   && !$effectdata['falloffattributeid']) {
			continue;
		}

		foreach(array('trackingspeed', 'range', 'falloff') as $t) {
			if(!$effectdata[$t.'attributeid']) continue;
			$attributeid = $effectdata[$t.'attributeid'];

			$attributename = \Osmium\Dogma\get_attributename($attributeid);

			$attributes[$t] = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $attributename);
		}

		return $attributes;
	}

	if(isset($fit['charges'][$type][$index])) {
		$typeid = $fit['charges'][$type][$index]['typeid'];
		if(isset($fit['cache'][$typeid]['attributes']['explosionDelay']) &&
		   isset($fit['cache'][$typeid]['attributes']['maxVelocity'])) {
			$flighttime = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'explosionDelay') / 1000;
			$velocity = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'maxVelocity');
			$mass = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'mass');
			$agility = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'agility');

			if($mass != 0 && $agility != 0) {
				/* Source: http://wiki.eveonline.com/en/wiki/Acceleration */
				/* Integrate the velocity of the missile from 0 to flighttime: */
				$K = -1000000 / ($mass * $agility);
				$attributes['maxrange'] = $velocity * ($flighttime + (1 - exp($K * $flighttime)) / $K);
			} else {
				/* Zero mass or zero agility, for example defender missiles */
				$attributes['maxrange'] = $velocity * $flighttime;
			}

			return $attributes;
		}
	}

	return $attributes;
}

/**
 * Get the average price of a fit, using the in-game average
 * prices. Items whose price cannot be determined will be added to
 * $missing.
 */
function get_average_price(&$fit, &$missing) {
	$total = 0;

	$types = array();

	if(isset($fit['ship']['typeid'])) {
		$types[$fit['ship']['typeid']] = array(1, $fit['ship']['typename']);
	}
	foreach($fit['modules'] as $a) {
		foreach($a as $m) {
			if(isset($types[$m['typeid']])) {
				++$types[$m['typeid']][0];
			} else {
				$types[$m['typeid']] = array(1, $m['typename']);
			}
		}
	}
	foreach($fit['charges'] as $a) {
		foreach($a as $c) {
			if(isset($types[$c['typeid']])) {
				++$types[$c['typeid']][0];
			} else {
				$types[$c['typeid']] = array(1, $c['typename']);
			}
		}
	}
	foreach($fit['drones'] as $d) {
		$qty = $d['quantityinbay'] + $d['quantityinspace'];

		if(isset($types[$d['typeid']])) {
			$types[$d['typeid']][0] += $qty;
		} else {
			$types[$d['typeid']] = array($qty, $d['typename']);
		}
	}

	foreach($types as $typeid => $a) {
		list($qty, $name) = $a;

		$p = isset($fit['cache'][$typeid]['averageprice']) ? $fit['cache'][$typeid]['averageprice'] : null;
		if($p !== null) {
			$total += $qty * $p;
		} else {
			$missing[$name] = true;
		}
	}

	return $total;
}

/**
 * Get the mining yield of the current fit, in m³ / ms (cubic meters
 * per millisecond).
 */
function get_mining_yield(&$fit) {
	if(!isset($fit['cache']['__effects']['miningLaser'])) return 0;
	$total = 0;

	foreach($fit['modules'] as $type => $a) {
		foreach($a as $index => $m) {
			$typeid = $m['typeid'];

			if(!isset($fit['cache'][$typeid]['effects']['miningLaser'])) continue;
			if($m['state'] != STATE_ACTIVE && $m['state'] != STATE_OVERLOADED) continue;

			$durationid = $fit['cache']['__effects']['miningLaser']['durationattributeid'];

			$durationname = \Osmium\Dogma\get_attributename($durationid);

			$duration = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $durationname);

			if(isset($fit['charges'][$type][$index])) {
				/* Has crystal */
				$amount = 'specialtyMiningAmount';
			} else {
				/* No crystal */
				$amount = 'miningAmount';
			}
			$volume = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $amount);

			$total += $volume / $duration;
		}
	}

	return $total;
}

/**
 * Return the total bandwidth used by the drones currently in space.
 *
 * This function isn't really needed at the moment, but in the future
 * there might be some ships or modules that have modifiers for the
 * bandwidth of certain drone types (like a bandwidth reduction bonus
 * for logistics drones on logistics ships, etc.).
 */
function get_used_drone_bandwidth($fit) {
	$used = 0;

	foreach($fit['drones'] as $drone) {
		if(($q = $drone['quantityinspace']) == 0) continue; /* Don't calculate the attribute for nothing */

		$used += $q * \Osmium\Dogma\get_drone_attribute($fit, $drone['typeid'], 'droneBandwidthUsed');
	}

	return $used;
}

/** Return the total volume used by drones. */
function get_used_drone_capacity($fit) {
	$used = 0;

	foreach($fit['drones'] as $drone) {
		$used += $drone['volume'] * ($drone['quantityinspace'] + $drone['quantityinbay']);
	}

	return $used;
}
