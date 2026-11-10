(function () {
    const promotions = document.querySelector('[data-hub-promotions]');
    if (!promotions) return;

    const mobile = window.matchMedia('(max-width: 991.98px)');
    const measure = function () {
        document.body.style.setProperty('--hub-promotions-height', mobile.matches
            ? promotions.offsetHeight + 'px' : '0px');
    };

    if (window.ResizeObserver) new ResizeObserver(measure).observe(promotions);
    mobile.addEventListener('change', measure);
    window.addEventListener('resize', measure, {passive: true});
    window.addEventListener('pageshow', measure);
    if (document.fonts) document.fonts.ready.then(measure);
    measure();
}());
