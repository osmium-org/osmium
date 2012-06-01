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

if(\Osmium\State\is_logged_in()) {
	$a = \Osmium\State\get_state('a');
	list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.allowedloadoutsbyaccount WHERE loadoutid = $1 AND accountid = $2', array($loadoutid, $a['accountid'])));
} else {
	list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.allowedloadoutsanonymous WHERE loadoutid = $1', array($loadoutid)));
}

if($count == 0) {
	\Osmium\fatal(404, 'Loadout not found.');
}

$fit = \Osmium\Fit\get_fit($loadoutid);
$author = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator FROM osmium.accounts WHERE accountid = $1', array($fit['metadata']['accountid'])));
$lastrev = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT updatedate, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator FROM osmium.loadouthistory JOIN osmium.accounts ON accounts.accountid = loadouthistory.updatedbyaccountid WHERE loadoutid = $1 AND revision = $2', array($loadoutid, $fit['metadata']['revision'])));

$can_edit = false;
if(\Osmium\State\is_logged_in()) {
	list($c) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.editableloadoutsbyaccount WHERE loadoutid = $1 AND accountid = $2', array($loadoutid, $a['accountid'])));
	$can_edit = ($c == 1);
}

if($fit === false) {
	\Osmium\fatal(500, '\Osmium\Fit\get_fit() returned false, please report! (loadoutid: '.$loadoutid.')');
}

if($fit['metadata']['view_permission'] == \Osmium\Fit\VIEW_PASSWORD_PROTECTED) {
	$pw = \Osmium\State\get_state('pw_fits', array());
	if((!isset($a) || $a['accountid'] != $fit['metadata']['accountid']) 
	   && ((!isset($pw[$loadoutid]) || $pw[$loadoutid] < time()))) {
		unset($pw[$loadoutid]);
    
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
			$pw[$loadoutid] = time() + PASSWORD_AUTHENTICATION_DURATION;
			\Osmium\State\put_state('pw_fits', $pw);
		}
	}
}

$preset = (isset($_GET['preset']) && isset($fit['charges'][$_GET['preset']])) ? $_GET['preset'] : null;
if(count($fit['charges']) > 0) {
	if($preset === null) {
		/* Use 1st preset */
		reset($fit['charges']);
		$preset = key($fit['charges']);
	}
	\Osmium\Fit\use_preset($fit, $preset);
}

$title = $fit['ship']['typename'].' / '.$fit['metadata']['name'];
\Osmium\Chrome\print_header(strip_tags($title), '..');

/* ----------------------------------------------------- */

echo "<div id='metadatabox'>\n";
echo "<h2>Fitting metadata</h2>\n";

echo "<ul>\n";
echo "<li>Originally created by: <strong>".\Osmium\Flag\format_moderator_name($author)."</strong> (".date('Y-m-d', $fit['metadata']['creation_date']).")<br />";
echo "<img src='http://image.eveonline.com/Character/".$author['characterid']."_64.jpg' alt='".$author['charactername']."' title='".$author['charactername']."' /> ";
echo "<img src='http://image.eveonline.com/Corporation/".$author['corporationid']."_64.png' alt='".$author['corporationname']."' title='".$author['corporationname']."' /> ";
if($author['allianceid'] > 0) {
	echo "<img src='http://image.eveonline.com/Alliance/".$author['allianceid']."_64.png' alt='".$author['alliancename']."' title='".$author['alliancename']."' />";
}
echo "<br /> </li>\n";
if($fit['metadata']['revision'] > 1) {
	echo "<li>Revision <strong>#".$fit['metadata']['revision']."</strong> edited by: <img src='http://image.eveonline.com/Character/".$lastrev['characterid']."_32.jpg' alt='".$lastrev['charactername']."' title='".$lastrev['charactername']."' /> <strong>".\Osmium\Flag\format_moderator_name($lastrev)."</strong> (".date('Y-m-d', $lastrev['updatedate']).")</li>";
}
echo "</ul>\n";

if(count($fit['charges']) > 1) {
	echo "<ul>\n<li>Charge presets:\n<ul id='vpresets'>\n";

	foreach($fit['charges'] as $name => $_) {
		if($name === $preset) {
			$class = " class='active'";
		} else {
			$class = '';
		}
		$f_name = htmlspecialchars($name, ENT_QUOTES);

		echo "<li data-index='$name'><a href='?".http_build_query(array('preset' => $name))."'$class>".$f_name."</a></li>\n";
	}

	echo "</ul>\n</li>\n</ul>\n";
}

echo "<ul>\n";
if($can_edit) {
	echo "<li><a href='../edit/".$loadoutid."?tok=".\Osmium\State\get_token()."'><strong>Edit this loadout</strong></a></li>\n";
	echo "<li><a href='../delete/".$loadoutid."?tok=".\Osmium\State\get_token()."' class='dangerous' onclick='return confirm(\"Deleting this loadout will also delete all its history, and cannot be undone. Are you sure you want to continue?\");'><strong>Delete this loadout</strong></a></li>\n";
}
echo "<li><a href='../search?q=".urlencode('@ship "'.$fit['ship']['typename'].'"')."'>Browse all ".$fit['ship']['typename']." loadouts</a></li>\n";
echo "<li><a href='../search?q=".urlencode('@author "'.$author['charactername'].'"')."'>Browse loadouts from the same author</a></li>\n";
echo "</ul>\n";

echo "<ul class='computed_attributes'>\n";

echo "<li>\n";
$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlotsLeft');
$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlots');
echo "<p class='oneline'><img src='../static/icons/turrethardpoints.png' alt='Turret hardpoints' title='Turret hardpoints' />".\Osmium\Chrome\format_used($slotsTotal - $slotsLeft, $slotsTotal, 0, false, $over)."</p>\n";
$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlotsLeft');
$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlots');
echo "<p class='oneline'><img src='../static/icons/launcherhardpoints.png' alt='Launcher hardpoints' title='Launcher hardpoints' />".\Osmium\Chrome\format_used($slotsTotal - $slotsLeft, $slotsTotal, 0, false, $over)."</p>\n";
echo "<p class='oneline'><img src='../static/icons/capacitor.png' alt='Capacitor' title='Capacitor' />".\Osmium\Chrome\format_capacitor(\Osmium\Fit\get_capacitor_stability($fit))."</p>\n";
echo "</li>\n";

echo "<li>\n";
$cpuUsed = \Osmium\Dogma\get_ship_attribute($fit, 'cpuLoad');
$cpuTotal = \Osmium\Dogma\get_ship_attribute($fit, 'cpuOutput');
$formatted = \Osmium\Chrome\format_used($cpuUsed, $cpuTotal, 2, true, $over);
echo "<p class='overflow$over'><img src='../static/icons/cpu.png' alt='CPU' title='CPU' />".$formatted."</p>\n";
$powerUsed = \Osmium\Dogma\get_ship_attribute($fit, 'powerLoad');
$powerTotal = \Osmium\Dogma\get_ship_attribute($fit, 'powerOutput');
$formatted = \Osmium\Chrome\format_used($powerUsed, $powerTotal, 2, true, $over);
echo "<p class='overflow$over'><img src='../static/icons/powergrid.png' alt='Powergrid' title='Powergrid' />".$formatted."</p>\n";
$upgradeCapacityUsed = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeLoad');
$upgradeCapacityTotal = \Osmium\Dogma\get_ship_attribute($fit, 'upgradeCapacity');
$formatted = \Osmium\Chrome\format_used($upgradeCapacityUsed, $upgradeCapacityTotal, 2, true, $over);
echo "<p class='overflow$over'><img src='../static/icons/calibration.png' alt='Calibration' title='Calibration' />".$formatted."</p>\n";
echo "</li>\n";

echo "</ul>\n";

echo "<h2>Fitting description</h2>\n";
echo "<p id='fitdesc'>\n".nl2br(htmlspecialchars($fit['metadata']['description']))."</p>\n";

echo "</div>\n";

/* ----------------------------------------------------- */

echo "<div id='vloadoutbox'>\n";

echo "<header>\n";
echo "<img src='http://image.eveonline.com/Render/".$fit['ship']['typeid']."_256.png' alt='".$fit['ship']['typename']."' id='fittypepic' />\n";
echo "<h2>".$fit['ship']['typename']." loadout</h2>\n";
echo "<h1 id='fitname'>";
echo \Osmium\Chrome\print_loadout_title($fit['metadata']['name'], $fit['metadata']['view_permission'], $author);
echo "</h1>\n";
echo "<div id='fittags'>\n<h2>Tags:</h2>\n";
if(count($fit['metadata']['tags']) > 0) {
	echo "<ul>\n";
	foreach($fit['metadata']['tags'] as $tag) {
		echo "<li><a href='../search?q=".urlencode('@tags '.$tag)."'>$tag</a></li>\n"; /* No escaping needed, tags are [A-Za-z0-9-] */
	}
	echo "</ul>\n</div>\n";
} else {
	echo "<em>(no tags)</em>";
}
echo "</header>\n";

$aslots = \Osmium\Fit\get_attr_slottypes();
$astates = \Osmium\Fit\get_state_names();
foreach(\Osmium\Fit\get_slottypes() as $type) {
	if(!isset($fit['modules'][$type])) continue;
	$modules = $fit['modules'][$type];

	$slotcount = \Osmium\Dogma\get_ship_attribute($fit, $aslots[$type], false);

	if(count($modules) == 0 && $slotcount == 0) continue;
	
	$used = count($modules);
	$class = in_array($type, \Osmium\Fit\get_stateful_slottypes()) ? ' stateful' : '';

	echo "<div id='{$type}_slots' class='slots$class'>\n<h3>".ucfirst($type)." slots <small class='capacity'>$used / $slotcount</small></h3>\n<ul>\n";

	foreach($modules as $index => $mod) {
		$charge = '';
		if(isset($fit['charges'][$preset][$type][$index])) {
			$charge = ",<br /><img src='http://image.eveonline.com/Type/".$fit['charges'][$preset][$type][$index]['typeid']."_32.png' alt='' />".$fit['charges'][$preset][$type][$index]['typename'];
		}

		list($stname, $stpicture) = $astates[$mod['state']];

		echo "<li class='index_$index'><img src='http://image.eveonline.com/Type/".$mod['typeid']."_32.png' alt='' />".$mod['typename']."<span class='charge'>$charge</span><a class='toggle' href='javascript:void(0);' title='$stname'><img src='../static/icons/$stpicture' alt='$stname' /></a></li>\n";
	}

	for($i = count($modules); $i < $slotcount; ++$i) {
		echo "<li class='unused'><img src='../static/icons/slot_$type.png' alt='' />Unused $type slot</li>\n";
	}

	echo "</ul>\n</div>\n";
}

if(($total = \Osmium\Dogma\get_ship_attribute($fit, 'droneCapacity')) > 0) {
	if(!isset($fit['drones'])) $fit['drones'] = array();

	$used = 0;
	foreach($fit['drones'] as $drone) $used += $drone['count'] * $drone['volume'];

	echo "<div id='vdronebay'>\n<h3>Drone bay <small class='capacity'>$used / $total m<sup>3</sup></small></h3>\n<ul>\n";

	foreach($fit['drones'] as $drone) {
		$qty = '';
		if($drone['count'] == 0) continue; /* Duh */
		if($drone['count'] > 1) {
			$qty = " <strong>×".$drone['count']."</strong>";
		}

		echo "<li><img src='http://image.eveonline.com/Type/".$drone['typeid']."_32.png' alt='' />".$drone['typename'].$qty."</li>\n";
	}

	if(count($fit['drones']) == 0) {
		echo "<li><em>(empty drone bay)</em></li>\n";
	}

	echo "</ul>\n</div>\n";
}

echo "</div>\n";

\Osmium\Chrome\print_footer();
