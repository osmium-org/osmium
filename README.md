Osmium
======

Osmium is a user-friendly fitting tool for the EVE Online game.

Credits
-------

Osmium is released under the GNU Affero General Public License,
version 3. You can see the full license text in the `COPYING` file.

For the full list of Osmium contributors, see the `CREDITS` file.

Osmium uses the PHP Markdown Extra library, released under the GNU
General Public License, version 2.
<http://michelf.com/projects/php-markdown/license/>

Osmium uses the phpass library, released in the public domain.
<http://www.openwall.com/phpass/>

Osmium uses the Common Loadout Format PHP validator, released under
the WTFPL license, version 2.
<https://github.com/Artefact2/common-loadout-format/blob/master/COPYING>

Contact
-------

* <artefact2@gmail.com>
* `#osmium` on `irc.coldfront.net`

Caveats
-------

If you are NOT using the Apache HTTP Server, you will have to adapt
the rules in the `.htaccess` file.

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

Updating
--------

1. Stop your webserver, and backup your database (you can use
   `bin/backup_osmium`).

2. Fetch the latest version and use it:

   ~~~~
   git fetch origin
   git merge origin/production
   git submodule update
   make
   ~~~~

3. Clear stale cache files:

   ~~~~
   make clear-harmless-cache
   ~~~~

4. Read the `UPDATING` file for release-specific upgrade instructions.

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

Dependencies
============

* PHP >= 5.4, with:
  * PostgreSQL extension (`pgsql.so`)
  * MySQLi extension (`mysqli.so`)
  * cURL extension (`curl.so`)
  * SimpleXML support (enabled by default)
  * Zlib support
  * (Optional) iconv extension (`iconv.so`)
  * (Optional) intl extension (`intl.so`)

* PostgreSQL >= 9.0

* Sphinx search server >= 2.0.4 
  (using the `sphinx.conf` from the `sphinx/` directory)

* HTMLPurifier PEAR package, see http://htmlpurifier.org/download#PEAR

* Sass >= 3.2 (http://sass-lang.com/)

* (Optional) UglifyJS (`uglifyjs` should be in your `$PATH`), see
  https://github.com/mishoo/UglifyJS **(heavily recommended for
  production)**

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
itself. Not all of the data (namely dgmexpressions and dgmoperands)
are in the official Static Data Dump, so you'll have to dump the
database from the client yourself then do some minor transformations
to make it PostgreSQL-friendly.

Use the `eve2sql.py` script of the Eos repository
<https://github.com/DarkFenX/Eos> to dump a SQLite database, then dump
it in a text file :

    PYTHONPATH=/path/to/reverence/library python2.7 scripts/eve2sql.py -e /path/to/eve -c /path/to/cache -l ~/dump.sqlite

    sqlite3 ~/dump.sqlite .dump > ~/dump.txt

Then use the `sqlite_to_postgres` script (included in `bin/`)
to convert it in PostgreSQL tables:

    bin/sqlite_to_postgres ~/dump.txt
   
This will create two (one for the schema, one for the data) SQL files
per table in the dump. Then, import the following (in this order):

~~~~
# you can find eve.sql in the pgsql directory of the Osmium repo
# it is more or less the raw schema with indexes, foreign keys and proper types
psql osmium osmium_user < pgsql/eve.sql

psql osmium osmium_user
SET search_path TO eve;
\i dgmoperands-schema.sql
\i dgmoperands-data.sql
\i dgmexpressions-schema.sql
\i dgmexpressions-data.sql
\i invcategories-data.sql
\i invgroups-data.sql
\i invmarketgroups-data.sql
\i invtypes-data.sql
\i invmetagroups-data.sql
\i invmetatypes-data.sql
\i dgmattribs-data.sql
\i dgmeffects-data.sql
\i dgmtypeattribs-data.sql
\i dgmtypeeffects-data.sql
~~~~

Import the Osmium schema:

     psql osmium osmium_user < pgsql/osmium.sql

Now, use the cache_expressions script to populate the `cacheexpressions`
table:

    bin/cache_expressions

That's it! You can now delete the `dgmoperands` and `dgmexpressions`
tables, and dump the eve schema for later use (for example by using
the `backup_eve` script).
