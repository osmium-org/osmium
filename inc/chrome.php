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

function print_header($title = '', $relative = '.', $add_head = '') {
	global $__osmium_chrome_relative;
	$__osmium_chrome_relative = $relative;

	\Osmium\State\api_maybe_redirect($relative);

	if($title == '') {
		$title = 'Osmium / '.\Osmium\SHORT_DESCRIPTION;
	} else {
		$title .= ' / Osmium';
	}

	echo "<!DOCTYPE html>\n<html>\n<head>\n";
	echo "<meta charset='UTF-8' />\n";
	echo "<script type='application/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js'></script>\n";
	echo "<script type='application/javascript' src='https://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js'></script>\n";
	echo "<link href='http://fonts.googleapis.com/css?family=Inconsolata' rel='stylesheet' type='text/css'>\n";
	echo "<link rel='stylesheet' href='$relative/static/chrome.css' type='text/css' />\n";
	echo "<link rel='icon' type='image/png' href='$relative/static/favicon.png' />\n";
	echo "<title>$title</title>\n";
	echo "$add_head</head>\n<body>\n<div id='wrapper'>\n";

	echo "<nav>\n<ul>\n";
	echo get_navigation_link($relative.'/', "Main page");
	echo get_navigation_link($relative.'/search', "Search loadouts");
	if(\Osmium\State\is_logged_in()) {
		echo get_navigation_link($relative.'/new', "New loadout");
		echo get_navigation_link($relative.'/import', "Import loadouts");
		echo get_navigation_link($relative.'/renew_api', "API settings");
	} else {

	}

	echo "</ul>\n";
	\Osmium\State\print_login_or_logout_box($relative);
	echo "</nav>\n";
	echo "<noscript>\n<p id='nojs_warning'>To get the full Osmium experience, please enable Javascript for host <strong>".$_SERVER['HTTP_HOST']."</strong>.</p>\n</noscript>\n";
}

function print_footer() {
	global $__osmium_chrome_relative;
	echo "<div id='push'></div>\n</div>\n<footer>\n";
	echo "<p><strong>Osmium ".\Osmium\VERSION." @ ".gethostname()."</strong> — (Artefact2/Indalecia) — <a href='https://github.com/Artefact2/osmium'>Browse source</a> (<a href='http://www.gnu.org/licenses/agpl.html'>AGPLv3</a>)</p>";
	echo "</footer>\n</body>\n</html>\n";
}

function get_navigation_link($dest, $label) {
	if(get_significant_uri($dest) == get_significant_uri($_SERVER['REQUEST_URI'])) {
		return "<li><strong><a href='$dest'>$label</a></strong></li>\n";
	}

	return "<li><a href='$dest'>$label</a></li>\n";
}

function get_significant_uri($uri) {
	$uri = explode('?', $uri, 2)[0];
	$uri = explode('/', $uri);
	return array_pop($uri);
}

function print_js_snippet($js_file) {
	echo "<script>\n".file_get_contents(\Osmium\ROOT.'/src/snippets/'.$js_file.'.js')."</script>\n";
}

function return_json($data, $flags = 0) {
	header('Content-Type: application/json');
	die(json_encode($data, $flags));
}

function print_loadout_title($name, $viewpermission, $visibility, $author, $relative = '.') {
	$pic = '';
	if($viewpermission == \Osmium\Fit\VIEW_PASSWORD_PROTECTED) {
		$pic = "<img src='$relative/static/icons/private.png' alt='(password-protected)' title='Password-protected fit' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_ALLIANCE_ONLY) {
		$pic = "<img src='$relative/static/icons/corporation.png' alt='(".$author['alliancename']." only)' title='".$author['alliancename']." only' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_CORPORATION_ONLY) {
		if(!$author['allianceid']) $author['alliancename'] = '*no alliance*';
		$pic = "<img src='$relative/static/icons/corporation.png' alt='(".$author['corporationname']." only)' title='".$author['corporationname']." only' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_OWNER_ONLY) {
		$pic = "<img src='$relative/static/icons/onlyme.png' alt='(only visible by me)' title='Only visible by me' />";
	}

	if($visibility == \Osmium\Fit\VISIBILITY_PRIVATE) {
		$pic .= "<img src='$relative/static/icons/hidden.png' alt='(hidden)' title='This loadout will never appear on search results, and will never be indexed by search engines.' />";
	}
  
	echo "<span class='fitname'>".htmlentities($name).$pic."</span>";
}

function print_search_form() {
	global $query;

	$val = '';
	if($query !== false) {
		$val = "value='".htmlspecialchars($query, ENT_QUOTES)."' ";
	}

	echo "<form method='get' action='./search'>\n";
	echo "<h1><img src='./static/icons/search.png' alt='' />Search loadouts</h1>\n<p>\n<input type='search' autofocus='autofocus' placeholder='Search by name, description, ship, modules or tags…' name='q' $val/> <input type='submit' value='Go!' />\n</p>\n";
	echo "</form>\n";
}

function format_used($used, $total, $digits, $show_percent, &$overflow) {
	if($total == 0 && $used == 0) {
		$overflow = 0;
		return '0';
	}

	$ret = format_number($used).' / '.format_number($total);
	$percent = $total > 0 ? (100 * $used / $total) : 100;
	$overflow = max(min(6, ceil($percent) - 100), 0);
	if($show_percent) {
		$ret .= '<br />'.round(100 * $used / $total, $digits).' %';
	}

	return $ret;
}

function format_number($num) {
	$num = floatval($num);
	if($num < 0) {
		$sign = '-';
		$num = -$num;
	} else {
		$sign = '';
	}

	if($num < 10000) return $sign.round($num, 1);
	else if($num < 10000000) {
		return $sign.round($num / 1000, 2).'k';
	} else {
		return $sign.round($num / 1000000, 2).'m';
	}
}

function format_duration($seconds) {
	$s = fmod($seconds, 60);
	$m = round($seconds - $s) / 60;

	$s = floor($s);

	if($s == 0 && $m == 0) return '0s';
	else {
		$k = '';
		if($m != 0) { $k .= $m.'m'; }
		if($s != 0) { $k .= $s.'s'; }
		return $k;
	}
}

function format_capacitor($array) {
	/* $array is returned by get_capacitor_stability */
	list($rate, $is_stable, $data) = $array;

	$rate = round(-$rate * 1000, 1).' GJ/s';
	if($rate > 0) $rate = '+'.((string)$rate);

	if($is_stable) {
		return "Stable at ".round($data, 1)."% ($rate)";
	} else {
		return "Lasts ".format_duration($data)." ($rate)";
	}
}

function format_resonance($resonance) {
	if($resonance < 0) return '100%';
	if($resonance > 1) return '0%';

	$percent = (1 - $resonance) * 100;

	return "<div>".number_format($percent, 1)."%<span class='bar' style='width: ".round($percent, 2)."%;'></span></div>";
}

function print_formatted_loadout_attributes(&$fit, $relative = '.') {	
	echo "<li>\n";
	$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlotsLeft');
	$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlots');
	$formatted = \Osmium\Chrome\format_used($slotsTotal - $slotsLeft, $slotsTotal, 0, false, $over);
	echo "<p class='overflow$over'><img src='$relative/static/icons/turrethardpoints.png' alt='Turret hardpoints' title='Turret hardpoints' /><span id='turrethardpoints'>".$formatted."</span></p>\n";
	$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlotsLeft');
	$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlots');
	$formatted = \Osmium\Chrome\format_used($slotsTotal - $slotsLeft, $slotsTotal, 0, false, $over);
	echo "<p class='overflow$over'><img src='$relative/static/icons/launcherhardpoints.png' alt='Launcher hardpoints' title='Launcher hardpoints' /><span id='launcherhardpoints'>".$formatted."</span></p>\n";
	echo "<p><img src='$relative/static/icons/capacitor.png' alt='Capacitor' title='Capacitor' /><span id='capacitor'>".\Osmium\Chrome\format_capacitor(\Osmium\Fit\get_capacitor_stability($fit))."</span></p>\n";
	echo "</li>\n";
	
	echo "<li>\n";
	$cpuUsed = \Osmium\Dogma\get_ship_attribute($fit, 'cpuLoad');
	$cpuTotal = \Osmium\Dogma\get_ship_attribute($fit, 'cpuOutput');
	$formatted = \Osmium\Chrome\format_used($cpuUsed, $cpuTotal, 2, true, $over);
	echo "<p class='overflow$over'><img src='$relative/static/icons/cpu.png' alt='CPU' title='CPU' /><span id='cpu'>".$formatted."</span></p>\n";
	$powerUsed = \Osmium\Dogma\get_ship_attribute($fit, 'powerLoad');
	$powerTotal = \Osmium\Dogma\get_ship_attribute($fit, 'powerOutput');
	$formatted = \Osmium\Chrome\format_used($powerUsed, $powerTotal, 2, true, $over);
	echo "<p class='overflow$over'><img src='$relative/static/icons/powergrid.png' alt='Powergrid' title='Powergrid' /><span id='power'>".$formatted."</span></p>\n";
	$upgradeCapacityUsed = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeLoad');
	$upgradeCapacityTotal = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeCapacity');
	$formatted = \Osmium\Chrome\format_used($upgradeCapacityUsed, $upgradeCapacityTotal, 2, true, $over);
	echo "<p class='overflow$over'><img src='$relative/static/icons/calibration.png' alt='Calibration' title='Calibration' /><span id='upgradecapacity'>".$formatted."</span></p>\n";
	echo "</li>\n";
	
	/* TODO refactor this mess (maybe, not a big deal) */
	$shieldCapacity = \Osmium\Dogma\get_ship_attribute($fit, 'shieldCapacity');
	$shieldEmResist = \Osmium\Dogma\get_ship_attribute($fit, 'shieldEmDamageResonance');
	$shieldThermalResist = \Osmium\Dogma\get_ship_attribute($fit, 'shieldThermalDamageResonance');
	$shieldKineticResist = \Osmium\Dogma\get_ship_attribute($fit, 'shieldKineticDamageResonance');
	$shieldExplosiveResist = \Osmium\Dogma\get_ship_attribute($fit, 'shieldExplosiveDamageResonance');
	$armorCapacity = \Osmium\Dogma\get_ship_attribute($fit, 'armorHP');
	$armorEmResist = \Osmium\Dogma\get_ship_attribute($fit, 'armorEmDamageResonance');
	$armorThermalResist = \Osmium\Dogma\get_ship_attribute($fit, 'armorThermalDamageResonance');
	$armorKineticResist = \Osmium\Dogma\get_ship_attribute($fit, 'armorKineticDamageResonance');
	$armorExplosiveResist = \Osmium\Dogma\get_ship_attribute($fit, 'armorExplosiveDamageResonance');
	$hullCapacity = \Osmium\Dogma\get_ship_attribute($fit, 'hp');
	$hullEmResist = \Osmium\Dogma\get_ship_attribute($fit, 'emDamageResonance');
	$hullThermalResist = \Osmium\Dogma\get_ship_attribute($fit, 'thermalDamageResonance');
	$hullKineticResist = \Osmium\Dogma\get_ship_attribute($fit, 'kineticDamageResonance');
	$hullExplosiveResist = \Osmium\Dogma\get_ship_attribute($fit, 'explosiveDamageResonance');
	/* Assume uniform damage distribution (TODO make it user-configurable) */
	$ehp = 4 * $shieldCapacity / 
		($shieldEmResist + $shieldThermalResist + $shieldKineticResist + $shieldExplosiveResist);
	$ehp += 4 * $armorCapacity / 
		($armorEmResist + $armorThermalResist + $armorKineticResist + $armorExplosiveResist);
	$ehp += 4 * $hullCapacity / 
		($hullEmResist + $hullThermalResist + $hullKineticResist + $hullExplosiveResist);
	$mehp = $shieldCapacity / max($shieldEmResist, $shieldThermalResist, $shieldKineticResist, $shieldExplosiveResist);
	$mehp += $armorCapacity / max($armorEmResist, $armorThermalResist, $armorKineticResist, $armorExplosiveResist);
	$mehp += $hullCapacity / max($hullEmResist, $hullThermalResist, $hullKineticResist, $hullExplosiveResist);
	$Mehp = $shieldCapacity / min($shieldEmResist, $shieldThermalResist, $shieldKineticResist, $shieldExplosiveResist);
	$Mehp += $armorCapacity / min($armorEmResist, $armorThermalResist, $armorKineticResist, $armorExplosiveResist);
	$Mehp += $hullCapacity / min($hullEmResist, $hullThermalResist, $hullKineticResist, $hullExplosiveResist);
	echo "<li>\n<table id='resists'>\n<thead>\n<tr>\n";
	echo "<th><abbr title='Effective Hitpoints'>EHP</abbr></th>\n";
	echo "<th id='ehp'>\n";
	echo "<span title='EHP in the worst case (dealing damage with the lowest resistance)'>≥".\Osmium\Chrome\format_number($mehp)."</span><br />\n";
	echo "<strong title='EHP in the average case (uniform damage repartition)'>".\Osmium\Chrome\format_number($ehp)."</strong><br />\n";
	echo "<span title='EHP in the best case (dealing damage with the highest resistance)'>≤".\Osmium\Chrome\format_number($Mehp)."</span></th>\n";
	echo "<td><img src='$relative/static/icons/r_em.png' alt='EM Resistance' title='EM Resistance' /></td>\n";
	echo "<td><img src='$relative/static/icons/r_thermal.png' alt='Thermal Resistance' title='Thermal Resistance' /></td>\n";
	echo "<td><img src='$relative/static/icons/r_kinetic.png' alt='Kinetic Resistance' title='Kinetic Resistance' /></td>\n";
	echo "<td><img src='$relative/static/icons/r_explosive.png' alt='Explosive Resistance' title='Explosive Resistance' /></td>\n";
	echo "</tr>\n</thead>\n<tfoot></tfoot>\n<tbody>\n<tr id='shield'>\n";
	echo "<th><img src='$relative/static/icons/shield.png' alt='Shield' title='Shield' /></th>\n";
	echo "<td class='capacity'>".\Osmium\Chrome\format_number($shieldCapacity)."</td>\n";
	echo "<td class='emresist'>".\Osmium\Chrome\format_resonance($shieldEmResist)."</td>\n";
	echo "<td class='thermalresist'>".\Osmium\Chrome\format_resonance($shieldThermalResist)."</td>\n";
	echo "<td class='kineticresist'>".\Osmium\Chrome\format_resonance($shieldKineticResist)."</td>\n";
	echo "<td class='explosiveresist'>".\Osmium\Chrome\format_resonance($shieldExplosiveResist)."</td>\n";
	echo"</tr>\n<tr id='armor'>\n";
	echo "<th><img src='$relative/static/icons/armor.png' alt='Armor' title='Armor' /></th>\n";
	echo "<td class='capacity'>".\Osmium\Chrome\format_number($armorCapacity)."</td>\n";
	echo "<td class='emresist'>".\Osmium\Chrome\format_resonance($armorEmResist)."</td>\n";
	echo "<td class='thermalresist'>".\Osmium\Chrome\format_resonance($armorThermalResist)."</td>\n";
	echo "<td class='kineticresist'>".\Osmium\Chrome\format_resonance($armorKineticResist)."</td>\n";
	echo "<td class='explosiveresist'>".\Osmium\Chrome\format_resonance($armorExplosiveResist)."</td>\n";
	echo"</tr>\n<tr id='hull'>\n";
	echo "<th><img src='$relative/static/icons/hull.png' alt='Hull' title='Hull' /></th>\n";
	echo "<td class='capacity'>".\Osmium\Chrome\format_number($hullCapacity)."</td>\n";
	echo "<td class='emresist'>".\Osmium\Chrome\format_resonance($hullEmResist)."</td>\n";
	echo "<td class='thermalresist'>".\Osmium\Chrome\format_resonance($hullThermalResist)."</td>\n";
	echo "<td class='kineticresist'>".\Osmium\Chrome\format_resonance($hullKineticResist)."</td>\n";
	echo "<td class='explosiveresist'>".\Osmium\Chrome\format_resonance($hullExplosiveResist)."</td>\n";
	echo "</tr>\n</tbody>\n</table>\n</li>\n";

	echo "<li>\n";
	$maxvelocity = round(\Osmium\Dogma\get_ship_attribute($fit, 'maxVelocity'));
	$aligntime = -log(0.25) * \Osmium\Dogma\get_ship_attribute($fit, 'mass')
		* \Osmium\Dogma\get_ship_attribute($fit, 'agility') / 1000000;
	echo "<p><img src='$relative/static/icons/propulsion.png' alt='Propulsion' title='Propulsion' /><span id='propulsion'><span title='Maximum velocity'>".\Osmium\Chrome\format_number($maxvelocity)." m/s</span><br /><span title='Align time'>".round($aligntime, 1)." s</span></span></p>\n";
	echo "</li>\n";
}

function get_formatted_loadout_attributes(&$fit, $relative = '.') {
	ob_start();
	print_formatted_loadout_attributes($fit, $relative);
	return ob_get_clean();
}