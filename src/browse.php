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

namespace Osmium\Page\Browse;

require __DIR__.'/../inc/root.php';

$type = $_GET['type'];
if($type == 'best') {
	$t = 'Browse top rated loadouts';
	$more = 'ORDER BY score DESC';
} else if($type == 'new') {
	$t = 'Browse new loadouts';
	$more = 'ORDER BY creationdate DESC';
} else {
	\Osmium\Fatal(400);
}

unset($_GET['type']);
if(!isset($_GET['q'])) $_GET['q'] = '';

$advq = \Osmium\Search\get_search_cond_from_advanced();

if(!isset($_GET['sort'])) {
	$advq .= ' '.$more;
}



$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$p->title = $t;
$p->index = false;
$ctx->relative = '..';

$p->content->appendCreate('div', [ 'id' => 'search_mini' ])->append(
	$p->makeSearchBox(\Osmium\DOM\Page::MSB_FILTER)
);

$p->content->append(
	\Osmium\Search\make_pretty_results(
		$p, $_GET['q'], $advq,
		true, 24, 'p', 'No loadouts matched your filter(s).'
	)[1]
);

$p->render($ctx);
