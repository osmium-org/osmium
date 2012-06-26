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

namespace Osmium\Page\ViewLoadoutHistory;

require __DIR__.'/../inc/root.php';

$loadoutid = intval($_GET['loadoutid']);

if(!\Osmium\State\can_view_fit($loadoutid)) {
	\Osmium\fatal(404, 'Loadout not found.');
}
$fit = \Osmium\Fit\get_fit($loadoutid);
$can_edit = \Osmium\State\can_edit_fit($loadoutid);

if(!\Osmium\State\can_access_fit($fit)) {
	\Osmium\fatal(403, "Password-protected fit, please enter password on the view loadout page first.");
}

\Osmium\Chrome\print_header('Revision history of loadout #'.$loadoutid, '..', $fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE ? "<meta name='robots' content='noindex' />\n" : '');
echo "<h1>Revision history of loadout <a href='../loadout/$loadoutid'>#$loadoutid</a></h1>\n";

$histq = \Osmium\Db\query_params('SELECT loadouthistory.revision, loadouthistory.updatedate, delta, accounts.accountid, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator
FROM osmium.loadouthistory
LEFT JOIN osmium.loadouthistory AS previousrev ON loadouthistory.loadoutid = previousrev.loadoutid
                                               AND previousrev.revision = (loadouthistory.revision - 1)
LEFT JOIN osmium.fittingdeltas ON fittingdeltas.fittinghash1 = previousrev.fittinghash
                               AND fittingdeltas.fittinghash2 = loadouthistory.fittinghash
JOIN osmium.accounts ON accounts.accountid = loadouthistory.updatedbyaccountid
WHERE loadouthistory.loadoutid = $1
ORDER BY revision DESC', array($loadoutid));

echo "<ol id='lhistory'>\n";
$first = true;
while($rev = \Osmium\Db\fetch_assoc($histq)) {
	$class = $first ? 'opened' : 'closed';
	if($first) $first = false;

	echo "<li value='".$rev['revision']."' class='$class' id='revision".$rev['revision']."'>\n<p>";
	echo "<a href='../loadout/$loadoutid?revision=".$rev['revision']."'><strong>Revision #".$rev['revision']."</strong></a>";
	echo ", by ".\Osmium\Chrome\format_character_name($rev, '..');
	echo " (".date('Y-m-d', $rev['updatedate']).")";
	echo " <small class='anchor'><a href='#revision".$rev['revision']."'>#</a></small></p>\n";

	if($rev['revision'] > 1) {
		echo "<pre>";
		echo $rev['delta'] === null ? '(delta is not available, sorry)' : htmlspecialchars($rev['delta']);
		echo "</pre>\n";
	}

	echo "</li>\n";
}
echo "</ol>\n";

\Osmium\Chrome\print_js_snippet('loadout_history');
\Osmium\Chrome\print_footer();
