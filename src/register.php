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

require __DIR__.'/../inc/root.php';

if(osmium_logged_in()) {
  osmium_fatal(403, "You are already logged in.");
}

if(isset($_POST['account_name'])) {
  $q = osmium_pg_query_params('SELECT COUNT(account_id) FROM osmium.accounts WHERE account_name = $1', array($_POST['account_name']));
  list($q) = pg_fetch_row($q);

  $pw = $_POST['password_0'];
  $pw1 = $_POST['password_1'];

  if($q !== '0') {
    osmium_add_field_error('account_name', 'Sorry, this account name is already taken.');
  } else if(!preg_match('%[a-zA-Z]%', $pw) || !preg_match('%[0-9]%', $pw) || mb_strlen($pw) < 5) {
    osmium_add_field_error('password_0', 'Your password must be at least 5 characters long, and contain at least one letter (a-z, A-Z) and one number (0-9).');
  } else if($pw !== $pw1) {
    osmium_add_field_error('password_1', 'The two password are not equal.');
  } else {
    $key_id = $_POST['key_id'];
    $v_code = $_POST['v_code'];
    $api = osmium_api('/account/APIKeyInfo.xml.aspx', array('keyID' => $key_id, 'vCode' => $v_code));

    if(isset($api->error) && !empty($api->error)) {
      osmium_add_field_error('key_id', (string)$api->error);
    } else if((string)$api->result->key["type"] !== 'Character') {
      osmium_add_field_error('key_id', 'Invalid key type. Make sure you only select one character (instead of "All").');
    } else if((int)$api->result->key["accessMask"] !== 0) {
      osmium_add_field_error('key_id', 'Incorrect access mask. Please set it to zero (untick any boxes on the right on the API page).');
    } else {
      $character_id = (int)$api->result->key->rowset->row["characterID"];
      
      $char_info = osmium_api('/eve/CharacterInfo.xml.aspx', array('characterID' => $character_id));

      $character_name = (string)$char_info->result->characterName;
      $corporation_id = (int)$char_info->result->corporationID;
      $corporation_name = (string)$char_info->result->corporation;
      $alliance_id = (int)$char_info->result->allianceID;
      $alliance_name = (string)$char_info->result->alliance;

      if($alliance_id == 0) $alliance_id = null;
      if($alliance_name == '') $alliance_name = null;

      require_once OSMIUM_ROOT.'/lib/PasswordHash.php';
      $pwHash = new PasswordHash(10, false);
      $hash = $pwHash->HashPassword($pw);

      osmium_pg_query_params('INSERT INTO osmium.accounts (account_name, password_hash, key_id, verification_code, creation_date, last_login_date, character_id, character_name, corporation_id, corporation_name, alliance_id, alliance_name) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)', array($_POST['account_name'], $hash, $key_id, $v_code, $t = time(), $t, $character_id, $character_name, $corporation_id, $corporation_name, $alliance_id, $alliance_name));

      $_POST = array();

      /* TODO: login & redirect to the main page */
    }
  }
}

osmium_header('Account creation', '.');

echo "<h1>Account creation</h1>\n";

osmium_form_begin();
osmium_generic_field('Account name', 'text', 'account_name', null, OSMIUM_FIELD_REMEMBER_VALUE);
osmium_generic_field('Password', 'password', 'password_0', null, OSMIUM_FIELD_REMEMBER_VALUE);
osmium_generic_field('Password (repeat)', 'password', 'password_1', null, OSMIUM_FIELD_REMEMBER_VALUE);

osmium_separator();

osmium_text("<p>You can create an API key here:<br />
<strong><a href='https://support.eveonline.com/api/Key/CreatePredefined/0'>https://support.eveonline.com/api/Key/CreatePredefined/0</a></strong><br />
<strong>Make sure you only select one character.</strong><br />
(Be sure not to tick any boxes on the right.)</p>");

osmium_generic_field('API Key ID', 'text', 'key_id', null, OSMIUM_FIELD_REMEMBER_VALUE);
osmium_generic_field('Verification Code', 'text', 'v_code', null, OSMIUM_FIELD_REMEMBER_VALUE);

osmium_separator();

osmium_submit();
osmium_form_end();

osmium_footer();