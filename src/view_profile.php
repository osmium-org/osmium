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

namespace Osmium\Page\ViewProfile;

require __DIR__.'/../inc/root.php';

if(!isset($_GET['accountid'])) {
	\Osmium\fatal(404);
}

$row = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
	'SELECT accountid, creationdate, lastlogindate, apiverified,
	nickname, characterid, charactername, corporationid, corporationname,
	allianceid, alliancename, ismoderator, flagweight, reputation
	FROM osmium.accounts WHERE accountid = $1',
	array($_GET['accountid'])
));

if($row === false) {
	\Osmium\fatal(404);
}

$a = \Osmium\State\get_state('a', array());
$myprofile = \Osmium\State\is_logged_in() && $a['accountid'] == $_GET['accountid'];
$ismoderator = isset($a['ismoderator']) && $a['ismoderator'] === 't';

$p = new \Osmium\DOM\Page();
$nameelement = $p->makeAccountLink($row, $name);
$p->title = $name.'\'s profile';

$content = $p->content->appendCreate('div', [ 'id' => 'vprofile' ]);
$header = $content->appendCreate('header');

$header->appendCreate('h2', [
	$nameelement,
	[ 'small', [
		$myprofile ? ' (this is you!) ' : '',
		$row['ismoderator'] === 't' ? ' '.\Osmium\Flag\MODERATOR_SYMBOL.'Moderator ' : '',
	]]
]);


if($row['apiverified'] === 't') {
	$allianceid = (($row['allianceid'] == null) ? 1 : $row['allianceid']);
	$alliancename = ($allianceid === 1) ? '(no alliance)' : $row['alliancename'];

	$pp = $header->appendCreate('p');

	$pp->append([
		[ 'a', [
			'o-rel-href' => '/search?q=@restrictedtoaccountid > 0',
			[ 'o-eve-img', [ 'src' => '/Character/'.$row['characterid'].'_512.jpg', 'alt' => 'portrait' ] ],
		]],
		[ 'br' ],
		[ 'a', [
			'o-rel-href' => '/search?q=@restrictedtocorporationid > 0',
			[ 'o-eve-img', [ 'src' => '/Corporation/'.$row['corporationid'].'_256.png',
			                 'alt' => 'corporation logo', 'title' => $row['corporationname'] ] ],
		]],
		[ 'a', [
			'o-rel-href' => '/search?q=@restrictedtoallianceid > 0',
			[ 'o-eve-img', [ 'src' => '/Alliance/'.$allianceid.'_128.png',
			                 'alt' => 'alliance logo', 'title' => $alliancename ] ],
		]],
	]);
}

$tbody = $header->appendCreate('table')->appendCreate('tbody');
$sep = $p->element('tr', [
	'class' => 'sep',
	[ 'td', [ 'colspan' => '3', ' ' ] ],
]);

if($row['apiverified'] === 't') {
	$tbody->appendCreate('tr', [
		[ 'th', [ 'rowspan' => '2', 'character' ] ],
		[ 'td', 'corporation' ],
		[ 'td', $row['corporationname'] ],
	]);
	$tbody->appendCreate('tr', [
		[ 'td', 'alliance' ],
		[ 'td', $alliancename ],
	]);
	$tbody->append($sep->cloneNode(true));
}

$tbody->appendCreate('tr', [
	[ 'th', [ 'rowspan' => '2', 'visits' ] ],
	[ 'td', 'member for' ],
	[ 'td', $p->formatRelativeDate($row['creationdate'], -1) ],
]);
$tbody->appendCreate('tr', [
	[ 'td', 'last seen' ],
	[ 'td', $p->formatRelativeDate($row['lastlogindate'], -1) ],
]);
$tbody->append($sep->cloneNode(true));

$tbody->appendCreate('tr', [
	[ 'th', [ 'rowspan' => '2', 'meta' ] ],
	[ 'td', 'api key verified' ],
	[ 'td', $row['apiverified'] === 't' ? 'yes' : 'no' ],
]);
$tbody->appendCreate('tr', [
	[ 'td', 'reputation points' ],
	[ 'td', [
		$p->formatReputation($row['reputation']),
		' ',
		$myprofile ? [ 'a', [ 'o-rel-href' => '/privileges#privlist', '(check my privileges)' ] ] : '',
	]],
]);

if($myprofile || $ismoderator) {
	$tbody->append($sep->cloneNode(true));
	$tbody->appendCreate('tr', [
		[ 'th', [ 'private' ] ],
		[ 'td', 'flag weight' ],
		[ 'td', [
			$p->formatExactInteger($row['flagweight']),
			' ',
			[ 'a', [ 'o-rel-href' => '/flagginghistory/'.$row['accountid'], '(see flagging history)' ] ],
		]],
	]);
}



$content->appendCreate('ul', [
	'class' => 'tabs',
	$myprofile ? [ 'li', [[ 'a', [ 'href' => '#pfavorites', 'Favorites' ] ]] ] : '',
	$myprofile ? [ 'li', [[ 'a', [ 'href' => '#phidden', 'Hidden' ] ]] ] : '',
	[ 'li', [[ 'a', [ 'href' => '#ploadouts', 'Recent' ] ]] ],
	[ 'li', [[ 'a', [ 'href' => '#reputation', 'Reputation' ] ]] ],
	[ 'li', [[ 'a', [ 'href' => '#votes', 'Votes' ] ]] ],
]);



$ploadouts = $content->appendCreate('section', [ 'id' => 'ploadouts', 'class' => 'psection' ]);
$ploadouts->appendCreate('h2', 'Loadouts recently submitted')->appendCreate('small')->appendCreate('a', [
	'o-rel-href' => '/search'.$p->formatQueryString([ 'q' => '@author "'.$name.'"' ]),
	'(browse all)'
]);
ob_start();
\Osmium\Search\print_pretty_results("..", '@author "'.$name.'"', 'ORDER BY creationdate DESC', false, 20, 'p', \Osmium\Chrome\escape($name).' does not have submitted any loadouts.');
$ploadouts->append($p->fragment(ob_get_clean())); /* XXX */



if($myprofile) {
	$pfavs = $content->appendCreate('section', [ 'id' => 'pfavorites', 'class' => 'psection' ]);
	$pfavs->appendCreate('h2', 'My favorite loadouts');

	/* TODO pagination */
	$favorites = array();
	$stale = array();
	$favq = \Osmium\Db\query_params(
		'SELECT af.loadoutid, al.loadoutid FROM osmium.accountfavorites af
		LEFT JOIN osmium.allowedloadoutsbyaccount al ON al.loadoutid = af.loadoutid AND al.accountid = $1
		WHERE af.accountid = $1
		ORDER BY af.favoritedate DESC',
		array($a['accountid'])
	);
	while($r = \Osmium\Db\fetch_row($favq)) {
		if($r[0] === $r[1]) {
			$favorites[] = $r[0];
		} else {
			$stale[] = $r[0];
		}
	}

	if($stale !== []) {
		$pfavs->appendCreate(
			'p',
			'These following loadouts you added as favorites are no longer accessible to you:'
		);

		$ol = $pfavs->appendCreate('ol');
		$qs = $p->formatQueryString([ 'tok' => \Osmium\State\get_token(), 'redirect' => 'profile' ]);

		foreach($stale as $id) {
			$ol->appendCreate('li', [
				'Loadout ',
				[ 'a', [ 'o-rel-href' => '/loadout/'.$id, '#'.$id ] ],
				' — ',
				[ 'a', [ 'o-rel-href' => '/favorite/'.$id.$qs, 'unfavorite' ] ]
			]);
		}
	}

	ob_start();
	\Osmium\Search\print_loadout_list($favorites, '..', 0, 'You have no favorited loadouts.');
	$pfavs->append($p->fragment(ob_get_clean())); /* XXX */


	/* TODO pagination */
	$phidden = $content->appendCreate('section', [ 'id' => 'phidden', 'class' => 'psection' ]);
	$phidden->appendCreate('h2', 'My hidden loadouts');

	$hidden = array();
	$hiddenq = \Osmium\Db\query_params(
		'SELECT loadoutid
		FROM osmium.loadouts
		WHERE accountid = $1 AND visibility = $2
		ORDER BY loadoutid DESC',
		array(
			$a['accountid'],
			\Osmium\Fit\VISIBILITY_PRIVATE
		)
	);
	while($r = \Osmium\Db\fetch_row($hiddenq)) {
		$hidden[] = $r[0];
	}
	ob_start();
	\Osmium\Search\print_loadout_list($hidden, '..', 0, 'You have no hidden loadouts.');
	$phidden->append($p->fragment(ob_get_clean())); /* XXX */
}


$preputation = $content->appendCreate('section', [ 'id' => 'reputation', 'class' => 'psection' ]);
$preputation->appendCreate('h2', [
	'Reputation changes this month',
	[ 'small', [ $p->formatReputation($row['reputation']), ' reputation points' ] ],
]);

$votetypes = \Osmium\Reputation\get_vote_types();
$repchangesq = \Osmium\Db\query_params(
	'SELECT v.creationdate, reputationgiventodest, type, targettype, targetid1, targetid2, targetid3,
		sl.loadoutid, f.name
	FROM osmium.votes AS v
	LEFT JOIN osmium.searchableloadouts AS sl ON ((v.targettype = $3 AND v.targetid1 = sl.loadoutid
		AND v.targetid2 IS NULL AND v.targetid3 IS NULL)
		OR (v.targettype = $4 AND v.targetid2 = sl.loadoutid AND v.targetid3 IS NULL))
	LEFT JOIN osmium.loadoutslatestrevision AS llr ON llr.loadoutid = sl.loadoutid
	LEFT JOIN osmium.loadouthistory AS lh ON lh.loadoutid = sl.loadoutid AND lh.revision = llr.latestrevision
	LEFT JOIN osmium.fittings AS f ON f.fittinghash = lh.fittinghash
	WHERE v.accountid = $1 AND v.creationdate >= $2 AND reputationgiventodest <> 0
	ORDER BY creationdate DESC',
	array($_GET['accountid'],
	      time() - 86400 * 365.25 / 12,
	      \Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT,
	      \Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT,
		)
	);
$lastday = null;
$first = true;
$data = array();
$ul = $preputation->appendCreate('ul');

function make_target(\Osmium\DOM\Document $p, $d) {
	if($d['targettype'] == \Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT) {
		if($d['name'] !== null) {
			return $p->element('a', [
				'o-rel-href' => '/loadout/'.$d['loadoutid'],
				$d['name'],
			]);
		} else {
			return $p->element('small', 'Private/hidden loadout');
		}
	} else if($d['targettype'] == \Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT) {
		if($d['name'] !== null) {
			return [
				'Comment ',
				$p->element('a', [
					'o-rel-href' => '/loadout/'.$d['loadoutid']
					.$p->formatQueryString([ 'jtc' => $d['targetid1'] ]).'#c'.$d['targetid1'],

					'#'.$d['targetid1'],
				]),
				' on ',
				$p->element('a', [
					'o-rel-href' => '/loadout/'.$d['loadoutid'],
					$d['name']
				]),
			];
		} else {
			return $p->element('small', 'Comment on a private/hidden loadout');
		}
	}
}

function make_reputation_day(\Osmium\DOM\Document $p, $day, $data) {
	global $votetypes;

	$net = 0;
	foreach($data as $d) $net += $d['reputationgiventodest'];

	$li = $p->createElement('li');
	$li->appendCreate('h4', [
		$day,
		[ 'span', [ 'class' => $net >= 0 ? 'positive' : 'negative', (string)$net ] ],
	]);

	$tbody = $li->appendCreate('table', [ 'class' => 'd' ])->appendCreate('tbody');

	foreach($data as $d) {
		$rep = $d['reputationgiventodest'];
		$tbody->appendCreate('tr', [
			[ 'td', $rep >= 0 ? [ 'class' => 'rep positive', '+'.$rep ]
			  : [ 'class' => 'rep negative', (string)$rep ] ],
			[ 'td', [ 'class' => 'time', date('H:i', $d['creationdate']) ] ],
			[ 'td', [ 'class' => 'type', $votetypes[$d['type']] ] ],
		])->appendCreate('td', [ 'class' => 'l' ])->append(make_target($p, $d));
	}

	return $li;
}

while($r = \Osmium\Db\fetch_assoc($repchangesq)) {
	$day = date('Y-m-d', $r['creationdate']);
	if($lastday !== $day) {
		if($first) $first = false;
		else {
			$ul->appendChild(make_reputation_day($p, $lastday, $data));
		}

		$lastday = $day;
		$data = array();
	}

	$data[] = $r;
}

if($first) {
	$preputation->appendCreate('p', [
		'class' => 'placeholder',
		'No reputation changes this month.',
	]);
} else {
	$ul->appendChild(make_reputation_day($p, $day, $data));
}



$pvotes = $content->appendCreate('section', [ 'id' => 'votes', 'class' => 'psection' ]);

list($total) = \Osmium\Db\fetch_row(
	\Osmium\Db\query_params(
		'SELECT COUNT(voteid) FROM osmium.votes WHERE fromaccountid = $1',
		array($_GET['accountid'])
		));
$offset = \Osmium\Chrome\paginate('vp', 25, $total, $result, $metaresult, null, '', '#votes');

$pvotes->appendCreate('h2', [
	'Votes cast',
	[ 'small', $p->formatExactInteger($total).' votes cast' ]
]);

if($result !== '') $pvotes->append($p->fragment($result)); /* XXX */

$votesq = \Osmium\Db\query_params(
	'SELECT v.creationdate, type, targettype, targetid1, targetid2, targetid3, sl.loadoutid, f.name
	FROM osmium.votes AS v
	LEFT JOIN osmium.searchableloadouts AS sl ON sl.accountid IN (0, $5) AND (
		((v.targettype = $2 AND v.targetid1 = sl.loadoutid
		AND v.targetid2 IS NULL AND v.targetid3 IS NULL)
		OR (v.targettype = $3 AND v.targetid2 = sl.loadoutid AND v.targetid3 IS NULL))
	)
	LEFT JOIN osmium.loadoutslatestrevision AS llr ON llr.loadoutid = sl.loadoutid
	LEFT JOIN osmium.loadouthistory AS lh ON lh.loadoutid = sl.loadoutid AND lh.revision = llr.latestrevision
	LEFT JOIN osmium.fittings AS f ON f.fittinghash = lh.fittinghash
	WHERE fromaccountid = $1 ORDER BY v.creationdate DESC LIMIT 25 OFFSET $4',
	array(
		$_GET['accountid'],
		\Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT,
		\Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT,
		$offset,
		isset($a['accountid']) ? $a['accountid'] : 0,
	)
);

$tbody = $pvotes->appendCreate('table', [ 'class' => 'd' ])->appendCreate('tbody');

$first = true;
while($v = \Osmium\Db\fetch_assoc($votesq)) {
	$first = false;
	$tbody->appendCreate('tr', [
		[ 'td', [ 'class' => 'date', $p->formatRelativeDate($v['creationdate']) ] ],
		[ 'td', [ 'class' => 'type', $votetypes[$v['type']] ] ],
	])->appendCreate('td', [ 'class' => 'l' ])->append(make_target($p, $v));
}

if($first) {
	$pvotes->appendCreate('p', [
		'class' => 'placeholder',
		'No votes cast.',
	]);
}



$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';
$p->snippets[] = 'tabs';
$p->snippets[] = 'view_profile';
$p->data['defaulttab'] = $myprofile ? 2 : 0;
$p->render($ctx);
