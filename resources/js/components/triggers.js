$('[save-query]').click(function (e) {
	window.location.hash = this.hash;
});

$('.no-click').bind('contextmenu', function(e) {
    return false;
}); 

$('[full-load]').on('click', function() {
	$('#loading-overlay').show();
});

$('form[submit-on-enter] input').keypress(function(e) {
    if (e.which == 13) {
    	console.log('Submitting the form on enter');
        $(this).closest('form').submit();
        return false;
    }
})

$('[data-toggle="fixed-panel"]').on('click', function() {
	let $link = $(this);
	let $panel = $($link.attr('data-target'));
	$link.removeClass('active');
	$panel.fadeToggle();
	$('body').toggleCssBetween('overflow', ['hidden', 'auto']);
	$panel.find('.panel-content').css('right', 0);
});

$('button[data-dismiss="fixed-panel"], .fixed-panel .panel-overlay').on('click', function() {
	let $panel = $(this).closest('.fixed-panel');
	$panel.find('.panel-content').css('right', '-100%');
	$panel.fadeToggle();
	$('body').toggleCssBetween('overflow', ['hidden', 'auto']);
});