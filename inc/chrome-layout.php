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

/** Relative path to Osmium root */
$__osmium_chrome_relative = '.';

/** Javascript snippets to add just before </body> */
$__osmium_js_snippets = array();

/** Javascript code to add just before </body> */
$__osmium_js_code = '';

/**
 * Print the page header. Nothing should be printed before this call
 * (except header() calls).
 *
 * @param $title the title of the page (will only be put in <title>),
 * no escaping done.
 *
 * @param $relative the relative path to the main page.
 *
 * @param $add_head optional HTML code to add just before </head>,
 * unescaped.
 */
function print_header($title = '', $relative = '.', $index = true, $add_head = '') {
	global $__osmium_chrome_relative;
	$__osmium_chrome_relative = $relative;

	if($title == '') {
		$title = 'Osmium / '.\Osmium\SHORT_DESCRIPTION;
	} else {
		$title .= ' / Osmium';
	}

	$xhtml = isset($_SERVER['HTTP_ACCEPT']) && 
		(strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false);

	$notifications = \Osmium\Notification\get_new_notification_count();
	if($notifications > 0) {
		$title = "({$notifications}) ".$title;
	}

	if($xhtml) {
		header('Content-Type: application/xhtml+xml; charset=utf-8');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	}
	echo "<!DOCTYPE html>\n<html xmlns='http://www.w3.org/1999/xhtml'>\n<head>\n";
	if(!$xhtml) echo "<meta charset='UTF-8' />\n";
	if(!$index) echo "<meta name='robots' content='noindex' />\n";
	echo "<link href='http://fonts.googleapis.com/css?family=Droid+Serif:400,400italic,700,700italic|Droid+Sans:400,700|Droid+Sans+Mono' rel='stylesheet' type='text/css' />\n";

	/* Guess the current theme and put it first (to avoid blinking). */
	static $themes = array('Dark' => 'dark.css', 'Light' => 'light.css');
	$curtheme = isset($_COOKIE['t']) && isset($themes[$_COOKIE['t']]) ? $_COOKIE['t'] : 'Dark';

	echo "<link rel='stylesheet' href='$relative/static-".\Osmium\CSS_STATICVER."/".$themes[$curtheme]."' title='".$curtheme."' type='text/css' />\n";
	foreach($themes as $t => $f) {
		if($curtheme === $t) continue;

		echo "<link rel='alternate stylesheet' href='$relative/static-".\Osmium\CSS_STATICVER."/$f' title='$t' type='text/css' />\n";
	}

	echo "<link rel='icon' type='image/png' href='$relative/static-".\Osmium\STATICVER."/favicon.png' />\n";
	echo "<title>$title</title>\n";
	echo "$add_head</head>\n<body>\n<div id='wrapper'>\n";

	echo "<nav>\n";
	\Osmium\State\print_login_or_logout_box($relative, $notifications);

	echo "<ul>\n";
	echo get_navigation_link($relative.'/', "Main page");
	echo get_navigation_link($relative.'/search', "Search loadouts");
	echo get_navigation_link($relative.'/new', "New loadout");
	if(\Osmium\State\is_logged_in()) {
		$a = \Osmium\State\get_state('a');

		echo get_navigation_link($relative.'/import', "Import loadouts");
		echo get_navigation_link($relative.'/settings', "Settings");

		if($a['ismoderator'] === 't') {
			echo get_navigation_link($relative.'/moderation/', \Osmium\Flag\MODERATOR_SYMBOL);
		}

		\Osmium\Chrome\print_js_snippet('notifications');
		\Osmium\Chrome\print_js_code('$(function() { osmium_notifications("'
		                             .str_replace('"', '\"', $relative).'"); });');
	} else {
		echo get_navigation_link($relative.'/import', "Import loadout");
	}
	echo "</ul>\n";

	echo "</nav>\n";

	\Osmium\Chrome\print_js_snippet('persistent_theme');
}

/**
 * Print the page footer. As this closes the <html> tag, nothing
 * should be printed after calling this.
 */
function print_footer() {
	global $__osmium_chrome_relative, $__osmium_js_snippets, $__osmium_js_code;

	echo "<div id='push'></div>\n</div>\n<footer>\n";
	echo "<p><a href='http://artefact2.com/osmium/'><strong>Osmium ".\Osmium\get_osmium_version()." @ ".gethostname()."</strong></a>  — <a href='https://github.com/Artefact2/osmium'>Browse source</a> (<a href='http://www.gnu.org/licenses/agpl.html'>AGPLv3</a>)</p>";
	echo "</footer>\n";

	echo "<script type='application/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js'></script>\n";
	echo "<script type='application/javascript' src='//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js'></script>\n";

	if(count($__osmium_js_snippets) > 0) {
		$name = 'JS_'.sha1(implode("\n", $__osmium_js_snippets)).'.js';
		$cache = '/static/cache/'.$name;
		$cachefile = \Osmium\ROOT.$cache;
		$cacheuri = $__osmium_chrome_relative.'/static-'.\Osmium\STATICVER.'/cache/'.$name;

		if(!file_exists($cachefile)) {
			if($ujs = \Osmium\get_ini_setting('use_uglifyjs')) {
				/* Concatenate & minify */
				shell_exec('cat '.
				           implode(' ', array_map('escapeshellarg', $__osmium_js_snippets))
				           .' | uglifyjs -nc -o '
				           .escapeshellarg($cachefile));
			}

			if(!$ujs || !file_exists($cachefile)) {
				/* Not using UglifyJS, or UglifyJS failed for some reason */
				/* Just concatenate the files together */

				file_put_contents($cachefile,
				                  implode("\n", array_map('file_get_contents', $__osmium_js_snippets)));
			}
		}

		echo "<script type='application/javascript' src='".htmlspecialchars($cacheuri, ENT_QUOTES)."'></script>\n";
	}

	if($__osmium_js_code !== '') {
		echo "<script type='application/javascript'>\n";
		echo "//<![CDATA[\n";
		/* Properly "escape" (for lack of a better word) CDATA
		 * terminators. This looks complicated but actually this is
		 * all it requires to do it properly. */
		echo str_replace(']]>', ']]]]><![CDATA[>', $__osmium_js_code);
		echo "//]]>\n";
		echo "</script>\n";
	}

	echo "</body>\n</html>\n";
}

function get_navigation_link($dest, $label) {
	if(is_current($dest)) {
		return "<li><strong><a href='$dest'>$label</a></strong></li>\n";
	}

	return "<li><a href='$dest'>$label</a></li>\n";
}

/** @internal */
function is_current($relativeuri) {
	$relativeparts = explode('/', explode('?', $relativeuri, 2)[0]);
	$absoluteparts = explode('/', $uri = explode('?', $_SERVER['REQUEST_URI'], 2)[0]);

	foreach($relativeparts as $p) {
		if($p === '.') {
			array_pop($absoluteparts);
			$absoluteparts[] = '';
		} else if($p === '..') {
			if(array_pop($absoluteparts) === '') {
				array_pop($absoluteparts);
				$absoluteparts[] = '';
			}
		} else {
			$absoluteparts[] = $p;
		}
	}

	return ltrim(implode('/', $absoluteparts), '/') == ltrim($uri, '/');
}

/**
 * Print a Javascript snippet in the current document.
 *
 * @param $js_file name of the snippet, witout the ".js" extension
 * (assumed to be in /src/snippets/).
 */
function print_js_snippet($js_file) {
	global $__osmium_js_snippets;

	$__osmium_js_snippets[] = \Osmium\ROOT.'/src/snippets/'.$js_file.'.js';
}

/**
 * Print Javascript code in the current document.
 */
function print_js_code($code) {
	global $__osmium_js_code;

	$__osmium_js_code .= trim($code)."\n";
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
