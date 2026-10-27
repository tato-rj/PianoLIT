const assert = require('assert');
const init = require('../../resources/js/components/project-deck');

function setup({mobile = true, reduced = false, count = 7, width = 390, height = 844} = {}) {
    function element() {
        const values = new Map();
        return {dataset: {}, events: {}, style: {
            setProperty(name, value) { values.set(name, String(value)); },
            removeProperty(name) { values.delete(name); },
            getPropertyValue(name) { return values.get(name) || ''; }
        }, addEventListener(name, fn, options) { this.events[name] = fn; this.options = {...this.options, [name]: options}; }};
    }
    const deck = element(), doc = element();
    const frames = new Map();
    let id = 0, deckTop = 360;
    const small = Object.assign(element(), {matches: mobile});
    const motion = Object.assign(element(), {matches: reduced});
    const win = Object.assign(element(), {
        innerWidth: width, innerHeight: height, scrollY: 0, document: doc,
        matchMedia: query => query.includes('reduced') ? motion : small,
        getComputedStyle: () => ({rowGap: '8px'}),
        requestAnimationFrame(fn) { frames.set(++id, fn); return id; },
        cancelAnimationFrame(key) { frames.delete(key); },
        scrollTo({top}) { this.scrollY = top; this.scrollCalls++; }, scrollCalls: 0
    });
    const cards = Array.from({length: count}, (_, index) => {
        const card = element();
        card.dataset.project = 'hub-' + index;
        card.href = '/hub-' + index;
        card.getBoundingClientRect = () => ({width: win.innerWidth - 30});
        card.querySelector = () => ({getBoundingClientRect: () => ({height: card.style.getPropertyValue('--project-open') === '1' ? 144 : 124})});
        card.closest = () => card;
        card.matches = () => card.keyboardFocus || false;
        return card;
    });
    const deckHeight = () => cards.length * Number.parseFloat(deck.style.getPropertyValue('--project-compact-height') || 144) +
        Number.parseFloat(deck.style.getPropertyValue('--project-expansion') || 360) * cards.reduce((sum, card) => sum + Number(card.style.getPropertyValue('--project-open')), 0) +
        (cards.length - 1) * 8 + Number.parseFloat(deck.style.getPropertyValue('--project-scroll-tail') || 0);
    deck.querySelectorAll = () => cards;
    deck.querySelector = () => ({});
    deck.getBoundingClientRect = () => ({top: deckTop - win.scrollY, bottom: deckTop + deckHeight() - win.scrollY});
    doc.documentElement = {};
    Object.defineProperty(doc.documentElement, 'scrollHeight', {get: () => deckTop + deckHeight() + 80});
    const flush = () => { for (const [key, fn] of [...frames]) { frames.delete(key); fn(); } };
    const scroll = top => { win.scrollY = top; win.events.scroll(); flush(); };
    const open = () => cards.map(card => Number(card.style.getPropertyValue('--project-open')));
    init(deck, win);
    return {deck, cards, doc, win, small, motion, frames, scroll, flush, open, deckHeight};
}

function test() {
    const s = setup();
    const {deck, cards, win, scroll, open, deckHeight} = s;
    assert.deepStrictEqual(open(), [1, 0, 0, 0, 0, 0, 0]);
    assert.strictEqual(win.options.scroll.passive, true, 'Native scrolling remains passive.');
    assert.strictEqual(win.events.wheel, undefined);
    assert.strictEqual(deck.events.click, undefined, 'Cards remain ordinary links without an extra activation step.');
    const total = deckHeight();
    // 504px full card centered in 844px: first handoff starts at 360 - 170.
    scroll(190 + 76);
    assert.deepStrictEqual(open(), [.5, .5, 0, 0, 0, 0, 0]);
    assert.strictEqual(deckHeight(), total, 'Adjacent cards share expansion without changing the list height.');
    scroll(190 + 152 * 4);
    assert.deepStrictEqual(open(), [0, 0, 0, 0, 1, 0, 0], 'A hub from the compact desktop row also expands fully.');
    scroll(190 + 152);
    assert.deepStrictEqual(open(), [0, 1, 0, 0, 0, 0, 0], 'Reverse scrolling restores an earlier card.');
    scroll(0);
    assert.strictEqual(open()[0], 1);
    scroll(s.doc.documentElement.scrollHeight - win.innerHeight);
    assert.strictEqual(open()[6], 1, 'The final card can fully expand before the page ends.');
    assert.strictEqual(win.scrollCalls, 0, 'Scroll effects never move the page themselves.');
    assert.strictEqual(s.frames.size, 0, 'No animation loop runs after scrolling stops.');
    assert.deepStrictEqual(cards.map(card => card.href), Array.from({length: 7}, (_, i) => '/hub-' + i));

    scroll(342);
    const before = open();
    win.innerHeight = 760; win.events.resize(); s.flush();
    assert.deepStrictEqual(open(), before, 'Collapsing mobile browser chrome does not shift the activation anchors.');
    cards[3].keyboardFocus = true;
    deck.events.focusin({target: cards[3]}); s.flush();
    assert.strictEqual(open()[3], 1, 'A keyboard-focused link expands without changing link behavior.');

    s.motion.matches = true; s.motion.events.change();
    assert.strictEqual(deck.dataset.mobileCards, undefined, 'Reduced motion restores the static layout.');
    assert.ok(cards.every(card => card.style.getPropertyValue('--project-open') === ''));
    s.motion.matches = false; s.motion.events.change();
    assert.strictEqual(deck.dataset.mobileCards, 'true');
    s.small.matches = false; win.innerWidth = 1024; s.small.events.change();
    assert.strictEqual(deck.dataset.mobileCards, undefined, 'Desktop does not retain mobile heights.');
    assert.strictEqual(deck.style.getPropertyValue('--project-scroll-tail'), '');
    s.small.matches = true; win.innerWidth = 390; s.small.events.change();
    assert.strictEqual(deck.dataset.mobileCards, 'true', 'Returning to mobile restores the effect.');
    win.events.pagehide(); scroll(300); assert.strictEqual(s.frames.size, 0);
    win.events.pageshow(); assert.strictEqual(deck.dataset.mobileCards, 'true');
    init(deck, win); assert.strictEqual(deck.dataset.projectReady, 'true');
    assert.strictEqual(setup({mobile: false}).deck.dataset.mobileCards, undefined);
    assert.strictEqual(setup({reduced: true}).deck.dataset.mobileCards, undefined);
    assert.strictEqual(setup({count: 1}).deck.dataset.mobileCards, undefined);
    console.log('Passed: mobile hub handoffs in both directions, stable list height, last-card reachability, keyboard links, reduced motion and desktop restoration.');
}
module.exports = test;
if (require.main === module) test();
