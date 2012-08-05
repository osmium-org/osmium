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
const COMMENTS_PER_PAGE = 10;

require __DIR__.'/../inc/root.php';

$loadoutid = intval($_GET['loadoutid']);

if(!\Osmium\State\can_view_fit($loadoutid)) {
	\Osmium\fatal(404, 'Loadout not found.');
}

if(isset($_GET['revision'])) {
	$fit = \Osmium\Fit\get_fit($loadoutid, $_GET['revision']);
	$fitlatestrev = false; /* FIXME this is not always false */

	if($fit === false) {
		\Osmium\fatal(500, '\Osmium\Fit\get_fit() returned false, are you sure you specified a valid revision number? (loadoutid: '.$loadoutid.')');
	}
} else {
	$fit = \Osmium\Fit\get_fit($loadoutid);
	$fitlatestrev = true;

	if($fit === false) {
		\Osmium\fatal(500, '\Osmium\Fit\get_fit() returned false, please report! (loadoutid: '.$loadoutid.')');
	}
}

$loggedin = \Osmium\State\is_logged_in();
$a = \Osmium\State\get_state('a', array());

$author = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT accountid, nickname, apiverified, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator, reputation FROM osmium.accounts WHERE accountid = $1', array($fit['metadata']['accountid'])));
$lastrev = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT updatedate, accountid, nickname, apiverified, characterid, charactername, ismoderator, reputation FROM osmium.loadouthistory JOIN osmium.accounts ON accounts.accountid = loadouthistory.updatedbyaccountid WHERE loadoutid = $1 AND revision = $2', array($loadoutid, $fit['metadata']['revision'])));
list($truecreationdate, $commentsallowed, $votetype, $totalvotes, $totalupvotes, $totaldownvotes) = 
	\Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT updatedate, allowcomments, v.type, ludv.votes, ludv.upvotes, ludv.downvotes
			FROM osmium.loadouts AS l
			JOIN osmium.loadouthistory AS lh ON (l.loadoutid = lh.loadoutid AND lh.revision = 1)
			LEFT JOIN osmium.votes AS v ON (v.type IN ($2, $3) AND v.targetid1 = l.loadoutid 
				AND v.targetid2 IS NULL AND v.targetid3 IS NULL 
				AND v.fromaccountid = $4 AND v.targettype = $5)
			JOIN osmium.loadoutupdownvotes AS ludv ON ludv.loadoutid = l.loadoutid
			WHERE l.loadoutid = $1',
			array(
				$loadoutid,
				\Osmium\Reputation\VOTE_TYPE_UP,
				\Osmium\Reputation\VOTE_TYPE_DOWN,
				$loggedin ? $a['accountid'] : 0,
				\Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT
				)
			));

$commentsallowed = ($commentsallowed === 't');
$ismoderator = $loggedin && isset($a['ismoderator']) && ($a['ismoderator'] === 't');
$isflaggable = \Osmium\Flag\is_fit_flaggable($fit);

$can_edit = \Osmium\State\can_edit_fit($loadoutid);

if(!\Osmium\State\can_access_fit($fit)) {
	if(!isset($_POST['pw']) || !\Osmium\State\check_password($_POST['pw'], $fit['metadata']['password'])) {
		if(isset($_POST['pw'])) {
			\Osmium\Forms\add_field_error('pw', 'Incorrect password.');
		}
      
		/* Show the password form */
		\Osmium\Chrome\print_header('Password-protected fit requires authentication', '..', false);
      
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

if($commentsallowed && isset($_POST['commentbody']) && $loggedin) {
	$body = trim($_POST['commentbody']);
	$formatted = \Osmium\Chrome\format_sanitize_md($body);

	if($body && $formatted) {
		\Osmium\Db\query('BEGIN;');
		$r = \Osmium\Db\query_params('INSERT INTO osmium.loadoutcomments (loadoutid, accountid, creationdate, revision) VALUES ($1, $2, $3, $4) RETURNING commentid', array($loadoutid, $a['accountid'], $t = time(), $fit['metadata']['revision']));
		if($r !== false && ($r = \Osmium\Db\fetch_row($r)) !== false) {
			list($commentid) = $r;
			\Osmium\Db\query_params('INSERT INTO osmium.loadoutcommentrevisions (commentid, revision, updatedbyaccountid, updatedate, commentbody, commentformattedbody) VALUES ($1, $2, $3, $4, $5, $6)', array($commentid, 1, $a['accountid'], $t, $_POST['commentbody'], $formatted));

			if($a['accountid'] != $author['accountid']) {
				\Osmium\Notification\add_notification(
					\Osmium\Notification\NOTIFICATION_TYPE_LOADOUT_COMMENTED,
					$a['accountid'], $author['accountid'], $commentid, $loadoutid);
			}

			\Osmium\Log\add_log_entry(
				\Osmium\Log\LOG_TYPE_CREATE_COMMENT,
				null, $commentid, $loadoutid);

			\Osmium\Db\query('COMMIT;');
			header('Location: #c'.$commentid);
			die();
		} else {
			\Osmium\Db\query('ROLLBACK;');
		}
	}
} else if($commentsallowed && isset($_POST['replybody']) && $loggedin) {
	$commentexists = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT commentid FROM osmium.loadoutcomments WHERE commentid = $1 AND loadoutid = $2 AND revision <= $3', array($_POST['commentid'], $loadoutid, $fit['metadata']['revision'])));

	if($commentexists !== false) {
		$body = trim($_POST['replybody']);
		$formatted = \Osmium\Chrome\format_sanitize_md_phrasing($body);

		if($body && $formatted) {
			\Osmium\Db\query('BEGIN;');
			$r = \Osmium\Db\query_params('INSERT INTO osmium.loadoutcommentreplies (commentid, accountid, creationdate, replybody, replyformattedbody, updatedate, updatedbyaccountid) VALUES ($1, $2, $3, $4, $5, null, null) RETURNING commentreplyid', array($_POST['commentid'], $a['accountid'], time(), $body, $formatted));

			if($r !== false && ($r = \Osmium\Db\fetch_row($r)) !== false) {
				list($crid) = $r;

				/* Notify the comment author and all other users who replied before */
				$ids = \Osmium\Db\query_params('SELECT accountid FROM osmium.loadoutcomments WHERE commentid = $1 UNION SELECT DISTINCT accountid FROM osmium.loadoutcommentreplies WHERE commentid = $1', array($_POST['commentid']));
				while($id = \Osmium\Db\fetch_row($ids)) {
					$id = $id[0];
					if($id == $a['accountid']) continue;

					\Osmium\Notification\add_notification(
						\Osmium\Notification\NOTIFICATION_TYPE_COMMENT_REPLIED,
						$a['accountid'], $id, $crid, $_POST['commentid'], $loadoutid);
				}

				\Osmium\Log\add_log_entry(
					\Osmium\Log\LOG_TYPE_CREATE_COMMENT_REPLY,
					null, $crid, $_POST['commentid'], $loadoutid);

				\Osmium\Db\query('COMMIT;');
				header('Location: #r'.$crid);
				die();
			} else {
				\Osmium\Db\query('ROLLBACK;');
			}
		}
	}
}

$green_fits = \Osmium\State\get_state('green_fits', array());
$green_fits[$fit['metadata']['loadoutid']] = true;
\Osmium\State\put_state('green_fits', $green_fits);

$defaultpid = $fit['modulepresetid'];
$defaultcpid = $fit['chargepresetid'];
$defaultdpid = $fit['dronepresetid'];

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

$title = htmlspecialchars($fit['ship']['typename'].' / '.$fit['metadata']['name']);
if(!$fitlatestrev) {
	$title .= " (revision ".$fit['metadata']['revision'].")";
}
if(count($fit['presets']) > 1 || count($fit['chargepresets']) > 1 || count($fit['dronepresets']) > 1) {
	$title .= " / ";
}
if(count($fit['presets']) > 1) {
	$title .= htmlspecialchars($fit['modulepresetname']);
}
if(count($fit['chargepresets']) > 1) {
	if(count($fit['presets']) > 1) {
		$title .= ', ';
	}
	$title .= htmlspecialchars($fit['chargepresetname']);
}
if(count($fit['dronepresets']) > 1) {
	if(count($fit['presets']) > 1 || count($fit['chargepresets']) > 1) {
		$title .= ', ';
	}
	$title .= htmlspecialchars($fit['dronepresetname']);
}

\Osmium\Chrome\print_header(
	$title, '..',
	$fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PUBLIC
	&& !isset($_GET['jtc'])
	&& ((!isset($_GET['pid']) && !isset($_GET['dpid']))
	    || $fit['modulepresetid'] != $defaultpid
	    || $fit['chargepresetid'] != $defaultcpid
	    || $fit['dronepresetid'] != $defaultdpid)
	);

/* ----------------------------------------------------- */

echo "<div id='metadatabox'>\n";
echo "<h2>Fitting metadata</h2>\n";

$class = $fit['metadata']['revision'] > 1 ? 'edited' : 'notedited';
echo "<div id='loadoutcredits' class='$class'>\n";
echo "<div class='author'>\n";
if($author['apiverified'] === 't') {
	if($author['allianceid'] > 0) {
		echo "<img class='alliance' src='http://image.eveonline.com/Alliance/".$author['allianceid']."_128.png' alt='' title='member of ".htmlspecialchars($author['alliancename'], ENT_QUOTES)."' />";
	} else {
		echo "<img class='corporation' src='http://image.eveonline.com/Corporation/".$author['corporationid']."_256.png' alt='' title='member of ".htmlspecialchars($author['corporationname'], ENT_QUOTES)."' />";
	}
	if($author['characterid'] > 0) {
		echo "<img class='portrait' src='http://image.eveonline.com/Character/".$author['characterid']."_256.jpg' alt='' />";
	}
}
echo "<small>submitted by</small><br />\n";
echo \Osmium\Chrome\format_character_name($author, '..', $rauthorname)."<br />\n";
echo \Osmium\Chrome\format_reputation($author['reputation']).' – '.\Osmium\Chrome\format_relative_date($truecreationdate)."\n";
echo "</div>\n";

if($fit['metadata']['revision'] > 1) {
	echo "<div class='author edit'>\n";
	echo "<small>revision #".$fit['metadata']['revision']." edited by</small><br />\n";
	echo \Osmium\Chrome\format_character_name($lastrev, '..')."<br />\n";
	echo \Osmium\Chrome\format_reputation($lastrev['reputation']).' – '
		.\Osmium\Chrome\format_relative_date($lastrev['updatedate'])."\n";
	echo "</div>\n";
}
echo "</div>\n";

if(count($fit['dronepresets']) > 1 || count($fit['presets']) > 1 || count($fit['chargepresets']) > 1) {
	$action = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
	echo "<form method='get' action='$action' class='presets'>\n";

	foreach(array(array('presets', 'pid', 'modulepresetid', 'preset'),
	              array('chargepresets', 'cpid', 'chargepresetid', 'chargepreset'),
	              array('dronepresets', 'dpid', 'dronepresetid', 'dronepreset')) as $presettype) {
		list($key, $name, $current, $selectid) = $presettype;


	}

	if(count($fit['presets']) > 1 || count($fit['chargepresets']) > 1) {
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

echo "<div id='computed_attributes'>\n";

echo "<section id='vmeta'>\n<h4>Meta</h4>\n<div>\n<ul>\n";
if($can_edit) {
	echo "<li><a href='../edit/".$loadoutid."?tok=".\Osmium\State\get_token()."'><strong>Edit this loadout</strong></a></li>\n";
	echo "<li><a href='../delete/".$loadoutid."?tok=".\Osmium\State\get_token()."' class='dangerous' onclick='return confirm(\"Deleting this loadout will also delete all its history, and cannot be undone. Are you sure you want to continue?\");'><strong>Delete this loadout</strong></a></li>\n";
}
if($isflaggable && $fitlatestrev) {
	echo "<li><a class='dangerous' href='../flag/".$loadoutid."' title='This loadout requires moderator attention'>Flag this loadout</a></li>\n";
}
echo "<li><a href='../loadouthistory/$loadoutid'>View revision history</a></li>\n";
echo "<li><a href='../search?q=".urlencode('@ship "'.$fit['ship']['typename'].'"')."'>Browse all ".$fit['ship']['typename']." loadouts</a></li>\n";
echo "<li><a href='../search?q=".urlencode('@author "'.htmlspecialchars($rauthorname, ENT_QUOTES).'"')."'>Browse loadouts from the same author</a></li>\n";

$slugname = ($author['apiverified'] === 't' && $author['charactername']) ? $author['charactername'] : $author['nickname'];
$slug = $slugname.' '.$fit['ship']['typename'].' '.$fit['metadata']['name'].' '.$fit['metadata']['revision'];
$slug = preg_replace('%[^a-z0-9-]%', '', str_replace(' ', '-', strtolower($slug)));
$presets = 'pid='.$fit['modulepresetid'].'&amp;cpid='.$fit['chargepresetid'].'&amp;dpid='.$fit['dronepresetid'];
$dna = \Osmium\Fit\export_to_dna($fit);
echo "<li class='export'>Export this loadout:\n<ul>\n";
echo "<li>Lossless formats: <a href='../export/{$slug}-clf-{$loadoutid}.json' title='Common Loadout Format, human-readable' type='application/json'>CLF</a>, <a title='Common Loadout Format, minified' href='../export/{$slug}-clf-{$loadoutid}.json?minify=1' type='application/json'>minified CLF</a>, <a href='../export/{$slug}-md-{$loadoutid}.md' title='Markdown with embedded gzipped Common Loadout Format' type='text/plain'>Markdown+gzCLF</a>, <a href='../export/{$slug}-evexml-{$loadoutid}.xml?{$presets}' title='EVE XML with embedded gzipped Common Loadout Format' type='application/xml'>XML+gzCLF</a></li>\n";
echo "<li>Lossy formats: <a href='../export/{$slug}-evexml-{$loadoutid}.xml?embedclf=0&amp;{$presets}' title='EVE XML' type='application/xml'>XML</a>, <a href='../export/{$slug}-eft-{$loadoutid}.txt?{$presets}' type='text/plain'>EFT</a>, <a href='../export/{$slug}-dna-{$loadoutid}.txt?{$presets}' type='text/plain'>DNA</a>, <a href='javascript:CCPEVE.showFitting(\"$dna\");'>in-game DNA</a></li>\n";
echo "</ul>\n</li>\n";

echo "</ul>\n</div>\n</section>\n";

\Osmium\Chrome\print_formatted_loadout_attributes($fit, '..');
echo "</div>\n";

echo "</div>\n";

/* ----------------------------------------------------- */

echo "<div id='vloadoutbox' data-loadoutid='".$fit['metadata']['loadoutid']."' data-revision='".$fit['metadata']['revision']."' data-presetid='".$fit['modulepresetid']."' data-cpid='".$fit['chargepresetid']."' data-dpid='".$fit['dronepresetid']."'>\n";

echo "<header>\n";
echo "<h2>".$fit['ship']['typename']." loadout</h2>\n";
echo "<img src='http://image.eveonline.com/Render/".$fit['ship']['typeid']."_256.png' alt='".$fit['ship']['typename']."' id='fittypepic' />\n";
echo "<h1 id='fitname' class='has_spinner'>";
echo \Osmium\Chrome\print_loadout_title($fit['metadata']['name'], $fit['metadata']['view_permission'], $fit['metadata']['visibility'], $author, '..', $fit['metadata']['loadoutid']);
echo "<img src='../static-".\Osmium\STATICVER."/icons/spinner.gif' id='vloadoutbox_spinner' class='spinner' alt='' /></h1>\n";
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
echo "</div>\n";
echo "<div class='votes' data-targettype='loadout'>\n";
echo "<a title='This loadout is creative, useful, and fills the role it was designed for' class='upvote".($votetype == \Osmium\Reputation\VOTE_TYPE_UP ? ' voted' : '')."'><img src='../static-".\Osmium\STATICVER."/icons/vote.svg' alt='upvote' /></a>\n";
echo "<strong title='".$totalupvotes." upvote(s), ".$totaldownvotes." downvote(s)'>".$totalvotes."</strong>\n";
echo "<a title='This loadout suffers from severe flaws, is badly formatted, or shows no research effort' class='downvote".($votetype == \Osmium\Reputation\VOTE_TYPE_DOWN ? ' voted' : '')."'><img src='../static-".\Osmium\STATICVER."/icons/vote.svg' alt='downvote' /></a>\n";
echo "</div>\n";
echo "</header>\n";

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
			$charge = ",<br /><img src='http://image.eveonline.com/Type/".$fit['charges'][$type][$index]['typeid']."_64.png' alt='' />".$fit['charges'][$type][$index]['typename'];
		}

		list($stname, $stpicture) = $astates[$state];

		echo "<li data-typeid='".$mod['typeid']."' data-index='".$index."' data-slottype='".$type."' data-state='".$state."'><img src='http://image.eveonline.com/Type/".$mod['typeid']."_64.png' alt='' />".$mod['typename']."<span class='charge'>$charge</span>";
		echo "<a class='toggle' href='javascript:void(0);' title='$stname; click to toggle'><img src='../static-".\Osmium\STATICVER."/icons/$stpicture' alt='$stname' /></a>";

		if($ranges !== array()) {
			echo "<span class='range' title='".\Osmium\Chrome\format_long_range($ranges)."'>".\Osmium\Chrome\format_short_range($ranges)."</span>";
		}

		echo "</li>\n";
	}

	for($i = count($modules); $i < $slotcount; ++$i) {
		echo "<li class='unused'><img src='../static-".\Osmium\STATICVER."/icons/slot_$type.png' alt='' />Unused $type slot</li>\n";
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

	echo "<div id='vdronebay'>\n<h3>Drones <small class='capacity'><span><img src='../static-".\Osmium\STATICVER."/icons/bandwidth_ds.png' alt='Drone bandwidth' title='Drone bandwidth' /><span id='dronebandwidth'>$usedbandwidth / $totalbandwidth</span> Mbit/s</span><span><img src='../static-".\Osmium\STATICVER."/icons/dronecapacity_ds.png' alt='Drone capacity' title='Drone capacity' />$usedcapacity / $totalcapacity m<sup>3</sup></span></small></h3>\n";

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
			
			echo "<li data-typeid='$typeid' data-count='$quantity'><img src='http://image.eveonline.com/Type/".$drone['typeid']."_64.png' alt='' />".$drone['typename'].$qty."</li>\n";
			++$z;
		}

		if($z === 0) {
			echo "<li><em>(no drones in $v)</em></li>\n";
		}

		echo "</ul>\n</div>\n";
	}

	echo "</div>\n";
}

/* TODO: cache the formatted descriptions if this becomes a resource hog */
echo "<div id='vdescriptions'>\n";
echo "<section id='fitdesc'>\n<h2>Fitting description</h2>\n";
echo \Osmium\Chrome\format_sanitize_md($fit['metadata']['description'])."</section>\n";
if(isset($fit['modulepresetdesc']) && $fit['modulepresetdesc']) {
	echo "<section id='presetdesc'>\n<h3>Preset description</h3>\n"
		.\Osmium\Chrome\format_sanitize_md($fit['modulepresetdesc'])."</section>\n";
}
if(isset($fit['chargepresetdesc']) && $fit['chargepresetdesc']) {
	echo "<section id='chargepresetdesc'>\n<h3>Charge preset description</h3>\n"
		.\Osmium\Chrome\format_sanitize_md($fit['chargepresetdesc'])."</section>\n";
}
if(isset($fit['dronepresetdesc']) && $fit['dronepresetdesc']) {
	echo "<section id='dronepresetdesc'>\n<h3>Drone preset description</h3>\n"
		.\Osmium\Chrome\format_sanitize_md($fit['dronepresetdesc'])."</section>\n";
}
echo "</div>\n";

echo "<div id='vcomments'>\n<h2>Comments</h2>\n";

list($totalcomments) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(commentid) FROM osmium.loadoutcomments WHERE loadoutid = $1 AND revision <= $2', array($loadoutid, $fit['metadata']['revision'])));

$pageoverride = null;
if(isset($_GET['jtc']) && $_GET['jtc'] > 0) {
	$jtc = $_GET['jtc'];
	unset($_GET['jtc']);

	$r = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT revision, creationdate FROM osmium.loadoutcomments WHERE commentid = $1', array($jtc)));

	if($r !== false) {
		list($before) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(commentid) FROM osmium.loadoutcomments WHERE loadoutid = $1 AND revision <= $2 AND (revision > $3 OR (revision = $3 AND creationdate > $4))', array($loadoutid, $fit['metadata']['revision'], $r[0], $r[1])));

		$pageoverride = 1 + floor($before / COMMENTS_PER_PAGE);
	}
}

$offset = \Osmium\Chrome\paginate('pagec', COMMENTS_PER_PAGE, $totalcomments, $result, $metaresult, $pageoverride, '', '#vcomments');

$commentidsq = \Osmium\Db\query_params('SELECT commentid FROM osmium.loadoutcomments WHERE loadoutid = $1 AND revision <= $2 ORDER BY revision DESC, creationdate DESC LIMIT $3 OFFSET $4', array($loadoutid, $fit['metadata']['revision'], COMMENTS_PER_PAGE, $offset));
$commentids = array(-1);
while($r = \Osmium\Db\fetch_row($commentidsq)) {
	$commentids[] = $r[0];
}
$cq = \Osmium\Db\query_params(
'SELECT lc.commentid, lc.accountid, lc.creationdate, lc.revision AS loadoutrevision,
lcudv.votes, lcudv.upvotes, lcudv.downvotes, v.type AS votetype,
lcrev.revision AS commentrevision, lcrev.updatedbyaccountid, lcrev.updatedate, lcrev.commentformattedbody,
lcrep.commentreplyid, lcrep.creationdate AS repcreationdate, lcrep.replyformattedbody, lcrep.updatedate AS repupdatedate,
cacc.accountid, cacc.nickname, cacc.apiverified, cacc.characterid, cacc.charactername, cacc.ismoderator, cacc.reputation,
racc.accountid AS raccountid, racc.nickname AS rnickname, racc.apiverified AS rapiverified, racc.characterid AS rcharacterid, racc.charactername AS rcharactername, racc.ismoderator AS rismoderator,
uacc.accountid AS uaccountid, uacc.nickname AS unickname, uacc.apiverified AS uapiverified, uacc.characterid AS ucharacterid, uacc.charactername AS ucharactername, uacc.ismoderator AS uismoderator, uacc.reputation AS ureputation
FROM osmium.loadoutcomments AS lc
JOIN osmium.loadoutcommentupdownvotes AS lcudv ON lcudv.commentid = lc.commentid
LEFT JOIN osmium.votes AS v ON (v.targettype = $1 AND v.type IN ($2, $3)
                                AND v.fromaccountid = $4 AND v.targetid1 = lc.commentid 
                                AND v.targetid2 = lc.loadoutid AND v.targetid3 IS NULL)
JOIN osmium.accounts AS cacc ON cacc.accountid = lc.accountid
JOIN osmium.loadoutcommentslatestrevision AS lclr ON lc.commentid = lclr.commentid
JOIN osmium.loadoutcommentrevisions AS lcrev ON lcrev.commentid = lc.commentid
                                             AND lcrev.revision = lclr.latestrevision
JOIN osmium.accounts AS uacc ON uacc.accountid = lcrev.updatedbyaccountid
LEFT JOIN osmium.loadoutcommentreplies AS lcrep ON lcrep.commentid = lc.commentid
LEFT JOIN osmium.accounts AS racc ON racc.accountid = lcrep.accountid
WHERE lc.commentid IN ('.implode(',', $commentids).')
ORDER BY lc.revision DESC, lcudv.votes DESC, lcrep.creationdate ASC',
array(
	\Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT,
	\Osmium\Reputation\VOTE_TYPE_UP,
	\Osmium\Reputation\VOTE_TYPE_DOWN,
	$loggedin ? $a['accountid'] : 0,
	));

$endcomment = function($commentid) use(&$loggedin) {
	if($loggedin) {
		echo "<li class='new'><form method='post' action='#creplies".$commentid."' accept-charset='utf-8'><textarea name='replybody' placeholder='Type your reply… (Markdown and some HTML allowed, basic formatting only)'></textarea> <input type='hidden' name='commentid' value='".$commentid."' /><input type='submit' value='Submit reply' /></form></li>\n";
	}

	echo "</ul>\n";
	if($loggedin) echo "<a href='javascript:void(0);' class='add_comment'>reply to this comment</a>\n";
	echo "</div>\n";
};

$previouscommentid = false;
while($row = \Osmium\Db\fetch_assoc($cq)) {
	if($row['commentid'] !== $previouscommentid) {
		if($previouscommentid !== false) {
			$endcomment($previouscommentid);
		}
		$previouscommentid = $row['commentid'];

		/* Show comment */
		echo "<div class='comment' id='c".$row['commentid']."' data-commentid='".$row['commentid']."'>\n";

		echo "<div class='votes' data-targettype='comment'>\n";
		echo "<a title='This comment is useful' class='upvote".($row['votetype'] == \Osmium\Reputation\VOTE_TYPE_UP ? ' voted' : '')."'><img src='../static-".\Osmium\STATICVER."/icons/vote.svg' alt='upvote' /></a>\n";
		echo "<strong title='".$row['upvotes']." upvote(s), ".$row['downvotes']." downvote(s)'>".$row['votes']."</strong>\n";
		echo "<a title='This comment is off-topic, not constructive or not useful' class='downvote".($row['votetype'] == \Osmium\Reputation\VOTE_TYPE_DOWN ? ' voted' : '')."'><img src='../static-".\Osmium\STATICVER."/icons/vote.svg' alt='downvote' /></a>\n";
		echo "</div>\n";

		echo "<div class='body'>\n".$row['commentformattedbody']."</div>\n";

		echo "<header>\n<div class='author'>\n";
		if($row['apiverified'] === 't' && $row['characterid'] > 0) {
			echo "<img class='portrait' src='http://image.eveonline.com/Character/".$row['characterid']."_256.jpg' alt='' />";
		}
		echo "<small>commented by</small><br />\n";
		echo \Osmium\Chrome\format_character_name($row, '..')."<br />\n";
		echo \Osmium\Chrome\format_reputation($row['reputation']).' – '
			.\Osmium\Chrome\format_relative_date($row['creationdate'])."\n";
		echo "</div>\n";

		if($row['commentrevision'] > 1) {
			echo "<div class='author edit'>\n";
			echo "<small>revision #".$row['commentrevision']." edited by</small><br />\n";
			$u = array('accountid' => $row['uaccountid'],
			           'nickname' => $row['unickname'],
			           'apiverified' => $row['uapiverified'],
			           'characterid' => $row['ucharacterid'],
			           'charactername' => $row['ucharactername'],
			           'ismoderator' => $row['uismoderator']);
			echo \Osmium\Chrome\format_character_name($u, '..')."<br />\n";
			echo \Osmium\Chrome\format_reputation($row['ureputation']).' – '
				.\Osmium\Chrome\format_relative_date($row['updatedate'])."\n";
			echo "</div>\n";
		}

		echo "<div class='meta'>\n";
		echo "<a href='?jtc=".$row['commentid']."#c".$row['commentid']."'>permanent link</a>";

		if($ismoderator || ($loggedin && $row['accountid'] == $a['accountid'])) {
			echo " — <a href='../editcomment/".$row['commentid']."'>edit</a>";
			echo " — <a onclick='return confirm(\"Deleting this comment will also delete all its replies. This operation cannot be undone. Continue?\");' href='../deletecomment/".$row['commentid']."?tok=".\Osmium\State\get_token()."' class='dangerous'>delete</a>";
		}
		if($isflaggable) {
			echo " — <a class='dangerous' href='../flagcomment/".$row['commentid']."' title='This comment requires moderator attention'>flag</a>";
		}

		if($row['loadoutrevision'] < $fit['metadata']['revision']) {
			echo "<br />\n<span class='outdated'>(this comment applies to a previous revision of this loadout: <a href='?revision=".$row['loadoutrevision']."'>revision #".$row['loadoutrevision']."</a>)</span>\n";
		}

		echo "</div>\n</header>\n";
		echo "<ul id='creplies".$row['commentid']."' class='replies'>\n";
	}

	if($row['commentreplyid'] !== null) {
		/* Show reply */
		$c = array('accountid' => $row['raccountid'],
		           'nickname' => $row['rnickname'],
		           'apiverified' => $row['rapiverified'],
		           'characterid' => $row['rcharacterid'],
		           'charactername' => $row['rcharactername'],
		           'ismoderator' => $row['rismoderator']);

		echo "<li id='r".$row['commentreplyid']."'>\n<div class='body'>".$row['replyformattedbody']."</div>";
		echo " — ".\Osmium\Chrome\format_character_name($c, '..');
		if($row['repupdatedate'] !== null) {
			echo " <span class='updated' title='This reply was edited (".strip_tags(\Osmium\Chrome\format_relative_date($row['repupdatedate'])).").'>✎</span>";
		}

		echo " — ".\Osmium\Chrome\format_relative_date($row['repcreationdate']);

		echo "<span class='meta'>";
		echo " — <a href='?jtc=".$row['commentid']."#r".$row['commentreplyid']."'>#</a>";

		if($ismoderator || ($loggedin && $row['raccountid'] == $a['accountid'])) {
			echo " — <a href='../editcommentreply/".$row['commentreplyid']."'>edit</a>";
			echo " — <a onclick='return confirm(\"You are about to delete a reply. This operation cannot be undone. Continue?\");' href='../deletecommentreply/".$row['commentreplyid']."?tok=".\Osmium\State\get_token()."' class='dangerous'>delete</a>";
		}
		if($isflaggable) {
			echo " — <a class='dangerous' href='../flagcommentreply/".$row['commentreplyid']."' title='This comment reply requires moderator attention'>flag</a>";
		}
		echo "</span>";

		echo "</li>\n";
	}
}

if($previouscommentid !== false) {
	$endcomment($previouscommentid);
}

echo $result;

if($commentsallowed && $loggedin) {
	echo "<h3>Add a comment</h3>\n";

	\Osmium\Forms\print_form_begin(htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES).'#vcomments');
	\Osmium\Forms\print_textarea('Comment body<br /><small>(Markdown and some HTML allowed)</small>', 'commentbody', 'commentbody');
	\Osmium\Forms\print_submit('Submit comment');
	\Osmium\Forms\print_form_end();
}

echo "</div>\n";

echo "</div>\n";

\Osmium\Chrome\print_js_snippet('formatted_attributes');
\Osmium\Chrome\print_js_snippet('view_loadout');
echo "<script>\nosmium_staticver = ".\Osmium\STATICVER.";\n</script>\n";
\Osmium\Chrome\print_footer();
