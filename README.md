Osmium
======

Osmium is a user-friendly fitting tool for the EVE Online game.

Credits
-------

Osmium is released under the GNU Affero General Public License,
version 3. You can see the full license text in the `COPYING` file.

For the full list of Osmium contributors, see the `CREDITS` file.

Osmium uses libdogma, released under the GNU General Public License,
version 3 (or later).
<https://github.com/Artefact2/libdogma/blob/master/COPYING>

Osmium uses php-dogma, released under the GNU Affero General Public
License, version 3 (or later).
<https://github.com/Artefact2/php-dogma/blob/master/COPYING>

Osmium uses the Common Loadout Format PHP validator, released under
the WTFPL license, version 2.
<https://github.com/Artefact2/common-loadout-format/blob/master/COPYING>

Osmium uses jQuery, released under the MIT license.
<https://jquery.org/license/>

Osmium uses the PHP Markdown Extra library, released under the GNU
General Public License, version 2.
<http://michelf.com/projects/php-markdown/license/>

Osmium uses the phpass library, released in the public domain.
<http://www.openwall.com/phpass/>

Osmium uses js-deflate, released under the GNU General Public License,
version 2.
<https://github.com/dankogai/js-deflate/blob/master/rawdeflate.js>

Contact
-------

* <artefact2@gmail.com>
* `#osmium` on `irc.coldfront.net`

Running Osmium
==============

Installation
------------

Assuming your webserver process runs with user `http` and group
`http`, follow the following steps:

1. Clone the repository, switch to latest stable release, and fetch
   submodules:

   ~~~~
   git clone git://github.com/Artefact2/osmium.git
   cd osmium
   git checkout production
   git submodule init
   git submodule update
   ~~~~

2. Make cache directories writeable by your webserver:

   ~~~~
   chgrp http cache static/cache
   chmod g+rwx cache static/cache
   ~~~~

3. Install the dependencies listed below.

4. Run `Make` to generate the stylesheet.

5. Start the Sphinx search daemon (usually by running `sphinx-searchd`
   in the `sphinx/` directory).

6. Copy the example configuration file `config-example.ini` to
   `config.ini`, and edit it accordingly.

7. See Initial database setup below.

8. Setup the cron jobs (they will renice their CPU/IO priority, no
   need to do it yourself):

   ~~~~
   sudo crontab -e -u http

   @hourly /path/to/osmium/bin/cron.hourly >/dev/null
   @daily  /path/to/osmium/bin/cron.daily  >/dev/null
   ~~~~

9. Configure your HTTP server. Check the `ext/httpd-conf/` directory
  for configuration examples of popular web servers. If yours is not
  in the list, or you want to tweak the configuration yourself, here
  is how Osmium assumes your web server will proceed:

  - Any URL that does **not** match `^/(src/|static)` is aliased (or
    "sent to") to `/src/dispatch.php`.

  - Any URL that matches `^/static-([1-9][0-9]*)/` is aliased to
    `/static/` (for example, '/static-2/foo/bar.png' is an alias of
    `/static/foo/bar.png`).

  - For optimal performance, it is recommended to set an expiration
    date for static files far in the future. Static files all have
    URIs that match `^/static(-[1-9][0-9]*)?/`.

  - To minimize bandwidth usage, be sure to enable gzip compression
    (especially on all the files (except images) under static/).

  - For pretty error pages, you can use
    `/src/fatal.php?code=<HTTP_CODE>&message=<TEXT>`.

Initial database setup
----------------------

Assuming your PostgreSQL database name is `osmium` and it is owned by
the `osmium_user` user, follow these steps:

1. Get the latest Osmium static data dump (see below for how to get it)

2. Import the Osmium static dump:

    ~~~~
    xzcat osmium-sde-*.sql.xz | psql osmium osmium_user
    ~~~~

3. Import the Osmium tables/views:

    ~~~~
    psql osmium osmium_user < pgsql/osmium.sql
    ~~~~

4. Generate the type search index:

   ~~~~
   (stop searchd)
   cd sphinx
   sphinx-indexer osmium_types
   (start searchd)
   ~~~~

Updating
--------

1. Stop your webserver, and backup your database (you can use
   `bin/backup_osmium`).

2. Fetch the latest version and use it:

   ~~~~
   git fetch origin
   git merge origin/production
   git submodule update
   ~~~~

3. Read the `UPDATING` file for release-specific upgrade instructions.

4. Clear stale cache files and regenerate stylesheets and static data:

   ~~~~
   make clear-harmless-cache
   make
   ~~~~

5. Start your webserver and test changes.

Updating the `eve` database schema
----------------------------------

*(Only do this if `UPDATING` specifies the `eve` schema has been
updated.)*

1. Backup the `osmium` schema (`bin/backup_osmium`).

2. Drop the two schemas:

   ~~~~
   DROP SCHEMA osmium CASCADE;
   DROP SCHEMA eve CASCADE;
   ~~~~

3. Redo step 1 and 2 of the initial database setup section (see
   above).

4. Restore the `osmium` schema.

   ~~~~
   pg_restore pgsql/osmium-full-XXXXX.pgsql | psql osmium osmium_user
   ~~~~

5. ~~~
   make post-eve-schema-update
   ~~~

Updating the `osmium` database schema
-------------------------------------

*(Only do this if `UPDATING` specifies the `osmium` schema has been
updated.)*

1. Backup the `osmium` schema (`bin/backup_osmium`).

2. Apply all the database patches for the version you are upgrading
   from (if you skipped multiple versions, use all patches in versions
   greater or equal than the version you are upgrading from):

   ~~~~
   cat pgsql/patches/<previous_version>/*.sql | psql osmium osmium_user
   ~~~~

3. Regenerate the module search index:

   ~~~~
   (stop searchd)
   cd sphinx
   sphinx-indexer osmium_types
   (start searchd)
   ~~~~

   You must do this after every `eve` or `osmium` schema update.

Dependencies
============

* PHP >= 5.4, with:
  * [dogma extension](https://github.com/Artefact2/php-dogma), using libdogma 1.1.x (`dogma.so`)
  * PostgreSQL extension (`pgsql.so`)
  * MySQLi extension (`mysqli.so`)
  * cURL extension (`curl.so`)
  * SimpleXML support (enabled by default)
  * Zlib support
  * (Optional) Semaphores support (`sysvsem.so`)
  * (Optional) iconv extension (`iconv.so`)
  * (Optional) intl extension (`intl.so`)
  * (Optional) APC (or APCu for PHP >= 5.5) extension (`apc.so`)

* PostgreSQL >= 9.0

* Sphinx search server >= 2.0.4 
  (using the `sphinx.conf` from the `sphinx/` directory)

* HTMLPurifier PEAR package, see http://htmlpurifier.org/download#PEAR

* Sass >= 3.2 (http://sass-lang.com/)

* (Optional) A Javascript minifier (UglifyJS is recommended)
  **(heavily recommended for production)**

* (Optional) Horde_Text_Diff+Horde_Autoloader PEAR packages, see
  http://pear.horde.org **(heavily recommended for production)**

* (Optional) PHPUnit, for automated tests

* (Optional) Xdebug, for debugging and code coverage reports

Getting the Osmium data dump
============================

The quick way
-------------

Get it from here: <http://artefact2.com/files/osmium-data/>

The long way
------------

The dump file is generated from the database of the EVE client
itself. It could be generated from the Static Data Dump (with a price
index from a third party source). Since Osmium uses a different schema
with clear, defined constraints, it is in practice much easier to
generate it from the client itself.

Use the [`phobos`](https://github.com/DarkFenX/Phobos) dumper to
dump the EVE database as JSON files:

~~~~
git clone git://github.com/DarkFenX/Phobos.git
cd phobos
python2.7 setup.py build

PYTHONPATH=./build/lib python2.7 dumpToJson.py -j <JSON_DIRECTORY> -c <EVE_CACHE_DIRECTORY> -e <EVE_DIRECTORY> -t dgmunits,dgmattribs,dgmtypeattribs,dgmeffects,dgmtypeeffects,invcategories,invgroups,invmetagroups,invmetatypes,invtypes,config_GetAverageMarketPricesForClient,marketProxy_GetMarketGroups
~~~~

Then convert the JSON files to SQL statements using the
`json_to_postgres` script in the `bin/` directory:

~~~~
./bin/json_to_postgres <JSON_DIRECTORY>
~~~~

This will create one big SQL file with all the data (but not the
structure). You can now import it:

~~~~
# Import the schema
psql osmium osmium_user < pgsql/eve.sql

# Import the data
psql osmium osmium_user

SET search_path TO eve;
\i osmium-eve-data.sql
~~~~

That's it! You can now dump the eve schema for later use (for example
by using the `backup_eve` script).

~~~~
./bin/backup_eve
~~~~
