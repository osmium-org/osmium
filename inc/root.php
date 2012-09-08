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

namespace Osmium;

function fatal($code, $message) {
	if(!headers_sent()) {
		http_response_code($code);
	}
	$message = htmlspecialchars($message)."<span id='caret'>_</span>";
	$code = $code.'!';

	$fcode = '';
	$len = strlen($code);
	for($i = 0; $i < $len; ++$i) {
		$fcode .= $code[$i].'&#8203;';
	}

	echo <<<EOFATAL
<!DOCTYPE html>
<head>
<title>$code / Osmium</title>
<style type='text/css'>
body {
	font-family: monospace;
	font-size: 2em;
	color: white;
	background-color: black;
}

div#bg {
	color: #222;
	overflow: hidden;
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
}

div#bg, div#bg > strong {
	user-select: none;
	-moz-user-select: none;
	-webkit-user-select: none;
	-ms-user-select: none;

	cursor: default;
}

div#bg > strong {
	color: red;
	outline: 1px solid red;
	font-weight: normal;
}

h1 {
	position: absolute;
	top: 50%;
	margin-top: -1em;
	left: 0;
	width: 100%;
	text-align: center;
}
</style>
</head>
<body>
<div unselectable='on' id='bg'></div>
<h1>
$message
</h1>
<script type='text/javascript'>
osmium_fancy_error = function(string) {
	var f = '';

	for(var i = 0; i < 5000; ++i) {
		if(Math.random() < 0.01) {
			f = f + "<strong unselectable='on'>" + string + "</strong>";
		} else {
			f = f + string;
		}
	}

	document.getElementById('bg').innerHTML = f;

	setTimeout(function() { osmium_fancy_error(string); }, Math.random() * 1500 + 500);
};

osmium_fancy_error("$fcode");
</script>
</body>
</html>

EOFATAL;

	die();
}

function printr($stuff) {
	echo "<pre>\n";
	print_r($stuff);
	echo "</pre>\n";
}

function fprintr($stuff) {
	static $f = null;
	if($f === null) $f = fopen('/tmp/osmium', 'wb');
	fwrite($f, print_r($stuff, true));
}

function get_ini_setting($key) {
	static $cnf = null;
	if($cnf === null) {
		if(!file_exists(INI_CONFIGURATION_FILE) || !is_readable(INI_CONFIGURATION_FILE)) {
			fatal(500, "Configuration file '".INI_CONFIGURATION_FILE."' not found or not readable.");
		}
		$cnf = parse_ini_file(INI_CONFIGURATION_FILE);
	}

	return isset($cnf[$key]) ? $cnf[$key] : null;
}

function get_osmium_version() {
	static $version = null;

	if($version === null) {
		$version = \Osmium\State\get_cache_memory('git_version', null);
		if($version === null) {
			$version = trim(shell_exec('cd '.escapeshellarg(ROOT)
			                           .'; (git describe --always --dirty 2>/dev/null || echo "unknown")'));
			\Osmium\State\put_cache_memory('git_version', $version, 600);
		}
	}

	return $version;
}

const SHORT_DESCRIPTION = 'the collaborative place to share your fittings!';

/** Bump this when static files (icons, JS snippets, etc.) are updated */
const STATICVER = 2;

/** Bump this when CSS files are updated */
const CSS_STATICVER = 2;

define(__NAMESPACE__.'\ROOT', realpath(__DIR__.'/../'));
define(__NAMESPACE__.'\INI_CONFIGURATION_FILE', ROOT.'/config.ini');
define(__NAMESPACE__.'\CACHE_DIRECTORY', ROOT.'/cache');

if(!is_dir(CACHE_DIRECTORY) || !is_writeable(CACHE_DIRECTORY)) {
	fatal(500, "Cache directory '".CACHE_DIRECTORY."' is not writeable.");
}

session_save_path(CACHE_DIRECTORY);
session_start();

require ROOT.'/inc/chrome.php';
require ROOT.'/inc/forms.php';
require ROOT.'/inc/db.php';
require ROOT.'/inc/eveapi.php';
require ROOT.'/inc/state.php';
require ROOT.'/inc/fit.php';
require ROOT.'/inc/dogma.php';
require ROOT.'/inc/flag.php';
require ROOT.'/inc/search.php';
require ROOT.'/inc/log.php';
require ROOT.'/inc/notification.php';
require ROOT.'/inc/reputation.php';

\Osmium\Forms\post_redirect_get();

if(isset($_POST['__osmium_login'])) {
	\Osmium\State\try_login();
}

if(!\Osmium\State\is_logged_in()) {
	\Osmium\State\try_recover();
}
