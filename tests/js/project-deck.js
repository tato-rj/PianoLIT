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
    let id = 0, deckTop = 360, time = 0, reads = 0, writes = 0;
    const small = Object.assign(element(), {matches: mobile});
    const motion = Object.assign(element(), {matches: reduced});
    const win = Object.assign(element(), {
        innerWidth: width, innerHeight: height, scrollY: 0, document: doc,
        matchMedia: query => query.includes('reduced') ? motion : small,
        getComputedStyle: () => ({rowGap: '8px', marginBottom: '8px'}),
        requestAnimationFrame(fn) { frames.set(++id, fn); return id; },
        cancelAnimationFrame(key) { frames.delete(key); },
        scrollTo({top}) { this.scrollY = top; this.scrollCalls++; }, scrollCalls: 0
    });
    const cards = Array.from({length: count}, (_, index) => {
        const card = element();
        card.parentElement = element();
        card.dataset.project = 'hub-' + index;
        card.href = '/hub-' + index;
        card.getBoundingClientRect = () => { reads++; return {width: win.innerWidth - 30}; };
        card.querySelector = selector => ({getBoundingClientRect: () => {
            reads++;
            return {height: selector === '.project-card__tag' ? 26 : selector === 'h2' ? 40 : 144};
        }});
        [card, card.parentElement].forEach(el => {
            const set = el.style.setProperty;
            el.style.setProperty = (name, value) => { writes++; set(name, value); };
        });
        card.closest = () => card;
        card.matches = () => card.keyboardFocus || false;
        return card;
    });
    const deckHeight = () => cards.length * Number.parseFloat(deck.style.getPropertyValue('--project-compact-height') || 144) +
        Number.parseFloat(deck.style.getPropertyValue('--project-expansion') || 360) +
        (cards.length - 1) * 8 + Number.parseFloat(deck.style.getPropertyValue('--project-scroll-tail') || 0);
    deck.querySelectorAll = () => cards;
    deck.querySelector = () => ({});
    deck.getBoundingClientRect = () => ({top: deckTop - win.scrollY, bottom: deckTop + deckHeight() - win.scrollY});
    doc.documentElement = {};
    Object.defineProperty(doc.documentElement, 'scrollHeight', {get: () => deckTop + deckHeight() + 80});
    const flush = (elapsed = 1000 / 60) => { time += elapsed; for (const [key, fn] of [...frames]) { frames.delete(key); fn(time); } };
    const settle = () => { let ticks = 0; while (frames.size && ticks++ < 100) flush(); assert.ok(ticks < 100, 'Animation settles and stops.'); };
    const scroll = top => { win.scrollY = top; win.events.scroll(); settle(); };
    const open = () => cards.map(card => Number(card.style.getPropertyValue('--project-open')));
    init(deck, win);
    return {deck, cards, doc, win, small, motion, frames, scroll, flush, settle, open, deckHeight, reads: () => reads, writes: () => writes};
}

function test() {
    const s = setup();
    const {deck, cards, win, scroll, open, deckHeight} = s;
    assert.deepStrictEqual(open(), [1, 0, 0, 0, 0, 0, 0]);
    assert.strictEqual(win.options.scroll.passive, true, 'Native scrolling remains passive.');
    assert.strictEqual(win.events.wheel, undefined);
    assert.strictEqual(deck.events.click, undefined, 'Cards remain ordinary links without an extra activation step.');
    const total = deckHeight();
    const measuredReads = s.reads();
    const initialWrites = s.writes();
    win.scrollY = 266; win.events.scroll(); s.flush();
    assert.ok(open()[0] < 1 && open()[0] > .5, 'A sparse scroll event eases instead of jumping to its target.');
    assert.strictEqual(s.writes() - initialWrites, 3, 'Only two card states and the intervening slot change.');
    assert.strictEqual(s.reads(), measuredReads, 'Scroll frames do not synchronously measure layout.');
    s.settle();
    // 504px full card centered in 844px: first handoff starts at 360 - 170.
    scroll(190 + 76);
    assert.deepStrictEqual(open(), [.5, .5, 0, 0, 0, 0, 0]);
    assert.strictEqual(deckHeight(), total, 'Adjacent cards share expansion without changing the list height.');
    assert.strictEqual(cards[1].parentElement.style.getPropertyValue('--project-shift'), '180.000px');
    assert.strictEqual(cards[2].parentElement.style.getPropertyValue('--project-shift'), '360.000px');
    assert.strictEqual(open().reduce((sum, value) => sum + value, 0), 1);
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
    assert.ok(cards.every(card => card.parentElement.style.getPropertyValue('--project-shift') === ''));
    s.motion.matches = false; s.motion.events.change();
    assert.strictEqual(deck.dataset.mobileCards, 'true');
    s.small.matches = false; win.innerWidth = 1024; s.small.events.change();
    assert.strictEqual(deck.dataset.mobileCards, undefined, 'Desktop does not retain mobile heights.');
    assert.strictEqual(deck.style.getPropertyValue('--project-scroll-tail'), '');
    assert.strictEqual(deck.style.getPropertyValue('--project-action-travel'), '');
    s.small.matches = true; win.innerWidth = 390; s.small.events.change();
    assert.strictEqual(deck.dataset.mobileCards, 'true', 'Returning to mobile restores the effect.');
    win.events.pagehide(); scroll(300); assert.strictEqual(s.frames.size, 0);
    win.events.pageshow(); assert.strictEqual(deck.dataset.mobileCards, 'true');
    init(deck, win); assert.strictEqual(deck.dataset.projectReady, 'true');
    assert.strictEqual(setup({mobile: false}).deck.dataset.mobileCards, undefined);
    assert.strictEqual(setup({reduced: true}).deck.dataset.mobileCards, undefined);
    assert.strictEqual(setup({count: 1}).deck.dataset.mobileCards, undefined);

    const sixty = setup(), oneTwenty = setup();
    [sixty, oneTwenty].forEach(state => { state.win.scrollY = 342; state.win.events.scroll(); state.flush(); });
    for (let frame = 0; frame < 6; frame++) sixty.flush(1000 / 60);
    for (let frame = 0; frame < 12; frame++) oneTwenty.flush(1000 / 120);
    assert.deepStrictEqual(sixty.open(), oneTwenty.open(), 'Easing follows elapsed time on 60Hz and 120Hz screens.');
    sixty.win.scrollY = 190; sixty.win.events.scroll(); sixty.settle();
    assert.strictEqual(sixty.open()[0], 1, 'Reversing direction during easing returns cleanly.');
    oneTwenty.doc.hidden = true; oneTwenty.doc.events.visibilitychange();
    assert.strictEqual(oneTwenty.frames.size, 0, 'Hidden pages cancel in-flight easing.');
    console.log('Passed: smooth frame-independent handoffs, bounded DOM writes, no scroll-time measurements, stable slots, last-card reachability, keyboard links, reduced motion and desktop restoration.');
}
module.exports = test;
if (require.main === module) test();
