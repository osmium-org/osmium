<?php
/* Osmium
 * Copyright (C) 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\API\Convert;

define('Osmium\NO_CSRF_CHECK', true);
define('Osmium\NO_PRG', true);

require __DIR__.'/../../inc/root.php';
require \Osmium\ROOT.'/inc/api_common.php';

if(!isset($_GET['target_fmt'])) {
	\Osmium\fatal(400, "Must provide target_fmt.");
}

$tgt = $_GET['target_fmt'];
$available_export_formats = \Osmium\Fit\get_export_formats();

if(!isset($available_export_formats[$tgt])) {
	\Osmium\fatal(400, "Export format unavailable.");
}

$fit = \Osmium\API\get_fit_from_input_post_get();

list(, $ctype, $func) = $available_export_formats[$tgt];

\Osmium\API\outputp(
	$func($fit, $_GET), $ctype,
	null, $fit['metadata']['view_permission'] != \Osmium\Fit\VIEW_EVERYONE
);
