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

if(isset($_POST['ccpssoinit'])) {
	\Osmium\State\ccp_oauth_redirect([
		'action' => 'signup',
	]);
} else if(isset($_POST['account_name'])
   && \Osmium\Login\check_username_and_passphrase($p, 'account_name', 'password_0', 'password_1')
   && \Osmium\Login\check_nickname($p, 'nickname')) {
	$hash = \Osmium\State\hash_password($_POST['password_0']);

	\Osmium\Db\query('BEGIN');
	$accountid = \Osmium\Login\register_account($_POST['nickname']);
	\Osmium\Db\query_params(
		'INSERT INTO osmium.accountcredentials (accountid, username, passwordhash)
			VALUES ($1, $2, $3)', [
				$accountid,
				$_POST['account_name'],
				$hash,
			]);
	\Osmium\Db\query('COMMIT');

	$settings = \Osmium\State\get_state('__settings', []);
	foreach($settings as $k => $v) {
		$settings[$k] = \Osmium\State\get_setting($k);
	}
		
	\Osmium\State\do_post_login($accountid, false);
	$_POST = array();

	foreach($settings as $k => $v) {
		\Osmium\State\put_setting($k, $v);
	}

	header('Location: ./', true, 303);
	die();
}

$p->title = 'Sign up: create an account';

$p->content->appendCreate('h1', $p->title);
$p->content->append(\Osmium\Login\make_https_warning($p));

if(\Osmium\get_ini_setting('whitelist')) {
	$p->content->appendCreate(
		'p.warning_box',
		'This Osmium instance has whitelisted access. You will need to verify your API in order to use your account.'
	);
}

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

$tbody->append($p->makeFormInputRow(
	'text', 'account_name',
	[ 'Username', [ 'br' ], [ 'small', '(used for logging in)' ] ]
));
$tbody->append($p->makeFormInputRow(
	'text', 'nickname',
	[ 'Nickname', [ 'br' ], [ 'small', '(display name)' ] ]
));

$tbody->append($p->makeFormSeparatorRow());

$tbody->append($p->makeFormInputRow('password', 'password_0', 'Passphrase'));
$tbody->append($p->makeFormInputRow(
	'password', 'password_1',
	[ 'Passphrase', [ 'br' ], [ 'small', '(confirm)' ] ]
));

$tbody->append($p->makeFormSubmitRow('Sign up with username and passphrase'));

if(\Osmium\get_ini_setting('ccp_oauth_available')) {
	$tbody->appendCreate('tr.separator')->appendCreate('th', [ 'colspan' => '2' ])->append('— or —');

	$tr = $tbody->appendCreate('tr');
	$tr->appendCreate('th');
	$tr->appendCreate('td')->appendCreate('input', [
		'name' => 'ccpssoinit',
		'value' => 'Sign up with my EVE character',
		'type' => 'submit',
	]);
}


$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';
$p->render($ctx);
