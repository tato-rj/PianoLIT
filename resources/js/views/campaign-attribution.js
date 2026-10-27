(function () {
    'use strict';
    var root = document.querySelector('[data-campaign-measurement]');
    if (!root || root.getAttribute('data-consent') !== 'granted') return;
    var token = root.getAttribute('data-token');
    var measurement = root.getAttribute('data-measurement-id');
    var submitted = false;
    function acknowledge(context) {
        if (submitted || (!token && !context)) return;
        submitted = true;
        var payload = {choice: 'granted', token: token || null};
        if (context) { payload.client_id = context.client_id; payload.session_id = context.session_id || null; }
        fetch(root.getAttribute('data-url'), {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': root.getAttribute('data-csrf')},
            body: JSON.stringify(payload)
        }).catch(function () {});
    }
    if (!measurement || !/^G-[A-Z0-9]+$/.test(measurement)) { acknowledge(null); return; }
    // Use the supported Google API, not assumptions about changing cookie formats.
    // This only retrieves IDs; it never emits a trial or purchase from the browser.
    window.dataLayer = window.dataLayer || [];
    var get = window.gtag || function () { window.dataLayer.push(arguments); };
    var client, session, clientDone = false, sessionDone = false;
    function complete() {
        if (!clientDone || !sessionDone) return;
        clearTimeout(timer);
        acknowledge(/^\d{1,20}\.\d{1,20}$/.test(client || '') ? {client_id: client, session_id: session} : null);
    }
    var timer = setTimeout(function () { acknowledge(null); }, 2500);
    get('get', measurement, 'client_id', function (value) { client = value; clientDone = true; complete(); });
    get('get', measurement, 'session_id', function (value) { session = value; sessionDone = true; complete(); });
}());
