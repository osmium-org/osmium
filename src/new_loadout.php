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

namespace Osmium\Page\NewLoadout;

require __DIR__.'/../inc/root.php';

const RELATIVE = '..';

function gen_new_loadout_token() {
	$tok = sha1(uniqid('Osmium_New_Loadout_', true));
	$tok = base64_encode(pack('H*', $tok));

	/* Remove the padding (useless) and use .: instead of +/ for
	 * URI-friendliness. */
	return str_replace(array('+', '/', '='), array('.', ':', ''), $tok);
}

if(!isset($_GET['token'])) {
	header('Location: ./new/'.gen_new_loadout_token());
	die();
}

\Osmium\Chrome\print_header('Create a new loadout', RELATIVE);

echo "<h1>Create a new loadout</h1>\n";

echo "<div id='nlattribs'>
<h2>Attributes</h2>
<div class='compact' id='computed_attributes'></div>
</div>\n";

echo "<div id='nlsources'>
<ul class='tabs'>
<li><a href='#search'>Search</a></li>
<li><a href='#browse'>Browse</a></li>
<li><a href='#shortlist'>Shortlist</a></li>
</ul>\n";

echo "<section id='search'>
<form method='get' action='?'>
<ul class='filters'></ul>
<div class='query'>
<div><input type='search' name='q' placeholder='Search modules, ships, implants…' /></div>
<input type='submit' value='Search' />
</div>
</form>
<ul class='results'></ul>
</section>\n";

echo "<section id='browse'>
<ul class='filters'></ul>
<p class='placeholder loading'>
Fetching the list of types<span>…</span>
</p>
</section>\n";

echo "<section id='shortlist'>
<ul class='filters'></ul>
<p class='placeholder loading'>
Fetching shortlist<span>…</span>
</p>
</section>\n";

echo "</div>\n";

echo "<div id='nlmain'>
<ul class='tabs'>
<li><a href='#modules'>Modules</a></li>
<li><a href='#charges'>Charges</a></li>
<li><a href='#drones'>Drones</a></li>
<li><a href='#implantsboosters'>Implants, boosters</a></li>
<li><a href='#presets'>Presets</a></li>
<li><a href='#metadata'>Metadata</a></li>
</ul>\n";

echo "</div>\n";

$meta = \Osmium\State\get_cache_memory('new_loadout_metagroups_json', null);
if($meta === null) {
	$meta = array();
	$metaq = \Osmium\Db\query('SELECT metagroupid, metagroupname
	FROM osmium.invmetagroups
	ORDER BY metagroupname ASC');

	while($r = \Osmium\Db\fetch_row($metaq)) {
		$meta[$r[0]] = $r[1];
	}
	$meta = json_encode($meta);
	\Osmium\State\put_cache_memory('new_loadout_metagroups_json', $meta);
}
echo "<script>
osmium_staticver = ".\Osmium\STATICVER.";
osmium_token = '".\Osmium\State\get_token()."';
osmium_metagroups = ".$meta.";
</script>\n";

\Osmium\Chrome\print_js_snippet('tabs');
\Osmium\Chrome\print_js_snippet('context_menu');
\Osmium\Chrome\print_js_snippet('new_loadout');
\Osmium\Chrome\print_footer();
