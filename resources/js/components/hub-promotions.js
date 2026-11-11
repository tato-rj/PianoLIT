(function () {
    const promotions = document.querySelector('[data-hub-promotions]');
    if (!promotions) return;

    const mobile = window.matchMedia('(max-width: 991.98px)');
    const sentinel = document.querySelector('[data-hub-promotions-sentinel]');
    const measure = function () {
        if (sentinel) promotions.classList.toggle('is-stuck', !mobile.matches && sentinel.getBoundingClientRect().bottom < 24);
        document.body.style.setProperty('--hub-promotions-height', mobile.matches
            ? promotions.offsetHeight + 'px' : '0px');
    };

    if (sentinel && window.IntersectionObserver) {
        new IntersectionObserver(measure, {rootMargin: '-24px 0px 0px 0px'}).observe(sentinel);
    }
    if (window.ResizeObserver) new ResizeObserver(measure).observe(promotions);
    mobile.addEventListener('change', measure);
    window.addEventListener('resize', measure, {passive: true});
    window.addEventListener('pageshow', measure);
    if (document.fonts) document.fonts.ready.then(measure);
    measure();
}());
