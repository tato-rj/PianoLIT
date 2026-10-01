(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root.document);
}(typeof window !== 'undefined' ? window : this, function (doc) {
    'use strict';
    var page = doc.getElementById('folders-list');
    if (!page) return;
    var input = page.querySelector('#folder-search');
    var cards = Array.prototype.slice.call(page.querySelectorAll('[data-folder-search]'));
    var status = page.querySelector('[data-folder-status]');
    var empty = page.querySelector('[data-folder-no-results]');
    function normalize(value) {
        return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }
    var searchable = cards.map(function (card) { return normalize(card.getAttribute('data-folder-search')); });
    function filter() {
        var query = normalize(input.value).trim();
        var terms = query ? query.split(/\s+/) : [];
        var count = 0;
        cards.forEach(function (card, index) {
            card.hidden = !terms.every(function (term) { return searchable[index].indexOf(term) !== -1; });
            if (!card.hidden) count++;
        });
        empty.hidden = !query || count !== 0;
        status.textContent = query ? count + (count === 1 ? ' folder found' : ' folders found') : '';
    }
    page.querySelector('.my-pieces-favorites__search').hidden = false;
    input.addEventListener('input', filter);
    input.addEventListener('search', filter);
    filter();
}));
