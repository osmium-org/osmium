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

namespace Osmium\Json\SearchModules;

require __DIR__.'/../../inc/root.php';

const MAX_MODULES = 16;

$q = $_GET['q'];
unset($_GET['q']);

$filters = array();

foreach($_GET as $i => $val) {
	if($val == 0) $filters[] = $i;
}

$query = \Osmium\Search\query('SELECT id, typename, category, subcategory
FROM osmium_types
WHERE metagroupid NOT IN ('.implode(',', array_merge(array(-1), $filters)).')
AND MATCH(\''.\Osmium\Search\escape($q).'\')
LIMIT '.(MAX_MODULES + 1));

$out = array();
while($row = \Osmium\Search\fetch_assoc($query)) {
	if($row['category'] != 'module') continue;

	$out[] = array('typeid' => $row['id'],
	               'typename' => $row['typename'],
	               'slottype' => $row['subcategory']);
}

if(count($out) == MAX_MODULES + 1) {
	array_pop($out);
	$warning = 'More modules matched the search.<br />Only showing the first '.MAX_MODULES.'.';
} else if($out === array()) {
	$warning = 'No match.';
} else {
	$warning = false;
}

\Osmium\Chrome\return_json(array('payload' => $out, 'warning' => $warning));
