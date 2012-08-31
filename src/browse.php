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

namespace Osmium\Page\Browse;

require __DIR__.'/../inc/root.php';

$type = $_GET['type'];
if($type == 'best') {
	$t = 'Browse top rated loadouts';
	$more = 'ORDER BY score DESC';
} else if($type == 'new') {
	$t = 'Browse new loadouts';
	$more = 'ORDER BY creationdate DESC';
} else {
	\Osmium\Fatal('Unknown $type');
}

unset($_GET['type']);
if(!isset($_GET['f'])) $_GET['f'] = '';

\Osmium\Chrome\print_header($t, '..', false);
echo "<div id='search_mini'>\n";
echo "<form method='get' action='".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."'>\n";
echo "<h1><label for='filter'><img src='../static-".\Osmium\STATICVER."/icons/filter.png' alt='' /> Filter loadouts</label></h1>\n";
echo "<p>\n<input type='search' name='f' value='".htmlspecialchars($_GET['f'], ENT_QUOTES)."' id='filter' />\n<input type='submit' value='Filter' />\n</p>\n";
echo "</form>\n</div>\n";
\Osmium\Search\print_pretty_results('..', $_GET['f'], $more, true, 25, 'p', 'No loadouts matched your filter(s).');
\Osmium\Chrome\print_footer();
