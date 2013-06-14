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
	'slots' => \Osmium\AjaxCommon\get_slot_usage($local),
);

if($type === 'new') {
	\Osmium\State\put_new_loadout($token, $local);

	if(isset($_GET['submit']) && $_GET['submit']) {
		if(!\Osmium\State\is_logged_in()) {
			header('HTTP/1.1 400 Bad Request', true, 400);
			\Osmium\Chrome\return_json(array());
		}

		if(!isset($local['ship']) || !isset($local['ship']['typeid']) || !$local['ship']['typeid']) {
			$payload['submit-error'] = 'You must select a ship first.';
		} else if(in_array($local['metadata']['name'], array(
			'Unnamed loadout', 'New DNA-imported loadout',
		))) {
			$payload['submit-error'] = 'Please enter a name for your loadout.';
			$payload['submit-tab'] = 'metadata';
		} else {
			/* Looks good, commit the loadout */

			$accountid = \Osmium\State\get_state('a')['accountid'];
			if(isset($fit['metadata']['accountid'])) {
				$ownerid = $fit['metadata']['accountid'];
			} else {
				$ownerid = $accountid;
			}

			\Osmium\Fit\commit_loadout($local, $ownerid, $accountid);

			$payload['submit-loadout-uri'] =
				'../'.\Osmium\Fit\get_fit_uri(
					$local['metadata']['loadoutid'],
					$local['metadata']['visibility'],
					$local['metadata']['privatetoken']
				);
		}
	} else if(isset($_GET['export']) && $_GET['export'] && isset($_GET['exportfmt'])) {
		$formats = \Osmium\Fit\get_export_formats();
		if(!isset($formats[$_GET['exportfmt']])) {
			header('HTTP/1.1 400 Bad Request', true, 400);
			\Osmium\Chrome\return_json(array());
		}

		$format = $formats[$_GET['exportfmt']];
		$payload['export-type'] = $format[1];
		$payload['export-payload'] = $format[2]($local);
	}
}

\Osmium\Chrome\return_json($payload);