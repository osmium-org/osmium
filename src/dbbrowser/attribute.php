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
require \Osmium\ROOT.'/inc/dbbrowser_common.php';

const RELATIVE = '../..';

$a = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT attributeid, attributename, dgmattribs.displayname, defaultvalue,
		stackable, dgmunits.unitid, published, dgmunits.displayname AS udisplayname
		FROM eve.dgmattribs
		LEFT JOIN eve.dgmunits ON dgmunits.unitid = dgmattribs.unitid
		WHERE attributeid = $1',
		array($_GET['attributeid'])
	)
);

if($a === false) \Osmium\fatal(404);

\Osmium\Chrome\print_header(
	$a['attributename'].' / Attribute '.$a['attributeid'],
	RELATIVE,
	$_SERVER['QUERY_STRING'] === ''
);
echo "<div id='dbb'>\n";

$name = '';
if($a['displayname']) {
	if(preg_replace('%[^a-z0-9]%', '', strtolower($a['attributename']))
	   === preg_replace('%[^a-z0-9]%', '', strtolower($a['displayname']))) {
		$name = $a['displayname'];
	} else {
		$name = "<span class='raw'>".$a['attributename']."</span><br />".ucfirst($a['displayname']);
	}
} else {
	$name = "<span class='raw'>".$a['attributename']."</span>";
}

echo "<header>\n<h2>".$name;
echo " <small>";
if($a['published'] !== 't') {
	echo "<span class='unpublished'>not public</span> – ";
}
echo "attribute ".$a['attributeid']."</small></h2>\n</header>\n";

$def = (!$a['defaultvalue'] && in_array($a['unitid'], [ 115, 116, 129 ]))
	? '<small>N/A</small>' :
	\Osmium\Chrome\format_number_with_unit(
		$a['defaultvalue'], $a['unitid'], $a['udisplayname'], RELATIVE
	);

echo "<ul>\n";
echo "<li>Default value: {$def}</li>\n";
echo "<li>Stacking penalized: ".($a['stackable'] === 't' ? 'never' : 'yes')."</li>\n";
echo "</ul>\n";

if(isset($_GET['s']) && in_array($_GET['s'], [ 'typeid', 'typename', 'value' ], true)) {
	$sort = $_GET['s'];
} else {
	$sort = 'typename';
}

$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename, dta.value
	FROM eve.dgmtypeattribs dta
	JOIN eve.invtypes it ON it.typeid = dta.typeid AND it.published = true
	WHERE dta.attributeid = $1
	ORDER BY '.$sort.' ASC',
	array($a['attributeid'])
);

echo "<h3>List of types with non-default attribute value:</h3>\n";

echo "<p>Sort list by: <a href='?s=typeid' rel='nofollow'>type ID</a>, <a href='?s=typename' rel='nofollow'>name</a>, <a href='?s=value' rel='nofollow'>attribute value</a>.</p>\n";

$types = [];
while($t = \Osmium\Db\fetch_assoc($typesq)) {
	$e = "<li>";
	$e .= "<span class='tval'>".$t['value']."</span> ";
	$e .= "<a href='".RELATIVE."/db/type/".$t['typeid']."'>"
		.\Osmium\Chrome\escape($t['typename'])."</a>";
	$e .= "</li>\n";

	$types[] = [ $t['typename'], $e ];
}

if($sort === 'typename') {
	\Osmium\DBBrowser\print_typelist($types);
} else {
	echo "<ul class='typelist'>\n";

	foreach($types as $t) echo $t[1];

	echo "</ul>\n";
}


echo "</div>\n";
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
