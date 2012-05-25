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

namespace Osmium\Page\EditFit;

require __DIR__.'/../inc/root.php';

if(!\Osmium\State\is_logged_in() || $_GET['tok'] != \Osmium\State\get_token()) {
  \Osmium\fatal(403, "Forbidden.");
}

$a = \Osmium\State\get_state('a');

list($c) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.editableloadoutsbyaccount WHERE loadoutid = $1 AND accountid = $2', array($_GET['loadoutid'], $a['accountid'])));
if($c != 1) {
  \Osmium\fatal(403, "Forbidden.");
}

$fit = \Osmium\Fit\get_fit(intval($_GET['loadoutid']));
\Osmium\State\put_state('new_fit', $fit);
\Osmium\State\put_state('create_fit_step', 2); /* Just a hunch, step 5 or even 1 may be better. */

header('Location: ../new');
die();
