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
require \Osmium\ROOT.'/inc/dbbrowser_common.php';

const RELATIVE = '../..';

$c = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT ic.categoryid, ic.categoryname
		FROM eve.invcategories ic
		WHERE categoryid = $1',
		array($_GET['categoryid'])
	)
);

if($c === false) \Osmium\fatal(404);

\Osmium\Chrome\print_header(
	strip_tags($c['categoryname']).' / Category '.$c['categoryid'],
	RELATIVE
);
echo "<div id='dbb'>\n";

echo "<header>\n<h2>".\Osmium\Chrome\escape($c['categoryname']);
echo " <small>category ".$c['categoryid']."</small></h2>\n</header>\n";


$groupsq = \Osmium\Db\query_params(
	'SELECT ig.groupid, ig.groupname
	FROM eve.invgroups ig
	WHERE ig.categoryid = $1 AND ig.published = true
	ORDER BY ig.groupname ASC',
	array($c['categoryid'])
);

echo "<h3>List of groups in this category:</h3>\n";

$groups = [];
while($g = \Osmium\Db\fetch_assoc($groupsq)) {
	$e = "<li>";
	$e .= "<a href='".RELATIVE."/db/group/".$g['groupid']."'>"
		.\Osmium\Chrome\escape($g['groupname'])."</a>";
	$e .= "</li>\n";

	$groups[] = [ $g['groupname'], $e ];
}

\Osmium\DBBrowser\print_typelist($groups);


echo "</div>\n";
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
