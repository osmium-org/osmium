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

osmium_gen_capacitor = function(capacity, current) {
	if(capacity === null) capacity = 0;
	if(current === null) current = 0;

	var nbranches = Math.min(10, Math.max(2, Math.floor(capacity / 50.0)));
	var nbubbles = 3;
	var bubblecapacity = (capacity > 0) ? (capacity / (nbubbles * nbranches)) : 0;
	/* Avoid using the same IDs on different <svg> elements in the
	 * same page */
	var idsuffix = Math.random().toFixed(20).substring(2);

	var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
	svg.setAttribute('viewBox', '-1 -1 2 2');
	svg.setAttribute('class', 'capacitorwheel');

	var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');

	for(var t in { full: 1, empty: 1 }) {
		var lg = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
		lg.setAttribute('id', 'gradient-' + t + '-' + idsuffix);
		lg.setAttribute('class', t);

		var stop;

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '0%');
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '33%');
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '67%');
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '100%');
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
			var w = 0.2 + j * 0.12;
			bubble.setAttribute('width', w + '');
			bubble.setAttribute('height', '0.15');
			bubble.setAttribute('x', (-w / 2) + '');
			bubble.setAttribute('y', (-0.9 + 0.8 * (nbubbles - j - 1) / nbubbles) + '');
			bubble.setAttribute('rx', '0.075');
			bubble.setAttribute('ry', '0.075');
			bubble.setAttribute(
				'fill',
				(progress < current) ?
					('url(#gradient-full-' + idsuffix + ')')
					: ('url(#gradient-empty-' + idsuffix + ')')
			);
			g.appendChild(bubble);

			progress += bubblecapacity;
		}

		svg.appendChild(g);
	}

	var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
	svg.appendChild(g);

	var title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
	title.appendChild(document.createTextNode('Capacity: ' + capacity.toFixed(0) + ' GJ'));
	g.setAttribute('class', 'overlay');
	g.appendChild(title);

	var r = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
	r.setAttribute('x', '-1');
	r.setAttribute('y', '-1');
	r.setAttribute('width', '2');
	r.setAttribute('height', '2');
	g.appendChild(r);

	svg = $(svg);

	svg.data('capacity', capacity);
	svg.data('current', current);

	svg.on('redraw', function() {
		var t = $(this);
		t.replaceWith(
			osmium_gen_capacitor(t.data('capacity'), t.data('current'))
		);
	});

	return svg;
};
