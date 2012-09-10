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

namespace Osmium\MarkdownStatic;

require __DIR__.'/../inc/root.php';

$f = __DIR__.'/md/'.$_GET['f'];

$data = $_GET;
$data['lastmodified'] = filemtime($f);
$data['this_lastmodified'] = filemtime(__FILE__);

$etag = sha1(json_encode($data));

header('ETag: '.$etag);
header('Cache-Control: public');
header('Pragma:');

if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
	header('HTTP/1.1 304 Not Modified', true, 304);
	die();
}

\Osmium\Chrome\print_header($data['title'], $data['relative']);

echo \Osmium\Chrome\format_md(file_get_contents($f));

\Osmium\Chrome\print_footer();