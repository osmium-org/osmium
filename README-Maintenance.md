Maintaining Osmium
==================

This document is intended for Osmium developers and explains the steps
required to update Osmium to a new EVE patch.

1. Update common-loadout-format, run `git diff` on the helpers to see
   if there's anything suspicious.

2. Update libdogma. Run the tests. Some tests may fail (because the
   values may have been changed by the patch), but the bruteforce test
   must never fail. (If it fails, then some loadout configuration is
   causing libdogma to crash, so obviously this is not safe for use in
   a public webserver.)

3. Update common-loadout-format submodule in the osmium repository.

4. Put the new patch in fit-db-versions.php. Only bump dogmaver when
   there were significant dogma changes, breaking most current
   loadouts.

5. Import the new EVE data (see README-Datadump.md).

6. Regenerate sphinx.conf and recreate Sphinx indexes (loadouts &
   types).

7. Bump CLIENT_DATA_STATICVER in inc/root.php.

8. Clear all cache, restart php-fpm and run tests. Also test some
   stuff manually.
