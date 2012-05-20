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

namespace Osmium\Db;

$__osmium_pg_link = null;

function connect() {
  global $__osmium_pg_link;
  $host = \Osmium\get_ini_setting('host');
  $port = \Osmium\get_ini_setting('port');
  $user = \Osmium\get_ini_setting('user');
  $password = \Osmium\get_ini_setting('password');
  $dbname = \Osmium\get_ini_setting('dbname');

  return $__osmium_pg_link = pg_connect("host=$host port=$port user=$user password=$password dbname=$dbname");
}

function query_params($query, array $params) {
  global $__osmium_pg_link;
  if($__osmium_pg_link === null && !connect()) {
    \Osmium\fatal(500, 'Could not connect to the database.');
  }

  return pg_query_params($__osmium_pg_link, $query, $params);
}

function fetch_row($resource) {
  return pg_fetch_row($resource);
}

function fetch_assoc($resource) {
  return pg_fetch_assoc($resource);
}