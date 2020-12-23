$(document).on('submit', 'form[disable-on-submit]', function() {
    $(this).find('button[type="submit"]').addLoader();
});

$(document).on('click', 'a[disable-on-submit]', function() {
    $(this).find('button[type="submit"]').addLoader()
});