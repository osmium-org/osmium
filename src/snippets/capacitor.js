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
	var nbranches = Math.min(10, Math.max(2, Math.floor(capacity / 50.0)));
	var nbubbles = 3;
	var bubblecapacity = (capacity > 0) ? (capacity / (nbubbles * nbranches)) : 0;
	/* Avoid using the same IDs on different <svg> elements in the
	 * same page */
	var idsuffix = Math.random().toFixed(20).substring(2);

	var dark = $("head > link[title]").filter(function() {
		return $(this).get(0).disabled !== true
	}).first().prop('title') === 'Dark';

	var colors = {
		full: dark ? 'hsl(20, 85%, 95%)' : 'hsl(200, 85%, 35%)',
		empty: dark ? 'hsl(0, 0%, 35%)' : 'hsl(0, 0%, 65%)',
	};

	var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
	svg.setAttribute('viewBox', '-1 -1 2 2');
	svg.setAttribute('width', '32');
	svg.setAttribute('height', '32');

	var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');

	var f = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
	f.setAttribute('id', 'gbf-' + idsuffix);
	var gb = document.createElementNS('http://www.w3.org/2000/svg', 'feGaussianBlur');
	gb.setAttribute('stdDeviation', '0.02');
	defs.appendChild(f).appendChild(gb);

	for(var t in colors) {
		var lg = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
		lg.setAttribute('id', 'gradient-' + t + '-' + idsuffix);

		var stop;

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '0%');
		stop.setAttribute('stop-color', colors[t]);
		stop.setAttribute('stop-opacity', 0);
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '30%');
		stop.setAttribute('stop-color', colors[t]);
		stop.setAttribute('stop-opacity', 1);
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '70%');
		stop.setAttribute('stop-color', colors[t]);
		stop.setAttribute('stop-opacity', 1);
		lg.appendChild(stop);

		stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
		stop.setAttribute('offset', '100%');
		stop.setAttribute('stop-color', colors[t]);
		stop.setAttribute('stop-opacity', 0);
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
			bubble.setAttribute('filter', 'url(#gbf-' + idsuffix + ')');
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
	g.setAttribute('opacity', '0');
	g.appendChild(title);

	var r = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
	r.setAttribute('x', '-1');
	r.setAttribute('y', '-1');
	r.setAttribute('width', '2');
	r.setAttribute('height', '2');
	r.setAttribute('fill-opacity', '0');
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
