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
  else $__osmium_fit['hull_name'] = $row[0];
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

echo "</li>";

echo "<li>\n<h2>Modules &amp; Charges</h2>\n";
echo "</li>";

echo "<li>\n<h2>Drones &amp; Cargohold</h2>\n";
echo "</li>";

echo "<li>\n<h2>Description &amp; privacy settings</h2>\n";
echo "</li>";

echo "</ol>\n";
osmium_footer();