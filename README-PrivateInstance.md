Private Osmium instances
========================

Private Osmium instances are targeted to corporations, alliances,
coalitions or other groups of people who want to have their own
private Osmium instance for increased security and convenience.

There are several features in Osmium to help you achieve that.



Whitelist mode
--------------

In this mode, only a predefined list of characters, corporations and
alliance are allowed to use the site. You can enter entity IDs by hand
in the configuration file, or use contact lists of characters or
corporations to allow access to certain contacts too.

Pros:

* *Safe* if done correctly (be wary with contactlists, you
    implicitely grant anyone who can set alliance/corporation contacts
    the right to allow anyone they want to access the site).

* *Simple for admins*: no coding involved, just changing the
   `config.ini` file.

Cons:

* *End users must API-verify*: this makes registration not very
   user-friendly.

* *Relies on the API*: if the API server goes down, nobody will be
   able to log in.



Delegated authentication
------------------------

This is a feature which lets you bypass the traditional
registration/login system of Osmium, and replace it by potentially any
other authentication system you already have (like alliance forums,
etc.).

Pros:

* *Safe*: as safe as the auth system you're delegating to.

* *Simple for end users*: they just have to reuse the same credentials
   (like alliance forum credentials), and it will just work.

Cons:

* *Batteries not included*: you have to write the authentication
   bridge yourself. Some examples are provided in
   `ext/delegated-auth`. They are not covered by the AGPL, so you can
   alter them as you want for you particular system without having to
   make the changes public.

Writing a bridge for delegated authentication
---------------------------------------------

A bridge is a program called whenever someone tries to log in.

The user credentials are passed to the program via standard input as a
JSON object with two keys: `accountname` and `password`.

Here's a PHP example which retrieves then prints the credentials:

~~~
<?php

$inputjson = file_get_contents("php://stdin");
$input = json_decode($inputjson);

echo "User entered:\n";
echo "Account name: ".$input->accountname."\n";
echo "Password: ".$input->password."\n";
~~~

The bridge should send the authentication results back to Osmium by
outputting JSON to the standard output.

If the bridge exits with a nonzero return code, Osmium will interpret
is as failure regardless of output.

The output should be a JSON object, which can have the following keys:

* `nickname`: optional string, the display name of the user.

* `characterid`: optional integer, the characterID of the user's
  character.

* `isfittingmanager`: optional boolean. If true, the user's character
  has the fitting manager corporation role. Defaults to false if
  unspecified.

The output should have at least `nickname` and `characterid` present.
