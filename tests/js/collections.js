const assert = require('assert');
const crypto = require('crypto');
const initialize = require('../../resources/js/views/collections');

module.exports = function () {
    function node(attributes) {
        return {
            attributes, hidden: false, events: {},
            getAttribute(name) { return this.attributes[name]; },
            setAttribute(name, value) { this.attributes[name] = value; },
            addEventListener(name, handler) { this.events[name] = handler; }
        };
    }
    const tagKey = label => label ? 'featured-' + crypto.createHash('sha256').update(label).digest('hex') : '';
    const cards = ['Theme', 'Theme', 'Composer', 'all', ''].map(label => node({'data-collection-category': tagKey(label)}));
    const buttons = ['all', tagKey('Theme'), tagKey('Composer'), tagKey('all')].map(category => node({'data-collection-filter': category}));
    const filters = {hidden: true, querySelectorAll() { return buttons; }};
    const status = {textContent: ''};
    const image = node({src: 'broken.jpg', 'data-fallback': 'fallback.webp'});
    image.complete = true;
    image.naturalWidth = 0;
    const page = {
        querySelector(selector) { return {'.collections-filters': filters, '[data-collection-status]': status}[selector] || null; },
        querySelectorAll(selector) { return {'[data-collection-category]': cards, '[data-collection-image]': [image]}[selector] || []; }
    };
    initialize({getElementById() { return page; }});
    assert.strictEqual(filters.hidden, false);
    buttons[1].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [false, false, true, true, true]);
    assert.strictEqual(status.textContent, '2 collections shown');
    buttons[2].events.click();
    assert.strictEqual(status.textContent, '1 collection shown');
    assert.strictEqual(buttons[1].attributes['aria-pressed'], 'false');
    assert.strictEqual(buttons[2].attributes['aria-pressed'], 'true');
    buttons[3].events.click();
    assert.deepStrictEqual(cards.map(card => card.hidden), [true, true, true, false, true], 'A saved tag named all must match only that tag');
    assert.strictEqual(status.textContent, '1 collection shown');
    buttons[0].events.click();
    assert(cards.every(card => !card.hidden), 'All restores every collection, including untagged ones');
    assert.strictEqual(image.attributes.src, 'fallback.webp');
    image.events.error();
    assert.strictEqual(image.attributes.src, 'fallback.webp');
    initialize({getElementById() { return null; }});

    let shownPrompt = null;
    const accessModal = {};
    const redirectedPage = {getAttribute(name) { assert.strictEqual(name, 'data-access-prompt'); return '#signup-collections-modal'; }, querySelector() { return null; }, querySelectorAll() { return []; }};
    initialize({getElementById() { return redirectedPage; }, querySelector(selector) { assert.strictEqual(selector, '#signup-collections-modal'); return accessModal; }, defaultView: {bootstrap: {Modal: {getOrCreateInstance(element) { return {show() { shownPrompt = element; }}; }}}}});
    assert.strictEqual(shownPrompt, accessModal, 'A direct blocked URL returns to Collections and opens the matching dialog');

    const rail = Object.assign(node({}), {
        scrollLeft: 0, scrollWidth: 2560, clientWidth: 1000,
        querySelectorAll() { return [{offsetLeft: 0}, {offsetLeft: 320}]; },
        scrollBy(options) {
            this.lastScroll = options;
            this.scrollLeft = Math.max(0, Math.min(this.scrollWidth - this.clientWidth, this.scrollLeft + options.left));
            this.events.scroll();
        }
    });
    const previous = node({}), next = node({});
    const controls = {hidden: true, querySelector(selector) { return selector === '[data-book-previous]' ? previous : next; }};
    const win = Object.assign(node({}), {matchMedia() { return {matches: true}; }});
    const bookPage = {
        querySelector(selector) { return {'.collections-books': rail, '[data-book-controls]': controls}[selector] || null; },
        querySelectorAll() { return []; }
    };
    initialize({getElementById() { return bookPage; }, defaultView: win});
    assert.strictEqual(controls.hidden, false);
    assert.strictEqual(previous.disabled, true);
    next.events.click();
    assert.strictEqual(rail.scrollLeft, 320);
    assert.strictEqual(rail.lastScroll.behavior, 'auto', 'Respect reduced motion');
    assert.strictEqual(previous.disabled, false);
    for (let i = 0; i < 5; i++) next.events.click();
    assert.strictEqual(next.disabled, true, 'Disable next at the last book');
    previous.events.click();
    assert.strictEqual(next.disabled, false);
    rail.scrollLeft = 2;
    rail.events.scroll();
    assert.strictEqual(previous.disabled, true, 'Native scroll snapping accounts for edge padding');
    rail.clientWidth = rail.scrollWidth;
    win.events.resize();
    assert.strictEqual(controls.hidden, true, 'Hide controls if the row fits');
};
