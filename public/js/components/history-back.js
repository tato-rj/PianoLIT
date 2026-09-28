(function () {
    var links = document.querySelectorAll('[data-history-back]');
    if (!links.length) return;

    // Keep this decision with the history entry, including after reloads and
    // returns to a new tab's first page (history.length also counts forward pages).
    var state = window.history.state || {};
    var canGoBack = state.pianolitCanGoBack;
    if (typeof canGoBack !== 'boolean') {
        canGoBack = false;
        try {
            canGoBack = window.history.length > 1 &&
                new URL(document.referrer).origin === window.location.origin;
        } catch (error) {
            // Direct visits and missing referrers use the link's fallback.
        }
        try {
            window.history.replaceState(Object.assign({}, state, {
                pianolitCanGoBack: canGoBack
            }), '');
        } catch (error) {
            // Back still works when the browser disallows updating history state.
        }
    }

    Array.prototype.forEach.call(links, function (link) {
        link.addEventListener('click', function (event) {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey ||
                event.ctrlKey || event.shiftKey || event.altKey || !canGoBack) return;

            event.preventDefault();
            window.history.back();
        });
    });
})();
