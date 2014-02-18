# Database browser help

The database browser is a hefty tool to browse the EVE database in a
quick and convenient way. It is intended for curious players and for
third party developers.

Feel free to link to these pages from your own application, but **please
don't scrape the content**.

## `/db/type/<typeid>` {#view-type}

Detailed page about a type.

Example: [Damage Control II](../db/type/2048) `(/db/type/2048)`



## `/db/attribute/<attributeid>` {#view-attribute}

Detailed page about an attribute and a recap of types having
non-default values.

Example: [CPU usage](../db/attribute/50) `(/db/attribute/50)`



## `/db/effect/<effectid>` {#view-effect}

Detailed page about an effect, and a list of types that have the
effect.

Example: [`armorHPBonusAdd`](../db/effect/2837) `(/db/effect/2837)`



## `/db/group/<groupid>` {#browse-group}

Browse the contents of a dogma group.



## `/db/category/<categoryid>` {#browse-category}

Browse a dogma category.



## `/db/marketgroup/<marketgroupid>` {#browse-marketgroup}

Browse a market group.


## `/db/comparegroup/<groupid>/<attributes>` {#comparegroup}
## `/db/comparetypes/<types>/<attributes>` {#comparetypes}

### Usage

Compare types. Very similar to the "compare" in-game window, but with
a twist.

Clicking on an attribute name will **sort** the table by this
attribute value. Click again for descending order, then again to
cancel sorting.

Clicking on a type name will **compare** other rows to it, and
highlight the differences. Differences are color-coded depending if
the value is better or worse, but this may be inaccurate. Please
report inaccuracies so they can be fixed.

### Parameters

* `groupid`: a valid group ID. This is the same as calling
  `/db/comparetypes/` with the list of types that belong in the group.

* `types`: a comma-separated list of type IDs to compare. 50 types
  maximum.

* `attributes`: can be either

  * `auto`, in which case all attributes having differences from the
    specified types will be shown.

  * a comma-separated list of attribute IDs. 50 attributes maximum.

### Examples

* [Compare all target painters](../db/comparegroup/379/auto) `(/db/comparegroup/379/auto)`

* [Compare HP bonus and mass addition of some plates](../db/comparetypes/11293,11345,31904,20351/796,1159) `(/db/comparetypes/11293,11345,31904,20351/796,1159)`
