# Convert/export a lodaout

## Syntax

`/api/convert/<source_fmt>/<target_fmt>/<padding>`



## Synopsis

Convert a loadout from one format to another.



## Parameters

**Important: also read the [common parameters](./common) list.**

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>URI</td><td>target_fmt</td><td>string</td><td>yes</td><td>clf, md, evexml, eft, dna</td><td>N/A</td>
<td>The format to export the loadout in.</td></tr>

<tr><td>URI</td><td>padding</td><td>string</td><td>no</td><td>any</td><td><em>none</em></td>
<td>Optionally specify a filename, for convenience when saving the document to a file.</td></tr>

<tr><td>GET</td><td>embedclf</td><td>bool</td><td>no</td><td>0, 1</td><td>1</td>
<td>Try to embed gzCLF in the output when possible.</td></tr>

</tbody>
</table>



## Result

The loadout will be returned in the target format (with the correct
`Content-Type`), or the appropriate HTTP error code will be returned:

* 400: input loadout is malformed and could not be
  converted. Additional error messages may be returned as plain text.

* 403: input loadout ID cannot be accessed.

* 404: input loadout ID cannot be found, or fleet parameter was
  specified and the loadout has no such booster (or it has an empty
  fitting).


## Examples

* [`/api/convert/1/eft/eft.txt?revision=1`](../../api/convert/1/eft/eft.txt?revision=1)

* [`/api/convert/dna/clf/clf.json?input=608:564;3:2046:4029:5441:5597:5629:5971:222;240::`](../../api/convert/dna/clf/clf.json?input=608:564;3:2046:4029:5441:5597:5629:5971:222;240::)