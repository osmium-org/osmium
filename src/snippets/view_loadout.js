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

osmium_commit_load = function(toggletype, toggleindex, toggledirection, 
							  transferdrone, transferquantity, transferfrom) {
	$("img#vloadoutbox_spinner").css('visibility', 'visible');

	var opts = {
		loadoutid: $('div#vloadoutbox').data('loadoutid'),
		pid: $('div#vloadoutbox').data('presetid'),
		cpid: $('div#vloadoutbox').data('cpid'),
		dpid: $('div#vloadoutbox').data('dpid'),
		toggletype: toggletype,
		toggleindex: toggleindex,
		toggledirection: toggledirection,
		transferdrone: transferdrone,
		transferquantity: transferquantity,
		transferfrom: transferfrom
	};

	$('div#vloadoutbox > div.slots.stateful > ul > li[data-state]').each(function() {
		opts[$(this).data('slottype') + $(this).data('index')] = $(this).data('state');
	});

	$('div#inspace > ul > li[data-count]').each(function() {
		var key = 'droneinspace' + $(this).data('typeid');
		var count = $(this).data('count');

		if(key in opts) opts[key] += count;
		else opts[key] = count;
	});

	$.getJSON('../src/json/view_loadout_alter.php', opts, function(json) {
		if($.type(json['preset']) === 'object') {
			$("div#vloadoutbox > div.slots > ul > li > span.charge").empty();

			for(var type in json['preset']) {
				for(var index in json['preset'][type]) {
					var charge = $("div#vloadoutbox > div.slots > ul > li").filter(function() {
						return $(this).data('slottype') == type && $(this).data('index') == index;
					}).find('span.charge');

					charge.append(',<br />');
					charge.append("<img src='http://image.eveonline.com/Type/" 
								  + json['preset'][type][index]['typeid'] + "_32.png' alt='' />");
					charge.append(json['preset'][type][index]['typename']);
				}
			}
		}

		if($.type(json['states']) === 'object') {
			for(var type in json['states']) {
				for(var index in json['states'][type]) {
					var li = $("div#vloadoutbox > div.slots > ul > li").filter(function() {
						return $(this).data('slottype') == type && $(this).data('index') == index;
					});
					li.data('state', json['states'][type][index]['state']);
					li.find('a.toggle')
						.attr('title', json['states'][type][index]['name'] + '; click to toggle')
						.find('img')
						.attr('alt', json['states'][type][index]['name'])
						.attr('src', '../static/icons/' + json['states'][type][index]['image']);
				}
			}
		}

		if($.type(json['ranges']) === 'object') {
			for(var type in json['ranges']) {
				for(var index in json['ranges'][type]) {
					var li = $("div#vloadoutbox > div.slots > ul > li").filter(function() {
						return $(this).data('slottype') == type && $(this).data('index') == index;
					});
					li.find('span.range').text(json['ranges'][type][index][0])
						.attr('title', json['ranges'][type][index][1]);
				}
			}
		}

		var used_bandwidth = 0;
		var total_bandwidth;
		$("div#inbay > ul, div#inspace > ul").empty();
		for(var i = 0; i < json['drones'].length; ++i) {
			var drone = json['drones'][i];
			if(drone['quantityinbay'] > 0) {
				$("div#inbay > ul").append(
					"<li data-typeid='"
						+ drone['typeid'] + "' data-count='"
						+ drone['quantityinbay'] + "'><img alt='' src='http://image.eveonline.com/Type/" 
						+ drone['typeid'] + "_32.png' />"
						+ drone['typename'] + " <strong>×"
						+ drone['quantityinbay'] + "</strong></li>");
			}
			if(drone['quantityinspace'] > 0) {
				$("div#inspace > ul").append(
					"<li data-typeid='"
						+ drone['typeid'] + "' data-count='"
						+ drone['quantityinspace'] + "'><img alt='' src='http://image.eveonline.com/Type/" 
						+ drone['typeid'] + "_32.png' />"
						+ drone['typename'] + " <strong>×"
						+ drone['quantityinspace'] + "</strong></li>");
				used_bandwidth += drone['quantityinspace'] * drone['bandwidth'], 10;
			}
		}

		osmium_drones_add_links();

		total_bandwidth = json['dronebandwidth'];
		$("span#dronebandwidth").text(used_bandwidth + " / " + total_bandwidth);
		if(used_bandwidth > total_bandwidth) {
			$("span#dronebandwidth").addClass('overflow');
		} else {
			$("span#dronebandwidth").removeClass('overflow');
		}
		
		if($("div#inbay > ul > li").length === 0) {
			$("div#inbay > ul").append("<li><em>(no drones in bay)</em></li>");
		}
		if($("div#inspace > ul > li").length === 0) {
			$("div#inspace > ul").append("<li><em>(no drones in space)</em></li>");
		}

		var meta = $("div#computed_attributes > section#vmeta");
		$("div#computed_attributes").html(json['attributes']).prepend(meta);
		osmium_fattribs_load();

		$("img#vloadoutbox_spinner").css('visibility', 'hidden');
	});
};

osmium_drones_add_links = function() {
	$("div#inbay > ul > li[data-typeid]").append(" <a href='javascript:void(0);' title='Launch 5 drones' class='movefivedrones'>⇉</a> <a href='javascript:void(0);' title='Launch one drone' class='moveonedrone'>→</a>");
	$("div#inspace > ul > li[data-typeid]").append(" <a href='javascript:void(0);' title='Return 5 drones to bay' class='movefivedrones'>⇇</a> <a href='javascript:void(0);' title='Return one drone to bay' class='moveonedrone'>←</a>");
};

$(function() {
	$('ul#vpresets > li > a').click(function() {
		$('ul#vpresets > li > a.active').removeClass('active');
		$(this).addClass('active');
		osmium_commit_load(null, null);
		return false;
	});

	$('div#vloadoutbox > div.slots.stateful > ul > li > a.toggle').click(function(obj) {
		osmium_commit_load($(this).parent().data('slottype'), $(this).parent().data('index'), true, null, null, null);
		obj.stopPropagation();
		obj.preventDefault();
		return false;
	}).bind('contextmenu', function(obj) {
		osmium_commit_load($(this).parent().data('slottype'), $(this).parent().data('index'), false, null, null, null);
		obj.stopPropagation();
		obj.preventDefault();
		return false;
	});

	$("div#inbay > ul").on('click', 'li > a.moveonedrone', function() {
		osmium_commit_load(null, null, null,
						   $(this).parent().data('typeid'), 1, 'bay');
	});
	$("div#inbay > ul").on('click', 'li > a.movefivedrones', function() {
		osmium_commit_load(null, null, null, $(this).parent().data('typeid'),
						   Math.min($(this).parent().data('count'), 5), 'bay');
	});
	$("div#inspace > ul").on('click', 'li > a.moveonedrone', function() {
		osmium_commit_load(null, null, null,
						   $(this).parent().data('typeid'), 1, 'space');
	});
	$("div#inspace > ul").on('click', 'li > a.movefivedrones', function() {
		osmium_commit_load(null, null, null, $(this).parent().data('typeid'),
						   Math.min($(this).parent().data('count'), 5), 'space');
	});

	$("div#inbay > ul, div#inspace > ul").sortable({
		receive: function() {
			osmium_commit_load(null, null, null, null, null, null);
		},
		items: '[data-typeid]',
		connectWith: 'div#inbay > ul, div#inspace > ul'
	});

	$("form.presets > span.submit").hide();
	$("form.presets > select").change(function() {
		$(this).parent().submit();
	});

	$("div#vcomments > div.comment > a.add_comment").click(function() {
		$(this).parent().find('ul.replies > li.new').fadeIn(500).find('textarea').focus();
	});

	osmium_drones_add_links();
});
