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

namespace Osmium\Ajax\UpdateChargePreset;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax_common.php';

if(!\Osmium\State\is_logged_in()) {
  die();
}

if(!isset($_GET['token']) || $_GET['token'] != \Osmium\State\get_token()) {
  die();
}

$fit = \Osmium\State\get_state('new_fit', array());

if($_GET['action'] == 'update') {
  $idx = intval($_GET['index']);
  $fit['charges'][$idx]['name'] = $_GET['name'];
  foreach(\Osmium\Fit\get_slottypes() as $type) {
    $i = 0;
    $fit['charges'][$idx][$type] = array();
    for($i = 0; $i < 16; ++$i) {
      if(!isset($_GET[$type.$i])) continue;
      $fit['charges'][$idx][$type][$i]['typeid'] = intval($_GET[$type.$i]);
    }
  }
} else if($_GET['action'] == 'delete') {
  $idx = intval($_GET['index']);
  unset($fit['charges'][$idx]);
  $fit['charges'] = array_values($fit['charges']); /* Reorder the numeric keys */
}

\Osmium\State\put_state('new_fit', $fit);