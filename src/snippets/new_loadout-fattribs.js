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

argsh = function(x) {
	return Math.log(x + Math.sqrt(x*x + 1.0));
};

osmium_targeting_time = function(targeter_scanres, targetee_sigradius) {
	/* Formula taken from the official wiki */
	return 40000.0 / (targeter_scanres * Math.pow(argsh(targetee_sigradius), 2));
};

osmium_init_fattribs = function() {
	var t = "Scan resolution\n\nTime to lock…";
	var sr = parseFloat($("span#scan_resolution").data('value'));
	var sig = parseFloat($("p#signature_radius").data('value'));

	for(var i = 0; i < osmium_targetclass.length; ++i) {
		if(!osmium_targetclass[i][1]) continue;
		t += "\n" + osmium_targetclass[i][0] + ": "
			+ osmium_targeting_time(sr, osmium_targetclass[i][3]).toFixed(1) + " s"
	}

	$("span#scan_resolution").prop('title', t);

	t = "Signature radius\n\nTime to be locked by…";

	for(var i = 0; i < osmium_targetclass.length; ++i) {
		if(!osmium_targetclass[i][2]) continue;
		t += "\n" + osmium_targetclass[i][0] + ": "
			+ osmium_targeting_time(osmium_targetclass[i][4], sig).toFixed(1) + " s"
	}

	$("p#signature_radius").prop('title', t);
};
