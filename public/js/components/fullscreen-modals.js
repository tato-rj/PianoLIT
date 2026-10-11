// Shared fullscreen shells restore from URLs and keep touch zoom inside their content.
module.exports = function (win) {
    var doc = win.document;
    var gestureModal = null, tap = null, lastTap = null;
    function resetGestures() { gestureModal = null; tap = null; lastTap = null; }
    function preventZoom(event) {
        var modal = doc.querySelector('.fullscreen-modal.show');
        if (modal !== gestureModal) { resetGestures(); gestureModal = modal; }
        if (!modal) return;
        // Cancel the browser default only: globe/score handlers still receive
        // the gesture and can perform their own content zoom.
        function prevent() { if (event.cancelable) event.preventDefault(); }
        if (event.type === 'wheel') {
            if (event.ctrlKey) prevent();
            return;
        }
        if (event.type === 'dblclick' || event.type.indexOf('gesture') === 0) {
            prevent(); tap = null; lastTap = null;
            return;
        }
        if (event.type === 'touchcancel' || event.touches.length > 1) {
            tap = null; lastTap = null;
            if (event.touches.length > 1) prevent();
            return;
        }
        if (event.type === 'touchstart') {
            var touch = event.touches[0];
            tap = touch ? {x: touch.clientX, y: touch.clientY, time: event.timeStamp} : null;
        } else if (event.type === 'touchmove') {
            var touch = event.touches[0];
            if (tap && touch && Math.hypot(touch.clientX - tap.x, touch.clientY - tap.y) > 10) tap = null;
        } else if (event.type === 'touchend') {
            var current = tap, previous = lastTap;
            tap = null;
            if (!current || event.touches.length || event.timeStamp - current.time > 350 ||
                event.target.closest('input, textarea, select, label, [contenteditable]:not([contenteditable="false"])')) {
                lastTap = null; return;
            }
            if (previous && event.timeStamp - previous.time < 350 && Math.hypot(current.x - previous.x, current.y - previous.y) < 24) {
                var wasPrevented = event.defaultPrevented;
                prevent();
                // Canceling touchend also cancels its native click. Keep repeat
                // taps on paging/zoom buttons and links working exactly once.
                var control = event.target.closest('button, a');
                if (!wasPrevented && event.defaultPrevented && control && !control.disabled) control.click();
            }
            lastTap = {x: current.x, y: current.y, time: event.timeStamp};
        }
    }
    ['wheel', 'gesturestart', 'gesturechange', 'gestureend', 'touchstart', 'touchmove', 'touchend', 'touchcancel', 'dblclick'].forEach(function (type) {
        doc.addEventListener(type, preventZoom, {passive: false, capture: true});
    });
    function isFullscreen(modal) {
        return modal && modal.id && modal.matches('.fullscreen-modal');
    }
    function write(id) {
        try {
            var url = new URL(win.location.href);
            if (id) url.searchParams.set('modal', id);
            else url.searchParams.delete('modal');
            // Like page tabs, overlays update this entry without adding Back steps.
            if (url.href !== win.location.href) win.history.replaceState(win.history.state, '', url.href);
        } catch (error) { /* The modal remains usable if history is unavailable. */ }
    }
    doc.addEventListener('shown.bs.modal', function (event) {
        if (isFullscreen(event.target)) { resetGestures(); write(event.target.id); }
    });
    doc.addEventListener('hidden.bs.modal', function (event) {
        if (gestureModal === event.target) resetGestures();
        // A finishing close must not erase a different modal's URL.
        if (isFullscreen(event.target) && new URL(win.location.href).searchParams.get('modal') === event.target.id) write(null);
    });
    function restore() {
        var id = new URL(win.location.href).searchParams.get('modal');
        if (!id) return;
        // Resolve only an existing fullscreen modal. Never execute a selector or
        // fetch markup from the URL, including for unavailable premium content.
        var modal = doc.getElementById(id);
        if (!isFullscreen(modal)) { write(null); return; }
        win.bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    function ready() {
        // Page controllers (notably eScore) also initialize on DOMContentLoaded.
        // Open after all of them have attached their Bootstrap lifecycle listeners.
        win.setTimeout(restore, 0);
    }
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', ready, {once: true});
    else ready();
};
