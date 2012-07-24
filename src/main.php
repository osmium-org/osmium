<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\Main;

require __DIR__.'/../inc/root.php';

\Osmium\Chrome\print_header('', '.');

echo "<h1 id='mainp'>Osmium — ".\Osmium\SHORT_DESCRIPTION."</h1>\n";
echo "<div id='mainpcont'>\n";

echo "<div class='quick' id='search_mini'>\n";
\Osmium\Chrome\print_search_form();
echo "</div>\n";

$a = \Osmium\State\get_state('a', null);
$now = time();
$accountid = $a === null ? 0 : $a['accountid'];

echo "<section>\n";
echo "<h2>Popular tags</h2>\n<ul class='tags' id='populartags'>\n";

if(($ptags = \Osmium\State\get_cache_memory('main_popular_tags', null)) === null) {
	$query = \Osmium\Db\query(
		"SELECT tagname, count FROM osmium.tagcount ORDER BY count DESC LIMIT 13");

	$ptags = '';
	while($row = \Osmium\Db\fetch_row($query)) {
		list($name, $count) = $row;

		$ptags .= "<li><a href='./search?q=".urlencode('@tags "'.$name.'"')
			."'>$name</a> ($count)</li>\n";
	}

	\Osmium\State\put_cache_memory('main_popular_tags', $ptags, 86400);
}

echo $ptags;
echo "</ul>\n</section>\n";

echo "<section>\n";
echo "<h2>New fits</h2>\n";

$ids = array();
$query = \Osmium\Db\query_params('SELECT a.loadoutid FROM osmium.searchableloadouts AS a 
JOIN osmium.loadoutslatestrevision AS llr ON a.loadoutid = llr.loadoutid
JOIN osmium.loadouthistory AS lhf ON (a.loadoutid = lhf.loadoutid AND lhf.revision = 1)
JOIN osmium.loadouthistory AS lh ON (lh.loadoutid = a.loadoutid AND lh.revision = llr.latestrevision)
WHERE a.accountid IN (0, $2) AND (lhf.updatedate >= ($1 - 86400) OR lh.revision = 1)
ORDER BY lh.updatedate DESC
LIMIT 5', array($now, $accountid));
while($row = \Osmium\Db\fetch_row($query)) {
	$ids[] = $row[0];
}

\Osmium\Search\print_loadout_list($ids, '.', 0, 'No loadouts yet! What are you waiting for?');
echo "</section>\n";

echo "<section>\n";
echo "<h2>Recently updated</h2>\n";

$ids = array();
$query = \Osmium\Db\query_params('SELECT a.loadoutid FROM osmium.searchableloadouts AS a 
JOIN osmium.loadoutslatestrevision AS llr ON a.loadoutid = llr.loadoutid
JOIN osmium.loadouthistory AS lhf ON (a.loadoutid = lhf.loadoutid AND lhf.revision = 1)
JOIN osmium.loadouthistory AS lh ON (lh.loadoutid = a.loadoutid AND lh.revision = llr.latestrevision)
WHERE a.accountid IN (0, $2) AND (lhf.updatedate < ($1 - 86400) AND lh.revision > 1)
ORDER BY lh.updatedate DESC
LIMIT 5', array($now, $accountid));
while($row = \Osmium\Db\fetch_row($query)) {
	$ids[] = $row[0];
}

\Osmium\Search\print_loadout_list($ids, '.');
echo "</section>\n";
echo "</div>\n";

\Osmium\Chrome\print_footer();
