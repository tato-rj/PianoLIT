const assert = require('assert');
const initialize = require('../../resources/js/views/composers');

module.exports = function () {
    function node(attrs = {}) {
        return {
            attrs, hidden: true, events: {}, style: {}, value: '', textContent: '',
            classList: {toggle() {}},
            getAttribute(key) { return this.attrs[key]; },
            setAttribute(key, value) { this.attrs[key] = value; },
            addEventListener(key, handler) { this.events[key] = handler; },
            focus() { this.focused = true; }
        };
    }
    const cards = [
        ['Bach', 'Johann Sebastian Bach Germany Europe Prelude and Fugue The Well-Tempered Clavier', true, 1, 20],
        ['Chopin', 'Frédéric Chopin Poland Europe Étude Douze Études', true, 2, 15],
        ['Price', 'Florence Price United States North America Fantasie', false, 3, 2],
    ].map(([name, search, popular, created, pieces]) => node({
        'data-composer-name': name, 'data-composer-search': search,
        'data-composer-popular': String(popular), 'data-composer-created': created, 'data-composer-pieces': pieces
    }));
    const filters = ['all', 'popular', 'recent'].map(value => node({'data-composer-filter': value}));
    const letters = ['all', 'b', 'c', 'p'].map(value => node({'data-composer-letter': value}));
    const sorts = ['pieces', 'name', 'recent'].map(value => node({'data-composer-sort': value}));
    const search = node(), erase = node(), empty = node(), status = node(), reset = node(), control = node();
    const order = [];
    const list = {querySelectorAll() { return cards; }, appendChild(card) { const index = order.indexOf(card); if (index >= 0) order.splice(index, 1); order.push(card); }};
    const form = Object.assign(node(), {querySelector(selector) { return selector === '[data-erase]' ? erase : search; }});
    const page = {
        querySelector(selector) { return {'#composers-list': list, '#search-form': form, '[data-composer-empty]': empty, '[data-composer-status]': status, '[data-composer-reset]': reset}[selector]; },
        querySelectorAll(selector) { return {'[data-composer-filter]': filters, '[data-composer-letter]': letters, '[data-composer-sort]': sorts, '[data-composer-controls]': [control]}[selector]; }
    };
    initialize({getElementById() { return page; }});
    assert.strictEqual(status.textContent, '3 composers shown');
    assert.strictEqual(control.hidden, false);
    search.value = '  EUROPE  '; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true], 'Continent search ignores case and whitespace');
    letters[2].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Continent search combines with surname filters');
    reset.events.click();
    search.value = 'europe prelude'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Continent and work terms combine');
    search.value = 'north america'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false], 'Multiword continents find their composers');
    filters[1].events.click();
    assert.strictEqual(empty.hidden, false, 'Continent search respects the popular filter');
    reset.events.click();
    search.value = 'well tempered clavier'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Collection names find their composer');
    letters[2].events.click();
    assert.strictEqual(empty.hidden, false, 'Collection search combines with surname filters');
    reset.events.click();
    search.value = 'douze etudes'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Collection searches ignore accents');
    reset.events.click();
    search.value = 'poland etude'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true], 'Country/work search ignores accents');
    letters[1].events.click();
    assert.strictEqual(empty.hidden, false, 'Search and surname letter combine');
    assert.strictEqual(status.textContent, '0 composers shown');
    reset.events.click();
    filters[1].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true]);
    search.value = 'fugue'; search.events.input();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true], 'Popular combines with work search');
    erase.events.click();
    assert.strictEqual(search.value, '');
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true], 'Clear search preserves the selected filter');
    assert.strictEqual(search.focused, true);
    reset.events.click(); filters[2].events.click();
    assert.deepStrictEqual(order, [cards[2], cards[1], cards[0]], 'Recently added uses catalog creation date');
    assert(cards.every(card => !card.hidden), 'Recent ordering retains the whole directory');
    sorts[1].events.click();
    assert.deepStrictEqual(order, cards, 'Surname sorting is alphabetical');
    assert.strictEqual(filters[0].attrs['aria-pressed'], 'true');
    letters[3].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false]);
    search.value = 'nobody'; search.events.input(); reset.events.click();
    assert(cards.every(card => !card.hidden));
    assert.strictEqual(empty.hidden, true);
    let prevented = false;
    form.events.submit({preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true, 'Directory search never submits to mobile or global search');
    initialize({getElementById() { return null; }});
};
