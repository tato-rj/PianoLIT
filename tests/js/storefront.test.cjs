const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const path = require('path');

module.exports = function () {
    const frames = [];
    const observers = [];
    let reducedMotion = false;
    function element(extra = {}) {
        return Object.assign({events: {}, addEventListener(name, fn) { this.events[name] = fn; }}, extra);
    }
    function row(width, total) {
        const previous = element({disabled: true});
        const next = element({disabled: true});
        const controls = {hidden: true};
        const track = element({clientWidth: width, scrollWidth: total, scrollLeft: 0,
            scrollTo(options) { this.lastScroll = options; this.scrollLeft = options.left; this.events.scroll(); }
        });
        const nodes = {'[data-category-track]': track, '[data-category-controls]': controls,
            '[data-category-step="-1"]': previous, '[data-category-step="1"]': next};
        return {track, previous, next, controls, querySelector(selector) { return nodes[selector]; }};
    }
    const shirts = row(600, 1224);
    const stickers = row(600, 600);
    const scores = row(1200, 3036);
    const root = {querySelectorAll(selector) { return selector === '[data-shop-category]' ? [shirts, stickers, scores] : []; }};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/storefront.js'), 'utf8'), {
        document: {querySelector: () => root},
        window: {matchMedia: () => ({matches: reducedMotion}), ResizeObserver: true},
        ResizeObserver: class { constructor(fn) { this.fn = fn; } observe(target) { observers.push({target, fn: this.fn}); } },
        getComputedStyle: () => ({columnGap: '24px'}),
        requestAnimationFrame: fn => { frames.push(fn); return frames.length; }
    });
    function flush() { while (frames.length) frames.shift()(); }
    function key(track, value, target = track) {
        let prevented = false;
        track.events.keydown({key: value, target, preventDefault() { prevented = true; }});
        flush();
        return prevented;
    }
    assert.strictEqual(shirts.controls.hidden, false);
    assert.strictEqual(shirts.previous.disabled, true);
    assert.strictEqual(shirts.next.disabled, false);
    assert.strictEqual(stickers.previous.disabled, true);
    assert.strictEqual(stickers.next.disabled, true, 'Both arrows are disabled when all products fit');
    shirts.next.events.click(); flush();
    assert.strictEqual(shirts.track.scrollLeft, 624, 'One arrow advances the visible row');
    assert.strictEqual(shirts.track.lastScroll.behavior, 'smooth');
    assert.strictEqual(shirts.next.disabled, true);
    assert.strictEqual(shirts.previous.disabled, false);
    assert.strictEqual(scores.track.scrollLeft, 0, 'Other categories stay in place');
    shirts.previous.events.click(); flush();
    assert.strictEqual(shirts.track.scrollLeft, 0);
    reducedMotion = true;
    key(scores.track, 'ArrowRight');
    assert.strictEqual(scores.track.scrollLeft, 1224);
    assert.strictEqual(scores.track.lastScroll.behavior, 'auto');
    key(scores.track, 'End');
    assert.strictEqual(scores.track.scrollLeft, 1836);
    assert.strictEqual(scores.next.disabled, true);
    key(scores.track, 'Home');
    assert.strictEqual(scores.track.scrollLeft, 0);
    assert.strictEqual(key(scores.track, 'ArrowRight', {}), false, 'Keys inside product controls are not hijacked');
    shirts.track.clientWidth = 1224;
    observers.find(observer => observer.target === shirts.track).fn(); flush();
    assert.strictEqual(shirts.next.disabled, true, 'Resize recalculates overflow');
    shirts.track.clientWidth = 320;
    observers.find(observer => observer.target === shirts.track).fn(); flush();
    assert.strictEqual(shirts.next.disabled, false);
    shirts.track.scrollLeft = 904;
    shirts.track.events.scroll(); flush();
    assert.strictEqual(shirts.next.disabled, true, 'Native touch/keyboard scrolling updates the arrows');
    console.log('Passed: independent Shop rows, boundaries, resizing, keyboard controls and reduced motion.');
};
