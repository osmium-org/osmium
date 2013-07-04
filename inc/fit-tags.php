<?php
/* Osmium
 * Copyright (C) 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

function sanitize_tags(&$fit, &$errors = null, $interactive = false) {
	$min = (int)\Osmium\get_ini_setting('min_tags');
	$max = (int)\Osmium\get_ini_setting('max_tags');
	$aliases = (array)\Osmium\get_ini_setting('aliases');

	if(!isset($fit['metadata']['tags'])) {
		$fit['metadata']['tags'] = array();
	}

	$tags =& $fit['metadata']['tags'];
	$tags = array_map(function($tag) use($aliases) {
			if(class_exists('Normalizer')) {
				$tag = \Normalizer::normalize($tag, \Normalizer::FORM_KD);
			}
			if(function_exists('iconv')) {
				$tag = iconv("UTF-8", 'US-ASCII//TRANSLIT//IGNORE', $tag);
			}

			$tag = strtolower(str_replace('_', '-', $tag));
			$tag = preg_replace('%[^a-z0-9-]+%', '', $tag);
			if(isset($aliases[$tag])) {
				$tag = $aliases[$tag];
			}

			return $tag;
		}, $tags);

	$tags = array_filter($tags, function($tag) { return $tag !== ''; });
	$tags = array_unique($tags);

	if(count($tags) < $min) {
		$errors[] = 'Not enough tags, must have at least '.$min.'.';
	} else if(count($tags) > $max) {
		if($interactive) {
			$errors[] = 'Too many tags, must have at most '.$max.'.';
		} else {
			$tags = array_slice($tags, 0, $max);
			$errors[] = 'Too many tags, only took the '.$max.' first in the list';
		}
	}
}

function get_recommended_tags($fit) {
	/* Count the number of modules by group */
	$groups = array();

/* XXX
	foreach($fit['modules'] as $a) {
		foreach($a as $m) {
			$groupid = $fit['cache'][$m['typeid']]['groupid'];
			if(!isset($groups[$groupid])) $groups[$groupid] = 0;

			++$groups[$groupid];
		}
	}
*/

	static $groupgroups = array(
		'shield' => array(
			77, /* Shield hardeners */ 
			295, /* Shield resistance amplifiers */
			774, /* Shield rigs */
		),
		'shieldactive' => array(
			40, /* Shield boosters */
			338, /* Shield boost amplifiers */
			1156, /* Fueled shield boosters */
		),
		'shieldpassive' => array(
			39, /* Shield rechargers */
			57, /* Shield power relays */
			770, /* Shield Flux Coil */
		),
		'shieldbuffer' => array(
			38, /* Shield extenders */
		),
		'armor' => array(
			773, /* Armor rigs */
			326, /* Armor plating energized (EANMs etc.) */
			98, /* Armor coating (ANPs etc.) */
			328, /* Armor hardener */
			1150, /* Reactive armor hardeners */
		),
		'armoractive' => array(
			62, /* Armor repairers */
		),
		'armorbuffer' => array(
			329, /* Armor plates */
		),
		'guns' => array(
			55, /* Projectile weapons */
			74, /* Hybrid weapons */
			53, /* Energy weapons */
			211, /* Tracking enhancers */
			213, /* Tracking computers */
			59, /* Gyrostabilizers */
			302, /* Magnetic field stabilizers */
			205, /* Heat sinks */
			777, /* Projectile weapon rig */
			776, /* Hybrid weapon rig */
			775, /* Energy weapon rig */
		),
		'missiles' => array(
			511, /* Rapid launchers */
			509, /* Light launchers */
			507, /* Rocket launchers */
			510, /* Heavy launchers */
			771, /* Heavy assault launchers */
			506, /* Cruise launchers */
			508, /* Torpedo launchers */
			524, /* Citadel launchers */
			779, /* Launcher rig */
			367, /* Ballistic control systems */
		),
		'drones' => array(
			647, /* Drone link augmentors */
			407, /* Drone control units */
			645, /* Drone damage modules */
			646, /* Drone tracking modules */
			778, /* Drone rig */
		),
	);

	$groupc = array();
	foreach($groupgroups as $name => $groupids) {
		$groupc[$name] = 0;
		foreach($groupids as $gid) {
			$groupc[$name] += isset($groups[$gid]) ? $groups[$gid] : 0;
		}
	}

	$recommended = array();

	if($groupc['shield'] + $groupc['shieldactive'] + $groupc['shieldpassive'] + $groupc['shieldbuffer'] >= 2) {
		$recommended[] = 'shield-tank';
	}
	if($groupc['armor'] + $groupc['armoractive'] + $groupc['armorbuffer'] >= 2) {
		$recommended[] = 'armor-tank';
	}
	if($groupc['shieldactive'] + $groupc['armoractive'] >= 1) {
		$recommended[] = 'active-tank';
	}
	if($groupc['shieldbuffer'] + $groupc['armorbuffer'] >= 1) {
		$recommended[] = 'buffer-tank';
	}
	if($groupc['shieldpassive'] >= 1) {
		$recommended[] = 'passive-tank';
	}
	if($groupc['guns'] >= 3) {
		$recommended[] = 'gun-boat';
	}
	if($groupc['missiles'] >= 3) {
		$recommended[] = 'missile-boat';
	}
	if($groupc['drones'] >= 2) {
		$recommended[] = 'drone-boat';
	}

	return $recommended;
}
