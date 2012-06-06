osmium_fattribs_load = function() {
	$("div#computed_attributes > section").each(function() {
		var key = 'osmium_fattribs_' + $(this).attr('id');
		if(localStorage.getItem(key) === "0") {
			$("section#" + $(this).attr('id') + " > div").hide()
				.parent().addClass('hidden');
		}
	});
};

osmium_fattribs_toggle = function(id) {
	var key = 'osmium_fattribs_' + id;
	if(localStorage.getItem(key) !== "0") {
		localStorage.setItem(key, "0");
		$("section#" + id + " > div").hide()
			.parent().addClass('hidden');
	} else {
		localStorage.setItem(key, "1");
		$("section#" + id + " > div").fadeIn(500)
			.parent().removeClass('hidden');
	}
};

$(function() {
	$(document).on('click', "div#computed_attributes > section > h4", function() {
		osmium_fattribs_toggle($(this).parent().attr('id'));
	});

	osmium_fattribs_load();
});