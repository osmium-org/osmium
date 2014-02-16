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

echo "<ul>\n";
echo "<li>Default value: ".\Osmium\Chrome\format_number_with_unit(
	$a['defaultvalue'], $a['unitid'], $a['udisplayname'], RELATIVE
)."</li>\n";
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

echo "<ul class='typelist'>";
$cnt = 0;
$entriesbyfl = [];
while($t = \Osmium\Db\fetch_assoc($typesq)) {
	$e = "<li>";
	$e .= "<span class='tval'>"./*\Osmium\Chrome\format_number_with_unit(*/
		$t['value']/*, $a['unitid'], $a['udisplayname'], RELATIVE
		             )*/."</span> ";
	$e .= "<a href='".RELATIVE."/db/type/".$t['typeid']."'>".\Osmium\Chrome\escape($t['typename'])."</a>";
	$e .= "</li>\n";

	if($sort !== 'typename') {
		echo $e;
	} else {
		if(preg_match('%(?<first>[a-zA-Z0-9])%', $t['typename'], $m)) {
			$first = strtoupper($m['first']);
			$first = (strpos("0123456789", $first) === false) ? $first : '0';
		} else {
			$first = ' ';
		}

		$entriesbyfl[$first][] = $e;
	}
}

if($sort === 'typename') {
	$entries = [];
	foreach($entriesbyfl as $k => $v) $entries[] = [ $k, $v ];
	unset($entriesbyfl);

	$c = count($entries);
	$current = 0;
	while(($current + 1) < $c) {
		if(($curcnt = count($entries[$current][1])) >= 10) {
			++$current;
			continue;
		}

		if(($curcnt + count($entries[$current+1][1])) >= 20) {
			$current += 2;
			continue;
		}

		$entries[$current] = [
			$entries[$current][0].' '.$entries[$current+1][0],
			array_merge($entries[$current][1], $entries[$current+1][1])
		];

		/* Remove entry from array and re-number keys */
		array_splice($entries, $current + 1, 1);
		--$c;
	}

	if($c >= 3) {
		foreach($entries as $v) {
			list($l, $entries) = $v;

			$letters = explode(' ', $l);
			$ids = [];
			$links = [];

			foreach($letters as $letter) {
				$links[] = "<a href='#t".$letter."' id='t".$letter."'>"
					.($letter === '0' ? '0-9' : ($letter === '_' ? '~' : $letter))
					."</a>";
			}

			echo "<li class='letteranchor'>";
			echo implode(', ', $links);
			echo "</li>\n";
			foreach($entries as $e) echo $e;
		}
	} else {
		foreach($entries as $v) {
			foreach($v[1] as $e) echo $e;
		}
	}
}

echo "</ul>\n";


echo "</div>\n";
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
