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

namespace Osmium\Chrome;

/**
 * Print the title of a loadout with additional state pictures.
 *
 * @param $name the name of the loadout (will be escaped).
 *
 * @param $viewpermission one of the VIEW_* contsants.
 *
 * @param $visibility one of the VISIBILITY_* constants.
 *
 * @param $author array containing the loadout author's info
 * (alliancename/id, corporationname/id).
 *
 * @param $relative relative path to the main page
 *
 * @param $loadoutid if non-null, display favorite status of the
 * loadout for the currently logged-in user.
 */
function print_loadout_title($name, $viewpermission, $visibility, $author, $relative = '.', $loadoutid = null) {
	$pic = '';
	if($viewpermission == \Osmium\Fit\VIEW_PASSWORD_PROTECTED) {
		$pic = "<img src='$relative/static-".\Osmium\STATICVER."/icons/private.png' alt='(password-protected)' title='Password-protected fit' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_ALLIANCE_ONLY) {
		$aname = ($author['apiverified'] === 't' && $author['allianceid'] > 0) ?
			htmlspecialchars($author['alliancename'], ENT_QUOTES) : 'My alliance';
		$pic = "<img src='$relative/static-".\Osmium\STATICVER."/icons/corporation.png' alt='($aname only)' title='$aname only' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_CORPORATION_ONLY) {
		$cname = ($author['apiverified'] === 't') ?
			htmlspecialchars($author['corporationname'], ENT_QUOTES) : 'My corporation';
		$pic = "<img src='$relative/static-".\Osmium\STATICVER."/icons/corporation.png' alt='($cname only)' title='$cname only' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_OWNER_ONLY) {
		$pic = "<img src='$relative/static-".\Osmium\STATICVER."/icons/onlyme.png' alt='(only visible by me)' title='Only visible by me' />";
	}

	if($visibility == \Osmium\Fit\VISIBILITY_PRIVATE) {
		$pic .= "<img src='$relative/static-".\Osmium\STATICVER."/icons/hidden.png' alt='(hidden)' title='This loadout will never appear on search results, and will never be indexed by search engines.' />";
	}

	if($loadoutid !== null && ($a = \Osmium\State\get_state('a', null)) !== null) {
		$fav = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT loadoutid FROM osmium.accountfavorites WHERE accountid = $1 AND loadoutid = $2', array($a['accountid'], $loadoutid)));
		$fav = ($fav !== false);

		if($fav) {
			$title = 'One of your favorite loadouts; click to un-favorite';
			$favimg = '';
		} else {
			$title = 'Add to your favorite loadouts';
			$favimg = '_ds';
		}

		$tok = \Osmium\State\get_token();
		$pic .= "<a href='$relative/favorite/$loadoutid?tok=$tok' title='$title'><img src='$relative/static-".\Osmium\STATICVER."/icons/favorited$favimg.png' alt='Favorite status' /></a>";
	}
  
	echo "<span class='fitname'>".htmlentities($name).$pic."</span>";
}

function print_formatted_attribute_category($identifier, $title, $titledata, $titleclass, $contents) {
	if($titleclass) $titleclass = " class='$titleclass'";
	echo "<section id='$identifier'>\n";
	echo "<h4$titleclass>$title <small>$titledata</small></h4>\n";
	echo "<div>\n$contents</div>\n";
	echo "</section>\n";
}

function print_formatted_engineering(&$fit, $relative, $capacitor) {
	ob_start();

	$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlotsLeft');
	$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlots');
	$formatted = \Osmium\Chrome\format_used($slotsTotal - $slotsLeft, $slotsTotal, 0, false, $overturrets);
	echo "<p class='overflow$overturrets'><img src='$relative/static-".\Osmium\STATICVER."/icons/turrethardpoints.png' alt='Turret hardpoints' title='Turret hardpoints' /><span id='turrethardpoints'>".$formatted."</span></p>\n";

	$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlotsLeft');
	$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlots');
	$formatted = \Osmium\Chrome\format_used($slotsTotal - $slotsLeft, $slotsTotal, 0, false, $overlaunchers);
	echo "<p class='overflow$overlaunchers'><img src='$relative/static-".\Osmium\STATICVER."/icons/launcherhardpoints.png' alt='Launcher hardpoints' title='Launcher hardpoints' /><span id='launcherhardpoints'>".$formatted."</span></p>\n";

	$formattedCapacitor = \Osmium\Chrome\format_capacitor($capacitor);
	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/capacitor.png' alt='Capacitor' title='Capacitor' /><span id='capacitor'>".$formattedCapacitor."</span></p>\n";

	$cpuUsed = \Osmium\Dogma\get_ship_attribute($fit, 'cpuLoad');
	$cpuTotal = \Osmium\Dogma\get_ship_attribute($fit, 'cpuOutput');
	$formatted = \Osmium\Chrome\format_used($cpuUsed, $cpuTotal, 2, true, $overcpu);
	echo "<p class='overflow$overcpu'><img src='$relative/static-".\Osmium\STATICVER."/icons/cpu.png' alt='CPU' title='CPU' /><span id='cpu'>".$formatted."</span></p>\n";

	$powerUsed = \Osmium\Dogma\get_ship_attribute($fit, 'powerLoad');
	$powerTotal = \Osmium\Dogma\get_ship_attribute($fit, 'powerOutput');
	$formatted = \Osmium\Chrome\format_used($powerUsed, $powerTotal, 2, true, $overpower);
	echo "<p class='overflow$overpower'><img src='$relative/static-".\Osmium\STATICVER."/icons/powergrid.png' alt='Powergrid' title='Powergrid' /><span id='power'>".$formatted."</span></p>\n";

	$upgradeCapacityUsed = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeLoad');
	$upgradeCapacityTotal = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeCapacity');
	$formatted = \Osmium\Chrome\format_used($upgradeCapacityUsed, $upgradeCapacityTotal, 2, true, $overupgrade);
	echo "<p class='overflow$overupgrade'><img src='$relative/static-".\Osmium\STATICVER."/icons/calibration.png' alt='Calibration' title='Calibration' /><span id='upgradecapacity'>".$formatted."</span></p>\n";

	print_formatted_attribute_category('engineering', 'Engineering', "<span title='Capacitor stability'>".lcfirst($formattedCapacitor).'</span>', 'overflow'.max($overturrets, $overlaunchers, $overcpu, $overpower, $overupgrade), ob_get_clean());
}

function print_formatted_offense(&$fit, $relative) {
	ob_start();

	list($missiledps, $missilealpha) = \Osmium\Fit\get_damage_from_missiles($fit);
	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/missilelauncher.png' alt='Missile damage' title='Missile damage' /><span><span title='Missile volley (alpha)'>".format_number($missilealpha)."</span><br /><span title='Missile DPS'>".format_number($missiledps)."</span></span></p>\n";

	list($turretdps, $turretalpha) = \Osmium\Fit\get_damage_from_turrets($fit);
	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/turret.png' alt='Turret damage' title='Turret damage' /><span><span title='Turret volley (alpha)'>".format_number($turretalpha)."</span><br /><span title='Turret DPS'>".format_number($turretdps)."</span></span></p>\n";

	$dronedps = \Osmium\Fit\get_damage_from_drones($fit);
	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/drones.png' alt='Drone damage' title='Drone damage' /><span title='Drone DPS'>".format_number($dronedps)."</span></p>\n";	

	$dps = format_number($missiledps + $turretdps + $dronedps, -1);
	print_formatted_attribute_category('offense', 'Offense', "<span title='Total damage per second'>".$dps." dps</span>", '', ob_get_clean());
}

function print_formatted_defense(&$fit, $relative, $ehp, $cap) {
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
	echo "<strong title='EHP in the average case (uniform damage repartition)'>".$aehp."</strong><br />\n";
	echo "<span title='EHP in the best case (dealing damage with the highest resistance)'>≤".$Mehp."</span></th>\n";
	echo "<td><img src='$relative/static-".\Osmium\STATICVER."/icons/r_em.png' alt='EM Resistance' title='EM Resistance' /></td>\n";
	echo "<td><img src='$relative/static-".\Osmium\STATICVER."/icons/r_thermal.png' alt='Thermal Resistance' title='Thermal Resistance' /></td>\n";
	echo "<td><img src='$relative/static-".\Osmium\STATICVER."/icons/r_kinetic.png' alt='Kinetic Resistance' title='Kinetic Resistance' /></td>\n";
	echo "<td><img src='$relative/static-".\Osmium\STATICVER."/icons/r_explosive.png' alt='Explosive Resistance' title='Explosive Resistance' /></td>\n";
	echo "</tr>\n</thead>\n<tfoot></tfoot>\n<tbody>\n<tr id='shield'>\n";
	echo "<th><img src='$relative/static-".\Osmium\STATICVER."/icons/shield.png' alt='Shield' title='Shield' /></th>\n";
	echo implode('', $resists['shield']);
	echo"</tr>\n<tr id='armor'>\n";
	echo "<th><img src='$relative/static-".\Osmium\STATICVER."/icons/armor.png' alt='Armor' title='Armor' /></th>\n";
	echo implode('', $resists['armor']);
	echo"</tr>\n<tr id='hull'>\n";
	echo "<th><img src='$relative/static-".\Osmium\STATICVER."/icons/hull.png' alt='Hull' title='Hull' /></th>\n";
	echo implode('', $resists['hull']);
	echo "</tr>\n</tbody>\n</table>\n";

	$passiverechargerate = 4 * 2500 * $ehp['shield']['capacity'] 
		/ \Osmium\Dogma\get_ship_attribute($fit, 'shieldRechargeRate') 
		/ array_sum($ehp['shield']['resonance']);
	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/shieldrecharge.png' alt='Passive shield recharge' title='Passive shield recharge' /><span title='Shield peak passive recharge rate' id='passiveshieldrecharge'>".\Osmium\Chrome\format_number($passiverechargerate)."</span></p>\n";

	$h = print_tank_layer($fit, 'structureRepair', 'structureDamageAmount', $ehp['hull']['resonance'], $cap,
	                      $relative, 'hullrepair.png', 'Hull repairs', 'Hull EHP repaired per second', false);
	$a = print_tank_layer($fit, 'armorRepair', 'armorDamageAmount', $ehp['armor']['resonance'], $cap,
	                      $relative, 'armorrepair.png', 'Armor repairs', 'Armor EHP repaired per second', false);
	$s = print_tank_layer($fit, 'shieldBoosting', 'shieldBonus', $ehp['shield']['resonance'], $cap,
	                      $relative, 'shieldboost.png', 'Shield boost', 'Shield EHP boost per second', false);
	$f = print_tank_layer($fit, 'fueledShieldBoosting', 'shieldBonus', $ehp['shield']['resonance'], $cap,
	                      $relative, 'fueledshieldboost.png', 'Fueled Shield boost', 'Shield EHP boost per second',
	                      false, true);

	$total = format_number($passiverechargerate + 1000 * ($h[0] + $a[0] + $s[0] + $f[0]), -1);
	$tehp = format_number($ehp['ehp']['avg'], -2);
	
	print_formatted_attribute_category('defense', 'Defense', '<span title="Average EHP">'.$tehp.' ehp</span> | <span title="Combined reinforced tank">'.$total.' dps</span>', '', ob_get_clean());
}

function print_formatted_navigation(&$fit, $relative) {
	ob_start();

	$maxvelocity = round(\Osmium\Dogma\get_ship_attribute($fit, 'maxVelocity'));
	$agility = \Osmium\Dogma\get_ship_attribute($fit, 'agility');
	$aligntime = -log(0.25) * \Osmium\Dogma\get_ship_attribute($fit, 'mass') * $agility / 1000000;

	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/propulsion.png' alt='Propulsion' title='Propulsion' /><span title='Maximum velocity'>".format_number($maxvelocity)." m/s</span></p>\n";

	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/agility.png' alt='Agility' title='Agility' /><span><span title='Agility modifier'>".format_number($agility, 3)."x</span><br /><span title='Time to align'>".format_number($aligntime)." s</span></span></p>\n";

	print_formatted_attribute_category('navigation', 'Navigation', '<span title="Maximum velocity">'.format_number($maxvelocity, -1).' m/s</span>', '', ob_get_clean());
}

function print_formatted_misc(&$fit) {
	ob_start();

	echo "<table>\n<tbody>\n";

	$missing = array();
	$p = \Osmium\Fit\get_average_price($fit, $missing);
	if(!$p) {
		$p = 'N/A';
	} else {
		$p = format_isk($p);
		if(count($missing) > 0) {
			$missing = implode(', ', array_keys($missing));
			$p = "<span title='Estimate of the following items unavailable: "
				.htmlspecialchars($missing, ENT_QUOTES)."'>≥".$p.'</span>';
		}
	}

	echo "<tr><th>Average price:</th><td>$p</td></tr>\n";

	echo "</tbody>\n</table>\n";
	print_formatted_attribute_category('misc', 'Miscellaneous', $p, '', ob_get_clean());
}

function print_formatted_loadout_attributes(&$fit, $relative = '.') {
	$cap = \Osmium\Fit\get_capacitor_stability($fit);
	$ehp = \Osmium\Fit\get_ehp_and_resists($fit);

	print_formatted_engineering($fit, $relative, $cap);
	print_formatted_offense($fit, $relative);
	print_formatted_defense($fit, $relative, $ehp, $cap);
	print_formatted_navigation($fit, $relative);
	print_formatted_misc($fit);
}

function print_tank_layer($fit, $effectname, $shipattributename, $resonances, $cap, 
                          $relative, $imagesrc, $imagetitle, $title, $forceshow = true, $nullifyifcharge = false) {
	list($reinforced, $sustained) = \Osmium\Fit\get_repaired_amount_per_second(
		$fit, $effectname, $shipattributename, $resonances, $cap, $nullifyifcharge
		);

	if($reinforced == 0 && !$forceshow) return;

	$reinforcedimg = "<img src='$relative/static-".\Osmium\STATICVER."/icons/tankreinforced.png' title='Reinforced tank' alt='Reinforced' />";
	$sustainedimg = "<img src='$relative/static-".\Osmium\STATICVER."/icons/tanksustained.png' title='Sustained tank' alt='Sustained tank' />";
	echo "<p><img src='$relative/static-".\Osmium\STATICVER."/icons/$imagesrc' alt='$imagetitle' title='$imagetitle' /><span>";
	echo $reinforcedimg."<span title='$title'>".format_number(1000 * $reinforced)."</span><br />";
	echo $sustainedimg."<span title='$title'>".format_number(1000 * $sustained)."</span>";
	echo "</span></p>\n";

	return array($reinforced, $sustained);
}

function get_formatted_loadout_attributes(&$fit, $relative = '.') {
	ob_start();
	print_formatted_loadout_attributes($fit, $relative);
	return ob_get_clean();
}
