$('[save-query]').click(function (e) {
	window.location.hash = this.hash;
});

$('.no-click').bind('contextmenu', function(e) {
    return false;
});

$('form[submit-on-enter] input').keypress(function(e) {
    if (e.which == 13) {
    	console.log('Submitting the form on enter');
        $(this).closest('form').submit();
        return false;
    }
})
