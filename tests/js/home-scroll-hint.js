const assert = require('assert');
const init = require('../../resources/js/components/home-scroll-hint');

function setup({scrollY = 0, pageHeight = 1800, reduced = false} = {}) {
    const events = new Map();
    const button = {addEventListener(name, handler) { this[name] = handler; }};
    const hint = {hidden: true, dataset: {}, querySelector: () => button};
    const win = {
        scrollY, innerHeight: 800, scrolls: [],
        document: {querySelector: () => hint, documentElement: {scrollHeight: pageHeight}},
        addEventListener(name, handler, options) { events.set(name, {handler, options}); },
        removeEventListener(name) { events.delete(name); },
        matchMedia: () => ({matches: reduced}),
        scrollBy(options) { this.scrolls.push(options); }
    };
    init(win);
    return {win, hint, button, events, fire(name) { events.get(name)?.handler(); }};
}

function test() {
    const s = setup();
    assert.strictEqual(s.hint.hidden, false, 'A new visit at the top offers the hint.');
    assert.strictEqual(s.events.get('scroll').options.passive, true);
    s.win.scrollY = 8; s.fire('scroll');
    assert.strictEqual(s.hint.hidden, false, 'Tiny initial scroll jitter does not dismiss it.');
    s.win.scrollY = 24; s.fire('scroll');
    assert.strictEqual(s.hint.hidden, true, 'Scrolling down dismisses the hint.');
    assert.strictEqual(s.events.has('scroll'), false, 'Dismissal releases its scroll listener.');
    s.win.scrollY = 0;
    s.fire('resize'); s.fire('pageshow'); s.fire('load'); init(s.win);
    assert.strictEqual(s.hint.hidden, true, 'Returning to the top or restoring the same document does not reset dismissal.');
    assert.strictEqual(setup().hint.hidden, false, 'A fresh visit can show the hint again.');
    assert.strictEqual(setup({scrollY: 200}).hint.hidden, true, 'A restored scroll position starts dismissed.');

    for (const reduced of [false, true]) {
        const clicked = setup({reduced}); clicked.button.click();
        assert.strictEqual(clicked.hint.hidden, true);
        assert.deepStrictEqual(clicked.win.scrolls, [{top: 600, behavior: reduced ? 'auto' : 'smooth'}]);
        clicked.fire('pageshow');
        assert.strictEqual(clicked.hint.hidden, true);
    }
    const short = setup({pageHeight: 800});
    assert.strictEqual(short.hint.hidden, true, 'A page with nothing below the fold needs no hint.');
    short.win.document.documentElement.scrollHeight = 1800; short.fire('resize');
    assert.strictEqual(short.hint.hidden, false);
    init({document: {querySelector: () => null}});
    console.log('Passed: homepage scroll hint dismissal, fresh visits, restoration, click scrolling, reduced motion and non-scrollable pages.');
}
module.exports = test;
if (require.main === module) test();
