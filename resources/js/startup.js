$(document).ready(function() {
	$('.alert-temporary').fadeAfter(2);
	$('[data-toggle="popover"]').popover();

	(new YoutubeV3).init();

    (new Search).listenTo('#global-search-input')
                .feedbackIn('#global-search-feedback')
                .resultsIn('#global-search-results')
                .ready();
                
    $('.modal').each(function() {
      if ($(this).find('.is-invalid')[0])
        $(this).modal('show');
    });
});

$(window).bind('load', function() {
    $('#load-screen').fadeOut(function() {
        $(this).remove();
    });
});