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

namespace Osmium\Page\NewLoadout;

require __DIR__.'/../inc/root.php';
require __DIR__.'/../inc/ajax-common.php';
require __DIR__.'/../inc/loadout-nv-common.php';



if(isset($_GET['import']) && $_GET['import'] === 'dna') {
    $dna = $_GET['dna'];
    $ckey = 'dnafit_'.$dna;

    $fit = \Osmium\State\get_cache_memory_fb($ckey, null);
    if($fit === null) {
	    $fit = \Osmium\Fit\try_parse_fit_from_shipdna($dna, 'New DNA-imported loadout', $errors);

	    if($fit === false) {
		    \Osmium\Fatal(400);
	    }

	    \Osmium\State\put_cache_memory_fb($ckey, $fit, 7200);
    }

    $tok = \Osmium\State\get_unique_loadout_token();
    \Osmium\Fit\use_default_skillset_for_account($fit);
    \Osmium\State\put_loadout($tok, $fit);

    header('Location: ../'.$tok);
    die();
}



if(isset($_GET['edit']) && $_GET['edit'] && isset($_GET['loadoutid'])
   && \Osmium\State\is_logged_in() && $_GET['tok'] == \Osmium\State\get_token()) {
	$loadoutid = (int)$_GET['loadoutid'];
	$revision = isset($_GET['revision']) ? (int)$_GET['revision'] : null;

	if(!\Osmium\State\can_view_fit($loadoutid)) {
		\Osmium\Fatal(404);
	}
	if(!\Osmium\State\can_edit_fit($loadoutid)) {
		\Osmium\Fatal(403);
	}

	$fit = \Osmium\Fit\get_fit($loadoutid, $revision);

	if(!\Osmium\State\can_access_fit($fit)) {
		\Osmium\Fatal(403);
	}

	$tok = \Osmium\State\get_unique_loadout_token();
    \Osmium\Fit\use_default_skillset_for_account($fit);
	\Osmium\State\put_loadout($tok, $fit);

	header('Location: ../new/'.$tok);
	die();
}



if(isset($_GET['fork']) && $_GET['fork'] && isset($_GET['loadoutid'])) {
	$loadoutid = (int)$_GET['loadoutid'];
	$revision = isset($_GET['revision']) ? (int)$_GET['revision'] : null;

	if(!\Osmium\State\can_view_fit($loadoutid)) {
		\Osmium\Fatal(404);
	}

	$fit = \Osmium\Fit\get_fit($loadoutid, $revision);

	if(!\Osmium\State\can_access_fit($fit)) {
		\Osmium\Fatal(403);
	}

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
		$matches
	)) {
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
			)."R".(int)$fit['metadata']['revision'].") (revision "
			.(int)$fit['metadata']['revision'].").*\n\n"
			.$fit['metadata']['description']
		);
	}

	if(isset($_GET['remote'])) {
		$key = $_GET['remote'];

		if($key !== 'local' && !isset($fit['remote'][$key])) {
			\Osmium\fatal(404);
		}

		\Osmium\Fit\set_local($fork, $key);
		/* XXX refactor this */
		$fork['metadata']['description'] = trim(
			"*This loadout is a fork of remote loadout #".$key
			." of loadout [#".(int)$fit['metadata']['loadoutid']
			."](".\Osmium\get_ini_setting('relative_path').\Osmium\Fit\get_fit_uri(
				$fit['metadata']['loadoutid'],
				$fit['metadata']['visibility'],
				0
			)."R".(int)$fit['metadata']['revision']."/remote/".urlencode($key).") (revision "
			.(int)$fit['metadata']['revision'].").*\n\n"
			.$fit['metadata']['description']
		);
	}

	if(isset($_GET['fleet'])) {
		$t = $_GET['fleet'];

		if(!isset($fit['fleet'][$t]) || !isset($fit['fleet'][$t]['ship']['typeid'])
		|| !$fit['fleet'][$t]['ship']['typeid']) {
			\Osmium\fatal(404);
		}

		$fork = $fit['fleet'][$t];
		/* XXX refactor this */
		$fork['metadata']['description'] = trim(
			"*This loadout is a fork of the {$t} booster of loadout [#".(int)$fit['metadata']['loadoutid']
			."](".\Osmium\get_ini_setting('relative_path').\Osmium\Fit\get_fit_uri(
				$fit['metadata']['loadoutid'],
				$fit['metadata']['visibility'],
				0
			)."R".(int)$fit['metadata']['revision']."/booster/{$t}) (revision "
			.(int)$fit['metadata']['revision'].").*\n\n"
			.$fit['metadata']['description']
		);
	}

	$tok = \Osmium\State\get_unique_loadout_token();
    \Osmium\Fit\use_default_skillset_for_account($fork);
	\Osmium\State\put_loadout($tok, $fork);

	header('Location: ../new/'.$tok);
	die();
}



if(!isset($_GET['token'])) {
	$tok = \Osmium\State\get_unique_loadout_token();

	\Osmium\Fit\create($fit);
	\Osmium\Fit\use_default_skillset_for_account($fit);
	\Osmium\State\put_loadout($tok, $fit);

	header('Location: ./new/'.$tok);
	die();
} else {
	$tok = $_GET['token'];
	$fit = \Osmium\State\get_loadout($tok);

	if(!is_array($fit)) {
		/* Invalid token? */
		header('Location: ../new');
		die();
	}
}



$p = new \Osmium\LoadoutCommon\Page();
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';
$p->index = false;

$p->head->appendCreate('link', [
	'href' => '//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/perfect-scrollbar.css',
	'rel' => 'stylesheet',
	'type' => 'text/css',
]);

if(isset($fit['metadata']['loadoutid']) && $fit['metadata']['loadoutid'] > 0) {
	$p->title = 'Editing loadout #'.$fit['metadata']['loadoutid'];
	$p->content
		->appendCreate('h1', 'Editing loadout ')
		->appendCreate('a', [
			'o-rel-href' => '/'.\Osmium\Fit\get_fit_uri(
				$fit['metadata']['loadoutid'],
				$fit['metadata']['visibility'],
				$fit['metadata']['privatetoken']
			),
			'#'.$fit['metadata']['loadoutid'],
		]);
} else {
	$p->title = 'Creating a new loadout';
	$p->content->appendCreate('h1', $p->title);
}

$nla = $p->content->appendCreate('div#nlattribs');
$nla->appendCreate('section#ship');

$section = $nla->appendCreate('section#control');
$form = $section->appendCreate('form', [ 'method' => 'get', 'action' => './'.$tok ]);
$tbody = $form->appendCreate('table')->appendCreate('tbody');

$tr = $tbody->appendCreate('tr');

$save = $tr->appendCreate('td')->appendCreate('input', [
	'type' => 'button',
	'name' => 'submit_loadout',
	'id' => 'submit_loadout',
	'value' => 'Save loadout',
]);

if(!\Osmium\State\is_logged_in()) {
	$save->setAttribute('disabled', 'disabled');
	$save->setAttribute('title', 'You need to sign in before you can save loadouts.');
	$save->addClass('force');
	$save->setAttribute('value', $save->getAttribute('value').' (requires account)');
}

if(isset($fit['metadata']['loadoutid']) && $fit['metadata']['loadoutid']) {
	$save->setAttribute('value', 'Update loadout');

	$tr->appendCreate('td')->appendCreate('input', [
		'id' => 'ureason',
		'name' => 'ureason',
		'placeholder' => 'Brief summary of your changes…',
		'type' => 'text',
	]);
} else {
	$save->parentNode->setAttribute('colspan', '2');
}

$tr = $tbody->appendCreate('tr');
$tr->appendCreate('td')->appendCreate('input', [
	'type' => 'button',
	'name' => 'export_loadout',
	'id' => 'export_loadout',
	'value' => 'Export loadout',
]);
$select = $tr->appendCreate('td')->appendCreate('select', [ 'name' => 'export_type', 'id' => 'export_type' ]);
foreach(\Osmium\Fit\get_export_formats() as $k => $f) {
	$select->appendCreate('option', [ 'value' => $k, $f[0] ]);
}



$nla->appendCreate('section#attributes')->appendCreate('div.compact#computed_attributes');



$nls = $p->content->appendCreate('div#nlsources');
$ul = $nls->appendCreate('ul.tabs');
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#search', 'Search' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#browse', 'Browse' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#shortlist', 'Shortlist' ]);

$searchexamples = array(
	/* Module synonyms */
	'dc2', '10mn ab', 'rf tp', 'large asb',
	'cn bcs', 'med cdfe', 'rcu 2', '1mn mwd',
	'fn web', 'c-type aif', 'eanm ii', 'pds',
	'mjd', 'rf disru', 'pwnage', 'tachyon ii',
	'425mm ac',

	/* Ship synonyms */
	'sni', 'rni', 'sfi', 'pirate frig',

	/* Meta level filter */
	'gyro @ml 4', 'web @ml 4', 'hml @ml 4', 'eanm @ml 1',

	/* Implants synonyms */
	'lg snake', 'crystal implant',
);

$section = $nls->appendCreate('section#search');
$form = $section->appendCreate('form', [ 'method' => 'get', 'action' => '?' ]);
$form->appendCreate('ul.filters');
$div = $form->appendCreate('div.query');
$div->appendCreate('div')->appendCreate('input', [
	'type' => 'search',
	'name' => 'q',
	'placeholder' => 'Example query: '.$searchexamples[mt_rand(0, count($searchexamples) - 1)],
	'title' => 'Search items by name, by group, by abbreviation',
]);
$div->appendCreate('input', [ 'type' => 'submit', 'value' => 'Search' ]);
$section->appendCreate('ul.results');

$section = $nls->appendCreate('section#browse');
$section->appendCreate('ul.filters');

$section = $nls->appendCreate('section#shortlist');
$section->appendCreate('ul.filters');



$nlm = $p->content->appendCreate('div#nlmain');
$ul = $nlm->appendCreate('ul.tabs');
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#modules', 'Modules & Charges' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#drones', 'Drones' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#implants', 'Implants & Boosters' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#remote', 'Fleet & Projected' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#presets', 'Presets' ]);
$ul->appendCreate('li')->appendCreate('a', [ 'href' => '#metadata', 'Metadata' ]);

$section = $nlm->appendCreate('section#modules');
foreach(\Osmium\Fit\get_slottypes() as $type => $tdata) {
	$div = $section->appendCreate('div.slots.'.$type);
	$h3 = $div->appendCreate('h3', $tdata[0]);
	$div->appendCreate('ul');

	$span = $h3->appendCreate('span');
	if($type === 'high' || $type === 'medium') {
		$div->addClass('grouped');
		$span->appendcreate('small.groupcharges', [
			'title' => 'Charges are grouped',
		]);
	} else {
		$div->addClass('ungrouped');
	}
	$span->appendCreate('small.counts');
}

$section = $nlm->appendCreate('section#drones');
foreach(array('space' => 'In space', 'bay' => 'In bay') as $type => $fname) {
	$div = $section->appendCreate('div.drones.'.$type);
	$span = $div->appendCreate('h3', $fname)->appendCreate('span');

	if($type === 'space') {
		$span->appendCreate('small.maxdrones', [ 'title' => 'Maximum number of drones in space' ]);
		$span->appendCreate('small', ' — ');
		$span->appendCreate('small.bandwidth', [ 'title' => 'Drone bandwidth usage' ]);
	} else if($type === 'bay') {
		$span->appendCreate('small.bayusage', [ 'title' => 'Drone bay usage' ]);
	}

	$div->appendCreate('ul');
}

$section = $nlm->appendCreate('section#implants');
$section->appendCreate('div.implants')->append([ [ 'h3', 'Implants' ], [ 'ul' ] ]);
$section->appendCreate('div.boosters')->append([ [ 'h3', 'Boosters' ], [ 'ul' ] ]);

$nlm->append($p->makeRemoteSection($fit));

$section = $nlm->appendCreate('section#presets');
$tbody = $section
	->appendCreate('form', [ 'method' => 'get', 'action' => '?' ])
	->appendCreate('table')
	->appendCreate('tbody');
$tpltd = $p->element('td', [
	[ 'select' ],
	[ 'br' ],
	[ 'input.createpreset', [ 'type' => 'button', 'value' => 'Create preset' ] ],
	' ',
	[ 'input.renamepreset', [ 'type' => 'button', 'value' => 'Rename preset' ] ],
	' ',
	[ 'input.clonepreset', [ 'type' => 'button', 'value' => 'Clone preset' ] ],
	' ',
	[ 'input.deletepreset', [ 'type' => 'button', 'value' => 'Delete preset' ] ],
]);

foreach([ '' => 'Preset', 'c' => 'Charge preset', 'd' => 'Drone preset' ] as $k => $n) {
	if($k !== '') $tbody->append($p->makeFormSeparatorRow());

	$td = $tpltd->cloneNode(true);
	$select = $td->firstChild;
	$select->setAttribute('id', $id = 's'.$k.'preset');
	$select->setAttribute('name', $id);

	$tbody->appendCreate('tr#r'.$k.'presets')->append([
		[ 'th', [[ 'label', [ 'for' => $id, $n ] ]] ],
		$td,
	]);

	$id = 't'.$k.'presetdesc';
	$tbody->appendCreate('tr')->append([
		[ 'th', [[ 'label', [ 'for' => $id, $n.' description' ] ]] ],
		[ 'td', [[ 'textarea', [ 'name' => $id, 'id' => $id ] ]] ],
	]);
}

$section = $nlm->appendCreate('section#metadata');
$tbody = $section->appendCreate('form', [ 'method' => 'get', 'action' => '?' ])
	->appendCreate('table')->appendCreate('tbody');

$tbody->append($p->makeFormInputRow('text', 'name', 'Loadout title'));

$tbody->append($p->makeFormRawRow(
	[[ 'label', [
		'for' => 'textarea',
		'Description',
		[ 'br' ],
		[ 'small', '(Markdown; optional)' ],
	] ]],
	[[ 'textarea', [
		'name' => 'description',
		'id' => 'description',
	] ]]
));
$tbody->append($p->makeFormInputRow('text', 'tags', [
	'Tags',
	[ 'br' ],
	[ 'small', '(space-separated, '.\Osmium\get_ini_setting('min_tags')
	  .'-'.\Osmium\get_ini_setting('max_tags').')' ],
]));

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
$ul = $p->element('ul.tags');
foreach($commontags as $tag) {
	$ul->appendCreate('li')->appendCreate('a', [ 'title' => 'add tag '.$tag, $tag ]);
}
$tbody->appendCreate('tr#common_tags')->append([
	[ 'th' ],
	[ 'td', [ 'Common tags:', $ul ] ],
]);

$select = $p->element('select#evebuildnumber', [ 'name' => 'evebuildnumber' ]);
foreach(\Osmium\Fit\get_eve_db_versions() as $k => $v) {
	$select->appendCreate('option', [
		'value' => $k,
		$v['name'].' ('.$v['tag'].'; build '.$p->formatExactInteger($v['build']).')'
	]);
}
$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'evebuildnumber', 'Expansion', [ 'br' ], [ 'small', '(for experts)' ] ] ]] ],
	[ 'td', $select ],
]);

if(\Osmium\State\is_logged_in()) {
	$tbody->append($p->makeFormSeparatorRow());

	$select = $p->element('select#view_perms', [ 'name' => 'view_perms' ]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VIEW_EVERYONE,
		'everyone',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VIEW_GOOD_STANDING,
		'my alliance mates and my contacts with good standing (≥0.01)',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VIEW_EXCELLENT_STANDING,
		'my alliance mates and my contacts with excellent standing (≥5.01)',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VIEW_ALLIANCE_ONLY,
		'my alliance mates',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VIEW_CORPORATION_ONLY,
		'my corporation mates',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VIEW_OWNER_ONLY,
		'only me',
	]);
	$tbody->append($p->makeFormRawRow(
		[[ 'label', [ 'for' => 'view_perms', 'Can be seen by' ] ]],
		$select
	));

	$select = $p->element('select#edit_perms', [ 'name' => 'edit_perms' ]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\EDIT_OWNER_ONLY,
		'only me',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\EDIT_OWNER_AND_FITTING_MANAGER_ONLY,
		'me and fitting managers in my corporation',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\EDIT_CORPORATION_ONLY,
		'me and my corporation mates',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\EDIT_ALLIANCE_ONLY,
		'me and my alliance mates',
	]);
	$tbody->append($p->makeFormRawRow(
		[[ 'label', [ 'for' => 'edit_perms', 'Can be edited by' ] ]],
		$select
	));

	$tbody->append($p->makeFormSeparatorRow());

	$select = $p->element('select#visibility', [ 'name' => 'visibility' ]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VISIBILITY_PUBLIC,
		'public (will appear on the homepage and in search results)',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\VISIBILITY_PRIVATE,
		'private (will have an obfuscated URI and only appear in your search results)',
	]);
	$tbody->append($p->makeFormRawRow(
		[[ 'label', [ 'for' => 'visibility', 'Visibility' ] ]],
		$select
	));

	$select = $p->element('select#password_mode', [ 'name' => 'password_mode' ]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\PASSWORD_NONE,
		'none (not password-protected)',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\PASSWORD_FOREIGN_ONLY,
		'foreign only (require password only for viewers not satisfying the view permission)',
	]);
	$select->appendCreate('option', [
		'value' => \Osmium\Fit\PASSWORD_EVERYONE,
		'everyone (require password for everyone but the owner)',
	]);
	$tbody->append($p->makeFormRawRow(
		[[ 'label', [ 'for' => 'password_mode', 'Password mode' ] ]],
		$select
	));

	$tbody->append($p->makeFormInputRow('password', 'pw', 'Password'));
}


$loverlay = $p->content->appendCreate('div#loadingoverlay');
$loverlay = $loverlay->appendCreate('div');
$lh = $loverlay->appendCreate('h1', 'Osmium is initializing the awesome');
$ls = $lh->appendcreate('span.loading');
for($i = 0; $i < 6; ++$i) {
	$ls->appendCreate('span.p'.$i, '.');
}
$loverlay->appendCreate('p.error_box#needjs', 'Not loading? Try enabling Javascript.');


$p->snippets = array_merge($p->snippets, [
	'new_loadout',
	'new_loadout-control',
	'new_loadout-sources',
	'new_loadout-ship',
	'new_loadout-presets',
	'new_loadout-metadata',
	'new_loadout-modules',
	'new_loadout-drones',
	'new_loadout-implants',
	'new_loadout-remote',
]);
$p->data['shortlist'] = \Osmium\AjaxCommon\get_module_shortlist();

$p->finalizeWithFit($ctx, $fit, $tok);
$p->body->appendCreate('script', [
	'type' => 'application/javascript',
	'src' => '//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/jquery.perfect-scrollbar-with-mousewheel.min.js',
]);

$p->render($ctx);
