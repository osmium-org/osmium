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

require __DIR__.'/../inc/root.php';

if(!osmium_logged_in()) {
  osmium_fatal(403, 'Sorry, anonymous users cannot create fittings. Please login first and retry.');
}

$__osmium_fit =& $__osmium_state['fit'];

/* effectid => slot type */
$slot_types = array(
		    12 => 'high',
		    13 => 'medium',
		    11 => 'low',
		    2663 => 'rig',
		    3772 => 'subsystem'
);

/* --------- */

if(isset($_POST['reset_all'])) {
  $__osmium_fit = array();
}

if(isset($_POST['change_hull'])) {
  $__osmium_fit['previous_hull_id'] = isset($__osmium_fit['hull_id']) ? $__osmium_fit['hull_id'] : 0;
  unset($__osmium_fit['hull_name']);
  unset($__osmium_fit['hull_id']);
} else if(isset($_POST['new_hull_id'])) {
  $new_hull_id = $_POST['new_hull_id'];
  if((int)$new_hull_id > 0) {
    $__osmium_fit['hull_id'] = (int)$new_hull_id;
  }
}

if(isset($__osmium_fit['hull_id']) && !isset($__osmium_fit['hull_name'])) {
  $row = pg_fetch_row(osmium_pg_query_params('SELECT typename FROM osmium.invships WHERE typeid = $1', array($__osmium_fit['hull_id'])));
  if($row === false) unset($__osmium_fit['hull_id']);
  else {
    $__osmium_fit['hull_name'] = $row[0];
    $row = pg_fetch_row(osmium_pg_query_params('SELECT lowslots, medslots, hislots, rigslots, subsystemslots FROM osmium.dgmslots WHERE typeid = $1', array($__osmium_fit['hull_id'])));
    $__osmium_fit['slotcount']['low'] = $row[0];
    $__osmium_fit['slotcount']['medium'] = $row[1];
    $__osmium_fit['slotcount']['high'] = $row[2];
    $__osmium_fit['slotcount']['rig'] = $row[3];
    $__osmium_fit['slotcount']['subsystem'] = $row[4];
  }
}

/* --------- */

osmium_header('Create a new fitting', '.');;

echo "<h1>Create a new fitting</h1>\n";

if(is_array($__osmium_fit) && count($__osmium_fit) >= 1) {
  echo "<form method='post' action='./new'>\n<p class='clear_fit'><input type='submit' name='reset_all' value='Clear fitting' /> This will clear all the changes you have made to this page. Use it if you want to start over.</p>\n</form>\n";
}

echo "<ol>\n";

echo "<li>\n<h2>Hull</h2>\n";

if(isset($__osmium_fit['hull_name'])) {
  echo "<form method='post' action='./new'>\n<p><img src='http://image.eveonline.com/Render/".$__osmium_fit['hull_id']."_128.png' alt='' /> <strong>".$__osmium_fit['hull_name']."</strong> <input type='submit' name='change_hull' value='Change' /></p>\n</form>\n";
} else {
  $previous = isset($__osmium_fit['previous_hull_id']) ? $__osmium_fit['previous_hull_id'] : 0;

  echo "<form method='post' action='./new'>\n<p>\n";
  echo "<select name='new_hull_id'>\n";
  echo "<option value='0'>——— Select a ship ———</option>\n";

  $q = osmium_pg_query_params('SELECT typeid, typename, groupname FROM osmium.invships ORDER BY groupname ASC, typename ASC', array());
  $previous_groupname = null;
  while($row = pg_fetch_row($q)) {
    list($typeid, $typename, $groupname) = $row;
    if($groupname !== $previous_groupname) {
      if($previous_groupname !== null) echo "</optgroup>\n";
      echo "<optgroup label='$groupname'>\n";

      $previous_groupname = $groupname;
    }

    if($typeid == $previous) {
      $selected = " selected='selected'";
      $has_selected = true;
    } else $selected = '';

    echo "<option value='$typeid'$selected>$typename</option>\n";
  }

  echo "</optgroup>\n";
  echo "</select>\n<input type='submit' value='Confirm choice' />";
  echo "</p>\n</form>\n";
}

echo "</li>\n";

/* --------- */

echo "<li>\n<h2>Modules &amp; Charges</h2>\n<form method='post' action='./new'>\n";

echo "<p class='add_module'>Add a module: ";
if(isset($_POST['new_module_name']) && strlen($_POST['new_module_name']) >= 3 && !empty($_POST['new_module_search'])) {
  echo "<select name='new_module_id'>\n";
  $q = osmium_pg_query_params('SELECT typeid, typename FROM osmium.invmodules WHERE typename ~* $1', array('.*'.$_POST['new_module_name'].'.*'));
  while($r = pg_fetch_row($q)) {
    $have_some_modules = true;
    list($typeid, $typename) = $r;
    echo "<option value='$typeid'>$typename</option>\n";
  }

  if(!isset($have_some_modules)) {
    echo "<option value='0'>——— No matches found ———</option>";
    $disabled = "disabled='disabled' ";
  } else {
    $disabled = '';
  }

  echo "</select> <input type='submit' name='confirm' value='Add module(s)' $disabled/> <input type='submit' name='cancel' value='Cancel' />\n<br />\nQuantity: <input type='text' name='quantity' maxlength='1' value='1' />";
} else {
  echo "<input type='text' name='new_module_name' placeholder='Search for module name (at least 3 characters)' /> <input type='submit' value='Search' name='new_module_search' />";
}
echo "</p>\n";

if(isset($_POST['update_modules'])) {
  if($_POST['update_modules_action'] == 1) {
    /* Remove modules */
    foreach($_POST['select'] as $type => $indices) {
      foreach($indices as $ind => $whatever) {
	$module = $__osmium_fit['slots'][$type][$ind];
	$__osmium_fit['slotcount']['low'] -= $module['extralows'];
	$__osmium_fit['slotcount']['medium'] -= $module['extrameds'];
	$__osmium_fit['slotcount']['high'] -= $module['extrahighs'];
	unset($__osmium_fit['slots'][$type][$ind]);
      }
    }
  } else if($_POST['update_modules_action'] == 2) {
    /* Update charges */
    foreach($_POST['select'] as $type => $indices) {
      foreach($indices as $ind => $whatever) {
	$__osmium_fit['slots'][$type][$ind]['chargeid'] = (int)$_POST['charge'][$type][$ind];
      }
    }
  }
}

echo "<p class='update_modules'>";
echo "On selected modules: <select name='update_modules_action'>\n<option value='0'>——— Select an action ———</option>\n";
echo "<option value='1'>Remove module</option>\n";
echo "<option value='2'>Update charges</option>\n";
echo "</select>\n<input type='submit' name='update_modules' value='Do it!' />\n</p>\n";

if(isset($_POST['confirm']) && $_POST['new_module_id'] > 0 && $_POST['quantity'] > 0) {
  $qty = (int)$_POST['quantity'];
  $typeid = $_POST['new_module_id'];
  if($qty > 8) $qty = 8;
  $r = pg_fetch_row(osmium_pg_query_params('SELECT typename, extralowslots, extramedslots, extrahighslots FROM osmium.invmodules WHERE typeid = $1', array($typeid)));
  if($r !== false) {
    list($typename, $extralows, $extrameds, $extrahighs) = $r;
    list($type) = pg_fetch_row(osmium_pg_query_params('SELECT effectid FROM eve.dgmtypeeffects WHERE typeid = $1 AND effectid IN ('.implode(',', array_keys($slot_types)).') LIMIT 1', array($typeid)));
    $type = $slot_types[$type];

    $charges = osmium_get_charges($typeid);

    for($i = 0; $i < $qty; ++$i) {
      $__osmium_fit['slots'][$type][] = array('typeid' => $typeid, 
					      'typename' => $typename, 
					      'extralows' => $extralows, 
					      'extrameds' => $extrameds, 
					      'extrahighs' => $extrahighs, 
					      'charges' => $charges,
					      'chargeid' => null, 
					      );
      $__osmium_fit['slotcount']['low'] += $extralows;
      $__osmium_fit['slotcount']['medium'] += $extrameds;
      $__osmium_fit['slotcount']['high'] += $extrahighs;
    }
  } 
}

echo "<table class='modules'>\n";
echo "<thead>\n<tr>\n<th>Slot</th>\n<th colspan='2'>Name</th>\n<th colspan='2'>Charge / Script</th>\n<th>Select?</th>\n</tr>\n</thead>\n<tfoot></tfoot>\n<tbody>\n";

$extra_slots_warning = array();
$has_slots = false;
foreach($slot_types as $type) {
  $max = isset($__osmium_fit['slotcount'][$type]) ? $__osmium_fit['slotcount'][$type] : 0;
  $fitted_count = 0;
  if(isset($__osmium_fit['slots'][$type]) && is_array($__osmium_fit['slots'][$type])) {
    foreach($__osmium_fit['slots'][$type] as $idx => $fitted) {
      $typeid = $fitted['typeid'];
      $typename = $fitted['typename'];
      $chargeid = $fitted['chargeid'];
      echo "<tr>\n<td><img class='slot_icon' src='./static/icons/slot_$type.png' alt='$type slot' title='$type slot' /></td>\n<td><img src='http://image.eveonline.com/Type/".$typeid."_64.png' alt='' class='slot_icon' /></td>\n";
      echo "<td>$typename</td>\n";
      
      if($chargeid === null || $chargeid === 0) {
	echo "<td></td>\n";
      } else {
	echo "<td><img src='http://image.eveonline.com/Type/".$chargeid."_64.png' alt='' class='slot_icon' /></td>\n";
      }

      echo "<td>\n";
      if(count($fitted['charges']) > 0) {
	echo "<select name='charge[$type][$idx]'>\n<option value='0'>——— No charge ———</option>\n";
	foreach($fitted['charges'] as $id => $chargename) {
	  if($id == $chargeid) {
	    $selected = " selected='selected'";
	  } else $selected = '';
	  echo "<option value='$id'$selected>$chargename</option>\n";
	}
	echo "</select>\n";
      }
      echo "</td>\n";

      echo "<td><input type='checkbox' name='select[$type][$idx]' /></td>\n</tr>\n";
      ++$fitted_count;
      $has_slots = true;
    }
  }

  if($fitted_count > $max) $extra_slots_warning[] = $type;

  for(; $fitted_count < $max; ++$fitted_count) {
    echo "<tr>\n<td><img class='slot_icon' src='./static/icons/slot_$type.png' alt='$type slot' title='$type slot' /></td>\n<td></td>\n<td><em>Empty $type slot</em></td>\n<td></td>\n<td></td>\n<td></td>\n</tr>\n";
    $has_slots = true;
  }
}

if(!$has_slots) {
  echo "<tr><td colspan='6'>No slots available.</td></tr>\n";
}

echo "</tbody>\n</table>\n";
echo "</form>\n";
foreach($extra_slots_warning as $type) {
  echo "<p class='extra_slots_warning'>Warning: you may have too many $type slots fitted!</p>\n";
}
echo "</li>\n";

/* --------- */

echo "<li>\n<h2>Drones &amp; Cargohold</h2>\n";
echo "</li>\n";

/* --------- */

echo "<li>\n<h2>Description &amp; privacy settings</h2>\n";
echo "</li>\n";

/* --------- */

echo "</ol>\n";
osmium_footer();

function osmium_get_charges($typeid) {
  $charges = array();
  $q = osmium_pg_query_params('SELECT chargeid, chargename FROM osmium.invcharges WHERE moduleid = $1 ORDER BY chargename ASC', array($typeid));
  while($r = pg_fetch_row($q)) {
    $charges[$r[0]] = $r[1];
  }

  return $charges;
}