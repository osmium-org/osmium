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

osmium_init_sources = function() {
	$('div#nlsources > section#search > form > ul.filters, div#nlsources > section#browse > ul.filters, div#nlsources > section#shortlist > ul.filters').each(function() {
		var t = $(this);
		var li, input, a;

		for(var id in osmium_metagroups) {
			li = $(document.createElement('li'));

			input = $(document.createElement('input'));
			input.prop('type', 'hidden');
			input.prop('name', 'mg[' + id + ']');
			input.val('1');
			input.data('metagroupid', id);
			li.append(input);

			a = $(document.createElement('a'));
			a.prop('href', 'javascript:void(0);');
			a.addClass('metagroup');
			a.addClass('mg' + id);
			a.text(osmium_metagroups[id]);
			a.prop('title', 'Filter ' + osmium_metagroups[id] + ' types');
			li.append(a);

			t.append(li);
		}
	}).find('a.metagroup').click(function() {
		var t = $(this).parent().children('input:first-child');
		if(t.val() === '1') {
			t.val('0');
		} else {
			t.val('1');
		}

		$(this).blur();
	});

	$('div#nlsources > section#search > form').submit(function() {
		var t = $(this);
		var ul, li, img, p;

		$.getJSON("../../src/json/search_types.php?" + t.serialize(), function(json) {
			ul = t.parent().children('ul.results');
			ul.empty();
			t.parent().children('p.warning').remove();

			for(var i in json.payload) {
				li = $(document.createElement('li'));
				li.addClass('module');
				li.data('typeid', json.payload[i][0]);
				li.data('category', json.payload[i][2]);
				li.data('subcategory', json.payload[i][3]);
				li.addClass('mg' + json.payload[i][4]);
				li.text(json.payload[i][1]);

				img = $(document.createElement('img'));
				img.prop('alt', '');
				img.prop('src', '//image.eveonline.com/Type/' + json.payload[i][0] + '_64.png');
				li.prepend(img);

				osmium_add_non_shortlist_contextmenu(li);

				ul.append(li);
			}

			if(json.warning !== null) {
				p = $(document.createElement('p'));
				p.addClass('placeholder');
				p.addClass('warning');
				p.html(json.warning);

				ul.after(p);
			}
		});

		return false;
	});

	osmium_add_metagroup_style("div#nlsources > section#browse > ", "div#nlsources > section#browse > div.mgroot");
	osmium_add_metagroup_style("div#nlsources > section#shortlist > ", "div#nlsources > section#shortlist > ul");

	osmium_init_browser();
	osmium_init_shortlist();
};

osmium_init_browser = function() {
	var section = $('div#nlsources > section#browse');
	var div, displaymg;

	div = $(document.createElement('div'));
	div.addClass('mgroot');

	var displaymg = function(parent, mg) {
		var ul, li, img, heading;

		if("children" in osmium_groups[mg] && osmium_groups[mg].children.length > 0) {
			ul = $(document.createElement('ul'));
			ul.addClass('children');

			for(var i in osmium_groups[mg].children) {
				li = $(document.createElement('li'));
				li.addClass('uninitialized');
				li.addClass('folded');
				li.data('mgid', osmium_groups[mg].children[i]);

				heading = $(document.createElement('h4'));
				heading.text(osmium_groups[osmium_groups[mg].children[i]].name);

				li.append(heading);
				ul.append(li);
			}

			parent.append(ul);
		}

		if("types" in osmium_groups[mg] && osmium_groups[mg].types.length > 0) {
			ul = $(document.createElement('ul'));
			ul.addClass('types');

			for(var i in osmium_groups[mg].types) {
				var type = osmium_types[osmium_groups[mg].types[i]];

				li = $(document.createElement('li'));
				li.addClass('module');
				li.data('typeid', type[0]);
				li.text(type[1]);
				li.data('category', type[2]);
				li.data('subcategory', type[3]);
				li.addClass('mg' + type[4]);

				img = $(document.createElement('img'));
				img.prop('src', '//image.eveonline.com/Type/' + type[0] + '_64.png');
				img.prop('alt', '');
				li.prepend(img);

				osmium_add_non_shortlist_contextmenu(li);

				ul.append(li);
			}

			parent.append(ul);
		}
	};
	displaymg(div, 'root');

	section.children('p.placeholder.loading').remove();
	section.append(div);

	section.on('click', 'li.uninitialized', function() {
		var t = $(this);
		t.removeClass('uninitialized');
		displaymg(t, t.data('mgid'));
	});

	section.on('click', 'li', function(e) {
		var t = $(this);

		t.toggleClass('folded');
		e.stopPropagation();

		if(t.parent().children('li:not(.folded)').length > 0) {
			t.parent().addClass('partiallyunfolded');
		} else {
			t.parent().removeClass('partiallyunfolded');
		}
	});
};

osmium_init_shortlist = function() {
	var ul, li, img, section;

	section = $('div#nlsources > section#shortlist');
	section.children('p.placeholder.loading').remove();

	ul = $(document.createElement('ul'));
	ul.addClass('types');

	for(var i in osmium_shortlist) {
		var type = osmium_types[osmium_shortlist[i]];

		li = $(document.createElement('li'));
		li.addClass('module');
		li.data('typeid', type[0]);
		li.data('category', type[2]);
		li.data('subcategory', type[3]);
		li.addClass('mg' + type[4]);
		li.text(type[1]);

		img = $(document.createElement('img'));
		img.prop('alt', '');
		img.prop('src', '//image.eveonline.com/Type/' + type[0] + '_64.png');
		li.prepend(img);

		osmium_add_shortlist_contextmenu(li);

		ul.append(li);
	}

	section.append(ul);
};

osmium_add_metagroup_style = function(aselector, liselector) {
	$(aselector + " ul.filters > li > a.metagroup").click(function() {
		var t = $(this).parent().children('input:first-child');
		var mg = t.data('metagroupid');
		var v = t.val();

		if(v === '1') {
			$('style.mg' + mg).remove();
		} else {
			var style = $(document.createElement('style'));
			style.prop('type', 'text/css');
			style.addClass('mg' + mg);
			style.text(liselector + ' li.module.mg' + mg + ' { display: none; }');
			$('head').append(style);
		}
	});
};

osmium_add_add_to_fit_option = function(menu, source, opts) {
	var name;
	var cat = source.data('category');

	if(cat === 'ship') name = 'Use ship';
	else if(cat === 'module') name = 'Fit module';
	else name = 'Add ' + cat;

	osmium_ctxmenu_add_option(menu, name, function() {
		osmium_add_to_clf(source);
	}, opts);
};

osmium_add_non_shortlist_contextmenu = function(li) {
	osmium_ctxmenu_bind(li, function() {
		var menu = osmium_ctxmenu_create();

		osmium_add_add_to_fit_option(menu, li, { default: true });
		osmium_ctxmenu_add_option(menu, "Add to shortlist", function() {
			if($('div#nlsources > section#shortlist > ul.types > li.module').filter(function() {
				return $(this).data('typeid') === li.data('typeid');
			}).length >= 1) {
				return;
			}

			var n = li.clone(true).unbind();
			osmium_add_shortlist_contextmenu(n);

			$('div#nlsources > section#shortlist > ul.types').append(n);
			osmium_commit_shortlist();
		}, {});

		return menu;
	});
};

osmium_add_shortlist_contextmenu = function(li) {
	osmium_ctxmenu_bind(li, function() {
		var menu = osmium_ctxmenu_create();

		osmium_add_add_to_fit_option(menu, li, { default: true });
		osmium_ctxmenu_add_option(menu, "Remove from shortlist", function() {
			li.remove();
			osmium_commit_shortlist();
		}, {});

		return menu;
	});
};

osmium_commit_shortlist = function() {
	var opts = {
		token: osmium_token
	};

	$("div#nlsources > section#shortlist > ul.types > li.module").each(function() {
		var t = $(this);
		opts[t.index()] = t.data('typeid');
	});

	$.get("../src/json/shortlist_modules.php", opts);
};
