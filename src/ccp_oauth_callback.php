<?php
/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\CCPOAuthCallback;

require __DIR__.'/../inc/root.php';

$json = \Osmium\State\ccp_oauth_verify($estr);
if($json === false) \Osmium\fatal(400, '(stage 1) '.$estr);

if(!isset($json['access_token']) || !isset($json['token_type']) || $json['token_type'] !== 'Bearer') {
	\Osmium\fatal(400, '(stage 1) CCP OAuth returned nonsensical JSON, please report.');
}

$cjson = \Osmium\State\ccp_oauth_get_characterid($json['access_token'], $estr);
if($cjson === false) \Osmium\fatal(400, '(stage 2) '.$estr);

if(!isset($cjson['CharacterID']) || !isset($cjson['CharacterOwnerHash']) || !is_numeric($cjson['CharacterID']) || $cjson['CharacterID'] <= 0) {
	\Osmium\fatal(400, '(stage 2) CCP OAuth returned nonsensical JSON, please report.');
}

$cid = $cjson['CharacterID'];
$oid = $cjson['CharacterOwnerHash'];

$payload = \Osmium\State\get_state('ccp_oauth_payload');

if(!isset($payload['action'])) \Osmium\fatal(400, 'Missing payload.');

switch((string)$payload['action']) {

case 'associate':
	\Osmium\State\assume_logged_in();
	$a = \Osmium\State\get_state('a');
	$ccnt = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(accountcredentialsid) FROM osmium.accountcredentials
		WHERE ccpoauthcharacterid = $1',
		[ $cid ]
	))[0];

	if($ccnt > 0) {
		/* TODO: offer to delete the old association, but this may
		 * leave an account without a way of logging in. A non-trivial
		 * issue. */
		\Osmium\fatal(500, 'Not yet implemented. This character is already associated to another account.');
	} else {
		\Osmium\Db\query_params(
			'INSERT INTO osmium.accountcredentials (accountid, ccpoauthcharacterid, ccpoauthownerhash)
			VALUES ($1, $2, $3)',
			[ $a['accountid'], $cid, $oid ]
		);
		header('Location: ../../settings#s_accountauth');
		die();
	}
	break;

case 'signin':
	\Osmium\State\assume_logged_out();
	$a = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
		'SELECT accountid FROM osmium.accountcredentials
		WHERE ccpoauthcharacterid = $1 AND ccpoauthownerhash = $2',
		[ $cid, $oid ]
	));

	if($a === false) {
		/* TODO: create account on the fly and associate it */
		\Osmium\fatal(500, 'Not yet implemented. There is no account associated to this character.');
	}

	\Osmium\State\do_post_login($a['accountid'], $payload['remember']);
	header('Location: '.$payload['request_uri'], true, 303);
	break;

default:
	\Osmium\fatal(400, 'Unknown payload action.');

}
