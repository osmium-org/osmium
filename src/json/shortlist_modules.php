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

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/json_common.php';

if(!osmium_logged_in()) {
  osmium_json(array());
}


if(isset($_GET['token']) && $_GET['token'] == osmium_tok()) {
  $shortlist = array();
  $keys = array();

  $i = 0;
  while(isset($_GET["$i"])) {
    $typeid = $_GET["$i"];
    if(!isset($keys[$typeid])) {
      $keys[$typeid] = true;
      $shortlist[] = intval($typeid);
    }
    ++$i;
  }

  osmium_settings_put('shortlist_modules', serialize($shortlist));
} else {
  $shortlist = unserialize(osmium_settings_get('shortlist_modules', serialize(array())));
}

osmium_json(osmium_get_module_shortlist($shortlist));