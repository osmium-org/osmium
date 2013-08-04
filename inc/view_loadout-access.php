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

namespace Osmium\ViewLoadout;



if(isset($_GET['import']) && $_GET['import'] == 'dna') {
    $dna = $_GET['dna'];
    $ckey = 'dnafit_'.$dna;

    if(!isset($_GET['mangle']) || $_GET['mangle']) {
	    $mangled = \Osmium\Fit\mangle_dna($dna);

	    if($mangled === false) {
		    \Osmium\Fatal(400, "Nonsensical DNA string");
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
		    \Osmium\Fatal(400, "Nonsensical DNA string");
	    }

	    \Osmium\State\put_cache_memory_fb($ckey, $fit, 7200);
    } else {
	    \Osmium\Dogma\late_init($fit);
    }

    define('RELATIVE', '../..');

    $fit['metadata']['name'] = 'DNA '.$fit['ship']['typename'];

    $loadoutid = false;
    $revision_overridden = true;
    $revision = 1;
    $forkuri = RELATIVE.'/new/dna/'.$_GET['dna'];
    $historyuri = 'javascript:void(0);';
    $canonicaluri = RELATIVE.'/loadout/dna/'.$_GET['dna'];
    $exporturi = function($format, $ext, $incpresets = false, $params = array()) use($fit, $dna) {
	    $uri = RELATIVE.'/api/convert/dna/'.$format.'/dna.'.$ext.'?input='.$dna;

	    foreach($params as $k => $v) {
		    $uri .= '&amp;'.$k.'='.$v;
	    }

	    return $uri;
    };

    return;
}



$loadoutid = isset($_GET['loadoutid']) ? intval($_GET['loadoutid']) : 0;
if(!\Osmium\State\can_view_fit($loadoutid)) {
	\Osmium\fatal(404, 'Loadout not found.');
}

$latestfit = \Osmium\Fit\get_fit($loadoutid);
if($latestfit === false) {
	\Osmium\fatal(500, 'Internal error, please report.');
}

if(isset($_GET['revision']) && $_GET['revision'] !== '') {
	$askrev = intval($_GET['revision']);
	$fit = \Osmium\Fit\get_fit($loadoutid, $askrev);
	$revision_overridden = true;

	if($fit === false) {
		\Osmium\fatal(404, 'Lodaout revision not found.');
	}
} else {
	$fit = $latestfit;
	$revision_overridden = false;
}

if($latestfit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
	$privatetoken = $latestfit['metadata']['privatetoken'];

	if(!isset($_GET['privatetoken']) || (string)$_GET['privatetoken'] !== (string)$privatetoken) {
		\Osmium\fatal(403, 'This loadout is private.');
	}

	define('RELATIVE', '../../..'.(isset($_GET['fleet']) ? '/../..' : ''));
} else {
	if(isset($_GET['privatetoken'])) {
		/* Accessing a public lodaout using a private-style
		 * URI. Silently redirect to the correct URI. */
		header('Location: ../../'.$loadoutid.($revision_overridden ? 'R'.$fit['metadata']['revision'] : ''));
		die();
	}

	define('RELATIVE', '..'.(isset($_GET['fleet']) ? '/../..' : ''));
}



if(!\Osmium\State\can_access_fit($fit)) {
	if(!isset($_POST['pw']) || !\Osmium\State\check_password($_POST['pw'], $latestfit['metadata']['password'])) {
		if(isset($_POST['pw'])) {
			\Osmium\Forms\add_field_error('pw', 'Incorrect password.');
		}
      
		/* Show the password form */
		\Osmium\Chrome\print_header('Password-protected fit requires authentication', RELATIVE, false);
      
		echo "<div id='pwfit'>\n";
		\Osmium\Forms\print_form_begin();
		\Osmium\Forms\print_text('<p class="m">This fit is password-protected. Please input password to continue.</p>');
		\Osmium\Forms\print_generic_field('Password', 'password', 'pw');
		\Osmium\Forms\print_submit();
		\Osmium\Forms\print_form_end();
		echo "</div>\n";
      
		\Osmium\Chrome\print_footer();
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

$canonicaluri = RELATIVE.'/'.\Osmium\Fit\get_fit_uri(
	$loadoutid, $fit['metadata']['visibility'], $fit['metadata']['privatetoken']
);

if(isset($_GET['fleet'])) {
	$t = htmlspecialchars($_GET['fleet'], ENT_QUOTES);

	if(!isset($fit['fleet'][$t]) || !isset($fit['fleet'][$t]['ship']['typeid'])
	|| !$fit['fleet'][$t]['ship']['typeid']) {
		\Osmium\Fatal(404, "This loadout has no {$t} booster.");
	}

	$revision = $fit['metadata']['revision'];
	$fit = $fit['fleet'][$t];
	$fit['metadata']['name'] = '#'.$loadoutid.", {$t} booster";

	$forkuri = RELATIVE.'/fork/'.$loadoutid
		."?tok=".\Osmium\State\get_token()
		."&amp;revision=".$revision
		."&amp;fleet=".$t;
    $historyuri = 'javascript:void(0);';
    $exporturi = function($format, $ext, $incpresets = false, $params = array()) use($fit, $t, $revision, $loadoutid) {
	    $uri = RELATIVE.'/api/convert/'.$loadoutid.'/'.$format.'/';;
	    $uri .= slugify('', $fit['metadata']['name']);
	    $uri .= '.'.$ext.'?revision='.$revision;

	    if($incpresets) {
		    $params['preset'] = $fit['modulepresetid'];
		    $params['chargepreset'] = $fit['chargepresetid'];
		    $params['dronepreset'] = $fit['dronepresetid'];
	    }
	    $params['fleet'] = $t;
	    foreach($params as $k => $v) {
		    $uri .= '&amp;'.$k.'='.$v;
	    }

	    return $uri;
    };
    $loadoutid = false;
    $revision_overridden = true;
    $revision = 1;

	return;
}

$revision = $fit['metadata']['revision'];
$forkuri = RELATIVE.'/fork/'.$loadoutid."?tok=".\Osmium\State\get_token()."&amp;revision=".$revision;
$historyuri = RELATIVE.'/loadouthistory/'.$loadoutid;
$exporturi = function($format, $ext, $incpresets = false, $params = array()) use($fit) {
	$uri = RELATIVE.'/api/convert/'.$fit['metadata']['loadoutid'].'/'.$format.'/';
	$uri .= slugify($fit['metadata']['loadoutid'], $fit['metadata']['name']);
	$uri .= '.'.$ext.'?revision='.$fit['metadata']['revision'];

	if($incpresets) {
		$params['preset'] = $fit['modulepresetid'];
		$params['chargepreset'] = $fit['chargepresetid'];
		$params['dronepreset'] = $fit['dronepresetid'];
	}
	foreach($params as $k => $v) {
		$uri .= '&amp;'.$k.'='.$v;
	}

	return $uri;
};
