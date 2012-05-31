osmium_load_drones = function(json) {
    $('div#dronebay > ul').empty();
    var capacity = json['attributes']['dronecapacity'];
    var used_capacity = 0;
    for(var i = 0; i < json['drones'].length; ++i) {
		$('div#dronebay > ul').append("<li class='drone' data-typeid='" + json['drones'][i]['typeid'] + "' data-count='" + json['drones'][i]['count'] + "'><img src='http://image.eveonline.com/Type/" + json['drones'][i]['typeid'] + "_32.png' alt='' title='' />" + json['drones'][i]['typename'] + " <strong>×" + json['drones'][i]['count'] + "</strong></li>");
		$("div#dronebay > ul > li.drone > img").last()
			.attr('alt', json['drones'][i]['typename'])
			.attr('title', json['drones'][i]['typename']);

		used_capacity += json['drones'][i]['count'] * json['drones'][i]['volume'];
    }
    $('p#dronecapacity > strong').text(used_capacity + ' / ' + capacity);
    if(used_capacity > capacity) {
		$('p#dronecapacity > strong').addClass('overflow');
    } else {
		$('p#dronecapacity > strong').removeClass('overflow');
    }
    $('div#dronebay > ul > li.drone').filter(function() { return $(this).data('count') == 1; })
		.children('strong').hide();
    $('div#dronebay > ul').append("<li class='drone_placeholder'>Drag drones here…</li>\n");
};

osmium_drones_commit = function() {
    $("img#dronebay_spinner").css('visibility', 'visible');

    var opts = {
		token: osmium_tok
    };

    $("div#dronebay > ul > li.drone").each(function(i) {
		opts["drone" + i] = $(this).data('typeid');
		opts["count" + i] = $(this).data('count') ? $(this).data('count') : 1;
    });

    $.getJSON('./src/json/update_drones.php', opts, function(json) {
		osmium_load_drones(json);
		$("img#dronebay_spinner").css('visibility', 'hidden');
    });
};

osmium_pop_drone = function(typeid) {
    $("img#dronebay_spinner").css('visibility', 'visible');

    $.getJSON('./src/json/pop_drone.php', {
		token: osmium_tok,
		typeid: typeid
    }, function(json) {
		osmium_load_drones(json);
		$("img#dronebay_spinner").css('visibility', 'hidden');
    });
};

$(function() {
    $("div#dronelistbox > form").submit(function() {
		$("img#dronelistbox_spinner").css('visibility', 'visible');

		$.getJSON('./src/json/search_drones.php', {
			q: $("div#dronelistbox input[type='text']").val(),
			token: osmium_tok
		}, function(json) {
			$("ul#search_results").empty();
			$("p#search_warning").remove();
			for(var i = 0; i < json['payload'].length; ++i) {
				$("ul#search_results").append("<li class='drone' data-typeid='" + json['payload'][i]['typeid'] + "'><img src='http://image.eveonline.com/Type/" + json['payload'][i]['typeid'] + "_32.png' alt='' title='' />" + json['payload'][i]['typename'] + "</li>\n");
				$("ul#search_results > li.drone > img").last()
					.attr('alt', json['payload'][i]['typename'])
					.attr('title', json['payload'][i]['typename']);
			}
			if(json['warning']) {
				$("ul#search_results").before("<p id='search_warning' class='warning_box'>" + json['warning'] + "</p>");
			}
			$("img#dronelistbox_spinner").css("visibility", "hidden");
			
		});

		return false;
    });

    $("ul#search_results").sortable({
		helper: 'clone',
		connectWith: 'div#dronebay > ul',
		start: function(event, ui) {
			$("ul#search_results li.drone").eq($(ui.item).index()).after(
				$(ui.item).clone().addClass('clone').show()
			);
			$("ul#search_results").css('opacity', '0.2');
		},
		remove: function(event, ui) {
			$("ul#search_results li.drone").removeClass('clone');
		},
		stop: function(event, ui) {
			$("ul#search_results li.clone").remove();
			$("ul#search_results").css('opacity', '1.0');
		}
    });

    $("div#dronebay > ul").sortable({
		update: osmium_drones_commit,
		items: '.drone',
		placeholder: "drone_placeholder"
    });

    $(document).on('dblclick', "ul#search_results > li.drone", function(obj) {
		$("div#dronebay > ul > li.drone_placeholder").before($(this).clone());
		osmium_drones_commit();
    });

    $(document).on('dblclick', "div#dronebay > ul > li.drone", function(obj) {
		osmium_pop_drone($(this).data('typeid'));
    });
});