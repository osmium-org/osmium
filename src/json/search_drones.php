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

namespace Osmium\Json\SearchDrones;

require __DIR__.'/../../inc/root.php';

if(!\Osmium\State\is_logged_in()) {
  \Osmium\Chrome\return_json(array());
}

const MAX_DRONES = 16;

$q = $_GET['q'];
unset($_GET['q']);

$filters = array();

foreach($_GET as $i => $val) {
  if($val == 0) $filters[] = $i;
}

$query = \Osmium\Db\query_params('SELECT typeid, typename
FROM osmium.invdrones
WHERE typename ~* $1 OR groupname ~* $1
ORDER BY groupid ASC, typeid ASC
LIMIT '.(MAX_DRONES + 1), array($q));

$out = array();
$typeids = array();
$i = 0;
while($row = \Osmium\Db\fetch_row($query)) {
  $out[] = array('typeid' => $row[0], 'typename' => $row[1]);
  $typeids[] = $row[0];
  ++$i;
}

if($i == MAX_DRONES + 1) {
  array_pop($out);
  $warning = 'More drones matched the search.<br />Only showing the first '.MAX_DRONES.'.';
} else if($i == 0) {
  $warning = 'No match.';
} else {
  $warning = false;
}

\Osmium\Chrome\return_json(array('payload' => $out, 'warning' => $warning));
