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

$__osmium_state =& $_SESSION['__osmium_state'];
$__osmium_login_state = array();

const OSMIUM_COOKIE_AUTH_DURATION = 604800; /* 7 days */

function osmium_logged_in() {
  global $__osmium_state;
  return isset($__osmium_state['a']['character_id']) && $__osmium_state['a']['character_id'] > 0;
}

function osmium_post_login($account_name, $use_cookie = false) {
  global $__osmium_state;
  $__osmium_state = array();

  osmium_pg_query_params('UPDATE osmium.accounts SET last_login_date = $1 WHERE account_name = $2', array(time(), $account_name));

  $q = osmium_pg_query_params('SELECT account_id, account_name, key_id, verification_code, creation_date, last_login_date, character_id, character_name, corporation_id, corporation_name, alliance_id, alliance_name FROM osmium.accounts WHERE account_name = $1', array($account_name));
  $__osmium_state['a'] = pg_fetch_assoc($q);

  if($use_cookie) {
    $token = uniqid('Osmium_', true);
    $account_id = $__osmium_state['a']['account_id'];
    $attributes = osmium_get_client_attributes();
    $expiration_date = time() + OSMIUM_COOKIE_AUTH_DURATION;

    osmium_pg_query_params('INSERT INTO osmium.cookie_tokens (token, account_id, client_attributes, expiration_date) VALUES ($1, $2, $3, $4)', array($token, $account_id, $attributes, $expiration_date));

    setcookie('Osmium', $token, $expiration_date, '/', $_SERVER['HTTP_HOST'], false, true);
  }

  $__osmium_state['logout_token'] = uniqid('Logout_', true);

  osmium_check_api_key();
}

function osmium_logoff($global = false) {
  global $__osmium_state;
  if($global && !osmium_logged_in()) return;

  if($global) {
    $account_id = $__osmium_state['a']['account_id'];
    osmium_pg_query_params('DELETE FROM osmium.cookie_tokens WHERE account_id = $1', array($account_id));
  }

  setcookie('Osmium', false, 42, '/', $_SERVER['HTTP_HOST'], false, true);
  $__osmium_state = array();
}

function osmium_get_client_attributes() {
  return hash('sha256', serialize(array($_SERVER['REMOTE_ADDR'],
					$_SERVER['HTTP_USER_AGENT'],
					$_SERVER['HTTP_ACCEPT'],
					$_SERVER['HTTP_HOST']
					)));
}

function osmium_check_client_attributes($attributes) {
  return $attributes === osmium_get_client_attributes();
}

function osmium_statebox($relative) {
  if(osmium_logged_in()) {
    return osmium_logoff_box($relative);
  } else {
    return osmium_login_box($relative);
  }
}

function osmium_login_box($relative) {
  $value = isset($_POST['account_name']) ? "value='".htmlspecialchars($_POST['account_name'], ENT_QUOTES)."' " : '';
  $remember = (isset($_POST['account_name']) && (!isset($_POST['remember']) || $_POST['remember'] !== 'on')) ? '' : "checked='checked' ";
  
  global $__osmium_login_state;
  if(isset($__osmium_login_state['error'])) {
    $error = "<p class='login_error'>".$__osmium_login_state['error']."</p>\n";
  } else $error = '';

  echo "<form method='post' action='".$_SERVER['REQUEST_URI']."' />\n<p id='login_box'>\n$error<input type='text' name='account_name' placeholder='Account name' $value/>\n<input type='password' name='password' placeholder='Password' />\n<input type='submit' name='__osmium_login' value='Login' /> or <a href='$relative/register'>create an account</a><br />\n<input type='checkbox' name='remember' id='remember' $remember/> <label for='remember'>Remember me on this computer</label>\n</p>\n</form>\n";
}

function osmium_logoff_box($relative) {
  global $__osmium_state;
  $name = $__osmium_state['a']['character_name'];
  $id = $__osmium_state['a']['character_id'];
  $tok = urlencode($__osmium_state['logout_token']);

  echo "<p id='logout_box'>\nLogged in as <img src='http://image.eveonline.com/Character/${id}_32.jpg' alt='' /> <strong>$name</strong>. <a href='$relative/logout?tok=$tok'>Logout</a> (<a href='$relative/logout?tok=$tok'>this session</a> / <a href='$relative/logout?tok=$tok&amp;global=1'>all sessions</a>)</p>\n";
}

function osmium_try_login() {
  if(osmium_logged_in()) return;

  $account_name = $_POST['account_name'];
  $pw = $_POST['password'];
  $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

  require_once OSMIUM_ROOT.'/lib/PasswordHash.php';
  $pwHash = new PasswordHash(10, true);
  
  list($hash) = pg_fetch_row(osmium_pg_query_params('SELECT password_hash FROM osmium.accounts WHERE account_name = $1', array($account_name)));

  if($pwHash->CheckPassword($pw, $hash)) {
    osmium_post_login($account_name, $remember);
  } else {
    global $__osmium_login_state;
    $__osmium_login_state['error'] = 'Invalid credentials. Please check your account name and password.';
  }
}

function osmium_try_recover() {
  if(osmium_logged_in()) return;

  if(!isset($_COOKIE['Osmium']) || empty($_COOKIE['Osmium'])) return;
  $token = $_COOKIE['Osmium'];
  $now = time();
  $login = false;

  list($has_token) = pg_fetch_row(osmium_pg_query_params('SELECT COUNT(token) FROM osmium.cookie_tokens WHERE token = $1 AND expiration_date >= $2', array($token, $now)));

  if($has_token == 1) {
    list($account_id, $client_attributes) = pg_fetch_row(osmium_pg_query_params('SELECT account_id, client_attributes FROM osmium.cookie_tokens WHERE token = $1', array($token)));

    if(osmium_check_client_attributes($client_attributes)) {
      $k = pg_fetch_row(osmium_pg_query_params('SELECT account_name FROM osmium.accounts WHERE account_id = $1', array($account_id)));
      if($k !== false) {
	list($name) = $k;
	osmium_post_login($name, true);
	$login = true;
      }
    }

    osmium_pg_query_params('DELETE FROM osmium.cookie_tokens WHERE token = $1', array($token));
  }

  if(!$login) {
    osmium_logoff(false); /* Delete that erroneous cookie */
  }
}

function osmium_check_api_key() {
  if(!osmium_logged_in()) return;
  global $__osmium_state;

  $key_id = $__osmium_state['a']['key_id'];
  $v_code = $__osmium_state['a']['verification_code'];
  $info = osmium_api('/account/APIKeyInfo.xml.aspx', array('keyID' => $key_id, 'vCode' => $v_code));

  if(!($info instanceof SimpleXMLElement)) {
    global $__osmium_login_state;

    osmium_logoff(false);
    $__osmium_login_state['error'] = 'Login failed because of API issues (osmium_api() returned a non-object). Sorry for the inconvenience.';
    return;
  }

  if(isset($info->error) && !empty($info->error)) {
    $err_code = (int)$info->error['code'];
    /* Error code details: http://wiki.eve-id.net/APIv2_Eve_ErrorList_XML */
    if(200 <= $err_code && $err_code < 300) {
      /* Most likely user error */
      $__osmium_state['renew_api'] = true;
    } else {
      /* Most likely internal error */
      global $__osmium_login_state;

      osmium_logoff(false);
      $__osmium_login_state['error'] = 'Login failed because of API issues (got error '.$err_code.': '.((string)$info->error).'). Sorry for the inconvenience.';
      return;  
    }
  }
}

function osmium_api_maybe_redirect($relative) {
  global $__osmium_state;
  global $__osmium_state_renew_api_ignore;

  if(!osmium_logged_in()) return;

  if(isset($__osmium_state['renew_api']) && $__osmium_state['renew_api'] === true
     && !$__osmium_state_renew_api_ignore) {
    header('Location: '.$relative.'/renew_api?non_consensual=1', true, 303);
    die();
  }
}