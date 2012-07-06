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

require __DIR__.'/chrome-layout.php';
require __DIR__.'/chrome-fit.php';

/**
 * Print a basic seach form. Pre-fills the search form with global
 * variable $query if non-false.
 */
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

function format_number($num, $precisionoffset = 0) {
	$num = floatval($num);
	if($num < 0) {
		$sign = '-';
		$num = -$num;
	} else {
		$sign = '';
	}

	if($num < 10000) return $sign.round($num, max(0, 1 + $precisionoffset));
	else if($num < 1000000) {
		return $sign.round($num / 1000, max(0, 2 + $precisionoffset)).'k';
	} else {
		return $sign.round($num / 1000000, max(0, 3 + $precisionoffset)).'m';
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

function format_long_duration($seconds, $lessthanoneday = '1 day') {
	list($y, $m, $d) = explode('-', date('Y-m-d', time() - $seconds));
	list($Y, $M, $D) = explode('-', date('Y-m-d', time()));

	$years = $Y - $y;
	$months = $M - $m;
	$days = $D - $d;

	while($days < 0) {
		--$months;
		$days += 30;
	}
	while($months < 0) {
		--$years;
		$months += 12;
	}

	if($years == 0 && $months == 0 && $days == 0) {
		return $lessthanoneday;
	}

	$out = array();
	foreach(array('year' => $years, 'month' => $months, 'day' => $days) as $n => $q) {
		if($q == 0) continue;
		if($q == 1) $out[] = '1 '.$n;
		if($q > 1) $out[] = $q.' '.$n.'s';
	}

	$out = array_slice($out, 0, 2);
	return implode(', ', $out);
}

/**
 * Format the capacitor stability percentage or the time it lasts.
 *
 * @param $array array that has the same structure than the return
 * value of get_capacitor_stability().
 */
function format_capacitor($array) {
	list($rate, $is_stable, $data) = $array;

	$rate = round(-$rate * 1000, 1).' GJ/s';
	if($rate > 0) $rate = '+'.((string)$rate);

	if($is_stable) {
		return "Stable at ".round($data, 1)."% ($rate)";
	} else {
		return "Lasts ".format_duration($data)." ($rate)";
	}
}

/**
 * Format a resonance by displaying it as a resistance percentage.
 */
function format_resonance($resonance) {
	if($resonance < 0) return '100%';
	if($resonance > 1) return '0%';

	$percent = (1 - $resonance) * 100;

	return "<div>".number_format($percent, 1)."%<span class='bar' style='width: ".round($percent, 2)."%;'></span></div>";
}

/**
 * Get nickname or character name of current user.
 */
function get_name($a, &$rawname) {
	$name = $rawname = 'Anonymous';

	if(isset($a['accountid']) && $a['accountid'] > 0) {
		if(isset($a['apiverified']) && $a['apiverified'] === 't' &&
		   isset($a['characterid']) && $a['characterid'] > 0) {
			$name = '<span class="apiverified">'.htmlspecialchars($rawname = $a['charactername']).'</span>';
		} else {
			$name = '<span class="normalaccount">'.htmlspecialchars($rawname = $a['nickname']).'</span>';
		}
	}

	return $name;
}

/**
 * Format a character name (with a link to the profile).
 */
function format_character_name($a, $relative = '.', &$rawname = null) {
	$name = get_name($a, $rawname);
	$name = \Osmium\Flag\maybe_add_moderator_symbol($a, $name);
	return maybe_add_profile_link($a, $relative, $name);
}

function maybe_add_profile_link($a, $relative = '.', $name) {
	if(isset($a['accountid'])) {
		return "<a class='profile' href='$relative/profile/".$a['accountid']."'>$name</a>";
	} else {
		return $name;
	}
}

/**
 * Format a optimal/falloff to give a short result (like 5+3.2k).
 *
 * @param $ranges array as returned by
 * get_optimal_falloff_tracking_of_module().
 */
function format_short_range($ranges) {
	if(isset($ranges['maxrange'])) {
		$max = round($ranges['maxrange'] / 1000, $ranges['maxrange'] >= 10000 ? 0 : 1);
		return '≤'.$max.'k';
	} else if(isset($ranges['range'])) {
		$optimal = round($ranges['range'] / 1000, $ranges['range'] >= 10000 ? 0 : 1);

		if(isset($ranges['falloff'])) {
			$falloff = '+'.round($ranges['falloff'] / 1000, $ranges['falloff'] >= 10000 ? 0 : 1);
		} else {
			$falloff = '';
		}

		return $optimal.$falloff.'k';
	}

	return '';
}

/**
 * Format a optimal/falloff/tracking speed.
 *
 * @param $ranges array as returned by
 * get_optimal_falloff_tracking_of_module().
 */
function format_long_range($ranges) {
	$r = array();

	if(isset($ranges['range'])) {
		if($ranges['range'] >= 10000) {
			$range = round($ranges['range'] / 1000, 1).' km';
		} else {
			$range = round($ranges['range']).' m';
		}

		$r[] = "Optimal: ".$range;
	}

	if(isset($ranges['falloff'])) {
		if($ranges['falloff'] >= 10000) {
			$falloff = round($ranges['falloff'] / 1000, 1).' km';
		} else {
			$falloff = round($ranges['falloff']).' m';
		}

		$r[] = "Falloff: ".$falloff;
	}

	if(isset($ranges['trackingspeed'])) {
		$r[] = "Tracking: ".round_sd($ranges['trackingspeed'], 2)." rad/s";
	}

	if(isset($ranges['maxrange'])) {
		if($ranges['maxrange'] >= 10000) {
			$max = round($ranges['maxrange'] / 1000, 1).' km';
		} else {
			$max = round($ranges['maxrange']).' m';
		}

		$r[] = "Maximum range: ".$max;
	}

	return implode(";&#10;", $r);
}

/**
 * Round a number with a fixed number of significant digits.
 */
function round_sd($number, $digits = 0) {
	if($number == 0) $normalized = 0;
	else {
		$normalized = $number / ($m = pow(10, floor(log10($number))));
	}

	$normalized = number_format($normalized, $digits);

	return $normalized * $m;
}

function paginate($name, $perpage, $total, &$result, &$metaresult,
                  $pageoverride = null, $format = 'Showing rows %1-%2 of %3.') {
	if($pageoverride !== null) {
		$page = $pageoverride;
	} else if(isset($_GET[$name])) {
		$page = intval($_GET[$name]);
	} else {
		$page = 1;
	}

	$maxpage = max(1, ceil($total / $perpage));

	if($page < 1) $page = 1;
	if($page > $maxpage) $page = $maxpage;

	$offset = ($page - 1) * $perpage;
	$max = min($total, $offset + $perpage);

	$metaresult = "<p class='pagination'>\n";
	$metaresult .= str_replace(array('%1', '%2', '%3'), array($offset + 1, $max, $total), $format);
	$metaresult .= "\n</p>\n";

	$r ="<ol class='pagination'>\n";

	$inf = max(1, $page - 5);
	$sup = min($maxpage, $page + 4);
	$p = $_GET;

	if($page > 1) {
		$p[$name] = $page - 1;
		$q = http_build_query($p, '', '&amp;');
		$r .= "<li value='".($page - 1)."'><a title='go to previous page' href='?$q'>Previous</a></li>\n";
	} else {
		$r .= "<li class='dummy' value='".($page - 1)."'><span>Previous</span></li>\n";
	}

	for($i = $inf; $i <= $sup; ++$i) {
		if($i != $page) {
			$p[$name] = $i;
			$q = http_build_query($p, '', '&amp;');
			$r .= "<li value='$i'><a title='go to page $i' href='?$q'>$i</a></li>\n";
		} else {
			$r .= "<li class='current' value='$i'><span>$i</span></li>\n";
		}
	}

	if($page < $maxpage) {
		$p[$name] = $page + 1;
		$q = http_build_query($p, '', '&amp;');
		$r .= "<li value='".($page + 1)."'><a title='go to next page' href='?$q'>Next</a></li>\n";
	} else {
		$r .= "<li class='dummy' value='".($page + 1)."'><span>Next</span></li>\n";
	}

	$r .= "</ol>\n";

	$result = $r;
	return $offset;
}

function format_sanitize_md($markdowntext) {
	static $purifier = null;

	require_once 'HTMLPurifier.includes.php';
	require_once 'HTMLPurifier.auto.php';
	require_once \Osmium\ROOT.'/lib/markdown.php';

	$html = \Markdown($markdowntext);

	if($purifier === null) {
		$config = \HTMLPurifier_Config::createDefault();

		$config->set('Cache.SerializerPath', \Osmium\CACHE_DIRECTORY);
		$config->set('HTML.DefinitionID', 'Osmium '.\Osmium\VERSION.' '.\Osmium\ROOT);
		$config->set('HTML.DefinitionRev', 1);

		/* Forbid all classes. IDs are forbidden by default. */
		$config->set('Attr.AllowedClasses', array());

		/* (Try) detering bots. */
		$config->set('HTML.Nofollow', true);

		/* Disable inline CSS (to avoid seizure-inducing spam/trolls). */
		$config->set('CSS.AllowedProperties', array());

		$purifier = new \HTMLPurifier($config);
	}

	return $purifier->purify($html);
}
