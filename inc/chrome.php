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

namespace Osmium\Chrome;

function print_header($title = '', $relative = '.', $add_head = '') {
  global $__osmium_chrome_relative;
  $__osmium_chrome_relative = $relative;

  \Osmium\State\api_maybe_redirect($relative);

  if($title == '') {
    $title = 'Osmium / '.\Osmium\SHORT_DESCRIPTION;
  } else {
    $title .= ' / Osmium';
  }

  echo "<!DOCTYPE html>\n<html>\n<head>\n";
  echo "<meta charset='UTF-8' />\n";
  echo "<script type='application/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js'></script>\n";
  echo "<script type='application/javascript' src='https://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js'></script>\n";
  echo "<link rel='stylesheet' href='$relative/static/chrome.css' type='text/css' />\n";
  echo "<link rel='icon' type='image/png' href='$relative/static/favicon.png' />\n";
  echo "<title>$title</title>\n";
  echo "$add_head</head>\n<body>\n<div id='wrapper'>\n";

  echo "<nav>\n<ul>\n";
  echo get_navigation_link($relative.'/', "Main page");
  if(\Osmium\State\is_logged_in()) {
    echo get_navigation_link($relative.'/new', "New fitting");
    echo get_navigation_link($relative.'/renew_api', "API settings");
  } else {

  }

  echo "</ul>\n";
  \Osmium\State\print_login_or_logout_box($relative);
  echo "</nav>\n";
  echo "<noscript>\n<p id='nojs_warning'>To get the full Osmium experience, please enable Javascript for host <strong>".$_SERVER['HTTP_HOST']."</strong>.</p>\n</noscript>\n";
}

function print_footer() {
  global $__osmium_chrome_relative;
  echo "<div id='push'></div>\n</div>\n<footer>\n";
  echo "<p><strong>Osmium ".\Osmium\VERSION." @ ".gethostname()."</strong> — (Artefact2/Indalecia) — <a href='https://github.com/Artefact2/osmium'>Browse source</a> (<a href='http://www.gnu.org/licenses/agpl.html'>AGPLv3</a>)</p>";
  echo "</footer>\n</body>\n</html>\n";
}

function get_navigation_link($dest, $label) {
  if(get_significant_uri($dest) == get_significant_uri($_SERVER['REQUEST_URI'])) {
    return "<li><strong><a href='$dest'>$label</a></strong></li>\n";
  }

  return "<li><a href='$dest'>$label</a></li>\n";
}

function get_significant_uri($uri) {
  $uri = explode('?', $uri, 2)[0];
  $uri = explode('/', $uri);
  return array_pop($uri);
}

function print_js_snippet($js_file) {
  echo "<script>\n".file_get_contents(\Osmium\ROOT.'/src/snippets/'.$js_file.'.js')."</script>\n";
}

function return_json($data, $flags = 0) {
  header('Content-Type: application/json');
  die(json_encode($data, $flags));
}
