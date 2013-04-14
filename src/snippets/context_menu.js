/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_ctxmenu_bind = function(element, menu_constructor) {
	var indicator = $(document.createElement('span'));

	element.children('.contextmenuindicator').remove();
	indicator.text('≡');
	indicator.addClass('contextmenuindicator');

	element.append(indicator);

	var showmenu = function(e) {
		var menu = menu_constructor();

		div = $(document.createElement('div'));
		div.prop('id', 'ctxbg');

		div.bind('click contextmenu', function(e2) {
			$("ul#ctxmenu, div#ctxbg").remove();
			$(document.elementFromPoint(e2.pageX, e2.pageY)).trigger(e2);
			return false;
		});

		menu.click(function() {
			$("ul#ctxmenu, div#ctxbg").remove();
		});

		$('body').append(div).append(menu);

		var x = Math.min(e.pageX, $(document).width() - menu.width() - 5);
		var y = e.pageY;
		menu.css('left', x);
		menu.css('top', y);

		return false;
	};

	element.on('dblclick do_default_ctxmenu_action', function() {
		var menu = menu_constructor();
		menu.children('li.default').trigger('do_action');
	});

	element.on('contextmenu', showmenu);
	indicator.on('click', showmenu);

	element.addClass('hascontextmenu');
};

osmium_ctxmenu_create = function() {
	var ul = $(document.createElement('ul'));
	ul.prop('id', 'ctxmenu');

	return ul;
};

/* opts is a Hashtable that can accept the properties:
 * - icon: URI of the icon to show
 * - title: tooltip of this option (uses title attribute)
 * - enabled: whether this option is enabled or not (default yes)
 * - default: whether this option is the default when the element is double-clicked (default false)
 */
osmium_ctxmenu_add_option = function(menu, name, action, opts) {
	var li = $(document.createElement('li'));

	li.text(name);

	if("title" in opts) {
		li.prop('title', opts.title);
	}

	if("icon" in opts) {
		var img = $(document.createElement('img'));
		img.prop('alt', '');
		img.prop('src', osmium_relative + '/static-' + osmium_staticver + '/icons/' + opts.icon);
		img.addClass('icon');
		li.prepend(img);
	}

	if(("enabled" in opts) && !opts.enabled) {
		li.addClass('disabled');
	} else {
		li.on('do_action', function() {
			action();
		}).on('click', function() {
			$(this).trigger('do_action');
		});
	}

	if(("default" in opts) && opts.default && !li.hasClass('disabled')) {
		li.addClass('default');
	}

	menu.append(li);
};

osmium_ctxmenu_add_separator = function(menu) {
	var li = $(document.createElement('li'));
	li.addClass('separator');
	li.text(' ');
	menu.append(li);
}
