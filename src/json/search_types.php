<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

$w = '';
$q = preg_replace_callback("%@meta ([0-9]+)(\s|$)%", function($m) use(&$w) {
	$w .= ' AND metalevel = '.(int)$m[1];
	return '';
}, $q, 1);

$query = \Osmium\Search\query('SELECT id
FROM osmium_types
WHERE metagroupid NOT IN ('.implode(',', $filters).')
AND MATCH(\''.\Osmium\Search\escape($q).'\') '.$w.'
LIMIT '.(MAX_TYPES + 1).'
OPTION field_weights=(typename2=100,synonyms=100,parenttypename=10,parentsynonyms=10,groupname=10,marketgroupname=10)');

if($query === false) {
	\Osmium\Chrome\return_json(array('payload' => array(), 'warning' => 'Invalid search query.'));
}

$out = array();
while($row = \Osmium\Search\fetch_assoc($query)) {
	$out[] = (int)$row['id'];
}

if(count($out) == MAX_TYPES + 1) {
	array_pop($out);
	$warning = 'More types matched the search.<br />Only showing the first '.MAX_TYPES.'.';
} else if($out === array()) {
	$warning = 'No match.';
} else {
	$warning = false;
}

$json = array('payload' => $out);
if($warning) $json['warning'] = $warning;

\Osmium\Chrome\return_json($json);
