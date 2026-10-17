function showFavoriteAccessPrompt(error) {
    const target = error.response && error.response.data && error.response.data.access_prompt;
    const modal = target && document.querySelector(target);
    if (!modal) return false;
    window.bootstrap.Modal.getOrCreateInstance(modal).show();
    return true;
}

const Offcanvas = window.bootstrap.Offcanvas;
let favoritePending = false;

// A heart means saved anywhere, even when this mutation only changed one folder.
window.PianoFavorites = {
    update(favorite) {
        if (!favorite || !Number.isInteger(favorite.piece_id) || typeof favorite.is_favorited !== 'boolean') return;
        document.querySelectorAll('[data-favorite-piece="' + favorite.piece_id + '"]').forEach(function(button) {
            button.setAttribute('aria-pressed', favorite.is_favorited ? 'true' : 'false');
            button.setAttribute('data-favorited', favorite.is_favorited ? 'true' : 'false');
            const icon = button.querySelector('i');
            if (icon) icon.classList.toggle('icon-filled', favorite.is_favorited);
        });
    }
};

    const saveToPanel = document.getElementById('save-to-offcanvas');
    if (saveToPanel) {
        let contentReady = false;
        let loadRequest = 0;
        saveToPanel.addEventListener('show.bs.offcanvas', function(event) {
            if (contentReady) {
                return;
            }

            event.preventDefault();
            const trigger = event.relatedTarget;
            if (!trigger) return;
            const $btn = $(trigger).disable();
            const requestId = ++loadRequest;

            axios.get($btn.data('url'))
                .then(function(response) {
                    if (requestId !== loadRequest) return;
                    $('#save-to-offcanvas-content').html(response.data);
                    contentReady = true;
                    Offcanvas.getOrCreateInstance(saveToPanel).show(trigger);
                })
                .catch(function(error) {
                    if (requestId !== loadRequest) return;
                    if (!showFavoriteAccessPrompt(error)) alert('Favorites could not be loaded. Please try again.');
                })
                .then(function() {
                    $btn.enable();
                });
        });
        saveToPanel.addEventListener('shown.bs.offcanvas', function() {
            contentReady = false;
        });
    }

    $(document).on('click', 'button[data-submit=favorite]', function() {
        if (favoritePending) return;
        favoritePending = true;
        let $btn = $(this);
        let $container = $btn.closest('#favorite-folders-container');
        let $btns = $container.length ? $container.find('button') : $btn;
        let scrollTop = $container.find('.save-to-panel__list').scrollTop();
        $btns.disable();

        axios.post($btn.data('url'))
            .then(function(response) {
                if ($container.length) {
                    $container.html(response.data.html.list);
                    $container.find('.save-to-panel__list').scrollTop(scrollTop);
                }
                window.PianoFavorites.update(response.data.favorite);
            })
            .catch(function(error) {
                if (!showFavoriteAccessPrompt(error)) alert('Your favorite could not be updated. Please try again.');
            })
            .then(function() {
                favoritePending = false;
                $btns.enable();
            });
    });

    $(document).on('click', 'button[data-submit=folder]', function() {
        if (favoritePending) return;
        favoritePending = true;
        let $btn = $(this);
        let $name = $($btn.data('name'));
        let $container = $('#favorite-folders-container');
        let $btns = $container.find('button').disable();

        $btn.disable().addLoader();
        $btn.siblings('button').hide();

        axios.post($btn.data('url'), {name: $($name).val()})
            .then(function(response) {
                $container.html(response.data.html.list);
                $container.find('.invalid-feedback').text('').hide();
                window.PianoFavorites.update(response.data.favorite);
            })
            .catch(function(error) {
                $btn.enable().removeLoader();
                $btn.siblings('button').show();
                if (showFavoriteAccessPrompt(error)) return;
                let feedback = error.response && error.response.data.errors ? objFirst(error.response.data.errors)[0] : 'The folder could not be created. Please try again.';
                $container.find('.invalid-feedback').text(feedback).show();
            })
            .then(function() {
                favoritePending = false;
                $btns.enable();
            });
    });

    $(document).on('click', 'button.new-folder', function() {
        $(this).hide();
        $($(this).data('target')).show();
    });

    $(document).on('click', 'button.cancel-new-folder', function() {
        $($(this).data('container')).hide();
        $($(this).data('target')).show();
    });
