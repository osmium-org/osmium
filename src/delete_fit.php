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

namespace Osmium\Page\DeleteFit;

require __DIR__.'/../inc/root.php';

if(!\Osmium\State\is_logged_in() || $_GET['tok'] != \Osmium\State\get_token()) {
	\Osmium\fatal(403, "Forbidden.");
}

$loadoutid = isset($_GET['loadoutid']) ? $_GET['loadoutid'] : 0;

$can_edit = \Osmium\State\can_edit_fit($loadoutid);
if(!$can_edit) {
	\Osmium\fatal(403, "Forbidden.");
}

\Osmium\Db\query('BEGIN;');
\Osmium\Db\query_params('DELETE FROM osmium.accountfavorites WHERE loadoutid = $1', array($loadoutid));
\Osmium\Db\query_params('DELETE FROM osmium.loadouthistory WHERE loadoutid = $1', array($loadoutid));
\Osmium\Db\query_params('DELETE FROM osmium.loadouts WHERE loadoutid = $1', array($loadoutid));
\Osmium\Db\query('COMMIT;');

/* FIXME check that transaction was successful before unindexing this */
\Osmium\Search\unindex($loadoutid);

header('Location: ../');
die();
