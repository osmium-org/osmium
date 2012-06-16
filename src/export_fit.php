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

namespace Osmium\Page\ExportFit;

require __DIR__.'/../inc/root.php';

if(!isset($_GET['type'])) {
	\Osmium\fatal(400, "No type specified.");
}

$type = $_GET['type'];

if($type != "clf") {
	\Osmium\fatal(400, "Invalid type specified.");
}

if(!isset($_GET['loadoutid'])) {
	\Osmium\fatal(400, "No loadoutid specified.");
}

$loadoutid = $_GET['loadoutid'];

if(\Osmium\State\is_logged_in()) {
	$a = \Osmium\State\get_state('a');
	list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.allowedloadoutsbyaccount WHERE loadoutid = $1 AND accountid = $2', array($loadoutid, $a['accountid'])));
} else {
	list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.allowedloadoutsanonymous WHERE loadoutid = $1', array($loadoutid)));
}

if($count == 0) {
	\Osmium\fatal(404, "Loadout not found.");
}

$fit = \Osmium\Fit\get_fit($loadoutid);
if($fit === false) {
	\Osmium\fatal(500, '\Osmium\Fit\get_fit() returned false, please report! (loadoutid: '.$loadoutid.')');
}

if($fit['metadata']['view_permission'] == \Osmium\Fit\VIEW_PASSWORD_PROTECTED) {
	$green = \Osmium\State\get_state('green_fits', array());
	if(!isset($green[$loadoutid]) || $green[$loadoutid] !== true) {
		\Osmium\fatal(403, "The loadout is password-protected. Please enter the password on the view loadout page (../loadout/".$loadoutid."), and retry.");
	}
}

if(isset($_GET['pid']) && isset($fit['presets'][$_GET['pid']])) {
	\Osmium\Fit\use_preset($fit, $_GET['pid']);
}
if(isset($_GET['cpid']) && isset($fit['chargepresets'][$_GET['cpid']])) {
	\Osmium\Fit\use_charge_preset($fit, $_GET['cpid']);
}
if(isset($_GET['dpid']) && isset($fit['dronepresets'][$_GET['dpid']])) {
	\Osmium\Fit\use_drone_preset($fit, $_GET['dpid']);
}

if($type == 'clf') {
	$minify = isset($_GET['minify']) && $_GET['minify'] == 1;

	$json = \Osmium\Fit\export_to_common_loadout_format($fit, $minify);
	header('Content-Type: application/json');
	echo $json;
	die();
}
