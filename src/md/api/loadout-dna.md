# DNA helpers

These are not really API calls per se, but they can be useful for
linking to loadouts from another page.



## `/loadout/dna/<dna_string>` {#view-loadout-dna}

View a loadout from a DNA string.

By default, the supplied DNA will be mangled into a smaller, slightly
altered version to make up for bad formatting. If your DNA setup
relies heavily on module ordering (like placing passive modules
between active modules for overheating), you can pass the `?mangle=0`
parameter to disable this behaviour.

Example: [`/loadout/dna/11381:438:2281:3074;4:5837:6160:9580:10190;2:26929:31788:23009;160::`](../../loadout/dna/11381:438:2281:3074;4:5837:6160:9580:10190;2:26929:31788:23009;160::)

## `/new/dna/<dna_string>` {#new-loadout-dna}

Create a new loadout from a baseline DNA.

Example: [`/new/dna/11381:438:2281:3074;4:5837:6160:9580:10190;2:26929:31788:23009;160::`](../../new/dna/11381:438:2281:3074;4:5837:6160:9580:10190;2:26929:31788:23009;160::)