(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.PianoLITProductRow = factory;
}(typeof window !== 'undefined' ? window : this, function (row, win) {
    'use strict';
    const track = row.querySelector('[data-product-row-track]');
    const controls = row.querySelector('[data-product-row-controls]');
    const previous = row.querySelector('[data-product-row-scroll="-1"]');
    const next = row.querySelector('[data-product-row-scroll="1"]');
    const reducedMotion = win.matchMedia('(prefers-reduced-motion: reduce)');

    function sync() {
        const end = track.scrollWidth - track.clientWidth;
        controls.hidden = end <= 1;
        previous.disabled = track.scrollLeft <= 1;
        next.disabled = track.scrollLeft >= end - 1;
    }

    [previous, next].forEach(function (button) {
        button.addEventListener('click', function () {
            track.scrollBy({left: Number(button.dataset.productRowScroll) * track.clientWidth, behavior: reducedMotion.matches ? 'auto' : 'smooth'});
        });
    });
    track.addEventListener('scroll', sync, {passive: true});
    win.addEventListener('pageshow', sync);
    if (win.ResizeObserver) new win.ResizeObserver(sync).observe(track);
    else win.addEventListener('resize', sync);
    sync();
}));
