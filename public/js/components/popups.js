$(document).ready(function() {
    $('#bottom-popup > div').css('bottom', $('#bottom-popup').siblings('.row').outerHeight() + 14);
});

$(document).on('click', '[data-dismiss=popup]', function() {
    $('#bottom-popup').fadeOut('fast');
});

$(document).on('click', function(event) {
    let $popup = $('#bottom-popup:visible');
    if (!$popup.length || $(event.target).closest('#bottom-popup-content').length) return;

    let $close = $popup.find('[data-dismiss=popup]').first();
    if ($close.length) $close.trigger('click');
    else $popup.fadeOut('fast');
});
