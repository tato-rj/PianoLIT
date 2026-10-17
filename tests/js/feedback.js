const assert = require('assert');
const {Controller, Floating, expression} = require('../../resources/js/views/feedback');

class Element {
    constructor() {
        this.events = {}; this.attributes = {}; this.hidden = false; this.disabled = false;
        this.value = ''; this.textContent = ''; this.style = {setProperty(name, value) { this[name] = value; }, removeProperty(name) { delete this[name]; }};
        const classes = new Set();
        this.classList = {contains: name => classes.has(name), add: name => classes.add(name), remove: name => classes.delete(name),
            toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); }};
    }
    setAttribute(name, value) { this.attributes[name] = String(value); }
    matches() { return false; }
    addEventListener(name, handler) { this.events[name] = handler; }
    focus() { this.focused = true; }
    blur() { this.focused = false; }
    getBoundingClientRect() { return {width: 320}; }
    setPointerCapture(id) { this.captured = id; }
    fire(name, event = {}) { return this.events[name](Object.assign({preventDefault() {}}, event)); }
}

function fixture() {
    const map = {}, frames = new Map(), pending = [], notifications = [], hidden = [];
    let nextFrame = 0;
    const selectors = ['[data-feedback-form]', '[data-feedback-range]', '[name="message"]', '[name="rating"]', '[name="_token"]', '[name="page_context"]',
        '[data-feedback-labels]', '[data-feedback-thumb]', '[data-feedback-handle-mouth]', '[data-feedback-note]', '[data-feedback-error]',
        '.feedback-submit', '[data-feedback-submit-label]', '[data-feedback-add-note]', '[data-feedback-note-back]', '.modal-content', '[data-feedback-swipe]'];
    selectors.forEach(selector => { map[selector] = new Element(); });
    const buttons = [0, 1, 2].map(value => Object.assign(new Element(), {dataset: {feedbackRating: String(value)}}));
    const faces = [0].map(() => {
        const elements = {'[data-face-eye="left"]': new Element(), '[data-face-eye="right"]': new Element(), '[data-face-mouth]': new Element()};
        return {querySelector: selector => elements[selector]};
    });
    const modal = new Element();
    modal.querySelector = selector => map[selector];
    modal.querySelectorAll = selector => selector === '.feedback-face' ? faces : buttons;
    modal.dispatchEvent = event => notifications.push(event.type);
    const form = map['[data-feedback-form]'];
    form.querySelector = modal.querySelector;
    form.querySelectorAll = () => [...buttons, map['[data-feedback-range]'], map['[name="message"]'], map['.feedback-submit'], map['[data-feedback-add-note]'], map['[data-feedback-note-back]']];
    form.reset = () => { map['[name="message"]'].value = ''; };
    form.action = '/feedback'; map['[name="_token"]'].value = 'session-csrf';
    map['[name="page_context"]'].value = 'page-token';
    const win = {innerWidth: 390, matchMedia: () => ({matches: false}), visualViewport: Object.assign(new Element(), {height: 500, offsetTop: 12}),
        CustomEvent: class { constructor(type) { this.type = type; } },
        bootstrap: {Modal: {getOrCreateInstance: () => ({hide() { hidden.push(true); }})}},
        requestAnimationFrame(callback) { frames.set(++nextFrame, callback); return nextFrame; }, cancelAnimationFrame(id) { frames.delete(id); }};
    const http = {post(url, payload, options) { return new Promise((resolve, reject) => pending.push({url, payload, options, resolve, reject})); }};
    const controller = new Controller(modal, http, win);
    function animate() { for (const time of [0, 440]) { const callbacks = [...frames.values()]; frames.clear(); callbacks.forEach(callback => callback(time)); } }
    return {controller, modal, map, pending, buttons, win, animate, notifications, hidden};
}

module.exports = async function () {
    const end = expression(2), start = expression(0), middle = expression(1);
    assert.deepStrictEqual(expression(-4), start); assert.deepStrictEqual(expression(20), end);
    assert(end.eye[3] > start.eye[3], 'Good has much bigger eyes than Bad');
    assert(middle.eye[3] > middle.eye[4], 'Not bad has pill-shaped eyes');
    assert(expression(.5).eye[3] > start.eye[3], 'Dragging continuously morphs the face');
    const f = fixture(), c = f.controller, map = f.map;
    f.buttons[0].fire('click');
    assert.strictEqual(c.rating.value, 'bad');
    assert.strictEqual(map['[data-feedback-range]'].attributes['aria-valuetext'], 'Bad');
    // Submission during the animation must use the chosen rating, not its old visual position.
    const first = c.submit({preventDefault() {}});
    c.submit({preventDefault() {}});
    assert.strictEqual(f.pending.length, 1, 'Repeated submit while busy sends exactly one request');
    assert.deepStrictEqual(f.pending[0].payload, {rating: 'bad', message: null, _token: 'session-csrf', page_context: 'page-token', device_type: 'mobile'});
    assert.strictEqual(f.pending[0].options.timeout, 15000, 'An unreachable server cannot leave controls locked forever');
    assert(c.busy); assert(map['[name="message"]'].disabled);
    f.pending[0].reject({response: {status: 500}}); await first;
    assert(!c.busy); assert(!c.error.hidden); assert(!map['[name="message"]'].disabled);

    map['[data-feedback-range]'].fire('keydown', {key: 'ArrowRight'});
    map['[data-feedback-range]'].fire('keydown', {key: 'ArrowRight'});
    assert.strictEqual(c.rating.value, 'good', 'Rapid arrow keys move between all three choices');
    f.animate(); assert.strictEqual(c.value, 2);
    map['[data-feedback-range]'].value = '.7'; map['[data-feedback-range]'].fire('input');
    assert.strictEqual(c.value, .7); assert.strictEqual(c.rating.value, 'not_bad');
    map['[data-feedback-range]'].fire('change'); f.animate(); assert.strictEqual(c.value, 1);
    const swipe = map['[data-feedback-swipe]'];
    swipe.fire('pointerdown', {pointerId: 1, clientX: 160, clientY: 200, button: 0});
    swipe.fire('pointermove', {pointerId: 1, clientX: 10, clientY: 204});
    swipe.fire('pointerup', {pointerId: 1}); f.animate();
    assert.strictEqual(c.rating.value, 'bad', 'Swiping the face snaps to the closest rating');
    swipe.fire('pointerdown', {pointerId: 2, clientX: 160, clientY: 200, button: 0});
    swipe.fire('pointermove', {pointerId: 2, clientX: 170, clientY: 260});
    assert.strictEqual(c.drag, null, 'Vertical gestures do not change the rating');
    c.reducedMotion = true; c.select(2); assert.strictEqual(c.value, 2);

    c.modal.classList.add('show'); c.setNote(true);
    assert(!c.note.hidden); assert(c.message.focused); assert(c.modal.classList.contains('is-noting'));
    assert.strictEqual(c.modal.style['--feedback-viewport-height'], '500px');
    f.win.visualViewport.height = 320; c.fitViewport();
    assert(c.modal.classList.contains('is-compact'), 'A native keyboard can compact the panel independently of media queries');
    c.message.value = '  Please add more music  '; c.setNote(false);
    assert.strictEqual(map['[data-feedback-add-note]'].textContent, 'Edit note');
    c.setNote(true);
    const noteRequest = c.submit({preventDefault() {}});
    assert.strictEqual(f.pending[1].payload.message, 'Please add more music');
    f.pending[1].reject({response: {status: 422, data: {errors: {message: ['Your message is too long.']}}}}); await noteRequest;
    assert.strictEqual(c.error.textContent, 'Your message is too long.');
    assert.strictEqual(c.message.value, '  Please add more music  ', 'Failures preserve the optional note and choice');
    const success = c.submit({preventDefault() {}});
    f.pending[2].resolve({data: {saved: true}}); await success;
    assert(c.sent); assert.strictEqual(f.hidden.length, 1, 'Success closes the existing Bootstrap modal');
    assert.strictEqual(f.notifications.length, 0, 'Wait until the modal finishes closing to announce success');
    await c.submit({preventDefault() {}}); assert.strictEqual(f.pending.length, 3, 'The confirmation cannot submit again');
    c.modal.fire('hidden.bs.modal');
    assert.deepStrictEqual(f.notifications, ['feedback:submitted']);
    assert.strictEqual(c.message.value, ''); assert.strictEqual(c.rating.value, 'good'); assert(!c.form.hidden);

    const stale = c.submit({preventDefault() {}});
    c.reset(); c.message.value = 'New draft'; c.select(0);
    f.pending[3].resolve({data: {saved: true}}); await stale;
    assert(!c.form.hidden); assert.strictEqual(f.notifications.length, 1); assert.strictEqual(c.message.value, 'New draft'); assert.strictEqual(c.rating.value, 'bad');
    const rejected = c.submit({preventDefault() {}});
    f.pending[4].reject({response: {status: 429}}); await rejected;
    assert.strictEqual(c.error.textContent, 'Please wait a minute before sending more feedback.');
    floatingChecks();
    console.log('Passed: feedback ratings/notes/context, close-and-confirm success, retry/race handling, floating scroll hysteresis, panel/overlay suppression, keyboard/fullscreen suppression and navigation spacing.');
};

function floatingChecks() {
    const frames = new Map(), timers = new Map(), events = {}, docEvents = {};
    let frameId = 0, timerId = 0;
    const mobile = {matches: true}, button = new Element(), confirmation = new Element(), root = new Element();
    confirmation.hidden = true;
    root.querySelector = selector => selector === '[data-feedback-launcher]' ? button : confirmation;
    const body = new Element();
    const obstacles = [], overlays = [], panels = [];
    const doc = {body, documentElement: {scrollHeight: 3000}, activeElement: null,
        addEventListener(name, handler) { docEvents[name] = handler; },
        querySelectorAll(selector) {
            if (selector.startsWith('#menu')) return obstacles;
            return overlays.concat(panels.filter(panel => panel.matches(selector)));
        }};
    const win = {document: doc, innerWidth: 390, innerHeight: 844, scrollY: 0,
        visualViewport: Object.assign(new Element(), {height: 844, scale: 1}),
        matchMedia: () => mobile, addEventListener(name, handler) { events[name] = handler; },
        getComputedStyle: element => element.computed || {display: 'block', visibility: 'visible', position: 'fixed'},
        requestAnimationFrame(callback) { frames.set(++frameId, callback); return frameId; },
        setTimeout(callback, delay) { timers.set(++timerId, {callback, delay}); return timerId; }, clearTimeout(id) { timers.delete(id); },
        ResizeObserver: class { constructor(callback) { this.callback = callback; } observe() {} },
        MutationObserver: class { constructor(callback) { this.callback = callback; } observe() {} }};
    const menu = new Element();
    menu.getBoundingClientRect = () => ({width: 390, height: 82, top: win.innerHeight - 82, bottom: win.innerHeight});
    obstacles.push(menu);
    const floating = new Floating(root, win);
    function flush() { const callbacks = [...frames.values()]; frames.clear(); callbacks.forEach(callback => callback()); }
    function scroll(y) { win.scrollY = y; events.scroll(); flush(); }
    assert(!button.hidden); assert(!button.classList.contains('is-scrolled-away'));
    assert.strictEqual(root.style['--feedback-bottom'], '82px');
    scroll(100); assert(button.classList.contains('is-scrolled-away'));
    scroll(95); assert(button.classList.contains('is-scrolled-away'), 'Small upward movement cannot flicker the button');
    scroll(75); assert(!button.classList.contains('is-scrolled-away'));
    scroll(80); assert(!button.classList.contains('is-scrolled-away'), 'Small downward movement does not hide the button');
    scroll(110); assert(button.classList.contains('is-scrolled-away'));
    scroll(0); assert(!button.classList.contains('is-scrolled-away'), 'Returning to the top always shows it');

    mobile.matches = false; events.resize(); flush(); scroll(400);
    assert(!button.hidden); assert(!button.classList.contains('is-scrolled-away'), 'Desktop never hides while scrolling');
    const overlay = new Element();
    docEvents['show.bs.modal']({target: overlay}); assert(button.hidden, 'Hide before an opening modal can cover it');
    docEvents['hidden.bs.modal']({target: overlay}); assert(!button.hidden, 'Restore synchronously so Bootstrap can return keyboard focus');
    docEvents['show.bs.offcanvas']({target: overlay}); assert(button.hidden);
    docEvents['hidden.bs.offcanvas']({target: overlay}); assert(!button.hidden);
    overlays.push(menu); floating.update(); assert(button.hidden, 'Fallback fullscreen overlays are also detected');
    overlays.pop();
    doc.fullscreenElement = {}; floating.update(); assert(button.hidden); doc.fullscreenElement = null;
    body.classList.add('score-editor-fullscreen-open'); floating.update(); assert(button.hidden); body.classList.remove('score-editor-fullscreen-open');
    docEvents.webkitbeginfullscreen(); assert(button.hidden); docEvents.webkitendfullscreen(); flush(); assert(!button.hidden);

    mobile.matches = true; doc.activeElement = {matches: () => true}; docEvents.focusin(); flush(); assert(button.hidden);
    doc.activeElement = null; docEvents.focusout(); flush(); assert(!button.hidden);
    win.visualViewport.height = 500; win.visualViewport.fire('resize'); flush(); assert(button.hidden, 'A keyboard can shrink the visual viewport without a focus event');
    win.visualViewport.height = 844; win.visualViewport.fire('resize'); flush(); assert(!button.hidden);
    const score = new Element();
    let scoreTabActive = false;
    score.matches = selector => selector.includes('.score-footer');
    score.getBoundingClientRect = () => scoreTabActive ? {width: 360, height: 100, top: 620, bottom: 720} : {width: 0, height: 0, top: 0, bottom: 0};
    panels.push(score);
    floating.update(); assert(!button.hidden, 'An inactive score tab must not hide feedback');
    for (const isMobile of [false, true]) {
        mobile.matches = isMobile;
        scoreTabActive = true; docEvents['shown.bs.tab'](); flush();
        assert(button.hidden, 'The visible floating score controls hide feedback on desktop and mobile');
        scoreTabActive = false; docEvents['shown.bs.tab'](); flush();
        assert(!button.hidden, 'Leaving the score tab restores feedback');
    }
    const player = new Element();
    player.matches = selector => selector.includes('[data-playlist-player]');
    player.getBoundingClientRect = () => ({width: 390, height: 100, top: 620, bottom: 720});
    panels.push(player);
    floating.observer.callback([{type: 'childList'}]); flush(); assert(button.hidden, 'Opening a floating audio player hides feedback');
    player.hidden = true;
    floating.observer.callback([{type: 'attributes', target: player}]); flush(); assert(!button.hidden, 'Closing the player restores feedback');
    player.hidden = false; player.computed = {display: 'none', visibility: 'visible'};
    floating.update(); assert(!button.hidden, 'A player hidden by its styles must not hide feedback');
    player.computed = {display: 'block', visibility: 'visible'};
    floating.update(); assert(button.hidden);
    panels.pop(); floating.observer.callback([{type: 'childList'}]); flush(); assert(!button.hidden, 'Removing the player restores feedback');
    assert.strictEqual(root.style['--feedback-bottom'], '82px', 'Feedback retains its normal navigation spacing');
    scroll(500); assert(button.classList.contains('is-scrolled-away'));
    docEvents['feedback:submitted'](); assert(!button.hidden); assert(!button.classList.contains('is-scrolled-away'));
    assert.strictEqual(confirmation.textContent, 'Thank you for your feedback!'); assert(!confirmation.hidden);
    doc.activeElement = button; scroll(600);
    assert(button.classList.contains('is-scrolled-away'), 'Restored pointer focus after submission must not prevent later scroll hiding');
    docEvents.keydown({key: 'Tab'}); flush();
    assert(!button.classList.contains('is-scrolled-away'), 'Keyboard focus stays available until the user tabs away');
    docEvents.pointerdown(); doc.activeElement = null;
    [...timers.values()].filter(timer => timer.delay === 5000).forEach(timer => timer.callback()); assert(confirmation.hidden);
    floating.observer.callback([{type: 'childList'}]); floating.observer.callback([{type: 'childList'}]);
    assert.strictEqual(frames.size, 1, 'DOM changes coalesce into one scheduled update'); flush();
    scroll(700); events.pageshow(); flush(); assert(!button.classList.contains('is-scrolled-away'), 'Reload/back restoration starts with the complete button');
}
