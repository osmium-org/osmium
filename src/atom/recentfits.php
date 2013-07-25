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

namespace Osmium\Atom\NewFits;

require __DIR__.'/../../inc/root.php';
require \Osmium\ROOT.'/inc/atom_common.php';

define('Osmium\ACTIVITY_IGNORE', true);

$type = isset($_GET['type']) ? $_GET['type'] : '';

if($type == 'newfits') {
	$ids = \Osmium\AtomCommon\get_new_fits(0, 15);
	$titlestr = 'New fits';
} else if($type == 'recentlyupdated') {
	$ids = \Osmium\AtomCommon\get_recently_updated_fits(0, 15);
	$titlestr = 'Updated recently';
} else {
	\Osmium\fatal(404, 'Unknown feed type');
}

$ids[] = 0; /* If $ids is empty, don't break the query */

$fpath = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
$fpath = explode('/', $fpath);
array_pop($fpath);
array_pop($fpath);
$fpath = implode('/', $fpath);

$proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https': 'http';
$host = $_SERVER['HTTP_HOST'];
$abs = rtrim($proto.'://'.$host.'/'.$fpath, '/');

header('Content-Type: application/atom+xml');

$idprefix = 'http://artefact2.com/osmium/atom/local/'.$_SERVER['HTTP_HOST'].$fpath;
$atom = new \DOMDocument();
$feed = $atom->appendChild($atom->createElement('feed'));

$xmlns = $feed->appendChild($atom->createAttribute('xmlns'));
$xmlns->appendChild($atom->createTextNode('http://www.w3.org/2005/Atom'));

$xmlnsosmium = $feed->appendChild($atom->createAttribute('xmlns:osmium'));
$xmlnsosmium->appendChild($atom->createTextNode('http://artefact2.com/osmium/atom/schema'));

$id = $feed->appendChild($atom->createElement('id'));
$id->appendChild($atom->createTextNode($idprefix.'/'.$type));

$title = $feed->appendChild($atom->createElement('title'));
$title->appendChild($atom->createTextNode($titlestr.' / Osmium'));

$link = $feed->appendChild($atom->createElement('link'));
$rel = $link->appendChild($atom->createAttribute('rel'));
$rel->appendChild($atom->createTextNode('self'));
$href = $link->appendChild($atom->createAttribute('href'));
$href->appendChild($atom->createTextNode($abs.$_SERVER['REQUEST_URI']));

$generator = $feed->appendChild($atom->createElement('generator'));
$generator->appendChild($atom->createTextNode('Osmium'));
$uri = $generator->appendChild($atom->createAttribute('uri'));
$uri->appendChild($atom->createTextNode('http://artefact2.com/osmium/'));
$version = $generator->appendChild($atom->createAttribute('version'));
$version->appendChild($atom->createTextNode(\Osmium\get_osmium_version()));

$q = \Osmium\Db\query('SELECT l.loadoutid, l.accountid, l.visibility,
a.nickname, a.charactername, a.characterid, a.apiverified,
lh.updatedate, f.name, f.hullid, it.typename AS hullname
FROM osmium.loadouts AS l
JOIN osmium.accounts AS a ON l.accountid = a.accountid
JOIN osmium.loadoutslatestrevision AS llr ON l.loadoutid = llr.loadoutid
JOIN osmium.loadouthistory AS lh ON l.loadoutid = lh.loadoutid AND llr.latestrevision = lh.revision
JOIN osmium.fittings AS f ON lh.fittinghash = f.fittinghash
JOIN eve.invtypes AS it ON f.hullid = it.typeid
WHERE l.loadoutid IN ('.implode(',', $ids).')
ORDER BY lh.updatedate DESC');
$first = false;
while($row = \Osmium\Db\fetch_assoc($q)) {
	$date = date('c', $row['updatedate']);

	if($first === false) {
		$first = true;

		$updated = $feed->appendChild($atom->createElement('updated'));
		$updated->appendChild($atom->createTextNode($date));
	}

	$entry = $feed->appendChild($atom->createElement('entry'));

	$id = $entry->appendChild($atom->createElement('id'));
	$id->appendChild($atom->createTextNode($idprefix.'/loadout/'.$row['loadoutid']));

	$title = $entry->appendChild($atom->createElement('title'));
	$title->appendChild($atom->createTextNode($row['name'].' ('.$row['hullname'].' loadout)'));

	$updated = $entry->appendChild($atom->createElement('updated'));
	$updated->appendChild($atom->createTextNode($date));

	$link = $entry->appendChild($atom->createElement('link'));
	$rel = $link->appendChild($atom->createAttribute('rel'));
	$rel->appendChild($atom->createTextNode('alternate'));
	$href = $link->appendChild($atom->createAttribute('href'));
	$href->appendChild($atom->createTextNode($abs.'/'.\Osmium\Fit\get_fit_uri($row['loadoutid'], $row['visibility'], '0')));

	$author = $entry->appendChild($atom->createElement('author'));
	$name = $author->appendChild($atom->createElement('name'));
	\Osmium\Chrome\format_character_name($row, '', $authorname);
	$name->appendChild($atom->createTextNode($authorname));
	$otype = $name->appendChild($atom->createAttribute('osmium:type'));
	$otype->appendChild($atom->createTextNode($row['apiverified'] === 't' ? 'charactername' : 'nickname'));
	$uri = $author->appendChild($atom->createElement('uri'));
	$uri->appendChild($atom->createTextNode($abs.'/profile/'.$row['accountid']));

	$oloadoutid = $entry->appendChild($atom->createElement('osmium:loadoutid'));
	$oloadoutid->appendChild($atom->createTextNode($row['loadoutid']));

	$ostypeid = $entry->appendChild($atom->createElement('osmium:shiptypeid'));
	$ostypeid->appendChild($atom->createTextNode($row['hullid']));
}

$atom->formatOutput = true;
$atom->encoding = 'UTF-8';
echo $atom->saveXML();
