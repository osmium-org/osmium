Getting the Osmium data dump
============================

The quick way
-------------

A collection of Osmium data dumps are available as [Github releases](https://github.com/osmium-org/osmium/releases).

The long way
------------

The dump file is generated from the database of the EVE client
itself.

* Import the `pgsql/eve.sql` schema;

* Install [Reverence](https://github.com/DarkFenX/reverence) and psycopg2;

* Run the `./bin/reverence_insert` script.

* Optionally insert traits with `./bin/insert_traits`. Requires a Phobos dump.

~~~~
# Import the schema
psql osmium osmium_user < pgsql/eve.sql

./bin/reverence_insert -c <path_to_cache> <path_to_eve>
~~~~

That's it! You can now dump the eve schema for later use (for example
by using the `backup_eve` script).

~~~~
./bin/backup_eve
~~~~
