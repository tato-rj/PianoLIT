$('.show-overlay').on('click', function() {
    let overlayId = $(this).attr('data-target');
    $('body').css('overflow-y', 'hidden');
    $(overlayId).fadeIn();
});

$('.close-overlay').on('click', function() {
    let overlayId = $(this).attr('data-target');
    $('body').css('overflow-y', 'scroll');
    $(overlayId).fadeOut();
}).children().on('click', function(e) {
    e.stopPropagation();
});