// Initialized immediately after each server-rendered grid, before the main bundle.
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.PianoLITProductReveal = factory();
})(typeof window !== 'undefined' ? window : this, function () {
    return function (grid, win) {
        if (!grid || grid.dataset.productRevealBound) return;
        grid.dataset.productRevealBound = 'true';
        var motion = win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)');
        if (!win.IntersectionObserver || !motion || motion.matches) return;
        var cards = Array.from(grid.querySelectorAll('[data-product-reveal]'));
        if (!cards.length) return;
        var pending = new Set(cards);
        var observer;

        function reveal(card, animate) {
            if (!animate) card.classList.remove('shop-product-revealing');
            if (!pending.delete(card)) return;
            card.classList.remove('shop-product-pending');
            if (animate) card.classList.add('shop-product-revealing');
            if (observer) observer.unobserve(card);
        }

        function showAll() {
            cards.forEach(function (card) { reveal(card, false); });
            if (observer) observer.disconnect();
        }

        try {
            observer = new win.IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) reveal(entry.target, !motion.matches);
                });
            }, {threshold: 0, rootMargin: '0px 0px -24px 0px'});
            cards.forEach(function (card) {
                card.classList.add('shop-product-pending');
                observer.observe(card);
            });
        } catch (error) {
            showAll();
            return;
        }

        grid.addEventListener('focusin', function (event) {
            var card = event.target.closest('[data-product-reveal]');
            if (card && grid.contains(card)) reveal(card, false);
        });
        grid.addEventListener('animationend', function (event) {
            if (event.animationName === 'shop-product-enter') event.target.classList.remove('shop-product-revealing');
        });
        function motionChanged() { if (motion.matches) showAll(); }
        if (motion.addEventListener) motion.addEventListener('change', motionChanged);
        else if (motion.addListener) motion.addListener(motionChanged);
        win.addEventListener('beforeprint', showAll);
        win.addEventListener('pageshow', function (event) { if (event.persisted) showAll(); });
    };
});
