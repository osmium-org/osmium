$(function() {
    $('select#view_perms').change(function() {
		if($(this).val() == 1) {
			$("select#visibility").val(1).attr('disabled', 'disabled');
			$("input#pw").removeAttr('disabled');
			$("input#pw").parent().parent().css('opacity', 1.0);
		} else {
			$("select#visibility").removeAttr('disabled');
			$("input#pw").val('').attr('disabled', 'disabled');
			$("input#pw").parent().parent().css('opacity', 0.2);
		}
    });

    $('select#view_perms').trigger('change');
});
