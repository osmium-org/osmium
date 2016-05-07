<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014, 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

if(!defined('Osmium\ROOT')) {
	require __DIR__.'/dispatchroot.php';
}

if(get_ini_setting('trust_x_forwarded_for') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	/* XXX: first entry or last? */
	$_SERVER['REMOTE_ADDR'] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}

if(get_ini_setting('trust_x_forwarded_proto')) {
	define(
		__NAMESPACE__.'\HTTPS',
		isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
		&& stripos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false
	);
} else {
	define(
		__NAMESPACE__.'\HTTPS',
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
	);
}

if(!get_ini_setting('tolerate_errors') && PHP_SAPI !== "cli") {
	ob_start();
	error_reporting(-1);
	set_error_handler(function($errno, $errstr, $errfile, $errline) {
		if (!error_reporting()) {
			/*
			 * Error reporting is off right now for whatever
			 * reason. Probably there was an @ and we're supposed
			 * to ignore it. I hate PHP. -jboning
			 */
			return;
		}
		while(ob_end_clean()) { /* Erase ALL levels of output buffering. */ }
		ob_start();
		$errfile = explode('/', $errfile);
		$errfile = array_pop($errfile);
		\Osmium\fatal(500, "<code><strong>{$errfile}({$errline})</strong>: {$errstr}</code>", null, null, false);

		/* Don't die just yet, log the original error */
		restore_error_handler();
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50);
		$sbt = '';
		foreach($bt as $c) {
			$sbt .= '\\'.$c['function'].'() called from '.$c['file'].':'.$c['line']."\n";
		}
		trigger_error(
			"\n".$errfile.':'.$errline
				.": ".$errstr
				."\n".$sbt,
			E_USER_ERROR
		);
		die();
	}, -1);
}

/** Bump this when static files (icons, etc.) are updated */
const STATICVER = 17;

/** Bump this when CSS files are updated */
const CSS_STATICVER = 28;

/** Bump this when JS snippets are updated */
const JS_STATICVER = 35;

/** Bump this when clientdata.json is updated */
const CLIENT_DATA_STATICVER = 50;

define(__NAMESPACE__.'\CACHE_DIRECTORY', ROOT.'/cache');

define(__NAMESPACE__.'\COOKIE_HOST',
       isset($_SERVER['HTTP_HOST']) ? explode(':', $_SERVER['HTTP_HOST'], 2)[0] : '127.0.0.1'
);

if(!is_dir(CACHE_DIRECTORY) || !is_writeable(CACHE_DIRECTORY)) {
	fatal(500, "Cache directory <code>'".CACHE_DIRECTORY."'</code> is not writeable.");
}

session_set_cookie_params(0, get_ini_setting('relative_path'), COOKIE_HOST, HTTPS, true);
session_save_path(CACHE_DIRECTORY);
session_name('O');

if(isset($_SERVER['REMOTE_ADDR']) && isset($_COOKIE['O'])) {
	/* Only resume session if necessary, don't create a new
	 * session for nothing! */
	session_start();
}

libxml_use_internal_errors(true);
libxml_disable_entity_loader(true);

require ROOT.'/inc/dom.php';
require ROOT.'/inc/chrome.php';
require ROOT.'/inc/forms.php';
require ROOT.'/inc/db.php';
require ROOT.'/inc/eveapi.php';
require ROOT.'/inc/state.php';
require ROOT.'/inc/fit.php';
require ROOT.'/inc/dogma.php';
require ROOT.'/inc/flag.php';
require ROOT.'/inc/search.php';
require ROOT.'/inc/skills.php';
require ROOT.'/inc/log.php';
require ROOT.'/inc/notification.php';
require ROOT.'/inc/reputation.php';

\Osmium\Forms\post_redirect_get();

if(!\Osmium\State\is_logged_in()) {
	\Osmium\State\try_recover();
}

if((!get_ini_setting('anonymous_access') || get_ini_setting('whitelist')) && isset($_SERVER['REQUEST_URI'])) {
	header('X-Robots-Tag: noindex, nofollow');

	$prefix = rtrim(get_ini_setting('relative_path'), '/');
	$req = substr(explode('?', $_SERVER['REQUEST_URI'], 2)[0], strlen($prefix));

	if(!\Osmium\State\is_logged_in()) {

		if(!get_ini_setting('anonymous_access') && !in_array($req, [
			'/internal/auth/ccpoauthcallback',
			'/login',
			'/register',
			'/resetpassword',
		], true)) {
			\Osmium\State\assume_logged_in();
		}
	} else {
		$a = \Osmium\State\get_state('a');
		if(isset($a['notwhitelisted']) && $a['notwhitelisted'] && !in_array($req, [
			'/internal/auth/ccpoauthcallback',
			'/internal/logout',
			'/settings',
		], true)) {
			header('Location: '.$prefix.'/settings#s_features');
			die();
		}
	}
}
