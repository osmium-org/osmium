Type: page
Title: Osmium, the official meta homepage

<p style='text-align: center;'>
<a href='#about'>About</a> |
<a href='#goals'>Goals</a> |
<a href='#latest'>Releases</a> |
<a href='#links'>Links</a> |
<a href='#contact'>Contact</a> |
<a href='#screenshots'>Screenshots</a>
</p>

# About {#about}

![](files/img/logo_osmium.png) ![](files/img/agplv3.png)

**Osmium** is both a web-based fitter and a platform to share ship
loadouts for the EVE online game.

Osmium is licensed under the GNU Affero General Public License,
version 3. You can see the full license text in the
[`COPYING`](https://github.com/osmium-org/osmium/blob/master/COPYING)
file in the main repository.

Developers and people wanting to run a local copy of Osmium should
also read the
[`README`](https://github.com/osmium-org/osmium/blob/master/README.md)
file.

# Astero: the plug-and-play Osmium VM {#astero}

You want to fiddle with the Osmium source code in an isolated
environment? You don't want to spend hours installing and configuring
every daemon and dependency that Osmium requires?

Astero is a ready-to-use VM image that runs Osmium out of the
box. Works with QEMU, and should work with VirtualBox too.

Download the disk image here: [Dropbox link](https://www.dropbox.com/sh/om91kg9eqfdp85r/AADLQtu3xqCNbmxrL2tSqwI0a?dl=0)

# Project goals {#goals}

In order of descending priority:

* **Accuracy**: computed attributes and stats are very accurate,
    thanks to libdogma. You have access to every modifier that's being
    applied to anything to make it easy to verify or cross-reference
    the results. Because of how it is designed, libdogma also is very
    easy to update in the future.

* **Accessibility**: the website should be usable on phones, tablets,
    etc. and should also work with text-based browsers and screen
    readers, or users with special restrictions (like firewalled
    `eveonline.com` domain, etc.).

  The site should also play nicely with browser features such as page
  search, bookmarks and page refreshes (avoid the "Confirm form
  resubmission?" dialog).

* **User-friendliness**: since users never read documentation or text
    anyway, most of the interface should be as self-explanatory as
    possible and appear familiar to most EVE users.

# Latest release {#latest}

**See the [releases](https://github.com/osmium-org/osmium/releases) on GitHub.**

See the
**[changelog](https://github.com/osmium-org/osmium/blob/staging/src/md/changelog.md)**
for a recap of the new features.

Releases are tagged in the main repository, and the `production`
branch always points at the latest release suitable for use in a
production environment. Version tags should be signed by Artefact2 with the
[Osmium master
key](http://keys.gnupg.net/pks/lookup?op=vindex&fingerprint=on&search=0xFD5F9B4E7168F39E),
which is:

~~~
pub   4096R/7168F39E 2012-07-20 [expires: 2015-08-16]
      Key fingerprint = 25A5 8B3D 5F6F 19CF F561  1331 FD5F 9B4E 7168 F39E
uid       [ultimate] Romain Dalmaso (Osmium master key) <artefact2@gmail.com>
~~~

You can use `git tag -v` to check the authenticity of version tags.

# Links {#links}

* **[Live production version: https://o.smium.org/](https://o.smium.org/)**
* **[Changelog](https://github.com/osmium-org/osmium/blob/staging/src/md/changelog.md)**
* **[/r/osmium subreddit](http://reddit.com/r/osmium)**
* **[Report an issue](https://github.com/osmium-org/osmium/issues/new)**
* [Project repository on Github](https://github.com/osmium-org/osmium)
* [Forum thread on the EVE forums](https://forums.eveonline.com/default.aspx?g=posts&m=1630542#post1630542)
* [Chat on the `#osmium` IRC channel](http://irc.lc/coldfront/osmium/osmiumguest@@@)

# Contact {#contact}

* By email: <artefact2@gmail.com>
* On IRC: `#osmium` at `irc.coldfront.net` ([link for your IRC client](irc://irc.coldfront.net/#osmium), [link for your browser](http://irc.lc/coldfront/osmium/osmiumguest@@@))
