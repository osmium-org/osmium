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
		revision: $('div#vloadoutbox').data('revision'),
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
								  + json['preset'][type][index]['typeid'] + "_64.png' alt='' />");
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
						.attr('src', '../static-' + osmium_staticver + '/icons/' + json['states'][type][index]['image']);
				}
			}
		}

		if($.type(json['ranges']) === 'object') {
			for(var type in json['ranges']) {
				for(var index in json['ranges'][type]) {
					var li = $("div#vloadoutbox > div.slots > ul > li").filter(function() {
						return $(this).data('slottype') == type && $(this).data('index') == index;
					});

					var fullrange = $(document.createElement('div')).html(json['ranges'][type][index][1]).text();
					li.find('span.range').text(json['ranges'][type][index][0])
						.attr('title', fullrange);
				}
			}
		}

		var used_bandwidth = json['usedbandwidth'];
		var total_bandwidth;
		$("div#inbay > ul, div#inspace > ul").empty();
		for(var i = 0; i < json['drones'].length; ++i) {
			var drone = json['drones'][i];
			if(drone['quantityinbay'] > 0) {
				$("div#inbay > ul").append(
					"<li data-typeid='"
						+ drone['typeid'] + "' data-count='"
						+ drone['quantityinbay'] + "'><img alt='' src='http://image.eveonline.com/Type/" 
						+ drone['typeid'] + "_64.png' />"
						+ drone['typename'] + " <strong>×"
						+ drone['quantityinbay'] + "</strong></li>");
			}
			if(drone['quantityinspace'] > 0) {
				$("div#inspace > ul").append(
					"<li data-typeid='"
						+ drone['typeid'] + "' data-count='"
						+ drone['quantityinspace'] + "'><img alt='' src='http://image.eveonline.com/Type/" 
						+ drone['typeid'] + "_64.png' />"
						+ drone['typename'] + " <strong>×"
						+ drone['quantityinspace'] + "</strong></li>");
			}
		}

		osmium_drones_load();

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

osmium_drones_load = function() {
	$("div#inbay > ul > li[data-typeid]").append(" <span class='links'><a href='javascript:void(0);' title='Launch 5 drones' class='movefivedrones'>⇉</a> <a href='javascript:void(0);' title='Launch one drone' class='moveonedrone'>→</a></span>");
	$("div#inspace > ul > li[data-typeid]").append(" <span class='links'><a href='javascript:void(0);' title='Return 5 drones to bay' class='movefivedrones'>⇇</a> <a href='javascript:void(0);' title='Return one drone to bay' class='moveonedrone'>←</a></span>");

	$("div#vdronebay li[data-typeid] > img").click(function() {
		var li = $(this).parent();
		var opts = { type: 'drone', typeid: li.data('typeid') };
		osmium_showinfo_from_vl(opts);
	});
	osmium_addicon($("div#vdronebay li[data-typeid] > img"));
};

osmium_showinfo_from_vl = function(opts) {
	var lb = $("div#vloadoutbox");
	opts.loadoutid = lb.data('loadoutid');
	opts.revision = lb.data('revision');
	opts.pid = lb.data('presetid');
	opts.cpid = lb.data('cpid');
	opts.dpid = lb.data('dpid');
	opts.skillset = lb.data('skillset');

	osmium_showinfo(opts, '..');
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

	$("div#inbay > ul").on('click', 'li > span.links > a.moveonedrone', function() {
		osmium_commit_load(null, null, null,
						   $(this).parent().parent().data('typeid'), 1, 'bay');
	});
	$("div#inbay > ul").on('click', 'li > span.links > a.movefivedrones', function() {
		osmium_commit_load(null, null, null, $(this).parent().parent().data('typeid'),
						   Math.min($(this).parent().parent().data('count'), 5), 'bay');
	});
	$("div#inspace > ul").on('click', 'li > span.links > a.moveonedrone', function() {
		osmium_commit_load(null, null, null,
						   $(this).parent().parent().data('typeid'), 1, 'space');
	});
	$("div#inspace > ul").on('click', 'li > span.links > a.movefivedrones', function() {
		osmium_commit_load(null, null, null, $(this).parent().parent().data('typeid'),
						   Math.min($(this).parent().parent().data('count'), 5), 'space');
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

	$("div#vloadoutbox > header > div.votes > a, div#vcomments > div.comment > div.votes > a").click(function() {
		var t = $(this);
		var score = t.parent().children('strong');
		var delta = 0;
		var upvoted = t.parent().children('a.upvote').hasClass('voted');
		var downvoted = t.parent().children('a.downvote').hasClass('voted');
		var action;

		if(t.hasClass('voted')) {
			t.removeClass('voted');
			delta += t.hasClass('upvote') ? -1 : 1;
			action = 'rmvote';
		} else {
			t.parent().children('a.voted').each(function() {
				delta += $(this).removeClass('voted').hasClass('upvote') ? -1 : 1;
			});

			t.addClass('voted');
			if(t.hasClass('upvote')) {
				delta += 1;
				action = 'castupvote';
			} else {
				delta += -1;
				action = 'castdownvote';
			}
		}

		score.text(parseInt(score.text(), 10) + delta);

		var targettype = t.parent().data('targettype');
		var opts = {
			targettype: targettype,
			action: action,
			loadoutid: $("div#vloadoutbox").data('loadoutid')
		};

		if(targettype == 'comment') {
			opts['commentid'] = t.parent().parent().data('commentid');
		}

		$.getJSON('../src/json/cast_vote.php', opts, function(data) {
			if(!data['success']) {
				score.text(parseInt(score.text(), 10) - delta);

				if(upvoted) {
					t.parent().children('a.upvote').addClass('voted');
				} else {
					t.parent().children('a.upvote').removeClass('voted');
				}

				if(downvoted) {
					t.parent().children('a.downvote').addClass('voted');
				} else {
					t.parent().children('a.downvote').removeClass('voted');
				}

				var error = $(document.createElement('div'));
				error.addClass('verror');
				error.text(data['error']);
				error.append('<br /><small>(click to close)</small>');
				error.hide();
				error.click(function() {
					$(this).fadeOut(250);
				});
				t.parent().append(error);
				error.fadeIn(250);
			}
		});
	});

	osmium_drones_load();

	$("div.slots > ul > li[data-typeid] > img").click(function() {
		var li = $(this).parent();
		var opts = {
			type: 'module',
			slottype: li.data('slottype'),
			index: li.data('index')
		};
		osmium_showinfo_from_vl(opts);
	});

	$("div.slots > ul > li[data-typeid] > span.charge > img").click(function() {
		var li = $(this).parent().parent();
		var opts = {
			type: 'charge',
			slottype: li.data('slottype'),
			index: li.data('index')
		};
		osmium_showinfo_from_vl(opts);
	});

	$("div#vloadoutbox > header > img#fittypepic").click(function() {
		var opts = { type: 'ship' };
		osmium_showinfo_from_vl(opts);
	});

	osmium_addicon($("div.slots > ul > li[data-typeid] > img, div.slots > ul > li[data-typeid] > span.charge > img, div#vloadoutbox > header > img#fittypepic"));
});
