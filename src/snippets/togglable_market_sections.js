/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_togglify_lazy_load_images = function(mgroup) {
	mgroup.find('ul.types > li > div.imgplaceholder').filter(':visible').each(function() {
		var li = $(this).parent();
		var src = $(this).data('src');
		$(this).remove();
		li.prepend('<img src="' + src + '" alt="" />');
	});
};

osmium_togglify_market_sections = function(prefix, element) {
	element.on('click', 'h1, h2, h3, h4, h5, h6', function() {
		var mgroup = $(this).parent();
		var marketgroupid = mgroup.data('marketgroupid');
		var key = prefix + '_' + marketgroupid;

		if(mgroup.hasClass('hidden')) {
			mgroup.children('ul.subgroups, ul.types').fadeIn(500);
			mgroup.removeClass('hidden');
			localStorage.setItem(key, "1");
			osmium_togglify_lazy_load_images(mgroup);
		} else {
			mgroup.children('ul.subgroups, ul.types').hide();
			mgroup.addClass('hidden');
			localStorage.removeItem(key);
		}
	});

	element.find('div.mgroup div.mgroup').each(function() {
		var mgroup = $(this);
		var show = localStorage.getItem(prefix + '_' + mgroup.data('marketgroupid'));

		if(show === null) {
			mgroup.children('ul.subgroups, ul.types').hide();
			mgroup.addClass('hidden');
		} else if(show === "1") {
			osmium_togglify_lazy_load_images(mgroup);
		}
	});
};
