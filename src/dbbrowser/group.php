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

namespace Osmium\Page\DBBrowser\ViewGroup;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();


$groupid = (int)$_GET['groupid'];
$cacheid = 'DBBrowser_Group_'.$groupid;
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage;
}



$g = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT ig.groupid, ig.groupname, ig.published,
		ic.categoryid, ic.categoryname
		FROM eve.invgroups ig
		LEFT JOIN eve.invcategories ic ON ic.categoryid = ig.categoryid
		WHERE groupid = $1',
		array($groupid)
	)
);

if($g === false) \Osmium\fatal(404);

$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);

$header = $dbb->appendCreate('header');
$h2 = $header->appendCreate('h2', $g['groupname']);
$small = $h2->appendCreate('small', 'group '.$groupid);
if($g['published'] !== 't') {
	$small->prepend([[ 'span', [ 'class' => 'unpublished', 'not public' ] ]]);
}

$nav = $dbb->appendCreate('nav');
$ul = $nav->appendCreate('ul');
$ul->append([
	[ 'li', [ [ 'a', [ 'o-rel-href' => '/db/category/'.$g['categoryid'], $g['categoryname'] ] ] ] ],
	[ 'li', [ 'class' => 'lst', $g['groupname'] ] ],
]);



$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename
	FROM eve.invtypes it
	WHERE it.groupid = $1 AND it.published = true
	ORDER BY it.typename ASC',
	array($g['groupid'])
);

$h3 = $p->element('h3', 'Types in this group:');
$ul = $p->element('ul', [ 'class' => 'typelist' ]);
$ntypes = 0;

while($t = \Osmium\Db\fetch_assoc($typesq)) {
	++$ntypes;
	$ul->appendCreate('li', [
		[ 'a', [ 'o-rel-href' => '/db/type/'.$t['typeid'], $t['typename'] ] ]
	]);
}

if($ntypes > 0) {
	$dbb->append([ $h3, $ul ]);
	$dbb->appendCreate('p', [
		'class' => 'compare',
		[ 'a', [ 'o-rel-href' => '/db/comparegroup/'.$groupid.'/auto',
		         'Compare all types in this group' ] ],
	]);
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = \Osmium\Fit\get_groupname($groupid).' / Group '.$groupid;
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
