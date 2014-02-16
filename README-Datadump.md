Getting the Osmium data dump
============================

The quick way
-------------

A collection of Osmium data dumps are available at
<http://artefact2.com/files/osmium-data/>. Use these at your own risk.

The long way
------------

The dump file is generated from the database of the EVE client
itself.

* Import the `pgsql/eve.sql` schema;

* Install [Reverence](https://github.com/ntt/reverence) and psycopg2;

* Run the `./bin/reverence_insert` script.

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
