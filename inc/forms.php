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

namespace Osmium\Forms;

function post_redirect_get() {
	if(isset($_POST) && $_POST !== [] && !defined('Osmium\NO_CSRF_CHECK')) {
		if(!isset($_POST['o___csrf']) || $_POST['o___csrf'] !== \Osmium\State\get_token()) {
			/* No/incorrect CSRF token */
			$_POST = [];
		}
	}

	if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])
	   && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
		/* Don't PRG XHRs */
		return;
	}

	if(isset($_GET['__NO_PRG']) || defined('Osmium\NO_PRG')) return;

	$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '__cli';

	if(isset($_POST) && $_POST !== []) {
		if(!isset($_FILES)) $_FILES = array();
		else {
			foreach($_FILES as &$file) {
				if($file['error'] != UPLOAD_ERR_OK) continue;

				$temp = tempnam(\Osmium\ROOT.'/cache', 'upload');
				move_uploaded_file($file['tmp_name'], $temp);
				$file['tmp_name'] = $temp;
			}
		}

		\Osmium\State\put_state('prg_data', array($uri, $_POST, $_FILES));
		session_commit();
		header('HTTP/1.1 303 See Other', true, 303);
		header('Location: '.$uri, true, 303);
		die();
	}

	$prg = \Osmium\State\get_state('prg_data', null);
	if($prg !== null) {
		list($from_uri, $prg_post, $prg_files) = $prg;
		if($from_uri === $uri) {
			$_POST = $prg_post;
			$_FILES = $prg_files;
			foreach($_FILES as $file) {
				if($file['error'] != UPLOAD_ERR_OK) continue;
				register_shutdown_function(function() use($file) {
						@unlink($file['tmp_name']);
					});
			}
		}

		\Osmium\State\put_state('prg_data', null);
	}
}
