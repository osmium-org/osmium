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
  $fit = &osmium_get_fit();
  $modules = array(
		   'high' => array(), 
		   'medium' => array(), 
		   'low' => array(), 
		   'rig' => array(), 
		   'subsystem' => array()
		   );
  $typeids = array();

  foreach(array_keys($modules) as $type) {
    $i = 0;
    while(isset($_GET[$type.$i])) {
      $modules[$type][] = ($typeid = $_GET[$type.$i]);
      $typeids[$typeid] = true;
      ++$i;
    }
  }

  list($highslots, $medslots, $lowslots, $rigslots, $subsystemslots) = pg_fetch_row(osmium_pg_query_params('SELECT hislots, medslots, lowslots, rigslots, subsystemslots FROM osmium.dgmshipslots WHERE typeid = $1', array($fit['hull']['typeid'])));
  $fit['hull']['slotcount']['high'] = $highslots;
  $fit['hull']['slotcount']['medium'] = $medslots;
  $fit['hull']['slotcount']['low'] = $lowslots;
  $fit['hull']['slotcount']['rig'] = $rigslots;
  $fit['hull']['slotcount']['subsystem'] = $subsystemslots;

  $req = osmium_pg_query_params('SELECT typename, invmodules.typeid, slottype, extrahighslots, extramedslots, extralowslots FROM osmium.invmodules JOIN osmium.dgmmoduleattributes ON dgmmoduleattributes.typeid = invmodules.typeid WHERE invmodules.typeid IN ('.
				implode(',', array_merge(array(-1), array_keys($typeids))).')', array());
  $rows = array();
  while($row = pg_fetch_row($req)) {
    $rows[$row[1]] = $row;
  }

  foreach($modules as $type => &$mods) {
    foreach($mods as &$m) {
      $fit['hull']['slotcount']['high'] += $rows[$m][3];
      $fit['hull']['slotcount']['medium'] += $rows[$m][4];
      $fit['hull']['slotcount']['low'] += $rows[$m][5];
      $m = array('typename' => $rows[$m][0], 'typeid' => $rows[$m][1], 'slottype' => $rows[$m][2]);
    }
  }

  foreach($modules as $type => &$mods) {
    for($i = count($mods); $i < $fit['hull']['slotcount'][$type]; ++$i) {
      $mods[] = -1;
    }
  }

  $fit['modules'] = $modules;
  osmium_json($fit);
} else {
  osmium_json(array());
}
