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
$relative = $_GET['relative'];
$local = null;

if($type === 'new') {
	$local = \Osmium\State\get_new_loadout($token);
} else if($type === 'view') {
	$local = \Osmium\State\get_view_loadout($token);
} else {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

$payload = array();

if($local === null) {
	/* Outdated token, generate a new one and send it to client */
	$token = \Osmium\State\get_unique_new_loadout_token();
	$local = \Osmium\Fit\try_parse_fit_from_common_loadout_format($clftext, $errors);
}

if($local === false || !\Osmium\Fit\synchronize_from_clf_1($local, $clftext)) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

$payload = array(
	'clftoken' => $token,
	'attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($local, $relative),
	'mia' => \Osmium\AjaxCommon\get_modules_interesting_attributes($local),
	'rawattribs' => array(
		'dronebandwidth' => \Osmium\Dogma\get_ship_attribute($local, 'droneBandwidth'),
		'dronebandwidthused' => \Osmium\Fit\get_used_drone_bandwidth($local),
		'dronecapacity' => \Osmium\Dogma\get_ship_attribute($local, 'droneCapacity'),
		'dronecapacityused' => \Osmium\Fit\get_used_drone_capacity($local),
		'maxactivedrones' => \Osmium\Dogma\get_char_attribute($local, 'maxActiveDrones'),
	),
);

if($type === 'new') {
	\Osmium\State\put_new_loadout($token, $local);
} else if($type === 'view') {
	\Osmium\State\put_view_loadout($token, $local);
}

if($type === 'new') {
	$payload['slots'] = \Osmium\AjaxCommon\get_slot_usage($local);
	$payload['hardpoints'] = array(
		'turret' => \Osmium\Dogma\get_ship_attribute($local, 'turretSlotsLeft'),
		'launcher' => \Osmium\Dogma\get_ship_attribute($local, 'launcherSlotsLeft'),
	);
	$payload['rectags'] = \Osmium\Fit\get_recommended_tags($local);

	if(isset($_GET['submit']) && $_GET['submit']) {
		if(!\Osmium\State\is_logged_in()) {
			header('HTTP/1.1 400 Bad Request', true, 400);
			\Osmium\Chrome\return_json(array());
		}

		\Osmium\Fit\sanitize_tags($local, $tag_errors, true);
		\Osmium\Fit\sanitize($local, $sanitize_errors, true);

		if(!isset($local['ship']) || !isset($local['ship']['typeid']) || !$local['ship']['typeid']) {
			$payload['submit-error'] = 'You must select a ship first.';
		} else if(in_array($local['metadata']['name'], array(
			'', 'Unnamed loadout', 'New DNA-imported loadout',
		))) {
			$payload['submit-error'] = 'Please enter a name for your loadout.';
			$payload['submit-tab'] = 'metadata';
			$payload['submit-form-error'] = 'input#name';
		} else if($local['metadata']['view_permission'] == \Osmium\Fit\VIEW_PASSWORD_PROTECTED
		          && empty($local['metadata']['password'])) {
			$payload['submit-error'] = 'If you want your fit to be password-protected, please enter a non-empty password.';
			$payload['submit-tab'] = 'metadata';
			$payload['submit-form-error'] = 'input#pw';
		} else if($local['metadata']['view_permission'] == \Osmium\Fit\VIEW_PASSWORD_PROTECTED
		          && $local['metadata']['visibility'] != \Osmium\Fit\VISIBILITY_PRIVATE) {
			$payload['submit-error'] = 'You cannot have a public password-protected fit. Make it private.';
			$payload['submit-tab'] = 'metadata';
			$payload['submit-form-error'] = 'input#visibility';
		} else if(count($tag_errors) > 0) {
			$payload['submit-error'] = array_pop($tag_errors);
			$payload['submit-tab'] = 'metadata';
			$payload['submit-form-error'] = 'input#tags';
		}

		else {
			/* Looks good, commit the loadout */

			/* Sanitize the loadout, even if it is destructive */
			\Osmium\Fit\sanitize($local, $sanitize_errors, false);

			$accountid = \Osmium\State\get_state('a')['accountid'];
			if(isset($local['metadata']['accountid'])) {
				$ownerid = $local['metadata']['accountid'];
			} else {
				$ownerid = $accountid;
			}

			$ret = \Osmium\Fit\commit_loadout($local, $ownerid, $accountid, $error);
			if($ret === false) {
				$payload['submit-error'] = 'An error occured while committing the loadout. Sorry. Please report! ('.$error.')';
			} else {
				$payload['submit-loadout-uri'] =
					'../'.\Osmium\Fit\get_fit_uri(
						$local['metadata']['loadoutid'],
						$local['metadata']['visibility'],
						$local['metadata']['privatetoken']
					);
			}
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
