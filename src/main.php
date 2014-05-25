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

namespace Osmium\Page\Main;

require __DIR__.'/../inc/root.php';

$p = new \Osmium\DOM\Page();
$a = \Osmium\State\get_state('a', [ 'accountid' => 0 ]);



$p->head->appendCreate('link', [
	'o-rel-href' => '/atom/newfits.xml',
	'type' => 'application/atom+xml',
	'rel' => 'alternate',
	'title' => 'New fits',
]);
$p->head->appendCreate('link', [
	'o-rel-href' => '/atom/recentlyupdated.xml',
	'type' => 'application/atom+xml',
	'rel' => 'alternate',
	'title' => 'Recently updated',
]);



$name = \Osmium\get_ini_setting('name');
$desc = \Osmium\get_ini_setting('description');
$h1 = $p->content->appendCreate('h1', [ 'id' => 'mainp', $name.' — '.$desc ]);



$p->content->appendCreate('div', [
	'class' => 'mainpcont quick',
	'id' => 'search_mini',
	$p->makeSearchBox(),
]);



$maincont = $p->content->appendCreate('div', [ 'class' => 'mainpcont' ]);



$maincont->append($p->fragment(get_cache_memory_or_gen('metrics', 30, function() use($p) {
	$section = $p->element('section', [
		'class' => 'metrics',
		[ 'h2', 'Quick stats' ],
	]);

	$nctx = \Osmium\State\count_cache_entries([ 'mmin' => 10 ], 'Loadout_New_')
		+ \Osmium\State\count_memory_cache_entries(array(), 'Loadout_View_');

	$nusers = max(1, \Osmium\State\count_memory_cache_entries(array(), 'Activity_'));

	$ul = $section->appendCreate('ul');
	$ul->appendCreate('li', get_estimate_count($p, 'loadouts', 'loadout', 'total loadouts'));
	$ul->appendCreate('li', get_estimate_count($p, 'fittings', 'fitting', 'distinct fittings'));
	$ul->appendCreate('li', get_estimate_count($p, 'fittingmodules', 'fitted module', 'total fitted modules'));
	$ul->append(get_li($p, $nctx, 'dogma context', 'dogma contexts'));

	$ul = $section->appendCreate('ul');
	$ul->appendCreate('li', get_estimate_count($p, 'accounts', 'account', 'accounts'));
	$ul->append([
		get_cached_li($p, function() {
			return (int)\Osmium\Db\fetch_row(\Osmium\Db\query(
				'SELECT count(accountid) FROM accounts WHERE apiverified = true'
			))[0];
		}, 'verified_accounts', 3601, 'verified account', 'verified accounts'),
		get_cached_li($p, function() {
			return (int)\Osmium\Db\fetch_row(\Osmium\Db\query(
				'SELECT sum(reputation) FROM accounts'
			))[0];
		}, 'reputation_total', 3602, 'total reputation points', 'total reputation points'),
		get_li($p, $nusers, 'active user', 'active users'),
	]);

	return $section->renderNode();
}, 'Main_')));




$maincont->append($p->fragment(get_cache_memory_or_gen('popular_tags', 3603, function() use($p) {
	$section = $p->element('section', [ 'class' => 'populartags' ]);
	$section->appendCreate('h2', 'Common tags');
	$ul = $section->appendCreate('ul', [ 'class' => 'tags', 'id' => 'populartags' ]);

	$query = \Osmium\Db\query(
		'SELECT tagname, count
		FROM osmium.tagcount
		ORDER BY count DESC
		LIMIT 15'
	);

	while($row = \Osmium\Db\fetch_row($query)) {
		$ul->appendCreate('li', [
			[ 'a', [ 'o-rel-href' => '/browse/best'.$p->formatQueryString([ 'q' => '@tags "'.$row[0].'"' ]),
					 $row[0] ] ],
			' (',
			$row[1],
			')',
		]);
	}

	return $section->renderNode();
}, 'Main_')));



$maincont->append($p->fragment(get_cache_memory_or_gen(
	'new_fits_'.$a['accountid'], 601, function() use($p) {
		$section = $p->element('section', [ 'class' => 'newfits' ]);
		$section->appendCreate('h2', [
			'New fits ',
			[ 'small', [
				[ 'a', [
					'o-rel-href' => '/atom/newfits.xml',
					'type' => 'application/atom+xml',
					[ 'img', [ 'o-static-src' => '/icons/feed.svg', 'alt' => 'Atom feed' ] ],
				]],
			]],
		]);

		$ids = \Osmium\Search\get_search_ids(
			'', 'ORDER BY creationdate DESC', 0, 20
		);

		if($ids !== []) {
			$section->append($p->makeLoadoutGridLayout($ids));
		} else {
			$section->appendCreate('p', [
				'class' => 'placeholder',
				'No loadouts yet! What are you waiting for?',
			]);
		}

		$section->appendCreate('p', [
			'class' => 'b_more',
			[ 'a', [ 'o-rel-href' => '/browse/new', 'Browse more new loadouts…' ] ],
		]);

		return $section->renderNode();
}, 'Main_')));



$maincont->append($p->fragment(get_cache_memory_or_gen(
	'popular_fits_'.$a['accountid'], 602, function() use($p) {
		$section = $p->element('section', [ 'class' => 'popularfits' ]);
		$section->appendCreate('h2', 'Popular fits');

		$vercutoff = array_values(\Osmium\Fit\get_eve_db_versions())[2]['build'];
		$ids = \Osmium\Search\get_search_ids(
			'', 'AND build >= '.$vercutoff.' ORDER BY score DESC', 0, 20
		);

		if($ids !== []) {
			$section->append($p->makeLoadoutGridLayout($ids));
		} else {
			$section->appendCreate('p', [
				'class' => 'placeholder',
				'No loadouts yet! What are you waiting for?',
			]);
		}

		$section->appendCreate('p', [
			'class' => 'b_more',
			[ 'a', [ 'o-rel-href' => '/browse/best', 'Browse more popular loadouts…' ] ],
		]);

		return $section->renderNode();
}, 'Main_')));



$maincont->append($p->fragment(get_cache_memory_or_gen('fotw', 603, function() use($p) {
	$section = $p->element('section', [ 'class' => 'fotw' ]);
	$section->appendCreate('h2', [
		'Flavors of the week ',
		[ 'small', [ 'data from ', [ 'a', [ 'href' => 'https://zkillboard.com/', 'zKillboard' ] ] ] ],
	]);

	$topkills = \Osmium\State\get_cache('top_kills', null);
	if($topkills === null || $topkills['fotw'] === []) {
		return '';
	}

	$ol = $section->appendCreate('ol');
	foreach($topkills['fotw'] as $f) {
		list($shipid, ) = explode(':', $f['dna'], 2);

		$shiptypename = \Osmium\Fit\get_typename($shipid);
		$fname = $shiptypename.' fitting'.($f['tags'] ? ': '.implode(', ', $f['tags']) : '');

		$a = $ol->appendCreate('li')
			->appendCreate('a', [ 'rel' => 'nofollow', 'o-rel-href' => '/loadout/dna/'.$f['dna'] ]);

		$a->appendCreate('div', [ 'class' => 'abs dogmaattrs' ])->append([
			[ 'strong', $p->formatKMB($f['dps'], 2) ],
			[ 'small', ' DPS' ],
			[ 'br' ],
			[ 'strong', $p->formatKMB($f['ehp'], 2, 'k') ],
			[ 'small', ' EHP' ],
			[ 'br' ],
			[ 'strong', $p->formatKMB($f['price'], 2) ],
			[ 'small', ' ISK' ],
		]);

		$a->append($p->makeLoadoutShipIcon($shipid, $shiptypename)->attr('title', $fname));

		$a->appendCreate('div', [
			'class' => 'absnum losscount',
			[ 'span', [
				[ 'strong', $p->formatExactInteger($f['count']) ],
				[ 'small', 'lost' ],
			]]
		]);
	}

	return $section->renderNode();
}, 'Main_')));



$maincont->append($p->fragment(get_cache_memory_or_gen('doctrines', 604, function() use($p) {
	$section = $p->element('section', [ 'class' => 'doctrines' ]);
	$section->appendCreate('h2', [
		'Popular alliance doctrines ',
		[ 'small', [ 'data from ', [ 'a', [ 'href' => 'https://zkillboard.com/', 'zKillboard' ] ] ] ],
	]);

	$topkills = \Osmium\State\get_cache('top_kills', null);
	if($topkills === null || $topkills['doctrine'] === []) {
		return '';
	}

	$ol = $section->appendCreate('ol');
	foreach($topkills['doctrine'] as $f) {
		list($shipid, ) = explode(':', $f['dna'], 2);

		$shiptypename = \Osmium\Fit\get_typename($shipid);
		$fname = $shiptypename.' fitting'.($f['tags'] ? ': '.implode(', ', $f['tags']) : '');

		$anames = [];
		$alogos = [];
		foreach($f['alliances'] as $a) {
			list($count, $id, $name) = $a;
			$anames[] = $name.' ('.$p->formatExactInteger(100 * $count / $f['count']).' %)';
			$alogos[] = [
				'o-eve-img',
				[ 'class' => 'abs', 'src' => '/Alliance/'.$id.'_128.png', 'alt' => $name ]
			];
		}
		$aname = "mainly flown by: \n".implode(", \n", $anames);

		$a = $ol->appendCreate('li')
			->appendCreate('a', [ 'rel' => 'nofollow', 'o-rel-href' => '/loadout/dna/'.$f['dna'] ]);

		$a->appendCreate('div', [ 'class' => 'abs dogmaattrs' ])->append([
			[ 'strong', $p->formatKMB($f['dps'], 2) ],
			[ 'small', ' DPS' ],
			[ 'br' ],
			[ 'strong', $p->formatKMB($f['ehp'], 2, 'k') ],
			[ 'small', ' EHP' ],
			[ 'br' ],
			[ 'strong', $p->formatKMB($f['price'], 2) ],
			[ 'small', ' ISK' ],
		]);

		$a->append($p->makeLoadoutShipIcon($shipid, $shiptypename)->attr('title', $fname));

		$a->appendCreate('div', [ 'title' => $aname, 'class' => 'abs alogos n'.count($f['alliances']) ])
			->append($alogos);

		$a->appendCreate('div', [
			'class' => 'absnum losscount',
			[ 'span', [
				[ 'strong', $p->formatExactInteger($f['count']) ],
				[ 'small', 'lost' ],
			]]
		]);
	}

	return $section->renderNode();
}, 'Main_')));



$p->title = $name.' / '.$desc;
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';
$p->canonical = '/';
$p->render($ctx);
die();



function get_estimate_count(\Osmium\DOM\Page $p, $table, $single, $plural) {
	$n = (int)\Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT reltuples FROM pg_class WHERE relname = $1',
		array($table)
	))[0];
	return [ [ 'strong', $p->formatExactInteger($n) ], ' ', $n === 1 ? $single : $plural ];
}

function get_li(\Osmium\DOM\Page $p, $num, $single, $plural) {
	return $p->element('li', [ [ 'strong', $p->formatExactInteger($num) ], ' ', $num === 1 ? $single : $plural ]);
}

function get_cached_li(\Osmium\DOM\Page $p, callable $getcount, $key, $ttl, $single, $plural) {
	$val = get_cache_memory_or_gen($key, $ttl, $getcount);
	if($val === false) return;
	return $p->element('li', [ [ 'strong', $p->formatExactInteger($val) ], ' ', $val === 1 ? $single : $plural ]);
}

function get_cache_memory_or_gen($key, $ttl, $genfunc, $prefix = 'Metrics_') {
	$val = \Osmium\State\get_cache_memory($key, null, $prefix);
	if($val === null) {
		$sem = \Osmium\State\semaphore_acquire_nc($prefix.$key);
		if($sem === false) return false;

		$val = \Osmium\State\get_cache_memory($key, null, $prefix);
		if($val === null) {
			$val = $genfunc();
			\Osmium\State\put_cache_memory($key, $val, $ttl, $prefix);
		}
		\Osmium\State\semaphore_release_nc($sem);
	}

	return $val;
}
