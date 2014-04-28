<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\ViewLoadout;



if(isset($_GET['import']) && $_GET['import'] == 'dna') {
    $dna = $_GET['dna'];
    $ckey = 'dnafit_'.$dna;

    if(!isset($_GET['mangle']) || $_GET['mangle']) {
	    $mangled = \Osmium\Fit\mangle_dna($dna);

	    if($mangled === false) {
		    \Osmium\Fatal(400);
	    }

	    if($mangled !== $dna) {
		    header('Location: ./'.$mangled, true, 303);
		    die();
		    $dna = $mangled;
	    }
    }

    $fit = \Osmium\State\get_cache_memory_fb($ckey, null);
    if($fit === null) {
	    $fit = \Osmium\Fit\try_parse_fit_from_shipdna($dna, 'New DNA-imported loadout', $errors);

	    if($fit === false) {
		    \Osmium\Fatal(400);
	    }

	    \Osmium\State\put_cache_memory_fb($ckey, $fit, 7200);
    }

    $ctx->relative = '../..';

    $fit['metadata']['name'] = 'DNA '
	    .(isset($fit['ship']['typename']) ? $fit['ship']['typename'] : 'Fragments');

    $loadoutid = false;
    $revision_overridden = true;
    $revision = 1;
    $maxrev = false;
    $forkuri = '/new/dna/'.$_GET['dna'];
    $historyuri = false;
    $canonicaluri = '/loadout/dna/'.$_GET['dna'];
    $exporturi = function($format, $ext, $incpresets = false, $params = array()) use($fit, $dna) {
	    $params['input'] = $dna;
	    return '/api/convert/dna/'.$format.'/dna.'.$ext.\Osmium\DOM\Page::formatQueryString($params);
    };

    return;
}



$loadoutid = isset($_GET['loadoutid']) ? intval($_GET['loadoutid']) : 0;
if(!\Osmium\State\can_view_fit($loadoutid)) {
	\Osmium\fatal(404);
}

$latestfit = \Osmium\Fit\get_fit($loadoutid);
$maxrev = $latestfit['metadata']['revision'];
if($latestfit === false) {
	\Osmium\fatal(500, 'Internal error, please report.');
}

if(isset($_GET['revision']) && $_GET['revision'] !== '') {
	$askrev = intval($_GET['revision']);
	$fit = \Osmium\Fit\get_fit($loadoutid, $askrev);
	$revision_overridden = true;

	if($fit === false) {
		\Osmium\fatal(404);
	}
} else {
	$fit = $latestfit;
	$revision_overridden = false;
}

if($latestfit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
	$privatetoken = $latestfit['metadata']['privatetoken'];

	if(!isset($_GET['privatetoken']) || (string)$_GET['privatetoken'] !== (string)$privatetoken) {
		\Osmium\fatal(404);
	}

	$ctx->relative = ('../../..'.(isset($_GET['fleet']) ? '/../..' : ''));
} else {
	if(isset($_GET['privatetoken'])) {
		/* Accessing a public lodaout using a private-style
		 * URI. Silently redirect to the correct URI. */
		header('Location: ../../'.$loadoutid.($revision_overridden ? 'R'.$fit['metadata']['revision'] : ''));
		die();
	}

	$ctx->relative = ('..'.(isset($_GET['fleet']) ? '/../..' : ''));
}



if(!\Osmium\State\can_access_fit($fit)) {
	if(!isset($_POST['pw']) || !\Osmium\State\check_password($_POST['pw'], $latestfit['metadata']['password'])) {
		$p = new \Osmium\DOM\Page();
      
		/* Show the password form */
		$p->index = false;
		$p->title = 'Password-protected loadout';

		$div = $p->content->appendCreate('div#pwfit');
		$tbody = $div->appendCreate('o-form', [ 'method' => 'post', 'action' => $_SERVER['REQUEST_URI'] ])
			->appendCreate('table')->appendCreate('tbody');


		$tbody->appendCreate('tr')->appendCreate('td', [ 'colspan' => '2' ])->appendCreate(
			'p.m',
			'This loadout is password-protected. Please input password to continue.'
		);

		if(isset($_POST['pw'])) {
			$tbody->appendCreate('tr')->appendCreate('td', [ 'colspan' => '2' ])->appendCreate(
				'p.error_box',
				'Incorrect password. ACCESS DENIED.'
			);
		}

		$tbody->append($p->makeFormInputRow('password', 'pw', 'Password'));
		$tbody->append($p->makeFormSubmitRow('Access loadout'));
      
		$p->render($ctx);
		die();
	} else {
		\Osmium\State\grant_fit_access($fit);
	}
}

function slugify($id, $name) {
	return preg_replace(
		'%[^0-9a-z-]%', '', ($id !== '' ? $id.'-' : '')
		.strtr(strtolower($name), '_ ', '--')
	);
}

$canonicaluri = '/'.\Osmium\Fit\get_fit_uri(
	$loadoutid, $fit['metadata']['visibility'], $fit['metadata']['privatetoken']
);
$canonicaluriwithrevision = '/'.\Osmium\Fit\get_fit_uri(
	$loadoutid, $fit['metadata']['visibility'], $fit['metadata']['privatetoken'], $fit['metadata']['revision']
);
$historyuri = '/loadouthistory/'.$loadoutid;
$forkparams = [];
$forkparams['tok'] = \Osmium\State\get_token();
$forkparams['revision'] = $fit['metadata']['revision'];
$exportparams = [];

if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
	$forkparams['privatetoken'] = $fit['metadata']['privatetoken'];

	$historyuri .= \Osmium\DOM\Page::formatQueryString([ 'privatetoken' => $fit['metadata']['privatetoken'] ]);
}

if(isset($_GET['remote']) && $_GET['remote']) {
	$key = $_GET['remote'];

	if($key !== 'local') {
		if(!isset($fit['remote'][$key])) {
			\Osmium\Fatal(404);
		}

		$revision = $fit['metadata']['revision'];
		\Osmium\Fit\set_local($fit, $key);
		$fit['metadata']['name'] = '#'.$loadoutid.', remote loadout #'.$key;
		$fit['metadata']['loadoutid'] = $loadoutid;
		$fit['metadata']['revision'] = $revision;

		$canonicaluri .= '/remote/'.$key;
		$canonicaluriwithrevision .= '/remote/'.$key;
	}

	$historyuri = false;
    $forkparams['remote'] = $key;
    $exportparams['remote'] = $key;

    $loadoutid = false;
    $revision_overridden = true;
    $revision = 1;
    $maxrev = false;
}

if(isset($_GET['fleet']) && $_GET['fleet']) {
	$t = $_GET['fleet'];

	if(!isset($fit['fleet'][$t]) || !isset($fit['fleet'][$t]['ship']['typeid'])
	|| !$fit['fleet'][$t]['ship']['typeid']) {
		\Osmium\Fatal(404);
	}

	$revision = $fit['metadata']['revision'];
	$fit = $fit['fleet'][$t];
	$fit['metadata']['name'] = '#'.$loadoutid.', '.$t.' booster';
	$fit['metadata']['loadoutid'] = $loadoutid;
	$fit['metadata']['revision'] = $revision;

	$canonicaluri .= '/booster/'.$t;
	$canonicaluriwithrevision .= '/booster/'.$t;

	$historyuri = false;
	$forkparams['fleet'] = $t;
    $exportparams['fleet'] = $t;

    $loadoutid = false;
    $revision_overridden = true;
    $revision = 1;
    $maxrev = false;
}

if(!isset($revision)) {
	$revision = $fit['metadata']['revision'];
}

$forkuri = '/fork/'.$loadoutid.\Osmium\DOM\Page::formatQueryString($forkparams);

if(!isset($exporturi)) {
	$exporturi = function($format, $ext, $incpresets = false, $params = array()) use($fit, $exportparams) {
		$uri = '/api/convert/'.$fit['metadata']['loadoutid'].'/'.$format.'/';
		$uri .= slugify($fit['metadata']['loadoutid'], $fit['metadata']['name']);
		$uri .= '.'.$ext;

		$params['revision'] = $fit['metadata']['revision'];

		if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
			$params['privatetoken'] = $fit['metadata']['privatetoken'];
		}

		if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
			$params['privatetoken'] = $fit['metadata']['privatetoken'];
		}

		if($incpresets) {
			$params['preset'] = $fit['modulepresetid'];
			$params['chargepreset'] = $fit['chargepresetid'];
			$params['dronepreset'] = $fit['dronepresetid'];
		}

		return $uri.\Osmium\DOM\Page::formatQueryString(array_merge($exportparams, $params));
	};
}
