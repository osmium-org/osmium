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

namespace Osmium\Page\ApiSettings;

require __DIR__.'/../inc/root.php';

if(!\Osmium\State\is_logged_in()) {
	osmium_fatal(403, "You are not logged in.");
}

$a = \Osmium\State\get_state('a');

if(isset($_POST['key_id'])) {
	$key_id = $_POST['key_id'];
	$v_code = $_POST['v_code'];

	$s = \Osmium\State\check_api_key_sanity($a['accountid'], $key_id, $v_code);

	if($s !== true) {
		\Osmium\Forms\add_field_error('key_id', $s);
	} else {
		\Osmium\Db\query_params('UPDATE osmium.accounts SET keyid = $1, verificationcode = $2, apiverified = true WHERE accountid = $3', array($key_id, $v_code, $a['accountid']));

		$a['apiverified'] = 't';
		$a['keyid'] = $key_id;
		$a['verificationcode'] = $v_code;

		\Osmium\State\put_state('a', $a);
		\Osmium\State\check_api_key($a, true);
		$a = \Osmium\State\get_state('a');
	}
} else {
	if(isset($a['keyid'])) {
		$_POST['key_id'] = $a['keyid'];
	}
}

\Osmium\Chrome\print_header('API settings', '.');

echo "<h1>Account status</h1>\n";
echo "<p>".($a['apiverified'] === 't' ? 'Your account is API-verified.'
            : 'Your account is <strong>not</strong> API-verified.')."</p>\n";

if($a['apiverified'] !== 't') {
	echo "<p>\nVerifying your account with an API key will allow you to:</p>\n";
	echo "<ul>\n";
	echo "<li>Share loadouts with your corporation/alliance, and access corporation/alliance-restricted loadouts;</li>\n";
	echo "<li>Have your character name used instead of your nickname.</li>\n";
	echo "</ul>\n";
}

echo "<h1>API credentials</h1>\n";

echo "<p>\nYou can create an API key here: <strong><a href='https://support.eveonline.com/api/Key/CreatePredefined/".\Osmium\State\REQUIRED_ACCESS_MASK."'>https://support.eveonline.com/api/Key/CreatePredefined/".\Osmium\State\REQUIRED_ACCESS_MASK."</a></strong><br />\n";
echo "<strong>Make sure that you only select one character, do not change any of the checkboxes on the right.</strong>\n</p>\n";

echo "<p>\nIf you are still having errors despite having updated your API key, you will have to wait for the cache to expire, or just create a whole new API key altogether (no waiting involved!).\n</p>\n";

\Osmium\Forms\print_form_begin();

\Osmium\Forms\print_generic_field('API Key ID', 'text', 'key_id', null, 
                                  \Osmium\Forms\FIELD_REMEMBER_VALUE);
\Osmium\Forms\print_generic_field('Verification Code', 'text', 'v_code', null, 
                                  \Osmium\Forms\FIELD_REMEMBER_VALUE);

\Osmium\Forms\print_submit();
\Osmium\Forms\print_form_end();

\Osmium\Chrome\print_footer();