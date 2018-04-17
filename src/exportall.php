<?php
/* Osmium
 * Copyright (C) 2018 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\ExportAll;

require __DIR__.'/../inc/root.php';

if(!isset($_POST) || !\Osmium\State\is_logged_in()) {
	\Osmium\fatal(400, 'Wrong request type, or not logged in');
}

if(!isset($_GET['type']) || ($_GET['type'] !== 'clf' && $_GET['type'] !== 'eft')) {
	\Osmium\fatal(400, 'Wrong type, must be clf or eft');
}

$a = \Osmium\State\get_state('a');

$out = [];
$q = \Osmium\Db\query_params('SELECT loadoutid FROM loadouts WHERE accountid = $1', [ $a['accountid'] ]);
while($row = \Osmium\Db\fetch_row($q)) {
	$fit = \Osmium\Fit\get_fit($row[0]);

	switch($_GET['type']) {
	case 'clf':
		$out[] = json_decode(\Osmium\Fit\export_to_common_loadout_format($fit, \Osmium\Fit\CLF_EXPORT_EXTRA_PROPERTIES | \Osmium\Fit\CLF_EXPORT_INTERNAL_PROPERTIES), true);
		break;
	case 'eft':
		$out[] = \Osmium\Fit\export_to_eft($fit);
		break;
	}
}

switch($_GET['type']) {
case 'clf':
	header('Content-Type: application/json');
	echo json_encode($out, JSON_PRETTY_PRINT);
	break;
case 'eft':
	header('Content-Type: text/plain');
	echo implode('', $out);
	break;
}
