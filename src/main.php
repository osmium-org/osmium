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

echo "<div class='quick' id='search_mini'>\n";
\Osmium\Chrome\print_search_form();
echo "</div>\n<div id='recent_loadouts'>\n";
echo "<h2>Recently updated</h2>\n";
\Osmium\Search\print_pretty_results('.', '', 'ORDER BY updatedate DESC', false, 10, 'p', 'No loadouts yet! What are you waiting for?');
echo "</div>\n";
\Osmium\Chrome\print_footer();
