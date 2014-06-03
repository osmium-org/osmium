<?php
/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\Redirect;

require __DIR__.'/../inc/root.php';

$to = urldecode($_SERVER['QUERY_STRING']);
$hash = $_GET['hash'];

if($hash !== ($foo = hash_hmac('sha256', $to, \Osmium\get_ini_setting('uri_munge_secret')))) {
	\Osmium\fatal(400);
}

if(!preg_match('%^((http|ftp)s?:)?//%', $to)) {
	\Osmium\fatal(400);
}

header('HTTP/1.1 303 See Other', true, 303);
header('Location: '.$to, true, 303);
