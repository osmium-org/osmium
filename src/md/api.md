# Osmium API

## Guidelines {#guidelines}

The Osmium API is for developers and enthusiasts who want to integrate
Osmium in their website or application. Usage is open for all provided
you follow the following simple rules:

* Do not hammer the server with API requests. Follow the cache timers
  (indicated by the `Expires:` headers) religiously, and, if you must
  do several requests in a row, try to wait a few seconds between each
  request. If you start getting 403 or 503 errors for extended periods
  of time, it's probably because you failed to apply this rule.

* If you can, send a "Accept-Encoding: gzip" header to help save
  bandwidth.

* Set a custom User-Agent which identifies your application and adds a
  way to contact you. If you don't and your application starts
  misbehaving, there will be no way to contact you and your
  IP(s) will be blacklisted. Example:

  ~~~
  MyApplication/1.0.42 (+http://example.org/contact)
  ~~~




## API call list

* **[Common parameters](./api/common): parameters common to most API
    calls. Read this first!**

* [DNA helpers](./api/loadout-dna): create/view a loadout from a DNA
  string.

* [Convert](./api/loadout-convert): convert (or export) loadouts from any supported format to
  any supported format.

* [Query](./api/loadout-query): search (or browse) loadouts.

* [Attributes](./api/loadout-attributes): retrieve some computed
  attributes of a loadout. Poor man's dogma engine.
