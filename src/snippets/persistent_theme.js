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

osmium_gettheme = function() {
	var links = document.getElementsByTagName("link");
	for(var i = 0; i < links.length; ++i) {
		if(links[i].getAttribute('title') && links[i].getAttribute('rel').indexOf('stylesheet') !== -1) {
			if(!links[i].disabled) return links[i].getAttribute('title');
		}
	}
};

osmium_settheme = function(title) {
	var links = document.getElementsByTagName("link");
	var foundit = false;

	for(var i = 0; i < links.length; ++i) {
		if(links[i].getAttribute('title') && links[i].getAttribute('rel').indexOf('stylesheet') !== -1) {
			links[i].disabled = true;
			if(links[i].getAttribute('title') === title) {
				foundit = true;
				links[i].disabled = false;
			}
		}
	}

	if(foundit) return;
	alert('not found');

	/* If the title was not found, enable the first sheet as a fallback */
	for(var i = 0; i < links.length; ++i) {
		if(links[i].getAttribute('title') && links[i].getAttribute('rel').indexOf('stylesheet') !== -1) {
			links[i].disabled = false;
			return;
		}
	}
};

osmium_setcookie = function(name, value, validityms) {
	var d = new Date();
	d.setTime(d.getTime() + validityms);
	document.cookie = name + "=" + value + "; expires=" + d.toGMTString() + "; path=/";
};

$(function() {
	var theme, label;
	if(osmium_gettheme() === 'Light') {
		theme = 'Dark';
		label = 'Paint it black';
	} else {
		theme = 'Light';
		label = 'Paint it white';
	}

	var rlink = $(document.createElement('a'));
	rlink.prop('id', 'repaint');
	rlink.data('theme', theme);
	rlink.text(label);
	rlink.click(function() {
		var theme, label, tts;
		if((tts = $(this).data('theme')) === 'Dark') {
			theme = 'Light';
			label = 'Paint it white';
		} else {
			theme = 'Dark';
			label = 'Paint it black';
		}

		osmium_setcookie('t', tts, 86400 * 7 * 1000);
		osmium_settheme(tts);
		$(this).data('theme', theme);
		$(this).text(label);
		$(this).blur();
	});

	$("div#wrapper + footer > p").append(' — ').append(rlink);
});
