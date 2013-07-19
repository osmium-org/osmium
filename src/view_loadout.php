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



	/* Insert the comment/reply if there is one. Jump to the correct
	 * page if a ?jtc URI is present. Requires $commentsallowed,
	 * $loggedin, $loadoutid, $a and $author to be set. Sets the
	 * $commentperpage variable. */
	require __DIR__.'/../inc/view_loadout-comments.php';
} else {
	/* Thin loadout mode */
	$commentsallowed = false;
	$isflaggable = false;
	$can_edit = false;
}

$ismoderator = $loggedin && isset($a['ismoderator']) && ($a['ismoderator'] === 't');
$canedit = ($loadoutid !== false) && \Osmium\State\can_edit_fit($loadoutid);
$modprefix = $ismoderator ? '<span title="Moderator action">'.\Osmium\Flag\MODERATOR_SYMBOL.'</span> ' : '';



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
	$_GET['dronepreset'] = $_GET['dpid'];
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

if(count($fit['metadata']['tags']) > 0) {
	$tags = ' ('.implode(', ', $fit['metadata']['tags']).')';
} else {
	$tags = '';
}

$title = htmlspecialchars($fit['metadata']['name'].$tags.' / '.$fit['ship']['typename'].' fitting');
if($revision_overridden) {
	$title .= ' (R'.$revision.')';
}

\Osmium\Chrome\print_header(
	$title, RELATIVE,
	($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PUBLIC
	 && !$revision_overridden
	 && !$preset_overridden)
);

echo "<h1 id='vltitle'>Viewing loadout: <strong class='fitname'>"
.htmlspecialchars($fit['metadata']['name'])."</strong>";

if(count($fit['metadata']['tags']) > 0) {
	echo "\n<ul class='tags'>\n";
	foreach($fit['metadata']['tags'] as $tag) {
		echo "<li><a href='".RELATIVE."/search?q=".urlencode('@tags '.$tag)."'>$tag</a></li>\n";
	}
	echo "</ul>\n";
}
echo "</h1>\n";


list($groupname) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
	'SELECT groupname FROM eve.invtypes
	JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
	WHERE typeid = $1',
	array($fit['ship']['typeid'])
));


list($commentcount) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
	'SELECT COUNT(commentid) FROM osmium.loadoutcomments
	WHERE loadoutid = $1 AND revision <= $2',
	array(
		(int)$loadoutid,
		$revision,
	)
));
$commentcount = (int)$commentcount;

echo "<div id='vlattribs'>
<section id='ship' data-loadoutid='".(int)$loadoutid."'>
<h1>
<img src='//image.eveonline.com/Render/".$fit['ship']['typeid']."_256.png' alt='' />
<small class='groupname'>".htmlspecialchars($groupname)."</small>
<strong>".htmlspecialchars($fit['ship']['typename'])."</strong>
<small class='dbver'>".htmlspecialchars(\Osmium\Fit\get_closest_version_by_build($fit['metadata']['evebuildnumber'])['name'])."</small>
</h1>\n";

if($loadoutid === false) {
	$votesclass = ' dummy';
	$totalupvotes = 0;
	$totalvotes = 0;
	$totaldownvotes = 0;
	$votetype = null;
} else {
	$votesclass = '';
}

echo "<div class='votes{$votesclass}' data-targettype='loadout'>\n";
echo "<a title='This loadout is creative, useful, and fills the role it was designed for' class='upvote"
.($votetype == \Osmium\Reputation\VOTE_TYPE_UP ? ' voted' : '')
."'><img src='".RELATIVE."/static-".\Osmium\STATICVER
."/icons/vote.svg' alt='upvote' /></a>\n";
echo "<strong title='".$totalupvotes." upvote(s), "
.$totaldownvotes." downvote(s)'>".$totalvotes."</strong>\n";
echo "<a title='This loadout suffers from severe flaws, is badly formatted, or shows no research effort'"
." class='downvote".($votetype == \Osmium\Reputation\VOTE_TYPE_DOWN ? ' voted' : '')
."'><img src='".RELATIVE."/static-".\Osmium\STATICVER
."/icons/vote.svg' alt='downvote' /></a>\n";
echo "</div>\n";

echo "</section>\n";

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
<li><a href='#comments'>Comments (".$commentcount.")</a></li>
<li><a href='#meta'>Meta</a></li>
<li><a href='#export'>Export</a></li>
<li class='external'><a href='".$historyuri."' title='View the different revisions of the loadout and compare the changes that were made'>History (".($revision - 1).")</a></li>
<li class='external'><a rel='nofollow' href='".$forkuri."' title='Make a private copy of this loadout and edit it immediately'>Fork</a></li>
</ul>\n";



echo "<section id='loadout'>
<section id='modules'>\n";
$stypes = \Osmium\Fit\get_slottypes();
$slotusage = \Osmium\AjaxCommon\get_slot_usage($fit);
$states = \Osmium\Fit\get_state_names();
$ia_ = \Osmium\AjaxCommon\get_modules_interesting_attributes($fit);
$ia = array();
foreach($ia_ as $k) { $ia[$k[0]][$k[1]] = $k; }
$fittedtotal = 0;
foreach($stypes as $type => $tdata) {
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
	echo htmlspecialchars($tdata[0])." <span>$groupedcharges";
	$u = count($sub);
	$overflow = $u > $slotusage[$type] ? ' overflow' : '';
	echo "<small class='counts$overflow'>".$u." / ".$slotusage[$type]."</small></span>";
	echo "</h3>\n<ul>\n";

	foreach($sub as $index => $m) {
		$s = $states[$m['state']];
		if(isset($fit['charges'][$type][$index])) {
			$c = $fit['charges'][$type][$index];
		} else $c = null;

		$class = '';
		if(isset($ia[$type][$index])) {
			$class = ' class="hasattribs"';
		}

		echo "<li{$class} data-typeid='".$m['typeid']."' data-slottype='"
			.$type."' data-index='".$index."' data-state='".$s[2]."' data-chargetypeid='"
			.($c === null ? 'null' : $c['typeid'])."'>\n";
		echo "<img src='//image.eveonline.com/Type/".$m['typeid']."_64.png' alt='' />";
		echo htmlspecialchars($m['typename'])."\n";

		if($c !== null) {
			dogma_get_number_of_module_cycles_before_reload(
				$fit['__dogma_context'], $m['dogma_index'], $ncycles
			);

			echo "<span class='charge".($ncycles !== -1 ? ' hasncycles' : '')."'>,<br />";
			echo "<img src='//image.eveonline.com/Type/".$c['typeid']."_64.png' alt='' />";
			echo "<span class='name'>".htmlspecialchars($c['typename'])."</span>";
			if($ncycles !== -1) {
				echo "<span class='ncycles' title='Number of module cycles before having to reload'>"
					.$ncycles."</span>";
			}
			echo "</span>\n";
		}

		if($tdata[2]) {
			echo "<a class='toggle_state' href='javascript:void(0);' title='".$s[0]."'>"
				.\Osmium\Chrome\sprite(RELATIVE, $s[2], $s[1][0], $s[1][1], $s[1][2], $s[1][3], 16)
				."</a>\n";
		}

		if(isset($ia[$type][$index])) {
			echo "<small class='attribs' title='".htmlspecialchars(
				$ia[$type][$index][3], ENT_QUOTES
			)."'>".htmlspecialchars($ia[$type][$index][2])."</small>\n";
		}

		echo "</li>\n";
		++$fittedtotal;
	}

	for($i = count($sub); $i < $slotusage[$type]; ++$i) {
		echo "<li class='placeholder'>"
			.\Osmium\Chrome\sprite(RELATIVE, '', $tdata[1][0], $tdata[1][1], $tdata[1][2], $tdata[1][3], 32)
			."Unused ".$type." slot</li>\n";
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
$dbw = \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth');
foreach(array('space' => 'Drones in space', 'bay' => 'Drones in bay') as $k => $v) {
	if($dbw == 0 && $dronesin['space'] === 0 && $dronesin['bay'] === 0) continue;

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

	if($dronesin[$k] === 0) {
		echo "<li class='placeholder'>"
			.\Osmium\Chrome\sprite(RELATIVE, '', 0, 13, 64, 64, 32)
			."No drones in ".$k."</li>\n";
	}

	echo "</ul>\n</div>\n";
}

echo "</section>
<section id='implants'>\n";

$implants = array();
$boosters = array();

foreach($fit['implants'] as $i) {
	if(\Osmium\Fit\get_groupid($i['typeid']) == \Osmium\Fit\GROUP_Booster) {
		$boosters[] = $i;
	} else {
		$implants[] = $i;
	}
}

$slotcmp = function($x, $y) { return $x['slot'] - $y['slot']; };
usort($implants, $slotcmp);
usort($boosters, $slotcmp);

foreach(array('implants' => $implants, 'boosters' => $boosters) as $k => $imps) {
	if($imps === array()) continue;

	echo "<div class='".$k."'>\n<h3>".ucfirst($k)."</h3>\n<ul>\n";

	foreach($imps as $i) {
		echo "<li><img src='//image.eveonline.com/Type/".$i['typeid']."_64.png' alt='' />"
			.htmlspecialchars($i['typename'])
			.'<span class="slot">, '.substr($k, 0, -1).' slot '.$i['slot'].'</span>'
			."</li>\n";
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

/* Prints paginated comments and the "add comment" form. */
require __DIR__.'/../inc/view_loadout-commentview.php';



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

/* The phony CLF token is obviously not a valid token, and process_clf
 * will pick it up and create a new token on demand. So if the user
 * never interacts with the loadout, it never gets cached. */
\Osmium\Chrome\print_loadout_common_footer($fit, RELATIVE, '___demand___');

\Osmium\Chrome\print_js_snippet('view_loadout');
\Osmium\Chrome\print_js_snippet('view_loadout-presets');
\Osmium\Chrome\print_js_snippet('new_loadout-ship');
\Osmium\Chrome\print_js_snippet('new_loadout-modules');
\Osmium\Chrome\print_js_snippet('new_loadout-drones');
\Osmium\Chrome\print_js_snippet('new_loadout-implants');
\Osmium\Chrome\print_footer();
