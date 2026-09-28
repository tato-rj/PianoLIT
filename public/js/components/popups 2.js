$(document).ready(function() {
    $('#bottom-popup > div').css('bottom', $('#bottom-popup').siblings('.row').outerHeight() + 14);
});

$(document).on('click', '[data-dismiss=popup]', function() {
    $('#bottom-popup').fadeOut('fast');
});

$(document).on('click', 'button[data-manage="favorite"]', function(event) {
    event.preventDefault();
    let $button = $(this);
    let $heart = $button.find('i');

    $button.disable().addClass('opacity-6');

    axios.post($button.attr('data-url-toggle'))
        .then(function(response) {
            $heart.toggleClass('fas far');
        })
        .catch(function(error) {
            alert('Sorry, we couldn\'t update your favorite at this time.');
        })
        .then(function() {
            $button.enable().removeClass('opacity-6');
        });
});