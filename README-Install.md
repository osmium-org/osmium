Installing Osmium
=================

This guide assumes you are running your webserver under the `http`
user and `http` group. You may need to change these in the commands
below (for example, Debian uses `www-data`).

1. Clone the repository, switch to the latest stable release and fetch
   the submodules:

   ~~~~
   git clone git://github.com/osmium-org/osmium.git
   cd osmium
   git checkout production
   git submodule init
   git submodule update
   ~~~~

   *From now on, you can run the `./bin/sanity_check` script to see
    which things need to be installed or configured.*

2. Make the cache directories writeable by your webserver:

   ~~~~
   chgrp http cache static/cache
   chmod g+rwx cache static/cache
   ~~~~

3. Copy the `config-example.ini` file to `config.ini`, and tweak the
   settings in `config.ini` to your liking.

   Do the same for `static/robots-example.txt`.

4. Install the depedencies listed below.

5. Install the PostgreSQL schema: see the "Initial database setup"
   section below.

6. Install Sphinx: see the "Initial Sphinx setup" section below.

7. Run `./bin/sanity_check`. At this point, you should not have any
   fatal issues (marked in red).

8. Run `make`. This will generate the stylesheets, the static client
   data, and generate other static cache. The first run may take some
   time.

9. Setup the cron jobs (they will renice their CPU/IO priority, no
   need to do it yourself):

   ~~~~
   sudo crontab -e -u http

   @hourly /path/to/osmium/bin/cron.hourly >/dev/null
   @daily  /path/to/osmium/bin/cron.daily  >/dev/null
   ~~~~

10. Configure your HTTP server. Check the `ext/httpd-conf/` directory
  for configuration examples of popular web servers. If yours is not
  in the list, or you want to tweak the configuration yourself, here
  is how Osmium assumes your web server will proceed:

  - Any URL that does **not** match `^/(src/|static)` is aliased (or
    "sent to") to `/src/dispatch.php`.

  - Any URL that matches `^/static-([1-9][0-9]*)/` is aliased to
    `/static/` (for example, '/static-2/foo/bar.png' is an alias of
    `/static/foo/bar.png`).

  - For optimal performance, it is recommended to set an expiration
    date for static files far in the future, and set `Cache-Control`
    to `public`. Static files all have URIs that match
    `^/static(-[1-9][0-9]*)?/`.

  - To minimize bandwidth usage, be sure to enable gzip compression
    (especially on all the files (except images) under static/).

  - For pretty error pages, you can use
    `/src/fatal.php?code=<HTTP_CODE>&message=<TEXT>`.

11. Start your webserver and browse to it. You should see the Osmium
  main page. If not, check the error logs and re-run
  `./bin/sanity_check` for things you may have missed.

12. (Optional) Create an account then give yourself moderator status:

	~~~~
	psql osmium osmium_user

	UPDATE osmium.accounts SET ismoderator = true WHERE accountname = 1
	~~~~

	(If you are not the first user creating an account, you can see
	your account ID by going on your profile page and looking at the
	number in the URL.)

13. (Optional) If you want to fetch killmails from Eve-Kill, you will
    have to run the `bin/eve_kill_stomp_client` script as a daemon. A
    systemd unit file and a supervisor configuration example are in
    `ext/`.

Dependencies
============

* PHP >= 5.4, with:
  * [dogma extension](https://github.com/osmium-org/php-dogma), using libdogma 1.1.x (`dogma.so`)
  * PostgreSQL extension (`pgsql.so`)
  * MySQLi extension (`mysqli.so`)
  * cURL extension (`curl.so`)
  * SimpleXML and DOM support (enabled by default)
  * Zlib support
  * (Optional) Semaphores support (`sysvsem.so`)
  * (Optional) iconv extension (`iconv.so`)
  * (Optional) intl extension (`intl.so`)
  * (Optional) APC (or APCu for PHP >= 5.5) extension (`apc.so`)
  * (Optional) Stomp extension (`stomp.so`)

* PostgreSQL >= 9.0

* Sphinx search server >= 2.0.4 
  (using the `sphinx.conf` from the `sphinx/` directory)

* HTMLPurifier >= 4.6.0 (PEAR package, see http://htmlpurifier.org/download#PEAR)

* Sass >= 3.2 (http://sass-lang.com/)

* (Optional) A Javascript minifier (UglifyJS is recommended)
  **(heavily recommended for production)**

* (Optional) Horde_Text_Diff+Horde_Autoloader PEAR packages, see
  http://pear.horde.org **(heavily recommended for production)**

* (Optional) PHPUnit, for automated tests

* (Optional) Xdebug, for debugging and code coverage reports

Initial database setup
======================

Assuming your PostgreSQL database name is `osmium` and it is owned by
the `osmium_user` user, follow these steps:

1. Get the latest Osmium static data dump (see `README-Datadump.md`
   for instructions)

2. Import the Osmium static dump:

    ~~~~
    xzcat osmium-sde-*.sql.xz | psql osmium osmium_user
    ~~~~

3. Import the Osmium tables/views:

    ~~~~
    psql osmium osmium_user
    \i pgsql/osmium.sql
    ALTER DATABASE osmium SET search_path TO osmium,eve;    
    ~~~~

Initial Sphinx setup
====================

1. Make a sphinx.conf file by running `./bin/make_sphinx_conf`.

2. Generate the type search index:

   ~~~~
   (stop searchd)
   cd /path/to/sphinx.conf
   indexer osmium_types
   (start searchd)
   ~~~~

   *Note: on some systems, the `indexer` binary is called
   `sphinx-indexer`. See your system packaging of Sphinx for more
   details.*

3. ~~~~
   make post-eve-schema-update
   ~~~~
