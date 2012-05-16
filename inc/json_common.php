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

function osmium_slottypes() {
  return array('high', 'medium', 'low', 'rig', 'subsystem');
}

function osmium_get_module_shortlist($shortlist = null) {
  if(!osmium_logged_in()) return array();

  if($shortlist === null) {
    $shortlist = unserialize(osmium_settings_get('shortlist_modules', serialize(array())));
  }
 
  $out = array();
  $rows = array();
  $req = osmium_pg_query_params('SELECT typename, invmodules.typeid, slottype FROM osmium.invmodules JOIN osmium.dgmmoduleattributes ON dgmmoduleattributes.typeid = invmodules.typeid WHERE invmodules.typeid IN ('
				.implode(',', array_merge(array(-1), $shortlist)).')', array());
  while($row = pg_fetch_row($req)) {
    $rows[$row[1]] = array('typename' => $row[0], 'typeid' => $row[1], 'slottype' => $row[2]);
  }

  foreach($shortlist as $typeid) {
    if(!isset($rows[$typeid])) continue;
    $out[] = $rows[$typeid];
  }

  return $out;
}

function &osmium_get_fit() {
  if(!osmium_logged_in()) return array();
  return $_SESSION['__osmium_fit'];
}

function osmium_create_fit($typeid) {
  $fit =& osmium_get_fit();

  $row = pg_fetch_row(osmium_pg_query_params('SELECT invships.typeid, typename, lowslots, medslots, hislots, rigslots, subsystemslots FROM osmium.invships JOIN osmium.dgmshipslots ON dgmshipslots.typeid = invships.typeid WHERE invships.typeid = $1', array($typeid)));

  if($row !== false) {
    list($typeid, $typename, $lowslots, $medslots, $highslots, $rigslots, $subsystemslots) = $row;
    $fit['hull'] = array(
			 'typeid' => $typeid,
			 'typename' => $typename,
			 'slotcount' => array(
					      'high' => $highslots,
					      'medium' => $medslots,
					      'low' => $lowslots,
					      'rig' => $rigslots,
					      'subsystem' => $subsystemslots
					      ),
			 );

    if(!isset($fit['modules'])) {
      $fit['modules'] = array(
			      'high' => array(), 
			      'medium' => array(), 
			      'low' => array(), 
			      'rig' => array(), 
			      'subsystem' => array()
			      );
    }

    if(!isset($fit['charges'])) {
      $fit['charges'] = array(array('name' => 'Default'));
    }
  }

  return $row !== false;
}
