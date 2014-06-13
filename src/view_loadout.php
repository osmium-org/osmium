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

require __DIR__.'/../inc/root.php';
require __DIR__.'/../inc/ajax-common.php';
require __DIR__.'/../inc/loadout-nv-common.php';

/* XXX: there is probably a way to slap a Last-Modified: header on
 * this to save some server resources */

$p = new \Osmium\LoadoutCommon\Page();
$ctx = new \Osmium\DOM\RenderContext();



/* Check permissions, password, private tokens. Populates the $fit,
 * $revision, $lodaoutid, $forkuri, $historyuri, $exporturi and
 * $revision_overridden variables. Fills $ctx->relative. */
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



$ss = \Osmium\Fit\use_default_skillset_for_account($fit, $a);
if($ss !== 'All V') {
	list(, $missing) = \Osmium\Fit\get_skill_prerequisites_and_missing_prerequisites($fit);
} else {
	$missing = [];
}
$p->data['missingprereqs'] = $missing;



$ismoderator = $loggedin && isset($a['ismoderator']) && ($a['ismoderator'] === 't');
$modprefix = $ismoderator ? [ 'span', [ 'title' => 'Moderator action', \Osmium\Flag\MODERATOR_SYMBOL ] ] : '';



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
		$pid = intval($_GET[$ptype.'preset']);
		if(!isset($fit[$ptype.'presets'][$pid])) {
			\Osmium\Fatal(404, "Invalid ".$ptype." preset");
		}
		call_user_func_array(
			'Osmium\Fit\use_'.$ptype.($ptype ? '_' : '').'preset',
			array(&$fit, $pid)
		);
		$preset_overridden = true;
	}
}



$capacitors = \Osmium\Fit\get_all_capacitors($fit);
$ia_ = \Osmium\Fit\get_interesting_attributes($fit);



$p->title = $fit['metadata']['name'];

if(count($fit['metadata']['tags']) > 0) {
	$p->title .= ' ('.implode(', ', $fit['metadata']['tags']).')';
}

if(isset($fit['ship']['typename'])) {
	$p->title .= ' / '.$fit['ship']['typename'].' fitting';
	if($loadoutid > 0) {
		$p->title .= ' #'.$loadoutid;

		if($revision_overridden) {
			$p->title .= ' (revision '.$revision.')';
		}
	}
}

$p->index = $fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PUBLIC
	&& !$revision_overridden && !$preset_overridden;
$p->canonical = $canonicaluri;

$dna = \Osmium\Fit\export_to_dna($fit);

$h1 = $p->content->appendCreate('h1#vltitle', 'Viewing loadout: ');
$h1->appendCreate('strong.fitname')->appendCreate(
	'a', [ 'data-ccpdna' => $dna, $fit['metadata']['name'] ]
);

$canretag = ($revision_overridden === false)
	&& isset($a['accountid']) && isset($author['accountid'])
	&& ($a['accountid'] == $author['accountid'] || (
		\Osmium\Reputation\is_fit_public($fit) && \Osmium\Reputation\has_privilege(
			\Osmium\Reputation\PRIVILEGE_RETAG_LOADOUTS
		)
	));

if(count($fit['metadata']['tags']) > 0 || $canretag) {
	$ul = $h1->appendCreate('ul.tags');

	foreach($fit['metadata']['tags'] as $tag) {
		$ul->appendCreate('li')->appendCreate('a', [
			'o-rel-href' => '/search'.$p->formatQueryString([ 'q' => '@tags "'.$tag.'"' ]),
			$tag
		]);
	}

	if($canretag) {
		$ul->appendCreate('li.retag')->appendCreate('a')->appendCreate('small', '✎ Edit tags');
	}
}



if(isset($fit['ship']['typeid'])) {
	$groupname = \Osmium\Fit\get_groupname(\Osmium\Fit\get_groupid($fit['ship']['typeid']));
} else {
	$groupname = '';
}


list($commentcount) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
	'SELECT COUNT(commentid) FROM osmium.loadoutcomments
	WHERE loadoutid = $1 AND revision <= $2',
	array(
		(int)$loadoutid,
		$revision,
	)
));
$commentcount = (int)$commentcount;

$div = $p->content->appendCreate('div#vlattribs');
$section = $div->appendCreate('section#ship', [ 'data-loadoutid' => (int)$loadoutid ]);
$h1 = $section->appendCreate('h1');

if(isset($fit['ship']['typeid'])) {
	$h1->appendCreate('o-eve-img', [
		'src' => '/Render/'.$fit['ship']['typeid'].'_256.png',
		'alt' => '',
	]);

	$h1->appendCreate('small.groupname', $groupname);

	$span = $h1->appendCreate('strong')->appendCreate('span.name', $fit['ship']['typename']);

	if(isset($missing[$fit['ship']['typeid']])) {
		$span->addClass('missingskill');
	}
} else {
	$h1->appendCreate('div.notype');
	$h1->appendCreate('small.groupname');
	$h1->appendCreate('strong', 'N/A');
}

$intendeddbver = \Osmium\Fit\get_closest_version_by_build($fit['metadata']['evebuildnumber']);
$h1->appendCreate('small.dbver', $intendeddbver['name']);

if($loadoutid === false) {
	$votesclass = ' dummy';
	$totalupvotes = 0;
	$totalvotes = 0;
	$totaldownvotes = 0;
	$votetype = null;
} else {
	$votesclass = '';
}

$votesdiv = $section->appendCreate('div.votes'.$votesclass, [ 'data-targettype' => 'loadout' ]);

$anch = $votesdiv->appendCreate('a.upvote', [
	'title' => 'This loadout is creative, useful, and fills the role it was designed for',
]);
$anch->appendCreate('img', [
	'o-static-src' => '/icons/vote.svg',
	'alt' => 'upvote',
]);
if($votetype == \Osmium\Reputation\VOTE_TYPE_UP) $anch->addClass('voted');

$votesdiv->appendCreate('strong', [
	'title' => $p->formatExactInteger($totalupvotes).' upvote(s), '.$p->formatExactInteger($totaldownvotes).' downvote(s)',
	$p->formatExactInteger($totalvotes),
]);

$anch = $votesdiv->appendCreate('a.downvote', [
	'title' => 'This loadout suffers from sever flaws, is badly formatted, or shows no research effort',
]);
$anch->appendCreate('img', [
	'o-static-src' => '/icons/vote.svg',
	'alt' => 'downvote',
]);
if($votetype == \Osmium\Reputation\VOTE_TYPE_DOWN) $anch->addClass('voted');



if($loadoutid !== false) {
	$section = $div->appendCreate('section#credits.'.($fit['metadata']['revision'] > 1 ? 'edited' : 'notedited'));

	$authordiv = $section->appendCreate('div.author');
	if($author['apiverified'] === 't') {
		if($author['allianceid'] > 0) {
			$authordiv->appendCreate('o-eve-img.alliance', [
				'src' => '/Alliance/'.$author['allianceid'].'_128.png',
				'alt' => '',
				'title' => 'member of '.$author['alliancename'],
			]);
		} else {
			$authordiv->appendCreate('o-eve-img.corporation', [
				'src' => '/Corporation/'.$author['corporationid'].'_256.png',
				'alt' => '',
				'title' => 'member of '.$author['corporationname'],
			]);
		}

		if($author['characterid'] > 0) {
			$authordiv->appendCreate('o-eve-img.portrait', [
				'src' => '/Character/'.$author['characterid'].'_256.jpg',
				'alt' => '',
			]);
		}
	}

	$authordiv->appendCreate('small', 'submitted by');
	$authordiv->appendCreate('br');
	$authordiv->append($p->makeAccountLink($author, $rauthorname))
		->appendCreate('br');
	$authordiv->append($p->formatReputation($author['reputation']))
		->append(' – ')
		->append($p->formatRelativeDate($truecreationdate));

	if($fit['metadata']['revision'] > 1) {
		$editdiv = $section->appendCreate('div.author.edit');
		$editdiv->appendCreate('small', 'revision #'.$fit['metadata']['revision'].' edited by');
		$editdiv->appendCreate('br');
		$editdiv->append($p->makeAccountLink($lastrev))
			->appendCreate('br');
		$editdiv->append($p->formatReputation($lastrev['reputation']))
			->append(' – ')
			->append($p->formatRelativeDate($lastrev['updatedate']));
	}
}



$section = $div->appendCreate('section#attributes');
$attribsdiv = $section->appendCreate('div.compact#computed_attributes');
$p->makeFormattedAttributes($attribsdiv, $fit, [
	'cap' => $capacitors['local'],
	'ia' => $ia_,
]);





$div = $p->content->appendCreate('div#vlmain');

if($revision_overridden && isset($lastrev['updatedate'])) {
	$div->appendCreate(
		'p.notice_box',
		'You are viewing revision '.$revision.' of this loadout, as it was published the '
		.date('Y-m-d \a\t H:i', $lastrev['updatedate']).'. '
	)->appendCreate('a', [
		'o-rel-href' => '/'.\Osmium\Fit\get_fit_uri(
			$loadoutid,
			$fit['metadata']['visibility'],
			$fit['metadata']['privatetoken']
		),
		'Link to the latest revision.'
	]);
}

$latestdbver = \Osmium\Fit\get_latest_eve_db_version();
if($latestdbver['dogmaver'] - $intendeddbver['dogmaver'] > 1) {
	$dogmawarn = $div->appendCreate('p.notice_box');

	if($can_edit && !$revision_overridden) {
		$dogmawarn->appendCreate(
			'strong',
			'Please update this loadout for '.$latestdbver['name'].'.'
		);
	} else {
		$dogmawarn->append(
			'This loadout was made for an older EVE version ('
			.$intendeddbver['name'].') and may no longer be applicable to the current EVE version ('
			.$latestdbver['name'].').'
		);
	}
}

$ul = $div->appendCreate('ul.tabs');
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#loadout', 'Loadout' ]);
$ul->appendCreate('li')->appendCreate('a', [
	'href' => '#presets',
	'Presets ('.max(
		count($fit['presets']),
		count($fit['chargepresets']),
		count($fit['dronepresets'])
	).')',
]);
$ul->appendCreate('li')->appendCreate('a', [
	'href' => '#remote',
	'Fleet ('.(isset($fit['fleet']) ? count($fit['fleet']) : 0)
	.') & Projected ('.(isset($fit['remote']) ? count($fit['remote']) : 0).')',
]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#comments', 'Comments ('.$commentcount.')' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#meta', 'Meta' ]);

if($maxrev !== false && $historyuri !== false) {
	$ul->prepend($p->element('li.external')->append([[ 'a', [
		'o-rel-href' => $historyuri,
		'title' => 'View different revisions of this loadout, and compare changes',
		'History ('.($maxrev - 1).')',
	]]]));
}

$ul->prepend($p->element('li.external')->append([[ 'a', [
	'rel' => 'nofollow',
	'o-rel-href' => $forkuri,
	'title' => 'Make a copy of this loadout and start editing it',
	'Fork',
]]]));

if($can_edit) {
	$editparams = [
		'tok' => \Osmium\State\get_token(),
		'revision' => $fit['metadata']['revision'],
	];

	if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
		$editparams['privatetoken'] = $fit['metadata']['privatetoken'];
	}

	$ul->prepend($p->element('li.external')->append([[ 'a', [
		'rel' => 'nofollow',
		'o-rel-href' => '/edit/'.$loadoutid.$p->formatQueryString($editparams),
		'Edit',
	]]]));
}



$section = $div->appendCreate('section#loadout');

$msection = $section->appendCreate('section#modules');
$stypes = \Osmium\Fit\get_slottypes();
$slotusage = \Osmium\AjaxCommon\get_slot_usage($fit);
$states = \Osmium\Fit\get_state_names();
$ia = array();

foreach($ia_ as $k) {
	if($k['location'][0] === 'module') {
		$ia['module'][$k['location'][1]][$k['location'][2]] = $k;
	} else if($k['location'][0] === 'drone') {
		$ia['drone'][$k['location'][1]] = $k;
	}
}

$fittedtotal = 0;
foreach($stypes as $type => $tdata) {
	if($slotusage[$type] == 0 && (
		!isset($fit['modules'][$type]) || count($fit['modules'][$type]) === 0
	)) continue;

	$sub = isset($fit['modules'][$type]) ? $fit['modules'][$type] : array();

	$sdiv = $msection->appendCreate('div.slots.'.$type);
	$span = $sdiv->appendCreate('h3', $tdata[0])->appendCreate('span');

	if($type === "high" || $type === "medium") {
		$sdiv->addClass('grouped');
		$span->appendCreate('small.groupcharges', [
			'title' => 'Charges are grouped',
		]);
	} else {
		$sdiv->addClass('ungrouped');
	}

	$u = count($sub);
	$small = $span->appendCreate('small.counts', $u.' / '.$slotusage[$type]);
	if($u > $slotusage[$type]) $small->addClass('overflow');

	$ul = $sdiv->appendCreate('ul');

	$fittedtype = 0;
	foreach($sub as $index => $m) {
		$s = $states[$m['state']];
		if(isset($fit['charges'][$type][$index])) {
			$c = $fit['charges'][$type][$index];
		} else $c = null;

		$li = $ul->appendCreate('li', [
			'data-typeid' => $m['typeid'],
			'data-slottype' => $type,
			'data-index' => $index,
			'data-state' => $s[2],
			'data-chargetypeid' => $c === null ? 'null' : $c['typeid'],
		]);

		if(isset($ia['module'][$type][$index])) {
			$li->addClass('hasattribs');
		}
		if($fittedtype >= $slotusage[$type]) {
			$li->addClass('overflow');
		}

		$li->appendCreate('o-eve-img', [
			'src' => '/Type/'.$m['typeid'].'_64.png',
			'alt' => '',
		]);

		$span = $li->appendCreate('span.name', $m['typename']);
		if(isset($missing[$m['typeid']])) $span->addClass('missingskill');

		if($c !== null) {
			$span = $li->appendCreate('span.charge');

			dogma_get_number_of_module_cycles_before_reload(
				$fit['__dogma_context'], $m['dogma_index'], $ncycles
			);
			if($ncycles !== -1) $span->addClass('hasncycles');

			$span->append([ ',', [ 'br' ] ]);
			$span->appendCreate('o-eve-img', [
				'src' => '/Type/'.$c['typeid'].'_64.png',
				'alt' => '',
			]);

			$nspan = $span->appendCreate('span.name', $c['typename']);
			if(isset($missing[$c['typeid']])) $nspan->addClass('missingskill');

			if($ncycles !== -1) {
				$span->appendCreate('span.ncycles', [
					'title' => 'Number of module cycles per reload',
					$p->formatExactInteger($ncycles),
				]);
			}
		}

		if($tdata[2]) {
			$li->appendCreate('a.toggle_state', [
				'title' => $s[0],
			])->appendCreate('o-sprite', [
				'alt' => $s[2],
				'x' => $s[1][0], 'y' => $s[1][1],
				'gridwidth' => $s[1][2],
				'gridheight' => $s[1][3],
				'width' => 16,
				'height' => 16,
			]);
		}

		if(isset($ia['module'][$type][$index]) && isset($ia['module'][$type][$index]['fshort'])) {
			$small = $li->appendCreate('small.attribs', $ia['module'][$type][$index]['fshort']);

			if(isset($ia['module'][$type][$index]['flong'])) {
				$small->setAttribute('title', $ia['module'][$type][$index]['flong']);
			}
		}

		++$fittedtotal;
		++$fittedtype;
	}

	for($i = count($sub); $i < $slotusage[$type]; ++$i) {
		$li = $ul->appendCreate('li.placeholder');
		$li->appendCreate('o-sprite', [
			'x' => $tdata[1][0], 'y' => $tdata[1][1],
			'gridwidth' => $tdata[1][2],
			'gridheight' => $tdata[1][3],
			'width' => '32', 'height' => '32',
			'alt' => '',
		]);
		$li->append('Unused '.$type.' slot');

		++$fittedtotal;
	}
}
if($fittedtotal === 0) {
	$msection->appendCreate('p.placeholder', 'No available slots.');
}



$dsection = $section->appendCreate('section#drones');

$dronesin['space'] = 0;
$dronesin['bay'] = 0;
foreach($fit['drones'] as $typeid => $d) {
	if(isset($d['quantityinspace'])) { $dronesin['space'] += $d['quantityinspace']; }
	if(isset($d['quantityinbay'])) { $dronesin['bay'] += $d['quantityinbay']; }
}
$dbw = \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth');

foreach(array('space' => 'Drones in space', 'bay' => 'Drones in bay') as $k => $v) {
	if($dbw == 0 && $dronesin['space'] === 0 && $dronesin['bay'] === 0) continue;

	$ddiv = $dsection->appendCreate('div.drones.'.$k);
	$span = $ddiv->appendCreate('h3', $v)->appendCreate('span');

	if($k === 'space') {
		$dbw = \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth');
		$dbwu = \Osmium\Fit\get_used_drone_bandwidth($fit);
		$mad = \Osmium\Dogma\get_char_attribute($fit, 'maxActiveDrones');
		$ad = $dronesin[$k];

		$small = $span->appendCreate('small.maxdrones', [
			'title' => 'Maximum number of drones in space',
			$ad.' / '.$mad,
		]);

		if($ad > $mad) $small->addClass('overflow');

		$span->appendCreate('small', ' — ');

		$small = $span->appendCreate('small.bandwidth', [
			'title' => 'Drone bandwidth usage',
			$dbwu.' / '.$dbw.' Mbps',
		]);
	} else if($k === 'bay') {
		$dcap = \Osmium\Dogma\get_ship_attribute($fit, 'droneCapacity');
		$dcapu = \Osmium\Fit\get_used_drone_capacity($fit);

		$small = $span->appendCreate('small.bayusage', [
			'title' => 'Drone bay usage',
			$dcapu.' / '.$dcap.' m³'
		]);

		if($dcapu > $dcap) $small->addClass('overflow');
	}

	$ul = $ddiv->appendCreate('ul');

	foreach($fit['drones'] as $typeid => $d) {
		if(!isset($d['quantityin'.$k])) continue;
		$qty = (int)$d['quantityin'.$k];
		if($qty === 0) continue;

		$li = $ul->appendCreate('li', [
			'data-typeid' => $typeid,
			'data-location' => $k,
			'data-quantity' => $qty,
		]);

		$li->appendCreate('o-eve-img', [
			'src' => '/Type/'.$typeid.'_64.png',
			'alt' => '',
		]);

		$li->appendCreate('strong.qty', $qty.'×');
		$span = $li->appendCreate('span.name', $d['typename']);

		if(isset($missing[$typeid])) $span->addClass('missingskill');

		if($k === 'space' && isset($ia['drone'][$typeid]['fshort'])) {
			$li->addClass('hasattribs');
			$small = $li->appendCreate('small.attribs', $ia['drone'][$typeid]['fshort']);

			if(isset($ia['drone'][$typeid]['flong'])) {
				$small->setAttribute('title', $ia['drone'][$typeid]['flong']);
			}
		}
	}

	if($dronesin[$k] === 0) {
		$li = $ul->appendCreate('li.placeholder');
		$li->appendCreate('o-sprite', [
			'alt' => '',
			'x' => 0, 'y' => 13,
			'gridwidth' => 64,
			'gridheight' => 64,
			'width' => 32, 'height' => 32,
		]);
		$li->append('No drones in '.$k);
	}
}



$isection = $section->appendCreate('section#implants');
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

	$idiv = $isection->appendCreate('div.'.$k);
	$idiv->appendCreate('h3', ucfirst($k));

	$ul = $idiv->appendCreate('ul');

	foreach($imps as $i) {
		$li = $ul->appendCreate('li');

		$li->appendCreate('o-eve-img', [
			'src' => '/Type/'.$i['typeid'].'_64.png',
			'alt' => '',
		]);

		$span = $li->appendCreate('span.name', $i['typename']);
		if(isset($missing[$i['typeid']])) $span->addClass('missingskill');

		$li->appendCreate('span.slot', substr($k, 0, -1).' slot '.$i['slot']);
	}
}

$dsection = $section->appendCreate('section#description');
$dsection->appendCreate('h3', 'Fitting description');

$desc = $fit['metadata']['fdescription'];
if($desc === '') {
	$dsection->appendCreate('p.placeholder', 'No description given.');
} else {
	$dsection->appendCreate('div')->append($p->fragment($desc));
}



$section = $div->appendCreate('section#presets');
$tbody = $section->appendCreate('o-form', [ 'method' => 'post', 'action' => $_SERVER['REQUEST_URI'] ])
	->appendCreate('table')->appendCreate('tbody');
$first = true;

foreach([ ['presets', 'modulepreset', 'Preset', 'spreset'],
          ['chargepresets', 'chargepreset', 'Charge preset', 'scpreset'],
          ['dronepresets', 'dronepreset', 'Drone preset', 'sdpreset'] ] as $ptype) {
	list($parraykey, $pkey, $fname, $name) = $ptype;

	if($first) {
		$first = false;
	} else {
		$tbody->append($p->makeFormSeparatorRow());
	}

	$select = $p->element('o-select', [
		'name' => $name,
		'id' => $name,
		'selected' => $fit[$pkey.'id'],
	]);
	foreach($fit[$parraykey] as $id => $preset) {
		$select->appendCreate('option', [ 'value' => $id, $preset['name'] ]);
	}

	$tbody->append($p->makeFormRawRow(
		[[ 'label', [ 'for' => $name, $fname ] ]],
		$select
	));

	$desc = \Osmium\Chrome\trim($fit[$pkey.'desc']);
	if(empty($desc)) {
		$desc = $p->element('p.placeholder', 'Empty description.');
	} else {
		$desc = $p->fragment(\Osmium\Chrome\format_sanitize_md($desc)); /* XXX */
	}

	$tbody->append($p->makeFormRawRow(
		[[ 'label', 'Description' ]],
		[[ 'div.pdesc', $desc ]]
	));
}


$div->append($p->makeRemoteSection($fit, true));



/* Prints paginated comments and the "add comment" form. */
require __DIR__.'/../inc/view_loadout-commentview.php';

/* Pretty prints permissions, show actions, moderator actions, export
 * and share links. */
require __DIR__.'/../inc/view_loadout-meta.php';





foreach($capacitors as &$c) {
	if(!isset($c['depletion_time'])) continue;
	$c['depletion_time'] = \Osmium\Chrome\format_duration($c['depletion_time'] / 1000);
}

$p->data['capacitors'] = $capacitors;
$p->data['ia'] = $ia_;
$p->data['clfslots'] = \Osmium\AjaxCommon\get_slot_usage($fit);

$p->snippets[] = 'view_loadout';

/* The phony CLF token is obviously not a valid token, and process_clf
 * will pick it up and create a new token on demand. So if the user
 * never interacts with the loadout, it never gets cached. */
$p->finalizeWithFit($ctx, $fit, '___demand___');
$p->render($ctx);
