<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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
require \Osmium\ROOT.'/inc/atom_common.php';

\Osmium\Chrome\print_header('', '.', true, "<link href='./atom/newfits.xml' type='application/atom+xml' rel='alternate' title='New fits' />\n<link href='./atom/recentlyupdated.xml' type='application/atom+xml' rel='alternate' title='Recently updated' />\n");

echo "<h1 id='mainp'>Osmium — ".\Osmium\SHORT_DESCRIPTION."</h1>\n";
echo "<div id='mainpcont'>\n";

echo "<div class='quick' id='search_mini'>\n";
\Osmium\Search\print_search_form('./search');
echo "</div>\n";

$a = \Osmium\State\get_state('a', null);
$now = time();
$accountid = $a === null ? 0 : $a['accountid'];

echo "<section>\n";
echo "<h2>Popular</h2>\n<ul class='tags' id='populartags'>\n";

if(($ptags = \Osmium\State\get_cache_memory('main_popular_tags', null)) === null) {
	$query = \Osmium\Db\query(
		"SELECT tagname, count FROM osmium.tagcount ORDER BY count DESC LIMIT 13");

	$ptags = '';
	while($row = \Osmium\Db\fetch_row($query)) {
		list($name, $count) = $row;

		$ptags .= "<li><a href='./browse/best?f=".urlencode('@tags "'.$name.'"')
			."'>$name</a> ($count)</li>\n";
	}

	\Osmium\State\put_cache_memory('main_popular_tags', $ptags, 86400);
}

echo $ptags;
echo "</ul>\n";
echo "<p class='b_more'><a href='./browse/best'>Browse all popular loadouts…</a></p>\n";
echo "</section>\n";

echo "<section>\n";
echo "<h2>New fits <small><a href='./atom/newfits.xml' type='application/atom+xml'><img src='./static-".\Osmium\STATICVER."/icons/feed.svg' alt='Atom feed' /></a></small></h2>\n";
\Osmium\Search\print_loadout_list(\Osmium\AtomCommon\get_new_fits($accountid),
                                  '.', 0, 'No loadouts yet! What are you waiting for?');
echo "<p class='b_more'><a href='./browse/new'>Browse more new loadouts…</a></p>\n";
echo "</section>\n";

echo "<section>\n";
echo "<h2>Recently updated <small><a href='./atom/recentlyupdated.xml' type='application/atom+xml'><img src='./static-".\Osmium\STATICVER."/icons/feed.svg' alt='Atom feed' /></a></small></h2>\n";
\Osmium\Search\print_loadout_list(
	\Osmium\AtomCommon\get_recently_updated_fits($accountid),
	'.');
echo "</section>\n";
echo "</div>\n";

\Osmium\Chrome\print_footer();
