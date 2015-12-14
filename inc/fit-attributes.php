<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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
 * Get capacitor stability.
 *
 * @returns an array with the following keys: capacity, stable, delta,
 * and (depending on stable value) stable_fraction or depletion_time.
 */
function get_capacitor_stability(&$fit, $reload = true) {
	\Osmium\Dogma\auto_init($fit);
	dogma_get_capacitor_all($fit['__dogma_context'], $reload, $result);

	return $result[dogma_get_hashcode($fit['__dogma_context'])];
}

/**
 * Get capacitor stability of this fit and all remote fits.
 */
function get_all_capacitors(&$fit, $reload = true) {
	\Osmium\Dogma\auto_init($fit);

	$remotes = array(
		'local' => $fit
	);

	if(isset($fit['remote'])) {
		foreach($fit['remote'] as $key => $rf) {
			$remotes[$key] = $rf;
		}
	}

	$capacitors = array();
	$hashcodes = array();

	foreach($remotes as $key => $rf) {
		$hashcodes[dogma_get_hashcode($rf['__dogma_context'])] = $key;
	}

	foreach($remotes as $key => $rf) {
		if(isset($capacitors[$key])) continue;

		dogma_get_capacitor_all(
			$rf['__dogma_context'],
			$reload,
			$result
		);

		foreach($result as $hashcode => $c) {
			$capacitors[$hashcodes[$hashcode]] = $c;
		}
	}

	return $capacitors;
}

/**
 * Get maximum/average/minimum effective hitpoints and hull, armor and
 * shield resonances (1 - resistances).
 *
 * @returns array(ehp => {min, avg, max}, {shield, armor, hull} =>
 * {capacity, resonance => {em, thermal, kinetic, explosive}}).
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

	\Osmium\Dogma\auto_init($fit);

	foreach($layers as $a) {
		list($name, $attributename, $resistprefix) = $a;

		foreach([ 'em', 'thermal', 'kinetic', 'explosive' ] as $dtype) {
			
			$out[$name]['resonance'][$dtype] = min(
				1,
				max(
					0, 
					\Osmium\Dogma\get_ship_attribute(
						$fit, lcfirst($resistprefix.ucfirst($dtype).'DamageResonance')
					)
				)
			);
		}
		
		$out[$name]['capacity'] = \Osmium\Dogma\get_ship_attribute($fit, $attributename);

		$out['ehp']['min'] += $out[$name]['capacity'] / max($out[$name]['resonance']);
		$out['ehp']['max'] += $out[$name]['capacity'] / min($out[$name]['resonance']);

		$avgresonance = 0;
		foreach($fit['damageprofile']['damages'] as $type => $dmg) {
			$avgresonance += $dmg * $out[$name]['resonance'][$type];
		}

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
 * @param $capdelta capacitor usage, in GJ/ms
 *
 * @returns array(repair_type => array(reinforced, sustained))
 */
function get_tank(&$fit, $ehp, $capdelta, $reload = false) {
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

	$usage = $capdelta;

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

	$multipliers = array();
	foreach(array('shield', 'armor', 'hull') as $ltype) {
		$resonances = $ehp[$ltype]['resonance'];
		$multipliers[$ltype] = 0;

		foreach($fit['damageprofile']['damages'] as $type => $dmg) {
			$multipliers[$ltype] += $dmg * $resonances[$type];
		}
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
		'hull' => [ [ [ EFFECT_RemoteHullRepairFalloff, 'structureDamageAmount' ] ], 'HP' ],
		'armor' => [ [ [ EFFECT_RemoteArmorRepairFalloff, 'armorDamageAmount' ] ], 'HP' ],
		'shield' => [ [ [ EFFECT_RemoteShieldTransferFalloff, 'shieldBonus' ] ], 'HP' ],
		'capacitor' => [ [ [ EFFECT_EnergyTransfer, 'powerTransferAmount' ] ], 'GJ' ],
		'neutralization' => [ [ [ EFFECT_EnergyNeutralizerFalloff, 'energyDestabilizationAmount' ] ], "GJ" ],
		'leech' => [ [ [ EFFECT_EnergyNosferatuFalloff, 'powerTransferAmount' ] ], "GJ" ],
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

/** @internal */
function get_damage_from_generic_damagetype(&$fit, array $ia, $damagetype, $reload) {
	$dps = 0;
	$volley = 0;

	\Osmium\Dogma\auto_init($fit);

	foreach($ia as $m) {
		$loc = $m['location'];
		$a = $m['raw'];

		if(!isset($a['damagetype']) || $a['damagetype'] !== $damagetype) continue;

		$v = $a['damage'];
		$d = $a['duration'];

		if($reload && $loc[0] === 'module') {
			dogma_get_number_of_module_cycles_before_reload(
				$fit['__dogma_context'], $fit['modules'][$loc[1]][$loc[2]]['dogma_index'], $ncycles
			);

			if($ncycles !== -1) {
				$reloadtime = \Osmium\Dogma\get_module_attribute($fit, $loc[1], $loc[2], ATT_ReloadTime);
				$d += $reloadtime / $ncycles;
			}
		}

		$dps += $v / $d;
		$volley += $v;
	}

	return array(1000 * $dps, $volley);
}

/**
 * Get DPS/volley damage from active missile launchers.
 */
function get_damage_from_missiles(&$fit, array $ia, $reload = false) {
	return get_damage_from_generic_damagetype($fit, $ia, 'missile', $reload);
}

/**
 * Get DPS/volley damage from active turrets (projectile, hybrids and lasers).
 */
function get_damage_from_turrets(&$fit, array $ia, $reload = false) {
	return get_damage_from_generic_damagetype($fit, $ia, 'turret', $reload);
}

/**
 * Get DPS/volley damage from fitted (and active) smartbombs.
 */
function get_damage_from_smartbombs(&$fit, array $ia) {
	return get_damage_from_generic_damagetype($fit, $ia, 'smartbomb', false);
};

/**
 * Get DPS/volley damage from active drones (drones "in space").
 */
function get_damage_from_drones(&$fit, array $ia) {
	$cd = get_damage_from_generic_damagetype($fit, $ia, 'combatdrone', false);
	$f = get_damage_from_generic_damagetype($fit, $ia, 'fighter', false);
	$fb = get_damage_from_generic_damagetype($fit, $ia, 'fighterbomber', false);

	return [
		$cd[0] + $f[0] + $fb[0],
		$cd[1] + $f[1] + $fb[1],
	];
}

/**
 * Get DPS/volley damage from a lodaout, including all damage sources.
 */
function get_damage_all(&$fit, array $ia) {
	$d = [
		get_damage_from_turrets($fit, $ia),
		get_damage_from_missiles($fit, $ia),
		get_damage_from_smartbombs($fit, $ia),
		get_damage_from_drones($fit, $ia),
	];

	$dps = 0;
	$volley = 0;

	foreach($d as $dt) {
		$dps += $dt[0];
		$volley += $dt[1];
	}

	return [ $dps, $volley ];
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
	$dstate = \Osmium\Dogma\get_dogma_states()[$state];
	$mdindex = $fit['modules'][$type][$index]['dogma_index'];

	$trackings = array();
	$ranges = array();
	$falloffs = array();

	$i = 0;
	while(dogma_get_nth_type_effect_with_attributes($typeid, $i, $effect) === DOGMA_OK) {
		++$i;

		$dur = $tra = $ran = $fal = 0;

		dogma_get_location_effect_attributes(
			$fit['__dogma_context'], [ DOGMA_LOC_Module, 'module_index' => $mdindex ], $effect,
			$dur, $tra, $dis, $ran, $fal, $fua
		);

		if(dogma_type_has_effect($typeid, $dstate, $effect, $hasit) !== DOGMA_OK || $hasit !== true) {
			/* Effect is not currently active */
			continue;
		}

		/* Sometimes zero values are not exactly zero but a very small
		 * value, due to floating point precision loss. */
		if($tra > 1e-300) $trackings[] = $tra;
		if($ran > 1e-300) $ranges[] = $ran;
		if($fal > 1e-300) $falloffs[] = $fal;

		if($effect === EFFECT_UseMissiles) {
			if(!isset($fit['charges'][$type][$index])) continue;
			if($dur < 1e-300) continue;

			$flighttime = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'explosionDelay') / 1000;
			$velocity = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'maxVelocity');
			$mass = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'mass');
			$agility = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'agility');

			$damage = \Osmium\Dogma\get_char_attribute($fit, 'missileDamageMultiplier') * (
				\Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'emDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'thermalDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'kineticDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'explosiveDamage')
			);

			if($damage < 1e-300) continue;

			if($mass != 0 && $agility != 0) {
				/* Source: http://wiki.eveonline.com/en/wiki/Acceleration */
				/* Integrate the velocity of the missile from 0 to flighttime: */
				$K = -1000000 / ($mass * $agility);
				$attributes['maxrange'] = $velocity * ($flighttime + (1 - exp($K * $flighttime)) / $K);
			} else {
				/* Zero mass or zero agility, for example defender missiles */
				$attributes['maxrange'] = $velocity * $flighttime;
			}

			$attributes['damagetype'] = 'missile';
			$attributes['duration'] = $dur;
			$attributes['damage'] = $damage;
			$attributes['expvelocity'] = \Osmium\Dogma\get_charge_attribute(
				$fit, $type, $index, 'aoeVelocity'
			);
			$attributes['expradius'] = \Osmium\Dogma\get_charge_attribute(
				$fit, $type, $index, 'aoeCloudSize'
			);
			$attributes['drf'] = \Osmium\Dogma\get_charge_attribute(
				$fit, $type, $index, 'aoeDamageReductionFactor'
			);
			$attributes['drs'] = \Osmium\Dogma\get_charge_attribute(
				$fit, $type, $index, 'aoeDamageReductionSensitivity'
			);

			if(\Osmium\Dogma\get_module_attribute($fit, $type, $index, 'disallowRepeatingActivation')) {
				$attributes['duration'] += \Osmium\Dogma\get_module_attribute(
					$fit, $type, $index, 'moduleReactivationDelay'
				);
			}

		} else if($effect === EFFECT_ProjectileFired || $effect === EFFECT_TargetAttack) {
			if(!isset($fit['charges'][$type][$index])) continue;
			if($dur < 1e-300) continue;

			$attributes['damagetype'] = 'turret';
			$attributes['duration'] = $dur;
			$attributes['damage'] = \Osmium\Dogma\get_module_attribute($fit, $type, $index, 'damageMultiplier') * (
				\Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'emDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'thermalDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'kineticDamage')
				+ \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'explosiveDamage')
			);
			$attributes['sigradius'] = \Osmium\Dogma\get_module_attribute(
				$fit, $type, $index, 'optimalSigRadius'
			);
		} else if($effect === EFFECT_EMPWave) {
			$attributes['damagetype'] = 'smartbomb';
			$attributes['duration'] = $dur;
			$attributes['damage'] = \Osmium\Dogma\get_module_attribute($fit, $type, $index, 'emDamage')
				+ \Osmium\Dogma\get_module_attribute($fit, $type, $index, 'thermalDamage')
				+ \Osmium\Dogma\get_module_attribute($fit, $type, $index, 'kineticDamage')
				+ \Osmium\Dogma\get_module_attribute($fit, $type, $index, 'explosiveDamage');
			$attributes['maxrange'] = \Osmium\Dogma\get_module_attribute(
				$fit, $type, $index, 'empFieldRange'
			);
		}
	}

	if($trackings !== array()) {
		$attributes['trackingspeed'] = min($trackings);
	}
	if($ranges !== array()) {
		$attributes['range'] = min($ranges);
	}
	if($falloffs !== array()) {
		$attributes['falloff'] = min($falloffs);
	}

	return $attributes;
}

/** @see get_module_interesting_attributes */
function get_drone_interesting_attributes(&$fit, $typeid) {
	\Osmium\Dogma\auto_init($fit);

	$groupid = (int)get_groupid($typeid);
	$attributes = array();

	$trackings = array();
	$ranges = array();
	$falloffs = array();

	$i = 0;
	while(dogma_get_nth_type_effect_with_attributes($typeid, $i, $effect) === DOGMA_OK) {
		++$i;

		$dur = $tra = $ran = $fal = 0;

		dogma_get_location_effect_attributes(
			$fit['__dogma_context'], [ DOGMA_LOC_Drone, 'drone_typeid' => $typeid ], $effect,
			$dur, $tra, $dis, $ran, $fal, $fua
		);

		if($tra > 1e-300) $trackings[] = $tra;
		if($ran > 1e-300) $ranges[] = $ran;
		if($fal > 1e-300) $falloffs[] = $fal;

		if($dur < 1e-300) continue;

		if($effect === EFFECT_TargetAttack) {
			$dmg =  (
				\Osmium\Dogma\get_drone_attribute($fit, $typeid, 'emDamage')
				+ \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'thermalDamage')
				+ \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'kineticDamage')
				+ \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'explosiveDamage')
			) * \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'damageMultiplier');

			$dmg *= $fit['drones'][$typeid]['quantityinspace'];
			if($dmg < 1e-300) continue;

			$attributes['damagetype'] = $groupid === GROUP_FighterDrone ? 'fighter' : 'combatdrone';
			$attributes['damage'] = $dmg;
			$attributes['duration'] = $dur;
			$attributes['sigradius'] = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'optimalSigRadius');
			$attributes['flyrange'] = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'entityFlyRange');
			$attributes['cruisespeed'] = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'entityCruiseSpeed');
			$attributes['maxvelocity'] = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'maxVelocity');
		} else if($effect === EFFECT_FighterMissile && $groupid === GROUP_FighterBomber) {
			$dmg =  (
				\Osmium\Dogma\get_drone_attribute($fit, $typeid, 'emDamage')
				+ \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'thermalDamage')
				+ \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'kineticDamage')
				+ \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'explosiveDamage')
			) * \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'damageMultiplier');

			$dmg *= $fit['drones'][$typeid]['quantityinspace'];
			if($dmg < 1e-300) continue;

			$attributes['damagetype'] = 'fighterbomber';
			$attributes['damage'] = $dmg;
			$attributes['duration'] = $dur;
			$attributes['expvelocity'] = \Osmium\Dogma\get_drone_attribute(
				$fit, $typeid, 'aoeVelocity'
			);
			$attributes['expradius'] = \Osmium\Dogma\get_drone_attribute(
				$fit, $typeid, 'aoeCloudSize'
			);
			$attributes['drf'] = \Osmium\Dogma\get_drone_attribute(
				$fit, $typeid, 'aoeDamageReductionFactor'
			);
			$attributes['drs'] = \Osmium\Dogma\get_drone_attribute(
				$fit, $typeid, 'aoeDamageReductionSensitivity'
			);
			$attributes['flyrange'] = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'entityFlyRange');
			$attributes['cruisespeed'] = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'entityCruiseSpeed');
			$attributes['maxvelocity'] = \Osmium\Dogma\get_drone_attribute($fit, $typeid, 'maxVelocity');
			
		}
	}

	if($trackings !== array()) {
		$attributes['trackingspeed'] = min($trackings);
	}
	if($ranges !== array()) {
		$attributes['range'] = min($ranges);
	}
	if($falloffs !== array()) {
		$attributes['falloff'] = min($falloffs);
	}

	if($attributes !== array() && $groupid !== GROUP_FighterDrone && $groupid !== GROUP_FighterBomber) {
		$attributes['controlrange'] = \Osmium\Dogma\get_char_attribute($fit, 'droneControlDistance');
	}

	return $attributes;
}

/**
 * Generate formatted interesting attributes for all the fitted things
 * in the fit.
 *
 * @returns an array of array(
 * location => array(locationtype, …),
 * fshort => (short formatted attributes),
 * flong => (long formatted attributes),
 * raw => array(attributename => attributevalue…)
 * )
 */
function get_interesting_attributes(&$fit) {
	$attrs = array();

	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $m) {
			$a = \Osmium\Fit\get_module_interesting_attributes($fit, $type, $index);
			if($a === array()) continue;

			$attrs[] = array(
				'location' => [ 'module', $type, $index ],
				'raw' => $a,
			);
		}
	}

	foreach($fit['drones'] as $typeid => $d) {
		if($d['quantityinspace'] > 0) {
			$a = \Osmium\Fit\get_drone_interesting_attributes($fit, $typeid);
			if($a === array()) continue;

			$attrs[] = array(
				'location' => [ 'drone', $typeid ],
				'raw' => $a,
			);
		}
	}

	foreach($attrs as &$a) {
		$fshort = \Osmium\Chrome\format_short_range($a['raw']);
		$flong = \Osmium\Chrome\format_long_range($a['raw']);

		if($fshort) $a['fshort'] = $fshort;
		if($flong) $a['flong'] = $flong;
	}

	return $attrs;
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
