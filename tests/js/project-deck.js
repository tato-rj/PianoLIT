const assert = require('assert');
const init = require('../../resources/js/components/project-deck');

function setup({reduced = false, count = 7, rounded = false, width = 600} = {}) {
    function element() {
        const classes = new Set();
        return {dataset: {}, events: {}, attrs: {},
            classList: {add: name => classes.add(name), remove: name => classes.delete(name)},
            addEventListener(name, fn) { this.events[name] = fn; },
            getAttribute(name) { return this.attrs[name]; },
            setAttribute(name, value) { this.attrs[name] = value; },
            matches() { return false; }};
    }
    const deck = element(), viewport = element(), doc = element();
    let cardWidth = 200, gap = 20, left = 0;
    function card(index) {
        const item = element();
        item.dataset.project = 'project-' + index;
        item.attrs = {href: '/hub-' + index, 'aria-labelledby': 'title-' + index, 'aria-describedby': 'description-' + index};
        const labels = [{id: 'title-' + index}, {id: 'description-' + index}];
        item.querySelectorAll = () => labels;
        item.closest = selector => selector === '[data-project]' ? item : null;
        item.tabIndex = 0;
        item.getBoundingClientRect = () => ({width: cardWidth, left: viewport.children.indexOf(item) * (cardWidth + gap) + 20 - left});
        item.remove = () => { viewport.children = viewport.children.filter(child => child !== item); if (doc.activeElement === item) doc.activeElement = null; };
        item.focus = options => {
            doc.activeElement = item;
            if (!options?.preventScroll) {
                const position = viewport.children.indexOf(item) * (cardWidth + gap);
                if (position < left || position + cardWidth > left + viewport.clientWidth) viewport.scrollLeft = position;
            }
        };
        return item;
    }
    const cards = Array.from({length: count}, (_, i) => card(i));
    Object.assign(viewport, {children: [...cards], clientWidth: width,
        querySelectorAll() { return this.children; }, querySelector() { return this.children[0]; },
        replaceChildren(...children) { this.children = children; },
        append(...children) { this.children.push(...children); }, prepend(...children) { this.children.unshift(...children); },
        setPointerCapture() { this.capture = true; }, hasPointerCapture() { return this.capture; }, releasePointerCapture() { this.capture = false; }});
    Object.defineProperty(viewport, 'scrollWidth', {get: () => viewport.children.length * (cardWidth + gap) + 20});
    Object.defineProperty(viewport, 'scrollLeft', {get: () => left, set: value => { left = Math.max(0, Math.min(viewport.scrollWidth - viewport.clientWidth, rounded ? Math.round(value) : value)); }});
    deck.querySelector = () => viewport;
    deck.contains = target => viewport.children.includes(target);
    const media = Object.assign(element(), {matches: reduced});
    let now = 0, id = 0, observer;
    const frames = new Map(), timers = new Map();
    const win = Object.assign(element(), {
        document: doc, getComputedStyle: () => ({columnGap: String(gap)}),
        matchMedia: query => query.includes('reduced-motion') ? media : {matches: true},
        requestAnimationFrame(fn) { frames.set(++id, fn); return id; },
        cancelAnimationFrame(key) { frames.delete(key); },
        setTimeout(fn, delay) { timers.set(++id, {fn, at: now + delay}); return id; },
        clearTimeout(key) { timers.delete(key); },
        IntersectionObserver: class { constructor(fn) { observer = fn; } observe() {} }
    });
    function advance(ms) {
        const until = now + ms;
        while (now < until) {
            now = Math.min(until, now + 16);
            for (const [key, timer] of [...timers]) if (timer.at <= now) { timers.delete(key); timer.fn(); }
            for (const [key, fn] of [...frames]) { frames.delete(key); fn(now); }
        }
    }
    const event = props => Object.assign({detail: 1, target: cards[0], prevented: false,
        preventDefault() { this.prevented = true; }, stopPropagation() {}}, props);
    const progress = () => viewport.scrollLeft;
    const resize = (card, space, viewportWidth) => { cardWidth = card; gap = space; viewport.clientWidth = viewportWidth; win.events.resize(); };
    init(deck, win);
    return {deck, viewport, cards, media, doc, win, frames, timers, advance, event, progress, resize, visibility: value => observer([{isIntersecting: value}])};
}

function test() {
    {
        const {deck, viewport, cards, doc, win, event, progress} = setup();
        assert.deepStrictEqual(viewport.children, cards, 'Each project appears exactly once.');
        const ordinary = event(); deck.events.click(ordinary);
        assert.strictEqual(ordinary.prevented, false);
        const modified = event({target: cards[6], metaKey: true}); deck.events.click(modified);
        assert.strictEqual(modified.prevented, false, 'Modified clicks retain native destinations.');
        const wheel = event({deltaX: 0, deltaY: 5000, deltaMode: 0}); viewport.events.wheel(wheel);
        const end = viewport.scrollWidth - viewport.clientWidth;
        assert.strictEqual(progress(), end, 'A large wheel movement stops at the last card.');
        assert.strictEqual(wheel.prevented, true);
        const endWheel = event({deltaX: 0, deltaY: 100, deltaMode: 0}); viewport.events.wheel(endWheel);
        assert.strictEqual(endWheel.prevented, false, 'Scrolling past the end releases to the page.');
        viewport.events.wheel(event({deltaX: -15000, deltaY: 0, deltaMode: 0}));
        assert.strictEqual(progress(), 0, 'Backward scrolling stops at the first card.');
        const startWheel = event({deltaX: 0, deltaY: -100, deltaMode: 0}); viewport.events.wheel(startWheel);
        assert.strictEqual(startWheel.prevented, false);
        const zoom = event({deltaY: 100, deltaX: 0, deltaMode: 0, ctrlKey: true}); viewport.events.wheel(zoom);
        assert.strictEqual(zoom.prevented, false);
        assert.strictEqual(progress(), 0);
        viewport.events.pointerdown(event({pointerType: 'mouse', button: 0, pointerId: 1, clientX: 500}));
        viewport.events.pointermove(event({pointerId: 1, clientX: 497}));
        win.events.pointerup(event({pointerId: 1}));
        const tinyMove = event(); deck.events.click(tinyMove);
        assert.strictEqual(tinyMove.prevented, false);
        viewport.events.pointerdown(event({pointerType: 'mouse', button: 0, pointerId: 2, clientX: 500}));
        viewport.events.pointermove(event({pointerId: 2, clientX: -1000}));
        assert.strictEqual(progress(), end, 'Mouse dragging also has a real last card.');
        viewport.events.pointermove(event({pointerId: 2, clientX: 600}));
        assert.strictEqual(progress(), 0);
        win.events.pointerup(event({pointerId: 2}));
        const dragged = event(); deck.events.click(dragged);
        assert.strictEqual(dragged.prevented, true);
        const keyboardLink = event({detail: 0}); deck.events.click(keyboardLink);
        assert.strictEqual(keyboardLink.prevented, false);
        deck.events.keydown(event({target: cards[6], key: 'ArrowRight'}));
        assert.strictEqual(doc.activeElement, cards[6], 'Right arrow never wraps to the first project.');
        deck.events.keydown(event({target: cards[6], key: 'Home'}));
        assert.strictEqual(doc.activeElement, cards[0]); assert.strictEqual(progress(), 0);
        deck.events.keydown(event({target: cards[0], key: 'ArrowLeft'}));
        assert.strictEqual(doc.activeElement, cards[0]);
        deck.events.keydown(event({target: cards[0], key: 'End'}));
        assert.strictEqual(doc.activeElement, cards[6]); assert.strictEqual(progress(), end);
        const enter = event({key: 'Enter'}); deck.events.keydown(enter);
        assert.strictEqual(enter.prevented, false);
        assert.strictEqual(viewport.children.filter(card => card.tabIndex === 0).length, 7);
    }
    {
        const {viewport, advance, event, progress} = setup({rounded: true});
        advance(1000); assert.strictEqual(progress(), 0);
        advance(2200); assert.ok(progress() >= 40 && progress() <= 45, 'Slow drift accumulates subpixels.');
        viewport.events.pointerenter(event({pointerType: 'mouse'}));
        const left = progress();
        advance(5000); assert.strictEqual(progress(), left, 'Hover immediately freezes the row.');
        viewport.events.pointerleave(event({pointerType: 'mouse'}));
        advance(2000); assert.strictEqual(progress(), left);
        advance(1500); assert.ok(progress() > left);
    }
    {
        const {viewport, win, advance, event, progress} = setup();
        advance(2400);
        viewport.events.touchstart();
        viewport.events.pointerdown(event({pointerType: 'touch', pointerId: 3}));
        win.events.pointercancel(event({pointerId: 3}));
        const left = progress();
        advance(6000); assert.strictEqual(progress(), left, 'Native pointercancel does not release a held touch.');
        viewport.scrollLeft = 400; viewport.events.scroll();
        assert.strictEqual(progress(), 400, 'Native swipe position is preserved.');
        win.events.touchend(event({touches: []}));
        advance(1800);
        viewport.scrollLeft = 500; viewport.events.scroll();
        advance(2000); assert.strictEqual(progress(), 500, 'Native inertia postpones automatic scrolling.');
        advance(1500); assert.ok(progress() > 500);
    }
    {
        const {viewport, win, advance, event, progress} = setup();
        advance(2400);
        viewport.events.pointerdown(event({pointerType: 'mouse', button: 0, pointerId: 4, clientX: 500}));
        const left = progress();
        advance(6000); assert.strictEqual(progress(), left);
        viewport.events.pointermove(event({pointerId: 4, clientX: 400}));
        assert.strictEqual(progress(), left + 100);
        win.events.pointerup(event({pointerId: 4}));
        advance(3500); assert.ok(progress() > left + 100);
    }
    {
        const {deck, advance, event, progress} = setup();
        advance(2400);
        deck.events.focusin(event({target: {matches: () => true}}));
        const left = progress();
        advance(6000); assert.strictEqual(progress(), left);
        deck.events.focusout(event({relatedTarget: null}));
        advance(3500); assert.ok(progress() > left);
    }
    for (const rounded of [false, true]) {
        const {viewport, cards, frames, timers, advance, progress, resize} = setup({rounded});
        advance(120000);
        const end = viewport.scrollWidth - viewport.clientWidth;
        assert.strictEqual(progress(), end, 'Automatic scrolling reaches the final card.');
        advance(1800000);
        assert.strictEqual(progress(), end, 'It stays at the end without looping or reversing.');
        assert.deepStrictEqual(viewport.children, cards, 'No duplicated cards after thirty idle minutes.');
        assert.strictEqual(frames.size, 0, 'The finished animation stops scheduling frames.');
        assert.strictEqual(timers.size, 0);
        resize(264, 28, 5000);
        assert.strictEqual(progress(), 0, 'Resizing to fit all projects removes overflow.');
        advance(6000); assert.strictEqual(progress(), 0);
    }
    {
        const {media, doc, win, advance, visibility, progress} = setup({reduced: true});
        advance(5000); assert.strictEqual(progress(), 0);
        media.matches = false; media.events.change(); advance(3500);
        assert.ok(progress() > 0);
        for (const [pause, resume] of [
            [() => { media.matches = true; media.events.change(); }, () => { media.matches = false; media.events.change(); }],
            [() => { doc.hidden = true; doc.events.visibilitychange(); }, () => { doc.hidden = false; doc.events.visibilitychange(); }],
            [() => visibility(false), () => visibility(true)],
            [() => win.events.pagehide(), () => win.events.pageshow()]
        ]) {
            pause(); const left = progress(); advance(6000); assert.strictEqual(progress(), left);
            resume(); advance(3500); assert.ok(progress() > left);
        }
    }
    for (const options of [{count: 0}, {count: 1}, {width: 2000}]) {
        const page = setup(options);
        page.advance(6000); assert.strictEqual(page.progress(), 0, 'A non-overflowing row stays stationary.');
        assert.strictEqual(page.viewport.children.length, page.cards.length);
    }
    console.log('Passed: finite seven-card row, both endpoints, no clones/wrapping, idle stop, native links, drag/touch/keyboard controls, hover/hold pauses, resize and reduced motion.');
}
module.exports = test;
if (require.main === module) test();
