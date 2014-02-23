<?php
/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\DBBrowser\ViewCategory;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();


$categoryid = (int)$_GET['categoryid'];
$cacheid = 'DBBrowser_Category_'.$categoryid;
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage;
}



$c = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT ic.categoryid, ic.categoryname
		FROM eve.invcategories ic
		WHERE categoryid = $1',
		array($_GET['categoryid'])
	)
);

if($c === false) \Osmium\fatal(404);

$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);

$header = $dbb->appendCreate('header');
$h2 = $header->appendCreate('h2', $c['categoryname']);
$small = $h2->appendCreate('small', 'category '.$categoryid);



$groupsq = \Osmium\Db\query_params(
	'SELECT ig.groupid, ig.groupname
	FROM eve.invgroups ig
	WHERE ig.categoryid = $1 AND ig.published = true
	ORDER BY ig.groupname ASC',
	array($c['categoryid'])
);

$h3 = $p->element('h3', 'Groups in this category:');
$ul = $p->element('ul', [ 'class' => 'typelist' ]);
$ngroups = 0;

while($g = \Osmium\Db\fetch_assoc($groupsq)) {
	++$ngroups;
	$ul->appendCreate('li', [
		[ 'a', [ 'o-rel-href' => '/db/group/'.$g['groupid'], $g['groupname'] ] ]
	]);
}

if($ngroups > 0) {
	$dbb->append([ $h3, $ul ]);
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = \Osmium\Fit\get_categoryname($categoryid).' / Category '.$categoryid;
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
