# Osmium API

## Guidelines

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

## `/api/json/query_loadouts.json`

### Synopsis

Get a list of loadouts matching a given query. JSON equivalent of the
browse/search pages. Only public loadouts will be returned.

### GET Parameters

<table class='d' style='width: 70em;'>
<thead>
<tr><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>
<tr><td>query</td><td>string</td><td>no</td><td>any</td><td><em>no filter</em></td>
<td>Search query used to filter the results.</td></tr>

<tr><td>limit</td><td>integer</td><td>no</td><td>0 ≤ x ≤ 50</td><td>25</td>
<td>Maximum number of loadouts to return. Hardcoded limit of 50.</td></tr>

<tr><td>offset</td><td>integer</td><td>no</td><td>0 ≤ x ≤ 1000</td><td>0</td>
<td>Used to paginate the results (skip the first x rows). Hardcoded limit of 1000.</td></tr>

<tr><td>sortby</td><td>string</td><td>no</td><td>creationdate, score, relevance</td><td>relevance</td>
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
