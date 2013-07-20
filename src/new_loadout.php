<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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
require __DIR__.'/../inc/ajax_common.php';

const RELATIVE = '..';

if(isset($_GET['import']) && $_GET['import'] === 'dna') {
    $dna = $_GET['dna'];
    $ckey = 'dnafit_'.$dna;

    $fit = \Osmium\State\get_cache_memory_fb($ckey, null);
    if($fit === null) {
	    $fit = \Osmium\Fit\try_parse_fit_from_shipdna($dna, 'New DNA-imported loadout', $errors);

	    if($fit === false) {
		    \Osmium\Fatal(400, "Nonsensical DNA string");
	    }

	    \Osmium\State\put_cache_memory_fb($ckey, $fit, 7200);
    }

    $tok = \Osmium\State\get_unique_new_loadout_token();
    \Osmium\State\put_new_loadout($tok, $fit);

    header('Location: ../'.$tok);
    die();
}

if(isset($_GET['edit']) && $_GET['edit'] && isset($_GET['loadoutid'])
   && \Osmium\State\is_logged_in() && $_GET['tok'] == \Osmium\State\get_token()) {
	$loadoutid = (int)$_GET['loadoutid'];
	$revision = isset($_GET['revision']) ? (int)$_GET['revision'] : null;

	if(!\Osmium\State\can_view_fit($loadoutid)) {
		\Osmium\Fatal(404, "Loadout not found");
	}
	if(!\Osmium\State\can_access_fit($loadoutid)) {
		\Osmium\Fatal(403, "Can't access loadout, password-protected?");
	}
	if(!\Osmium\State\can_edit_fit($loadoutid)) {
		\Osmium\Fatal(403, "Permission is required to edit this loadout");
	}

	$fit = \Osmium\Fit\get_fit($loadoutid, $revision);
	$tok = \Osmium\State\get_unique_new_loadout_token();
	\Osmium\State\put_new_loadout($tok, $fit);

	header('Location: ../new/'.$tok);
	die();
}

if(isset($_GET['fork']) && $_GET['fork'] && isset($_GET['loadoutid'])) {
	$loadoutid = (int)$_GET['loadoutid'];
	$revision = isset($_GET['revision']) ? (int)$_GET['revision'] : null;

	if(!\Osmium\State\can_view_fit($loadoutid)) {
		\Osmium\Fatal(404, "Loadout not found");
	}
	if(!\Osmium\State\can_access_fit($loadoutid)) {
		\Osmium\Fatal(403, "Can't access loadout, password-protected?");
	}

	$fit = \Osmium\Fit\get_fit($loadoutid, $revision);
	$fork = $fit; /* Since $fit is an array, this makes a copy */

	/* Make $fork look like a new loadout */
	unset($fork['metadata']['loadoutid']);
	unset($fork['metadata']['revision']);
	unset($fork['metadata']['accountid']);

	/* Make a few adjustments */
	$fork['metadata']['visibility'] = \Osmium\Fit\VISIBILITY_PRIVATE;
	if(preg_match(
		'%^(?<title>.+?) \(fork( (?<forknumber>[1-9][0-9]*))?\)$%D',
		$fork['metadata']['name'],
		$matches)
	) {
		$fork['metadata']['name'] = $matches['title'];
		$forknum = isset($matches['forknumber']) ? (int)$matches['forknumber'] : 1;
		$fork['metadata']['name'] .= ' (fork '.($forknum + 1).')';
	} else {
		$fork['metadata']['name'] .= ' (fork)';
	}

	if($fit['metadata']['visibility'] != \Osmium\Fit\VISIBILITY_PRIVATE) {
		$fork['metadata']['description'] = trim(
			"*This loadout is a fork of loadout [#".(int)$fit['metadata']['loadoutid']
			."](".\Osmium\get_ini_setting('relative_path').\Osmium\Fit\get_fit_uri(
				$fit['metadata']['loadoutid'],
				$fit['metadata']['visibility'],
				0 /* No need to risk showing the real private token here */
			)."?revision=".(int)$fit['metadata']['revision'].") (revision "
			.(int)$fit['metadata']['revision'].").*\n\n"
			.$fork['metadata']['description']
		);
	}

	$tok = \Osmium\State\get_unique_new_loadout_token();
	\Osmium\State\put_new_loadout($tok, $fork);

	header('Location: ../new/'.$tok);
	die();
}

if(!isset($_GET['token'])) {
	$tok = \Osmium\State\get_unique_new_loadout_token();

	\Osmium\Fit\create($fit);
	\Osmium\State\put_new_loadout($tok, $fit);

	header('Location: ./new/'.$tok);
	die();
} else {
	$tok = $_GET['token'];
	$fit = \Osmium\State\get_new_loadout($tok);

	if(!is_array($fit)) {
		/* Invalid token? */
		header('Location: ../new');
		die();
	}
}

if(isset($fit['metadata']['loadoutid']) && $fit['metadata']['loadoutid'] > 0) {
	$basetitle = 'Editing loadout #'.$fit['metadata']['loadoutid'];
	$title = "Editing loadout <a href='../".\Osmium\Fit\get_fit_uri(
		$fit['metadata']['loadoutid'],
		$fit['metadata']['visibility'],
		$fit['metadata']['privatetoken']
	)."'>#".$fit['metadata']['loadoutid']."</a>";
} else {
	$title = $basetitle = 'Creating a new loadout';
}

\Osmium\Chrome\print_header($basetitle, RELATIVE, false);

echo "<h1>".$title."</h1>\n";

echo "<div id='nlattribs'>
<section id='ship'></section>
<section id='control'>
<form method='GET' action='./".$tok."'>
<input type='button' name='reset_loadout' id='reset_loadout' value='Reset loadout' />\n";

if(\Osmium\State\is_logged_in()) {
	echo "<input type='button' name='submit_loadout' id='submit_loadout' value='Save loadout' />\n";
} else {
	echo "<input type='button' name='export_loadout' id='export_loadout' value='Export loadout' /><br />\n";
	echo "Export format: <select name='export_type' id='export_type'>\n";
	foreach(\Osmium\Fit\get_export_formats() as $k => $f) {
		echo "<option value='".htmlspecialchars($k, ENT_QUOTES)."'>".htmlspecialchars($f[0])."</option>\n";
	}
	echo "</select>\n";
}

echo "</form>
</section>
<section id='attributes'>
<div class='compact' id='computed_attributes'>
<p class='placeholder loading'>
Loading attributes<span>…</span>
</p>
</div>
</section>
</div>\n";

echo "<div id='nlsources'>
<ul class='tabs'>
<li><a href='#search'>Search</a></li>
<li><a href='#browse'>Browse</a></li>
<li><a href='#shortlist'>Shortlist</a></li>
</ul>\n";

$searchexamples = array(
	'dc2', '10mn ab', 'rf tp', 'large asb',
	'cn bcs', 'med cdfe', 'rcu 2', '1mn mwd',
	'fn web', 'c-type aif', 'eanm ii', 'pds',
	'mjd', 'rf disru', 'pwnage', 'tachyon ii',
	'sni', 'rni', 'sfi', 'pirate frig',
);
echo "<section id='search'>
<form method='get' action='?'>
<ul class='filters'></ul>
<div class='query'>
<div><input type='search' name='q' placeholder='Search items (ex: ".
htmlspecialchars($searchexamples[mt_rand(0, count($searchexamples) - 1)], ENT_QUOTES)
.")' title='Search items by name, by group, by abbreviation' /></div>
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
<li><a href='#modules'>Modules &amp; Charges</a></li>
<li><a href='#drones'>Drones</a></li>
<li><a href='#implants'>Implants &amp; Boosters</a></li>
<li><a href='#presets'>Presets</a></li>
<li><a href='#metadata'>Metadata</a></li>
</ul>\n";

echo "<section id='presets'>\n";
$presetactions = "<input type='button' class='createpreset' value='Create preset' />\n<input type='button' class='renamepreset' value='Rename preset' />\n<input type='button' class='clonepreset' value='Clone preset' />\n<input type='button' class='deletepreset' value='Delete preset' />";
\Osmium\Forms\print_form_begin();
\Osmium\Forms\print_generic_row('spreset', "<label for='spreset'>Preset</label>", "<select id='spreset' name='spreset'></select><br />\n".$presetactions."\n", 'rpresets');
\Osmium\Forms\print_textarea('Preset description', 'tpresetdesc');
\Osmium\Forms\print_separator();
\Osmium\Forms\print_generic_row('scpreset', "<label for='scpreset'>Charge preset</label>", "<select id='scpreset' name='scpreset'></select><br />\n".$presetactions."\n", 'rchargepresets');
\Osmium\Forms\print_textarea('Charge preset description', 'tcpresetdesc');
\Osmium\Forms\print_separator();
\Osmium\Forms\print_generic_row('sdpreset', "<label for='sdpreset'>Drone preset</label>", "<select id='sdpreset' name='sdpreset'></select><br />\n".$presetactions."\n", 'rdronepresets');
\Osmium\Forms\print_textarea('Drone preset description', 'tdpresetdesc');
\Osmium\Forms\print_form_end();
echo "</section>\n";

echo "<section id='metadata'>\n";
\Osmium\Forms\print_form_begin();
\Osmium\Forms\print_generic_field('Loadout title', 'text', 'name', 'name');
\Osmium\Forms\print_textarea('Description<br /><small>(optional)</small>', 'description', 'description');
\Osmium\Forms\print_generic_field('Tags (space-separated)<br /><small>(between '
                                  .(int)\Osmium\get_ini_setting('min_tags').' and '
                                  .(int)\Osmium\get_ini_setting('max_tags').')</small>',
                                  'text', 'tags', 'tags');
$commontags = array(
	/* General usage */
	'pve', 'pvp',
	'solo', 'fleet',
	'small-gang',

	/* Defense related */
	'shield-tank', 'armor-tank', 
	'buffer-tank', 'active-tank', 
	'passive-tank',

	/* Offense related */
	'gun-boat', 'missile-boat',
	'drone-boat', 'support',
	'short-range', 'long-range',

	/* ISK/SP */
	'cheap', 'expensive',
	'low-sp', 'high-sp',
);
\Osmium\Forms\print_generic_row(
	'common_tags', '',
	'Common tags:<ul class="tags">'
	.implode(
		' ',
		array_map(
			function($tag) { return '<li><a href="javascript:void(0);" title="Add this tag">'.$tag.'</a></li>'; },
			$commontags
		)
	).'</ul>',
	'common_tags'
);

$versions = \Osmium\Fit\get_eve_db_versions();
foreach($versions as &$v) {
	$v = $v['name']." (".$v['tag'].", build ".$v['build'].")";
}
\Osmium\Forms\print_select('Expansion<br /><small>(for experts only)</small>', 'evebuildnumber', $versions);

if(\Osmium\State\is_logged_in()) {
	\Osmium\Forms\print_separator();

	\Osmium\Forms\print_select(
		'Can be seen by', 'view_perms', 
		array(
			\Osmium\Fit\VIEW_EVERYONE => 'everyone',
			\Osmium\Fit\VIEW_PASSWORD_PROTECTED => 'everyone but require a password',
			\Osmium\Fit\VIEW_ALLIANCE_ONLY => 'my alliance only',
			\Osmium\Fit\VIEW_CORPORATION_ONLY => 'my corporation only',
			\Osmium\Fit\VIEW_OWNER_ONLY => 'only me',
			), null, 'view_perms');

	\Osmium\Forms\print_select(
		'Can be edited by', 'edit_perms', 
		array(
			\Osmium\Fit\EDIT_OWNER_ONLY => 'only me',
			\Osmium\Fit\EDIT_OWNER_AND_FITTING_MANAGER_ONLY => 'me and anyone in my corporation with the Fitting Manager role',
			\Osmium\Fit\EDIT_CORPORATION_ONLY => 'anyone in my corporation',
			\Osmium\Fit\EDIT_ALLIANCE_ONLY => 'anyone in my alliance',
			), null, 'edit_perms');

	\Osmium\Forms\print_select(
		'Visibility', 'visibility', 
		array(
			\Osmium\Fit\VISIBILITY_PUBLIC => 'public (will appear on the homepage and in search results)',
			\Osmium\Fit\VISIBILITY_PRIVATE => 'private (will not appear in search results)',
			), null, 'visibility');

	\Osmium\Forms\print_generic_field('Password', 'password', 'pw', 'pw');
}
\Osmium\Forms\print_form_end();
echo "</section>\n";

echo "<section id='modules'>\n";
$stypes = \Osmium\Fit\get_slottypes();
foreach($stypes as $type => $tdata) {
    if($type === "high" || $type === "medium") {
        $groupstatus = "grouped";
        $groupedcharges = "<small class='groupcharges'>Charges are grouped</small>";
    } else {
        $groupstatus = "ungrouped";
        $groupedcharges = "";
    }

	echo "<div class='slots $type $groupstatus'>\n<h3>".htmlspecialchars($tdata[0])
		." <span>$groupedcharges<small class='counts'></small></span></h3>\n";
	echo "<ul></ul>\n";
	echo "</div>\n";
}
echo "</section>\n";

echo "<section id='drones'>\n";
foreach(array('space' => 'In space', 'bay' => 'In bay') as $type => $fname) {
	echo "<div class='drones $type'>\n<h3>".htmlspecialchars($fname)." <span>";
	if($type === 'space') {
		echo "<small title='Maximum number of drones in space' class='maxdrones'></small>";
		echo "<small title='Drone bandwidth usage' class='bandwidth'></small>";
	} else if($type === 'bay') {
		echo "<small title='Drone bay usage' class='bayusage'></small>";
	}
	echo "</span></h3>\n<ul></ul>\n</div>\n";
}
echo "</section>\n";

echo "<section id='implants'>\n";
echo "<div class='implants'>\n<h3>Implants</h3>\n<ul></ul>\n</div>\n";
echo "<div class='boosters'>\n<h3>Boosters</h3>\n<ul></ul>\n</div>\n";
echo "</section>\n";

echo "</div>\n";

\Osmium\Chrome\print_loadout_common_footer($fit, RELATIVE, $tok);
\Osmium\Chrome\print_js_code(
	"osmium_shortlist = ".json_encode(\Osmium\AjaxCommon\get_module_shortlist()).";"
);

\Osmium\Chrome\print_js_snippet('new_loadout');
\Osmium\Chrome\print_js_snippet('new_loadout-control');
\Osmium\Chrome\print_js_snippet('new_loadout-sources');
\Osmium\Chrome\print_js_snippet('new_loadout-ship');
\Osmium\Chrome\print_js_snippet('new_loadout-presets');
\Osmium\Chrome\print_js_snippet('new_loadout-metadata');
\Osmium\Chrome\print_js_snippet('new_loadout-modules');
\Osmium\Chrome\print_js_snippet('new_loadout-drones');
\Osmium\Chrome\print_js_snippet('new_loadout-implants');
\Osmium\Chrome\print_footer();
