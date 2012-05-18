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

namespace Osmium\Page\RenewApi;

require __DIR__.'/../inc/root.php';

if(!\Osmium\State\is_logged_in()) {
  osmium_fatal(403, "You are not logged in.");
}

if(isset($_POST['key_id'])) {
  $key_id = $_POST['key_id'];
  $v_code = $_POST['v_code'];

  $api = \Osmium\EveApi\fetch('/account/APIKeyInfo.xml.aspx', array('keyID' => $key_id, 'vCode' => $v_code));

  if(isset($api->error) && !empty($api->error)) {
    \Osmium\Forms\add_field_error('key_id', (string)$api->error);
  } else if((string)$api->result->key["type"] !== 'Character') {
    \Osmium\Forms\add_field_error('key_id', 'Invalid key type. Make sure you only select the character '.$__osmium_state['a']['character_name'].'.');
  } else if((int)$api->result->key["accessMask"] !== 0) {
    \Osmium\Forms\add_field_error('key_id', 'Incorrect access mask. Please set it to zero (untick any boxes on the right on the API page).');
  } else if((int)$api->result->key->rowset->row['characterID'] != $__osmium_state['a']['character_id']) {
    \Osmium\Forms\add_field_error('key_id', 'Wrong character. Please select the character '.$__osmium_state['a']['character_name'].'.');
  } else {
    \Osmium\Db\query_params('UPDATE osmium.accounts SET key_id = $1, verification_code = $2 WHERE account_id = $3', array($key_id, $v_code, $__osmium_state['a']['account_id']));
    unset($__osmium_state['renew_api']);
    session_commit();
    header('Location: ./', true, 303);
    die();
  }
}

$__osmium_state_renew_api_ignore = 1; /* Don't redirect forever to this page. */
\Osmium\Chrome\print_header('Update API credentials', '.');

echo "<h1>Update API credentials</h1>\n";

if(isset($_GET['non_consensual']) && $_GET['non_consensual'] === '1') {
  echo "<p class='warning_box expired_api_message'>\nYou are seeing this page because the API key you entered at registration time has become invalid. It may have expired, or may have been deleted. To be able to log in again with this character (<strong>".$__osmium_state['a']['character_name']."</strong>), please enter a new API key in the form below.</p>\n";
}

\Osmium\Forms\print_form_begin();

\Osmium\Forms\print_text("<p>You can create an API key here:<br />
<strong><a href='https://support.eveonline.com/api/Key/CreatePredefined/0'>https://support.eveonline.com/api/Key/CreatePredefined/0</a></strong><br />
<strong>Make sure that you only select the character ".$__osmium_state['a']['character_name'].".</strong><br />
(Be sure not to tick any boxes on the right.)</p>");

\Osmium\Forms\print_generic_field('API Key ID', 'text', 'key_id', null, 
				  \Osmium\Forms\FIELD_REMEMBER_VALUE);
\Osmium\Forms\print_generic_field('Verification Code', 'text', 'v_code', null, 
				  \Osmium\Forms\FIELD_REMEMBER_VALUE);

\Osmium\Forms\print_submit();
\Osmium\Forms\print_form_end();

\Osmium\Chrome\print_footer();