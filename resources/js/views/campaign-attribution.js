(function () {
    'use strict';
    var root = document.querySelector('[data-campaign-measurement]');
    if (!root) return;
    var panel = root.querySelector('#campaign-measurement-choice');
    var settings = root.querySelector('[data-campaign-settings]');
    var status = root.querySelector('[data-campaign-status]');
    var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-campaign-choice]'));
    var busy = false;
    var token = root.getAttribute('data-token');
    settings.addEventListener('click', function () {
        panel.hidden = !panel.hidden;
        settings.setAttribute('aria-expanded', String(!panel.hidden));
    });
    function choose(choice) {
        if (busy) return;
        busy = true;
        buttons.forEach(function (button) { button.disabled = true; });
        status.textContent = 'Saving your choice…';
        // No URL, user identity, form fields or contact data are sent.
        fetch(root.getAttribute('data-url'), {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': root.getAttribute('data-csrf')},
            body: JSON.stringify({choice: choice, token: choice === 'granted' ? token || null : null})
        }).then(function (response) {
            if (!response.ok) throw new Error('Could not save');
            return response.json();
        }).then(function (data) {
            root.setAttribute('data-consent', data.choice);
            panel.hidden = true;
            settings.setAttribute('aria-expanded', 'false');
            status.textContent = data.choice === 'granted' ? 'Video link measurement allowed.' : 'Video link measurement is off.';
            token = null; // Reopening settings cannot reuse an old landing token.
        }).catch(function () {
            panel.hidden = false;
            settings.setAttribute('aria-expanded', 'true');
            status.textContent = 'Your choice could not be saved. Please reload this page and try again.';
        }).then(function () {
            busy = false;
            buttons.forEach(function (button) { button.disabled = false; });
        });
    }
    buttons.forEach(function (button) {
        button.addEventListener('click', function () { choose(button.getAttribute('data-campaign-choice')); });
    });
    if (token && root.getAttribute('data-consent') === 'granted') choose('granted');
}());
