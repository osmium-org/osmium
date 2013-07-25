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

const F_USED_SHOW_ABSOLUTE = 1;
const F_USED_SHOW_DIFFERENCE = 2;
const F_USED_SHOW_PERCENTAGE = 4;
const F_USED_SHOW_PROGRESS_BAR = 8;

require __DIR__.'/chrome-layout.php';
require __DIR__.'/chrome-fit.php';

/** Much better than the junk trim() function from the PHP library */
function trim($s) {
	return preg_replace('%^\p{Z}*(.*?)\p{Z}*$%u', '$1', $s);
}

function format_small_progress_bar($percent, $fill = true, $ltr = true, $maxoverflow = 10) {
	$over = min($maxoverflow, max(0, $percent - 100));

	$bclass = $ltr ? 'lbar' : 'bar';
	$oclass = $ltr ? 'bar' : 'lbar';
	if($percent > 100) {
		$c = 'p100';
	} else if($percent >= 95) {
		$c = 'p95';
	} else if($percent >= 80) {
		$c = 'p80';
	} else {
		$c = 'p00';
	}
	$progress = max(0, min(100, $percent));

	$ret = "<span class='{$bclass} {$c}' style='width: ".round($progress, 2)."%;'></span>";
	if($over > 0) {
		$over = round($over, 2).'%';
		$ret .= "<span class='{$oclass} {$c} over' style='width: {$over}; "
			.($ltr ? 'right' : 'left').": -{$over};'></span>";
	}
	if($fill) {
		$ret .= "<span class='bar fill'></span>";
	}

	return $ret;
}

function format_used($rawused, $rawtotal, $unit, $digits, $opts = F_USED_SHOW_ABSOLUTE) {
	if($rawtotal == 0 && $rawused == 0) {
		return '0';
	}

	$lines = array();
	if($unit !== '') $unit = ' '.$unit;

	$used = format_number($rawused).' / '.format_number($rawtotal).$unit;
	$diff = ($rawtotal >= $rawused) ?
		format_number($rawtotal - $rawused).$unit.' left' :
		format_number($rawused - $rawtotal).$unit.' over';

	if($opts & F_USED_SHOW_ABSOLUTE) {
		$lines[] = "<span title='".htmlspecialchars($diff, ENT_QUOTES)."'>"
			.htmlspecialchars($used)
			."</span>";
	}
	if($opts & F_USED_SHOW_DIFFERENCE) {
		$lines[] = "<span title='".htmlspecialchars($used, ENT_QUOTES)."'>"
			.htmlspecialchars($diff)
			."</span>";
	}

    $percent = $rawtotal > 0 ? (100 * $rawused / $rawtotal) : 100;
	if($opts & F_USED_SHOW_PERCENTAGE) {
		$lines[] = ($rawtotal == 0 ? '∞' : round($percent, $digits)).' %';
	}

	$ret = implode("<br />", $lines);

	if($opts & F_USED_SHOW_PROGRESS_BAR) {
		$ret .= format_small_progress_bar($percent);
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

function format_relative_date($date, $now = null) {
	if($now === null) $now = time();
	$before = "<time datetime='".date('c', $date)."'>";
	$after = '</time>';

	if($date > $now || $date < ($now - 2 * 86400)) {
		return $before.date('Y-m-d', $date).$after;
	}

	$duration = $now - $date;

	if($duration < 2) return $before."less than a second ago".$after;

	$s = $duration % 60;
	$m = (($duration - $s) / 60) % 60;
	$h = (($duration - $s - 60 * $m) / 3600) % 24;
	$d = ($duration - $s - 60 * $m - 3600 * $h) / 86400;

	$a = array_filter(array(
		                  'd' => $d,
		                  'h' => $h,
		                  'm' => $m,
		                  's' => $s
		                  ));

	$ret = array();
	foreach($a as $k => $v) {
		$ret[] = $v.$k;
	}

	return $before.implode(' ', array_slice($ret, 0, 2)).' ago'.$after;
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
		return array('stable at '.round($data, 1)."%", $rate);
	} else {
		return array(format_duration($data), $rate);
	}
}

/**
 * Format a resonance by displaying it as a resistance percentage.
 */
function format_resonance($resonance) {
	if($resonance < 0) return '100%';
	if($resonance > 1) return '0%';

	$percent = (1 - $resonance) * 100;

	return "<div>"
		.number_format($percent, 1)."%"
		.format_small_progress_bar($percent, false, false, 0)
		."</div>";
}

function format_integer($i, $exact = true) {
	if($exact) {
		return number_format($i);
	}

	if($i >= 1e9) {
		return number_format($i / 1e9, 3).'b';
	}

	if($i >= 1e6) {
		return number_format($i / 1e6, 3).'m';
	}

	if($i >= 1e4) {
		return number_format($i / 1e3, 2).'k';
	}

	return number_format($i);
}

function format_reputation($rep) {
	if($rep <= 0) $rep = 0;
	$rep = format_integer($rep, false);
	return "<span class='reputation' title='reputation'>{$rep}</span>";
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

function maybe_add_profile_link($a, $relative, $name) {
	if(isset($a['accountid'])) {
		return "<a class='profile' href='$relative/profile/".$a['accountid']."'>$name</a>";
	} else {
		return $name;
	}
}

/**
 * Format a optimal/falloff to give a short result (like 5+3.2k).
 *
 * @param An array containing ranges, using the format generated by
 * get_module_interesting_attributes().
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
 * @param An array containing ranges, using the format generated by
 * get_module_interesting_attributes().
 */
function format_long_range($ranges) {
	$r = array();

	if(isset($ranges['range'])) {
		$r[] = (isset($ranges['falloff']) ? "Optimal" : "Range").": ".format_range($ranges['range']);
	}

	if(isset($ranges['falloff'])) {
		$r[] = "Falloff: ".format_range($ranges['falloff']);
	}

	if(isset($ranges['trackingspeed'])) {
		$r[] = "Tracking: ".round_sd($ranges['trackingspeed'], 2)." rad/s";
	}

	if(isset($ranges['maxrange'])) {
		$r[] = "Maximum range: ".format_range($ranges['maxrange']);
	}

	return implode(";\n", $r);
}

/** Format a range in meters. */
function format_range($meters, $shortfmt = false) {
	if($shortfmt) {
		return round($meters / 1000, 1).'k';
	} else {
		if($meters >= 10000) {
			return round($meters / 1000, 1).' km';
		} else {
			return round($meters).' m';
		}
	}
}

/**
 * Round a number with a fixed number of significant digits.
 */
function round_sd($number, $digits = 0) {
	if($number == 0) return 0;
	else {
		$normalized = $number / ($m = pow(10, floor(log10($number))));
	}

	$normalized = number_format($normalized, $digits);

	return $normalized * $m;
}

/**
 * Generate pagination links and get the offset of the current page.
 *
 * @param $name name of the $_GET parameter
 * @param $perpage number of elements per page
 * @param $total total number of elements
 * @param $result where the pagination links are stored
 * @param $metaresult where the pagination info is stored
 * @param $pageoverride force a certain page number instead of $_GET default
 * @param $format format of $metaresult; %1, %2 and %3 will be replaced
 * @param $anchor optional anchor to append to the generated link URIs
 *
 * @return offset of the current page
 */
function paginate($name, $perpage, $total, &$result, &$metaresult,
                  $pageoverride = null, $format = 'Showing rows %1-%2 of %3.',
                  $anchor = '') {
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

	$replacement = ($total > 0) ? array($offset + 1, $max, $total) : array(0, 0, 0);
	$metaresult = "<p class='pagination'>\n";
	$metaresult .= str_replace(array('%1', '%2', '%3'), $replacement, $format);
	$metaresult .= "\n</p>\n";

	if($maxpage == 1) {
		$result = '';
		return $offset;
	}

	$r ="<ol class='pagination'>\n";

	$inf = max(1, $page - 5);
	$sup = min($maxpage, $page + 4);
	$p = $_GET;

	if($page > 1) {
		$p[$name] = $page - 1;
		$q = http_build_query($p, '', '&amp;');
		$r .= "<li value='".($page - 1)."'><a title='go to previous page' href='?$q$anchor'>Previous</a></li>\n";
	} else {
		$r .= "<li class='dummy' value='".($page - 1)."'><span>Previous</span></li>\n";
	}

	for($i = $inf; $i <= $sup; ++$i) {
		if($i != $page) {
			$p[$name] = $i;
			$q = http_build_query($p, '', '&amp;');
			$r .= "<li value='$i'><a title='go to page $i' href='?$q$anchor'>$i</a></li>\n";
		} else {
			$r .= "<li class='current' value='$i'><span>$i</span></li>\n";
		}
	}

	if($page < $maxpage) {
		$p[$name] = $page + 1;
		$q = http_build_query($p, '', '&amp;');
		$r .= "<li value='".($page + 1)."'><a title='go to next page' href='?$q$anchor'>Next</a></li>\n";
	} else {
		$r .= "<li class='dummy' value='".($page + 1)."'><span>Next</span></li>\n";
	}

	$r .= "</ol>\n";

	$result = $r;
	return $offset;
}

function sanitize_html($html) {
	static $purifier = null;
	require_once 'HTMLPurifier.includes.php';
	require_once 'HTMLPurifier.auto.php';

	if($purifier === null) {
		$config = \HTMLPurifier_Config::createDefault();

		$config->set('Cache.SerializerPath', \Osmium\CACHE_DIRECTORY);
		$config->set('HTML.DefinitionID', 'Osmium-full');
		$config->set('HTML.DefinitionRev', 1);

		$config->set('Attr.AllowedClasses', array());
		$config->set('HTML.Nofollow', true);
		$config->set('CSS.AllowedProperties', array());

		$purifier = new \HTMLPurifier($config);
	}

	return $purifier->purify($html);
}

function sanitize_html_phrasing($html) {
	static $purifier = null;
	require_once 'HTMLPurifier.includes.php';
	require_once 'HTMLPurifier.auto.php';

	if($purifier === null) {
		$config = \HTMLPurifier_Config::createDefault();

		$config->set('Cache.SerializerPath', \Osmium\CACHE_DIRECTORY);
		$config->set('HTML.DefinitionID', 'Osmium-phrasing');
		$config->set('HTML.DefinitionRev', 1);

		$config->set('Attr.AllowedClasses', array());
		$config->set('HTML.Nofollow', true);
		$config->set('CSS.AllowedProperties', array());
		$config->set('HTML.AllowedElements', 'a, abbr, b, cite, code, del, em, i, ins, kbd, q, s, samp, small, span, strong, sub, sup');

		$purifier = new \HTMLPurifier($config);
	}

	return $purifier->purify($html);
}

function format_md($markdowntext) {
	require_once \Osmium\ROOT.'/lib/markdown.php';
    return \Markdown($markdowntext);
}

function format_sanitize_md($markdowntext) {
	return sanitize_html(format_md($markdowntext));
}

function format_sanitize_md_phrasing($markdowntext) {
	return sanitize_html_phrasing(format_md($markdowntext));
}

function format_isk($isk, $withunit = true) {
	if($isk >= 10000000000) {
		$isk = round($isk / 1000000000, 2).'B';
	} else if($isk >= 1000000000) {
		$isk = round($isk / 1000000000, 3).'B';
	} else if($isk > 100000000) {
		$isk = round($isk / 1000000, 1).'M';
	} else if($isk > 10000000) {
		$isk = round($isk / 1000000, 2).'M';
	} else if($isk > 1000000) {
		$isk = round($isk / 1000000, 3).'M';
	} else {
		$isk = round($isk / 1000, 1).'K';
	}

	return $isk.($withunit ? ' ISK' : '');
}

function truncate_string($s, $length, $fill = '…') {
	if(mb_strlen($s) > $length) {
		$s = mb_substr($s, 0, $length - mb_strlen($fill)).$fill;
	}

	return $s;
}

function format_number_with_unit($number, $unitid, $unitdisplayname) {
	switch((int)$unitid) {

	case 1: /* Meters */
		if($number >= 10000) {
			$number /= 1000;
			$unitdisplayname = 'km';
		} else {
			$unitdisplayname = 'm';
		}
		break;

	case 101: /* Milliseconds */
		$number /= 1000;
		$unitdisplayname = 's';
		break;

	case 104: /* Multiplier */
		$unitdisplayname = 'x';
		break;

	case 108: /* Inverse absolute percent */
		$number = (1 - $number) * 100;

	case 105: /* Percentage */
	case 121: /* Real percentage */
		$unitdisplayname = '%';
		break;

	case 111: /* Inversed modifier percent */
		$number = 2 - $number;

	case 109: /* Modifier percent */
		$number -= 1;
		$number *= 100;

	case 124: /* Modifier relative percent */
		$unitdisplayname = '%';
		$rounded = ($number >= 0 ? '+' : '').round_sd($number, 2);
		break;

	case 127: /* Absolute percent */
		$unitdisplayname = '%';
		$number *= 100;
		break;

	case 112: /* Rotation speed */
		$rounded = round_sd($number, 4);
		break;

	case 115: /* Group ID */
		$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
			'SELECT groupname, typeid FROM eve.invgroups
			LEFT JOIN eve.invtypes
			ON invtypes.groupid = invgroups.groupid AND invtypes.published = true
			WHERE invgroups.groupid = $1 ORDER BY typeid ASC
			LIMIT 1',
			array($number)
		));
		if($row !== false) {
			$image = '';
			if($row[1] !== null) {
				$image = "<img src='http://image.eveonline.com/Type/".$row[1]."_64.png' alt='' /> ";
			}
			return $image.htmlspecialchars($row[0]);
		}
		$unitdisplayname = 'Group ID';
		break;

	case 116: /* Type ID */
		$typename = \Osmium\Fit\get_typename($number);
		if($typename !== false) {
			return "<img src='http://image.eveonline.com/Type/".$number."_64.png' alt='' /> "
				.htmlspecialchars($typename);
		}
		$unitdisplayname = 'Type ID';
		break;

	case 117: /* Size class */
		if($number == 1) return 'Small';
		if($number == 2) return 'Medium';
		if($number == 3) return 'Large';
		if($number == 4) return 'XLarge';
		break;

	case 137: /* Boolean */
		if($number == 0) return 'False';
		if($number == 1) return 'True';
		break;

	case 139: /* Bonus */
		$rounded = (($number >= 0) ? '+' : '').round_sd($number, 2);
		break;

	case 140: /* Level */
		return 'Level '.$number;

	case 142: /* Sex */
		if($number == 1) return 'Male';
		if($number == 2) return 'Unisex';
		if($number == 3) return 'Female';
		break;

	}

	if(!isset($rounded)) {
		$n = number_format($number, 3);
		list($rounded, $dec) = explode('.', $n);
		$dec = rtrim($dec, '0');
		if($dec) $rounded .= '.'.$dec;
	}

	return '<span title="'.sprintf("%.14f", $number).'">'
		.$rounded.' '.htmlspecialchars($unitdisplayname)
		.'</span>';
}

function sprite($relative, $alt, $grid_x, $grid_y, $grid_width, $grid_height = null, $width = null, $height = null) {
	if($grid_height === null) $grid_height = $grid_width;
	if($width === null) $width = $grid_width;
	if($height === null) $height = $width;

	$posx = $grid_x * $width;
	$posy = $grid_y * $height;
	$imgwidth = $width / $grid_width * 1024;
	$imgheight = $height / $grid_height * 1024;

	$alt = htmlspecialchars($alt, ENT_QUOTES);

	return "<div class='mainsprite' style='width: {$width}px; height: {$height}px;'><img src='{$relative}/static-".\Osmium\STATICVER."/icons/sprite.png' alt='{$alt}' title='{$alt}' style='width: {$imgwidth}px; height: {$imgheight}px; top: -{$posx}px; left: -{$posy}px;' /></div>";
}
