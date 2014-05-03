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

namespace Osmium\Page\About;

require __DIR__.'/../inc/root.php';

$aboutxml = \Osmium\State\get_cache('about', null);
$p = new \Osmium\DOM\Page();

if($aboutxml !== null) goto RenderStage;

$div = $p->element('div', [ 'id' => 'mdstatic' ]);

$div->appendCreate('h1', 'About Osmium');
$div->append($p->fragment(\Osmium\Chrome\format_md(
	file_get_contents(__DIR__.'/md/about.md')
)));

$div->appendCreate('h2', [ 'id' => 'contact', 'Contact' ]);
$div->append($p->fragment(\Osmium\Chrome\format_md(
	\Osmium\get_ini_setting('contact')
)));

$div->appendCreate('h2', 'Get the source code');
$div->append($p->fragment(\Osmium\Chrome\format_md(
	"The full source code of this instance of Osmium "
	."should be available at <".\Osmium\get_ini_setting('source').">. "
	."If you believe this is not the case, please contact "
	."the administrators about a possible AGPL violation."
)));

$div->append($p->fragment(\Osmium\Chrome\format_md(
	file_get_contents(__DIR__.'/md/about-disclaimers.md')
)));



$div->appendCreate('h2', 'Javascript license information');
$table = $div->appendCreate('table', [ 'class' => 'd', 'id' => 'jslicense-labels1' ]);
$table->appendCreate('thead')->appendCreate('tr')->append([
	[ 'th', 'Script name' ],
	[ 'th', 'License' ],
	[ 'th', 'Non-minified source' ],
]);
$tbody = $table->appendCreate('tbody');

$js = [
	[
		'jquery.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.min.js',
		'MIT',
		'https://raw.github.com/jquery/jquery/master/MIT-LICENSE.txt',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.js',
	],
	[
		'jquery-ui.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js',
		'MIT',
		'https://raw.github.com/jquery/jquery-ui/master/MIT-LICENSE.txt',
		'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/jquery-ui.js',
	],
	[
		'perfect-scrollbar-with-mousewheel.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/jquery.perfect-scrollbar-with-mousewheel.min.js',
		'MIT',
		'http://www.yuiazu.net/perfect-scrollbar/',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/jquery.perfect-scrollbar-with-mousewheel.js',
	],
	[
		'jquery.jsPlumb-1.6.0.min.js',
		'./static/jquery.jsPlumb-1.6.0.min.js',
		'MIT',
		'https://github.com/sporritt/jsPlumb/blob/1.6.0/jsPlumb-MIT-LICENSE.txt',
		'https://raw.github.com/sporritt/jsPlumb/1.6.0/dist/js/jquery.jsPlumb-1.6.0.js',
	],
	[
		'rawdeflate.min.js (rawdeflate)',
		'./static-1/rawdeflate.min.js',
		'GNU-GPL-2.0-only',
		'http://opensource.org/licenses/GPL-2.0',
		'https://raw.github.com/dankogai/js-deflate/1cc649243c7e0ada065b880180bdccce3c2dbcc2/rawdeflate.js',
	],
	[
		'rawdeflate.min.js (base64)',
		'./static-1/rawdeflate.min.js',
		'MIT',
		'http://opensource.org/licenses/mit-license',
		'https://raw.github.com/dankogai/js-deflate/1cc649243c7e0ada065b880180bdccce3c2dbcc2/test/base64.js',
	],
	[
		'mousetrap.min.js',
		'./static-1/mousetrap.min.js',
		'Apache-2.0',
		'http://www.apache.org/licenses/LICENSE-2.0',
		'https://raw.githubusercontent.com/ccampbell/mousetrap/1.4.6/mousetrap.js',
	],
];

chdir(__DIR__.'/../static/cache');
foreach(glob('JS_*.min.js') as $min) {
	$full = substr($min, 0, -strlen('.min.js')).".js";

	$js[] = [
		$min,
		'./static-'.\Osmium\JS_STATICVER.'/cache/'.$min,
		'GNU-AGPL-3.0-or-later',
		'./static/copying.txt',
		'./static-'.\Osmium\JS_STATICVER.'/cache/'.$full,
	];
}



$rowtpl = $p->element('tr');
$rowtpl->appendCreate('td')->appendCreate('a')->appendCreate('code');
$rowtpl->appendCreate('td')->appendCreate('a');
$rowtpl->appendCreate('td')->appendCreate('a')->appendCreate('code');

foreach($js as $d) {
	list($min, $minuri, $license, $licenseuri, $fulluri) = $d;
	$row = $rowtpl->cloneNode(true);

	$a = $row->firstChild->firstChild;
	$a->setAttribute('href', $minuri);
	$a->firstChild->append($min);

	$a = $row->childNodes->item(1)->firstChild;
	$a->setAttribute('href', $licenseuri);
	$a->append($license);

	$a = $row->lastChild->firstChild;
	$a->setAttribute('href', $fulluri);
	$a->firstChild->append($fulluri);

	$tbody->append($row);
}

$aboutxml = $div->renderNode();
\Osmium\State\put_cache('about', $aboutxml, 600);



RenderStage:
$ctx = new \Osmium\DOM\RenderContext();
$p->title = 'About Osmium';
$ctx->relative = '.';
$p->content->append($p->fragment($aboutxml));
$p->render($ctx);
