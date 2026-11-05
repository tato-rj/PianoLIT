const assert = require('node:assert/strict');
const initialize = require('../../resources/js/components/product-reveal');

module.exports = function () {
    function setup({reduced = false, observer = true, fail = false} = {}) {
        function element() {
            const classes = new Set();
            return {dataset: {}, events: {}, classList: {
                add: value => classes.add(value), remove: value => classes.delete(value), contains: value => classes.has(value)
            }, addEventListener(name, callback) { this.events[name] = callback; }};
        }
        const grid = element(), cards = [element(), element(), element()];
        grid.querySelectorAll = () => cards;
        grid.contains = card => cards.includes(card);
        cards.forEach(card => { card.closest = () => card; });
        const motion = Object.assign(element(), {matches: reduced});
        const win = Object.assign(element(), {matchMedia: () => motion});
        let callback, disconnected = false, constructors = 0;
        const observed = new Set();
        if (observer) win.IntersectionObserver = class {
            constructor(fn) { callback = fn; constructors++; }
            observe(card) { if (fail) throw new Error('Observer unavailable'); observed.add(card); }
            unobserve(card) { observed.delete(card); }
            disconnect() { observed.clear(); disconnected = true; }
        };
        initialize(grid, win);
        return {grid, cards, win, motion, observed, intersect: (card, isIntersecting = true) => callback([{target: card, isIntersecting}]),
            disconnected: () => disconnected, constructors: () => constructors};
    }
    function visible(state) {
        state.cards.forEach(card => {
            assert.equal(card.classList.contains('shop-product-pending'), false);
            assert.equal(card.classList.contains('shop-product-revealing'), false);
        });
    }
    const state = setup();
    assert.equal(state.observed.size, 3);
    state.intersect(state.cards[0], false);
    assert.equal(state.cards[0].classList.contains('shop-product-pending'), true);
    state.intersect(state.cards[0]);
    assert.equal(state.cards[0].classList.contains('shop-product-pending'), false);
    assert.equal(state.cards[0].classList.contains('shop-product-revealing'), true);
    assert.equal(state.observed.has(state.cards[0]), false, 'Visible cards stop being observed');
    state.grid.events.animationend({target: state.cards[0], animationName: 'shop-product-enter'});
    state.intersect(state.cards[0]);
    assert.equal(state.cards[0].classList.contains('shop-product-revealing'), false, 'Scrolling back does not replay the reveal');
    state.grid.events.focusin({target: state.cards[1]});
    assert.equal(state.cards[1].classList.contains('shop-product-pending'), false, 'Keyboard focus reveals an offscreen card immediately');
    assert.equal(state.cards[1].classList.contains('shop-product-revealing'), false);
    state.intersect(state.cards[2]);
    state.grid.events.focusin({target: state.cards[2]});
    visible(state);
    initialize(state.grid, state.win);
    assert.equal(state.constructors(), 1, 'Repeated initialization cannot hide cards again');

    visible(setup({reduced: true}));
    visible(setup({observer: false}));
    const failed = setup({fail: true});
    visible(failed);
    assert.equal(failed.disconnected(), true, 'Partial initialization fails open');
    const changed = setup();
    changed.intersect(changed.cards[0]);
    changed.motion.matches = true;
    changed.motion.events.change();
    visible(changed);
    assert.equal(changed.disconnected(), true);
    changed.intersect(changed.cards[1]);
    visible(changed);
    const restored = setup();
    restored.win.events.pageshow({persisted: false});
    assert.equal(restored.observed.size, 3);
    restored.win.events.pageshow({persisted: true});
    visible(restored);
    const printed = setup();
    printed.win.events.beforeprint();
    visible(printed);
    console.log('Passed: once-per-product viewport reveals, keyboard visibility, reduced motion, missing/failed observers, page restoration and printing.');
};
