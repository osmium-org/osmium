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
	echo "<link rel='stylesheet' href='$relative/static/fonts/stylesheet.css' type='text/css' />\n";
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

function print_loadout_title($name, $viewpermission, $author) {
	$pic = '';
	if($viewpermission == \Osmium\Fit\VIEW_PASSWORD_PROTECTED) {
		$pic = "<img src='../static/icons/private.png' alt='(password-protected)' title='Password-protected fit' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_ALLIANCE_ONLY) {
		$pic = "<img src='../static/icons/corporation.png' alt='(".$author['alliancename']." only)' title='".$author['alliancename']." only' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_CORPORATION_ONLY) {
		if(!$author['allianceid']) $author['alliancename'] = '*no alliance*';
		$pic = "<img src='../static/icons/corporation.png' alt='(".$author['corporationname']." only)' title='".$author['corporationname']." only' />";
	} else if($viewpermission == \Osmium\Fit\VIEW_OWNER_ONLY) {
		$pic = "<img src='../static/icons/onlyme.png' alt='(only visible by me)' title='Only visible by me' />";
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
		return '0';
	}

	$ret = format_number($used).' / '.format_number($total);
	if($show_percent) {
		$percent = 100 * $used / $total;
		$overflow = max(min(6, ceil($percent) - 100), 0);
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

	return number_format((1 - $resonance) * 100, 1).'%';
}