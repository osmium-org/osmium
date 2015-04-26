<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014, 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

$section = $div->appendCreate('section#meta');

function make_list($heading, array $elements) {
	global $section;
	$subsection = $section->appendCreate('section');

	if(count($elements) === 0) return;

	$subsection->appendCreate('h2', $heading);
	$ul = $subsection->appendCreate('ul');

	foreach($elements as $v) {
		$li = $ul->appendCreate('li');
		$lead = array_shift($v);

		if(is_array($lead)) {
			$li->appendCreate('o-sprite', [
				'alt' => '',
				'x' => $lead[0],
				'y' => $lead[1],
				'gridwidth' => $lead[2],
				'gridheight' => $lead[3],
				'width' => 16,
				'height' => 16,
			]);
		} else if($lead !== null) {
			$li->appendCreate('img', [
				'o-static-src' => '/icons/'.$lead,
				'alt' => '',
			]);
		}

		if($lead !== null) {
			$li->append(' ');
			$li->addClass('hasleadicon');
		}

		$li->append($v);
	}

	return $subsection;
}

$share = [];
$uriprefix = (\Osmium\HTTPS ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']
	.rtrim(\Osmium\get_ini_setting('relative_path'), '/');
$title = 'View '.$fit['metadata']['name'].' on Osmium';

$object = $p->element('object', [
	'type' => 'image/svg+xml',
	'data' => $svguri = $uriprefix.$exporturi('svg', 'svg'),
]);
$object->appendCreate('a', [
	'href' => $uriprefix.$canonicaluri,
	$title,
]);

$share[] = [
	null,
	'Embed this loadout in your website/wiki/forum/etc. ',
	[ 'a', [ 'href' => $svguri, '(see demo)' ] ],
	':',
	[ 'br' ],
	[ 'pre', $object->renderNode() ],
];

if(isset($canonicaluriwithrevision)) {
	$share[] = [
		null,
		'# Permanent link ',
		[ 'small', '(latest revision):' ],
		[ 'br' ],
		[ 'pre', $uriprefix.$canonicaluri ],
	];

	$share[] = [
		null,
		'# Permanent link ',
		[ 'small', '(revision #'.$fit['metadata']['revision'].'):' ],
		[ 'br' ],
		[ 'pre', $uriprefix.$canonicaluriwithrevision ],
	];
} else {
	$share[] = [
		null,
		'# Permanent link:',
		[ 'br' ],
		[ 'pre', $uriprefix.$canonicaluri ],
	];
}

$share[] = [
	null,
	'Markdown code:',
	[ 'br' ],
	[ 'pre', '['.$title.']('.$uriprefix.$canonicaluri.')' ],
];

$share[] = [
	null,
	'BBCode:',
	[ 'br' ],
	[ 'pre', '[url='.$uriprefix.$canonicaluri.']'.$title.'[/url]' ],
];

$anchor = $p->element('a', [
	'href' => $uriprefix.$canonicaluri,
	$title,
]);

$share[] = [
	null,
	'HTML:',
	[ 'br' ],
	[ 'pre', $anchor->renderNode() ],
];

make_list('Share', $share);



$export = [];

$export[] = [
	null,
	[ 'a', [
		'o-rel-href' => $exporturi('clf', 'json'),
		'type' => 'application/json',
		'rel' => 'nofollow',
		[ 'strong', 'Export to CLF (Common Loadout Format)' ]
	] ],
	': recommended for archival and for usage with other programs.'
];
$export[] = [
	null,
	[ 'a', [
		'o-rel-href' => $exporturi('clf', 'json', false, [ 'minify' => 1 ]),
		'type' => 'application/json',
		'rel' => 'nofollow',
		[ 'strong', 'Export to minified CLF' ]
	] ],
	': stripped down version of the above. Not human-readable.'
];
$export[] = [
	null,
	[ 'a', [
		'o-rel-href' => $exporturi('md', 'txt'),
		'type' => 'text/plain',
		'rel' => 'nofollow',
		[ 'strong', 'Export to Markdown+gzCLF' ]
	] ],
	': a Markdown-formatted description of the loadout, with embedded CLF for programs.'
];
$export[] = [
	null,
	[ 'a', [
		'o-rel-href' => $exporturi('evexml', 'xml', true),
		'type' => 'application/xml',
		'rel' => 'nofollow',
		[ 'strong', 'Export to XML+gzCLF' ]
	] ],
	': recommended when importing in the game client.'
];
$export[] = [
	null,
	'Lossy formats: ',
	[ 'a', [
		'o-rel-href' => $exporturi('evexml', 'xml', true, [ 'embedclf' => 0 ]),
		'type' => 'application/xml',
		'rel' => 'nofollow',
		'XML'
	] ],
	', ',
	[ 'a', [
		'o-rel-href' => $exporturi('eft', 'txt', true),
		'type' => 'text/plain',
		'rel' => 'nofollow',
		'EFT'
	] ],
	', ',
	[ 'a', [
		'o-rel-href' => $exporturi('dna', 'txt', true),
		'type' => 'text/plain',
		'rel' => 'nofollow',
		'DNA'
	] ],
	', ',
	[ 'a', [
		'data-ccpdna' => $dna,
		'in-game DNA'
	] ],
	[ 'br' ],
	[ 'pre', $dna ],
];

$subsection = make_list('Export', $export);
$subsection->setAttribute('id', 'export');
if(!isset($fit['ship']['typeid'])) {
	$subsection->prepend($p->element(
		'p.warning_box',
		'You are exporting an incomplete loadout. Some programs may not react nicely to them.'
	));
}



$perms = array();

$fvp = [ null, '' ];
switch($fit['metadata']['view_permission']) {

case \Osmium\Fit\VIEW_EVERYONE:
	$fvp =  [
		null,
		'This loadout can be viewed by ',
		[ 'strong', 'anyone' ],
	];
	break;

case \Osmium\Fit\VIEW_ALLIANCE_ONLY:
	if($author['apiverified'] === 't' && $author['allianceid'] > 0) {
		$fvp = [
			[ 2, 13, 64, 64 ],
			'This loadout can only be viewed by members of ',
			[ 'strong', $author['alliancename'] ]
		];
	} else {
		$fvp = [
			[ 1, 25, 32, 32 ],
			'This loadout can only be viewed by members of ',
			[ 'strong', 'the owner\'s alliance' ],
			' (but the owner is not API-verified)',
		];
	}
	break;

case \Osmium\Fit\VIEW_CORPORATION_ONLY:
	if($author['apiverified'] === 't') {
		$fvp = [
			[ 3, 13, 64, 64 ],
			'This loadout can only be viewed by members of ',
			[ 'strong', $author['corporationname'] ],
		];
	} else {
		$fvp = [
			[ 1, 25, 32, 32 ],
			'This loadout can only be viewed by members of ',
			[ 'strong', 'the owner\'s corporation' ],
			' (but the owner is not API-verified)',
		];
	}
	break;

case \Osmium\Fit\VIEW_OWNER_ONLY:
	$fvp = [
		[ 1, 25, 32, 32 ],
		'This loadout can be viewed by its ',
		[ 'strong', 'owner only' ],
	];
	break;

case \Osmium\Fit\VIEW_GOOD_STANDING:
case \Osmium\Fit\VIEW_EXCELLENT_STANDING:

	$level = $fit['metadata']['view_permission'] == \Osmium\Fit\VIEW_GOOD_STANDING
		? 'good' : 'excellent';

	if($author['apiverified'] !== 't') {
		$fvp = [
			'This loadout can be viewed by members of ',
			[ 'strong', 'the owner\'s alliance' ],
			' and the owner\'s contacts with a ',
			[ 'strong', $level.' standing' ],
			' (but the owner is not API-verified)',
		];
	} else if($author['allianceid'] > 0) {
		$fvp = [
			'This loadout can be viewed by members of ',
			[ 'strong', $author['alliancename'] ],
			' and contacts of ',
			[ 'strong', $author['charactername'] ],
			' with a ',
			[ 'strong', $level.' standing' ],
		];
	} else {
		$fvp = [
			'This loadout can be viewed by members of ',
			[ 'strong', $author['corporationname'] ],
			' and contacts of ',
			[ 'strong', $author['charactername'] ],
			' with a ',
			[ 'strong', $level.' standing' ],
		];
	}

	array_unshift($fvp, [ 5, 28, 32, 32 ]);
	break;

}

switch((int)$fit['metadata']['password_mode']) {

case \Osmium\Fit\PASSWORD_NONE:
	$fvp[] = '.';
	break;

case \Osmium\Fit\PASSWORD_FOREIGN_ONLY:
	$fvp[] = ', or by anyone provided they have the ';
	$fvp[] = [ 'strong', 'password' ];
	$fvp[] = '.';
	break;

case \Osmium\Fit\PASSWORD_EVERYONE:
	$fvp[0] = [ 0, 25, 32, 32 ]; /* Override icon */
	$fvp[] = ', and is ';
	$fvp[] = [ 'strong', 'password protected' ];
	$fvp[] = ' for everyone but the owner.';
	break;

}

$perms[] = $fvp;

if($loadoutid !== false) {
	switch($fit['metadata']['edit_permission']) {

	case \Osmium\Fit\EDIT_OWNER_ONLY:
		$perms[] = [
			[ 1, 25, 32, 32 ],
			'This loadout can be edited by its ',
			[ 'strong', 'owner only' ],
			'.',
		];
		break;

	case \Osmium\Fit\EDIT_OWNER_AND_FITTING_MANAGER_ONLY:
		if($author['apiverified'] === 't') {
			$perms[] = [
				[ 3, 13, 64, 64 ],
				'This loadout can be edited by its ',
				[ 'strong', 'owner' ],
				' and by members of ',
				[ 'strong', $author['corporationname'] ],
				' having the ',
				[ 'strong', 'fitting manager' ],
				' role.',
			];
		} else {
			$perms[] = [
				[ 1, 25, 32, 32 ],
				'This loadout can be edited by its ',
				[ 'strong', 'owner' ],
				' and by ',
				[ 'strong', 'fitting managers' ],
				' in the owner\'s corporation, but the owner\'s account is not API-verified.',
			];
		}
		break;

	case \Osmium\Fit\EDIT_CORPORATION_ONLY:
		if($author['apiverified'] === 't') {
			$perms[] = [
				[ 3, 13, 64, 64 ],
				'This loadout can be edited by members of ',
				[ 'strong', $author['corporationname'] ],
				'.',
			];
		} else {
			$perms[] = [
				[ 1, 25, 32, 32 ],
				'This loadout can be edited by members of the owner\'s ',
				[ 'strong', 'corporation' ],
				' but the owner\'s account is not API-verified.',
			];
		}
		break;

	case \Osmium\Fit\EDIT_ALLIANCE_ONLY:
		if($author['apiverified'] === 't' && $author['allianceid'] > 0) {
			$perms[] = [
				[ 2, 13, 64, 64 ],
				'This loadout can be edited by members of ',
				[ 'strong', $author['alliancename'] ],
				'.',
			];
		} else {
			$perms[] = [
				[ 1, 25, 32, 32 ],
				'This loadout can be edited by members of the owner\'s ',
				[ 'strong', 'alliance' ],
				' but the owner is not in any alliance or owner\'s account is not API-verified.',
			];
		}
		break;

	}
} else {
	$perms[] = [
		null,
		'This loadout is ',
		[ 'strong', 'read only' ],
		', but it can still be forked freely.',
	];
}

if($loadoutid === false) {
	$fit['metadata']['visibility'] = \Osmium\Fit\VISIBILITY_PRIVATE;
}

switch($fit['metadata']['visibility']) {

case \Osmium\Fit\VISIBILITY_PUBLIC:
	$perms[] = [
		null,
		'This loadout is ',
		[ 'strong', 'public' ],
		'. It will appear in search results of people that can view the loadout.',
	];
	break;

case \Osmium\Fit\VISIBILITY_PRIVATE:
	$perms[] = [
		[ 4, 13, 64, 64 ],
		'This loadout is ',
		[ 'strong', 'private' ],
		'. It will never appear in search results and it has an obfuscated URI.',
	];
	break;

}

$moderated = $loadoutid !== false && \Osmium\Reputation\is_fit_public($fit);
if(!$moderated) {
	$perms[] = [
		null,
		'This loadout is not subject to public moderation and votes cast on it yield no reputation points.',
	];
}

make_list('Permissions and visibility', $perms);



$actions = array();

if($loadoutid !== false && \Osmium\Flag\is_fit_flaggable($fit)) {
	$actions[] = [
		null,
		[ 'strong', [[ 'a.dangerous', [ 'o-rel-href' => '/flag/'.$loadoutid, '⚑ Report this loadout' ] ]] ],
		': this loadout requires moderator attention.',
	];
}

if($can_edit) {
	$opts = [
		'revision' => $fit['metadata']['revision'],
	];

	if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
		$opts['privatetoken'] = $fit['metadata']['privatetoken'];
	}

	$actions[] = [
		null,
		[ 'o-state-altering-a', [
			'o-rel-href' => '/internal/edit/'.$loadoutid.$p->formatQueryString($opts),
			[ 'strong', 'Edit this loadout' ],
		]],
		': change the loadout (older versions are saved and can be viewed and rolled back through the history)',
	];

	unset($opts['revision']);

	$actions[] = [
		null,
		[ 'strong', [[ 'o-state-altering-a', [
			'o-rel-href' => '/internal/delete/'.$loadoutid.$p->formatQueryString($opts),
			'class' => 'dangerous confirm',
			'Delete this loadout',
		] ]] ],
		': ',
		[ 'strong', 'permanently' ],
		' remove the loadout and all its history.',
	];
}

if($loggedin && $loadoutid !== false) {
	list($fav) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(loadoutid) FROM osmium.accountfavorites
		WHERE accountid = $1 AND loadoutid = $2',
		array(
			$a['accountid'],
			$loadoutid
		)
	));

	if($fav) {
		$title = 'Remove from your favorite loadouts';
		$favimg = [ 3, 25, 32, 32 ];
	} else {
		$title = 'Add to your favorite loadouts';
		$favimg = [ 2, 25, 32, 32 ];
	}

	$opts = [ 'redirect' => 'loadout' ];

	if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
		$opts['privatetoken'] = $fit['metadata']['privatetoken'];
	}

	$actions[] = [
		$favimg,
		[ 'o-state-altering-a', [ 'o-rel-href' => '/internal/favorite/'.$loadoutid.$p->formatQueryString($opts), $title ] ],
		': favorite loadouts are listed on your ',
		[ 'a', [ 'o-rel-href' => '/profile/'.$a['accountid'].'#pfavorites', 'profile page' ] ],
		'.',
	];
}

if(isset($fit['ship']['typename'])) {
	$actions[] = [
		null,
		[ 'a', [
			'o-rel-href' => '/search'.$p->formatQueryString([ 'q' => '@ship "'.$fit['ship']['typename'].'"' ]),
			'Browse all '.$fit['ship']['typename'].' loadouts',
		] ],
	];
}

if(isset($rauthorname)) {
	$actions[] = [
		null,
		[ 'a', [
			'o-rel-href' => '/search'.$p->formatQueryString([ 'q' => '@author "'.$rauthorname.'"' ]),
			'Browse loadouts from '.$rauthorname,
		] ],
	];
}

if(isset($fit['ship']['typeid'])) {
	$href = 'https://zkillboard.com/ship/'.$fit['ship']['typeid'];
	$munged = hash_hmac('sha256', $href, \Osmium\get_ini_setting('uri_munge_secret'));

	$anchor = $p->element('a', [
		'o-rel-href' => '/internal/redirect/'.$munged.'?'.urlencode($href),
		$fit['ship']['typename'].' activity on zKillboard',
	]);

	if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
		/* Don't leak private URIs */
		$anchor->setAttribute('rel', 'noreferrer');
	}

	$actions[] = [
		null,
		$anchor,
	];
}

make_list('Actions', $actions);
