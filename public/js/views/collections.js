(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root.document);
}(typeof window !== 'undefined' ? window : this, function (doc) {
    'use strict';
    var page = doc.getElementById('collections-page');
    if (!page) return;
    var filters = page.querySelector('.collections-filters');
    var cards = Array.prototype.slice.call(page.querySelectorAll('[data-collection-category]'));
    var rail = page.querySelector('.collections-books');
    var controls = page.querySelector('[data-book-controls]');
    if (rail && controls) {
        var previous = controls.querySelector('[data-book-previous]');
        var next = controls.querySelector('[data-book-next]');
        var win = doc.defaultView;
        function updateBooks() {
            var maximum = rail.scrollWidth - rail.clientWidth;
            controls.hidden = maximum <= 1;
            // Allow the rail's 2px edge padding and fractional snap positions.
            previous.disabled = rail.scrollLeft <= 3;
            next.disabled = rail.scrollLeft >= maximum - 3;
        }
        function moveBooks(direction) {
            var items = rail.querySelectorAll('.collections-book');
            if (items.length < 2) return;
            var distance = items[1].offsetLeft - items[0].offsetLeft;
            var reduceMotion = win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches;
            rail.scrollBy({left: direction * distance, behavior: reduceMotion ? 'auto' : 'smooth'});
        }
        previous.addEventListener('click', function () { moveBooks(-1); });
        next.addEventListener('click', function () { moveBooks(1); });
        rail.addEventListener('scroll', updateBooks, {passive: true});
        win.addEventListener('resize', updateBooks);
        updateBooks();
    }
    if (filters) {
        var buttons = Array.prototype.slice.call(filters.querySelectorAll('button'));
        filters.hidden = false;
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var category = button.getAttribute('data-collection-filter');
                var count = 0;
                cards.forEach(function (card) {
                    card.hidden = category !== 'all' && card.getAttribute('data-collection-category') !== category;
                    if (!card.hidden) count++;
                });
                buttons.forEach(function (item) { item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
                page.querySelector('[data-collection-status]').textContent = count + (count === 1 ? ' collection shown' : ' collections shown');
            });
        });
    }
    Array.prototype.forEach.call(page.querySelectorAll('[data-collection-image]'), function (image) {
        function fallback() {
            if (image.getAttribute('src') === image.getAttribute('data-fallback')) return;
            image.setAttribute('src', image.getAttribute('data-fallback'));
        }
        image.addEventListener('error', fallback);
        if (image.complete && !image.naturalWidth) fallback();
    });
}));
