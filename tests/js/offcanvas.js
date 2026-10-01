const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = function () {
    const handlers = {};
    let panels = [];
    let opened = null;
    let locked = true;
    let restoredFocus = false;
    const document = {
        activeElement: {getClientRects() { return [1]; }, focus() { restoredFocus = true; }},
        addEventListener(event, callback) { handlers[event] = callback; },
        querySelectorAll() { return panels; }
    };
    const Offcanvas = {NAME: 'offcanvas', getOrCreateInstance(panel) { return panel; }};
    const Modal = {NAME: 'modal', getOrCreateInstance(target) {
        return {show(trigger) { opened = {target, trigger}; locked = true; }};
    }};
    function panel() {
        return {
            hiding: false,
            addEventListener(event, callback, options) {
                assert.strictEqual(event, 'hidden.bs.offcanvas');
                assert(options.once);
                this.hidden = callback;
            },
            hide() { this.hiding = true; },
            finish() { locked = false; this.hidden(); }
        };
    }
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/components/offcanvas.js'), 'utf8'), {
        document, window: {bootstrap: {Offcanvas, Modal}}
    });
    const options = panel();
    let event = {target: options, preventDefault() { this.defaultPrevented = true; }};
    panels = [options];
    handlers['show.bs.offcanvas'](event);
    assert(!event.defaultPrevented, 'Bootstrap opens its own panel without interception');

    const modal = {addEventListener(event, handler) { assert.strictEqual(event, 'hidden.bs.modal'); this.hidden = handler; }};
    const shareButton = {};
    event = {target: modal, relatedTarget: shareButton, preventDefault() { this.defaultPrevented = true; }};
    handlers['show.bs.modal'](event);
    assert(event.defaultPrevented && options.hiding);
    assert.strictEqual(opened, null, 'Share waits until the Options panel finishes closing');
    options.finish();
    assert.strictEqual(opened.target, modal);
    assert.strictEqual(opened.trigger, shareButton);
    assert(locked, 'The new modal locks scrolling after the old panel releases it');
    modal.hidden();
    assert(restoredFocus, 'Closing Share returns focus to the visible Options opener');

    const first = panel(), second = panel();
    panels = [first, second];
    opened = null;
    const next = {addEventListener() {}, show(trigger) { opened = trigger; locked = true; }};
    const favoriteButton = {};
    event = {target: next, relatedTarget: favoriteButton, preventDefault() { this.defaultPrevented = true; }};
    handlers['show.bs.offcanvas'](event);
    first.finish();
    assert.strictEqual(opened, null);
    second.finish();
    assert.strictEqual(opened, favoriteButton);
    assert(locked, 'Panel handoff preserves the new Bootstrap scroll lock');

    const loading = panel();
    panels = [loading];
    event.defaultPrevented = true;
    handlers['show.bs.offcanvas'](event);
    assert(!loading.hiding, 'An AJAX-prevented opening stays owned by its loader');
    console.log('Passed: native offcanvas/modal handoffs wait for Bootstrap hidden events and retain scroll ownership.');
};
