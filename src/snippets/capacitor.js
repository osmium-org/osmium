/* Osmium
 * Copyright (C) 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_gen_capacitor = function(capacity, usage) {
	var nbranches = Math.min(10, Math.max(2, Math.floor(capacity / 50.0)));
	var nbubbles = 3;
	var bubblecapacity = (capacity > 0) ? (capacity / (nbubbles * nbranches)) : 0;

	var dark = $("head > link[title]").filter(function() {
		return $(this).get(0).disabled !== true
	}).first().prop('title') === 'Dark';
	var mainbubblefullcolor = dark ? '#FDEDE5' : '#77400D';
	var sidebubblefullcolor = dark ? '#77400D' : '#FDEDE5';

	var colors = {
		full: {
			main: [ dark ? '#FDEDE5' : '#77400D', '1' ],
			side: [ dark ? '#77400D' : '#FDEDE5', '0.8' ]
		},
		empty: {
			main: [ '#888888', '0.8' ],
			side: [ dark ? '#444444' : '#CCCCCC', '0.6' ]
		},
	};

	var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
	svg.setAttribute('viewBox', '-1 -1 2 2');
	svg.setAttribute('width', '32');
	svg.setAttribute('height', '32');

	var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');

	var f = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
	f.setAttribute('id', 'gbf');
	var gb = document.createElementNS('http://www.w3.org/2000/svg', 'feGaussianBlur');
	gb.setAttribute('stdDeviation', '0.02');
	defs.appendChild(f).appendChild(gb);

	for(var t in colors) {
		var lg = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
		lg.setAttribute('id', 'gradient-' + t);

		var stop;

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '0%');
		stop.setAttribute('stop-color', colors[t].side[0]);
		stop.setAttribute('stop-opacity', colors[t].side[1]);
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '40%');
		stop.setAttribute('stop-color', colors[t].main[0]);
		stop.setAttribute('stop-opacity', colors[t].main[1]);
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '60%');
		stop.setAttribute('stop-color', colors[t].main[0]);
		stop.setAttribute('stop-opacity', colors[t].main[1]);
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '100%');
		stop.setAttribute('stop-color', colors[t].side[0]);
		stop.setAttribute('stop-opacity', colors[t].side[1]);
		lg.appendChild(stop);

		defs.appendChild(lg);
	}

	svg.appendChild(defs);

	var progress = 0;
	for(var i = 0; i < nbranches; ++i) {
		var angle = -360 * i / nbranches;
		var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		g.setAttribute('transform', 'rotate(' + angle + ' 0 0)');

		for(var j = 0; j < nbubbles; ++j) {
			var bubble = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
			var w = 0.2 + j * 0.07;
			bubble.setAttribute('width', w + '');
			bubble.setAttribute('height', '0.15');
			bubble.setAttribute('x', (-w / 2) + '');
			bubble.setAttribute('y', (-0.9 + 0.8 * (nbubbles - j - 1) / nbubbles) + '');
			bubble.setAttribute('rx', '0.075');
			bubble.setAttribute('ry', '0.075');
			bubble.setAttribute('filter', 'url(#gbf)');
			bubble.setAttribute('fill', (progress < usage) ? 'url(#gradient-full)' : 'url(#gradient-empty)');
			g.appendChild(bubble);

			progress += bubblecapacity;
		}

		svg.appendChild(g);
	}

	svg = $(svg);

	svg.data('capacity', capacity);
	svg.data('usage', usage);

	svg.on('redraw', function() {
		var t = $(this);
		t.replaceWith(
			osmium_gen_capacitor(t.data('capacity'), t.data('usage'))
		);
	});

	return svg;
};
