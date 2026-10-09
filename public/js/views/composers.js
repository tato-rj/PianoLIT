(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else factory(root.document);
}(typeof window !== 'undefined' ? window : this, function (doc) {
    'use strict';
    var page = doc.getElementById('composers-directory');
    if (!page) return;
    var list = page.querySelector('#composers-list');
    var cards = Array.prototype.slice.call(list.querySelectorAll('.composer-card'));
    var form = page.querySelector('#search-form');
    var search = form.querySelector('input[name="search"]');
    var filters = Array.prototype.slice.call(page.querySelectorAll('[data-composer-filter]'));
    var letters = Array.prototype.slice.call(page.querySelectorAll('[data-composer-letter]'));
    var sorts = Array.prototype.slice.call(page.querySelectorAll('[data-composer-sort]'));
    var empty = page.querySelector('[data-composer-empty]');
    var status = page.querySelector('[data-composer-status]');
    var reset = page.querySelector('[data-composer-reset]');
    var erase = form.querySelector('[data-erase]');
    var filter = 'all', letter = 'all', sort = /[?&]sort=name(?:&|$)/.test(doc.location ? doc.location.search : '') ? 'name' : 'pieces';

    function normalize(value) {
        return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }
    // Cache normalized metadata once, rather than inspecting every work on each keystroke.
    var entries = cards.map(function (card, index) {
        var regions = normalize(card.getAttribute('data-composer-regions') || '').split('|');
        if (regions.indexOf('north america') !== -1 || regions.indexOf('south america') !== -1) regions.push('america');
        return {
            card: card, index: index,
            regions: regions,
            name: normalize(card.getAttribute('data-composer-name') || ''),
            search: normalize(card.getAttribute('data-composer-search') || ''),
            popular: card.getAttribute('data-composer-popular') === 'true',
            created: Number(card.getAttribute('data-composer-created')) || 0,
            pieces: Number(card.getAttribute('data-composer-pieces')) || 0
        };
    });
    function select(buttons, attribute, value) {
        buttons.forEach(function (button) {
            var active = button.getAttribute(attribute) === value;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }
    function update() {
        // Complete geographic terms filter country metadata, never incidental
        // substrings in names (Goolkasian) or works (Fantasia).
        var regions = [];
        var terms = normalize(search.value).replace(/\s+/g, ' ').replace(
            /(^|\s)(north america|south america|latin america|africa|antarctica|asia|europe|oceania|america)(?=\s|$)/g,
            function (match, space, region) { regions.push(region); return ' '; }
        );
        var words = terms.split(/\s+/).filter(Boolean);
        var count = 0;
        entries.sort(function (a, b) {
            var difference = sort === 'name' ? a.name.localeCompare(b.name) :
                sort === 'recent' ? b.created - a.created : b.pieces - a.pieces;
            return difference || a.name.localeCompare(b.name) || a.index - b.index;
        }).forEach(function (entry) {
            entry.card.hidden = (filter === 'popular' && !entry.popular) ||
                (letter !== 'all' && entry.name.charAt(0) !== letter) ||
                !regions.every(function (region) { return entry.regions.indexOf(region) !== -1; }) ||
                !words.every(function (word) { return entry.search.indexOf(word) !== -1; });
            if (!entry.card.hidden) count++;
            list.appendChild(entry.card);
        });
        select(filters, 'data-composer-filter', filter);
        select(letters, 'data-composer-letter', letter);
        select(sorts, 'data-composer-sort', sort);
        empty.hidden = count !== 0;
        status.textContent = count + (count === 1 ? ' composer shown' : ' composers shown');
        erase.style.display = search.value ? '' : 'none';
    }
    filters.forEach(function (button) {
        button.addEventListener('click', function () {
            filter = button.getAttribute('data-composer-filter');
            sort = filter === 'recent' ? 'recent' : 'pieces';
            update();
        });
    });
    letters.forEach(function (button) {
        button.addEventListener('click', function () {
            letter = button.getAttribute('data-composer-letter');
            update();
        });
    });
    sorts.forEach(function (button) {
        button.addEventListener('click', function () {
            sort = button.getAttribute('data-composer-sort');
            if (filter === 'recent' && sort !== 'recent') filter = 'all';
            update();
        });
    });
    search.addEventListener('input', update);
    form.addEventListener('submit', function (event) { event.preventDefault(); update(); });
    // The shared clear control is a button on this page, so it also works by keyboard.
    erase.addEventListener('click', function () { search.value = ''; update(); search.focus(); });
    reset.addEventListener('click', function () {
        search.value = ''; filter = 'all'; letter = 'all'; sort = 'pieces';
        update(); search.focus();
    });
    Array.prototype.forEach.call(page.querySelectorAll('[data-composer-controls]'), function (control) { control.hidden = false; });
    reset.hidden = false;
    update();
}));
