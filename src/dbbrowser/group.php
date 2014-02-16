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
require \Osmium\ROOT.'/inc/dbbrowser_common.php';

const RELATIVE = '../..';

$g = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT ig.groupid, ig.groupname, ig.published,
		ic.categoryid, ic.categoryname
		FROM eve.invgroups ig
		LEFT JOIN eve.invcategories ic ON ic.categoryid = ig.categoryid
		WHERE groupid = $1',
		array($_GET['groupid'])
	)
);

if($g === false) \Osmium\fatal(404);

\Osmium\Chrome\print_header(
	\Osmium\Chrome\escape(strip_tags($g['groupname'])).' / Group '.$g['groupid'],
	RELATIVE
);
echo "<div id='dbb'>\n";

echo "<header>\n<h2>".\Osmium\Chrome\escape($g['groupname']);
echo " <small>";
if($g['published'] !== 't') {
	echo "<span class='unpublished'>not public</span> – ";
}
echo "group ".$g['groupid']."</small></h2>\n</header>\n";


echo "<nav>\n<ul>\n";
echo "<li><a href='../category/".$g['categoryid']."'>".\Osmium\Chrome\escape(
	$g['categoryname']
)."</a></li>\n";
echo "<li class='lst'>".\Osmium\Chrome\escape($g['groupname'])."</li>\n";
echo "</ul>\n</nav>\n";


$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename
	FROM eve.invtypes it
	WHERE it.groupid = $1 AND it.published = true
	ORDER BY it.typename ASC',
	array($g['groupid'])
);

echo "<h3>List of types in this group:</h3>\n";

$types = [];
while($t = \Osmium\Db\fetch_assoc($typesq)) {
	$e = "<li>";
	$e .= "<a href='".RELATIVE."/db/type/".$t['typeid']."'>"
		.\Osmium\Chrome\escape($t['typename'])."</a>";
	$e .= "</li>\n";

	$types[] = [ $t['typename'], $e ];
}

\Osmium\DBBrowser\print_typelist($types);


echo "</div>\n";
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
