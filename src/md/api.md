# Osmium API

## Guidelines {#guidelines}

The Osmium API is for developers and enthusiasts who want to integrate
Osmium in their website or application. Usage is open for all provided
you follow the following simple rules:

* Do not hammer the server with API requests. Follow the cache timers
  (indicated by the `Expires:` headers) religiously, and, if you must
  do several requests in a row, try to wait a few seconds between each
  request. If you start getting 403 errors for extended periods of
  time, it's probably because you failed to apply this rule.

* If you can, send a "Accept-Encoding: gzip" header to help save
  bandwidth.

* Set a custom User-Agent which identifies your application and adds a
  way to contact you. If you don't and your application starts
  misbehaving, there will be no way to contact you and your
  IP(s) will be blacklisted. Example:

  ~~~
  MyApplication/1.0.42 (+http://example.org/contact)
  ~~~




## `/loadout/dna/<dna_string>` {#view-loadout-dna}

View a loadout from a DNA string.

By default, the supplied DNA will be mangled into a smaller, slightly
altered version to make up for bad formatting. If your DNA setup
relies heavily on module ordering (like placing passive modules
between active modules for overheating), you can pass the `?mangle=0`
parameter to disable this behaviour.





## `/new/dna/<dna_string>` {#new-loadout-dna}

Create a new loadout from a baseline DNA.





## `/api/convert/<source_fmt>/<target_fmt>/<padding>` {#api-convert}



### Synopsis

Convert a loadout from one format to another.

The `source_fmt` parameter specifies the format of the input loadout,
and can either be a numeric value (in which case it will be treated as
a loadout ID and will use that for the input loadout), or one of the
following strings : `clf`, `gzclf`, `evexml`, `eft`, `dna` or
`autodetect`.

The `target_fmt` parameter specifies the format the loadout is
converted to. It can be one of the following : `clf`, `md`, `evexml`,
`eft`, `dna`.

The `padding` parameter is optional and ignored. You can set it to a
pretty filename for convenience when saving the file from the browser.



### Parameters

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>GET or POST</td><td>input</td><td>string</td><td>yes/no</td><td>N/A</td><td>N/A</td>
<td>Mandatory unless a loadout ID is used as input.<br />The source loadout to convert.<br />POST is preffered (and has precedence), unless the input loadout is a very short string.</td></tr>

<tr><td>GET</td><td>revision</td><td>integer</td><td>no</td><td>any</td><td><em>latest</em></td>
<td>If exporting a loadout from its ID, use this specific revision.</td></tr>

<tr><td>GET</td><td>fleet</td><td>string</td><td>no</td><td>fleet, wing, squad</td><td><em>N/A</em></td>
<td>If exporting a loadout from its ID, convert its fleet/wing/squad booster instead.</td></tr>

<tr><td>GET</td><td>embedclf</td><td>bool</td><td>no</td><td>0, 1</td><td>1</td>
<td>Try to embed gzCLF in the output when possible.</td></tr>

<tr><td>GET</td><td>minify</td><td>bool</td><td>no</td><td>0, 1</td><td>0</td>
<td>If the output format is JSON-based, minify it.</td></tr>

<tr><td>GET</td><td>preset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>If the output format does not support presets, use this preset.</td></tr>

<tr><td>GET</td><td>chargepreset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>If the output format does not support charge presets, use this charge preset.</td></tr>

<tr><td>GET</td><td>dronepreset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>If the output format does not support drone presets, use this drone preset.</td></tr>

<tr><td>GET</td><td>callback</td><td>string</td><td>no</td><td>any</td><td><em>none</em></td>
<td>If present, wrap the data in a JSON object and use the specified callback function (JSONP).</td></tr>

</tbody>
</table>



### Result

The loadout will be returned in the target format (with the correct
`Content-Type`), or the appropriate HTTP error code will be returned:

* 400: input loadout is malformed and could not be
  converted. Additional error messages may be returned as plain text.

* 403: input loadout ID cannot be accessed.

* 404: input loadout ID cannot be found, or fleet parameter was
  specified and the loadout has no such booster (or it has an empty
  fitting).





## `/api/json/query_loadouts.json` {#api-query-loadouts}



### Synopsis

Get a list of loadouts matching a given query. JSON equivalent of the
browse/search pages. Only public loadouts will be returned.



### Parameters

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>GET</td><td>query</td><td>string</td><td>no</td><td>any</td><td><em>no filter</em></td>
<td>Search query used to filter the results.</td></tr>

<tr><td>GET</td><td>limit</td><td>integer</td><td>no</td><td>0 ≤ x ≤ 50</td><td>25</td>
<td>Maximum number of loadouts to return. Hardcoded limit of 50.</td></tr>

<tr><td>GET</td><td>offset</td><td>integer</td><td>no</td><td>0 ≤ x ≤ 1000</td><td>0</td>
<td>Used to paginate the results (skip the first x rows). Hardcoded limit of 1000.</td></tr>

<tr><td>GET</td><td>sortby</td><td>string</td><td>no</td><td>creationdate, score, comments, relevance</td><td>relevance</td>
<td>Sort loadouts either by creation date, by score (calculated from up and down votes), number of comments, or relevance (wrt the search query). Sort order is always DESC.</td></tr>

<tr><td>GET</td><td>buildmin</td><td>integer</td><td>no</td><td>0 ≤ x</td><td>0</td>
<td>If present, only return loadouts whose build number (EVE expansion) is at least the specified value.</td></tr>

<tr><td>GET</td><td>buildmax</td><td>integer</td><td>no</td><td>0 ≤ x</td><td>+∞</td>
<td>If present, only return loadouts whose build number (EVE expansion) is at most the specified value.</td></tr>

</tbody>
</table>



### Result

An array of JSON objects having the following fields:

* `uri`: the permanent URI of the loadout;
* `name`: the loadout name;
* `shiptypeid`: typeid of the fitted ship;
* `shiptypename`: typename of the fitted ship;
* `author`: object containing two keys, `type` (either `character` or `nickname`) and `name`.
* `tags`: an array of tag names;
* `creationdate`: the creation date of the loadout (UNIX timestamp);
* `rawdescription`: general description of the loadout (raw Markdown);
* `fdescription`: parsed and filtered description (safe XHTML to display);
* `score`: score of the loadout, based on the number of up/down votes;
* `upvotes`: total number of upvotes;
* `downvotes`: total number of downvotes;
* `comments`: total number of comments (does not include replies);
* `buildnumber`: intended EVE build number for this loadout.





## `/api/json/loadout/<source_fmt>/attributes/<attributes…>` {#api-loadout-attributes}



### Synopsis

Get some computed attributes from a loadout.

For the `source_fmt` usage, see the [convert call](#api-convert).

The `attributes…` parameter is a forward slash (`/`) separated list of
location specifiers.

You cannot specify more than 50 location specifiers or request more
than 50 attributes in one single request.

### Location specifiers

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



### Osmium extensions

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


### Parameters

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>GET or POST</td><td>input</td><td>string</td><td>yes/no</td><td>N/A</td><td>N/A</td>
<td>Mandatory unless a loadout ID is used as input.<br />The source loadout to convert.<br />POST is preffered (and has precedence), unless the input loadout is a very short string.</td></tr>

<tr><td>GET</td><td>revision</td><td>integer</td><td>no</td><td>any</td><td><em>latest</em></td>
<td>If using a loadout ID as input, use this specific revision.</td></tr>

<tr><td>GET</td><td>fleet</td><td>string</td><td>no</td><td>fleet, wing, squad</td><td><em>N/A</em></td>
<td>If using a loadout ID as input, use its fleet/wing/squad booster instead.</td></tr>

<tr><td>GET</td><td>minify</td><td>bool</td><td>no</td><td>0, 1</td><td>0</td>
<td>Minify generated JSON.</td></tr>

<tr><td>GET</td><td>preset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>Use this preset.</td></tr>

<tr><td>GET</td><td>chargepreset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>Use this charge preset.</td></tr>

<tr><td>GET</td><td>dronepreset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>Use this drone preset.</td></tr>

<tr><td>GET</td><td>callback</td><td>string</td><td>no</td><td>any</td><td><em>none</em></td>
<td>If present, use the specified callback function (JSONP).</td></tr>

<tr><td>GET</td><td>capreload</td><td>bool</td><td>no</td><td>0, 1</td><td>1</td>
<td>Include reload time in capacitor stability calculations.</td></tr>

</tbody>
</table>



### Result

On success, a JSON object containing the requested attributes. The
syntax should be self-explanatory from the examples. On libdogma
errors, an attribute value of `null` will be set.



### Examples

Get the slot counts of a Rifter ([try it](../api/json/loadout/dna/attributes/loc:ship,a:hiSlots,a:medSlots,a:lowSlots,a:upgradeSlotsLeft?input=587::)):

~~~
/api/json/loadout/dna/attributes/loc:ship,a:hiSlots,a:medSlots,a:lowSlots,a:upgradeSlotsLeft?input=587::

# Result
{
    "ship": {
        "hiSlots": 4,
        "medSlots": 3,
        "lowSlots": 3,
        "upgradeSlotsLeft": 3
    }
}
~~~