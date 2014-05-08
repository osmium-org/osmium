# Query loadouts

## Syntax

`/api/json/loadout/query/<query>`

## Synopsis

Get a list of loadouts matching a given query. JSON equivalent of the
browse/search pages. May return account, corporation or alliance-only
loadouts if the request is made from a logged-in session. Will not
return hidden loadouts, unless you are the owner and the request is
made from a logged-in session.


## Parameters

**Important: also read the [common parameters](./common) list.**

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>URI</td><td>query</td><td>string</td><td>no</td><td>any</td><td><em>none</em></td>
<td>Filter returned loadouts using this query. See the <a href='../search#loadouts'>search help</a> for the query syntax.</td></tr>

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



## Result

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

## Examples

* [`/api/json/loadout/query/drake?sortby=creationdate&minify=0`](../../api/json/loadout/query/drake?sortby=creationdate&minify=0)

* <a href='../../api/json/loadout/query/@dps &gt;= 700 @estimatedprice &lt;= 200m @estimatedprice &gt;= 1?minify=0'><code>/api/json/loadout/query/@dps &gt;= 700 @estimatedprice &lt;= 200m @estimatedprice &gt;= 1?minify=0</code></a>