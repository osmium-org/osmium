Osmium
======

Osmium is a user-friendly fitting tool for the EVE Online game.

Caveats
=======

Make sure the `cache` directory is writeable by your `http` user. You
can do it by using (assuming your `http` user is in group `http`):

    chgrp -c http cache
    chmod -c g+rwx cache

If you are NOT using the Apache HTTP Server, you will have to adapt
the rules in the `.htaccess` file.

Running Osmium
==============

1. Install the dependencies below.

2. Run `Make` to generate the stylesheet.

3. Start the Sphinx search daemon (usually by running `sphinx-searchd`
   in the `sphinx/` directory).

4. See the caveats above.

5. Copy the example configuration file `config-example.ini` to
   `config.ini`, and edit it accordingly.

6. See Initial database setup below.

Dependencies
============

* PHP >= 5.4 with:
    PostgreSQL extension,
    MySQLi extension (not mysqld itself),
    cURL extension,
    SimpleXML support.

* PostgreSQL >= 9.0

* Sphinx search server >= 2.0.4 
  (using the `sphinx.conf` from the `sphinx/` directory)

* Sass (http://sass-lang.com/)

* (Optional) memcached + PECL/memcached, for obvious reasons

Initial database setup
======================

Assuming your PostgreSQL database name is `osmium` and it is owned by
the `osmium_user` user, follow these steps:

1. Get the latest Osmium static data dump (see below for how to get it)

2. Import the Osmium static dump:

    psql osmium osmium_user < osmium-sde-*.sql

3. Create the `osmium` schema and import the tables/views:

    pg_restore -O osmium_pgsql.backup | psql osmium osmium_user

Updating the Osmium static data dump
====================================

Because of the tight dependencies between the EVE tables and the
Osmium tables, upgrading the EVE DB (for example after an expansion)
is not as simple as it ought to be. You can do it using the following
steps:

1. Backup your Osmium schema.

    pg_dump -n osmium -U osmium_user osmium -F c > OSMIUM_DUMP.backup
    # or use the bin/backup_osmium script

2. Delete the `osmium` and `eve` schemas.

    DROP SCHEMA osmium CASCADE;
    DROP SCHEMA eve CASCADE;

3. Follow steps 1 and 2 of the previous section ("Initial database
   setup").

4. Restore your Osmium schema.

    pg_restore -O OSMIUM_DUMP.backup | psql osmium osmium_user

   If you run into integrity issues, you may have to delete some
   fittings that use removed modules/ships.

Getting the Osmium data dump
============================

The quick way
-------------

Get it from here: <http://artefact2.com/files/osmium-data/>

Use unxz to decompress.

(Please be kind and use this with moderation, I don't have a lot of
bandwidth! If you can mirror this, please do so.)

The long way
------------

The dump file is generated from the database of the EVE client
itself. Not all of the data (namely dgmexpressions and dgmoperands)
are in the official Static Data Dump, so you'll have to dump the
database from the client yourself then do some minor transformations
to make it PostgreSQL-friendly.

Use the "eve2sql.py" script of the Eos repository
<https://github.com/DarkFenX/Eos> to dump a SQLite database, then dump
it in a text file :

    PYTHONPATH=/path/to/reverence/library python2.7 scripts/eve2sql.py -e /path/to/eve -c /path/to/cache -l ~/dump.sqlite

    sqlite3 ~/dump.sqlite .dump > ~/dump.txt

Then use the `sqlite_to_postgres` script (included in `bin/`)
to convert it in PostgreSQL tables:

    bin/sqlite_to_postgres ~/dump.txt
   
This will create two (one for the schema, one for the data) SQL files
per table in the dump. Then, import the following (in this order):

     psql osmium osmium_user
     SET search_path TO eve;
     \i eve_pgsql.sql
     \i dgmoperands-schema.sql
     \i dgmoperands-data.sql
     \i dgmexpressions-schema.sql
     \i dgmexpressions-data.sql
     \i dgmattribs-data.sql
     \i dgmeffects-data.sql
     \i dgmtypeattribs-data.sql
     \i dgmtypeeffects-data.sql
     \i invcategories-data.sql
     \i invgroups-data.sql
     \i invmetagroups-data.sql
     \i invmetatypes-data.sql
     \i invtypes-data.sql

Now, use the cache_expressions script to populate the `cacheexpressions`
table:

    bin/cache_expressions

That's it! You can now dump the relevant tables and save them for later use.