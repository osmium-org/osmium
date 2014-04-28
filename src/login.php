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
$p->title = 'Login';

if(isset($_POST['__osmium_login'])) {
	if(($errormsg = \Osmium\State\try_login()) === true) {
		header('Location: '.$redirect, true, 303);
		die();
	}

	$p->formerrors['account_name'][] = $errormsg;
	$p->formerrors['password'][] = '';
}

$p->content->appendCreate('h1', 'Login');
$p->content->append(\Osmium\Login\make_https_warning($p));

$tbody = $p->content->appendCreate('o-form', [
	'o-rel-action' => $_SERVER['REQUEST_URI'],
	'method' => 'post',
])->appendCreate('table')->appendCreate('tbody');

if($redirect !== './') {
	$tbody->appendCreate('tr')
		->appendCreate('td', [ 'colspan' => '2' ])
		->appendCreate('p', [
			'class' => 'error_box',
			'You need to log in to access ',
			[ 'strong', [[ 'code', $redirect ]] ],
			'.',
		]);
}

$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'account_name', 'Account name' ] ]] ],
	[ 'td', [[ 'o-input', [
		'type' => 'text',
		'name' => 'account_name',
		'id' => 'account_name',
	]]]],
]);

$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'password', 'Password' ] ]] ],
	[ 'td', [[ 'o-input', [
		'type' => 'password',
		'name' => 'password',
		'id' => 'password',
	]]]],
]);

$tbody->appendCreate('tr')->append([
	[ 'th' ], [ 'td', [
		[ 'o-input', [ 'type' => 'checkbox', 'default' => 'checked', 'id' => 'remember', 'name' => 'remember' ] ],
		[ 'label', [ 'for' => 'checkbox', 'Remember me ', [ 'small', '(uses a cookie)' ] ] ],
	]],
]);

$tbody->appendCreate('tr')->append([
	[ 'th' ], [ 'td', [
		[ 'o-input', [ 'type' => 'submit', 'name' => '__osmium_login', 'value' => 'Login' ] ],
	]],
]);

$p->content->appendCreate('h1', 'Other actions');
$ul = $p->content->appendCreate('ul');

$ul->appendCreate('li', [
	'Don\'t have an account yet? ',
	[ 'a', [ 'o-rel-href' => '/register', 'Create one.' ] ],
	' It takes less than a minute.',
]);

$ul->appendCreate('li', [
	'Forgot your password? ',
	[ 'a', [ 'o-rel-href' => '/resetpassword', 'Reset your password.' ] ],
]);


$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '.';
$p->render($ctx);
