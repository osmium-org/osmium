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




## `/loadout/dna/<dna_string>` {#view-loadout-dna}

View a loadout from a DNA string.





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

* 404: input loadout ID cannot be found.





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

<tr><td>GET</td><td>sortby</td><td>string</td><td>no</td><td>creationdate, score, relevance</td><td>relevance</td>
<td>Sort loadouts either by creation date, by score (calculated from up and down votes), or relevance (wrt the search query). Sort order is always DESC.</td></tr>

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

* `downvotes`: total number of downvotes.
