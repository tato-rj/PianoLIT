$(document).ready(function() {
	$('.alert-temporary').fadeAfter(2);
	$('[data-toggle="popover"]').popover();

    if (app.youtube_key)
    	(new YoutubeV3).init();

    (new Search).listenTo('#global-search-input')
                .feedbackIn('#global-search-feedback')
                .resultsIn('#global-search-results')
                .ready();
                
    $('.modal').each(function() {
      if ($(this).find('.is-invalid')[0])
        $(this).modal('show');
    });

    $('.btn-subscribe').on('click', function() {
        $("#subscribe-overlay").fadeIn('fast');
    });

    $('#options button').click(function() {
        $($(this).attr('data-target')).fadeToggle('fast').siblings().hide();
    });

    $('#sort-container input[type="radio"]').click(function() {
        $('#pieces-list').sortChildrenBy(this);
    });

    $('[data-clamp]').each(function() {
        $(this).clamp($(this).data('clamp'));
    });
});

$(window).bind('load', function() {
    $('#load-screen').fadeOut(function() {
        $(this).remove();
    });
});

$(document).on('click', '#reload', function() {
    window.location = window.location.href.split("?")[0];
});

$(document).on('click', '[data-anchor]', function() {
    let anchor = $(this).attr('data-anchor');
    window.location.hash = anchor;
});