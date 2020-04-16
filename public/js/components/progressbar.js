showScrollProgressBar = function(content) {
    let body = document.body,
    html = document.documentElement;

    let pageHeight = Math.max( body.scrollHeight, body.offsetHeight, 
        html.clientHeight, html.scrollHeight, html.offsetHeight);

    let offset = content.offset().top;
    let height = content.height();

    let proportion = (height/pageHeight) + 1;

    let $progressbar = $('#page-progress .progress-bar');

    if ($progressbar.length) {
        $(window).scroll(function() {
            let scrollTop = $(this).scrollTop();
            $progressbar.css('width', percentage(scrollTop - offset*4, height)*proportion + '%');
        });
    }
};