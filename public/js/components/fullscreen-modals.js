// All modals using the shared fullscreen shell get reloadable URLs automatically.
module.exports = function (win) {
    var doc = win.document;
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
        if (isFullscreen(event.target)) write(event.target.id);
    });
    doc.addEventListener('hidden.bs.modal', function (event) {
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
