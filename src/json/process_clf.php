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

namespace Osmium\Json\ProcessCLF;

require __DIR__.'/../../inc/root.php';
require \Osmium\ROOT.'/inc/ajax_common.php';

if(!isset($_GET['token']) || $_GET['token'] != \Osmium\State\get_token()
   || !isset($_GET['type']) || !in_array($_GET['type'], array('new', 'view'))
   || !isset($_GET['clftoken']) || !isset($_POST['clf'])) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

$type = $_GET['type'];
$token = $_GET['clftoken'];
$clftext = $_POST['clf'];
$local = null;

if($type === 'new') {
	$local = \Osmium\State\get_new_loadout($token);
	$relative = '..';
}

if($local === null) {
	header('HTTP/1.1 404 Not Found', true, 404);
	\Osmium\Chrome\return_json(array());
}

if(!\Osmium\Fit\synchronize_from_clf_1($local, $clftext)) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

$payload = array(
	'attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($local, $relative),
);

if($type === 'new') {
	\Osmium\State\put_new_loadout($token, $local);
}

\Osmium\Chrome\return_json($payload);