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

require __DIR__.'/../inc/root.php';
require __DIR__.'/../inc/ajax_common.php';

/* XXX: there is probably a way to slap a Last-Modified: header on
 * this to save some server resources */



/* Check permissions, password, private tokens. Populates the $fit,
 * $revision, $lodaoutid, $forkuri, $historyuri, $exporturi and
 * $revision_overridden variables. Defines the RELATIVE constant. */
require __DIR__.'/../inc/view_loadout-access.php';



$loggedin = \Osmium\State\is_logged_in();
$a = \Osmium\State\get_state('a', array());

if($loadoutid !== false) {
	$author = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
		'SELECT accountid, nickname, apiverified, characterid, charactername,
		corporationid, corporationname, allianceid, alliancename,
		ismoderator, reputation
		FROM osmium.accounts WHERE accountid = $1',
		array($fit['metadata']['accountid'])
	));

	$lastrev = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
		'SELECT updatedate, accountid, nickname, apiverified,
		characterid, charactername, ismoderator, reputation
		FROM osmium.loadouthistory
		JOIN osmium.accounts ON accounts.accountid = loadouthistory.updatedbyaccountid
		WHERE loadoutid = $1 AND revision = $2',
		array($loadoutid, $fit['metadata']['revision'])
	));

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
			)
		);

	$commentsallowed = ($commentsallowed === 't');
	$isflaggable = \Osmium\Flag\is_fit_flaggable($fit);
	$can_edit = \Osmium\State\can_edit_fit($loadoutid);
	$revision = $fit['metadata']['revision'];

	\Osmium\State\set_fit_green($fit['metadata']['loadoutid']);



	/* Insert the comment/reply if there is one. Requires
	 * $commentsallowed, $loggedin, $loadoutid, $a and $author to be
	 * set. */
	require __DIR__.'/../inc/view_loadout-comments.php';
} else {
	/* Thin loadout mode */
	$commentsallowed = false;
	$isflaggable = false;
	$can_edit = false;
}

$ismoderator = $loggedin && isset($a['ismoderator']) && ($a['ismoderator'] === 't');
$canedit = ($loadoutid !== false) && \Osmium\State\can_edit_fit($loadoutid);



/* Be compatible with old-style ?pid=X-Y&dpid=Z URIs. */
if(isset($_GET['pid'])) {
	$ids = explode('-', $_GET['pid'], 2);
	if(!isset($_GET['preset'])) {
		$_GET['preset'] = $ids[0];
	}
	if(isset($ids[1]) && !isset($_GET['chargepreset'])) {
		$_GET['chargepreset'] = $ids[1];
	}
}
if(isset($_GET['dpid']) && !isset($_GET['dronepreset'])) {
	$_GET['dpid'] = $_GET['dronepreset'];
}

$preset_overridden = false;
foreach(array('', 'charge', 'drone') as $ptype) {
	if(isset($_GET[$ptype.'preset']) && $_GET[$ptype.'preset'] !== '') {
		$p = intval($_GET[$ptype.'preset']);
		if(!isset($fit[$ptype.'presets'][$p])) {
			\Osmium\Fatal(400, "Invalid ".$ptype." preset");
		}
		call_user_func_array(
			'Osmium\Fit\use_'.$ptype.($ptype ? '_' : '').'preset',
			array(&$fit, $p)
		);
		$preset_overridden = true;
	}
}

$title = htmlspecialchars($fit['ship']['typename'].' / '.$fit['metadata']['name']);
if($revision_overridden) {
	$title .= ' (R'.$revision.')';
}

\Osmium\Chrome\print_header(
	$title, RELATIVE,
	($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PUBLIC
	 && !$revision_overridden
	 && !$preset_overridden)
);

echo "<h1>Viewing loadout: <strong class='fitname'>".htmlspecialchars($fit['metadata']['name'])."</strong></h1>\n";



list($groupname) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
	'SELECT groupname FROM eve.invtypes
	JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
	WHERE typeid = $1',
	array($fit['ship']['typeid'])
));

echo "<div id='vlattribs'>
<section id='ship'>
<h1>
<img src='//image.eveonline.com/Render/".$fit['ship']['typeid']."_256.png' alt='' />
<small class='groupname'>".htmlspecialchars($groupname)."</small>
<strong>".htmlspecialchars($fit['ship']['typename'])."</strong>
<small class='dbver'>".htmlspecialchars(\Osmium\Fit\get_closest_version_by_build($fit['metadata']['evebuildnumber'])['name'])."</small>
</h1>
</section>\n";

if($loadoutid !== false) {
	$class = $fit['metadata']['revision'] > 1 ? 'edited' : 'notedited';
	echo "<section id='credits' class='$class'>\n";

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
	echo \Osmium\Chrome\format_character_name($author, RELATIVE, $rauthorname)."<br />\n";
	echo \Osmium\Chrome\format_reputation($author['reputation']).' – '
		.\Osmium\Chrome\format_relative_date($truecreationdate)."\n";
	echo "</div>\n";

	if($fit['metadata']['revision'] > 1) {
		echo "<div class='author edit'>\n";
		echo "<small>revision #".$fit['metadata']['revision']." edited by</small><br />\n";
		echo \Osmium\Chrome\format_character_name($lastrev, RELATIVE)."<br />\n";
		echo \Osmium\Chrome\format_reputation($lastrev['reputation']).' – '
			.\Osmium\Chrome\format_relative_date($lastrev['updatedate'])."\n";
		echo "</div>\n";
	}

	echo "</section>";
}

echo "<section id='attributes'>
<div class='compact' id='computed_attributes'>\n";
\Osmium\Chrome\print_formatted_loadout_attributes($fit, RELATIVE);
echo "</div>
</section>
</div>\n";



echo "<div id='vlmain'>
<ul class='tabs'>
<li><a href='#loadout'>Loadout</a></li>
<li><a href='#presets'>Presets (".(max(count($fit['presets']), count($fit['chargepresets']), count($fit['dronepresets']))).")</a></li>
<li><a href='#comments'>Comments</a></li>
<li><a href='#meta'>Meta</a></li>
<li><a href='#export'>Export</a></li>
<li class='external'><a href='".$historyuri."' title='View the different revisions of the loadout and compare the changes that were made'>History (".($revision - 1).")</a></li>
<li class='external'><a rel='nofollow' href='".$forkuri."' title='Make a private copy of this loadout and edit it immediately'>Fork</a></li>
</ul>\n";



echo "<section id='loadout'>
<section id='modules'>\n";
$stypes = \Osmium\Fit\get_slottypes_names();
$stateful = array_flip(\Osmium\Fit\get_stateful_slottypes());
$slotusage = \Osmium\AjaxCommon\get_slot_usage($fit);
$states = \Osmium\Fit\get_state_names();
$fittedtotal = 0;
foreach($stypes as $type => $fname) {
	if($slotusage[$type] == 0 && (
		!isset($fit['modules'][$type]) || count($fit['modules'][$type]) === 0
	)) continue;

	$sub = isset($fit['modules'][$type]) ? $fit['modules'][$type] : array();

	if($type === "high" || $type === "medium") {
		$groupstatus = "grouped";
		$groupedcharges = "<small class='groupcharges'>Charges are grouped</small>";
	} else {
		$groupstatus = "ungrouped";
		$groupedcharges = "";
	}

	echo "<div class='slots $type $groupstatus'>\n<h3>";
	echo htmlspecialchars($fname)." <span>$groupedcharges";
	$u = count($sub);
	$overflow = $u > $slotusage[$type] ? ' overflow' : '';
	echo "<small class='counts$overflow'>".$u." / ".$slotusage[$type]."</small></span>";
	echo "</h3>\n<ul>\n";

	foreach($sub as $index => $m) {
		$s = $states[$m['state']];

		echo "<li data-typeid='".$m['typeid']."' data-slottype='"
			.$type."' data-index='".$index."' data-state='".$s[2]."'>";
		echo "<img src='//image.eveonline.com/Type/".$m['typeid']."_64.png' alt='' />";
		echo htmlspecialchars($m['typename']);

		if(isset($fit['charges'][$type][$index])) {
			$c = $fit['charges'][$type][$index];
			echo "<span class='charge'>,<br />";
			echo "<img src='//image.eveonline.com/Type/".$c['typeid']."_64.png' alt='' />";
			echo "<span class='name'>".htmlspecialchars($c['typename'])."</span>";
			echo "</span>";
		}

		if(isset($stateful[$type])) {
			echo "<a class='toggle_state' href='javascript:void(0);' title='".$s[0]."'>";
			echo "<img src='".RELATIVE."/static-".\Osmium\STATICVER."/icons/".$s[1]."' alt='".$s[0]."' />";
			echo "</a>";
		}

		echo "</li>\n";
		++$fittedtotal;
	}

	for($i = count($sub); $i < $slotusage[$type]; ++$i) {
		echo "<li class='placeholder'>";
		echo "<img src='".RELATIVE."/static-".\Osmium\STATICVER."/icons/slot_".$type.".png' alt='' />";
		echo "Unused ".$type." slot</li>\n";
		++$fittedtotal;
	}

	echo "</ul>\n</div>\n";
}
if($fittedtotal === 0) {
	echo "<p class='placeholder'>No available slots.</p>";
}

echo "</section>
<section id='drones'>\n";

$dronesin['space'] = 0;
$dronesin['bay'] = 0;
foreach($fit['drones'] as $typeid => $d) {
	if(isset($d['quantityinspace'])) { $dronesin['space'] += $d['quantityinspace']; }
	if(isset($d['quantityinbay'])) { $dronesin['bay'] += $d['quantityinbay']; }
}
foreach(array('space' => 'Drones in space', 'bay' => 'Drones in bay') as $k => $v) {
	if($dronesin[$k] === 0) continue;

	echo "<div class='drones ".$k."'>\n";
	echo "<h3>".htmlspecialchars($v)." <span>";

	if($k === 'space') {
		$dbw = \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth');
		$dbwu = \Osmium\Fit\get_used_drone_bandwidth($fit);
		$mad = \Osmium\Dogma\get_char_attribute($fit, 'maxActiveDrones');
		$ad = $dronesin[$k];

		$overflow = ($ad > $mad) ? ' overflow' : '';
		echo "<small title='Maximum number of drones in space' class='maxdrones$overflow'>$ad / $mad — </small>";
		$overflow = ($dbwu > $dbw) ? ' overflow' : '';
		echo "<small title='Drone bandwidth usage' class='bandwidth$overflow'>$dbwu / $dbw Mbps</small>";
	} else if($k === 'bay') {
		$dcap = \Osmium\Dogma\get_ship_attribute($fit, 'droneCapacity');
		$dcapu = \Osmium\Fit\get_used_drone_capacity($fit);

		$overflow = ($dcapu > $dcap) ? ' overflow' : '';
		echo "<small title='Drone bay usage' class='bayusage$overflow'>$dcapu / $dcap m³</small>";
	}

	echo "</span></h3>\n<ul>\n";

	foreach($fit['drones'] as $typeid => $d) {
		if(!isset($d['quantityin'.$k])) continue;
		$qty = (int)$d['quantityin'.$k];
		if($qty === 0) continue;

		echo "<li data-typeid='".$typeid."' data-location='".$k."' data-quantity='".$qty."'>";
		echo "<img src='//image.eveonline.com/Type/".$typeid."_64.png' alt='' />";
		echo "<strong class='qty'>".$qty."×</strong>".$d['typename'];
		echo "</li>\n";
	}
	echo "</ul>\n</div>\n";
}

echo "</section>
<section id='description'>
<h3>Fitting description</h3>\n";

$desc = \Osmium\Chrome\trim($fit['metadata']['description']);
if(empty($desc)) {
	echo "<p class='placeholder'>No description given.</p>\n";
} else {
	/* XXX: potential resource hog */
	echo "<div>\n".\Osmium\Chrome\format_sanitize_md($desc)."\n</div>\n";
}
echo "</section>\n";



echo "</section>
<section id='presets'>\n";

\Osmium\Forms\print_form_begin();
$first = true;
foreach([ ['presets', 'modulepreset', 'Preset', 'spreset'],
          ['chargepresets', 'chargepreset', 'Charge preset', 'scpreset'],
          ['dronepresets', 'dronepreset', 'Drone preset', 'sdpreset'] ] as $ptype) {
	list($parraykey, $pkey, $fname, $name) = $ptype;

	if($first) {
		$first = false;
	} else {
		\Osmium\Forms\print_separator();
	}

	$presets = array(); /* FIXME: use array_column later */
	foreach($fit[$parraykey] as $id => $p) {
		$presets[$id] = $p['name'];
	}
	$_POST[$name] = $fit[$pkey.'id'];
	\Osmium\Forms\print_select($fname, $name, $presets, null, null, \Osmium\Forms\FIELD_REMEMBER_VALUE);

	$desc = \Osmium\Chrome\trim($fit[$pkey.'desc']);
	if(empty($desc)) {
		$desc = '<p class="placeholder">No description available.</p>';
	} else {
		$desc = \Osmium\Chrome\format_sanitize_md($desc);
	}
	\Osmium\Forms\print_generic_row($name.'desc', '<label>Description</label>',
	                                "<div class='pdesc'>\n".$desc."\n</div>");
}

\Osmium\Forms\print_form_end();



echo "</section>
<section id='comments'>\n";



echo "</section>
<section id='meta'>\n";

/* Pretty prints permissions, show actions and moderator actions. */
require __DIR__.'/../inc/view_loadout-meta.php';

$dna = \Osmium\Fit\export_to_dna($fit);
echo "</section>
<section id='export'>
<h2>Lossless formats (recommended)</h2>
<ul>
<li><a href='".$exporturi('clf', 'json')."' type='application/json' rel='nofollow'><strong>Export to CLF (Common Loadout Format)</strong></a>: recommended for archival and for usage with other programs supporting it.</li>
<li><a href='".$exporturi('clf', 'json', false, ['minify' => 1])."' type='application/json' rel='nofollow'>Export to minified CLF</a>: same as above, minus all the redundant information. Not readable by humans.</li>
<li><a href='".$exporturi('md', 'txt')."' type='text/plain' rel='nofollow'>Export to Markdown+gzCLF</a>: a Markdown-formatted description of the loadout, with embedded CLF for programs.</li>
<li><a href='".$exporturi('evexml', 'xml', true)."' type='application/xml' rel='nofollow'><strong>Export to XML+gzCLF</strong></a>: recommended if you want to import loadouts from the game client.</li>
</ul>
<h2>Lossy formats</h2>
<p>Only use those formats if none of the lossless option is usable for your situation.</p>
<ul>
<li><a href='".$exporturi('evexml', 'xml', true, ['embedclf' => 0])."' type='application/xml' rel='nofollow'>Export to XML</a>: same as XML+gzCLF, minus the description.</li>
<li><a href='".$exporturi('eft', 'txt', true)."' type='text/plain' rel='nofollow'>Export to EFT</a>: the <em>de-facto</em> format used by the fitting tool EFT.</li>
<li><a href='".$exporturi('dna', 'txt', true)."' type='text/plain' rel='nofollow'>Export to DNA</a>: short format that can be understood by the game client.<br /><small><code>".$dna."</code></small></li>
<li><a href='javascript:CCPEVE.showFitting(\"".$dna."\");' rel='nofollow'>Export to in-game DNA</a>: use this link to open the loadout window from the in-game browser.</li>
</ul>
</section>\n";

echo "</div>\n";

\Osmium\Chrome\print_js_code(
"osmium_cdatastaticver = ".\Osmium\CLIENT_DATA_STATICVER.";
osmium_staticver = ".\Osmium\STATICVER.";
osmium_relative = '".RELATIVE."';
osmium_token = '".\Osmium\State\get_token()."';
osmium_clftoken = '".\Osmium\State\get_unique_new_loadout_token()."';
osmium_clf = ".json_encode(\Osmium\Fit\export_to_common_loadout_format_1($fit, true, true, true)).";
osmium_skillsets = ".json_encode(\Osmium\Fit\get_available_skillset_names_for_account()).";"
);

\Osmium\Chrome\print_js_snippet('tabs');
\Osmium\Chrome\print_js_snippet('modal');
\Osmium\Chrome\print_js_snippet('context_menu');
\Osmium\Chrome\print_js_snippet('loadout_common');
\Osmium\Chrome\print_js_snippet('show_info');
\Osmium\Chrome\print_js_snippet('view_loadout');
\Osmium\Chrome\print_js_snippet('formatted_attributes');
\Osmium\Chrome\print_footer();
