# Version 0.13

## Version 0.13.5, released 2015-08-25

* Add option to export loadouts as SVG. Add code template to embed it into another webpage.

* Support adding effect beacons (like wormhole or incursion effects) to loadouts.

## Version 0.13.4, released 2015-02-17

* Fix slot counts while editing tech III ships.

* Other minor bugfixes.

* Fetch recent kills from Eve-Kill using STOMP.

## Version 0.13.3, released 2014-12-13

* Support Confessor modes.

* Unpublished types, groups and attributes are now more explicitely
  shown in the database browser.

* Type prices are updated weekly using EVE-Central.

* Minor usability improvements.

* Lots of bugs fixed.

## Version 0.13.2, released 2014-10-02

* Lots of bugs fixed.

## Version 0.13.1, released 2014-07-22

* **Using loadout IDs in `source_fmt` in API calls has been
    deprecated.** Use `uri` instead. See the [help](./help/api/common)
    for the new usage.

* Initial support for the CCP OAuth service (Single Sign-On).

* Mostly redone account settings page:

  * Nickname can now be changed every so often.

  * Clearer API verification recap.

  * Add more than one username/passphrase to an account.

* API keys used for verification or passphrase reset can now have any
  mask, but (for security reasons) must have a predetermined
  verification code.

## Version 0.13.0, released 2014-06-03

* **The syntax for the query loadouts API call has changed. Please update your
    applications. [New syntax is here.](./help/api/loadout-query)**

* New API call: loadout attributes.

* The API documentation has been completely overhauled and should be
  easier to navigate.

* More flexible password protected loadouts: can now choose between
  requiring a password for everyone or for people not satisfying the
  view permission. The latter can be made public. Old
  password-protected loadouts will still behave the same as they used
  to.

* Private loadouts will now show in search results, provided you are
  the owner. Advanced search now allows you to filter public, private,
  corporation or alliance loadouts more easily.

* Changed keyboard shortcut for Undo from `C-_` to `C-z` for
  compatibility reasons.

* Undo history now interacts with browser history when possible.

# Version 0.12

## Version 0.12.1, released 2014-04-28

* Rubicon 1.4 readiness.

* Many minor bugfixes. See `git log` for all the gory details.

## Version 0.12.0, released 2014-03-12

* Import loadouts from CREST killmails.

* Ship traits are back.

* Add simple database browser for EVE types, attributes and effects.

* New "compare types" page to compare multiple types together.

* Add search form in the navigation bar.

* Search page now searches types.

* Instead of showing the search results page when there is a unique
  result, you will be redirected to the result directly.

* Set a default character for your account to be used instead of "All
  V" when viewing/creating a loadout.

* Select skillsets in the compare DPS page.

* Better IGB support.

* **Filter loadouts by flyability (for one of your characters).**

* Training time will use character attributes and is no longer an
  estimate (unless using All V or All 0).

# Version 0.11

## Version 0.11.0, released 2014-01-28

* Account password can now be changed in the settings page
  (finally…). Settings page now has tabbed sections for easier
  browsing.

* Search results can be sorted in ascending order. They can also be
  sorted by name, ship name, ship group, author or tags.

* New page that explains reputation points and shows a recap of
  privileges. The link is shown when seeing your own profile.

* Privilege: re-tag any public loadout if you have more than 100
  reputation points.

* New Mastery section in the loadout side bar, shows the missing
  skills and an estimated training time to be able to fly the
  loadout. Thanks jboning.

* New "browse to market group" context menu action.

# Version 0.10

## Version 0.10.1, released 2013-11-19

* DPS graphs implemented (accessible via the "ship" context
  menu). Both one-dimensional and two-dimensional graphs can be
  made. DPS of up to 6 loadouts can be compared on a dedicated page.

* Smartbomb damage is now included in DPS summary.

* Fixed cycle time of bomb launchers in DPS calculations.

* Auto-add modules, charges, etc. when searching types by resubmitting
  the search form (or pressing "Enter" twice).

* Update the EVE database to Rubicon 1.0.

## Version 0.10.0, released 2013-09-03

* Fleet bonuses are now fully supported.

* Projected effects are supported, using remote loadouts. Per-module
  targets and complex setups (including loops) are possible.

* Revamped home page:

  * Shows some metrics like number of current users, number of fits,
    etc.

  * Replaced recently updated fits by popular fits (fits with the
    highest score, determined using votes).

  * Shows more fits (20 per section).

  * Shows popular losses of the week and detects likely alliance
    doctrines (with data from zKillboard).

* Improved layout for fittings that appear in search results. DPS, EHP
  and estimated price are now shown.

* Filter, search or sort loadouts by DPS, EHP and estimated price.

* Added two new view permissions: "good standings only" and "excellent
  standings only". Uses your contact list. Thanks Jognu.

* Search types by meta level with the "@ml X" syntax. Added a help
  page for search in general.

* Modal dialogs are now prettier.

* Added parent type names to loadout and type search indexes. Added
  group names of fitted types to the loadout search index.

* Use progress bars for CPU, powergrid and calibration and show the
  amount left by default (the classic "used/total" format is shown in
  the tooltip).

* Show variations of modules in the "show info" dialog.

* Outgoing and incoming effects (repairs, energy transfers,
  neutralizers and nosferatus) are now recapped in the attribute side
  bar.

* Damage profiles are now persistent. More default damage profiles are
  available, generated from ammo damage profiles.

# Version 0.9

## Version 0.9.1, released 2013-07-22

* Performance: put most used icons in a sprite.

* Add "Edit" link right next to "Fork" for convenience.

* "Show info" now available for non-fitted types.

## Version 0.9.0, released 2013-07-18

* Performance: now using
  [libdogma](https://github.com/osmium-org/libdogma) as a drop-in dogma
  engine

* Performance: local loadout is made as needed in view_loadout

* Show more attributes in the sidebar by default:

  * targeting range, number of targets, sensor strength, scan
    resolution, signature radius and various targeting times in the
    tooltip (for both signature radius and scan resolution)

  * warp speed and warp core strength

  * cargo capacity and drone control range

* Choose whether to include reload time in DPS, tank and capacitor
  calculations

* Show number of cycles a module can do before having to reload
  (usually this is the number of charges that fit in the module)

* Support customizable damage profiles (with persistent custom
  profiles)

* Support implants and boosters, with user-togglable side effects

* Description of the item is now displayed in the "show info" dialog.

* Charges are now grouped by parent in context menus.

* Improved layout on the in-game browser

# Version 0.8

## Version 0.8.0, released 2013-06-30

* Fittings now track the intended EVE expansion; exported and imported
  fits from CLF use build-number accordingly. Advanced search mode to
  search loadouts by expansion.

* New "new loadout" and "view loadout page":

  * Stateless: it is possible to edit/create multiple loadouts in
    different tabs simultaneously.

  * Responsive: more work is now done locally

  * More features: powerful undo, module grouping for batch charge
    operations, select a different character's skills, …

  * Less cluttered and user-friendlier

* Basic loadout forking (private editable copies of loadouts)

* Tag aliases

* API call to edit/view a fit from DNA

* API call to convert loadouts from any format to any format

* Convert page to convert loadouts without importing them

* Privacy: stale favorites are handled correctly

* Can use any Javascript minifier (not just UglifyJS)

* Fixed a bug in typessearchdata mixing up high and medium slots of
  modules.

* Fixed negative sustained tank values in certain capacitor-heavy
  situations.

* Fixed icons disappearing when playing with module states on a hidden
  loadout.

* Maximum (and minimum) number of tags are now specified in the
  configuration file. The default maximum was increased to 8.

# Version 0.7

## Version 0.7.0, released 2013-06-09

* Search improvements: search by fitted charges/drones, search by ship
  group.

* Simple yet persistent theme switcher (either switch styles using the
  browser menu or the link in the footer).

# Version 0.6

## Version 0.6.5, released 2013-06-04

* Updated the EVE database to Odyssey 1.0.

## Version 0.6.4, released 2013-02-22

* The Ancillary Armor Repairer now works correctly

## Version 0.6.3, released 2013-02-21

* Updated the EVE database to Retribution 1.1

## Version 0.6.2, released 2012-12-04

* Updated the EVE database to Retribution 1.0

## Version 0.6.1, released 2012-10-17

* Fixed the API SSL certificate not being trusted by cURL

## Version 0.6.0, released 2012-10-16

* Darker, easier on the eyes color scheme

* "Show info" dialog now takes selected skillset into account

* Newly added drones (either when importing from DNA, EFT or XML
  formats or when creating a new fit) will firstly be added in space
  while bandwidth and max number of drones allow it, then will be put
  in the bay.

* A basic public JSON API to query loadouts

* New URIs for private loadouts (that cannot be trivially guessed)

# Version 0.5

## Version 0.5.0, released 2012-09-08

* New "browse loadouts" page to see loadouts sorted by score or
  creation date, with the possibility to filter results

* Selectable characters in the view fitting page (API-based importing
  of characters and manual editing too)

* Improved navigation between steps on the fitting creation page

* "Show info"-like dialog to see attributes and affectors of
  modules/ships/drones (in particular, you can see CPU/powergrid usage
  of modules)

# Version 0.4

## Version 0.4.1, released 2012-08-14

* Fixed tabs on browsers not supporting the history API (like the
  Android browser)

* Import a fit directly to the edit page (also possible for anonymous
  users)

* Improved tank calculations (shows sane sustained values in some
  exotic fits)

* Fixed "empty" character names when API server is responding with
  garbage data

## Version 0.4.0, released 2012-08-08

* Database updated for Inferno 1.2;

* Added two atom feeds for new loadouts and recently updated loadouts;

* Reputation system: users can cast votes (upvotes, downvotes) on
  loadouts and comments, and gain reputation;

* Profile page is now tabbed and shows reputation changes and votes
  cast;

* Improved module search: permits word omissions (so "em field ii"
  will match "EM Ward Field II"), partial matches ("Invul Field II"
  will match "Adaptive Invulnerability Field II") and abbreviations
  ("a-type eanm", "rf tp", "dc2", "100mn mwd", etc.);

* Fixed duplicate titles of pages (now includes version or preset
  names for non-defaults, search page includes search query in title)
  for better bookmarks and search-engine results.

# Version 0.3

## Version 0.3.0, released 2012-07-25

* Added notifications for when people comment on your loadouts (or your comments);

* Added price estimate for loadouts (using the average prices of the game client);

* Most used tags now appear in the front page;

* The new loadout page will now suggest some tags according to your fit.

# Version 0.2

## Version 0.2-rc3, released 2012-07-23

Critical bugfix, and minor performance improvements.

## Version 0.2-rc2, released 2012-07-22

Even more fixes.

## Version 0.2-rc1, released 2012-07-19

This version introduces market-like browsing of modules and ships in
the new fitting page, and fixes some more issues.

# Version 0.1

## Version 0.1-rc3, released 2012-07-16

Fixed some notices in the code, and more bugs fixed.

## Version 0.1-rc2, released 2012-07-15

There are always bugs that make it all the way to production before
showing up. Some were fixed in this release.

## Version 0.1-rc1, released 2012-07-15

First public release of Osmium.
