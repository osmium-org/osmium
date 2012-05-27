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

namespace Osmium\State;

$__osmium_state =& $_SESSION['__osmium_state'];
$__osmium_login_state = array();

const COOKIE_AUTH_DURATION = 604800; /* 7 days */
const REQUIRED_ACCESS_MASK = 8; /* Just for CharacterSheet */

function is_logged_in() {
	global $__osmium_state;
	return isset($__osmium_state['a']['characterid']) && $__osmium_state['a']['characterid'] > 0;
}

function do_post_login($account_name, $use_cookie = false) {
	global $__osmium_state;
	$__osmium_state = array();

	$q = \Osmium\Db\query_params('SELECT accountid, accountname, keyid, verificationcode, creationdate, lastlogindate, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator FROM osmium.accounts WHERE accountname = $1', array($account_name));
	$__osmium_state['a'] = \Osmium\Db\fetch_assoc($q);

	check_api_key();

	if($use_cookie) {
		$token = uniqid('Osmium_', true);
		$account_id = $__osmium_state['a']['accountid'];
		$attributes = get_client_attributes();
		$expiration_date = time() + COOKIE_AUTH_DURATION;

		\Osmium\Db\query_params('INSERT INTO osmium.cookietokens (token, accountid, clientattributes, expirationdate) VALUES ($1, $2, $3, $4)', array($token, $account_id, $attributes, $expiration_date));

		setcookie('Osmium', $token, $expiration_date, '/', $_SERVER['HTTP_HOST'], false, true);
	}

	$__osmium_state['logouttoken'] = uniqid('OsmiumTok_', true);

	\Osmium\Db\query_params('UPDATE osmium.accounts SET lastlogindate = $1 WHERE accountid = $2', array(time(), $__osmium_state['a']['accountid']));
}

function logoff($global = false) {
	global $__osmium_state;
	if($global && !is_logged_in()) return;

	if($global) {
		$account_id = $__osmium_state['a']['accountid'];
		\Osmium\Db\query_params('DELETE FROM osmium.cookietokens WHERE accountid = $1', array($account_id));
	}

	setcookie('Osmium', false, 42, '/', $_SERVER['HTTP_HOST'], false, true);
	$_SESSION = array();
}

function get_client_attributes() {
	return hash('sha256', serialize(array($_SERVER['REMOTE_ADDR'],
	                                      $_SERVER['HTTP_USER_AGENT'],
	                                      $_SERVER['HTTP_ACCEPT'],
	                                      $_SERVER['HTTP_HOST']
		                                )));
}

function check_client_attributes($attributes) {
	return $attributes === get_client_attributes();
}

function print_login_or_logout_box($relative) {
	if(is_logged_in()) {
		print_logoff_box($relative);
	} else {
		print_login_box($relative);
	}
}

function print_login_box($relative) {
	$value = isset($_POST['account_name']) ? "value='".htmlspecialchars($_POST['account_name'], ENT_QUOTES)."' " : '';
	$remember = (isset($_POST['account_name']) && (!isset($_POST['remember']) || $_POST['remember'] !== 'on')) ? '' : "checked='checked' ";
  
	global $__osmium_login_state;
	if(isset($__osmium_login_state['error'])) {
		$error = "<p class='error_box'>\n".$__osmium_login_state['error']."\n</p>\n";
	} else $error = '';

	echo "<div id='state_box' class='login'>\n";
	echo "<form method='post' action='".$_SERVER['REQUEST_URI']."'>\n";
	echo "$error<p>\n<input type='text' name='account_name' placeholder='Account name' $value/>\n";
	echo "<input type='password' name='password' placeholder='Password' />\n";
	echo "<input type='submit' name='__osmium_login' value='Login' /> (<small><input type='checkbox' name='remember' id='remember' $remember/> <label for='remember'>Remember me</label></small>) or <a href='$relative/register'>create an account</a><br />\n";
	echo "</p>\n</form>\n</div>\n";
}

function print_logoff_box($relative) {
	global $__osmium_state;
	$id = $__osmium_state['a']['characterid'];
	$tok = urlencode(get_token());

	echo "<div id='state_box' class='logout'>\n<p>\nLogged in as <img src='http://image.eveonline.com/Character/${id}_32.jpg' alt='' /> <strong>".\Osmium\Flag\format_moderator_name($__osmium_state['a'])."</strong>. <a href='$relative/logout?tok=$tok'>Logout</a> (<a href='$relative/logout?tok=$tok'>this session</a> / <a href='$relative/logout?tok=$tok&amp;global=1'>all sessions</a>)\n</p>\n</div>\n";
}

function hash_password($pw) {
	require_once \Osmium\ROOT.'/lib/PasswordHash.php';
	$pwHash = new \PasswordHash(10, true);
	return $pwHash->HashPassword($pw);
}

function check_password($pw, $hash) {
	require_once \Osmium\ROOT.'/lib/PasswordHash.php';
	$pwHash = new \PasswordHash(10, true);
	return $pwHash->CheckPassword($pw, $hash);
}

function try_login() {
	if(is_logged_in()) return;

	$account_name = $_POST['account_name'];
	$pw = $_POST['password'];
	$remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

	list($hash) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT passwordhash FROM osmium.accounts WHERE accountname = $1', array($account_name)));

	if(check_password($pw, $hash)) {
		do_post_login($account_name, $remember);
	} else {
		global $__osmium_login_state;
		$__osmium_login_state['error'] = 'Invalid credentials. Please check your account name and password.';
	}
}

function try_recover() {
	if(is_logged_in()) return;

	if(!isset($_COOKIE['Osmium']) || empty($_COOKIE['Osmium'])) return;
	$token = $_COOKIE['Osmium'];
	$now = time();
	$login = false;

	list($has_token) = pg_fetch_row(\Osmium\Db\query_params('SELECT COUNT(token) FROM osmium.cookietokens WHERE token = $1 AND expirationdate >= $2', array($token, $now)));

	if($has_token == 1) {
		list($account_id, $client_attributes) = pg_fetch_row(\Osmium\Db\query_params('SELECT accountid, clientattributes FROM osmium.cookietokens WHERE token = $1', array($token)));

		if(check_client_attributes($client_attributes)) {
			$k = pg_fetch_row(\Osmium\Db\query_params('SELECT accountname FROM osmium.accounts WHERE accountid = $1', array($account_id)));
			if($k !== false) {
				list($name) = $k;
				do_post_login($name, true);
				$login = true;
			}
		}

		\Osmium\Db\query_params('DELETE FROM osmium.cookietokens WHERE token = $1', array($token));
	}

	if(!$login) {
		logoff(false); /* Delete that erroneous cookie */
	}
}

function check_api_key() {
	$a = \Osmium\State\get_state('a');

	$key_id = $a['keyid'];
	$v_code = $a['verificationcode'];
	$info = \Osmium\EveApi\fetch('/account/APIKeyInfo.xml.aspx', array('keyID' => $key_id, 'vCode' => $v_code));

	if(!($info instanceof \SimpleXMLElement)) {
		global $__osmium_login_state;

		logoff(false);
		$__osmium_login_state['error'] = 'Login failed because of API issues (osmium_api() returned a non-object). Sorry for the inconvenience.';
		return;
	}

	if(isset($info->error) && !empty($info->error)) {
		$err_code = (int)$info->error['code'];
		/* Error code details: http://wiki.eve-id.net/APIv2_Eve_ErrorList_XML */
		if(200 <= $err_code && $err_code < 300) {
			/* Most likely user error (deleted API key or modified vcode) */
			\Osmium\State\put_state('must_renew_api', true);
			return;
		} else {
			/* Most likely internal error */
			global $__osmium_login_state;

			logoff(false);
			$__osmium_login_state['error'] = 'Login failed because of API issues (got error '.$err_code.': '.((string)$info->error).'). Sorry for the inconvenience.';
			return;  
		}
	}

	if((string)$info->result->key["type"] !== 'Character'
	   || (int)$info->result->key['accessMask'] !== REQUIRED_ACCESS_MASK
	   || (int)$info->result->key->rowset->row['characterID'] != $a['characterid']) {
		/* Key settings got modified since last login, and they are invalid now. */
		\Osmium\State\put_state('must_renew_api', true);
		return;
	}

	list($character_name, $corporation_id, $corporation_name, $alliance_id, $alliance_name, $is_fitting_manager) = \Osmium\State\get_character_info($a['characterid']);
	if($character_name != $a['charactername']
	   || $corporation_id != $a['corporationid']
	   || $corporation_name != $a['corporationname']
	   || $alliance_id != $a['allianceid']
	   || $alliance_name != $a['alliancename']) {
		/* Update values in the DB. */
		\Osmium\Db\query_params('UPDATE osmium.accounts SET charactername = $1, corporationid = $2, corporationname = $3, allianceid = $4, alliancename = $5, isfittingmanager = $6 WHERE accountid = $7', array($character_name, $corporation_id, $corporation_name, $alliance_id, $alliance_name, $is_fitting_manager, $a['accountid']));

		/* Put the correct values in state */
		$a['charactername'] = $character_name;
		$a['corporationid'] = $corporation_id;
		$a['corporation_name'] = $corporation_name;
		$a['allianceid'] = $alliance_id;
		$a['alliancename'] = $alliance_name;

		\Osmium\State\put_state('a', $a);
	}
}

function get_character_info($character_id) {
	$char_info = \Osmium\EveApi\fetch('/eve/CharacterInfo.xml.aspx', array('characterID' => $character_id));
  
	$character_name = (string)$char_info->result->characterName;
	$corporation_id = (int)$char_info->result->corporationID;
	$corporation_name = (string)$char_info->result->corporation;
	$alliance_id = (int)$char_info->result->allianceID;
	$alliance_name = (string)$char_info->result->alliance;
  
	if($alliance_id == 0) $alliance_id = null;
	if($alliance_name == '') $alliance_name = null;

	$a = \Osmium\State\get_state('a');
	$char_sheet = \Osmium\EveApi\fetch('/char/CharacterSheet.xml.aspx', 
	                                   array(
		                                   'characterID' => $character_id, 
		                                   'keyID' => $a['keyid'],
		                                   'vCode' => $a['verificationcode'],
		                                   ));

	$is_fitting_manager = false;
	foreach(($char_sheet->result->rowset ?: array()) as $rowset) {
		if((string)$rowset['name'] != 'corporationRoles') continue;

		foreach($rowset->children() as $row) {
			$name = (string)$row['roleName'];
			if($name == 'roleFittingManager' || $name == 'roleDirector') {
				/* FIXME: roleFittingManager may be implicitly granted by other roles. */
				$is_fitting_manager = true;
				break;
			}
		}

		break;
	}
  
	return array($character_name, $corporation_id, $corporation_name, $alliance_id, $alliance_name, (int)$is_fitting_manager);
}

function api_maybe_redirect($relative) {
	global $__osmium_state;

	if(!is_logged_in()) return;

	$must_renew_api = \Osmium\State\get_state('must_renew_api', false);
	$pagename = explode('?', $_SERVER['REQUEST_URI'], 2);
	$pagename = explode('/', $pagename[0]);
	$pagename = array_pop($pagename);
	if($must_renew_api === true && $pagename != 'renew_api') {
		header('Location: '.$relative.'/renew_api?non_consensual=1', true, 303);
		die();
	}
}

function get_setting($key, $default = null) {
	if(!is_logged_in()) return $default;

	global $__osmium_state;
	$accountid = $__osmium_state['a']['accountid'];
	$ret = $default;

	$k = \Osmium\Db\query_params('SELECT value FROM osmium.accountsettings WHERE accountid = $1 AND key = $2', array($accountid, $key));
	while($r = \Osmium\Db\fetch_row($k)) {
		$ret = $r[0];
	}

	return $ret;
}

function put_setting($key, $value) {
	if(!is_logged_in()) return;

	global $__osmium_state;
	$accountid = $__osmium_state['a']['accountid'];
	\Osmium\Db\query_params('DELETE FROM osmium.accountsettings WHERE accountid = $1 AND key = $2', array($accountid, $key));
	\Osmium\Db\query_params('INSERT INTO osmium.accountsettings (accountid, key, value) VALUES ($1, $2, $3)', array($accountid, $key, $value));

	return $value;
}

function get_token() {
	global $__osmium_state;
	return $__osmium_state['logouttoken'];
}

function get_state($key, $default = null) {
	if(isset($_SESSION['__osmium_state'][$key])) {
		return $_SESSION['__osmium_state'][$key];
	} else return $default;
}

function put_state($key, $value) {
	if(!isset($_SESSION['__osmium_state']) || !is_array($_SESSION['__osmium_state'])) {
		$_SESSION['__osmium_state'] = array();
	}

	return $_SESSION['__osmium_state'][$key] = $value;
}
