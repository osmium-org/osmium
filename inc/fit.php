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

namespace Osmium\Fit;

function get_slottypes() {
  return array('high', 'medium', 'low', 'rig', 'subsystem');
}

function &get_fit() {
  return $_SESSION['__osmium_fit'];
}

function &get_fit_private() {
  return $_SESSION['__osmium_fit_private'];
}

function init_fit($typeid) {
  $fit =& get_fit();
  $fitp =& get_fit_private();

  $row = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT invships.typeid, typename FROM osmium.invships WHERE invships.typeid = $1', array($typeid)));

  if($row === false) return false;

  get_attributes_and_effects(array($typeid), $fitp['hull']);
  $fitp['hull'] = $fitp['hull'][$row[0]];

  $fit['hull'] = array(
		       'typeid' => $row[0],
		       'typename' => $row[1]
		       );

  init_base_slots($fit, $fitp);
  
  if(!isset($fit['modules'])) {
    foreach(get_slottypes() as $type) {
      $fit['modules'][$type] = array();
    }
  }
  
  if(!isset($fit['charges'])) {
    $fit['charges'] = array(array('name' => 'Default'));
  }
  
  return true;
}

function update_modules($typeids, $modules) {
  static $slot_modifiers = 
    array(
	  'low' => 'lowSlotModifier', 
	  'medium' => 'medSlotModifier', 
	  'high' => 'hiSlotModifier'
	  );

  $fitp =& get_fit_private();
  $fit =& get_fit();

  get_modules_attributes_and_effects($typeids, $fitp['modules'], $fitp['modules']);
  init_base_slots($fit, $fitp);

  $typeids[] = -1;
  $names = array();
  $r = \Osmium\Db\query_params('SELECT typeid, typename FROM osmium.invmodules WHERE typeid IN ('.implode(',', $typeids).')', array());
  while($row = \Osmium\Db\fetch_row($r)) {
    $names[$row[0]] = $row[1];
  }

  foreach(get_slottypes() as $type) {
    $fit['modules'][$type] = array();
  }

  foreach($modules as $type => $a) {
    foreach($a as $i => $typeid) {
      $trueslottype = get_module_slottype($fitp['modules'][$typeid]['effects']);
      if($trueslottype === false) continue;

      $fit['modules'][$trueslottype][] = array('typeid' => $typeid, 'typename' => $names[$typeid]);
      foreach($slot_modifiers as $mtype => $mattribute) {
	if(isset($fitp['modules'][$typeid]['attributes'][$mattribute])) {
	  $fit['hull']['slotcount'][$mtype] += $fitp['modules'][$typeid]['attributes'][$mattribute]['value'];
	}
      }
    }
  }
}

function format_in_array($arr) {
  return implode(',', array_map(function($x) { return "'$x'"; }, $arr));
}

function get_attributes_and_effects($typeids, &$out) {
  static $interesting_effects = 
    array(
	  'loPower',
	  'medPower',
	  'hiPower',
	  'rigSlot',
	  'subSystem',
	  );
  static $interesting_attributes = 
    array(
	  'hiSlots', 
	  'medSlots', 
	  'lowSlots', 
	  'rigSlots', 
	  'maxSubSystems',
	  'hiSlotModifier',
	  'medSlotModifier',
	  'lowSlotModifier',
	  );

  $typeids[] = -1;
  $typeidIN = implode(',', $typeids);
  
  $out['effects'] = array();
  $effectsq = \Osmium\Db\query_params('SELECT typeid, effectname, dgmeffects.effectid
  FROM eve.dgmeffects 
  JOIN eve.dgmtypeeffects 
  ON dgmeffects.effectid = dgmtypeeffects.effectid 
  WHERE typeid IN ('.$typeidIN.') 
  AND effectname IN ('.format_in_array($interesting_effects).')', array());
  while($row = \Osmium\Db\fetch_assoc($effectsq)) {
    $tid = $row['typeid'];
    unset($row['typeid']);
    $out[$tid]['effects'][$row['effectname']] = $row;
  }

  $out['attributes'] = array();
  $effectsq = \Osmium\Db\query_params('SELECT typeid, attributename, dgmattributetypes.attributeid,
  COALESCE(valuefloat, valueint) AS value 
  FROM eve.dgmattributetypes 
  JOIN eve.dgmtypeattributes ON dgmattributetypes.attributeid = dgmtypeattributes.attributeid 
  WHERE typeid IN ('.$typeidIN.') 
  AND attributename IN ('.format_in_array($interesting_attributes).')', array());
  while($row = \Osmium\Db\fetch_assoc($effectsq)) {
    $tid = $row['typeid'];
    unset($row['typeid']);
    $out[$tid]['attributes'][$row['attributename']] = $row;
  }
}

function init_base_slots(&$fit, $fitp) {
  foreach(array_combine(get_slottypes(), 
			array('hiSlots', 'medSlots', 'lowSlots', 'rigSlots', 'maxSubSystems')) 
	  as $type => $attributename) {
    if(isset($fitp['hull']['attributes'][$attributename])) {
      $fit['hull']['slotcount'][$type] = $fitp['hull']['attributes'][$attributename]['value'];
    } else {
      $fit['hull']['slotcount'][$type] = 0;
    }
  }
}

function get_modules_attributes_and_effects($typeids, &$out, $cache) {
  $out = array();
  foreach($typeids as &$typeid) {
    if(isset($cache[$typeid])) {
      $out[$typeid] = $cache[$typeid];
      $typeid = -1;
    }
  }

  get_attributes_and_effects($typeids, $out);
}

function get_module_slottype($effects) {
  if(isset($effects['loPower'])) return 'low';
  if(isset($effects['medPower'])) return 'medium';
  if(isset($effects['hiPower'])) return 'high';
  if(isset($effects['rigSlot'])) return 'rig';
  if(isset($effects['subSystem'])) return 'subsystem';
  return false;
}
