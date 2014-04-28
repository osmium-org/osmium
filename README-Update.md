Updating Osmium
===============

1. Fetch the latest version, but don't change any local files yet:

   ~~~~
   git fetch origin
   ~~~~

2. Read the latest UPDATING for any special updating
   instructions. Some updates may require special interventions, and
   it is **crucial** that you read this file before doing anything.

   ~~~~
   git show origin/production:UPDATING
   ~~~~

3. Stop your webserver and backup your database:

   ~~~~
   (stop webserver)
   ./bin/backup_osmium
   ~~~~

4. Merge the latest version:

   ~~~~
   git merge origin/production
   git submodule init
   git submodule update
   ~~~~

5. If UPDATING says that the `eve` schema has been updated, do the
   steps in the "Updating the `eve` schema" section below.

6. If UPDATING says that the `osmium` schema has been updated, do the
   steps in the "Updating the `osmium` schema" section below.

7. Make sure that everything is in working order:

   ~~~~
   ./bin/sanity_check
   ~~~~

8. Clear cache:

   ~~~~
   make clear-harmless-cache
   ~~~~

9. Start your webserver and test the changes. You may want to only
   allow your IP address to access the server during that period.

Updating the `eve` schema/data
==============================

1. Backup the `osmium` schema (`bin/backup_osmium`).

2. Drop the two schemas:

   ~~~~
   DROP SCHEMA osmium CASCADE;
   DROP SCHEMA eve CASCADE;
   ~~~~

3. Redo step 1 and 2 of the initial database setup section (see
   `README-Install.md`).

4. Restore the `osmium` schema.

   ~~~~
   pg_restore -j 8 -d osmium -U osmium_user pgsql/osmium-full-XXXXX.pgsql
   ~~~~

5. Wipe all Sphinx indexes and redo the initial Sphinx setup (see
   `README-Install.md`).

Updating the `osmium` schema
============================

1. Backup the `osmium` schema (`bin/backup_osmium`). This step isn't
   strictly necessary, but it will allow you to restore a working data
   set if the database patches fail.

2. Apply all the database patches for the version you are upgrading
   from (if you skipped multiple versions, use all patches in versions
   greater or equal than the version you are upgrading from):

   ~~~~
   cat pgsql/patches/<previous_version>/*.sql | psql osmium osmium_user
   ~~~~

3. Wipe all Sphinx indexes and redo the initial Sphinx setup (see
   `README-Install.md`).
