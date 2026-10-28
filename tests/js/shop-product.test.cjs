const assert = require('node:assert/strict');
const init = require('../../resources/js/views/shop-product');

function element(dataset = {}) {
    const events = {};
    const classes = new Set();
    return {
        dataset, hidden: false, disabled: false, children: [], style: {}, attrs: {}, textContent: '',
        classList: {add: c => classes.add(c), toggle: (c, on) => on ? classes.add(c) : classes.delete(c)},
        addEventListener(name, callback) { (events[name] ||= []).push(callback); },
        removeEventListener(name, callback) { events[name] = (events[name] || []).filter(fn => fn !== callback); },
        fire(name, event = {}) { return Promise.all((events[name] || []).map(fn => fn(event))); },
        setAttribute(name, value) { this.attrs[name] = value; },
        removeAttribute(name) { delete this.attrs[name]; },
        getAttribute(name) { return this[name] || this.attrs[name] || null; },
        querySelector() { return null; },
    };
}

function environment() {
    const root = element();
    const timers = new Map();
    let id = 0;
    root.setTimeout = callback => { timers.set(++id, callback); return id; };
    root.clearTimeout = id => timers.delete(id);
    root.flushTimers = () => { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(fn => fn()); };
    root.matchMedia = () => ({matches: false});
    root.document = element();
    root.document.getElementById = () => null;
    return root;
}

async function preview({pending = false, fallback = false} = {}) {
    const root = environment();
    const nodes = Object.fromEntries(['stage', 'host', 'loading', 'error', 'previous', 'next', 'counter', 'magnify', 'zoom', 'zoom-image', 'zoom-label', 'hint', 'retry'].map(name => [name, element()]));
    nodes.stage.clientWidth = 1000;
    nodes.stage.clientHeight = 500;
    const modal = element();
    const book = element();
    const images = [1, 2, 3, 4, 5].map(n => Object.assign(element({previewSrc: '/page-' + n}), {naturalWidth: 650, naturalHeight: 900, complete: !pending, alt: 'Page ' + n, nextElementSibling: element()}));
    book.children = images.map(() => element());
    book.querySelectorAll = () => images;
    const thumbs = images.map((img, i) => element({bookPage: String(i + 1)}));
    const preview = element();
    preview.closest = () => modal;
    preview.querySelector = selector => selector === '[data-book]' ? book : nodes[selector.slice(11, -1)];
    preview.querySelectorAll = () => thumbs;
    root.document.querySelector = selector => selector === '[data-book-preview]' ? preview : null;
    let options, current = 1, display = 'double';
    const calls = [];
    const api = {turn(action, ...args) {
        calls.push([action, ...args]);
        if (typeof action === 'object') { options = action; display = options.display; current = options.page; return; }
        if (action === 'view') return display === 'single' ? [current] : current === 1 ? [0, 1] : [current - current % 2, current - current % 2 + 1];
        if (action === 'display') { display = args[0]; return; }
        if (action === 'next') current = current === 1 ? 2 : Math.min(5, current + (display === 'double' ? 2 : 1));
        if (action === 'previous') current = Math.max(1, current - (display === 'double' ? 2 : 1));
        if (action === 'page') current = args[0];
        if (['page', 'next', 'previous'].includes(action)) options.when.turned({}, current);
    }};
    if (!fallback) { root.jQuery = () => api; root.jQuery.fn = {turn() {}}; }
    init(root);
    await modal.fire('shown.bs.modal');
    await Promise.resolve();
    return {root, nodes, modal, book, images, thumbs, calls, options: () => options};
}

async function run() {
    let p = await preview();
    assert(p.options().height > 0);
    assert.equal(p.nodes.host.style.width, p.options().width + 'px', 'cover centering must not shrink the measured book host');
    assert.equal(p.nodes.previous.disabled, true);
    assert.equal(p.book.children[0].attrs['aria-hidden'], 'false');
    assert.equal(p.book.children[2].attrs['aria-hidden'], 'true');
    await p.nodes.next.fire('click');
    assert.equal(p.nodes.counter.textContent, 'Pages 2–3 of 5');
    await p.thumbs[4].fire('click');
    assert.equal(p.nodes.next.disabled, true);
    await p.nodes.magnify.fire('click');
    assert.equal(p.nodes.zoom.hidden, false);
    assert.equal(p.nodes['zoom-image'].src, '/page-5');
    await p.nodes.previous.fire('click');
    assert.equal(p.nodes['zoom-image'].src, '/page-4');
    await p.nodes.magnify.fire('click');
    assert.equal(p.nodes.zoom.hidden, true);
    p.nodes.stage.clientWidth = 360;
    await p.root.fire('resize');
    p.root.flushTimers();
    assert(p.calls.some(call => call[0] === 'display' && call[1] === 'single'));
    assert.match(p.nodes.counter.textContent, /^Page \d of 5$/);

    p = await preview({pending: true});
    await p.modal.fire('hide.bs.modal');
    await p.modal.fire('shown.bs.modal');
    await p.images[0].fire('load');
    await Promise.resolve();
    assert.equal(p.calls.filter(call => typeof call[0] === 'object').length, 1, 'closing and reopening during image loading must initialize once');
    assert.equal(p.nodes.loading.hidden, true);

    p = await preview({fallback: true});
    await p.nodes.next.fire('click');
    assert.equal(p.nodes.counter.textContent, 'Page 2 of 5');
    assert.equal(p.book.children[0].hidden, true);
    assert.equal(p.book.children[1].hidden, false);
    await p.images[1].fire('error');
    assert.equal(p.nodes.error.hidden, false);
    await p.images[1].fire('load');
    assert.equal(p.nodes.error.hidden, true);

    const root = environment();
    const button = element({src: '/sample.mp3'});
    const error = element();
    const labels = Object.fromEntries(['play', 'stop', 'loading'].map(key => [key, element()]));
    button.querySelector = selector => labels[selector.slice(12, -1)];
    root.document.querySelector = selector => selector === '[data-product-audio]' ? button : selector === '[data-audio-error]' ? error : null;
    let audio, resolvePlay, rejectPlay;
    root.Audio = function () {
        audio = element();
        audio.pause = () => { audio.paused = true; };
        audio.play = () => new Promise((resolve, reject) => { resolvePlay = resolve; rejectPlay = reject; });
        return audio;
    };
    init(root);
    let pending = button.fire('click');
    assert.equal(button.disabled, true);
    rejectPlay(new Error('Playback denied'));
    await pending;
    assert.equal(button.disabled, false);
    assert.equal(error.hidden, false);
    pending = button.fire('click');
    resolvePlay();
    await pending;
    assert.equal(button.attrs['aria-pressed'], 'true');
    await audio.fire('ended');
    assert.equal(button.attrs['aria-pressed'], 'false');
    pending = button.fire('click');
    root.document.hidden = true;
    await root.document.fire('visibilitychange');
    resolvePlay();
    await pending;
    assert.equal(button.attrs['aria-pressed'], 'false', 'late playback completion must not restore playing state after leaving');
    assert.equal(button.disabled, false);
    console.log('Passed: shop preview sizing, page controls, zoom, mobile resize, reopen race, readable fallback, image errors and cancellable audio.');
}

if (require.main === module) run().catch(error => { console.error(error); process.exitCode = 1; });
module.exports = run;
