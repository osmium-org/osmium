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

function osmium_fatal($code, $message) {
  header('Content-Type: text/plain', true, $code);
  die((string)$message);
}

define('OSMIUM_ROOT', realpath(__DIR__.'/../'));
define('OSMIUM_VERSION', '0.1');
define('OSMIUM_SHORTDESC', 'the collaborative place to share your fittings!');

if(!is_dir($cache = OSMIUM_ROOT.'/cache') || !is_writeable($cache)) {
  osmium_fatal(500, "Cache directory $cache is not writeable.");
}

session_save_path($cache);
session_start();

if(!file_exists($cnf = OSMIUM_ROOT.'/config.ini') || !is_readable($cnf)) {
  osmium_fatal(500, "Configuration file $cnf not found or not readable.");
}

$__osmium_config = parse_ini_file($cnf);

require OSMIUM_ROOT.'/inc/chrome.php';
require OSMIUM_ROOT.'/inc/forms.php';
require OSMIUM_ROOT.'/inc/pg.php';
require OSMIUM_ROOT.'/inc/api.php';
require OSMIUM_ROOT.'/inc/state.php';
require OSMIUM_ROOT.'/inc/fit.php';

osmium_prg();

if(isset($_POST['__osmium_login'])) {
  osmium_try_login();
}

if(!osmium_logged_in()) {
  osmium_try_recover();
}