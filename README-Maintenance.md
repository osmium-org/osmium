Maintaining Osmium
==================

This document is intended for Osmium developers and explains the steps
required to update Osmium to a new EVE patch.

1. Update libdogma. Run the tests. Some tests may fail (because the
   values may have been changed by the patch), but the bruteforce test
   must never fail. (If it fails, then some loadout configuration is
   causing libdogma to crash, so obviously this is not safe for use in
   a public webserver.)

2. Put the new patch in ext/eve-versions.json. Only bump dogmaver when
   there were significant dogma changes, breaking most current
   loadouts.

3. Import the new EVE data (see README-Datadump.md).

4. Regenerate sphinx.conf and recreate Sphinx indexes (loadouts &
   types).

5. Bump CLIENT_DATA_STATICVER in inc/root.php.

6. Clear all cache, restart php-fpm and run tests. Also test some
   stuff manually.
