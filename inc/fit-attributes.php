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
	\Osmium\Dogma\auto_init($fit);
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

	\Osmium\Dogma\auto_init($fit);

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

		// @codeCoverageIgnoreStart
		if($sum == 0) {
			trigger_error(__FUNCTION__.'(): invalid damage profile', E_USER_WARNING);
			$sum = 1;
		}
		// @codeCoverageIgnoreEnd

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
function get_tank(&$fit, $ehp, $capacitor, $damageprofile, $reload = false) {
	static $localrepaireffects = array(
		'hull' => [ [ EFFECT_StructureRepair, 'structureDamageAmount' ] ],
		'armor' => [ [ EFFECT_ArmorRepair, 'armorDamageAmount' ],
		             [ EFFECT_FueledArmorRepair, 'armorDamageAmount' ] ],
		'shield' => [ [ EFFECT_ShieldBoosting, 'shieldBonus' ],
		              [ EFFECT_FueledShieldBoosting, 'shieldBonus' ] ]
	);

	\Osmium\Dogma\auto_init($fit);

	$modules = array();
	$out = array();

	foreach($localrepaireffects as $layer => $effects) {
		foreach($effects as $effectdata) {
			list($effect, $moduleattribute) = $effectdata;

			foreach(get_modules($fit) as $type => $a) {
				foreach($a as $index => $module) {
					$ret = dogma_type_has_effect(
						$module['typeid'],
						\Osmium\Dogma\get_dogma_states()[$module['state']],
						$effect, $hasit);
					if($ret !== DOGMA_OK || $hasit !== true) {
						continue;
					}

					$amount = \Osmium\Dogma\get_module_attribute($fit, $type, $index, $moduleattribute);
					dogma_get_location_effect_attributes(
						$fit['__dogma_context'],
						[ DOGMA_LOC_Module, "module_index" => $module['dogma_index'] ],
						$effect,
						$duration, $tracking, $discharge,
						$range, $falloff, $usagechance
					);

					dogma_get_number_of_module_cycles_before_reload(
						$fit['__dogma_context'], $module['dogma_index'], $ncycles
					);

					$reloadtime = \Osmium\Dogma\get_module_attribute($fit, $type, $index, ATT_ReloadTime);

					if($duration > 1e-300) {
						$modules[] = array($layer, $amount, $duration, $discharge, $ncycles, $reloadtime);
					}
				}
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
		list($key, $amount, $duration, $discharge, $ncycles, $reloadtime) = $m;

		$moduleusage = (!$reload || $ncycles === -1) ? ($discharge / $duration) :
			($discharge / ($duration + $reloadtime / $ncycles));
		if($moduleusage == 0) $fraction = 1;
		else if($usage > 0) continue;
		else $fraction = min(1, -$usage / $moduleusage);

		$usage += $fraction * $moduleusage;
		$out[$key][1] += $fraction * (
			(!$reload || $ncycles === -1) ? ($amount / $duration) :
			($amount / ($duration + $reloadtime / $ncycles))
		);
	}

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

	foreach($out as $layer => $a) {
		if(!isset($multipliers[$layer])) continue;

		$out[$layer] = array(
			$out[$layer][0] / $multipliers[$layer],
			$out[$layer][1] / $multipliers[$layer],
		);
	}

	$shieldrechargerate = \Osmium\Dogma\get_ship_attribute($fit, 'shieldRechargeRate');
	$passiverate = ($shieldrechargerate > 0) ? 2.5 * $ehp['shield']['capacity'] / $shieldrechargerate : 0;

	$out['shield_passive'] = array(
		$passiverate / $multipliers['shield'],
		$passiverate / $multipliers['shield']
	);


	return $out;
}

/** @internal */
function get_remote_effects() {
	static $remoteeffects = array(
		'hull' => [ [ [ EFFECT_RemoteHullRepair, 'structureDamageAmount' ] ], 'HP' ],
		'armor' => [ [ [ EFFECT_TargetArmorRepair, 'armorDamageAmount' ] ], 'HP' ],
		'shield' => [ [ [ EFFECT_ShieldTransfer, 'shieldBonus' ] ], 'HP' ],
		'capacitor' => [ [ [ EFFECT_EnergyTransfer, 'powerTransferAmount' ] ], 'GJ' ],
		'neutralization' => [ [ [ EFFECT_EnergyDestabilizationNew, 'energyDestabilizationAmount' ] ], "GJ" ],
		'leech' => [ [ [ EFFECT_Leech, 'powerTransferAmount' ] ], "GJ" ],
	);

	return $remoteeffects;
}

/**
 * Get the incoming repair and capacitor numbers.
 */
function get_incoming(&$fit, $targetkey = 'local') {
	$incoming = array();

	$remotes = isset($fit['remote']) ? $fit['remote'] : array();
	$remotes['local'] = $fit;

	foreach(get_remote_effects() as $key => $effects) {
		$incoming[$key] = [ 0, $effects[1] ];

		foreach($effects[0] as $e) {
			list($eid, $aname) = $e;

			foreach($remotes as $rkey => $rfit) {
				foreach($rfit['modules'] as $type => $sub) {
					foreach($sub as $index => $m) {
						if(!isset($m['target']) || $m['target'] != $targetkey) continue;

						$ret = dogma_type_has_effect(
							$m['typeid'], \Osmium\Dogma\get_dogma_states()[$m['state']],
							$eid, $hasit
						);

						if($ret !== DOGMA_OK || $hasit !== true) continue;

						dogma_get_location_effect_attributes(
							$rfit['__dogma_context'],
							$loc = [ DOGMA_LOC_Module, "module_index" => $m['dogma_index'] ],
							$eid,
							$duration, $tracking, $discharge,
							$range, $falloff, $usagechance
						);

						$incoming[$key][0] += \Osmium\Dogma\get_remote_attribute(
							$fit, $rkey, $loc, $aname
						) / $duration;
					}
				}
			}
		}
	}

	return $incoming;
}

/**
 * Get the outgoing repair and capacitor numbers.
 */
function get_outgoing(&$fit) {
	$outgoing = array();

	foreach(get_remote_effects() as $key => $effects) {
		$outgoing[$key] = [ 0, $effects[1] ];

		foreach($effects[0] as $e) {
			list($eid, $aname) = $e;

			foreach($fit['modules'] as $type => $sub) {
				foreach($sub as $index => $m) {
					$ret = dogma_type_has_effect(
						$m['typeid'], \Osmium\Dogma\get_dogma_states()[$m['state']],
						$eid, $hasit
					);

					if($ret !== DOGMA_OK || $hasit !== true) continue;

					dogma_get_location_effect_attributes(
						$fit['__dogma_context'],
						$loc = [ DOGMA_LOC_Module, "module_index" => $m['dogma_index'] ],
						$eid,
						$duration, $tracking, $discharge,
						$range, $falloff, $usagechance
					);

					$outgoing[$key][0] += \Osmium\Dogma\get_module_attribute(
						$fit, $type, $index,
						$aname
					) / $duration;
				}
			}

			foreach($fit['drones'] as $typeid => $d) {
				if($d['quantityinspace'] <= 0) continue;

				$ret = dogma_type_has_effect(
					$typeid, DOGMA_STATE_Active,
					$eid, $hasit
				);

				if($ret !== DOGMA_OK || $hasit !== true) continue;

				dogma_get_location_effect_attributes(
					$fit['__dogma_context'],
					[ DOGMA_LOC_Drone, "drone_typeid" => $typeid ],
					$eid,
					$duration, $tracking, $discharge,
					$range, $falloff, $usagechance
				);

				$outgoing[$key][0] += $d['quantityinspace'] * \Osmium\Dogma\get_drone_attribute(
					$fit, $typeid, $aname
				) / $duration;
			}
		}
	}

	return $outgoing;
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
function get_damage_from_attack_effect(&$fit, $attackeffectid,
                                       $modulemultiplierattribute = null,
                                       $globalmultiplier = 1,
                                       $reload = false) {
	\Osmium\Dogma\auto_init($fit);

	$dps = 0;
	$alpha = 0;

	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $module) {
			$ret = dogma_type_has_effect(
				$module['typeid'],
				\Osmium\Dogma\get_dogma_states()[$module['state']],
				$attackeffectid, $hasit
			);
			if($ret !== DOGMA_OK || $hasit !== true) {
				continue;
			}

			$duration = 0;
			dogma_get_location_effect_attributes(
				$fit['__dogma_context'],
				[ DOGMA_LOC_Module, "module_index" => $module['dogma_index'] ],
				$attackeffectid,
				$duration, $tracking, $discharge, $range, $falloff, $usagechance
			);

			if($duration <= 1e-300) continue;

			$damage = 
				\Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'emDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'thermalDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'kineticDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'explosiveDamage');

			$multiplier = $modulemultiplierattribute === null ? 1 :
				\Osmium\Dogma\get_module_attribute($fit, $type, $index, $modulemultiplierattribute);

			$alpha += $multiplier * $damage;
			if(!$reload) {
				$dps += $multiplier * $damage / $duration;
				continue;
			}

			dogma_get_number_of_module_cycles_before_reload(
				$fit['__dogma_context'], $module['dogma_index'], $ncycles
			);

			if($ncycles === -1) {
				$dps += $multiplier * $damage / $duration;
				continue;
			}

			$reloadtime = \Osmium\Dogma\get_module_attribute($fit, $type, $index, ATT_ReloadTime);
			$dps += $multiplier * $damage / ($duration + $reloadtime / $ncycles);
		}
	}

	return array(1000 * $dps * $globalmultiplier, $alpha * $globalmultiplier);
}

/**
 * Get DPS/volley damage from active missile launchers.
 */
function get_damage_from_missiles(&$fit, $reload = false) {
	return get_damage_from_attack_effect(
		$fit, EFFECT_UseMissiles, null,
		\Osmium\Dogma\get_char_attribute($fit, 'missileDamageMultiplier'),
		$reload
	);
}

/**
 * Get DPS/volley damage from active turrets (projectile, hybrids and lasers).
 */
function get_damage_from_turrets(&$fit, $reload = false) {
	$projectiles = get_damage_from_attack_effect(
		$fit, EFFECT_ProjectileFired, 'damageMultiplier', 1.0, $reload
	);

	$lasers = get_damage_from_attack_effect(
		$fit, EFFECT_TargetAttack, 'damageMultiplier', 1.0, $reload
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
	\Osmium\Dogma\auto_init($fit);
	$dps = 0;

	foreach($fit['drones'] as $drone) {
		if($drone['quantityinspace'] == 0) continue;

		$ret = dogma_type_has_effect($drone['typeid'], DOGMA_STATE_Active, EFFECT_TargetAttack, $hasit);
		if($ret !== DOGMA_OK || $hasit !== true) {
			continue;
		}

		$duration = 0;
		dogma_get_location_effect_attributes(
			$fit['__dogma_context'],
			[ DOGMA_LOC_Drone, "drone_typeid" => $drone['typeid'] ],
			EFFECT_TargetAttack,
			$duration, $tracking, $discharge, $range, $falloff, $usagechance
		);

		if($duration <= 1e-300) continue;

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
	\Osmium\Dogma\auto_init($fit);

	$attributes = array();
	$typeid = $fit['modules'][$type][$index]['typeid'];
	$state = $fit['modules'][$type][$index]['state'];
	$mdindex = $fit['modules'][$type][$index]['dogma_index'];

	if($state < STATE_ONLINE) return array();

	$trackings = array();
	$ranges = array();
	$falloffs = array();

	$i = 0;
	while(dogma_get_nth_type_effect_with_attributes($typeid, $i, $effect) === DOGMA_OK) {
		++$i;

		$tra = $ran = $fal = 0;

		/* XXX check effect category? */
		dogma_get_location_effect_attributes(
			$fit['__dogma_context'], [ DOGMA_LOC_Module, 'module_index' => $mdindex ], $effect,
			$dur, $tra, $dis, $ran, $fal, $fua
		);

		/* Sometimes zero values are not exactly zero but a very small
		 * value, due to floating point precision loss. */
		if($tra > 1e-300) $trackings[] = $tra;
		if($ran > 1e-300) $ranges[] = $ran;
		if($fal > 1e-300) $falloffs[] = $fal;
	}

	if($trackings !== array()) {
		$attributes['trackingspeed'] = min($trackings);
	}
	if($ranges !== array()) {
		$attributes['range'] = min($ranges);
	}
	if($trackings !== array()) {
		$attributes['falloff'] = min($falloffs);
	}

	if(isset($fit['charges'][$type][$index])
	   && dogma_type_has_effect($typeid, \Osmium\Dogma\get_dogma_states()[$state],
	                            EFFECT_UseMissiles, $hasit) === DOGMA_OK
	   && $hasit) {
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
	}

	return $attributes;
}

/**
 * Get the estimated price of a fit, using the in-game
 * estimates. Items whose price cannot be determined will be added to
 * $missing.
 */
function get_estimated_price(&$fit, array &$missing) {
	$types = array();

	if(isset($fit['ship']['typeid'])) {
		$types['ship'][$fit['ship']['typeid']] = 1;
	}

	foreach($fit['modules'] as $a) {
		foreach($a as $m) {
			if(isset($types['fitting'][$m['typeid']])) {
				++$types['fitting'][$m['typeid']];
			} else {
				$types['fitting'][$m['typeid']] = 1;
			}
		}
	}
	foreach($fit['charges'] as $a) {
		foreach($a as $c) {
			if(isset($types['fitting'][$c['typeid']])) {
				++$types['fitting'][$c['typeid']];
			} else {
				$types['fitting'][$c['typeid']] = 1;
			}
		}
	}
	foreach($fit['drones'] as $d) {
		$qty = $d['quantityinbay'] + $d['quantityinspace'];

		if(isset($types[$d['typeid']])) {
			$types['fitting'][$d['typeid']] += $qty;
		} else {
			$types['fitting'][$d['typeid']] = $qty;
		}
	}
	foreach($fit['implants'] as $i) {
		$types['implants'][$i['typeid']] = 1;
	}

	$totals = array();

	foreach($types as $section => $t) {
		$total = 0;
		$hassomemissing = false;

		foreach($t as $typeid => $qty) {
			$p = get_average_market_price($typeid);
			if($p !== false) {
				$total += $qty * $p;
			} else {
				$hassomemissing = true;
				$missing[] = $typeid;
			}
		}

		$totals[$section] = ($total === 0 && $hassomemissing) ? 'N/A' : $total;
	}

	return $totals;
}

/**
 * Get the mining yield of the current fit, in m³ / ms (cubic meters
 * per millisecond).
 */
function get_mining_yield(&$fit) {
	\Osmium\Dogma\auto_init($fit);
	$total = 0;

	foreach($fit['modules'] as $type => $a) {
		foreach($a as $index => $m) {
			$typeid = $m['typeid'];

			$ret = dogma_type_has_effect(
				$m['typeid'],
				\Osmium\Dogma\get_dogma_states()[$m['state']],
				EFFECT_MiningLaser,
				$hasit
			);
			if($ret !== DOGMA_OK || $hasit !== true) continue;

		    dogma_get_location_effect_attributes(
			    $fit['__dogma_context'],
			    [ DOGMA_LOC_Module, "module_index" => $m['dogma_index'] ],
			    EFFECT_MiningLaser,
			    $duration, $tra, $dis, $ran, $fal, $fuc
		    );

		    if($duration <= 1e-300) continue;

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
