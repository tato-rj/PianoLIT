$(document).on('submit', 'form[disable-on-submit]', function() {
    let spinner = `<div class="animated fadeIn" style="position: absolute;
                                top: 50%;
                                left: 50%;
                                -webkit-transform: transform: translate(-50%, -50%);
                                transform: translate(-50%, -50%);">
                        <div class="spinner-border opacity-8" style="width: 1rem; height: 1rem; border-width: .16em; margin-bottom: .1rem;"></div>
                    </div>`;
    let $button = $(this).find('button[type="submit"]');

    $button.prop('disabled', true).addClass('position-relative').contents().wrapAll('<div class="invisible"></div>');
    $button.append(spinner);
});

$(document).on('click', 'a[disable-on-submit]', function() {
    let spinner = `<div class="animated fadeIn" style="position: absolute;
                                top: 50%;
                                left: 50%;
                                -webkit-transform: transform: translate(-50%, -50%);
                                transform: translate(-50%, -50%);">
                        <div class="spinner-border opacity-8" style="width: 1rem; height: 1rem; border-width: .16em; margin-bottom: .1rem;"></div>
                    </div>`;
    let $button = $(this).find('button[type="submit"]');

    $button.prop('disabled', true).addClass('position-relative').contents().wrapAll('<div class="invisible"></div>');
    $button.append(spinner);
});