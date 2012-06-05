osmium_commit_load = function(toggletype, toggleindex, toggledirection) {
	$("img#vloadoutbox_spinner").css('visibility', 'visible');

	var opts = {
		loadoutid: $('div#vloadoutbox').data('loadoutid'),
		preset: $('ul#vpresets > li > a.active').parent().data('index'),
		toggletype: toggletype,
		toggleindex: toggleindex,
		toggledirection: toggledirection
	};

	$('div#vloadoutbox > div.slots.stateful > ul > li[data-state]').each(function() {
		opts[$(this).data('slottype') + $(this).data('index')] = $(this).data('state');
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

		$("ul.computed_attributes").html(json['attributes']);

		$("img#vloadoutbox_spinner").css('visibility', 'hidden');
	});
};

$(function() {
	$('ul#vpresets > li > a').click(function() {
		$('ul#vpresets > li > a.active').removeClass('active');
		$(this).addClass('active');
		osmium_commit_load(null, null);
		return false;
	});

	$('div#vloadoutbox > div.slots.stateful > ul > li > a.toggle').click(function(obj) {
		osmium_commit_load($(this).parent().data('slottype'), $(this).parent().data('index'), true);
		obj.stopPropagation();
		obj.preventDefault();
		return false;
	}).bind('contextmenu', function(obj) {
		osmium_commit_load($(this).parent().data('slottype'), $(this).parent().data('index'), false);
		obj.stopPropagation();
		obj.preventDefault();
		return false;
	});
});