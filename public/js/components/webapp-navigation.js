(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root);
})(typeof window === 'undefined' ? this : window, function (win) {
    'use strict';
    const doc = win.document;
    const menu = doc.querySelector('[data-webapp-navigation]');
    if (!menu || menu.dataset.navigationReady) return;
    menu.dataset.navigationReady = 'true';
    const mobile = win.matchMedia('(max-width: 991.98px)');
    const scoreControls = doc.querySelector('.score-editor .score-footer');
    let hidden = false;
    let space = -1;
    let direction = 0;
    let travel = 0;
    let lastY = scrollY();
    let lastTime = win.performance.now();
    let frame = null;

    // Clamp iOS rubber-band scrolling at both ends before deciding direction.
    function scrollY() {
        const end = Math.max(0, doc.documentElement.scrollHeight - win.innerHeight);
        return Math.max(0, Math.min(win.scrollY || 0, end));
    }

    function publishSpace() {
        doc.documentElement.style.setProperty('--webapp-menu-space', space + 'px');
        doc.documentElement.style.setProperty('--webapp-menu-height', (hidden ? 0 : space) + 'px');
    }

    function reveal() {
        setHidden(false);
        direction = travel = 0;
        lastY = scrollY();
        lastTime = win.performance.now();
    }

    function setHidden(next) {
        if (hidden === next) return;
        hidden = next;
        menu.classList.toggle('is-scrolled-away', hidden);
        publishSpace();
    }

    function measure() {
        if (!mobile.matches) reveal();
        if (scoreControls) {
            const controlsHeight = scoreControls.getBoundingClientRect().height;
            // Hidden tabs report zero; keep the last visible measurement.
            if (controlsHeight) doc.documentElement.style.setProperty('--score-controls-space', Math.ceil(controlsHeight) + 'px');
        }
        const height = menu.getBoundingClientRect().height;
        const next = height ? Math.ceil(height + (parseFloat(win.getComputedStyle(menu).bottom) || 0)) : 0;
        if (space !== next) { space = next; publishSpace(); }
    }

    function pinned() {
        if (menu.contains(doc.activeElement)) return true;
        if (doc.querySelector('.account-page--prepared, .modal.show, .modal.showing, .offcanvas.show, .offcanvas.showing')) return true;
        const popup = doc.getElementById('bottom-popup');
        return popup && win.getComputedStyle(popup).display !== 'none';
    }

    function update() {
        frame = null;
        const y = scrollY();
        const now = win.performance.now();
        const elapsed = Math.max(1, Math.min(100, now - lastTime));
        const delta = y - lastY;
        if (now - lastTime > 180) direction = travel = 0;
        lastY = y;
        lastTime = now;
        if (!mobile.matches || y <= 32 || pinned()) { reveal(); return; }
        if (!delta) return;
        const nextDirection = delta > 0 ? 1 : -1;
        if (nextDirection !== direction) { direction = nextDirection; travel = 0; }
        travel += Math.abs(delta);
        if (direction < 0 && travel >= 8) setHidden(false);
        if (direction > 0 && travel >= 48 && delta / elapsed >= 0.45 && y > 96) setHidden(true);
    }

    win.addEventListener('scroll', () => {
        if (frame === null) frame = win.requestAnimationFrame(update);
    }, {passive: true});
    win.addEventListener('resize', measure, {passive: true});
    win.addEventListener('pageshow', () => { reveal(); measure(); });
    menu.addEventListener('focusin', reveal);
    ['show.bs.offcanvas', 'hidden.bs.offcanvas', 'show.bs.modal', 'hidden.bs.modal'].forEach(name => doc.addEventListener(name, reveal));
    if (win.ResizeObserver) {
        const observer = new win.ResizeObserver(measure);
        observer.observe(menu);
        if (scoreControls) observer.observe(scoreControls);
    }
    if (win.visualViewport) win.visualViewport.addEventListener('resize', measure, {passive: true});
    const popup = doc.getElementById('bottom-popup');
    if (popup && win.MutationObserver) {
        new win.MutationObserver(() => {
            if (win.getComputedStyle(popup).display !== 'none') reveal();
        }).observe(popup, {attributes: true, attributeFilter: ['style', 'class', 'hidden']});
    }
    measure();
    return {measure};
});
