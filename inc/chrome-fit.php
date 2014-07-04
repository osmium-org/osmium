<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * Copyright (C) 2013 Josiah Boning <jboning@gmail.com>
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



function get_remotes() {
	static $types = array(
		'hull' => [ "Raw hull hitpoints repaired per second", 524 ],
		'armor' => [ "Raw armor hitpoints repaired per second", 523 ],
		'shield' => [ "Raw shield hitpoints transferred per second", 405 ],
		'capacitor' => [ "Giga joules transferred per second", 529 ],
		'neutralization' => [ "Giga joules neutralized per second", 533 ],
		'leech' => [ "Giga joules leeched per second (in the best case)", 530 ],
	);

	return $types;
}

/** @deprecated */
function print_formatted_attribute_category($identifier, $title, $titledata, $titleclass, $contents) {
	if($titleclass) $titleclass = " class='$titleclass'";
	echo "<section id='$identifier'>\n";
	echo "<h4$titleclass>$title <small>$titledata</small></h4>\n";
	echo "<div>\n$contents</div>\n";
	echo "</section>\n";
}

function print_formatted_offense(&$fit, $relative, array $ia, $reload = false) {
	ob_start();
	$ndtypes = 0;

	list($turretdps, $turretalpha) = \Osmium\Fit\get_damage_from_turrets($fit, $ia, $reload);
	if($turretalpha > 0) {
		++$ndtypes;
		echo "<p>"
			.sprite($relative, 'Turret damage', 0, 0, 64, 64, 32)
			."<span><span title='Turret volley (alpha)'>"
			.format_number($turretalpha)."</span><br /><span title='Turret DPS'>"
			.format_number($turretdps)."</span></span></p>\n";
	}

	list($missiledps, $missilealpha) = \Osmium\Fit\get_damage_from_missiles($fit, $ia, $reload);
	if($missilealpha > 0) {
		++$ndtypes;
		echo "<p>"
			.sprite($relative, 'Missile damage', 0, 1, 64, 64, 32)
			."<span><span title='Missile volley (alpha)'>"
			.format_number($missilealpha)."</span><br /><span title='Missile DPS'>"
			.format_number($missiledps)."</span></span></p>\n";
	}

	list($sbdps, $sbalpha) = \Osmium\Fit\get_damage_from_smartbombs($fit, $ia);
	if($sbalpha > 0) {
		++$ndtypes;
		echo "<p>"
			.sprite($relative, 'Smartbomb damage', 0, 3, 64, 64, 32)
			."<span><span title='Smartbomb volley (alpha)'>"
			.format_number($sbalpha)."</span><br /><span title='Smartbomb DPS'>"
			.format_number($sbdps)."</span></span></p>\n";
	}

	list($dronedps, $dronealpha) = \Osmium\Fit\get_damage_from_drones($fit, $ia);
	if($dronedps > 0) {
		++$ndtypes;
		echo "<p>"
			.sprite($relative, 'Drone damage', 0, 2, 64, 64, 32)
			."<span title='Drone DPS'>".format_number($dronedps)."</span></p>\n";
	}

	if($ndtypes > 0) {
		$dps = format_number($missiledps + $turretdps + $dronedps + $sbdps, -1);
		print_formatted_attribute_category('offense', 'Offense', "<span title='Total damage per second'>".$dps." dps</span>", '', ob_get_clean());
	} else {
		ob_end_clean();
	}
}

function print_formatted_defense(&$fit, $relative, $ehp, $cap, $reload = false) {
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
	foreach(\Osmium\Fit\get_tank($fit, $ehp, $cap['delta'], $reload) as $lname => $a) {
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
	
	print_formatted_attribute_category('defense', 'Defense <span class="pname">'.escape($fit['damageprofile']['name']).'</span>', implode(' – ', $subtitles), '', ob_get_clean());
}

function print_formatted_incoming(&$fit, $relative, $ehp) {
	ob_start();

	$incoming = \Osmium\Fit\get_incoming($fit);
	$best = 0;
	$fbest = '';

	foreach(get_remotes() as $t => $info) {
		if(!isset($incoming[$t])) continue;
		list($amount, $unit) = $incoming[$t];
		list($title, $img) = $info;
		if($amount < 1e-300) continue;

		if(isset($ehp[$t]) && $unit === 'HP') {
			$avgresonance = 0;
			foreach($fit['damageprofile']['damages'] as $type => $dmg) {
				$avgresonance += $dmg * $ehp[$t]['resonance'][$type];
			}

			$amount /= $avgresonance;
			$unit = 'EHP';
			$title = str_replace('Raw ', 'Effective ', $title);
		}

		$unit .= '/s';
		$amount *= 1000;
		$famount = format($amount).' '.$unit;

		if($amount > $best) {
			$best = $amount;
			$fbest = "<span title='{$title}'>{$t}: {$famount}</span>";
		}

		echo "<p title='{$title}'>"
			."<img src='//image.eveonline.com/Type/{$img}_64.png' alt='{$t}' />"
			.$famount
			."</p>\n";
	}

	$contents = ob_get_clean();

	if($contents) {
		print_formatted_attribute_category(
			'incoming', 'Incoming', $fbest,
			'', $contents
		);
	}
}

function print_formatted_outgoing(&$fit, $relative) {
	ob_start();

	$outgoing = \Osmium\Fit\get_outgoing($fit);
	$best = 0;
	$fbest = '';

	foreach(get_remotes() as $t => $info) {
		if(!isset($outgoing[$t])) continue;
		list($amount, $unit) = $outgoing[$t];
		list($title, $img) = $info;
		if($amount < 1e-300) continue;

		$unit .= '/s';
		$amount *= 1000;
		$famount = format($amount).' '.$unit;

		if($amount > $best) {
			$best = $amount;
			$fbest = "<span title='{$title}'>{$t}: {$famount}</span>";
		}

		echo "<p title='{$title}'>"
			."<img src='//image.eveonline.com/Type/{$img}_64.png' alt='{$t}' />"
			.$famount
			."</p>\n";
	}

	$contents = ob_get_clean();

	if($contents) {
		print_formatted_attribute_category(
			'outgoing', 'Outgoing', $fbest,
			'', $contents
		);
	}
}

function print_formatted_navigation(&$fit, $relative) {
	ob_start();

	$maxvelocity = round(\Osmium\Dogma\get_ship_attribute($fit, 'maxVelocity'));
	$agility = \Osmium\Dogma\get_ship_attribute($fit, 'agility');
	$aligntime = -log(0.25) * \Osmium\Dogma\get_ship_attribute($fit, 'mass') * $agility / 1000000;
	$warpspeed = \Osmium\Dogma\get_ship_attribute($fit, 'warpSpeedMultiplier') * \Osmium\Dogma\get_ship_attribute($fit, 'baseWarpSpeed');
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
