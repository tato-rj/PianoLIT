(function () {
    'use strict';
    var root = document.querySelector('[data-campaign-measurement]');
    if (!root || root.getAttribute('data-consent') !== 'granted') return;
    var token = root.getAttribute('data-token');
    if (!token) return;
    // Honor an existing opt-in without displaying campaign controls. Anonymous
    // arrivals are recorded on the server and do not grant conversion linkage.
    fetch(root.getAttribute('data-url'), {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': root.getAttribute('data-csrf')},
        body: JSON.stringify({choice: 'granted', token: token})
    }).catch(function () {
        // A failed optional acknowledgement must not interrupt the piece page.
    });
}());
