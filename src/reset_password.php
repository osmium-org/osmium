<?php
/* Osmium
 * Copyright (C) 2012, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\ResetPassword;

require __DIR__.'/../inc/root.php';
require \Osmium\ROOT.'/inc/login-common.php';

$p = new \Osmium\DOM\Page();
$p->title = 'Reset passphrase';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';

\Osmium\State\assume_logged_out($ctx->relative);

$p->content->appendCreate('h1', $p->title);
$p->content->append(\Osmium\Login\make_https_warning($p));

if(isset($_POST['key_id'])) {
	$keyid = $_POST['key_id'];
	$vcode = $_POST['v_code'];
	$pw = $_POST['password_0'];
	$pw1 = $_POST['password_1'];

	$keyinfo = \Osmium\EveApi\fetch(
		'/account/APIKeyInfo.xml.aspx',
		[ 'keyID' => $keyid, 'vCode' => $vcode ],
		null, $etype, $estr
	);

	if(($s = \Osmium\State\is_password_sane($pw)) !== true) {
		$p->formerrors['password_0'][] = $s;

	} else if($pw !== $pw1) {
		$p->formerrors['password_1'][] = 'The two passphrases are not equal.';

	} else if($keyinfo === false) {
		$p->formerrors['key_id'][] = '('.$etype.') '.$estr;

	} else if(($characterid = (int)$keyinfo->result->key->rowset->row['characterID']) === 0) {
		$p->formerrors['key_id'][] = 'No character is associated with this API key.';

	} else if(($a = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
		'SELECT accountid
		FROM osmium.accounts WHERE characterid = $1',
		[ $characterid ]
	))) === false) {
		$p->formerrors['key_id'][] = 'This character is not associated with an account.';

	} else if(\Osmium\State\register_eve_api_key_account_auth(
		$a['accountid'], $keyid, $vcode,
		$etype, $estr
	) === false) {
		$p->formerrors['key_id'][] = '('.$etype.') '.$estr;

	} else {
		$hash = \Osmium\State\hash_password($pw);

		$a = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
			'SELECT accountid, username FROM osmium.accountcredentials
			WHERE accountid = $1 AND username IS NOT NULL',
			[ $a['accountid'] ]
		));

		\Osmium\Db\query_params(
			'UPDATE osmium.accountcredentials SET passwordhash = $1
			WHERE accountid = $2 AND passwordhash IS NOT NULL',
			array($hash, $a['accountid'])
		);

		$p->content->appendCreate('p')->appendCreate('strong', [
			'Passphrase reset was successful. You can now login on the account ',
			[ 'code', $a['username'] ],
			' using your new passphrase.',
		]);

		$p->render($ctx);
		die();
	}
}

$p->content->appendCreate('p')->append([
	'If you forgot the passphrase of your API-verified account, you can reset it by supplying an API key associated to the character you verified your account with.',
	[ 'br' ],
	'You can see a list of your API keys here: ',
	[ 'strong', [[ 'a', [ 'href' => 'https://support.eveonline.com/api', 'https://support.eveonline.com/api' ] ]] ]
]);

$tbody = $p->content
	->appendCreate('o-form', [ 'action' => $_SERVER['REQUEST_URI'], 'method' => 'post' ])
	->appendCreate('table')
	->appendCreate('tbody')
	;

$tbody->append($p->makeFormInputRow('text', 'key_id', 'API Key ID'));
$tbody->append($p->makeFormInputRow('text', 'v_code', 'Verification Code'));

$tbody->append($p->makeFormSeparatorRow());

$tbody->append($p->makeFormInputRow('password', 'password_0', 'New passphrase'));
$tbody->append($p->makeFormInputRow('password', 'password_1', [
	'New passphrase',
	[ 'br' ],
	[ 'small', '(confirm)' ],
]));

$tbody->append($p->makeFormSubmitRow('Check API key'));

$p->render($ctx);
