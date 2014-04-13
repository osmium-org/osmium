# Loadout attributes

## Syntax

`/api/json/loadout/<source_fmt>/attributes/<attributes…>`



## Synopsis

Get some computed attributes from a loadout.

You cannot specify more than 50 location specifiers or request more
than 50 attributes in one single request.

## Parameters

**Important: also read the [common parameters](./common) list.**

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>URI</td><td>attributes</td><td>string</td><td>yes</td><td>any</td><td>N/A</td>
<td>A forward slash (/) separated list of location specifiers.</td></tr>

<tr><td>GET</td><td>capreload</td><td>bool</td><td>no</td><td>0, 1</td><td>1</td>
<td>Include reload time in capacitor stability calculations.</td></tr>

<tr><td>GET</td><td>dpsreload</td><td>bool</td><td>no</td><td>0, 1</td><td>0</td>
<td>Include reload time in damage-per-second calculations.</td></tr>

<tr><td>GET</td><td>tankreload</td><td>bool</td><td>no</td><td>0, 1</td><td>0</td>
<td>Include reload time in tank calculations.</td></tr>

<tr><td>GET</td><td>damageprofile</td><td>array of 4 double</td><td>no</td><td>[ ≥0, ≥0, ≥0, ≥0 ]</td><td>[ .25, .25, .25, .25 ]</td>
<td>Override the default damage profile. If present, must be an array of 4 nonnegative numeric values (corresponding to EM/Explosive/Kinetic/Thermal proportions), with at least one nonzero value.</td></tr>

</tbody>
</table>

## Location specifiers

Here is the syntax of a location specifier:

~~~
    name1:value1,name2:value2,…,nameN:valueN
~~~

Here is a list of recognised names:

* `loc` is required and can take the value of: `char`, `implant`,
  `skill`, `ship`, `module`, `charge`, `drone`.

* `typeid` is required if `loc` is one of `implant`, `skill`,
  `drone`. May be used if `loc` is `module` or `charge`. If `loc` is
  `charge`, you must specify the type ID of the module using the
  charge, not the type ID of the charge itself.

* `slot` may be used to specify a specific module. Can take the value
  of `high`, `medium`, `low`, `rig`, `subsystem`.

* `index` may be used to specify a specific module (not all input
  formats may support indices). If omitted, use the first fitted
  module with the given type ID.

* `name` may be used to identify this location in the generated
  response. If omitted, an automatically generated name will be used.

* `a` may be used (multiple times in the same location specifier) to
  request the value of a certain attribute. Permitted values are
  attribute names or numeric attribute IDs.



## Osmium extensions

Osmium exposes some additional attributes, and some attributes have
different values than what you would expect:

* On `ship`:

  * `upgradeLoad` will return used calibration.

  * `hiSlots`, `medSlots`, `lowSlots` will take any subsystem bonii
    into account.

  * `turretSlots`, `launcherSlots` will return the **total** number of
    hardpoints on the ship.

  * `turretSlotsLeft`, `launcherSlotsLeft` will return the number of
    **free** hardpoints on the ship.

  * `capacitor` will run a capacitor simulation and return some
    capacitor info of the main ship, a JSON object with the following
    keys: `capacity` (GJ), `stable` (boolean), `delta` (used GJ/ms)
    and `stable_fraction` (between 0 (0%) and 1 (100%)) or
    `depletion_time` (milliseconds).

  * `capacitors` will run a capacitor simulation and return capacitor
    info for all ships (including remotes). Same returned syntax as
    `capacitor`.

  * `ehpAndResonances` will return EHPs and resonances for hull, armor
    and shield. See examples for the return syntax.

  * `priceEstimateTotal` will return a breakdown of the loadout
    estimated price per category. See examples for the return syntax.

  * `miningYieldTotal` will return the total mining yield, in m³/ms.

  * `droneBandwidthUsed` will return the used drone bandwidth by
    drones in space.

  * `droneCapacityUsed` will returned the used drone capacity by
    drones in space and in bay.

  * `damage` will return dps and alpha by source (missiles, turrets,
    smartbombs, drones) and in total.

  * `tank` will return reinforced and sustained tank by type and in total.

  * `outgoing` will return outgoing effects.




## Result

On success, a JSON object containing the requested attributes. The
syntax should be self-explanatory from the examples. On libdogma
errors, an attribute value of `null` will be set.



## Examples

* [`/api/json/loadout/dna/attributes/loc:ship,a:hiSlots,a:medSlots,a:lowSlots,a:upgradeSlotsLeft?input=587::`](../../api/json/loadout/dna/attributes/loc:ship,a:hiSlots,a:medSlots,a:lowSlots,a:upgradeSlotsLeft?input=587::)

* [`/api/json/loadout/1/attributes/loc:ship,a:tank,a:ehpAndResonances,a:capacitors,a:damage?minify=0`](../../api/json/loadout/1/attributes/loc:ship,a:tank,a:ehpAndResonances,a:capacitors,a:damage?minify=0)
