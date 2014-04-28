<?php
/* Osmium
 * Copyright (C) 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\ViewPrivileges;

require __DIR__.'/../inc/root.php';

$p = new \Osmium\DOM\Page();
$p->title = 'Privileges';

$anonymous = !\Osmium\State\is_logged_in();
$myrep = \Osmium\Reputation\get_current_reputation();
$bs = \Osmium\get_ini_setting('bootstrap_mode');

$div = $p->content->appendCreate('div', [ 'id' => 'vprivileges' ]);



$changes = \Osmium\Reputation\get_updown_vote_reputation();
$up = $changes[\Osmium\Reputation\VOTE_TYPE_UP];
$down = $changes[\Osmium\Reputation\VOTE_TYPE_DOWN];

function formatquantities(\Osmium\DOM\Page $p, array $deltas, $type) {
	$return = [];

	list($destdelta, $srcdelta) = $deltas[$type];

	if($destdelta !== 0) {
		$unit = ' point'.(abs($destdelta) != 1 ? 's' : '');
		if($destdelta > 0) $destdelta = '+'.$destdelta;
		$return[] = $destdelta.$unit;
	}
	if($srcdelta !== 0) {
		$unit = ' point'.(abs($srcdelta) != 1 ? 's' : '');
		if($srcdelta > 0) $srcdelta = '+'.$srcdelta;
		$return[] = $srcdelta.$unit.' for the voter';
	}

	return $return === [] ? '' : $p->element('small', ' ('.implode('; ', $return).')');
}

$div->appendCreate('section', [ 'id' => 'repguide' ])->append([
	[ 'h2', 'What is reputation?' ],
	[ 'p', 'Reputation points are a rough measure of the community\'s trust. The more points you have, the more privileges you have.' ],
	[ 'p', 'Most privileges do not apply to private or hidden loadouts.' ],
	[ 'h3', 'How do I get reputation?' ],
	[ 'p', 'You get reputation points when…' ],
	[ 'ul', [
		[ 'li', [
			'Someone upvotes one of your public loadouts',
			formatquantities($p, $up, \Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT),
			';',
		]],
		[ 'li', [
			'Someone upvotes one of your comments on a public loadout',
			formatquantities($p, $up, \Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT),
			';',
		]],
	]],
	[ 'p', 'If you submit quality content, your reputation points will raise naturally. Similarly, upvote content when you believe it deserves it.' ],
	[ 'h3', 'Can I lose points?' ],
	[ 'p', 'It is possible to lose reputation, when…' ],
	[ 'ul', [
		[ 'li', [
			'Someone downvotes one of your public loadouts',
			formatquantities($p, $down, \Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT),
			';',
		]],
		[ 'li', [
			'Someone downvotes one of your comments on a public loadout',
			formatquantities($p, $down, \Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT),
			';',
		]],
	]],
	[ 'h3', 'So votes are only useful for reputation?' ],
	[ 'p', [
		'Not only. The amount of votes is used to determine a ',
		[ 'a', [ 'href' => 'http://www.evanmiller.org/how-not-to-sort-by-average-rating.html', 'score' ] ],
		' which is used to sort results. Entries with a very low score may not appear in search results at all.',
	]],

	$anonymous ?
	[ 'p', 'You need to be logged in to gain reputation points and earn privileges.' ]
	: [ 'p', [ 'You currently have ',
	           [ 'strong', [ 'class' => 'reptotal', $p->formatReputation($myrep) ] ],
	           ' reputation point(s).' ]
	],
]);

$section = $div->appendCreate('section', [ 'id' => 'privlist' ]);
$section->appendCreate('h2', 'Available privileges');

if($bs) {
	$section->appendCreate('p', [ 'class' => 'notice_box' ])->append([
		[ 'strong', 'The site is currently in bootstrap mode.' ],
		[ 'br' ],
		'Some privilege requirements may be lowered in bootstrap mode.',
		[ 'br' ],
		'When this happens, the real requirements will be shown in parentheses.',
	]);
}

$ol = $section->appendCreate('ol');
foreach(\Osmium\Reputation\get_privileges() as $priv => $d) {
	$name = $d['name'];
	$rep_needed = $d['req'][0];
	$rep_needed_bs = $d['req'][1];
	$desc = $d['desc'];

	$needed = $bs ? $rep_needed_bs : $rep_needed;
	$progress = round(min(1, $myrep / $needed) * 100, 2);

	$li = $ol->appendCreate('li', [ 'id' => 'p'.$priv ]);
	if($myrep >= $needed) {
		$li->addClass('haveit');
	} else if(!$anonymous) {
		$li->addClass('donthaveit');
	}

	$sp = $li->appendCreate('h2', $name)->appendCreate('span');
	if($myrep >= $needed) {
		$sp->append('got it!');

		if($myrep < $rep_needed) {
			$sp->append(' ');
			$sp->appendCreate('small', [
				'(',
				$p->formatExactInteger($myrep),
				' / ',
				$p->formatExactInteger($rep_needed),
				')',
			]);
		}
	} else {
		$sp->append([
			$p->formatExactInteger($myrep),
			' / ',
			$p->formatExactInteger($bs ? $rep_needed_bs : $rep_needed),
		]);

		if($bs && $rep_needed > $rep_needed_bs) {
			$sp->append(' ');
			$sp->appendCreate('small', [
				'(',
				$p->formatExactInteger($myrep),
				' / ',
				$p->formatExactInteger($rep_needed),
				')',
			]);
		}
	}

	$li->appendCreate('div', [ 'class' => 'progress' ])->appendCreate('div', [
		'class' => 'pinner',
		'style' => 'width: '.$progress.'%;',
		' ',
	]);

	$li->appendCreate('div', [ 'class' => 'desc', $p->fragment($desc) ]);
}

$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '';
$p->snippets[] = 'view_privileges';
$p->render($ctx);

