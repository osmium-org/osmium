# Common API parameters

## Common to most API calls {#common}

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>GET</td><td>callback</td><td>string</td><td>no</td><td>any</td><td><em>none</em></td>
<td>If present, wrap the data in a JSON object and use the specified callback function (JSONP).</td></tr>

</tbody>
</table>


## Common to API calls returning JSON {#output-json}

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>GET</td><td>minify</td><td>bool</td><td>no</td><td>0, 1</td><td>1</td>
<td>Minify the outputted JSON. You should only disable minification for debugging.</td></tr>

</tbody>
</table>

## Common to API calls requiring a loadout {#input-loadout}

<table class='d'>
<thead>
<tr><th>Type</th><th>Name</th><th>Type</th><th>Mandatory?</th><th>Possible values</th><th>Default</th>
<th>Description</th></tr>
</thead>
<tfoot></tfoot>
<tbody>

<tr><td>URI or GET</td><td>source_fmt</td><td>string or integer</td><td>yes</td><td>any positive integer or clf, gzclf, evexml, eft, dna, autodetect</td><td>N/A</td>
<td>If an integer is given, assume it is a loadout ID. If a string value is given, assume the input is in the specified format.</td></tr>

<tr><td>GET or POST</td><td>input</td><td>string</td><td>yes/no</td><td>N/A</td><td>N/A</td>
<td>Mandatory unless a loadout ID is used in input_fmt. Specifier the source loadout to use. POST is preffered (and has precedence), unless the input loadout is a very short string.</td></tr>

<tr><td>GET</td><td>revision</td><td>integer</td><td>no</td><td>any</td><td><em>latest</em></td>
<td>If using a loadout from its ID, use this specific revision.</td></tr>

<tr><td>GET</td><td>privatetoken</td><td>integer</td><td>no</td><td>any</td><td>N/A</td>
<td>If using a private loadout from its ID, use this privatetoken (the number after <code>/private/</code> in the URI).</td></tr>

<tr><td>GET</td><td>password</td><td>string</td><td>no</td><td>any</td><td>N/A</td>
<td>If using a password-protected loadout from its ID, use this password.</td></tr>



<tr><td>GET</td><td>fleet</td><td>string</td><td>no</td><td>fleet, wing, squad</td><td>N/A</td>
<td>If using a loadout from its ID, use its fleet/wing/squad booster instead.</td></tr>

<tr><td>GET</td><td>preset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>Use this preset, when applicable.</td></tr>

<tr><td>GET</td><td>chargepreset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>Use this charge preset, when applicable.</td></tr>

<tr><td>GET</td><td>dronepreset</td><td>integer</td><td>no</td><td>any</td><td><em>first</em></td>
<td>Use this drone preset, when applicable.</td></tr>


</tbody>
</table>