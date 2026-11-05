const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

module.exports = function () {
    const source = fs.readFileSync(require('node:path').join(__dirname, '../../resources/js/views/shop-welcome.js'), 'utf8');
    function fixture({storage = new Map(), retry = false, blocked = false, unavailable = false} = {}) {
        function target(extra = {}) {
            return Object.assign({events: {}, addEventListener(name, callback) { this.events[name] = callback; }}, extra);
        }
        let shows = 0, focused = 0, id = 0;
        const timers = new Map();
        const modal = target({dataset: {shopWelcomeRetry: String(retry)}});
        const doc = target({hidden: false, activeElement: {isConnected: true, focus() { focused++; }},
            querySelector(selector) { return selector === '[data-shop-welcome]' ? modal : blocked; }});
        const win = target({localStorage: {
            getItem(key) { if (unavailable) throw Error('Blocked'); return storage.get(key) ?? null; },
            setItem(key, value) { if (unavailable) throw Error('Blocked'); storage.set(key, value); }
        }, setTimeout(callback, delay) { assert.equal(delay, 3000); timers.set(++id, callback); return id; },
        clearTimeout(timer) { timers.delete(timer); },
        bootstrap: {Modal: {getOrCreateInstance() { return {show() {
            let prevented = false;
            modal.events['show.bs.modal']({preventDefault() { prevented = true; }});
            if (!prevented) { shows++; modal.events['shown.bs.modal'](); }
        }}; }}}});
        vm.runInNewContext(source, {document: doc, window: win});
        return {modal, doc, win, timers, storage, get shows() { return shows; }, get focused() { return focused; },
            block(value) { blocked = value; },
            flush() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(callback => callback()); }};
    }
    const key = 'pianolit.shop.newsletter.seen';
    const first = fixture();
    assert.equal(first.shows, 0);
    assert.equal(first.storage.size, 0, 'Leaving before the delay does not count as seeing the popup');
    first.flush();
    assert.equal(first.shows, 1);
    assert.equal(first.storage.get(key), '1');
    first.modal.events['hidden.bs.modal']();
    assert.equal(first.focused, 1, 'Closing restores the prior keyboard focus');
    first.win.events.pageshow(); first.flush();
    assert.equal(first.shows, 1, 'Page-cache restoration cannot show it again');
    const returning = fixture({storage: first.storage}); returning.flush();
    assert.equal(returning.shows, 0, 'Later visits remain suppressed');

    const busy = fixture({blocked: true}); busy.flush();
    assert.equal(busy.shows, 0);
    busy.block(false); busy.doc.events['hidden.bs.offcanvas'](); busy.flush();
    assert.equal(busy.shows, 1, 'Wait for another dialog to close');
    const background = fixture(); background.doc.hidden = true;
    background.doc.events.visibilitychange(); background.flush();
    assert.equal(background.shows, 0);
    background.doc.hidden = false; background.doc.events.visibilitychange(); background.flush();
    assert.equal(background.shows, 1);

    const departed = fixture(); departed.win.events.pagehide(); departed.flush();
    assert.equal(departed.shows, 0);
    const otherTab = fixture(); otherTab.storage.set(key, '1'); otherTab.flush();
    assert.equal(otherTab.shows, 0, 'Recheck storage immediately before opening');
    const simultaneous = fixture();
    simultaneous.win.events.storage({key, newValue: '1'});
    assert.equal(simultaneous.timers.size, 0);
    const blockedStorage = fixture({unavailable: true}); blockedStorage.flush();
    assert.equal(blockedStorage.shows, 0, 'Unavailable storage cannot cause recurring prompts');
    const validation = fixture({storage: first.storage, retry: true}); validation.flush();
    assert.equal(validation.shows, 1, 'An explicit failed signup remains correctable');
    validation.doc.events['hidden.bs.modal'](); validation.flush();
    assert.equal(validation.shows, 1, 'Validation recovery is a one-time exception');
    console.log('Passed: Shop welcome persistence, delay, dialog/visibility handoffs, focus and validation recovery.');
};
