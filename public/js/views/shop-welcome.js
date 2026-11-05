(function () {
    'use strict';
    var modal = document.querySelector('[data-shop-welcome]');
    if (!modal || !window.bootstrap) return;

    // Host-local and permanent until browser data is cleared; never store an email.
    var key = 'pianolit.shop.newsletter.seen';
    var retry = modal.dataset.shopWelcomeRetry === 'true';
    var timer = null;
    var finished = false;
    var previousFocus = null;

    function seen() {
        try { return window.localStorage.getItem(key) !== null; }
        catch (error) { return true; } // Avoid repeated prompts when storage is blocked.
    }
    function cancel() {
        window.clearTimeout(timer);
        timer = null;
    }
    function blocked() {
        return document.hidden || document.querySelector('.modal.show, .modal-backdrop, .offcanvas.show, .offcanvas.showing, .offcanvas-backdrop');
    }
    function schedule() {
        cancel();
        if (finished || (!retry && seen()) || blocked()) return;
        timer = window.setTimeout(function () {
            timer = null;
            if (finished || (!retry && seen()) || blocked()) return;
            previousFocus = document.activeElement;
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        }, 3000);
    }

    modal.addEventListener('show.bs.modal', function (event) {
        // Persist before opening so refreshes and other Shop tabs cannot repeat it.
        try { window.localStorage.setItem(key, '1'); }
        catch (error) { if (!retry) event.preventDefault(); }
    });
    modal.addEventListener('shown.bs.modal', function () {
        finished = true;
        retry = false;
        cancel();
    });
    modal.addEventListener('hidden.bs.modal', function () {
        if (previousFocus && previousFocus.isConnected) previousFocus.focus({preventScroll: true});
    });
    document.addEventListener('visibilitychange', schedule);
    ['show.bs.modal', 'show.bs.offcanvas'].forEach(function (name) {
        document.addEventListener(name, cancel);
    });
    ['hidden.bs.modal', 'hidden.bs.offcanvas'].forEach(function (name) {
        document.addEventListener(name, schedule);
    });
    window.addEventListener('pagehide', cancel);
    window.addEventListener('pageshow', schedule);
    window.addEventListener('storage', function (event) {
        if (event.key === key && event.newValue !== null) cancel();
    });
    schedule();
}());
