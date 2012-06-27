toggle_revision_delta = function(li) {
	if(li.hasClass('hidden')) {
		li.find('p > a.toggle').text("hide changes");
		li.find('pre').fadeIn(500);
	} else {
		li.find('p > a.toggle').text("show changes");
		li.find('pre').hide();
	}

	li.toggleClass('hidden');
}

$(function() {
	$("ol#lhistory > li > p > small.anchor").not(":last").before("<a href='javascript:void(0);' class='toggle'>show changes</a> — ");
	$("ol#lhistory > li > pre").hide();
	$("ol#lhistory > li").addClass('hidden');

	var first = $("ol#lhistory > li").first();
	first.find('pre').show();
	toggle_revision_delta(first);

	$('ol#lhistory > li > p > a.toggle').click(function() {
		toggle_revision_delta($(this).parent().parent());
		$(this).blur();
	});
});
