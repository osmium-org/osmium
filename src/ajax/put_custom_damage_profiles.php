<?php
/* Osmium
 * Copyright (C) 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Ajax\PutCustomDamageProfiles;

require __DIR__.'/../../inc/root.php';

const MAX_DAMAGE_PROFILES = 50;

if(!isset($_GET['token']) || $_GET['token'] != \Osmium\State\get_token() || !isset($_POST['payload'])) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	die();
}

$profiles = json_decode($_POST['payload'], true);
if(json_last_error() !== JSON_ERROR_NONE || !is_array($profiles)) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	die();
}

$saneprofiles = array();
$sanecount = 0;

foreach($profiles as $n => $p) {
	if($sanecount >= MAX_DAMAGE_PROFILES) break;
	if(count($p) !== 4) continue;

	list($a, $b, $c, $d) = $p;

	if($a < 0 || $b < 0 || $c < 0 || $d < 0 || ($s = $a + $b + $c + $d) <= 0) continue;

	$saneprofiles[$n] = [ $a / $s, $b / $s, $c / $s, $d / $s ];
	++$sanecount;
}

\Osmium\State\put_state_trypersist('custom_damage_profiles', $saneprofiles);
