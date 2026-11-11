(function () {
    const promotions = document.querySelector('[data-hub-promotions]');
    if (!promotions) return;

    const mobile = window.matchMedia('(max-width: 991.98px)');
    const sentinel = document.querySelector('[data-hub-promotions-sentinel]');
    const updateSticky = function () {
        if (sentinel) promotions.classList.toggle('is-stuck', !mobile.matches && sentinel.getBoundingClientRect().bottom < 24);
    };
    const measureBar = function () {
        document.body.style.setProperty('--hub-promotions-height', mobile.matches
            ? promotions.offsetHeight + 'px' : '0px');
    };
    const refresh = function () {
        updateSticky();
        measureBar();
    };

    if (sentinel && window.IntersectionObserver) {
        new IntersectionObserver(updateSticky, {rootMargin: '-24px 0px 0px 0px'}).observe(sentinel);
    }
    if (window.ResizeObserver) new ResizeObserver(function () {
        if (mobile.matches) measureBar();
    }).observe(promotions);
    mobile.addEventListener('change', refresh);
    window.addEventListener('resize', refresh, {passive: true});
    window.addEventListener('pageshow', refresh);
    if (document.fonts) document.fonts.ready.then(refresh);
    refresh();
}());
