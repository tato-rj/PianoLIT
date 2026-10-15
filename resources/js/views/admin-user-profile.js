(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root, root.jQuery);
}(typeof window !== 'undefined' ? window : this, function (win, $) {
    'use strict';
    var page = win.document.querySelector('[data-user-profile]');
    if (!page || !$) return;
    var toggle = page.querySelector('[data-user-super-status]');
    var feedback = page.querySelector('[data-super-status-message]');
    if (toggle) toggle.addEventListener('change', function () {
        var checked = toggle.checked;
        toggle.disabled = true;
        feedback.hidden = false;
        feedback.classList.remove('is-error');
        feedback.textContent = 'Saving access…';
        $.ajax({url: toggle.getAttribute('data-url'), type: 'PATCH', dataType: 'json', timeout: 15000})
            .done(function (response) {
                feedback.textContent = response.status;
                var status = page.querySelector('[data-profile-status]');
                var access = page.querySelector('[data-profile-access]');
                status.textContent = checked ? 'Super user' : status.getAttribute('data-base-label');
                access.textContent = checked || access.getAttribute('data-billing-access') === '1' ? 'Full media access' : 'Preview access';
            }).fail(function () {
                toggle.checked = !checked;
                feedback.classList.add('is-error');
                feedback.textContent = 'Access could not be updated. Please try again.';
            }).always(function () { toggle.disabled = false; });
    });
    Array.prototype.forEach.call(page.querySelectorAll('[data-removal-form]'), function (form) {
        var input = form.querySelector('[name="confirmation_email"]');
        var submit = form.querySelector('[data-removal-submit]');
        var submitting = false;
        function matches() { return input.value.trim().toLowerCase() === form.getAttribute('data-confirm-email'); }
        function update() { submit.disabled = submitting || submit.getAttribute('data-billing-blocked') === '1' || !matches(); }
        input.addEventListener('input', update);
        form.addEventListener('submit', function (event) {
            if (submitting || submit.disabled || !matches()) { event.preventDefault(); return; }
            submitting = true;
            submit.disabled = true;
            submit.textContent = 'Removing account…';
        });
        update();
    });
    var open = page.querySelector('[data-removal-modal][data-open-on-load]');
    if (open && win.bootstrap) win.bootstrap.Modal.getOrCreateInstance(open).show();
}));
