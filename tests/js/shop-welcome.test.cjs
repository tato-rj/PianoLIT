const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

module.exports = function () {
    const source = fs.readFileSync(require('node:path').join(__dirname, '../../resources/js/views/shop-welcome.js'), 'utf8');
    function fixture({storage = new Map(), retry = false, blocked = false, unavailable = false, auto = true, subscribed = false, authenticated = false} = {}) {
        function target(extra = {}) {
            return Object.assign({events: {}, addEventListener(name, callback) { this.events[name] = callback; }}, extra);
        }
        let shows = 0, focused = 0, id = 0;
        const timers = new Map();
        const state = {dataset: {subscribed: String(subscribed), authenticated: String(authenticated)}};
        const launcher = target({hidden: true, isConnected: true, focus() { focused++; }});
        const modal = target({dataset: {shopWelcomeRetry: String(retry), shopWelcomeAuto: String(auto)}});
        const doc = target({hidden: false, activeElement: {isConnected: true, focus() { focused++; }},
            querySelector(selector) {
                return {'[data-shop-welcome-state]': state, '[data-shop-welcome]': modal, '[data-shop-welcome-launcher]': launcher}[selector] || blocked;
            }});
        const win = target({localStorage: {
            getItem(key) { if (unavailable) throw Error('Blocked'); return storage.get(key) ?? null; },
            setItem(key, value) { if (unavailable) throw Error('Blocked'); storage.set(key, value); }
        }, setTimeout(callback, delay) { assert.equal(delay, 3000); timers.set(++id, callback); return id; },
        clearTimeout(timer) { timers.delete(timer); },
        bootstrap: {Modal: {getOrCreateInstance() { return {show() {
            let prevented = false;
            modal.events['show.bs.modal']({preventDefault() { prevented = true; }});
            if (!prevented) { shows++; modal.events['shown.bs.modal'](); }
        }, hide() { modal.events['hidden.bs.modal'](); }}; }}}});
        vm.runInNewContext(source, {document: doc, window: win});
        return {modal, launcher, doc, win, timers, storage, get shows() { return shows; }, get focused() { return focused; },
            click() { doc.activeElement = launcher; launcher.events.click(); },
            block(value) { blocked = value; },
            flush() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(callback => callback()); }};
    }
    const key = 'pianolit.shop.newsletter.seen';
    const subscribedKey = 'pianolit.shop.newsletter.subscribed';
    const signedIn = fixture({authenticated: true}); signedIn.flush();
    assert.equal(signedIn.shows, 0, 'Logged-in visitors never receive the guest prompt');
    assert.equal(signedIn.launcher.hidden, true);
    assert.equal(signedIn.storage.has(subscribedKey), false, 'Login is not newsletter consent');
    const first = fixture();
    assert.equal(first.shows, 0);
    assert.equal(first.storage.size, 0, 'Leaving before the delay does not count as seeing the popup');
    assert.equal(first.launcher.hidden, true);
    first.flush();
    assert.equal(first.shows, 1);
    assert.equal(first.storage.get(key), '1');
    first.modal.events['hidden.bs.modal']();
    assert.equal(first.launcher.hidden, false, 'Closing the popup leaves the persistent offer button');
    assert.equal(first.focused, 1, 'Closing restores the prior keyboard focus');
    first.win.events.pageshow(); first.flush();
    assert.equal(first.shows, 1, 'Page-cache restoration cannot show it again');
    const returning = fixture({storage: first.storage}); returning.flush();
    assert.equal(returning.shows, 0, 'Later visits remain suppressed');
    assert.equal(returning.launcher.hidden, false, 'Later visits keep the offer accessible');
    returning.click();
    assert.equal(returning.shows, 1, 'The offer button reopens a previously dismissed popup');
    assert.equal(returning.launcher.hidden, true);
    returning.modal.events['hidden.bs.modal']();
    assert.equal(returning.launcher.hidden, false);
    assert.equal(returning.focused, 1, 'Manual dismissal returns focus to the offer button');

    const category = fixture({auto: false}); category.flush();
    assert.equal(category.shows, 0, 'Product/category/bag pages never interrupt browsing');
    assert.equal(category.launcher.hidden, false);
    category.click(); assert.equal(category.shows, 1);

    const confirmed = fixture({subscribed: true}); confirmed.flush();
    assert.equal(confirmed.shows, 0);
    assert.equal(confirmed.launcher.hidden, true);
    assert.equal(confirmed.storage.get(subscribedKey), '1', 'Only confirmed server success records subscription');
    const subscribedReturn = fixture({storage: confirmed.storage}); subscribedReturn.flush();
    assert.equal(subscribedReturn.launcher.hidden, true, 'Subscription survives later browser visits');
    assert.equal(subscribedReturn.shows, 0);
    const otherSubscribeTab = fixture(); otherSubscribeTab.flush();
    otherSubscribeTab.win.events.storage({key: subscribedKey, newValue: '1'});
    assert.equal(otherSubscribeTab.launcher.hidden, true, 'Successful subscription hides other tabs’ offer buttons');
    assert.equal(otherSubscribeTab.timers.size, 0);

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
    assert.equal(blockedStorage.launcher.hidden, false);
    blockedStorage.click(); assert.equal(blockedStorage.shows, 1, 'Manual signup remains available when storage is blocked');
    const validation = fixture({storage: first.storage, retry: true}); validation.flush();
    assert.equal(validation.shows, 1, 'An explicit failed signup remains correctable');
    validation.modal.events['hidden.bs.modal'](); validation.doc.events['hidden.bs.modal'](); validation.flush();
    assert.equal(validation.shows, 1, 'Validation recovery is a one-time exception');
    assert.equal(validation.launcher.hidden, false, 'Failed validation never counts as subscribing');
    console.log('Passed: persistent Shop offer, manual reopening, subscription-only dismissal, storage, focus and validation recovery.');
};
