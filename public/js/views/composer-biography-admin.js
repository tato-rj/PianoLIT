(function () {
    'use strict';

    var button = document.getElementById('regenerate-biography');
    if (!button) return;
    var form = button.closest('form');
    var biography = form.querySelector('[name="biography"]');
    var status = document.getElementById('biography-status');
    var saves = Array.prototype.slice.call(form.querySelectorAll('[type="submit"]'));
    var revision = 0;
    var busy = false;

    biography.addEventListener('input', function () { revision++; });
    form.addEventListener('submit', function (event) {
        if (busy) event.preventDefault();
    });

    function message(text, error) {
        status.textContent = text;
        status.classList.toggle('text-danger', !!error);
    }

    button.addEventListener('click', function () {
        if (busy) return;
        var source = biography.value;
        if (!source.trim()) {
            message('Add a source bio first so it can be rewritten.', true);
            biography.focus();
            return;
        }

        busy = true;
        var startedRevision = revision;
        var saveStates = saves.map(function (save) { return save.disabled; });
        button.disabled = true;
        button.textContent = 'Regenerating…';
        biography.setAttribute('aria-busy', 'true');
        saves.forEach(function (save) { save.disabled = true; });
        message('Writing a simple, short bio…');

        $.ajax({
            url: button.getAttribute('data-url'),
            method: 'POST',
            dataType: 'json',
            timeout: 55000,
            headers: {'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
            data: {biography: source}
        }).done(function (data) {
            if (revision !== startedRevision || biography.value !== source) {
                message('Your bio changed while the request was running. Click Regenerate bio to use your latest text.');
                return;
            }
            if (!data || typeof data.biography !== 'string' || !data.biography.trim()) {
                message('No bio was returned. Please try again.', true);
                return;
            }
            biography.value = data.biography;
            biography.dispatchEvent(new Event('input', {bubbles: true}));
            message('Bio regenerated. Review it, then click Save changes.');
        }).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            var text = response.errors && response.errors.biography ? response.errors.biography[0] : response.message;
            if (xhr.status === 401 || xhr.status === 419) text = 'Your session expired. Reload this page and try again.';
            if (xhr.status === 429) text = 'Too many requests. Please wait a minute and try again.';
            message(text || 'The bio could not be regenerated. Please try again.', true);
        }).always(function () {
            busy = false;
            button.disabled = false;
            button.textContent = 'Regenerate bio';
            biography.removeAttribute('aria-busy');
            saves.forEach(function (save, index) { save.disabled = saveStates[index]; });
        });
    });
}());
