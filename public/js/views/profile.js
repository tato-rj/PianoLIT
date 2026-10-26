(function () {
    'use strict';
    var invalid = document.querySelector('[aria-invalid="true"]');
    if (invalid && typeof invalid.focus === 'function') invalid.focus();

    var area = document.querySelector('[data-profile-emails]');
    if (!area || !window.fetch || !window.FormData) return;
    var forms = Array.from(area.querySelectorAll('[data-profile-list]'));
    var status = area.querySelector('[data-email-status]');
    var all = area.querySelector('[data-unsubscribe-all]');
    var busy = 0;
    var autosave = area.querySelector('[data-profile-autosave]');
    if (autosave) autosave.hidden = false;

    function updateAll() {
        if (all) all.disabled = busy > 0 || !forms.some(function (form) { return form.querySelector('[role="switch"]').checked; });
    }
    forms.forEach(function (form) {
        var input = form.querySelector('[role="switch"]');
        var save = form.querySelector('[data-profile-list-save]');
        var confirmed = input.checked;
        if (save) save.hidden = true;
        async function submit(event) {
            event.preventDefault();
            if (input.disabled) return;
            var desired = input.checked;
            var body = new FormData(form);
            body.set('enabled', desired ? '1' : '0');
            input.disabled = true;
            busy++;
            updateAll();
            form.setAttribute('aria-busy', 'true');
            status.textContent = 'Saving email preferences…';
            status.classList.remove('text-danger');
            try {
                var response = await fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'Accept': 'application/json'}, body: body});
                if (!response.ok) throw new Error('save failed');
                var data = await response.json();
                if (typeof data.subscribed !== 'boolean') throw new Error('invalid response');
                confirmed = data.subscribed;
                input.checked = confirmed;
                status.textContent = 'Email preferences saved.';
            } catch (error) {
                input.checked = confirmed;
                status.textContent = 'We couldn’t save that preference. Please try again.';
                status.classList.add('text-danger');
            } finally {
                input.disabled = false;
                busy--;
                updateAll();
                form.removeAttribute('aria-busy');
            }
        }
        input.addEventListener('change', submit);
        form.addEventListener('submit', submit);
    });
    updateAll();
    if (new URLSearchParams(window.location.search).get('tab') === 'email-preferences' && !window.location.hash) {
        document.getElementById('email-preferences').scrollIntoView();
    }
}());
