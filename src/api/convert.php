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

namespace Osmium\Api\Convert;

require __DIR__.'/../../inc/root.php';

function fatal($code, $message) {
	http_response_code($code);
	header('Content-Type: text/plain');
	die($message);
}

if(!isset($_GET['source_fmt']) || !isset($_GET['target_fmt'])) {
	fatal(400, "Must provide source_fmt and target_fmt.");
}

$src = $_GET['source_fmt'];
$tgt = $_GET['target_fmt'];

$available_export_formats = \Osmium\Fit\get_export_formats();
$available_import_formats = \Osmium\Fit\get_import_formats();

if(!isset($available_export_formats[$tgt])) {
	fatal(400, "Export format unavailable.");
}

$fit = false;

if(is_numeric($src)) {
	/* Assume loadout ID */
	$loadoutid = (int)$src;

	if(!\Osmium\State\can_view_fit($loadoutid)) {
		fatal(404, "Loadout not found.");
	}

	$rev = null;
	if(isset($_GET['revision'])) $rev = (int)$_GET['revision'];
	$fit = \Osmium\Fit\get_fit($loadoutid, $rev);

	if($fit === false) {
		fatal(400, "get_fit() returned false, invalid revision specified?");
	}

	if(!\Osmium\State\can_access_fit($fit)) {
		fatal(403, "Can't access loadout, password-protected?");
	}

	if(isset($_GET['remote'])) {
		$key = $_GET['remote'];

		if($key !== 'local' && !isset($fit['remote'][$key])) {
			fatal(404, "No such remote.");
		}

		\Osmium\Fit\set_local($fit, $key);
	}

	if(isset($_GET['fleet'])) {
		$t = $_GET['fleet'];

		if(!isset($fit['fleet'][$t]) || !isset($fit['fleet'][$t]['ship']['typeid'])
		|| !$fit['fleet'][$t]['ship']['typeid']) {
			fatal(404, "No such fleet booster.");
		}

		$fit = $fit['fleet'][$t];
	}
} else {
	if(isset($_POST['input'])) {
		$input = $_POST['input'];
	} else if(isset($_GET['input'])) {
		$input = $_GET['input'];
	} else {
		fatal(400, "No input specified. Send data using the input GET or POST parameter.");
	}

	$errors = array();
	if(!isset($available_import_formats[$src])) {
		fatal(400, "Import format unavailable.");
	}
	$fits = $available_import_formats[$src][2]($input, $errors);

	if($fits === false || $fits === array() || $fits[0] === false) {
		fatal(400, implode("\n", $errors));
	}

	$fit = $fits[0];
}

if(isset($_GET['preset'])) {
	if(isset($fit['presets'][$_GET['preset']])) {
		\Osmium\Fit\use_preset($fit, $_GET['preset']);
	} else {
		fatal(400, "Nonexistent preset specified.");
	}
}

if(isset($_GET['chargepreset'])) {
	if(isset($fit['chargepresets'][$_GET['chargepreset']])) {
		\Osmium\Fit\use_charge_preset($fit, $_GET['chargepreset']);
	} else {
		fatal(400, "Nonexistent charge preset specified.");
	}
}

if(isset($_GET['dronepreset'])) {
	if(isset($fit['dronepresets'][$_GET['dronepreset']])) {
		\Osmium\Fit\use_drone_preset($fit, $_GET['dronepreset']);
	} else {
		fatal(400, "Nonexistent drone preset specified.");
	}
}

list(, $ctype, $func) = $available_export_formats[$tgt];

header('X-Robots-Tag: noindex');
header('Content-Type: '.$ctype);
$dest = $func($fit, $_GET);

if(isset($_GET['callback']) && !empty($_GET['callback'])) {
	if($ctype !== "application/json") {
		$dest = json_encode($dest);
	}

	echo $_GET['callback']."(".$dest.");\n";
} else {
	echo $dest;
}

die();
