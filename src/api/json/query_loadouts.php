<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\API\Json\QueryLoadouts;

require __DIR__.'/../../../inc/root.php';
require \Osmium\ROOT.'/inc/api_common.php';

$query = isset($_GET['query']) ? $_GET['query'] : '';
$limit = isset($_GET['limit']) ? $_GET['limit'] : 25;
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : 'creationdate';

/* XXX: complete the list using inc/search.php */
$sorts = array(
	'creationdate' => 'ORDER BY updatedate DESC', /* Not a typo */
	'score' => 'ORDER BY score DESC',
	'comments' => 'ORDER BY comments DESC',
	'relevance' => '',
);

if(!preg_match('%[0-9]+%', (string)$limit)) {
	\Osmium\fatal(400, 'limit is not a positive integer');
}

$limit = (int)$limit;
if($limit < 0 || $limit > 50) {
	\Osmium\fatal(400, 'limit is out of bounds');
}

if(!preg_match('%[0-9]+%', (string)$offset)) {
	\Osmium\fatal(400, 'offset is not a positive integer');
}

$offset = (int)$offset;
if($offset < 0 || $offset > 1000) {
	\Osmium\fatal(400, 'offset is out of bounds');
}

if(!in_array($sortby, array_keys($sorts))) {
	\Osmium\fatal(400, 'invalid sortby value');
}

$cond = $sorts[$sortby];

if(isset($_GET['buildmin'])) {
	$cond = ' AND build >= '.(int)$_GET['buildmin'].' '.$cond;
}
if(isset($_GET['buildmax'])) {
	$cond = ' AND build <= '.(int)$_GET['buildmax'].' '.$cond;
}

$ids = \Osmium\Search\get_search_ids($query, $cond, $offset, $limit);
$ids[] = -1;

/* XXX: use loadoutssearchresults */
$q = \Osmium\Db\query(
'SELECT l.loadoutid, l.visibility, f.name, f.hullid, stn.typename, lh.updatedate,
a.nickname, a.charactername, a.apiverified,
fat.taglist, f.description, ls.upvotes, ls.downvotes, ls.score, COALESCE(lcc.count, 0) AS comments,
f.evebuildnumber
FROM osmium.loadouts AS l
JOIN osmium.loadoutslatestrevision AS llr ON llr.loadoutid = l.loadoutid
JOIN osmium.loadouthistory AS lh ON lh.loadoutid = l.loadoutid AND lh.revision = llr.latestrevision
JOIN osmium.fittings AS f ON f.fittinghash = lh.fittinghash
JOIN eve.invtypes AS stn ON stn.typeid = f.hullid
JOIN osmium.accounts AS a ON a.accountid = l.accountid
LEFT JOIN osmium.fittingaggtags AS fat ON fat.fittinghash = lh.fittinghash
JOIN osmium.loadoutscores AS ls ON ls.loadoutid = l.loadoutid
LEFT JOIN osmium.loadoutcommentcount lcc ON lcc.loadoutid = l.loadoutid
WHERE l.loadoutid IN ('.implode(',', $ids).')'
);
$rows = array();
while($row = \Osmium\Db\fetch_assoc($q)) {
	$rows[$row['loadoutid']] = $row;
}

$uriprefix = (\Osmium\HTTPS ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
$uripath = explode('/', $_SERVER['REQUEST_URI']);
array_pop($uripath);
array_pop($uripath);
array_pop($uripath);
$uriprefix .= implode('/', $uripath);

$result = array();
foreach($ids as $id) {
	if($id === -1) continue;

	$r = array();
	/* Use dummy private token as a safeguard in case this ever spits out private loadouts */
	$r['uri'] = $uriprefix.'/'.\Osmium\Fit\get_fit_uri($rows[$id]['loadoutid'], $rows[$id]['visibility'], '0');
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
	$r['comments'] = (int)$rows[$id]['comments'];
	$r['buildnumber'] = (int)$rows[$id]['evebuildnumber'];

	$result[] = $r;
}

/* If the user is logged in, private loadouts may be returned and should not be stored in a public cache. */
\Osmium\API\outputp(json_encode($result), 'application/json', null, \Osmium\State\is_logged_in());
