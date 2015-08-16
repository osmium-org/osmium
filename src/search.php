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

namespace Osmium\Page\Search;

require __DIR__.'/../inc/root.php';

if(isset($_GET['q']) && $_GET['q'] !== '') {
	$query = $_GET['q'];
} else {
	$query = false;
}

$cond = \Osmium\Search\get_search_cond_from_advanced();
$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';
$p->index = false;

$typesonly = isset($_GET['m']) && $_GET['m'] === 't';
$smode = $typesonly ? $p::MSB_SEARCH_TYPES : $p::MSB_SEARCH;

if($query === false) {
	$p->title = (isset($_GET['ad']) && $_GET['ad'] == 1) ? 'Advanced search' : (
		$typesonly ? 'Search types' : 'Search lodaouts'
	);
	$p->content->appendCreate('div', [ 'id' => 'search_full' ])->append(
		$p->makeSearchBox($smode)
	);
} else {
	if((!isset($_GET['p']) || $_GET['p'] === '1')
	   && $query
	   && $tq = \Osmium\Search\query(\Osmium\Search\get_type_search_query($query, [], 20))) {

		$firstid = null;
		$typelist = $p->element('ol.type_sr');
		while($t = \Osmium\Search\fetch_row($tq)) {
			$typeid = $t[0];
			$tn = \Osmium\Fit\get_typename($typeid);

			if($firstid === null) $firstid = $t[0];

			$li = $typelist->appendCreate('li');
			$a = $li->appendCreate('a', [ 'o-rel-href' => '/db/type/'.$typeid, 'title' => $tn ]);
			$a->appendCreate('o-eve-img', [ 'src' => '/Type/'.$typeid.'_64.png', 'alt' => '' ]);
			$a->append($tn);
		}

		$ntypes = $typelist->childNodes->length;

		if($ntypes === 0) {
			$typelist->appendCreate('li.placeholder', 'No results.');
		}
	} else {
		$ntypes = 0;
	}

	$p->title = 'Search results';
	if($query !== '') {
		$p->title .= ' / '.$query;
	}

	list($loadoutids, $loadoutsr) = \Osmium\Search\make_pretty_results($p, $query, $cond, true, 24);
	$nloadouts = count($loadoutids);

	if(!isset($_GET['p'])) {
		if($ntypes === 1 && $nloadouts === 0) {
			/* Redirect to type page */
			header('Location: ./db/type/'.$firstid);
			die();
		}
	}



	$p->content->appendCreate('div', [ 'id' => 'search_mini' ])->append(
		$p->makeSearchBox($smode)
	);

	if($ntypes > 0 || $typesonly) {
		$p->content->appendCreate('section', [ 'class' => 'sr' ])->append([
			[ 'h2', 'Types' ],
			$typelist,
		]);
	}

	if(!$typesonly && ($nloadouts > 0 || $ntypes === 0)) {
		$p->content->appendCreate('section', [ 'class' => 'sr' ])
			->append([[ 'h2', 'Loadouts' ]])
			->append($loadoutsr);
	}
}

$p->render($ctx);
