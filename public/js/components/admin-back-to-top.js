(function () {
    'use strict';

    var button = document.querySelector('[data-admin-back-to-top]');
    if (!button) return;

    var scheduled = false;

    function updateVisibility() {
        scheduled = false;
        // 100vh is one viewport height, recalculated after resizing.
        button.hidden = window.pageYOffset <= window.innerHeight;
    }

    function scheduleUpdate() {
        if (scheduled) return;
        scheduled = true;
        window.requestAnimationFrame(updateVisibility);
    }

    button.addEventListener('click', function () {
        var main = document.getElementById('admin-main');
        if (main) main.focus({preventScroll: true});

        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({top: 0, behavior: reduceMotion ? 'auto' : 'smooth'});
    });

    window.addEventListener('scroll', scheduleUpdate, {passive: true});
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('pageshow', scheduleUpdate);
    updateVisibility();
}());
