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

osmium_charges_load = function(json) {
	var select = $("select#preset");
	var chargeselect = $("select#chargepreset");

	var option, optgroup;
	var cgroup, cselect;
	var cli, cimg;
	var chargeid;
	var cspan;
	var clink;

	select.empty();
	for(var i = 0; i < json['presets'].length; ++i) {
		option = $(document.createElement('option'));
		option.prop('value', json['presets'][i][0]).text(json['presets'][i][1]);
		select.append(option);
	}
	select.val(json['presetid']);

	chargeselect.empty();
	for(var i = 0; i < json['chargepresets'].length; ++i) {
		option = $(document.createElement('option'));
		option.prop('value', json['chargepresets'][i][0]).text(json['chargepresets'][i][1]);
		chargeselect.append(option);
	}
	chargeselect.val(json['cpid']);

	$("div#computed_attributes").html(json['attributes']);
	$("textarea#charge_preset_desc").val(json['chargepresetdesc']);

	$("ul#chargegroups").empty();
	for(var i = 0; i < json['charges'].length; ++i) {
		cgroup = $(document.createElement('ul'));
		cgroup.addClass('chargegroup');
		cselect = $(document.createElement('select'));
		cselect.append('<option value="0">(No charge)</option>');

		for(var metagroupname in json['charges'][i]['charges']) {
			optgroup = $(document.createElement('optgroup'));
			optgroup.prop('label', metagroupname);

			for(var j = 0; j < json['charges'][i]['charges'][metagroupname].length; ++j) {
				option = $(document.createElement('option'));
				option.prop('value', json['charges'][i]['charges'][metagroupname][j]['typeid']);
				option.text(json['charges'][i]['charges'][metagroupname][j]['typename']);
				optgroup.append(option);
			}

			cselect.append(optgroup);
		}

		for(var j = 0; j < json['charges'][i]['modules'].length; ++j) {
			cli = $(document.createElement('li'));
			cimg = $(document.createElement('img'));
			cimg.attr('src', 'http://image.eveonline.com/Type/'
					  + json['charges'][i]['modules'][j]['typeid'] + '_64.png');
			cimg.attr('alt', json['charges'][i]['modules'][j]['typename']);
			cimg.attr('title', json['charges'][i]['modules'][j]['typename']);
			cli.append(cimg);

			cimg = $(document.createElement('img'));
			chargeid = json['charges'][i]['modules'][j]['chargeid'];
			if(chargeid > 0) {
				cimg.attr('src', 'http://image.eveonline.com/Type/' + chargeid + '_64.png');
			} else {
				cimg.attr('src', './static-' + osmium_staticver + '/icons/no_charge.png');
			}
			cli.append(cimg);
			
			cli.append(cselect.clone()
					   .val(chargeid)
					   .data('type', json['charges'][i]['modules'][j]['type'])
					   .data('index', json['charges'][i]['modules'][j]['index']));
			cgroup.append(cli);
		}

		cspan = $(document.createElement('span'));
		cspan.addClass('links');

		clink = $(document.createElement('a'));
		clink.attr('href', 'javascript:void(0);');
		clink.attr('title', 'Select all modules in this group');
		clink.attr('class', 'selectall');
		clink.text('select all');
		cspan.append(clink);

		clink = $(document.createElement('a'));
		clink.attr('href', 'javascript:void(0);');
		clink.attr('title', 'Deselect all modules in this group');
		clink.attr('class', 'selectnone');
		clink.text('select none');
		cspan.append(clink);

		cli = $(document.createElement('li'));
		cli.append(cspan);
		cli.append(cgroup);
		$("ul#chargegroups").append(cli);
	}

	if(json['chargepresets'].length === 1) {
		$("button#delete_charge_preset").prop('disabled', 'disabled');
	} else {
		$("button#delete_charge_preset").removeProp('disabled');
	}

	osmium_fattribs_load();
	
    $("ul#chargegroups > li > ul.chargegroup").selectable({
		items: 'li',
		autoRefresh: false
    });

	$("ul#chargegroups > li > span.links > a.selectall").click(function() {
		$(this).parent().parent().find('ul.chargegroup > li.ui-selectee').addClass('ui-selected');
		$(this).blur();
	});
	$("ul#chargegroups > li > span.links > a.selectnone").click(function() {
		$(this).parent().parent().find('ul.chargegroup > li.ui-selectee').removeClass('ui-selected');
		$(this).blur();
	});
};

osmium_presets_commit = function(opts) {
	opts['token'] = osmium_tok;
	if(!('type' in opts)) {
		opts['type'] = 'charge';
	}
	opts['returntype'] = 'charge';

	$("img#presets_spinner").css("visibility", "visible");
    $.getJSON('./src/json/update_presets.php', opts, function(json) {
		osmium_charges_load(json);
		$("img#presets_spinner").css('visibility', 'hidden');
    });
};

osmium_presetdesc_commit = function() {
	opts = {};
	opts['token'] = osmium_tok;
	opts['type'] = 'charge';
	opts['returntype'] = 'charge';
	opts['action'] = 'updatedesc';
	opts['desc'] = $("textarea#charge_preset_desc").val();

	$("img#presets_spinner").css("visibility", "visible");
    $.post('./src/json/update_presets.php', opts, function(json) {
		osmium_charges_load(json);
		$("img#presets_spinner").css('visibility', 'hidden');
    }, "json");
};

osmium_charges_commit = function() {
	opts = {};
	opts['token'] = osmium_tok;

	$("ul#chargegroups > li > ul.chargegroup > li > select").each(function() {
		opts[$(this).data('type') + $(this).data('index')] = $(this).val();
	});

	$("img#chargegroupsbox_spinner").css("visibility", "visible");
    $.getJSON('./src/json/update_charges.php', opts, function(json) {
		osmium_charges_load(json);
		$("img#chargegroupsbox_spinner").css('visibility', 'hidden');
    });
}

$(function() {
	$("button#create_charge_preset").click(function() {
		var new_name = prompt('Enter the name of the new charge preset (must not conflict with another charge preset name):', 'Charge preset #' + ($("select#chargepreset > option").length + 1));
		if(new_name) {
			osmium_presets_commit({
				action: 'create',
				name: new_name
			});
		}
	});

	$("button#delete_charge_preset").click(function() {
		if($("select#chargepreset > option").length > 1) {
			osmium_presets_commit({
				action: 'delete'
			});
		}
	});

	$("button#rename_charge_preset").click(function() {
		var new_name = prompt('Enter the new name for the current charge preset (must not conflict with another charge preset name):', $("select#chargepreset > option[value='" + $("select#chargepreset").val() + "']").text());
		if(new_name) {
			osmium_presets_commit({
				action: 'rename',
				name: new_name
			});
		}
	});

	$("button#clone_charge_preset").click(function() {
		var new_name = prompt('Enter the name of the clone (must not conflict with another charge preset name):', 'Charge preset #' + ($("select#chargepreset > option").length + 1) + ' (clone of ' + $("select#chargepreset > option[value='" + $("select#chargepreset").val() + "']").text() + ')');
		if(new_name) {
			osmium_presets_commit({
				action: 'clone',
				name: new_name
			});
		}
	});

	$("button#update_desc").click(function() {
		osmium_presetdesc_commit();
	});

	$("select#preset").change(function() {
		osmium_presets_commit({
			action: 'switch',
			type: 'module',
			presetid: $(this).val()
		});
	});

	$("select#chargepreset").change(function() {
		osmium_presets_commit({
			action: 'switch',
			presetid: $(this).val()
		});
	});

	$(document).on("change", "ul#chargegroups > li > ul.chargegroup > li > select", function() {
		var li = $(this).parent();
		var chargeid = $(this).val();
		if(li.hasClass('ui-selected')) {
			li.parent().find('li.ui-selected > select').each(function() {
				$(this).val(chargeid);
			});
		}

		osmium_charges_commit();
	});

	osmium_fattribs_load();
});
