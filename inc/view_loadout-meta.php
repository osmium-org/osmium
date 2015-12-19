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
