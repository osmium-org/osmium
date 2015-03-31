/* Osmium
 * Copyright (C) 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

$(function() {
	$("div#wrapper").css({
		transform: "rotate(180deg)",
	});
	$(window).scrollTop($(document).height());

	var tn = [];
	var imn = [];
	
	var traverse = function(node) {
		if(node.nodeType === 3) {
			tn.push(node);
			return;
		}

		if(node.nodeType === 1 && node.nodeName === 'img' && node.src.match(/image\.eveonline\.com/)) {
			imn.push(node);
			return;
		}

		if(node.hasChildNodes()) {
			for(var i = 0, max = node.childNodes.length; i < max; ++i) {
				traverse(node.childNodes[i]);
			}
		}
	};
	traverse(document.getElementById('wrapper'));

	setInterval(function() {
		var r = Math.floor(Math.random() * 1000000000);
		var n = tn[r % tn.length];
		var i = imn[r % imn.length];

		var oldData = n.data;
		var oldSrc = i.src;
		
		n.data = 'ĮS̨͘H͞͏T҉͘AR͡';
		i.src = '//image.eveonline.com/Render/12005_512.png';

		setTimeout(function() {
			n.data = oldData;
			i.src = oldSrc;
		}, 400);
	}, 500);
});
