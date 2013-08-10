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

namespace Osmium;

/* This file will be included before inc/root.php and is the only file
 * included before dispatching the URI. */

$__start = microtime(true);

define(__NAMESPACE__.'\ROOT', realpath(__DIR__.'/../'));
define(__NAMESPACE__.'\INI_CONFIGURATION_FILE', ROOT.'/config.ini');

/* Also used in try_get_fit_from_remote_format() */
const PUBLIC_LOADOUT_RULE = '%^/loadout/(?<loadoutid>[1-9][0-9]*)(R(?<revision>[1-9][0-9]*))?(P(?<preset>[0-9]+))?(C(?<chargepreset>[0-9]+))?(D(?<dronepreset>[0-9]+))?(/booster/(?<fleet>(fleet|wing|squad)))?$%D';

/* Also used in try_get_fit_from_remote_format() */
const PRIVATE_LOADOUT_RULE = '%^/loadout/private/(?<loadoutid>[1-9][0-9]*)(R(?<revision>[1-9][0-9]*))?(P(?<preset>[0-9]+))?(C(?<chargepreset>[0-9]+))?(D(?<dronepreset>[0-9]+))?/(?<privatetoken>0|[1-9][0-9]*)(/booster/(?<fleet>(fleet|wing|squad)))?$%D';

/* Also used in try_get_fit_from_remote_format() */
const NEW_LOADOUT_RULE = '%^/new(/(?<token>0|[1-9][0-9]*))?$%D';

function printr($stuff) {
	echo "<pre>\n";
	print_r($stuff);
	echo "</pre>\n";
}

function debug() {
	$f = fopen('/tmp/osmium', 'ab');
	ob_start();

	echo "\n\n===== ".date('c')."  =====\n";
	debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	echo "\n";

	foreach(func_get_args() as $thing) var_dump($thing);

	fwrite($f, ob_get_clean());
	fflush($f);
	fclose($f);
}

function get_ini_setting($key) {
	static $cnf = null;
	if($cnf === null) {
		if(!file_exists(INI_CONFIGURATION_FILE) || !is_readable(INI_CONFIGURATION_FILE)) {
			fatal(500, "Configuration file '".INI_CONFIGURATION_FILE."' not found or not readable.");
		}
		/* Maybe cache this in memory? Parsing ini files is pretty
		 * much as fast as deserialization and the file will be in
		 * filesystem cache anyway */
		$cnf = parse_ini_file(INI_CONFIGURATION_FILE);
	}

	return isset($cnf[$key]) ? $cnf[$key] : null;
}

function get_osmium_version() {
	$version = \Osmium\State\get_cache_memory('git_version', null);
	if($version !== null) return $version;

	$sem = \Osmium\State\semaphore_acquire('git_version');
	if($sem === false) return 'unknown';

	$version = \Osmium\State\get_cache_memory('git_version', null);
	if($version !== null) {
		\Osmium\State\semaphore_release($sem);
		return $version;
	}

	$version = trim(shell_exec(
		'cd '.escapeshellarg(ROOT)
		.'; (git describe --always --dirty 2>/dev/null || echo "unknown")'
	));
	\Osmium\State\put_cache_memory('git_version', $version, 600);
	\Osmium\State\semaphore_release($sem);
	return $version;
}

function curl_init_branded() {
	$c = curl_init();
	$cver = curl_version();
	$over = ltrim(get_osmium_version(), 'v');
	$pver = phpversion();
	curl_setopt($c, CURLOPT_USERAGENT, "Osmium/{$over} (PHP/{$pver}; libcurl/{$cver['version']}; {$cver['ssl_version']}; +http://artefact2.com/osmium/)");
	return $c;
}

function fatal($code, $message) {
	if(!headers_sent()) {
		http_response_code($code);
	}

	$message = "It appears you have reached a fatal error.\n\n"
		."The provided message was: {$message}.\n"
		."The returned HTTP code is: {$code}.\n\n"
		."If you believe this is a bug in the code or a system issue, please report it.\n\n"
		."If you decide to report the issue, please include the following debug backtrace:";

	$l = strlen($message);
	for($i = 0; $i < $l; ++$i) {
		for($j = 0; $j < 8; ++$j) {
			$z = 1;

			if((mt_rand() % 1024) === 0) {
				$message[$i] = chr(ord($message[$i]) ^ $z);
			}

			$z <<= 1;
		}
	}

	$message .= "\n";

	$k = 0;
	foreach(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10) as $c) {
		$message .= '\\'.$c['function'].'() called from '.$c['file'].':'.$c['line']."\n";
	}

	$message .= "\n".date('c')."\n";

	echo "<!DOCTYPE html>\n<html>\n<head>\n"
		."<title>{$code} / Osmium</title>\n"
		."</head>\n<body>\n<pre>"
		.htmlspecialchars($message)
		."</pre>\n"
		."</body>\n</html>\n";

	die((int)$code);
}
