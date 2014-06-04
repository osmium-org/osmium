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

$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if($ref === '' || preg_match('%^https?://'.preg_quote($_SERVER['HTTP_HOST'], '%').'/?$%', $ref)) {
	/* No need to hide the referer; a 303 is faster and standard HTTP. */
	header('HTTP/1.1 303 See Other', true, 303);
	header('Location: '.$to, true, 303);
	die();
}

/* This method is non-standard, but widely supported accross
 * browsers. When doing a redirect using Refresh, some browsers will
 * blank the referrer (Firefox), while other will set it to the page
 * that contained the redirect (the /internal/redirect/ URI, which is
 * safe to leak). */
header('Refresh: 0; url='.$to);
$to = \Osmium\Chrome\escape($to);

echo "<!DOCTYPE html><html>";
echo "<head><title>Redirecting</title>";
echo "<meta http-equiv='refresh' content='0; url={$to}'>";
echo "<meta name='robots' content='noindex,nofollow'>";
echo "</head>";
echo "</html>";
