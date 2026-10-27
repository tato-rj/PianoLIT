(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', () => factory(root));
    else factory(root);
})(typeof window !== 'undefined' ? window : this, function initHomeScrollHint(win) {
    const hint = win.document.querySelector('[data-home-scroll-hint]');
    if (!hint || hint.dataset.scrollHintReady) return;
    hint.dataset.scrollHintReady = 'true';
    const button = hint.querySelector('button');
    let dismissed = false;

    function dismiss() {
        dismissed = true;
        hint.hidden = true;
        win.removeEventListener('scroll', onScroll);
    }

    function update() {
        if (dismissed) return;
        // A restored scroll position counts as already exploring this page.
        if ((win.scrollY || 0) > 16) { dismiss(); return; }
        hint.hidden = win.document.documentElement.scrollHeight <= win.innerHeight + 16;
    }

    function onScroll() {
        if ((win.scrollY || 0) > 16) dismiss();
    }

    button.addEventListener('click', () => {
        dismiss();
        const reduced = win.matchMedia('(prefers-reduced-motion: reduce)').matches;
        win.scrollBy({top: win.innerHeight * .75, behavior: reduced ? 'auto' : 'smooth'});
    });
    win.addEventListener('scroll', onScroll, {passive: true});
    win.addEventListener('resize', update);
    win.addEventListener('load', update);
    // Keep dismissal in this document only, including back/forward restoration.
    // Fresh navigation/reload gets a fresh hint, without cookies or storage.
    win.addEventListener('pageshow', update);
    if (win.document.fonts && win.document.fonts.ready) win.document.fonts.ready.then(update);
    update();
});
