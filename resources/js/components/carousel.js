$('#whatsnew-carousel').on('slide.bs.carousel', function (event) {
    $dots = $(this).find('.carousel-dots i');
    $dots.removeClass('text-primary').addClass('text-grey');
    $dots.eq(event.to).removeClass('text-grey').addClass('text-primary');
    if (event.to == $(this).find('.carousel-item').length - 1) {
        $(this).find('button[data-slide="end"]').show().siblings('button').hide();
    }
});