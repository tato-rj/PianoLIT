const assert = require('assert');
const {Controller, expression} = require('../../resources/js/views/feedback');

class Element {
    constructor() {
        this.events = {}; this.attributes = {}; this.hidden = false; this.disabled = false;
        this.value = ''; this.textContent = ''; this.style = {setProperty(name, value) { this[name] = value; }, removeProperty(name) { delete this[name]; }};
        const classes = new Set();
        this.classList = {contains: name => classes.has(name), add: name => classes.add(name), remove: name => classes.delete(name),
            toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); }};
    }
    setAttribute(name, value) { this.attributes[name] = String(value); }
    addEventListener(name, handler) { this.events[name] = handler; }
    focus() { this.focused = true; }
    blur() { this.focused = false; }
    getBoundingClientRect() { return {width: 320}; }
    setPointerCapture(id) { this.captured = id; }
    fire(name, event = {}) { return this.events[name](Object.assign({preventDefault() {}}, event)); }
}

function fixture() {
    const map = {}, frames = new Map(), pending = [];
    let nextFrame = 0;
    const selectors = ['[data-feedback-form]', '[data-feedback-range]', '[name="message"]', '[name="rating"]', '[name="_token"]',
        '[data-feedback-labels]', '[data-feedback-thumb]', '[data-feedback-handle-mouth]', '[data-feedback-note]', '[data-feedback-error]', '[data-feedback-thanks]',
        '.feedback-submit', '[data-feedback-submit-label]', '[data-feedback-add-note]', '[data-feedback-note-back]', '.feedback-done', '.modal-content', '[data-feedback-swipe]'];
    selectors.forEach(selector => { map[selector] = new Element(); });
    const buttons = [0, 1, 2].map(value => Object.assign(new Element(), {dataset: {feedbackRating: String(value)}}));
    const faces = [0, 1].map(() => {
        const elements = {'[data-face-eye="left"]': new Element(), '[data-face-eye="right"]': new Element(), '[data-face-mouth]': new Element()};
        return {querySelector: selector => elements[selector]};
    });
    const modal = new Element();
    modal.querySelector = selector => map[selector];
    modal.querySelectorAll = selector => selector === '.feedback-face' ? faces : buttons;
    const form = map['[data-feedback-form]'];
    form.querySelector = modal.querySelector;
    form.querySelectorAll = () => [...buttons, map['[data-feedback-range]'], map['[name="message"]'], map['.feedback-submit'], map['[data-feedback-add-note]'], map['[data-feedback-note-back]']];
    form.reset = () => { map['[name="message"]'].value = ''; };
    form.action = '/feedback'; map['[name="_token"]'].value = 'session-csrf';
    const win = {matchMedia: () => ({matches: false}), visualViewport: Object.assign(new Element(), {height: 500, offsetTop: 12}),
        requestAnimationFrame(callback) { frames.set(++nextFrame, callback); return nextFrame; }, cancelAnimationFrame(id) { frames.delete(id); }};
    const http = {post(url, payload, options) { return new Promise((resolve, reject) => pending.push({url, payload, options, resolve, reject})); }};
    const controller = new Controller(modal, http, win);
    function animate() { for (const time of [0, 440]) { const callbacks = [...frames.values()]; frames.clear(); callbacks.forEach(callback => callback(time)); } }
    return {controller, modal, map, pending, buttons, win, animate};
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
    assert.deepStrictEqual(f.pending[0].payload, {rating: 'bad', message: null, _token: 'session-csrf'});
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
    assert(c.sent); assert(c.form.hidden); assert(!c.thanks.hidden); assert(map['.feedback-done'].focused);
    await c.submit({preventDefault() {}}); assert.strictEqual(f.pending.length, 3, 'The confirmation cannot submit again');
    c.reset(); assert.strictEqual(c.message.value, ''); assert.strictEqual(c.rating.value, 'good'); assert(c.thanks.hidden); assert(!c.form.hidden);

    const stale = c.submit({preventDefault() {}});
    c.reset(); c.message.value = 'New draft'; c.select(0);
    f.pending[3].resolve({data: {saved: true}}); await stale;
    assert(!c.form.hidden); assert(c.thanks.hidden); assert.strictEqual(c.message.value, 'New draft'); assert.strictEqual(c.rating.value, 'bad');
    const rejected = c.submit({preventDefault() {}});
    f.pending[4].reject({response: {status: 429}}); await rejected;
    assert.strictEqual(c.error.textContent, 'Please wait a minute before sending more feedback.');
    console.log('Passed: feedback morphing, swipe/keyboard ratings, note/viewport behavior, submission identity, duplicate clicks, failure recovery and stale responses.');
};
