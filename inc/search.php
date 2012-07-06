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

namespace Osmium\Search;

const SPHINXQL_PORT = 24492;

function get_link() {
	static $link = null;
	if($link === null) {
		$link = mysqli_connect('127.0.0.1:'.SPHINXQL_PORT);
		if(!$link) {
			\Osmium\fatal(500, 'Could not connect to Sphinx.');
		}
	}

	return $link;
}

function query_select_searchdata($cond, array $params = array()) {
	return \Osmium\Db\query_params('SELECT loadoutid, restrictedtoaccountid, restrictedtocorporationid, restrictedtoallianceid, tags, modules, author, name, description, shipid, ship, creationdate, updatedate FROM osmium.loadoutssearchdata '.$cond, $params);
}

function query($q) {
	return mysqli_query(get_link(), $q);
}

function escape($string) {
	/* Taken from the GPL PHP API of Sphinx */
	$from = array ('\\', '(',')','|','-','!','@','~',"'",'&', '/', '^', '$', '=');
	$to   = array ('\\\\', '\(','\)','\|','\-','\!','\@','\~','\\\'', '\&', '\/', '\^', '\$', '\=');
	return str_replace ($from, $to, $string);
}

function unindex($loadoutid) {
	query('DELETE FROM osmium_loadouts WHERE id = '.$loadoutid);
}

function index($loadout) {
	unindex($loadout['loadoutid']);
	
	return query('INSERT INTO osmium_loadouts 
  (id, restrictedtoaccountid, restrictedtocorporationid, restrictedtoallianceid, 
  shipid, creationdate, updatedate, ship, author, name, description, tags, modules) 
  VALUES ('
	             .$loadout['loadoutid'].','
	             .$loadout['restrictedtoaccountid'].','
	             .$loadout['restrictedtocorporationid'].','
	             .$loadout['restrictedtoallianceid'].','
	             .$loadout['shipid'].','
	             .$loadout['creationdate'].','
	             .$loadout['updatedate'].','
	             .'\''.escape($loadout['ship']).'\','
	             .'\''.escape($loadout['author']).'\','
	             .'\''.escape($loadout['name']).'\','
	             .'\''.escape($loadout['description']).'\','
	             .'\''.escape($loadout['tags']).'\','
	             .'\''.escape($loadout['modules']).'\''
	             .')');
}

function get_search_query($search_query) {
	$accountids = array(0);
	$corporationids = array(0);
	$allianceids = array(0);

	if(\Osmium\State\is_logged_in()) {
		$a = \Osmium\State\get_state('a');
		$accountids[] = intval($a['accountid']);

		if($a['apiverified'] === 't') {
			$corporationids[] = intval($a['corporationid']);
			if($a['allianceid'] > 0) $allianceids[] = intval($a['allianceid']);
		}
	}

	return 'SELECT id FROM osmium_loadouts WHERE MATCH(\''.escape($search_query).'\') AND restrictedtoaccountid IN ('.implode(',', $accountids).') AND restrictedtocorporationid IN ('.implode(',', $corporationids).') AND restrictedtoallianceid IN ('.implode(',', $allianceids).')';
}

function get_search_ids($search_query, $more_cond = '', $offset = 0, $limit = 1000) {
	$q = query(get_search_query($search_query).' '.$more_cond.' LIMIT '.$offset.','.$limit);
	if($q === false) return false; /* Invalid query */

	$ids = array();
	while($row = fetch_row($q)) {
		$ids[] = $row[0];
	}

	return $ids;
}

function get_total_matches($search_query, $more_cond = '') {
	$q = query(get_search_query($search_query).' '.$more_cond);
	if($q === false) return 0;

	$meta = get_meta();
	return $meta['total_found'];
}

function fetch_assoc($result) {
	return mysqli_fetch_assoc($result);
}

function fetch_row($result) {
	return mysqli_fetch_row($result);
}

function get_meta() {
	$q = query('SHOW META;');
	$meta = array();

	while($r = fetch_row($q)) {
		$meta[$r[0]] = $r[1];
	}

	return $meta;
}

function print_pretty_results($relative, $query, $more = '', $paginate = false, $perpage = 20, $pagename = 'p', $message = 'No loadouts matched your query.') {
	$total = get_total_matches($query, $more);
	if($paginate) {
		$offset = \Osmium\Chrome\paginate($pagename, $perpage, $total, $pageresult, $pageinfo);
	} else $offset = 0;

	$ids = \Osmium\Search\get_search_ids($query, $more, $offset, $perpage);
	if($ids === false) {
		echo "<p class='error_box no_search_result'>The supplied query is invalid.</p>\n";
		return;
	}

	if($paginate) {
		echo $pageinfo;
		echo $pageresult;
		print_loadout_list($ids, $relative, $offset, $message);
		echo $pageresult;
	} else {
		print_loadout_list($ids, $relative, $offset, $message);
	}
}

function print_loadout_list(array $ids, $relative, $offset = 0, $nothing_message = 'No loadouts.') {
	if($ids === array()) {
		echo "<p class='error_box no_search_result'>".$nothing_message."</p>\n";
		return;		
	}

	$orderby = implode(',', array_map(function($id) { return 'loadouts.loadoutid='.$id.' DESC'; }, $ids));
	$in = implode(',', $ids);
	$first = true;
    
	$lquery = \Osmium\Db\query('SELECT loadouts.loadoutid, latestrevision, viewpermission, visibility, hullid, typename, fittings.creationdate, updatedate, name, fittings.description, accounts.accountid, nickname, apiverified, charactername, characterid, corporationname, corporationid, alliancename, allianceid, loadouts.accountid, taglist
FROM osmium.loadouts 
JOIN osmium.loadoutslatestrevision ON loadouts.loadoutid = loadoutslatestrevision.loadoutid 
JOIN osmium.loadouthistory ON (loadoutslatestrevision.latestrevision = loadouthistory.revision AND loadouthistory.loadoutid = loadouts.loadoutid) 
JOIN osmium.fittings ON fittings.fittinghash = loadouthistory.fittinghash 
JOIN osmium.accounts ON accounts.accountid = loadouts.accountid 
JOIN eve.invtypes ON hullid = invtypes.typeid 
LEFT JOIN osmium.loadoutstaglist ON loadoutstaglist.loadoutid = loadouts.loadoutid
WHERE loadouts.loadoutid IN ('.$in.') ORDER BY '.$orderby);

	while($loadout = \Osmium\Db\fetch_assoc($lquery)) {
		if($first === true) {
			$first = false;
			/* Only write the <ol> tag if there is at least one loadout */
			echo "<ol start='".($offset + 1)."' class='loadout_sr'>\n";
		}
		echo "<li>\n";
		echo "<img src='http://image.eveonline.com/Render/".$loadout['hullid']."_64.png' alt='".$loadout['typename']."' />\n";
		echo "<a href='$relative/loadout/".$loadout['loadoutid']."'>";
		\Osmium\Chrome\print_loadout_title($loadout['name'], $loadout['viewpermission'], $loadout['visibility'], $loadout, $relative);
		echo "</a>\n<br />\n";
		echo "<small><a href='$relative/search?q=".urlencode('@ship "'.$loadout['typename'].'"')."'>".$loadout['typename']."</a> loadout";
		echo " — <a href='$relative/profile/".$loadout['accountid']."'>".\Osmium\Chrome\format_character_name($loadout, $relative)."</a>";
		echo " — revision #".$loadout['latestrevision'];
		echo " — ".date('Y-m-d', $loadout['updatedate'])."</small><br />\n";
      
		$tags = array_filter(explode(' ', $loadout['taglist']), function($tag) { return trim($tag) != ''; });
		if(count($tags) == 0) {
			echo "<em>(no tags)</em>";
		} else {
			echo "<ul class='tags'>\n".implode('', array_map(function($tag) use($relative) {
						$tag = trim($tag);
						return "<li><a href='$relative/search?q=".urlencode('@tags "'.$tag.'"')."'>$tag</a></li>\n";
					}, $tags))."</ul>\n";
		}
		echo "</li>\n";
	}

	if($first === false) {
		echo "</ol>\n";
	} else {
		echo "<p class='error_box no_search_result'>".$nothing_message."</p>\n";
	}
}