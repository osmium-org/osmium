Osmium contributor guidelines
=============================

If you want to contribute code to the project, please respect the
conventions below. **If you don't, your code will not be merged in the
main repository!**

Directory structure
-------------------

~~~~
bin/ - executable command-line scripts
cache/ - temporary cache files, not accessible via HTTP
inc/ - Osmium include files
lib/ - Non-osmium include files
pgsql/ - Database schemas, backups and patches
sphinx/ - search engine index and configuration
src/ - Osmium pages source files
static/ - static content like images, fonts, stylesheets, ... (accessible directory, not URL-rewritten)
static/cache/ - temporary cache files, accessible via HTTP
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

* If you absolutely need to use global variables (you should try not
  to), prefix them by `$__osmium_`, for example:
  `$__osmium_cache_stack`.

* If you need to define a constant, try to use `const CONSTANT =
  $value;` instead of `define('CONSTANT', $value)` if it is possible
  to do so.

DOM
---

Use DOM for creating XHTML or XML markup. DOM is safer and makes for
more robust code. Osmium extends the PHP DOM classes with many
convenience methods to make developement quicker.

DOM takes care of escaping entities when needed:

~~~
$document = new \Osmium\DOM\Page();
$p = $document->content->appendCreate('p');

$p->append('foo & bar');                        /* Safe */
$p->append('<![CDATA[ baz ]]>');                /* Safe */
$p->append('<script>alert("foo");</script>');   /* Safe */

$p->append($document->fragment(
	'<div>baz</div>'                        /* Unsafe */
));
~~~

DOM takes care of closing elements and always generates syntaxically valid XML.

~~~
/* Don't do this! */

echo "<ul id='somelist' class='foo bar'>";

foreach($foo as $baz) {
    echo "<li>".escape($baz)."</li>";
}

echo "</ul>";



/* Do this instead. */

$ul = $document->element('ul#somelist.foo.bar');

foreach($foo as $baz) {
    $ul->appendCreate('li', $baz);
}
~~~

In many cases, DOM makes for less cumbersome coding:

~~~
/* Don't do this! */

$m = isset($missing[$fit['ship']['typeid']]) ? ' missingskill' : '';
echo "<strong><span class='name{$m}'>".\Osmium\Chrome\escape($fit['ship']['typename'])."</span></strong>\n";



/* Do this instead. */

$span = $h1->appendCreate('strong')->appendCreate('span.name', $fit['ship']['typename']);

if(isset($missing[$fit['ship']['typeid']])) {
	$span->addClass('missingskill');
}
~~~

Osmium introduces "dynamic elements" which are only rendered at the
last minute, allowing for more dynamic elements:

~~~
/* o-form will automatically add a CSRF token and specify the accept-encoding value */
$form = $document->element('o-form', [ 'method' => 'post', 'action' => '/somepage' ]);

/* o-input will remember its value (unless remember attribute is off or value is overridden) */
$form->appendCreate('o-input', [ 'type' => 'text', 'name' => 'somefield' ]);

/* o-select will remember its value, also accepts selected attribute */
$select = $form->appendCreate('o-select', [ 'name' => 'someselect', 'selected' => 'three' ]);
$select->appendCreate('option', [ 'value' => 'one', 'Option one' ]);
$select->appendCreate('option', [ 'value' => 'two', 'Option two' ]);
$select->appendCreate('option', [ 'value' => 'three', 'Option three' ]);
~~~

**Dynamic elements must begin with `o-`.**

Since DOM is slower than directly echoing XHTML, it's good practice to
use templates when iterating over big datasets:

~~~
$rowtemplate = $document->element('tr');
$rowtemplate->appendCreate('th')->appendCreate('a');
$rowtemplate->appendCreate('td');

$tbody = $document->element('table')->appendCreate('tbody');
foreach($types as $typeid => $typename) {
    $row = $rowtemplate->cloneNode(true); /* Clone, don't recreate */

    $a = $row->firstChild->firstChild;
    $td = $row->lastChild;

    $a->setAttribute('href', '/db/type/'.$typeid);
    $a->append($typeid);
    $td->append($typename);
    $tbody->append($row);
}
~~~


Polyglot markup
---------------

Any HTML code you generate should validate as both XTHML5 and HTML5.

You can use http://validator.nu to check if your code is correct (your
code should validate with both the HTML5 parser and the XML parser).

Here are the most important gotchas to keep in mind:

* Never use the shorthand notation for attributes:

  ~~~~
  $input->setAttribute('required', '');                // No
  $input->setAttribute('required', 'required');        // Yes
  ~~~~

* When using tables, always explicitely insert the `<tbody>` tag (and
  optionally `<thead>` and `<tfoot>`), for example:

  ~~~~
  $table->appendCreate('tr')->appendCreate('td', 'foo');              // No

  $tbody = $table->appendCreate('tbody');
  $tbody->appendCreate('tr')->appendCreate('td', 'foo');              // Yes
  ~~~~

* If using Javascript, do not use `document.write()` (it is bad
  practice anyway, and XHTML5 forbids it).

See the full list at: http://dev.w3.org/html5/html-xhtml-author-guide/

Accessibility guidelines
------------------------

* Do not rely **only** on color to convey information (for color blind
  people).

* Do not rely on drag and drop, double-click or hover for essential
  functionality (not available on touchscreen devices).

* Always fill the `alt` attribute of an image. It can be empty if the
  image is purely decorational (or if it would be redundant with
  information already next to it).

Tracking database changes
-------------------------

Do not change the `eve` schema structure, it follows the structure of
the Eos dump.

If you make changes to the `osmium` schema (for example, adding a
table), always use the `bin/backup_osmium_schema` script before
commiting, and include the new schema in the commit. You must also
write a patch in `pgsql/patches/current/` that can be used to update a
production database (without having to drop then reinsert everything).

Getting your code merged
------------------------

You can either fork the repository, push your changes somewhere and
ask a developer to merge your changes, or use `git format-patch`.

See the contact section in the `README` for how to reach develpers.
