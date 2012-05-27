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
		header('Content-Type: text/plain', true, $code);
	}
	die((string)$message);
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

const SHORT_DESCRIPTION = 'the collaborative place to share your fittings!';
const VERSION = '0.1.0';

define(__NAMESPACE__.'\ROOT', realpath(__DIR__.'/../'));
define(__NAMESPACE__.'\INI_CONFIGURATION_FILE', ROOT.'/config.ini');
define(__NAMESPACE__.'\CACHE_DIRECTORY', ROOT.'/cache');

if(!is_dir(CACHE_DIRECTORY) || !is_writeable(CACHE_DIRECTORY)) {
	osmium_fatal(500, "Cache directory '".CACHE_DIRECTORY."' is not writeable.");
}

session_save_path(CACHE_DIRECTORY);
session_start();

require ROOT.'/inc/chrome.php';
require ROOT.'/inc/forms.php';
require ROOT.'/inc/db.php';
require ROOT.'/inc/eveapi.php';
require ROOT.'/inc/state.php';
require ROOT.'/inc/fit.php';
require ROOT.'/inc/flag.php';
require ROOT.'/inc/search.php';

\Osmium\Forms\post_redirect_get();

if(isset($_POST['__osmium_login'])) {
	\Osmium\State\try_login();
}

if(!\Osmium\State\is_logged_in()) {
	\Osmium\State\try_recover();
}