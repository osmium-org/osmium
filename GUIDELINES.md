Osmium contributor guidelines
=============================

If you want to contribute code to the project, please respect the
conventions below. **If you don't, your code will not be merged in the
main repository!**

Directory structure
-------------------

~~~~
bin/ - executable command-line scripts
cache/ - temporary cache files
inc/ - Osmium include files
lib/ - Non-osmium include files
sphinx/ - search engine index and configuration
src/ - Osmium pages source files
static/ - static content like images, fonts, stylesheets, ... (accessible directory, not URL-rewritten)
tests/ - PHPUnit tests
~~~~

Copyright notices
-----------------

This goes without saying, but by contributing code you implicitely
agree to place it under the GNU AGPL version 3 (or later).

To keep things consistent, please place the following copyright notice
at the top (for PHP files, at line 2, just after `<?php`, or line 1
for Javascript files) of any source file you create:

~~~~
/* Osmium
 * Copyright (C) YEAR NAME <EMAIL>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
~~~~

Replace `YEAR`, `NAME` and `EMAIL` appropriately. You can use multiple
years or range of years, separated by a comma (for example `2006,
2008-2010, 2012`).

If you make **non-trivial changes** to an existing source file, add
your name, year and email under the existing names, for example:

~~~~
/* Osmium
 * Copyright (C) 2012 Name1 <email1@example.org>
 * Copyright (C) 2012 Name2 <email2@example.org>
 * ...
 *
 * This program is free software: ...
 */
~~~~

Taking credit for your work
---------------------------

If you made non-trivial changes to the codebase, please add your name
to the `CREDITS` file. The format of entries is specified in the file
itself.

Also edit the `.mailcap` file appropriately if you made commits using
multiple different email addresses (you can test your results by
running `git shortlog`).

File naming conventions
-----------------------

* Lower case only.

* `some_file_name.php` or `somefilename.php` style is OK.

* If you want to split a big file in multiple smaller file that each
  belong to the same namespace, do as follows (change `section1`,
  `section2`, ... appropriately):

  ~~~~
  Before: foo.php
  After: foo.php, foo-section1.php, foo-section2.php, etc...
         foo-section1.php, foo-section2.php, ... are required in foo.php
  ~~~~

Code conventions
----------------

* Use a K&R-like brace style, for example:
  ~~~~
  /* OK */
  function foo($x, $y) {
      if($x) {
          bar();
      } else {
          baz();
      }
  }

  /* Not OK - don't do this! */
  function foo($x, $y)
  {
      if($x)
      {
          bar();
      }
      else
      {
          baz();
      }
  } 
   ~~~~

* Please always put Osmium code in the `\Osmium` namespace (with
  sub-namespaces if needed).

* Indent with tabs. Use spaces for inline pretty alignment if
  necessary. See: http://www.emacswiki.org/SmartTabs

* Never, ever, EVER indent with spaces!

* Function names should use the `a_function_name()` convention (the
  only exception is tests where you should use
  `testSomethingWorksAsExpected`). Variable names can use either
  `$a_variable_name` or `$avariablename`.

* If your function prints something to the standard output, please
  prefix its name by `print_`, for example:
  `print_login_or_logout_box()`.

* If you absolutely need to use global variables (you should try not
  to), prefix them by `$__osmium_`, for example:
  `$__osmium_cache_stack`.

* If you need to define a constant, try to use `const CONSTANT =
  $value;` instead of `define('CONSTANT', $value)` if it is possible
  to do so.

* If your code prints or generates HTML code, make sure you write
  polyglot markup (see
  http://dev.w3.org/html5/html-xhtml-author-guide/). In particular, do
  not use the shorthand notation for attributes (`<input type='text'
  required='required' />` instead of `<input type='text' required
  />`), always specify closing tags (and use the `/>` shorthand for
  void elements like `img`, `br` etc.) and do not use named entitiy
  references other than `amp`, `lt`, `gt`, `apos` and `quot`.

Getting your code merged
------------------------

You can either fork the repository, push your changes somewhere and
ask a developer to merge your changes, or use `git format-patch`.

See the contact section in the `README` for how to reach develpers.
