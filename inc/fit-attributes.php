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
 * Get capacitor-related attributes of a given fit.
 *
 * Returns an array of length 3: the first value is the capacitor
 * usage rate (minus peak recharge rate) in GJ/s, the second is a
 * boolean indicating whether the capacitor is stable or not and the
 * third is either time in seconds until the capacitor is depleted, or
 * the stable percentage level (between 0 and 100).
 */
function get_capacitor_stability(&$fit) {
	static $step = 1000; /* Number of milliseconds between each integration step */
	$categories = get_state_categories();

	/* Base formula taken from: http://wiki.eveuniversity.org/Capacitor_Recharge_Rate */
	$capacity = \Osmium\Dogma\get_ship_attribute($fit, 'capacitorCapacity');
	$tau = \Osmium\Dogma\get_ship_attribute($fit, 'rechargeRate') / 5.0;

	$usage_rate = 0;
	$usage = array();
	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $module) {
			foreach($fit['cache'][$module['typeid']]['effects'] as $effect) {
				$effectdata = $fit['cache']['__effects'][$effect['effectname']];

				if(!in_array($effectdata['effectcategory'], $categories[$module['state']])) continue;
				if(!isset($effectdata['durationattributeid'])) continue;

				$duration = \Osmium\Dogma\get_module_attribute(
					$fit, $type, $index, 
					\Osmium\Dogma\get_attributename($effectdata['durationattributeid']));

				if($effect['effectname'] == 'powerBooster') {
					/* Special case must be hardcoded (eg. cap boosters) */
					if(!isset($fit['charges'][$type][$index])) {
						continue;
					}

					$restored = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'capacitorBonus', false);

					$usage_rate -= $restored / $duration;
					$usage[] = array(-$restored, $duration, 0);
					continue;
				}

				if(!isset($effectdata['dischargeattributeid'])) continue;
				get_attribute_in_cache($effectdata['dischargeattributeid'], $fit['cache']);

				$discharge = \Osmium\Dogma\get_module_attribute(
					$fit, $type, $index, 
					\Osmium\Dogma\get_attributename($effectdata['dischargeattributeid']));

				$usage_rate += $discharge / $duration;
				$usage[] = array($discharge, $duration, 0);
			}
		}
	}

	$peak_rate = (sqrt(0.25) - 0.25) * 2 * $capacity / $tau; /* Recharge rate at 25% capacitor */
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
		/* $delta negative, not cap stable */
		$t = 0;
		$capacitor = $capacity; /* Start with full capacitor */

		/* Simulate what happens with the Runge-Kutta method (RK4) */
		$f = function($c) use($capacity, $tau) {
			$c = max($c, 0);
			return (sqrt($c / $capacity) - $c / $capacity) * 2 * $capacity / $tau;
		};

		/* The limit on $t is there for two reasons: 1. because there
		 * is no guarantee this loop will ever exit otherwise, and
		 * 2. to prevent malicious users from generating excessive
		 * load. */
		while($capacitor > 0 && $t < 3600000) {
			foreach($usage as &$u) {
				while($u[2] <= $t) {
					$capacitor -= $u[0];
					$u[2] += $u[1];
				}
			}

			$k1 = $f($capacitor);
			$k2 = $f($capacitor + 0.5 * $step * $k1);
			$k3 = $f($capacitor + 0.5 * $step * $k2);
			$k4 = $f($capacitor + $step * $k3);
			$capacitor += ($increment = $step * ($k1 + 2 * $k2 + 2 * $k3 + $k4) / 6);
			$t += $step;
		}

		/* Use a linear interpolation to refine the result */
		$rate = $increment / $step;
		$zero = ($increment - $capacitor) * $rate;
		$t -= (1 - $zero) * $step;

		return array($usage_rate - $peak_rate, false, $t / 1000);
	} else {
		/* $delta positive, cap-stable */
		/* Use the highest root of our equation (but there is also another solution below the 25% peak) */
		$C = 0.5 * ($capacity - $tau * $X + sqrt($delta));
		return array($usage_rate - $peak_rate, true, 100 * $C / $capacity);
	}
}

/**
 * Get maximum/average/minimum effective hitpoints and hull, armor and
 * shield resonances (1 - resistances).
 *
 * Returns an array of the following structure: array(ehp => {min,
 * avg, max}, {shield, armor, hull} => {capacity, resonance => {em,
 * thermal, kinetic, explosive}}).
 */
function get_ehp_and_resists(&$fit) {
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
		/* TODO user-defined profiles */
		$out['ehp']['avg'] += 4 * $out[$name]['capacity'] / array_sum($out[$name]['resonance']);
	}

	return $out;
}

/**
 * Get reinforced/sustained tank. Returns an array of the two values
 * (reinforced/sustained in this order, in EHP repaired per second).
 *
 * @param $effectname name of the effect that does the repairing
 *
 * @param $boostattributename value of the attribute that gets
 * increased by the repairing effect
 *
 * @param $resonances array of four resonances, for example
 * get_ehp_and_resists()['shield']['resonance']
 *
 * @param $capacitor assumes same format as the returned value of
 * get_capacitor_stability()
 */
function get_repaired_amount_per_second(&$fit, $effectname, $boostattributename, $resonances, $capacitor, $nullifydischargeifchargepresent = false) {
	if(!isset($fit['cache']['__effects'][$effectname])) {
		/* The interesting effect is not cached, so no module has
		 * it. It is useless to continue further. */
		return array(0, 0);
	}

	$total = 0;
	$sustained = 0;
	$capusage = $capacitor[0];

	$durationattributeid = $fit['cache']['__effects'][$effectname]['durationattributeid'];
	$dischargeattributeid = $fit['cache']['__effects'][$effectname]['dischargeattributeid'];
	get_attribute_in_cache($durationattributeid, $fit['cache']);
	get_attribute_in_cache($dischargeattributeid, $fit['cache']);

	$durationattributename = \Osmium\Dogma\get_attributename($durationattributeid);
	$dischargeattributename = \Osmium\Dogma\get_attributename($dischargeattributeid);

	$modules = array();

	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $module) {
			if(!isset($fit['cache'][$module['typeid']]['effects'][$effectname])) {
				continue;
			}
			if($module['state'] !== STATE_ACTIVE && $module['state'] !== STATE_OVERLOADED) {
				continue;
			}

			$amount = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $boostattributename);
			$duration = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $durationattributename);
			if($nullifydischargeifchargepresent && isset($fit['charges'][$type][$index]['typeid'])) {
				$discharge = 0;
			} else $discharge = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $dischargeattributename);
			
			$total += $amount / $duration;

			$modules[] = array($amount, $duration, $discharge);
			$capusage -= $discharge / $duration;
		}
	}

	/* Sort modules by best HP repaired per capacitor unit */
	usort($modules, function($b, $a) {
			if($a[2] == 0) return ($b[2] == 0) ? 0 : 1;
			else if($b[2] == 0) return -1;

			$k =  $a[0] / $a[2] - $b[0] / $b[2];
			return $k > 0 ? 1 : ($k < 0 ? -1 : 0);
		});

	/* Enable repairers until cap stability is lost */
	while($capusage < 0 && ($m = array_shift($modules)) !== null) {
		$module_capusage = $m[2] / $m[1];

		if($module_capusage == 0) $fraction = 1;
		else $fraction = max(min(1, -$capusage / $module_capusage), 0);
		$capusage += $fraction * $module_capusage;
		$sustained += $fraction * ($m[0] / $m[1]);
	}

	$factor = 4 / array_sum($resonances);
	return array($total * $factor, $sustained * $factor);
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

	get_attribute_in_cache($fit['cache']['__effects'][$attackeffectname]['durationattributeid'], $fit['cache']);

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

	get_attribute_in_cache($fit['cache']['__effects']['targetAttack']['durationattributeid'], $fit['cache']);

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
 * Get the optimal range, falloff and tracking speed of a module (in
 * the current preset).
 *
 * @returns an array with any of the three keys: trackingspeed, range,
 * falloff.
 */
function get_optimal_falloff_tracking_of_module($fit, $type, $index) {
	$categories = get_state_categories();

	$attributes = array();
	$typeid = $fit['modules'][$type][$index]['typeid'];
	$state = $fit['modules'][$type][$index]['state'];

	if($state != STATE_ACTIVE && $state != STATE_OVERLOADED) return $attributes;

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

			get_attribute_in_cache($attributeid, $fit['cache']);
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

			/* Source: http://wiki.eveonline.com/en/wiki/Acceleration */
			/* Integrate the velocity of the missile from 0 to flighttime: */
			$K = -1000000 / ($mass * $agility);
			$attributes['maxrange'] = $velocity * ($flighttime + (1 - exp($K * $flighttime)) / $K);

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
	$types[$fit['ship']['typeid']] = array(1, $fit['ship']['typename']);
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

	get_attributes_and_effects(array_keys($types), $fit['cache']);

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
			$durationid = $fit['cache']['__effects']['miningLaser']['durationattributeid'];

			get_attribute_in_cache($durationid, $fit['cache']);
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
