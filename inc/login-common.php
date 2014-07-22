<?php
/* Osmium
 * Copyright (C) 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Login;

function make_https_warning(\Osmium\DOM\RawPage $d) {
	if(!\Osmium\get_ini_setting('https_available')
	   || \Osmium\HTTPS
	   || !\Osmium\get_ini_setting('prefer_secure_login')) {
		return '';
	}

	$p = $d->element('p', [ 'class' => 'nohttps warning_box' ]);
	$p->append([
		[ 'strong', 'You are not using HTTPS.' ],
		[ 'br' ],
		'Your account credentials (and your session token) will be sent in plaintext over the network.',
		[ 'br' ],
		[ 'a', [
			'href' => 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
			'Use the HTTPS version of this page (recommended).'
		]],
	]);

	return $p;
}

function make_forced_vcode_box(\Osmium\DOM\Page $p, $uniq, $postname, $action = null) {
	if($action === null) $action = $_SERVER['REQUEST_URI'];

	$p->snippets[] = 'forced_vcode';
	$e = $p->element('p.forcedvcode');

	$vcode = \Osmium\State\get_state('eveapi_auth_vcode', null);
	if($vcode === null || isset($_POST['refreshvcode'])) {
		$vcode = preg_replace(
			'%[^a-zA-Z0-9]%',
			'',
			'Osmium'.\Osmium\get_ini_setting('host').$uniq.'n'.\Osmium\State\get_nonce()
		);
		\Osmium\State\put_state('eveapi_auth_vcode', $vcode);
	}
	if(!isset($_POST[$postname]) || $_POST[$postname] === '') $_POST[$postname] = $vcode;

	$e->append('For security reasons, you must use the following verification code:');
	$e->appendCreate('br');
	$form = $e->appendCreate('o-form', [ 'method' => 'post', 'action' => $action ]);
	$form->appendCreate('input', [
		'type' => 'text',
		'value' => $vcode,
		'readonly' => 'readonly',
	]);
	$form->appendCreate('input', [
		'type' => 'submit',
		'name' => 'refreshvcode',
		'value' => 'Regenerate code'
	]);

	return $e;
}

/* Check that a passphrase is okay to use for an account.
 *
 * @see check_username_and_passphrase().
 */
function check_passphrase(\Osmium\DOM\Page $p, $ppname, $cppname, $pp = null, $cpp = null) {
	if($pp === null) $pp = $_POST[$ppname];
	if($cpp === null) $cpp = $_POST[$cppname];

	if($pp !== $cpp) {
		$p->formerrors[$cppname][] = 'The two passphrases do not match.';
		return false;
	}

	$s = \Osmium\State\is_password_sane($pp);
	if($s !== true) {
		$p->formerrors[$ppname][] = $s;
		return false;
	}

	return true;
}

/* Check that a username and password are okay to use for an
 * account. Will add error messages to $p when appropriate.
 *
 * @returns true if credentials are okay to use.
 */
function check_username_and_passphrase(\Osmium\DOM\Page $p, $unname, $ppname, $cppname,
                                       $un = null, $pp = null, $cpp = null) {
	if($un === null) $un = $_POST[$unname];

	$ucount = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(accountid) FROM osmium.accountcredentials
		WHERE username = $1',
		[ $un ]
	))[0];

	if($ucount > 0) {
		$p->formerrors[$unname][] = 'This username is already taken.';
		return false;
	}

	if(mb_strlen($un) < 3) {
		$p->formerrors[$unname][] = 'Must be at least 3 characters.';
		return false;
	}

	return check_passphrase($p, $ppname, $cppname, $pp, $cpp);
}

/* Check that a nickname can be used for an account.
 *
 * @see check_username_and_passphrase().
 */
function check_nickname(\Osmium\DOM\Page $p, $nnname, &$nn = null) {
	if($nn === null) $nn =& $_POST[$nnname];

	$ncount = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(accountid) FROM osmium.accounts
		WHERE nickname = $1',
		[ $nn ]
	))[0];

	if($ncount > 0) {
		$p->formerrors[$nnname][] = 'This nickname is already taken.';
		return false;
	}

	$nn = \Osmium\Chrome\trim($nn);

	if(mb_strlen($nn) < 3) {
		$p->formerrors[$nnname][] = 'Must be at least 3 characters.';
		return false;
	}

	return true;
}

/* Create a new account (without any credentials) and return the
 * accountid. Recommended that you do this inside a transaction and
 * immediately insert credentials afterwards. */
function register_account($nickname) {
	return \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'INSERT INTO osmium.accounts (nickname,
		creationdate, lastlogindate, keyid, apiverified,
		characterid, charactername, corporationid, corporationname, allianceid, alliancename,
		isfittingmanager, ismoderator, flagweight, reputation) VALUES (
		$1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15
		) RETURNING accountid', [
			$nickname,
			time(),
			0,
			null,
			'f',
			null,
			null,
			null,
			null,
			null,
			null,
			'f',
			'f',
			\Osmium\Flag\DEFAULT_FLAG_WEIGHT,
			\Osmium\Reputation\DEFAULT_REPUTATION,
	]))[0];
}
