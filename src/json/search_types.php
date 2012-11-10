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

namespace Osmium\Json\SearchTypes;

require __DIR__.'/../../inc/root.php';

const MAX_TYPES = 10;

$q = $_GET['q'];

$filters = array_keys(array_filter(isset($_GET['mg']) && is_array($_GET['mg']) ? $_GET['mg'] : array(),
                                   function($v) { return $v === '0'; }));
$filters[] = -1;

$query = \Osmium\Search\query('SELECT id, typename, category, subcategory, metagroupid
FROM osmium_types
WHERE metagroupid NOT IN ('.implode(',', $filters).')
AND MATCH(\''.\Osmium\Search\escape($q).'\')
LIMIT '.(MAX_TYPES + 1));

if($query === false) {
	\Osmium\Chrome\return_json(array('payload' => array(), 'warning' => 'Invalid search query.'));
}

$out = array();
while($row = \Osmium\Search\fetch_assoc($query)) {
	$out[] = array(
		(int)$row['id'],
		$row['typename'],
		$row['category'],
		$row['subcategory'],
		(int)$row['metagroupid'],
		);
}

if(count($out) == MAX_TYPES + 1) {
	array_pop($out);
	$warning = 'More types matched the search.<br />Only showing the first '.MAX_TYPES.'.';
} else if($out === array()) {
	$warning = 'No match.';
} else {
	$warning = false;
}

\Osmium\Chrome\return_json(array('payload' => $out, 'warning' => $warning));
