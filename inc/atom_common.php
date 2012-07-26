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

namespace Osmium\AtomCommon;

function get_new_fits($accountid = 0, $limit = 5) {
	$ids = array();
	$query = \Osmium\Db\query_params('SELECT a.loadoutid FROM osmium.searchableloadouts AS a 
	JOIN osmium.loadoutslatestrevision AS llr ON a.loadoutid = llr.loadoutid
	JOIN osmium.loadouthistory AS lhf ON (a.loadoutid = lhf.loadoutid AND lhf.revision = 1)
	JOIN osmium.loadouthistory AS lh
		ON (lh.loadoutid = a.loadoutid AND lh.revision = llr.latestrevision)
	WHERE a.accountid IN (0, $2) AND (lhf.updatedate >= ($1 - 86400) OR lh.revision = 1)
	ORDER BY lh.updatedate DESC
	LIMIT $3', array(time(), $accountid, $limit));

	while($row = \Osmium\Db\fetch_row($query)) {
		$ids[] = $row[0];
	}

	return $ids;
}

function get_recently_updated_fits($accountid = 0, $limit = 5) {
	$ids = array();
	$query = \Osmium\Db\query_params('SELECT a.loadoutid FROM osmium.searchableloadouts AS a 
	JOIN osmium.loadoutslatestrevision AS llr ON a.loadoutid = llr.loadoutid
	JOIN osmium.loadouthistory AS lhf ON (a.loadoutid = lhf.loadoutid AND lhf.revision = 1)
	JOIN osmium.loadouthistory AS lh
		ON (lh.loadoutid = a.loadoutid AND lh.revision = llr.latestrevision)
	WHERE a.accountid IN (0, $2) AND (lhf.updatedate < ($1 - 86400) AND lh.revision > 1)
	ORDER BY lh.updatedate DESC
	LIMIT $3', array(time(), $accountid, $limit));

	while($row = \Osmium\Db\fetch_row($query)) {
		$ids[] = $row[0];
	}

	return $ids;
}
