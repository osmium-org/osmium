# Loadout formats

Osmium supports most of the commonly used formats for importing and
exporting loadouts. This page acts as a reference for other developers
wanting to implement these formats in their own programs.

*Note: some formats are poorly specified. Unless stated otherwise, all
 the information below is not authoritative.*

*Note: if you think this page is incomplete or has inconsistencies, or
 if you found a problem in Osmium regarding importing or exporting,
 please [contact the developers](../about#project).*

## Common Loadout Format (CLF) {#clf}

This is the recommended format for general use: storage, program
interoperability, etc. It has been designed to be resilient, easy to
parse, unambiguous and extendable. The format is [clearly
specified](http://artefact2.com/clf/spec-common-loadout-format-01).

### Non-standard extensions

The properties below are not standard, but not Osmium specific. Other
programs are encouraged to support them when applicable.

- `X-generatedby`: additional key in the root object. Contains a
  string that identifies the program that generated the CLF.

  Osmium will generate it while exporting, and ignore it while
  importing.

- `X-tags`: additional key in the `metadata` object. Contains an array
  of tags.

  Osmium will generate the array when exporting, and will import the
  tags when importing, but some tag names may be mangled, and the
  array may be truncated if it contains too many tags.

  In Osmium, a tag name must only contain lower-case letters, numbers,
  and dashes (-). If a tag doesn't match this description, it will:

  - be [normalized](http://unicode.org/reports/tr15/) to the KD
  form;
  - have its special characters will be transliterated if possible to
  their plain ASCII representation (for example, æ will become ae, é
  will become e, etc.);
  - have any underscores converted to dashes;
  - be converted to lowercase;
  - maybe be replaced by an alias to minimize the amount of similar
  tags (for example, brawler may be replaced by short-range, etc.).

- `X-damage-profile`: additional key in the root object. If present,
  it contains an array of two elements. The first element is a string
  which contains the name of the damage profile. The second element is
  another array which contains the damage profile itself: it is an
  array of 4 values, each of them should be between 0 and 1, with at
  least one non-zero, and they should add up to 1. The first element
  is the proportion of EM damage, the second of Explosive damage, the
  third of Kinetic damage, the fourth of Thermal damage.

  Osmium will generate this key while exporting. The only exception is
  when a minified CLF is requested and when the default uniform damage
  profile is being used. The key will be used while importing, unless
  the damage profile is invalid. If invalid or not present, the
  uniform profile will be used.

- `X-sideeffects`: additional key in a booster element. If present, it
  contains an array of effect IDs representing the side effects of the
  booster to enable.

  Osmium will generate it when exporting if some side effects have
  been enabled by the user, and will restore them when importing.

- `X-mode`: additional key in a preset element. If present, it is an
  object with two keys, `typeid` and `typename` specifying the mode of
  the ship for this preset.

### Osmium-specific extensions

The properties below are not standard and are Osmium specific. They
are described here for documentation purposes. In most cases, they are
not included by default when exporting, but are used internally for
client-server communication.

- `X-Osmium-loadouthash`, `X-Osmium-loadoutid`, `X-Osmium-revision`,
  `X-Osmium-view-permission`, `X-Osmium-edit-permission`,
  `X-Osmium-visibility`, `X-Osmium-password-mode`,
  `X-Osmium-hashed-password`, `X-Osmium-update-reason`: additional
  keys in the metadata object. Self-explanatory.

- `X-Osmium-current-presetid`, `X-Osmium-current-chargepresetid`,
  `X-Osmium-current-dronepresetid`: additional keys in the root
  object. Self-explanatory.

- `X-Osmium-capreloadtime`, `X-Osmium-dpsreloadtime`,
  `X-Osmium-tankreloadtime`: additional keys in the metadata
  object. They each contain a boolean value, which indicates whether
  reloading time of modules should be used in capacitor, DPS or
  sustained tank calculations.

- `X-Osmium-skillset`: additional key in the metadata
  element. Contains the name of the skillset being used. Can be "All
  V", "All 0", or the name of an account-specific character.

- `X-Osmium-fleet`: additional key in the root object. It contains an
  object with three keys, each optional: `fleet`, `wing` and
  `squad`. Each of those keys contains another object with two
  optional keys:

  - `skillset`: plays the same role as `X-Osmium-skillset`, but it has
    precedence over it. If empty or not present, "All V" is assumed.

  - `fitting`: it contains a string containing a valid remote fit (see
    [Remote format](#remote)), to be used as the fleet, wing or squad
    booster for the parent CLF. If empty or not present, a blank
    fitting is assumed.

- `X-Osmium-remote`: additional key in the root object. It contains an
  object where the keys can take any string value other than `local`,
  and the values are full CLF objects. It defines a remote fitting,
  which can be used as a source (or target) for projecting modules.

  The CLF of the remote fitting can also contain the `skillset` and
  `fitting` elements in its root object, and they play the same role
  than described in `X-Osmium-fleet` (and `fitting` has precedence
  over the CLF fitting itself).

- `X-Osmium-target`: additional key in a module element. If present,
  it contains either the string `local` or the key of a fitting in
  `X-Osmium-remote` and indicates that this module should be projected
  on either the root CLF fitting (if `local`) or the corresponding
  remote fitting in `X-Osmium-remote`.

## gzCLF {#gzclf}

gzCLF is a variant of CLF. It only contains "regular" characters (as
it is Base64-encoded) so it is safe for email transmission, or for
embedding in other formats which allow arbitrary text. There are two
variants of gzCLF: armored and raw. The only difference is in the
formatting.

### Raw gzCLF {#gzclf-raw}

Raw gzCLF is a Base64-encoded, [ZLIB-compressed (RFC
1950)](http://www.ietf.org/rfc/rfc1950.txt), optionally minified CLF
string.

~~~
    $raw_gzclf = base64_encode(gzcompress($clf)); /* For encoding */
	$clf = gzuncompress(base64_decode($gzclf)); /* For decoding */
~~~

### Armored gzCLF {#gzclf-armored}

Armored gzCLF is raw gzCLF with added delimiters to make
identification and parsing easier. It follows the following ABNF
grammar:

~~~
    armored-gzclf = "BEGIN gzCLF BLOCK" raw-gzclf "END gzCLF BLOCK"
	raw-gzclf     = *( ALPHA / DIGIT / "+" / "/" / whitespace )
	whitespace    = SP / HTAB / CR / LF
~~~

Since Base64-decoding will ignore whitespace, the raw gzCLF string can
be neatly indented and formatted in fixed-width lines. Osmium will do
so by default.

## Ship DNA {#dna}

The ship DNA format has the advantages of being compact and understood
by the game client. Unfortunately it is very poorly documented.

### Strict DNA {#dna-strict}

This is the format used and understood by the game client, and most
programs. It uses the following ABNF grammar:

~~~
    dna-string     = typeid *( ":" typeid ";" quantity ) "::"
    typeid         = non-zero-digit *( digit )
    quantity       = 1*( digit )
    non-zero-digit = "1" / "2" / "3" / "4" / "5" / "6" / "7" / "8" / "9"
    digit          = "0" / non-zero-digit
~~~

The first type ID must be a ship type ID, and will be used as
such. The following type IDs are modules, charges or drones IDs with a
mandatory quantity.

When exporting DNA, Osmium will generate strict DNA. When importing
DNA, Osmium will parse it as augmented DNA (the algorithm is
documented below).

When viewing a fitting from a DNA string, Osmium will mangle the
supplied DNA (mostly convert it to augmented DNA) and issue
redirect. For more details, see the [API](../api#view-loadout-dna)
page.

### Augmented DNA {#dna-augmented}

This is an improved DNA variant, used by Osmium. It is retrocompatible
with strict DNA if it is well-formed. It uses the following ABNF grammar:

~~~
    augmented-dna-string = 1*( typeid *( ";" quantity ) ":" ) 1*( ":" )
    typeid               = non-zero-digit *( digit )
    quantity             = 1*( digit )
    non-zero-digit       = "1" / "2" / "3" / "4" / "5" / "6" / "7" / "8" / "9"
    digit                = "0" / non-zero-digit
~~~

In many ways, this format is more resilient to errors, more compact
(quantities are optional) and more flexible (no particular order is
imposed) than strict DNA. Here is how the format is parsed:

- The input is first tokenized in "typeid;quantity" pairs;

- Every pair is processed in order:

  - If the pair has a quantity of 0, move on to the next pair;

  - If the pair has no quantity specified, assume a quantity of 1;

  - If the type ID is a ship type ID, use this ship (replace any other
    ship by this one if applicable) and ignore the quantity;

  - If the type ID is a module type ID, append *quantity* modules to
    the fitting;

  - If the type ID is a charge type ID, try to find *quantity* modules
    without fitted charges which can use this charge, and add it to
    them. If there is not enough modules to fit all the charges,
    discard the rest;

  - If the type ID is a drone type ID, add *quantity* drones to the
    fitting. Try adding them first in space, if the current ship has
    enough bandwidth and if the maximum number of drones in space has
    not yet been reached. If not, add them to the drone bay;

  - If the type ID is an implant (or booster) type ID, add *one*
    implant (or booster) to the fitting. Osmium will refuse to add the
    same implant (or booster) multiple times.

## Remote format {#remote}

Technically not a real format, the remote format is used by Osmium to
describe remote fittings (used to define fleet boosters, or remote
projected fittings).

When exporting a fitting to the remote format, Osmium will preferably
use augmented DNA, unless:

- The fitting has more than one preset (same with charge or drone
  presets);
- The fitting has fleet boosts;
- The fitting has remote fittings;
- The fitting has boosters with side effects.

If either of these conditions are verified, Osmium will use the
`gzclf://` form to avoid losing information.

When importing a fitting from the remote format, Osmium will proceed
as follows:

- If the input string matches an augmented DNA string, parse the
  string as [augmented DNA](#dna-augmented);

- If the input string starts with `gzclf://`, this prefix is removed
  and the rest is parsed as [raw gzCLF](#gzclf-raw);

- If the input string looks like the URL of a local lodaout, use this
  loadout.

Finally, the resulting fitting will be stripped of its non-selected
presets and of its metadata.
