# Database browser help

The database browser is a hefty tool to browse the EVE database in a
quick and convenient way. It is intended for curious players and for
third party developers.

Feel free to link to these pages from your own application, but **please
don't scrape the content**.


## Detail pages {#view-detail}

### Type

`/db/type/<typeid>`

Detailed page about a type.

Example: [Damage Control II](../db/type/2048) `(/db/type/2048)`

### Attribute

`/db/attribute/<attributeid>`

Detailed page about an attribute and a recap of types having
non-default values.

Example: [CPU usage](../db/attribute/50) `(/db/attribute/50)`

### Effect

`/db/effect/<effectid>`

Detailed page about an effect, and a list of types that have the
effect.

Example: [`armorHPBonusAdd`](../db/effect/2837) `(/db/effect/2837)`

## Listing pages {#view-listing}

### Group

`/db/group/<groupid>`

Browse the contents of a dogma group.

### Category

`/db/category/<categoryid>`

Browse a dogma category.

### Market group

`/db/marketgroup/<marketgroupid>`

Browse the contents of a market group.


## Compare types page {#compare}

### URIs

* `/db/comparetypes/<types>/<attributes>`
* `/db/comparevariations/<typeid>/<attributes>`
* `/db/comparegroup/<groupid>/<attributes>`
* `/db/comparemarketgroup/<marketgroupid>/<attributes>`

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

* `marketgroupid`: a valid market group ID. This is the same as
  calling `/db/comparetypes/` with the list of types that belong in
  the group.

* `typeid`: a type ID. This is the same as calling `/db/comparetypes/`
  with the list of variations of the given type.

* `types`: a comma-separated list of type IDs to compare. 50 types
  maximum.

* `attributes`: a comma-separated list of any of

  * `auto`, this will add all attributes that differ among the
    selected types;

  * an attribute ID;

  * a RPN expression, see below for the syntax.

### RPN attributes

If you want to compare values derived from attributes, like armor
repaired per second, or shield transferred per capacitor unit, or
damage per second, you can specify RPN expressions in the attributes
list.

RPN comes from *reverse polish notation*, so read up on that if you
are not familiar with it.

Here is the syntax of a RPN attribute:

~~~
		rpn:<new-attribute-name>:<lexeme1>:<lexeme2>:…:<lexemeN>
~~~

* `new-attribute-name` is any string, which defines the name of the
  new attribute you want to compute.

* `lexeme1`, …, `lexemeN` are lexemes that will compute the new
  attribute. Here are the possible lexemes:

  * *numeric value*: push this numeric value on the stack. Examples:
     `2`, `-.5`, `1.9e-10`, etc.

  * `a`: pop the stack, treat the popped value as an attribute ID, and
    push the value of this attribute on the stack. **You must also
    specify any attribute you want to use with `a` on its own in the
    attribute list.**
	
  * `add`, `mul`: add or multiply the two last elements of the stack,
    and push the result.

  * `sub`, `div`: substract or divide the two last elements of the
    stack.

Examples of RPN attributes you may want to use:

~~~
rpn:Armor HP repaired per millisecond   :84:a:73:a:div
rpn:Armor HP repaired per second        :84:a:73:a:div:1000:mul
~~~

### Examples

* [Compare all target painters](../db/comparegroup/379/auto) `(/db/comparegroup/379/auto)`

* [Compare HP bonus and mass addition of some plates](../db/comparetypes/11293,11345,31904,20351/796,1159) `(/db/comparetypes/11293,11345,31904,20351/796,1159)`

* [Compare large shield booster and their efficiency](../db/comparemarketgroup/611/6,68,73,rpn:Shield%20bonus%20per%20second:68:a:73:a:div:1000:mul,rpn:Shield%20repaired%20per%20capacitor%20unit:68:a:6:a:div)

  ~~~
  /db/comparemarketgroup/611/6,68,73,
                               rpn:Shield bonus per second:68:a:73:a:div:1000:mul,
                               rpn:Shield repaired per capacitor unit:68:a:6:a:div
  ~~~
