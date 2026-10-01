// Let Bootstrap finish closing a panel before it owns the next overlay.
// Otherwise the old panel can restore body scrolling after the new one locks it.
function waitForOffcanvas(event, Component) {
    if (event.defaultPrevented) return; // An AJAX component is still loading.

    const panels = Array.from(document.querySelectorAll('.offcanvas.show, .offcanvas.showing, .offcanvas.hiding'))
        .filter(panel => panel !== event.target);
    if (!panels.length) return;

    event.preventDefault();
    const target = event.target;
    const trigger = event.relatedTarget;
    let remaining = panels.length;
    panels.forEach(panel => {
        panel.addEventListener('hidden.bs.offcanvas', function () {
            if (--remaining !== 0) return;
            // Bootstrap has restored focus to the old panel's visible opener.
            // The next overlay's trigger lives inside that now-hidden panel.
            const returnFocus = document.activeElement;
            if (returnFocus && returnFocus !== document.body) {
                target.addEventListener('hidden.bs.' + Component.NAME, function () {
                    if (returnFocus.getClientRects().length) returnFocus.focus();
                }, {once: true});
            }
            Component.getOrCreateInstance(target).show(trigger);
        }, {once: true});
        window.bootstrap.Offcanvas.getOrCreateInstance(panel).hide();
    });
}

document.addEventListener('show.bs.modal', event => waitForOffcanvas(event, window.bootstrap.Modal));
document.addEventListener('show.bs.offcanvas', event => waitForOffcanvas(event, window.bootstrap.Offcanvas));
