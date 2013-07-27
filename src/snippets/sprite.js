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

osmium_sprite = function(alt, grid_x, grid_y, grid_width, grid_height, width, height) {
	var span = $(document.createElement('span'));
	var img = $(document.createElement('img'));

	span.addClass('mainsprite');
	span.css({
		width: width + 'px',
		height: height + 'px'
	});

	img.prop('src', osmium_relative + '/static-' + osmium_staticver + '/icons/sprite.png');
	img.prop('alt', alt);
	img.prop('title', alt);
	img.css({
		width: (width / grid_width * 1024) + 'px',
		height: (height / grid_height * 1024) + 'px',
		top: (-grid_x * width) + 'px',
		left: (-grid_y * height) + 'px'
	});

	span.append(img);
	return span;
};
