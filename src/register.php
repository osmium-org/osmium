<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\Register;

require __DIR__.'/../inc/root.php';

if(!\Osmium\get_ini_setting('registration_enabled')) {
	\Osmium\fatal(403, 'Registration has been disabled. Contact the site administrators for more information.');
}

require \Osmium\ROOT.'/inc/login-common.php';

\Osmium\State\assume_logged_out('.');

$p = new \Osmium\DOM\Page();

if(isset($_POST['account_name'])) {
	$q = \Osmium\Db\query_params(
		'SELECT COUNT(accountid) FROM osmium.accounts
		WHERE accountname = $1',
		array($_POST['account_name'])
	);
	list($an) = \Osmium\Db\fetch_row($q);

	$q = \Osmium\Db\query_params(
		'SELECT COUNT(accountid) FROM osmium.accounts
		WHERE nickname = $1',
		array($_POST['nickname'])
	);
	list($nn) = \Osmium\Db\fetch_row($q);

	$pw = $_POST['password_0'];
	$pw1 = $_POST['password_1'];

	if($an !== '0') {
		$p->formerrors['account_name'][] = 'Sorry, this account name is already taken.';
	} else if($nn !== '0') {
		$p->formerrors['nickname'][] = 'Sorry, this nickname is already taken.';
	} else if(mb_strlen($_POST['account_name']) < 3) {
		$p->formerrors['account_name'][] = 'Must be at least 3 characters.';
	} else if(mb_strlen($_POST['nickname']) < 3) {
		$p->formerrors['nickname'][] = 'Must be at least 3 characters.';
	} else if(($s = \Osmium\State\is_password_sane($pw)) !== true) {
		$p->formerrors['password_0'][] = $s;
	} else if($pw !== $pw1) {
		$p->formerrors['password_1'][] = 'The two passwords did not match.';
	} else {
		$hash = \Osmium\State\hash_password($pw);

		\Osmium\Db\query_params(
			'INSERT INTO osmium.accounts (accountname, passwordhash, nickname,
			creationdate, lastlogindate, keyid, verificationcode, apiverified,
			characterid, charactername, corporationid, corporationname, allianceid, alliancename,
			isfittingmanager, ismoderator, flagweight, reputation) VALUES (
			$1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18
			)', 
			array(
				$_POST['account_name'],
				$hash,
				$_POST['nickname'],
				time(),
				0,
				null,
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
			));
		
		\Osmium\State\do_post_login($_POST['account_name'], false);
		$_POST = array();
		header('Location: ./', true, 303);
		die();
	}
}

$p->title = 'Account creation';

$p->content->appendCreate('h1', 'Account creation');
$p->content->append(\Osmium\Login\make_https_warning($p));

$p->content->appendCreate('p', 'Creating an account allows you to:');
$p->content->appendCreate('ul', [
	[ 'li', 'Save and create public (and private) loadouts;' ],
	[ 'li', 'Comment on loadouts;' ],
	[ 'li', 'Cast votes and flags to help moderation;' ],
	[ 'li', [ 'Access and create corporation or alliance-only loadouts ',
	          [ 'small', '(requires API verification)' ],
	          '.' ] ],
]);



$tbody = $p->content->appendCreate('o-form', [
	'o-rel-action' => '/register',
	'method' => 'post',
])->appendCreate('table')->appendCreate('tbody');

$tbody->appendCreate('tr')->appendCreate('td', [ 'colspan' => '2' ])->appendCreate('p', 'The account name is only used for logging in (and never displayed), while the nickname will be used as a display name.');

$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'account_name', 'Account name' ] ]] ],
	[ 'td', [[ 'o-input', [ 'type' => 'text', 'name' => 'account_name', 'id' => 'account_name' ] ]] ],
]);

$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'nickname', 'Nickname' ] ]] ],
	[ 'td', [[ 'o-input', [ 'type' => 'text', 'name' => 'nickname', 'id' => 'nickname' ] ]] ],
]);

$tbody->appendCreate('tr', [ 'class' => 'separator'])->appendCreate('td', [ 'colspan' => '2' ])->appendCreate('hr');

$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'password_0', 'Password' ] ]] ],
	[ 'td', [[ 'o-input', [ 'type' => 'password', 'name' => 'password_0', 'id' => 'password_0' ] ]] ],
]);
$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'password_1', 'Password (confirm)' ] ]] ],
	[ 'td', [[ 'o-input', [ 'type' => 'password', 'name' => 'password_1', 'id' => 'password_1' ] ]] ],
]);

$tbody->appendCreate('tr', [ 'class' => 'separator'])->appendCreate('td', [ 'colspan' => '2' ])->appendCreate('hr');

$tbody->appendCreate('tr')->append([
	[ 'th' ], [ 'td', [
		[ 'o-input', [ 'type' => 'submit', 'value' => 'Create account' ] ]
	]],
]);


$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';
$p->render($ctx);
