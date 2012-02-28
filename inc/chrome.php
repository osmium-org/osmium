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

function osmium_header($title = '', $relative = '.') {
  global $__osmium_chrome_relative;
  $__osmium_chrome_relative = $relative;

  osmium_api_maybe_redirect($relative);

  if($title == '') {
    $title = 'Osmium / '.OSMIUM_SHORTDESC;
  } else {
    $title .= ' / Osmium';
  }

  echo "<!DOCTYPE html>\n<html>\n<head>\n";
  echo "<meta charset='UTF-8' />\n";
  echo "<link rel='stylesheet' href='$relative/static/chrome.css' type='text/css' />\n";
  echo "<link rel='icon' type='image/png' href='$relative/static/favicon.png' />\n";
  echo "<title>$title</title>\n";
  echo "</head>\n<body>\n<div id='wrapper'>\n";

  osmium_statebox($relative);
}

function osmium_footer() {
  global $__osmium_chrome_relative;
  echo "<div id='push'></div>\n</div>\n<div id='footer'>\n";
  echo "<p><strong>Osmium ".OSMIUM_VERSION." @ ".gethostname()."</strong> — (Artefact2/Indalecia) — <a href='https://github.com/Artefact2/osmium'>Browse source</a> (<a href='http://www.gnu.org/licenses/agpl.html'>AGPLv3</a>)</p>";
  echo "</div>\n</body>\n</html>\n";
}