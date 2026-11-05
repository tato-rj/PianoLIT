(function () {
    'use strict';
    var state = document.querySelector('[data-shop-welcome-state]');
    if (!state) return;

    // Host-local UI flags only. Discounts remain authorized by the server.
    var key = 'pianolit.shop.newsletter.seen';
    var subscribedKey = 'pianolit.shop.newsletter.subscribed';
    var subscribed = state.dataset.subscribed === 'true';
    if (subscribed) {
        try { window.localStorage.setItem(subscribedKey, '1'); } catch (error) {}
    }
    var modal = document.querySelector('[data-shop-welcome]');
    var launcher = document.querySelector('[data-shop-welcome-launcher]');
    if (!modal || !launcher || !window.bootstrap) return;

    var autoShow = modal.dataset.shopWelcomeAuto === 'true';
    var retry = modal.dataset.shopWelcomeRetry === 'true';
    var timer = null;
    var finished = false;
    var open = false;
    var manual = false;
    var previousFocus = null;

    function hasSubscribed() {
        if (!subscribed) {
            try { subscribed = window.localStorage.getItem(subscribedKey) === '1'; } catch (error) {}
        }
        return subscribed;
    }
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
    function syncLauncher() {
        launcher.hidden = hasSubscribed() || open || (autoShow && !finished && !seen());
    }
    function show() {
        if (hasSubscribed() || open || blocked()) return;
        previousFocus = document.activeElement;
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    function schedule() {
        cancel();
        syncLauncher();
        if (hasSubscribed() || finished || (!autoShow && !retry) || (!retry && seen()) || blocked()) return;
        timer = window.setTimeout(function () {
            timer = null;
            if (finished || (!retry && seen())) { syncLauncher(); return; }
            show();
        }, 3000);
    }

    launcher.addEventListener('click', function () {
        cancel();
        manual = true;
        show();
        manual = false;
    });
    modal.addEventListener('show.bs.modal', function (event) {
        cancel();
        if (hasSubscribed()) { event.preventDefault(); return; }
        // Persist before opening so refreshes and other Shop tabs cannot repeat it.
        try { window.localStorage.setItem(key, '1'); }
        catch (error) { if (!manual && !retry) { event.preventDefault(); return; } }
        open = true;
        syncLauncher();
    });
    modal.addEventListener('shown.bs.modal', function () {
        finished = true;
        retry = false;
        cancel();
        syncLauncher();
    });
    modal.addEventListener('hidden.bs.modal', function () {
        open = false;
        syncLauncher();
        if (previousFocus && previousFocus.isConnected && (previousFocus !== launcher || !launcher.hidden)) previousFocus.focus({preventScroll: true});
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
        if (event.key === key && event.newValue !== null) { cancel(); syncLauncher(); }
        if (event.key === subscribedKey && event.newValue === '1') {
            subscribed = true;
            cancel(); syncLauncher();
            if (open) window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        }
    });
    schedule();
}());
