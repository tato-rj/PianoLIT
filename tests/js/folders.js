const assert = require('assert');
const initialize = require('../../resources/js/views/folders');

module.exports = function () {
    const input = {value: '', events: {}, addEventListener(name, handler) { this.events[name] = handler; }};
    const cards = ['Book 3 Allegro M. Clementi Muzio Clementi', 'To record Nocturne F. Chopin Rêverie Claude Debussy', 'Empty folder'].map(text => ({
        hidden: false, getAttribute() { return text; }
    }));
    const status = {textContent: ''}, empty = {hidden: true}, search = {hidden: true};
    const page = {
        querySelector(selector) { return {'#folder-search': input, '[data-folder-status]': status, '[data-folder-no-results]': empty, '.my-pieces-favorites__search': search}[selector]; },
        querySelectorAll() { return cards; }
    };
    initialize({getElementById() { return page; }});
    assert.strictEqual(search.hidden, false);
    function type(value) { input.value = value; input.events.input(); }
    type('  MUZIO  ');
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, true, true]);
    assert.strictEqual(status.textContent, '1 folder found');
    type('debussy reverie');
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, false, true]);
    type('empty');
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, false]);
    type('missing piece');
    assert(cards.every(card => card.hidden));
    assert.strictEqual(status.textContent, '0 folders found');
    assert.strictEqual(empty.hidden, false);
    input.value = ''; input.events.search();
    assert(cards.every(card => !card.hidden));
    assert.strictEqual(status.textContent, '');
    assert.strictEqual(empty.hidden, true);
    type('folder');
    assert.strictEqual(status.textContent, '1 folder found');
    initialize({getElementById() { return null; }});
    page.querySelectorAll = () => [];
    input.value = '';
    initialize({getElementById() { return page; }});
    assert.strictEqual(empty.hidden, true);
    type('Bach');
    assert.strictEqual(empty.hidden, false);
};
