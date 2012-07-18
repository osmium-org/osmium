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

namespace Osmium\Log;

const LOG_TYPE_CHANGED_FLAG_STATUS = 1;

const LOG_TYPE_CREATE_LOADOUT = 100;
const LOG_TYPE_UPDATE_LOADOUT = 101;
const LOG_TYPE_DELETE_LOADOUT = 102;
const LOG_TYPE_REVERT_LOADOUT = 110;

const LOG_TYPE_CREATE_COMMENT = 200;
const LOG_TYPE_UPDATE_COMMENT = 201;
const LOG_TYPE_DELETE_COMMENT = 202;

const LOG_TYPE_CREATE_COMMENT_REPLY = 300;
const LOG_TYPE_UPDATE_COMMENT_REPLY = 301;
const LOG_TYPE_DELETE_COMMENT_REPLY = 302;

function add_log_entry($type, $subtype, $target1 = null, $target2 = null, $target3 = null) {
	return \Osmium\Db\query_params(
		'INSERT INTO osmium.log (clientid, creationdate, type, subtype, target1, target2, target3) VALUES ($1, $2, $3, $4, $5, $6, $7)',
		array(\Osmium\State\get_client_id(),
		      time(),
		      $type,
		      $subtype,
		      $target1,
		      $target2,
		      $target3));
}
