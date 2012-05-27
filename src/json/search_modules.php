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

if(!\Osmium\State\is_logged_in()) {
	\Osmium\Chrome\return_json(array());
}

const MAX_MODULES = 10;

$q = $_GET['q'];
unset($_GET['q']);

$filters = array();

foreach($_GET as $i => $val) {
	if($val == 0) $filters[] = $i;
}

$query = \Osmium\Db\query_params('SELECT invmodules.typeid, typename
FROM osmium.invmodules
WHERE metagroupid NOT IN ('.implode(',', array_merge(array(-1), $filters)).')
AND typename ~* $1 OR groupname ~* $1
ORDER BY metagroupid ASC, typename ASC
LIMIT '.(MAX_MODULES + 1), array($q));

$out = array();
$typeids = array();
$i = 0;
while($row = \Osmium\Db\fetch_row($query)) {
	$out[] = array('typeid' => $row[0], 'typename' => $row[1]);
	$typeids[] = $row[0];
	++$i;
}

$modattr = array();
\Osmium\Fit\get_attributes_and_effects($typeids, $modattr);
foreach($out as &$row) {
	$row['slottype'] = \Osmium\Fit\get_module_slottype($modattr[$row['typeid']]['effects']);
}

if($i == MAX_MODULES + 1) {
	array_pop($out);
	$warning = 'More modules matched the search.<br />Only showing the first '.MAX_MODULES.'.';
} else if($i == 0) {
	$warning = 'No match.';
} else {
	$warning = false;
}

\Osmium\State\put_setting('module_search_filter', serialize($filters));

\Osmium\Chrome\return_json(array('payload' => $out, 'warning' => $warning));
