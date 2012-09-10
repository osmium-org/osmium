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

namespace Osmium\Api\Json\QueryLoadouts;

require __DIR__.'/../../../inc/root.php';

const CACHE_TIMER = 3600;

$query = isset($_GET['query']) ? $_GET['query'] : '';
$limit = isset($_GET['limit']) ? $_GET['limit'] : 25;
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : 'relevance';

$sorts = array(
	'creationdate' => 'ORDER BY updatedate DESC', /* Not a typo */
	'score' => 'ORDER BY score DESC',
	'relevance' => '',
);

if(!preg_match('%[0-9]+%', (string)$limit)) {
	header('HTTP/1.1 400 Bad Request');
	die('limit is not a positive integer');
}

$limit = (int)$limit;
if($limit < 0 || $limit > 50) {
	header('HTTP/1.1 400 Bad Request');
	die('limit is out of bounds');
}

if(!preg_match('%[0-9]+%', (string)$offset)) {
	header('HTTP/1.1 400 Bad Request');
	die('offset is not a positive integer');
}

$offset = (int)$offset;
if($offset < 0 || $offset > 1000) {
	header('HTTP/1.1 400 Bad Request');
	die('offset is out of bounds');
}

if(!in_array($sortby, array_keys($sorts))) {
	header('HTTP/1.1 400 Bad Request');
	die('invalid sortby value');	
}

$ids = \Osmium\Search\get_search_ids($query, $sorts[$sortby], $offset, $limit);
$ids[] = -1;

$q = \Osmium\Db\query(
'SELECT l.loadoutid, f.name, f.hullid, stn.typename, lh.updatedate,
a.nickname, a.charactername, a.apiverified,
ltl.taglist, f.description, ls.upvotes, ls.downvotes, ls.score
FROM osmium.loadouts AS l
JOIN osmium.loadoutslatestrevision AS llr ON llr.loadoutid = l.loadoutid
JOIN osmium.loadouthistory AS lh ON lh.loadoutid = l.loadoutid AND lh.revision = llr.latestrevision
JOIN osmium.fittings AS f ON f.fittinghash = lh.fittinghash
JOIN eve.invtypes AS stn ON stn.typeid = f.hullid
JOIN osmium.accounts AS a ON a.accountid = l.accountid
LEFT JOIN osmium.loadoutstaglist AS ltl ON ltl.fittinghash = lh.fittinghash
JOIN osmium.loadoutscores AS ls ON ls.loadoutid = l.loadoutid
WHERE l.loadoutid IN ('.implode(',', $ids).')'
);
$rows = array();
while($row = \Osmium\Db\fetch_assoc($q)) {
	$rows[$row['loadoutid']] = $row;
}

$uriprefix = 'http://'.$_SERVER['HTTP_HOST'];
$uripath = explode('/', $_SERVER['REQUEST_URI']);
array_pop($uripath);
array_pop($uripath);
array_pop($uripath);
$uriprefix .= implode('/', $uripath);

$result = array();
foreach($ids as $id) {
	if($id === -1) continue;

	$r = array();
	$r['uri'] = $uriprefix.'/loadout/'.$rows[$id]['loadoutid'];
	$r['name'] = $rows[$id]['name'];
	$r['shiptypeid'] = (int)$rows[$id]['hullid'];
	$r['shiptypename'] = $rows[$id]['typename'];
	$r['author']['type'] = $rows[$id]['apiverified'] === 't' ? 'character' : 'nickname';
	$r['author']['name'] = $rows[$id]['apiverified'] === 't' ? $rows[$id]['charactername'] : $rows[$id]['nickname'];
	$r['tags'] = explode(' ', $rows[$id]['taglist']);
	$r['creationdate'] = (int)$rows[$id]['updatedate'];
	$r['rawdescription'] = $rows[$id]['description'];
	$r['fdescription'] = \Osmium\Chrome\format_sanitize_md($rows[$id]['description']);
	$r['score'] = (float)$rows[$id]['score'];
	$r['upvotes'] = (int)$rows[$id]['upvotes'];
	$r['downvotes'] = (int)$rows[$id]['downvotes'];

	$result[] = $r;
}

header('Content-Type: application/json; charset=utf-8');
header('Expires: '.gmdate('r', time() + 3600));
header('Cache-Control: public');
header('Pragma:');
\Osmium\Chrome\return_json($result, JSON_PRETTY_PRINT);