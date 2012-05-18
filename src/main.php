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

echo "<h1>Osmium — ".\Osmium\SHORT_DESCRIPTION."</h1>\n";

if(\Osmium\State\is_logged_in()) {
  echo "<p class='create_new'><a href='./new'>Create a new fitting</a></p>\n";
}

\Osmium\Chrome\print_footer();