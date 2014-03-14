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

namespace Osmium\MarkdownStatic;

require __DIR__.'/../inc/root.php';

$f = __DIR__.'/md/'.$_GET['f'];
$data = $_GET;

$allowed = realpath(__DIR__.'/md/');
$f = realpath($f);

if(strpos($f, $allowed) !== 0) {
	\Osmium\fatal(404);
}

$mdxml = \Osmium\State\get_cache('mdstatic_'.$f, null);
if($mdxml === null) {
	$mdxml = \Osmium\Chrome\format_md(file_get_contents($f));
	\Osmium\State\put_cache('mdstatic_'.$f, $mdxml);
}

$p = new \Osmium\DOM\Page();
$p->content->appendCreate('div', [ 'id' => 'mdstatic' ])->append($p->fragment($mdxml));
$p->title = $data['title'];
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = $data['relative'];
$p->render($ctx);
