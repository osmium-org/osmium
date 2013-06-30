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

    $fit = \Osmium\State\get_cache_memory_fb($ckey, null);
    if($fit === null) {
	    $fit = \Osmium\Fit\try_parse_fit_from_shipdna($dna, 'New DNA-imported loadout', $errors);

	    if($fit === false) {
		    \Osmium\Fatal(400, "Nonsensical DNA string");
	    }

	    \Osmium\State\put_cache_memory_fb($ckey, $fit, 7200);
    }

    define('RELATIVE', '../..');

    $fit['metadata']['name'] = 'DNA '.$fit['ship']['typename'];

    $loadoutid = false;
    $revision_overridden = true;
    $revision = 1;
    $forkuri = RELATIVE.'/new/dna/'.$_GET['dna'];
    $historyuri = 'javascript:void(0);';
    $exporturi = function($format, $ext, $incpresets = false, $params = array()) use($fit, $dna) {
	    $uri = RELATIVE.'/api/convert/dna/'.$format.'/dna.'.$ext.'?input='.$dna;
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

	define('RELATIVE', '../../..');
} else {
	if(isset($_GET['privatetoken'])) {
		/* Accessing a public lodaout using a private-style
		 * URI. Silently redirect to the correct URI. */
		header('Location: ../../'.$loadoutid.($revision_overridden ? 'R'.$fit['metadata']['revision'] : ''));
		die();
	}

	define('RELATIVE', '..');
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

$revision = $fit['metadata']['revision'];
$forkuri = RELATIVE.'/fork/'.$loadoutid."?tok=".\Osmium\State\get_token()."&amp;revision=".$revision;
$historyuri = RELATIVE.'/loadouthistory/'.$loadoutid;
$exporturi = function($format, $ext, $incpresets = false, $params = array()) use($fit) {
	$uri = RELATIVE.'/api/convert/'.$fit['metadata']['loadoutid'].'/'.$format.'/';
	$uri .= preg_replace(
		'%[^0-9a-z-]%', '', $fit['metadata']['loadoutid'].'-'
		.strtr(strtolower($fit['metadata']['name']), '_ ', '--')
	).'.'.$ext.'?revision='.$fit['metadata']['revision'];

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
