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

namespace Osmium\Page\ViewFit;

const PASSWORD_AUTHENTICATION_DURATION = 1800; /* How long to "remember" the password for protected fits */

require __DIR__.'/../inc/root.php';

$loadoutid = intval($_GET['loadoutid']);

if(!\Osmium\State\can_view_fit($loadoutid)) {
	\Osmium\fatal(404, 'Loadout not found.');
}

if(isset($_GET['revision'])) {
	$fit = \Osmium\Fit\get_fit($loadoutid, $_GET['revision']);

	if($fit === false) {
		\Osmium\fatal(500, '\Osmium\Fit\get_fit() returned false, are you sure you specified a valid revision number? (loadoutid: '.$loadoutid.')');
	}
} else {
	$fit = \Osmium\Fit\get_fit($loadoutid);

	if($fit === false) {
		\Osmium\fatal(500, '\Osmium\Fit\get_fit() returned false, please report! (loadoutid: '.$loadoutid.')');
	}
}

$author = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT accountid, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator FROM osmium.accounts WHERE accountid = $1', array($fit['metadata']['accountid'])));
$lastrev = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT updatedate, accountid, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator FROM osmium.loadouthistory JOIN osmium.accounts ON accounts.accountid = loadouthistory.updatedbyaccountid WHERE loadoutid = $1 AND revision = $2', array($loadoutid, $fit['metadata']['revision'])));

$can_edit = \Osmium\State\can_edit_fit($loadoutid);

if(!\Osmium\State\can_access_fit($fit)) {
	if(!isset($_POST['pw']) || !\Osmium\State\check_password($_POST['pw'], $fit['metadata']['password'])) {
		if(isset($_POST['pw'])) {
			\Osmium\Forms\add_field_error('pw', 'Incorrect password.');
		}
      
		/* Show the password form */
		\Osmium\Chrome\print_header('Password-protected fit requires authentication', '..', "<meta name='robots' content='noindex' />\n");
      
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
		\Osmium\State\grant_fit_access($fit, PASSWORD_AUTHENTICATION_DURATION);
	}
}

$title = $fit['ship']['typename'].' / '.htmlspecialchars($fit['metadata']['name']);
\Osmium\Chrome\print_header(strip_tags($title), '..', $fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE ? "<meta name='robots' content='noindex' />\n" : '');

$green_fits = \Osmium\State\get_state('green_fits', array());
$green_fits[$fit['metadata']['loadoutid']] = true;
\Osmium\State\put_state('green_fits', $green_fits);

if(isset($_GET['pid'])) {
    $ids = explode('-', $_GET['pid'], 2);
    $pid = intval($ids[0]);
    if(count($ids) >= 2) $cpid = intval($ids[1]);

	if(isset($fit['presets'][$pid])) {
		\Osmium\Fit\use_preset($fit, $pid);
	}

	if(isset($cpid) && isset($fit['chargepresets'][$cpid])) {
		\Osmium\Fit\use_charge_preset($fit, $cpid);
	}
}
if(isset($_GET['dpid']) && isset($fit['dronepresets'][$_GET['dpid']])) {
	\Osmium\Fit\use_drone_preset($fit, $_GET['dpid']);
}

/* ----------------------------------------------------- */

echo "<div id='metadatabox'>\n";
echo "<h2>Fitting metadata</h2>\n";

echo "<ul>\n";
echo "<li>Originally created by: <strong>".\Osmium\Chrome\format_character_name($author, '..')."</strong> (".date('Y-m-d', $fit['metadata']['creation_date']).")<br />";
echo "<img src='http://image.eveonline.com/Character/".$author['characterid']."_64.jpg' alt='".$author['charactername']."' title='".$author['charactername']."' /> ";
echo "<img src='http://image.eveonline.com/Corporation/".$author['corporationid']."_64.png' alt='".$author['corporationname']."' title='".$author['corporationname']."' /> ";
if($author['allianceid'] > 0) {
	echo "<img src='http://image.eveonline.com/Alliance/".$author['allianceid']."_64.png' alt='".$author['alliancename']."' title='".$author['alliancename']."' />";
}
echo "<br /> </li>\n";
if($fit['metadata']['revision'] > 1) {
	echo "<li>Revision <strong>#".$fit['metadata']['revision']."</strong> edited by: <img src='http://image.eveonline.com/Character/".$lastrev['characterid']."_32.jpg' alt='".$lastrev['charactername']."' title='".$lastrev['charactername']."' /> <strong>".\Osmium\Chrome\format_character_name($lastrev, '..')."</strong> (".date('Y-m-d', $lastrev['updatedate']).")</li>";
}
echo "</ul>\n";

if(count($fit['dronepresets']) > 1 || count($fit['presets']) > 1 || count($fit['chargepresets']) > 1) {
	$action = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
	echo "<form method='get' action='$action' class='presets'>\n";

	foreach(array(array('presets', 'pid', 'modulepresetid', 'preset'),
	              array('chargepresets', 'cpid', 'chargepresetid', 'chargepreset'),
	              array('dronepresets', 'dpid', 'dronepresetid', 'dronepreset')) as $presettype) {
		list($key, $name, $current, $selectid) = $presettype;


	}

	if(count($fit['presets']) > 1) {
		/* Use one big select, with optgroups for charge presets */
		echo "<select name='pid' id='preset'>\n";

		foreach($fit['presets'] as $presetid => $p) {
			echo "<optgroup label='".htmlspecialchars($p['name'], ENT_QUOTES)."'>\n";

			foreach($p['chargepresets'] as $cpid => $cp) {
				$selected = $fit['modulepresetid'] == $presetid && $fit['chargepresetid'] == $cpid ?
					" selected='selected'" : '';
				echo "<option value='{$presetid}-{$cpid}'$selected>".htmlspecialchars($cp['name'])."</option>\n";
			}

			echo "</optgroup>\n";
		}

		echo "</select><br />\n";
	}

	if(count($fit['dronepresets']) > 1) {
		echo "<select name='dpid' id='dronepreset'>\n";
		foreach($fit['dronepresets'] as $presetid => $p) {
			$selected = $fit['dronepresetid'] == $presetid ? ' selected="selected"' : '';
			echo "<option value='$presetid'$selected>".htmlspecialchars($p['name'])."</option>\n";
		}
		echo "</select>\n";
	}

	echo "<span class='submit'>\n<br />\n<input type='submit' value='Switch presets' />\n</span>\n";
	echo"</form>\n";
}

echo "<ul>\n";
if($can_edit) {
	echo "<li><a href='../edit/".$loadoutid."?tok=".\Osmium\State\get_token()."'><strong>Edit this loadout</strong></a></li>\n";
	echo "<li><a href='../delete/".$loadoutid."?tok=".\Osmium\State\get_token()."' class='dangerous' onclick='return confirm(\"Deleting this loadout will also delete all its history, and cannot be undone. Are you sure you want to continue?\");'><strong>Delete this loadout</strong></a></li>\n";
}
echo "<li><a href='../loadouthistory/$loadoutid'>View revision history</a></li>\n";
echo "<li><a href='../search?q=".urlencode('@ship "'.$fit['ship']['typename'].'"')."'>Browse all ".$fit['ship']['typename']." loadouts</a></li>\n";
echo "<li><a href='../search?q=".urlencode('@author "'.$author['charactername'].'"')."'>Browse loadouts from the same author</a></li>\n";

$slug = $author['charactername'].' '.$fit['ship']['typename'].' '.$fit['metadata']['name'].' '.$fit['metadata']['revision'];
$slug = preg_replace('%[^a-z0-9-]%', '', str_replace(' ', '-', strtolower($slug)));
echo "<li><small>Export this loadout: <a href='../export/{$slug}-clf-{$loadoutid}.json' title='Export in the Common Loadout Format'>CLF</a>, <a title='Export in the Common Loadout Format, minified' href='../export/{$slug}-clf-{$loadoutid}.json?minify=1'>minified CLF</a>, <a href='../export/{$slug}-md-{$loadoutid}.md'>Markdown+gzCLF</a></small></li>\n";

echo "</ul>\n";

echo "<div id='computed_attributes'>\n";
\Osmium\Chrome\print_formatted_loadout_attributes($fit, '..');
echo "</div>\n";

echo "<h2>Fitting description</h2>\n";
echo "<p id='fitdesc'>\n".nl2br(htmlspecialchars($fit['metadata']['description']))."</p>\n";

echo "</div>\n";

/* ----------------------------------------------------- */

echo "<div id='vloadoutbox' data-loadoutid='".$fit['metadata']['loadoutid']."' data-presetid='".$fit['modulepresetid']."' data-cpid='".$fit['chargepresetid']."' data-dpid='".$fit['dronepresetid']."'>\n";

echo "<header>\n";
echo "<img src='http://image.eveonline.com/Render/".$fit['ship']['typeid']."_256.png' alt='".$fit['ship']['typename']."' id='fittypepic' />\n";
echo "<h2>".$fit['ship']['typename']." loadout</h2>\n";
echo "<h1 id='fitname' class='has_spinner'>";
echo \Osmium\Chrome\print_loadout_title($fit['metadata']['name'], $fit['metadata']['view_permission'], $fit['metadata']['visibility'], $author, '..', $fit['metadata']['loadoutid']);
echo "<img src='../static/icons/spinner.gif' id='vloadoutbox_spinner' class='spinner' alt='' /></h1>\n";
echo "<div id='fittags'>\n<h2>Tags:</h2>\n";
if(count($fit['metadata']['tags']) > 0) {
	echo "<ul>\n";
	foreach($fit['metadata']['tags'] as $tag) {
		echo "<li><a href='../search?q=".urlencode('@tags '.$tag)."'>$tag</a></li>\n"; /* No escaping needed, tags are [A-Za-z0-9-] */
	}
	echo "</ul>\n";
} else {
	echo "<em>(no tags)</em>";
}
echo "</div>\n</header>\n";

$aslots = \Osmium\Fit\get_attr_slottypes();
$astates = \Osmium\Fit\get_state_names();
$allmodules = \Osmium\Fit\get_modules($fit);
foreach(\Osmium\Fit\get_slottypes() as $type) {
	if(!isset($allmodules[$type])) continue;
	$modules = $allmodules[$type];

	$slotcount = \Osmium\Dogma\get_ship_attribute($fit, $aslots[$type], false);

	if(count($modules) == 0 && $slotcount == 0) continue;
	
	$used = count($modules);
	$class = in_array($type, \Osmium\Fit\get_stateful_slottypes()) ? ' stateful' : '';

	echo "<div id='{$type}_slots' class='slots$class'>\n<h3>".ucfirst($type)." slots <small class='capacity'>$used / $slotcount</small></h3>\n<ul>\n";

	foreach($modules as $index => $mod) {
		$state = \Osmium\Fit\get_module_state_by_location($fit, $type, $index);
		$ranges = \Osmium\Fit\get_optimal_falloff_tracking_of_module($fit, $type, $index);

		$charge = '';
		if(isset($fit['charges'][$type][$index])) {
			$charge = ",<br /><img src='http://image.eveonline.com/Type/".$fit['charges'][$type][$index]['typeid']."_32.png' alt='' />".$fit['charges'][$type][$index]['typename'];
		}

		list($stname, $stpicture) = $astates[$state];

		echo "<li data-typeid='".$mod['typeid']."' data-index='".$index."' data-slottype='".$type."' data-state='".$state."'><img src='http://image.eveonline.com/Type/".$mod['typeid']."_32.png' alt='' />".$mod['typename']."<span class='charge'>$charge</span>";
		echo "<a class='toggle' href='javascript:void(0);' title='$stname; click to toggle'><img src='../static/icons/$stpicture' alt='$stname' /></a>";

		if($ranges !== array()) {
			echo "<span class='range' title='".\Osmium\Chrome\format_long_range($ranges)."'>".\Osmium\Chrome\format_short_range($ranges)."</span>";
		}

		echo "</li>\n";
	}

	for($i = count($modules); $i < $slotcount; ++$i) {
		echo "<li class='unused'><img src='../static/icons/slot_$type.png' alt='' />Unused $type slot</li>\n";
	}

	echo "</ul>\n</div>\n";
}

if(($totalcapacity = \Osmium\Dogma\get_ship_attribute($fit, 'droneCapacity')) > 0) {
	if(!isset($fit['drones'])) $fit['drones'] = array();

	$totalbandwidth = \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth');

	$usedcapacity = 0;
	$usedbandwidth = 0;
	foreach($fit['drones'] as $drone) {
		$usedcapacity += ($drone['quantityinbay'] + $drone['quantityinspace']) * $drone['volume'];
		$usedbandwidth += $drone['quantityinspace'] * $drone['bandwidth'];
	}

	echo "<div id='vdronebay'>\n<h3>Drones <small class='capacity'><span><img src='../static/icons/bandwidth_ds.png' alt='Drone bandwidth' title='Drone bandwidth' /><span id='dronebandwidth'>$usedbandwidth / $totalbandwidth</span> Mbit/s</span><span><img src='../static/icons/dronecapacity_ds.png' alt='Drone capacity' title='Drone capacity' />$usedcapacity / $totalcapacity m<sup>3</sup></span></small></h3>\n";

	foreach(array('bay', 'space') as $v) {
		echo "<div id='in$v'>\n<h4>In $v</h4>\n<ul>\n";
		$z = 0;
		foreach($fit['drones'] as $drone) {
			$quantity = $drone['quantityin'.$v];
			$typeid = $drone['typeid'];
			$qty = '';
			if($drone['quantityin'.$v] == 0) continue; /* Duh */
			if($drone['quantityin'.$v] > 1) {
				$qty = " <strong>×".$quantity."</strong>";
			}
			
			echo "<li data-typeid='$typeid' data-count='$quantity'><img src='http://image.eveonline.com/Type/".$drone['typeid']."_32.png' alt='' />".$drone['typename'].$qty."</li>\n";
			++$z;
		}

		if($z === 0) {
			echo "<li><em>(no drones in $v)</em></li>\n";
		}

		echo "</ul>\n</div>\n";
	}

	echo "</div>\n";
}

echo "</div>\n";

\Osmium\Chrome\print_js_snippet('formatted_attributes');
\Osmium\Chrome\print_js_snippet('view_loadout');
\Osmium\Chrome\print_footer();
