<?php
/* Osmium
 * Copyright (C) 2013, 2014, 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\API;

function get_fit_from_input_post_get() {
	if(!isset($_GET['source_fmt'])) {
		\Osmium\fatal(400, 'Must provide source_fmt.');
	}

	$src = $_GET['source_fmt'];

	if($src === 'uri') {
		if(!isset($_GET['input'])) \Osmium\fatal(400, 'Must provide input.');

		$uri = $_GET['input'];
		if(preg_match('%^(https?:)?//%', $uri)) {
			/* Absolute URI */
			$parts = parse_url($uri);

			if($parts === false) \Osmium\fatal(400, 'Severely broken URI!');

			$host = $parts['host'];
			$shost = explode(':', $_SERVER['HTTP_HOST'], 2)[0];
			if($shost !== $host) \Osmium\fatal(400, 'Only local URIs are supported');

			$path = $parts['path'];
		} else {
			/* Relative URI */
			$path = explode('?', $uri, 2)[0];
		}

		$path = explode('/loadout/', $path);
		$path = '/loadout/'.array_pop($path);

		if(preg_match(\Osmium\PUBLIC_LOADOUT_RULE, $path, $match)
		   || preg_match(\Osmium\PRIVATE_LOADOUT_RULE, $path, $match)) {

			$src = $match['loadoutid'];

			foreach([
				'revision' => 'revision',
				'preset' => 'preset',
				'chargepreset' => 'chargepreset',
				'dronepreset' => 'dronepreset',
				'privatetoken' => 'privatetoken',
				'fleet' => 'fleet',
				'remote' => 'remote',
			] as $rname => $gname) {
				if(!isset($match[$rname]) || $match[$rname] === '') continue;
				$_GET[$gname] = $match[$rname];
			}

		} else {
			\Osmium\fatal(400, 'URI does not match a loadout URI.');
		}
	}

	if(is_numeric($src)) {
		/* Assume loadout ID */
		$loadoutid = (int)$src;

		if(!\Osmium\State\can_view_fit($loadoutid)) {
			\Osmium\fatal(404);
		}

		$rev = null;
		if(isset($_GET['revision'])) $rev = (int)$_GET['revision'];
		$fit = \Osmium\Fit\get_fit($loadoutid, $rev);

		if($fit === false) {
			\Osmium\fatal(404);
		}

		if(!\Osmium\State\can_access_fit($fit)) {
			\Osmium\fatal(403, 'Loadout is hidden and/or password-protected, please supply privatetoken and/or password.');
		}

		if(isset($_GET['remote'])) {
			$key = $_GET['remote'];

			if($key !== 'local' && !isset($fit['remote'][$key])) {
				\Osmium\fatal(404);
			}

			\Osmium\Fit\set_local($fit, $key);
		}

		if(isset($_GET['fleet'])) {
			$t = $_GET['fleet'];

			if(!isset($fit['fleet'][$t]) || !isset($fit['fleet'][$t]['ship']['typeid'])
			   || !$fit['fleet'][$t]['ship']['typeid']) {
				\Osmium\fatal(404);
			}

			$fit = $fit['fleet'][$t];
		}
	} else {
		if(isset($_POST['input'])) {
			$input = $_POST['input'];
		} else if(isset($_GET['input'])) {
			$input = $_GET['input'];
		} else {
			\Osmium\fatal(400, 'No input specified. Send data using the '
			              .'input GET or POST parameter.');
		}

		$impf = \Osmium\Fit\get_import_formats();

		$errors = array();
		if(!isset($impf[$src])) {
			\Osmium\fatal(400, 'Import format unavailable.');
		}
		$fits = $impf[$src][2]($input, $errors);

		if($fits === false || $fits === array() || $fits[0] === false) {
			\Osmium\fatal(400, "<pre>".implode("\n", $errors)."</pre>");
		}

		$fit = $fits[0];
	}

	if(isset($_GET['preset'])) {
		if(isset($fit['presets'][$_GET['preset']])) {
			\Osmium\Fit\use_preset($fit, $_GET['preset']);
		} else {
			\Osmium\fatal(404, 'Nonexistent preset specified.');
		}
	}

	if(isset($_GET['chargepreset'])) {
		if(isset($fit['chargepresets'][$_GET['chargepreset']])) {
			\Osmium\Fit\use_charge_preset($fit, $_GET['chargepreset']);
		} else {
			\Osmium\fatal(404, 'Nonexistent charge preset specified.');
		}
	}

	if(isset($_GET['dronepreset'])) {
		if(isset($fit['dronepresets'][$_GET['dronepreset']])) {
			\Osmium\Fit\use_drone_preset($fit, $_GET['dronepreset']);
		} else {
			\Osmium\fatal(404, 'Nonexistent drone preset specified.');
		}
	}

	return $fit;
}

function outputp($data, $ctype, $cache = null, $sessionbound = false) {
	if($cache === null) $cache = 3600;
	if($cache > 0 && $sessionbound === false) {
		/* Cache-Control: private for session-bound content looks good
		 * on paper, but badly-programmed proxies may cache the
		 * request anyway. (I'm looking at you, Varnish.) */
		
		header('Cache-Control: public');
		header('Expires: '.gmdate('r', time() + $cache));
		header_remove('Pragma');
		header_remove('Set-Cookie');
	}

	$jsonopts = isset($_GET['minify']) && !$_GET['minify'] ? JSON_PRETTY_PRINT : 0;

	if($ctype === 'application/json+encode') {
		$data = json_encode($data, $jsonopts);
		$ctype = 'application/json';
	}

	if(isset($_GET['callback']) && !empty($_GET['callback'])) {
		if($ctype !== 'application/json') {
			$data = json_encode($data, $jsonopts);
		}

		$data = $_GET['callback']."(".$data.");\n";
		$ctype = 'application/javascript';
	}


	header('Content-Type: '.$ctype);
	echo $data;
	die();
}
