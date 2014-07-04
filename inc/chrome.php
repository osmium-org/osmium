<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

/* @deprecated */
define(
	__NAMESPACE__.'\XHTML',
	isset($_SERVER['HTTP_ACCEPT']) && (
		strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false
	)
);

const CONTENT_FILTER_MARKDOWN = 1;
const CONTENT_FILTER_SANITIZE_TRUST = 2;
const CONTENT_FILTER_SANITIZE = 4;
const CONTENT_FILTER_SANITIZE_PHRASING = 8;

const FATTRIBS_USE_RELOAD_TIME_FOR_CAPACITOR = 1;
const FATTRIBS_USE_RELOAD_TIME_FOR_DPS = 2;
const FATTRIBS_USE_RELOAD_TIME_FOR_TANK = 4;

const F_USED_SHOW_ABSOLUTE = 1;
const F_USED_SHOW_DIFFERENCE = 2;
const F_USED_SHOW_PERCENTAGE = 4;
const F_USED_SHOW_PROGRESS_BAR = 8;

require __DIR__.'/chrome-fit.php';


/**
 * Escape a string.
 *
 * @deprecated
 */
function escape($s) {
	if(\Osmium\Chrome\XHTML) {
		return htmlspecialchars($s, ENT_QUOTES | ENT_XML1);
	}

	/* ENT_HTML5 should be used, but some old old browsers won't
	 * understand &apos; */
	return htmlspecialchars($s, ENT_QUOTES);
}

/* Format a number with a fixed number of significant digits.
 *
 * @param $sd number of significant digits. If negative, keep all
 * significant digits.
 *
 * @param $min show this suffix at least (must be one of k, m or b)
 *
 * @deprecated use Formatter::formatKMB
 */
function format($number, $sd = 3, $min = '') {
	static $suffixes = [
		'b' => 1e9,
		'm' => 1e6,
		'k' => 1e3,
		'' => 1
	];

	if($number < 0) return '-'.format(-$number, $sd, $min);

	if($sd < 0) return number_format($number / $suffixes[$min]).$min;

	foreach($suffixes as $s => $limit) {
		if($number >= $limit || $s === $min) {
			return round_sd($number / $limit, $sd - 1).$s;
		}
	}
}

/** Much better than the junk trim() function from the PHP library */
function trim($s) {
	return preg_replace('%^\p{Z}*(.*?)\p{Z}*$%u', '$1', $s);
}

/** @deprecated */
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

/** @deprecated use Formatter::formatExactInteger */
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

/** @deprecated use Formatter::formatDuration(_, true) */
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

/** @deprecated use Formatter::formatDuration() */
function format_long_duration($seconds, $precision = 6) {
	list($y, $m, $d, $h, $i, $s) = explode('-', date('Y-m-d-H-i-s', 0));
	list($Y, $M, $D, $H, $I, $S) = explode('-', date('Y-m-d-H-i-s', $seconds));

	$years = $Y - $y;
	$months = $M - $m;
	$days = $D - $d;
	$hours = $H - $h;
	$minutes = $I - $i;
	$seconds = $S - $s;

	$out = array(
		'year' => $years,
		'month' => $months,
		'day' => $days,
		'hour' => $hours,
		'minute' => $minutes,
		'second' => $seconds,
	);
	foreach($out as $k => $v) {
		if($v == 0) unset($out[$k]);
		else if($v === 1) $out[$k] = $v.' '.$k;
		else $out[$k] = $v.' '.$k.'s';
	}
	$out = array_slice($out, 0, $precision);

	$c = count($out);
	if($c === 0) {
		return 'less than 1 '.$k;
	}
	if($c >= 2) {
		$s = array_pop($out);
		$m = array_pop($out);
		$out[] = $m.' and '.$s;
	}

	return implode(', ', $out);
}

/** @deprecated use Formatter::formatExactInteger */
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

/**
 * @deprecated
 * Get nickname or character name of current user.
 */
function get_name($a, &$rawname) {
	$name = $rawname = 'Anonymous';

	if(isset($a['accountid']) && $a['accountid'] > 0) {
		if(isset($a['apiverified']) && $a['apiverified'] === 't' &&
		   isset($a['characterid']) && $a['characterid'] > 0) {
			$name = '<span class="apiverified">'.escape($rawname = $a['charactername']).'</span>';
		} else {
			$name = '<span class="normalaccount">'.escape($rawname = $a['nickname']).'</span>';
		}
	}

	return $name;
}

/**
 * @deprecated
 * Format a character name (with a link to the profile).
 */
function format_character_name($a, $relative = '.', &$rawname = null) {
	$name = get_name($a, $rawname);
	$name = \Osmium\Flag\maybe_add_moderator_symbol($a, $name);
	return maybe_add_profile_link($a, $relative, $name);
}

/** @deprecated */
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

function make_htmlpurifier_config() {
	require_once 'HTMLPurifier.includes.php';
	require_once 'HTMLPurifier.auto.php';

	return \HTMLPurifier_Config::createDefault();
}

function finalize_htmlpurifier_config(\HTMLPurifier_Config $config, $name, $rev) {
	$config->set('Cache.SerializerPath', \Osmium\CACHE_DIRECTORY);
	$config->set('HTML.DefinitionID', 'Osmium-'.$name);
	$config->set('HTML.DefinitionRev', $rev);

	$def = $config->maybeGetRawHTMLDefinition();

	/* Check if cached definition is available */
	if($def === null) return $config;

	$def->addElement(
		's',
		'Inline',
		'Inline',
		'Common',
		[]
	);

	return $config;
}

function make_untrusted_htmlpurifier_config() {
	$config = make_htmlpurifier_config();

	/* For convenience */
	$config->set('AutoFormat.Linkify', true);

	/* Better safe than sorry */
	$config->set('Attr.AllowedClasses', array());
	$config->set('CSS.AllowedProperties', array());
	$config->set('HTML.Nofollow', true);

	/* Disallow pictures. Better safe than sorry, as this makes CSRF
	 * attacks a little harder. */
	$config->set('URI.DisableResources', true);

	/* Do not leak referrers on external links. Also an incentive for
	 * bad people as this makes spamming links less effective. */
	$config->set('URI.Munge', rtrim(\Osmium\get_ini_setting('relative_path'), '/').'/internal/redirect/%t?%s');

	/* Using hashes to verify redirected URIs hopefully prevent
	 * redirect loops and also prevent others from freeloading off
	 * this nifty redirection page. */
	$config->set('URI.MungeSecretKey', \Osmium\get_ini_setting('uri_munge_secret'));

	return $config;
}

function sanitize_html_trust($html) {
	static $purifier = null;

	if($purifier === null) {
		$config = make_htmlpurifier_config();
		$purifier = new \HTMLPurifier(finalize_htmlpurifier_config($config, 'trusted', 2));
	}

	return $purifier->purify($html);
}

function sanitize_html($html) {
	static $purifier = null;

	if($purifier === null) {
		$config = make_untrusted_htmlpurifier_config();
		$purifier = new \HTMLPurifier(finalize_htmlpurifier_config($config, 'untrusted-full', 3));
	}

	return $purifier->purify($html);
}

function sanitize_html_phrasing($html) {
	static $purifier = null;

	if($purifier === null) {
		$config = make_untrusted_htmlpurifier_config();

		/* Only allow some inline elements typically used in phrasing content. */
		$config->set(
			'HTML.AllowedElements',
			'a, abbr, b, cite, code, del, em, i, ins, kbd, q, s, samp, small, span, strong, sub, sup'
		);

		$purifier = new \HTMLPurifier(finalize_htmlpurifier_config($config, 'untrusted-phrasing', 3));
	}

	return $purifier->purify($html);
}

function format_md($markdowntext) {
	require_once \Osmium\ROOT.'/lib/markdown.php';
    return \Markdown($markdowntext);
}

/* @deprecated use filter_content() */
function format_sanitize_md($markdowntext) {
	return sanitize_html(format_md($markdowntext));
}

/* Filter some content.
 *
 * @param $filter a bitmask of CONTENT_FILTER_* constants.
 */
function filter_content($content, $filter) {
	if($filter & CONTENT_FILTER_MARKDOWN)
		$content = format_md($content);

	if($filter & CONTENT_FILTER_SANITIZE_TRUST)
		$content = sanitize_html_trust($content);

	if($filter & CONTENT_FILTER_SANITIZE)
		$content = sanitize_html($content);

	if($filter & CONTENT_FILTER_SANITIZE_PHRASING)
		$content = sanitize_html_phrasing($content);

	return $content;
}

function format_showinfo_links($desc) {
	$desc = preg_replace_callback(
		'%<a href=showinfo:(?<typeid>[1-9][0-9]*)(// ?(?<itemid>[1-9][0-9]*))?>%',
		function($match) {
			return "<a o-rel-href='/db/type/{$match['typeid']}'>";
		},
		$desc
	);

	/* XXX: this is ultimately flawed, as it will break on nested
	 * <url>s (hopefully this NEVER appears in type descriptions… */
	$desc = preg_replace_callback(
		'%<url=showinfo:(?<typeid>[1-9][0-9]*)(// ?(?<itemid>[1-9][0-9]*))?>(?<content>.*?)</url>%sU',
		function($match) {
			return "<a o-rel-href='/db/type/{$match['typeid']}'>".$match['content']."</a>";
		},
		$desc
	);

	return $desc;
}

function format_type_description($desc) {
	$desc = format_showinfo_links($desc);
	return sanitize_html(format_md(nl2br($desc, true)));
}

function format_isk($isk, $withunit = true) {
	if($isk >= 10000000000) {
		$isk = round($isk / 1000000000, 2).'b';
	} else if($isk >= 1000000000) {
		$isk = round($isk / 1000000000, 3).'b';
	} else if($isk > 100000000) {
		$isk = round($isk / 1000000, 1).'m';
	} else if($isk > 10000000) {
		$isk = round($isk / 1000000, 2).'m';
	} else if($isk > 1000000) {
		$isk = round($isk / 1000000, 3).'m';
	} else {
		$isk = round($isk / 1000, 1).'k';
	}

	return $isk.($withunit ? ' ISK' : '');
}

function truncate_string($s, $length, $fill = '…') {
	if(mb_strlen($s) > $length) {
		$s = mb_substr($s, 0, $length - mb_strlen($fill)).$fill;
	}

	return $s;
}

/* @deprecated */
function sprite($relative, $alt, $grid_x, $grid_y, $grid_width, $grid_height = null, $width = null, $height = null) {
	if($grid_height === null) $grid_height = $grid_width;
	if($width === null) $width = $grid_width;
	if($height === null) $height = $width;

	$posx = $grid_x * $width;
	$posy = $grid_y * $height;
	$imgwidth = $width / $grid_width * 1024;
	$imgheight = $height / $grid_height * 1024;

	$alt = escape($alt);

	return "<span class='mainsprite' style='width: {$width}px; height: {$height}px;'><img src='{$relative}/static-".\Osmium\STATICVER."/icons/sprite.png' alt='{$alt}' title='{$alt}' style='width: {$imgwidth}px; height: {$imgheight}px; top: -{$posx}px; left: -{$posy}px;' /></span>";
}

function format_effect_category($id) {
	/* Taken from https://github.com/DarkFenX/Eos/blob/master/const/eve.py#L186 */
	static $map = [
		0 => 'passive',
		1 => 'active',
		2 => 'target',
		3 => 'area',
		4 => 'online',
		5 => 'overload',
		6 => 'dungeon',
		7 => 'system',
	];

	return isset($map[$id]) ? $map[$id] : "unknown ({$id})";
}

/**
 * Ends the script and outputs JSON-encoded data.
 *
 * @param $data the PHP object/array/value to encode.
 *
 * @param $flags flags to pass to json_encode().
 */
function return_json($data, $flags = 0) {
	header('Content-Type: application/json');
	die(json_encode($data, $flags));
}
