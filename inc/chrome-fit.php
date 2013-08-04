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

namespace Osmium\Chrome;

const USE_RELOAD_TIME_FOR_CAPACITOR = 1;
const USE_RELOAD_TIME_FOR_DPS = 2;
const USE_RELOAD_TIME_FOR_TANK = 4;



function print_formatted_attribute_category($identifier, $title, $titledata, $titleclass, $contents) {
	if($titleclass) $titleclass = " class='$titleclass'";
	echo "<section id='$identifier'>\n";
	echo "<h4$titleclass>$title <small>$titledata</small></h4>\n";
	echo "<div>\n$contents</div>\n";
	echo "</section>\n";
}

function print_formatted_engineering(&$fit, $relative, $capacitor) {
	ob_start();
	$overall = '';

	$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlotsLeft');
	$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlots');
	$formatted = format_used(
		$slotsTotal - $slotsLeft, $slotsTotal, 0,
		F_USED_SHOW_ABSOLUTE
	);
	$overturrets = $slotsLeft < 0 ? 'overflow' : '';
	echo "<p class='{$overturrets}'>"
		.sprite($relative, 'Turret hardpoints', 0, 0, 64, 64, 32)
		."<span id='turrethardpoints'>".$formatted."</span></p>\n";

	$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlotsLeft');
	$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlots');
	$formatted = format_used(
		$slotsTotal - $slotsLeft, $slotsTotal, 0,
		F_USED_SHOW_ABSOLUTE
	);
	$overlaunchers = $slotsLeft < 0 ? 'overflow' : '';
	echo "<p class='{$overlaunchers}'>"
		.sprite($relative, 'Launcher hardpoints', 0, 1, 64, 64, 32)
		."<span id='launcherhardpoints'>".$formatted."</span></p>\n";

	list($captime, $capdelta) = \Osmium\Chrome\format_capacitor($capacitor);
	echo "<p>"
		.sprite($relative, 'Capacitor', 2, 0, 64, 64, 32)
		."<span id='capacitor'><span title='Capacitor stability percentage / depletion time (estimated)'>".$captime."</span><br /><span title='Capacitor delta at peak recharge rate'>".$capdelta."</span></span></p>\n";

	$cpuUsed = \Osmium\Dogma\get_ship_attribute($fit, 'cpuLoad');
	$cpuTotal = \Osmium\Dogma\get_ship_attribute($fit, 'cpuOutput');
	$formatted = format_used(
		$cpuUsed, $cpuTotal, 2,
		F_USED_SHOW_DIFFERENCE | F_USED_SHOW_PERCENTAGE | F_USED_SHOW_PROGRESS_BAR
	);
	$overcpu = $cpuUsed > $cpuTotal ? 'overflow' : '';
	echo "<p class='{$overcpu}'>"
		.sprite($relative, 'CPU', 1, 0, 64, 64, 32)
		."<span id='cpu'>".$formatted."</span></p>\n";

	$powerUsed = \Osmium\Dogma\get_ship_attribute($fit, 'powerLoad');
	$powerTotal = \Osmium\Dogma\get_ship_attribute($fit, 'powerOutput');
	$formatted = format_used(
		$powerUsed, $powerTotal, 2,
		F_USED_SHOW_DIFFERENCE | F_USED_SHOW_PERCENTAGE | F_USED_SHOW_PROGRESS_BAR
	);
	$overpower = $powerUsed > $powerTotal ? 'overflow' : '';
	echo "<p class='{$overpower}'>"
		.sprite($relative, 'Powergrid', 1, 1, 64, 64, 32)
		."<span id='power'>".$formatted."</span></p>\n";

	$upgradeCapacityUsed = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeLoad');
	$upgradeCapacityTotal = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeCapacity');
	$formatted = format_used(
		$upgradeCapacityUsed, $upgradeCapacityTotal, 2,
		F_USED_SHOW_DIFFERENCE | F_USED_SHOW_PERCENTAGE | F_USED_SHOW_PROGRESS_BAR
	);
	$overupgrade = $upgradeCapacityUsed > $upgradeCapacityTotal ? 'overflow' : '';
	echo "<p class='{$overupgrade}'>"
		.sprite($relative, 'Calibration (upgrade capacity)', 1, 2, 64, 64, 32)
		."<span id='upgradecapacity'>".$formatted."</span></p>\n";

	print_formatted_attribute_category(
		'engineering', 'Engineering',
		"<span title='Capacitor stability percentage / depletion time (estimated)'>".$captime.' </span>',
		$overturrets || $overlaunchers || $overcpu || $overpower || $overupgrade ? 'overflow' : '',
		ob_get_clean()
	);
}

function print_formatted_offense(&$fit, $relative, $reload = false) {
	ob_start();

	list($turretdps, $turretalpha) = \Osmium\Fit\get_damage_from_turrets($fit, $reload);
	echo "<p>"
		.sprite($relative, 'Turret damage', 0, 0, 64, 64, 32)
		."<span><span title='Turret volley (alpha)'>"
		.format_number($turretalpha)."</span><br /><span title='Turret DPS'>"
		.format_number($turretdps)."</span></span></p>\n";

	list($missiledps, $missilealpha) = \Osmium\Fit\get_damage_from_missiles($fit, $reload);
	echo "<p>"
		.sprite($relative, 'Missile damage', 0, 1, 64, 64, 32)
		."<span><span title='Missile volley (alpha)'>"
		.format_number($missilealpha)."</span><br /><span title='Missile DPS'>"
		.format_number($missiledps)."</span></span></p>\n";

	$dronedps = \Osmium\Fit\get_damage_from_drones($fit);
	echo "<p>"
		.sprite($relative, 'Drone damage', 0, 2, 64, 64, 32)
		."<span title='Drone DPS'>".format_number($dronedps)."</span></p>\n";	

	$dps = format_number($missiledps + $turretdps + $dronedps, -1);
	print_formatted_attribute_category('offense', 'Offense', "<span title='Total damage per second'>".$dps." dps</span>", '', ob_get_clean());
}

function print_formatted_defense(&$fit, $relative, $ehp, $cap, $dmgprofile, $reload = false) {
	ob_start();

	$resists = array();
	foreach($ehp as $k => $a) {
		if($k === 'ehp') continue;
		$resists[$k][] = "<td class='capacity'>".\Osmium\Chrome\format_number($a['capacity'])."</td>\n";
		$resists[$k][] = "<td class='emresist'>"
			.\Osmium\Chrome\format_resonance($a['resonance']['em'])."</td>\n";
		$resists[$k][] = "<td class='thermalresist'>"
			.\Osmium\Chrome\format_resonance($a['resonance']['thermal'])."</td>\n";
		$resists[$k][] = "<td class='kineticresist'>"
			.\Osmium\Chrome\format_resonance($a['resonance']['kinetic'])."</td>\n";
		$resists[$k][] = "<td class='explosiveresist'>"
			.\Osmium\Chrome\format_resonance($a['resonance']['explosive'])."</td>\n";
	}

	$mehp = format_number($ehp['ehp']['min']);
	$aehp = format_number($ehp['ehp']['avg']);
	$Mehp = format_number($ehp['ehp']['max']);

	echo "<table id='resists'>\n<thead>\n<tr>\n";
	echo "<th><abbr title='Effective Hitpoints'>EHP</abbr></th>\n";
	echo "<th id='ehp'>\n";
	echo "<span title='EHP in the worst case (dealing damage with the lowest resistance)'>≥".$mehp."</span><br />\n";
	echo "<strong title='EHP using the selected damage profile'>".$aehp."</strong><br />\n";
	echo "<span title='EHP in the best case (dealing damage with the highest resistance)'>≤".$Mehp."</span></th>\n";
	echo "<td>".sprite($relative, 'EM Resistance', 7, 29, 32)."</td>\n";
	echo "<td>".sprite($relative, 'Thermal Resistance', 4, 29, 32)."</td>\n";
	echo "<td>".sprite($relative, 'Kinetic Resistance', 5, 29, 32)."</td>\n";
	echo "<td>".sprite($relative, 'Explosive Resistance', 6, 29, 32)."</td>\n";
	echo "</tr>\n</thead>\n<tfoot></tfoot>\n<tbody>\n<tr id='shield'>\n";
	echo "<th>".sprite($relative, 'Shield hitpoints', 3, 0, 64, 64, 32)."</th>\n";
	echo implode('', $resists['shield']);
	echo"</tr>\n<tr id='armor'>\n";
	echo "<th>".sprite($relative, 'Armor hitpoints', 3, 1, 64, 64, 32)."</th>\n";
	echo implode('', $resists['armor']);
	echo"</tr>\n<tr id='hull'>\n";
	echo "<th>".sprite($relative, 'Structure (hull) hitpoints', 3, 2, 64, 64, 32)."</th>\n";
	echo implode('', $resists['hull']);
	echo "</tr>\n</tbody>\n</table>\n";

	$layers = array(
		'hull' => array('Hull repairs', 'Hull EHP repaired per second', true, 4, 3),
		'armor' => array('Armor repairs', 'Armor EHP repaired per second', true, 4, 2),
		'shield' => array('Shield boost', 'Shield EHP boost per second', true, 4, 1),
		'shield_passive' => array('Passive shield recharge', 'Peak shield EHP recharged per second', false, 4, 0),
		);

	$rimg = sprite($relative, 'Reinforced tank', 2, 2, 64, 64, 16);
	$simg = sprite($relative, 'Sustained tank', 2, 1, 64, 64, 16);

	$rtotal = 0;
	$stotal = 0;
	foreach(\Osmium\Fit\get_tank($fit, $ehp, $cap, $dmgprofile, $reload) as $lname => $a) {
		list($reinforced, $sustained) = $a;
		if($reinforced == 0) continue;

		list($alt, $title, $showboth, $posx, $posy) = $layers[$lname];

		echo "<p>".sprite($relative, $title, $posx, $posy, 64, 64, 32);
		if($showboth) {
			echo "<span>";
			echo $rimg."<span title='$title'>".format_number(1000 * $reinforced)."</span><br />";
			echo $simg."<span title='$title'>".format_number(1000 * $sustained)."</span>";
			echo "</span>";
		} else {
			echo "<span title='$title'>".format_number(1000 * $reinforced)."</span>";
		}
		echo "</p>\n";

		$rtotal += $reinforced;
		$stotal += $sustained;
	}

	$tehp = format_number($ehp['ehp']['avg'], -2);
	$subtitles[] = '<span title="Average EHP">'.$tehp.' ehp</span>';

	if($rtotal > 0) {
		$rtotal = format_number(1000 * $rtotal, -1);
		$subtitles[] = '<span title="Combined reinforced tank">'.$rtotal.' dps</span>';
	}
	if($stotal > 0) {
		$stotal = format_number(1000 * $stotal, -1);
		if($stotal !== $rtotal) {
			$subtitles[] = '<span title="Combined sustained tank">'.$stotal.' dps</span>';
		}
	}
	
	print_formatted_attribute_category('defense', 'Defense <span class="pname">Uniform</span>', implode(' – ', $subtitles), '', ob_get_clean());
}

function print_formatted_navigation(&$fit, $relative) {
	ob_start();

	$maxvelocity = round(\Osmium\Dogma\get_ship_attribute($fit, 'maxVelocity'));
	$agility = \Osmium\Dogma\get_ship_attribute($fit, 'agility');
	$aligntime = -log(0.25) * \Osmium\Dogma\get_ship_attribute($fit, 'mass') * $agility / 1000000;
	$warpspeed = \Osmium\Dogma\get_ship_attribute($fit, 'warpSpeedMultiplier') * 3.0;
	$warpstrength = -\Osmium\Dogma\get_ship_attribute($fit, 'warpScrambleStatus');
	$fpoints = 'point'.(abs($warpstrength) == 1 ? '' : 's');

	echo "<p>"
		.sprite($relative, 'Propulsion', 5, 0, 64, 64, 32)
		."<span title='Maximum velocity'>".format_number($maxvelocity)." m/s</span></p>\n";

	echo "<p>"
		.sprite($relative, 'Agility', 10, 2, 32)
		."<span><span title='Agility modifier'>"
		.format_number($agility, 3)."x</span><br /><span title='Time to align'>"
		.format_number($aligntime)." s</span></span></p>\n";

	echo "<p>"
		.sprite($relative, 'Warp Core', 5, 2, 64, 64, 32)
		."<span><span title='Warp speed'>"
		.round($warpspeed, 1)." AU/s</span><br /><span title='Warp core strength'>"
		.$warpstrength." ".$fpoints."</span></span></p>\n";

	print_formatted_attribute_category('navigation', 'Navigation', '<span title="Maximum velocity">'.format_number($maxvelocity, -1).' m/s</span>', '', ob_get_clean());
}

function print_formatted_targeting(&$fit, $relative) {
	ob_start();

	static $types = array(
		'radar' => [ 12, 0 ],
		'ladar' => [ 13, 0 ],
		'magnetometric' => [ 12, 1 ],
		'gravimetric' => [ 13, 1 ],
	);

	$scanstrength = array();
	$maxtype = null;
	foreach($types as $t => $p) {
		$scanstrength[$t] = \Osmium\Dogma\get_ship_attribute($fit, 'scan'.ucfirst($t).'Strength');
		if($maxtype === null || $scanstrength[$t] > $scanstrength[$maxtype]) {
			$maxtype = $t;
		}
	}

	$targetrange = \Osmium\Dogma\get_ship_attribute($fit, 'maxTargetRange');
	$numtargets = (int)min(\Osmium\Dogma\get_char_attribute($fit, 'maxLockedTargets'),
	                  \Osmium\Dogma\get_ship_attribute($fit, 'maxLockedTargets'));
	$ftargets = 'target'.($numtargets !== 1 ? 's' : '');
	$scanres = \Osmium\Dogma\get_ship_attribute($fit, 'scanResolution');
	$sigradius = \Osmium\Dogma\get_ship_attribute($fit, 'signatureRadius');

	echo "<p>"
		.sprite($relative, ucfirst($maxtype).' Sensor Strength', $types[$maxtype][0], $types[$maxtype][1], 32)
		."<span><span title='Maximum locked targets'>"
		.$numtargets." ".$ftargets."</span><br /><span title='Sensor strength'>"
		.round($scanstrength[$maxtype], 1)." points</span></span></p>\n";

	echo "<p>"
		.sprite($relative, 'Targeting', 6, 1, 64, 64, 32)
		."<span><span title='Targeting range'>".format_range($targetrange)
		."</span><br /><span title='Scan resolution' id='scan_resolution' data-value='".$scanres."'>"
		.format_number($scanres)." mm</span></span></p>\n";

	echo "<p id='signature_radius' title='Signature radius' data-value='".$sigradius."'>"
		.sprite($relative, 'Signature Radius', 12, 4, 32)
		."<span>".format_range($sigradius)."</span></p>\n";

	print_formatted_attribute_category(
		'targeting', 'Targeting',
		$numtargets.' '.$ftargets.' @ '.format_range($targetrange, true),
		'', ob_get_clean()
	);
}

function print_formatted_misc(&$fit) {
	ob_start();

	echo "<table>\n<tbody>\n";

	$missing = array();
	$prices = \Osmium\Fit\get_estimated_price($fit, $missing);
	$grand_total = 0;
	$fprices = array();

	foreach($prices as $section => $p) {
		if($p !== 'N/A') {
			$grand_total += $p;
			$p = format_isk($p);
		}

		$fprices[] = $section.': '.$p;
	}

	if($grand_total === 0 && count($missing) > 0) {
		$grand_total = 'N/A';
	} else {
		$grand_total = format_isk($grand_total);
		if(count($missing) > 0) {
			$grand_total = '≥ '.$grand_total;
		}
	}

	$fprices = implode(',<br />', $fprices);
	if($fprices === '') $fprices = '<small>N/A</small>';

	if(count($missing) > 0) {
		$missing = implode(",&#10;", array_unique(array_map('Osmium\Fit\get_typename', $missing)));
		$fprices = "<span title='Estimate of the following items unavailable:&#10;"
			.escape($missing)."'>".$fprices.'</span>';
	}

	echo "<tr><th>Estimated price:</th><td>{$fprices}</td></tr>\n";

	$yield = \Osmium\Fit\get_mining_yield($fit);
	if($yield > 0) {
		$yield *= 3600000; /* From m³/ms to m³/h */
		$yield = number_format($yield);
		echo "<tr><th>Mining yield:</th><td>$yield m<sup>3</sup>/h</td></tr>\n";
	}

	$cargo = \Osmium\Dogma\get_ship_attribute($fit, 'capacity');
	echo "<tr><th>Cargo capacity:</th><td>".round($cargo, 2)." m<sup>3</sup></td></tr>\n";

	if(count($fit['drones']) > 0) {
		$dcrange = \Osmium\Dogma\get_char_attribute($fit, 'droneControlDistance');
		echo "<tr><th>Drone control range:</th><td>".format_range($dcrange)."</td></tr>\n";
	}

	echo "</tbody>\n</table>\n";
	print_formatted_attribute_category('misc', 'Miscellaneous', $grand_total, '', ob_get_clean());
}

function print_formatted_loadout_attributes(&$fit, $relative = '.', $opts = null, $dmgprofile = null) {
	if($opts === null) {
		/* NB: if you change the defaults here, also change the
		 * default exported values in CLF export function. */
		$opts = USE_RELOAD_TIME_FOR_CAPACITOR;
	}

	if($dmgprofile === null) {
		$dmgprofile = array('em' => .25, 'thermal' => .25, 'explosive' => .25, 'kinetic' => .25);
	}

	$cap = \Osmium\Fit\get_capacitor_stability($fit, $opts & USE_RELOAD_TIME_FOR_CAPACITOR);
	$ehp = \Osmium\Fit\get_ehp_and_resists($fit, $dmgprofile);

	print_formatted_engineering($fit, $relative, $cap);
	print_formatted_offense($fit, $relative, $opts & USE_RELOAD_TIME_FOR_DPS);
	print_formatted_defense($fit, $relative, $ehp, $cap, $dmgprofile, $opts & USE_RELOAD_TIME_FOR_TANK);
	print_formatted_navigation($fit, $relative);
	print_formatted_targeting($fit, $relative);
	print_formatted_misc($fit);
}

function get_formatted_loadout_attributes(&$fit, $relative = '.', $opts = null, $dmgprofile = null) {
	ob_start();
	print_formatted_loadout_attributes($fit, $relative, $opts, $dmgprofile);
	return ob_get_clean();
}

function print_loadout_common_footer(&$fit, $relative, $clftoken) {
	$cdp = \Osmium\State\get_state_trypersist('custom_damage_profiles', []);
	if($cdp === []) {
		$cdp = "{}";
	} else {
		$cdp = json_encode($cdp);
	}

	\Osmium\Chrome\print_js_code(
		"osmium_cdatastaticver = ".\Osmium\CLIENT_DATA_STATICVER.";
osmium_staticver = ".\Osmium\STATICVER.";
osmium_relative = '".$relative."';
osmium_token = '".\Osmium\State\get_token()."';
osmium_clftoken = '".$clftoken."';
osmium_clf = ".json_encode(\Osmium\Fit\export_to_common_loadout_format_1(
	$fit,
	\Osmium\Fit\CLF_EXPORT_MINIFY
	| \Osmium\Fit\CLF_EXPORT_EXTRA_PROPERTIES
	| \Osmium\Fit\CLF_EXPORT_INTERNAL_PROPERTIES
)).";
osmium_custom_damage_profiles = ".$cdp.";
osmium_skillsets = ".json_encode(\Osmium\Fit\get_available_skillset_names_for_account()).";"
	);

	echo "<script type='application/javascript' src='".$relative."/static-1/rawdeflate.min.js'></script>";

	\Osmium\Chrome\print_js_snippet('tabs');
	\Osmium\Chrome\print_js_snippet('modal');
	\Osmium\Chrome\print_js_snippet('context_menu');
	\Osmium\Chrome\print_js_snippet('loadout_common');
	\Osmium\Chrome\print_js_snippet('sprite');
	\Osmium\Chrome\print_js_snippet('show_info');
	\Osmium\Chrome\print_js_snippet('formatted_attributes');
	\Osmium\Chrome\print_js_snippet('new_loadout-fattribs');
}
