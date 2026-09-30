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

function setFixedPanelOpen($panel, open) {
    if (!$panel.length) return;

    $panel.stop(true, true).toggleClass('is-open', open);
    $panel.find('.panel-content').css('right', open ? 0 : '-100%');
    if (open) $panel.fadeIn();
    else $panel.fadeOut();

    $('body').toggleClass('fixed-panel-open', $('.fixed-panel.is-open').length > 0);
}

$(document).on('click', '[data-toggle="fixed-panel"]', function() {
	let $link = $(this);
	let $panel = $($link.attr('data-target'));
	$link.removeClass('active');
    setFixedPanelOpen($panel, !$panel.hasClass('is-open'));
});

$(document).on('click', 'button[data-dismiss="fixed-panel"], .fixed-panel .panel-overlay', function() {
    setFixedPanelOpen($(this).closest('.fixed-panel'), false);
});

$(document).on('close.fixedPanel', '.fixed-panel', function() {
    setFixedPanelOpen($(this), false);
});
