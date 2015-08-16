<?php
/* Osmium
 * Copyright (C) 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\DBBrowser\MainPage;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();

$cacheid = 'DBBrowser_Root';
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage;
}



$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);

$header = $dbb->appendCreate('header');
$header->appendCreate('h1', 'Database browser');

$header->appendCreate('p', 'The database browser allows you to browse through items (called types) and view their attributes, effects, etc. You can also compare types by using the compare tool.');



$section = $dbb->appendCreate('section#dbsearch');
$section->appendCreate('h2', 'Search for types');

$section->appendCreate('div#search_mini', [
	$p->makeSearchBox($p::MSB_SEARCH_TYPES),
]);



$section = $dbb->appendCreate('section#dbmarket');
$section->appendCreate('h2', 'Browse types by market group');
$ul = $section->appendCreate('ul.typelist');

$mgq = \Osmium\Db\query(
	'SELECT marketgroupid, marketgroupname
    FROM eve.invmarketgroups
    WHERE parentgroupid IS NULL
    ORDER BY marketgroupname ASC'
);

while($mg = \Osmium\Db\fetch_assoc($mgq)) {
	$ul->appendCreate('li')->appendCreate('a', [
		'o-rel-href' => '/db/marketgroup/'.$mg['marketgroupid'],
		$mg['marketgroupname']
	]);
}



$section = $dbb->appendCreate('section#dbdogma');
$section->appendCreate('h2', 'Browse types by dogma category and group');
$ul = $section->appendCreate('ul.typelist');

$catsq = \Osmium\Db\query(
	'SELECT categoryid, categoryname
    FROM eve.invcategories
    ORDER BY categoryname ASC'
);

while($c = \Osmium\Db\fetch_assoc($catsq)) {
	$ul->appendCreate('li')->appendCreate('a', [
		'o-rel-href' => '/db/category/'.$c['categoryid'],
		$c['categoryname']
	]);
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = 'Database browser';
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
