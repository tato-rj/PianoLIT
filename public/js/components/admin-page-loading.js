(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
})(typeof window !== 'undefined' ? window : this, function (win) {
    'use strict';

    const doc = win.document;
    const indicator = doc.querySelector('[data-admin-page-loading]');
    if (!indicator || indicator.pageLoadingReady) return;
    indicator.pageLoadingReady = true;

    const bar = indicator.querySelector('.admin-page-loading__bar');
    let progress = 0;
    let trickle, fade, hide;
    let running = false;
    let leaving = false;
    let pending = null;

    function clearTimers() {
        win.clearInterval(trickle);
        win.clearTimeout(fade);
        win.clearTimeout(hide);
    }

    function start() {
        if (running) return;
        clearTimers();
        running = true;
        indicator.hidden = false;
        indicator.classList.remove('is-complete');
        progress = 0.08;
        bar.style.transform = 'scaleX(' + progress + ')';
        // Estimated activity only: reserve completion for the actual load event.
        trickle = win.setInterval(function () {
            progress += (0.9 - progress) * 0.12;
            bar.style.transform = 'scaleX(' + progress + ')';
        }, 400);
    }

    function finish() {
        clearTimers();
        running = false;
        leaving = false;
        pending = null;
        if (indicator.hidden) return;
        bar.style.transform = 'scaleX(1)';
        fade = win.setTimeout(function () {
            indicator.classList.add('is-complete');
            hide = win.setTimeout(function () { indicator.hidden = true; }, 200);
        }, 180);
    }

    win.addEventListener('load', function () { if (!leaving) finish(); });
    win.addEventListener('pageshow', function (event) { if (event.persisted) finish(); });

    if (win.navigation && win.navigation.addEventListener) {
        // Observe native navigation without intercepting links, forms or history.
        win.navigation.addEventListener('navigate', function (event) {
            if (event.destination.sameDocument || event.hashChange || event.downloadRequest !== null) return;
            Promise.resolve().then(function () {
                if (event.defaultPrevented || event.signal.aborted) return;
                pending = event;
                leaving = true;
                start();
                event.signal.addEventListener('abort', function () {
                    if (pending === event) finish();
                }, {once: true});
            });
        });
        win.navigation.addEventListener('navigateerror', finish);
    } else {
        // Older browsers still report real page departures, including refreshes.
        win.addEventListener('beforeunload', function (event) {
            Promise.resolve().then(function () {
                // Leave existing unsaved-change confirmations in control.
                if (event.defaultPrevented || event.returnValue) return;
                leaving = true;
                start();
            });
        });
        doc.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && leaving) finish();
        });
    }

    if (doc.readyState !== 'complete') start();
});
