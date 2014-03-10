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

namespace Osmium\Page\DBBrowser\ViewAttribute;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();



$attributeid = (int)$_GET['attributeid'];
$cacheid = 'DBBrowser_Attribute_'.$attributeid;
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage;
}



$a = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT attributeid, attributename, dgmattribs.displayname, defaultvalue, highisgood,
		stackable, dgmunits.unitid, published, dgmunits.displayname AS udisplayname
		FROM eve.dgmattribs
		LEFT JOIN eve.dgmunits ON dgmunits.unitid = dgmattribs.unitid
		WHERE attributeid = $1',
		array($attributeid)
	)
);

if($a === false) \Osmium\fatal(404);

$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);


if($a['displayname']) {
	if(preg_replace('%[^a-z0-9]%', '', strtolower($a['attributename']))
	   === preg_replace('%[^a-z0-9]%', '', strtolower($a['displayname']))) {
		$name = $a['displayname'];
	} else {
		$name = [
			[ 'span', [ 'class' => 'raw', $a['attributename'] ] ],
			[ 'br' ],
			ucfirst($a['displayname'])
		];
	}
} else {
	$name = [ 'span', [ 'class' => 'raw', $a['attributename'] ] ];
}

$header = $dbb->appendCreate('header');
$h2 = $header->appendCreate('h2', $name);
$small = $h2->appendCreate('small', 'attribute '.$a['attributeid']);

if($a['published'] !== 't') {
	$small->prepend([
		[ 'span', [ 'class' => 'unpublished', 'not public' ] ],
		' – ',
	]);
}

$def = (!$a['defaultvalue'] && in_array($a['unitid'], [ 115, 116, 129 ]))
	? [ 'small', 'N/A' ] : $p->formatNumberWithUnit($a['defaultvalue'], $a['unitid'], $a['udisplayname']);
$dbb->appendCreate('ul')->appendCreate('li', [
	'Default value: ', $def, ' ', [ 'small', [ '(unitid '.$a['unitid'].')' ] ]
]);

$dbb->appendCreate('ul')->append([
	[ 'li', [ 'Stacking penalized: ', [ 'strong', $a['stackable'] === 't' ? 'never' : 'yes' ] ] ],
	[ 'li', [ 'The ', [ 'strong', $a['highisgood'] ? 'higher' : 'lower' ],
	          ', the better ', [ 'small', '(grossly inaccurate)' ] ] ],
]);



$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename, dta.value
	FROM eve.dgmtypeattribs dta
	JOIN eve.invtypes it ON it.typeid = dta.typeid AND it.published = true
	WHERE dta.attributeid = $1 AND dta.value <> $2
	ORDER BY it.typename ASC',
	array($a['attributeid'], $a['defaultvalue'])
);

$h3 = $p->element('h3', 'List of types with non-default attribute value:');
$ul = $p->element('ul', [ 'class' => 'typelist' ]);
$ntypes = 0;

while($t = \Osmium\Db\fetch_assoc($typesq)) {
	++$ntypes;
	$ul->appendCreate('li', [
		[ 'span', [ 'class' => 'tval', $t['value'] ] ],
		' ',
		[ 'a', [ 'o-rel-href' => '/db/type/'.$t['typeid'], $t['typename'] ] ],
	]);
}

if($ntypes > 0) {
	$dbb->append([ $h3, $ul ]);
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = ucfirst(\Osmium\Fit\get_attributename($attributeid)).' / Attribute '.$attributeid;
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
