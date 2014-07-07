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

namespace Osmium\Page\Login;

require __DIR__.'/../inc/root.php';
require \Osmium\ROOT.'/inc/login-common.php';

$redirect = isset($_POST['request_uri']) ? $_POST['request_uri'] : (isset($_GET['r']) ? $_GET['r'] : './');


if(\Osmium\State\is_logged_in()) {
	header('Location: '.$redirect, true, 303);
	die();
}

$p = new \Osmium\DOM\Page();
$p->title = 'Sign in';
$p->index = !isset($_POST['request_uri']) && !isset($_GET['r']);

if(isset($_POST['ccpssoinit'])) {
	\Osmium\State\ccp_oauth_redirect([
		'action' => 'signin',
		'request_uri' => $redirect,
		'remember' => isset($_POST['remember']) && $_POST['remember'] === 'on',
	]);
} else if(isset($_POST['unpp']) && isset($_POST['account_name']) && isset($_POST['password'])) {
	if(($errormsg = \Osmium\State\try_login()) === true) {
		header('Location: '.$redirect, true, 303);
		die();
	}

	$p->formerrors['account_name'][] = $errormsg;
	$p->formerrors['password'][] = '';
}

$p->content->appendCreate('h1', $p->title);
$p->content->append(\Osmium\Login\make_https_warning($p));

$tbody = $p->content->appendCreate('o-form', [
	'o-rel-action' => $_SERVER['REQUEST_URI'].$p->formatQueryString([ 'r' => $redirect ]),
	'method' => 'post',
])->appendCreate('table')->appendCreate('tbody');

if(isset($_GET['m']) && $_GET['m']) {
	$tbody->appendCreate('tr')
		->appendCreate('td', [ 'colspan' => '3' ])
		->appendCreate('p', [
			'class' => 'error_box',
			'You need to sign in to access ',
			[ 'strong', [[ 'code', $redirect ]] ],
			'.',
		]);
}

$tr = $tbody->appendCreate('tr');
$tr->appendCreate('th')->appendCreate('label', [ 'for' => 'account_name', 'Username' ]);
$td = $tr->appendCreate('td');
$td->appendCreate('o-input', [
	'name' => 'account_name',
	'id' => 'account_name',
	'type' => 'text',
	'tabindex' => '1',
	'autofocus' => 'autofocus',
]);
$td->appendCreate('input', [ 'type' => 'hidden', 'name' => 'request_uri', 'value' => $redirect ]);

$tr = $tbody->appendCreate('tr');
$tr->appendCreate('th')->appendCreate('label', [ 'for' => 'password', 'Passphrase' ]);
$tr->appendCreate('td')->appendCreate('o-input', [
	'name' => 'password',
	'id' => 'password',
	'type' => 'password',
	'tabindex' => '2',
]);

$tbody->appendCreate('tr')->append([
	[ 'th' ], [ 'td', [
		[ 'o-input', [
			'type' => 'checkbox',
			'default' => 'checked',
			'id' => 'remember',
			'name' => 'remember',
			'tabindex' => '4',
		] ],
		[ 'label', [ 'for' => 'checkbox', 'Remember me ', [ 'small', '(uses a cookie)' ] ] ],
	]],
]);

$tr = $tbody->appendCreate('tr');
$tr->appendCreate('th');
$tr->appendCreate('td')->appendCreate('input', [
	'type' => 'submit',
	'name' => 'unpp',
	'value' => 'Sign in with username and passphrase',
	'tabindex' => '3',
]);

if(\Osmium\get_ini_setting('ccp_oauth_available')) {
	$tbody->appendCreate('tr.separator')->appendCreate('th', [ 'colspan' => '2' ])->append('— or —');

	$tr = $tbody->appendCreate('tr');
	$tr->appendCreate('th');
	$tr->appendCreate('td')->appendCreate('input', [
		'name' => 'ccpssoinit',
		'value' => 'Sign in with my EVE character',
		'type' => 'submit',
		'tabindex' => '5',
	]);
}



$p->content->appendCreate('h1', 'Other actions');
$ul = $p->content->appendCreate('ul');

if(\Osmium\get_ini_setting('registration_enabled')) {
	$ul->appendCreate('li', [
		'Don\'t have an account yet? ',
		[ 'a', [ 'o-rel-href' => '/register', 'Sign up.' ] ],
		' It\'s free and takes less than a minute.',
	]);
}

$ul->appendCreate('li', [
	'Forgot your passphrase? ',
	[ 'a', [ 'o-rel-href' => '/resetpassword', 'Reset your passphrase.' ] ],
]);


$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';
$p->render($ctx);
