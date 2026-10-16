const assert = require('assert');
const initialize = require('../../resources/js/components/payment-checkout');
const tick = () => new Promise(resolve => setImmediate(resolve));

function fixture(options = {}) {
    const events = {}, documentEvents = {}, calls = [], replies = [], navigations = [], dispatched = [], timers = new Map();
    const scriptNodes = [];
    let timerId = 0, focused = 0, trapped = 0;
    const status = {textContent: ''}, retry = {hidden: true}, heading = {setAttribute() {}, focus() {focused++;}};
    function node(extra = {}) { return {attributes: {}, inert: true, setAttribute(key, value) {this.attributes[key] = value;}, removeAttribute(key) {delete this.attributes[key];}, ...extra}; }
    const loading = node({outerHTML: 'loading-shell'}), stage = node(), background = {inert: false};
    const content = {innerHTML: 'loading-shell', insertAdjacentHTML(position, html) {this.innerHTML += html;}};
    const modal = {id: 'payment-checkout-modal', dataset: {checkoutCancelUrl: '/membership/pricing'}, busy: false,
        querySelector(selector) {
            if (selector === '.modal-content') return content;
            if (selector === '[data-checkout-loading]') return loading;
            if (selector === '[data-checkout-stage]') return content.innerHTML.includes('form-') ? stage : null;
            if (selector.includes('aria-busy')) return this.busy ? {} : null;
            return {'[data-checkout-load-status]': status, '[data-checkout-retry]': retry, '#checkout-heading': heading}[selector] || null;
        },
        addEventListener(name, handler) {events[name] = handler;},
        removeEventListener(name, handler) {if (events[name] === handler) delete events[name];},
        setAttribute(key, value) {this[key] = value;},
        dispatchEvent(event) {
            dispatched.push(event.type);
            if (event.type === 'checkout:ready' && options.autoReady !== false) queueMicrotask(() => events['checkout:loaded'] && events['checkout:loaded']());
        }, classList: {contains() {return true;}}};
    const dialog = {_focustrap: {deactivate() {trapped--;}, activate() {trapped++;}}, shown: 0,
        show() {if (!this.opened) {this.opened = true; this.shown++; this.transitioning = options.autoShown === false; events['show.bs.modal'](); if (!this.transitioning) events['shown.bs.modal']();}}, hide() {
            if (this.transitioning) return;
            let prevented = false;
            events['hide.bs.modal']({preventDefault() {prevented = true;}});
            if (!prevented) {this.opened = false; events['hidden.bs.modal']();}
        }};
    const doc = {readyState: 'loading', getElementById() {return background;}, body: {appendChild() {}},
        addEventListener(name, handler) {documentEvents[name] = handler;},
        createElement() {return {remove() {this.removed = true;}};}, head: {appendChild(node) {
            scriptNodes.push(node);
            if (options.autoScripts !== false) queueMicrotask(() => node.onload());
        }}};
    const win = {document: doc, URL, Stripe() {}, CustomEvent: class {constructor(type) {this.type = type;}}, AbortController,
        setTimeout(callback) {timers.set(++timerId, callback); return timerId;}, clearTimeout(id) {timers.delete(id);},
        location: {href: 'https://my.pianolit.test/membership/pricing?search=kept', assign(url) {navigations.push(url);}},
        history: {state: {keep: true}, replaceState(state, title, url) {win.location.href = url;}},
        bootstrap: {Modal: {getOrCreateInstance() {return dialog;}}},
        fetch(url, options) {
            calls.push({url, options});
            const reply = replies.shift();
            if (typeof reply === 'function') return reply();
            return Promise.resolve(reply);
        }, DOMParser: class {parseFromString(html) {
            return {querySelector() {return {dataset: {checkoutController: '/js/views/membership-billing.js', checkoutKey: html},
                querySelector() {return {outerHTML: 'form-' + html};}};}};
        }}};
    if (options.cold) delete win.Stripe;
    const api = initialize(win);
    const launcher = new api.Launcher(modal);
    return {win, api, launcher, events, content, modal, dialog, calls, replies, navigations, status, retry, dispatched, timers, background, stage, loading, scriptNodes,
        response(plan) {return {ok: true, status: 200, json: async () => ({html: plan})};},
        finishShow() {dialog.transitioning = false; events['shown.bs.modal']();},
        focused() {return focused;}, trapped() {return trapped;}};
}
module.exports = async function () {
    let f = fixture(); f.replies.push(f.response('monthly'));
    await f.launcher.open('/membership/monthly/checkout');
    assert.strictEqual(f.content.innerHTML, 'loading-shellform-monthly');
    assert.strictEqual(f.calls[0].options.credentials, 'same-origin');
    assert.strictEqual(f.calls[0].options.headers.Accept, 'application/json');
    assert.strictEqual(f.calls[0].options.cache, 'no-store');
    assert.strictEqual(new URL(f.win.location.href).searchParams.get('checkout'), 'monthly');
    assert.strictEqual(new URL(f.win.location.href).searchParams.get('search'), 'kept');
    assert(f.dispatched.includes('checkout:ready')); assert.strictEqual(f.focused(), 1);
    assert.strictEqual(f.modal.dataset.checkoutState, 'ready');
    assert.strictEqual(f.stage.inert, false); assert.strictEqual(f.loading.inert, true);
    assert.strictEqual(f.modal['aria-labelledby'], 'checkout-heading');
    assert.strictEqual(f.background.inert, true, 'Background controls cannot receive focus, including during a Stripe bank challenge.');

    let release;
    f = fixture(); f.replies.push(() => new Promise(resolve => {release = resolve;}));
    const old = f.launcher.open('/membership/monthly/checkout');
    f.replies.push(f.response('yearly')); await f.launcher.open('/membership/yearly/checkout');
    release(f.response('monthly')); await old;
    assert.strictEqual(f.content.innerHTML, 'loading-shellform-yearly', 'A stale monthly request cannot replace the latest yearly form.');
    assert(f.calls[0].options.signal.aborted);

    f = fixture(); f.replies.push(() => new Promise(resolve => {release = resolve;}));
    const closed = f.launcher.open('/membership/monthly/checkout');
    f.dialog.hide(); release(f.response('monthly')); await closed;
    assert(!f.content.innerHTML.includes('form-monthly'));
    assert(f.calls[0].options.signal.aborted);

    f = fixture(); f.replies.push({status: 401}); await f.launcher.open('/membership/monthly/checkout');
    assert.deepStrictEqual(f.navigations, ['https://my.pianolit.test/membership/monthly/checkout']);
    assert.throws(() => f.api.sameOrigin('https://example.test/pay'), /PianoLIT/);

    f = fixture(); f.replies.push({ok: false, status: 503, json: async () => ({message: 'Unavailable'})});
    await f.launcher.open('/membership/monthly/checkout'); assert.strictEqual(f.retry.hidden, false);
    assert(f.status.textContent.includes('try again'));
    f.replies.push(f.response('monthly')); await f.launcher.open('/membership/monthly/checkout');
    assert.strictEqual(f.content.innerHTML, 'loading-shellform-monthly');
    f.modal.busy = true; let prevented = false;
    f.events['hide.bs.modal']({preventDefault() {prevented = true;}}); assert(prevented);
    f.events['checkout:busy'](); assert.strictEqual(f.trapped(), -1, 'Stripe bank authentication may receive focus outside the Bootstrap dialog.');
    f.modal.busy = false; f.events['checkout:busy'](); assert.strictEqual(f.trapped(), 0);
    f.dialog.hide(); assert.strictEqual(new URL(f.win.location.href).searchParams.get('checkout'), null);
    assert.strictEqual(f.background.inert, false);
    f = fixture(); f.win.location.href += '&modal=payment-checkout-modal';
    await f.launcher.start();
    assert.strictEqual(new URL(f.win.location.href).searchParams.get('modal'), null);
    assert.strictEqual(f.calls.length, 0, 'An invalid restore URL cannot open an empty checkout shell or fetch an arbitrary order.');

    f = fixture({cold: true, autoScripts: false, autoReady: false, autoShown: false});
    f.replies.push(() => new Promise(resolve => {release = resolve;}));
    const coldOpen = f.launcher.open('/membership/monthly/checkout');
    assert.strictEqual(f.dialog.shown, 1, 'The existing shell opens immediately, before the first response.');
    assert.strictEqual(f.scriptNodes[0].src, 'https://js.stripe.com/v3/', 'Stripe starts alongside the private fragment request.');
    assert.strictEqual(f.content.innerHTML, 'loading-shell');
    release(f.response('monthly')); await tick();
    assert.strictEqual(f.scriptNodes.length, 2, 'The controller downloads while Stripe is still pending.');
    assert.strictEqual(f.modal.dataset.checkoutState, 'loading');
    assert.strictEqual(f.stage.inert, true);
    f.scriptNodes.forEach(script => script.onload()); await tick();
    assert(f.dispatched.includes('checkout:ready'));
    assert.strictEqual(f.modal.dataset.checkoutState, 'loading', 'Script download is not the same as hosted-field readiness.');
    f.events['checkout:loaded'](); await coldOpen;
    assert.strictEqual(f.modal.dataset.checkoutState, 'loading', 'A fast form cannot replace the placeholder during the opening transition.');
    assert.strictEqual(f.focused(), 0);
    f.finishShow();
    assert.strictEqual(f.modal.dataset.checkoutState, 'ready');
    assert.strictEqual(f.focused(), 1);

    f = fixture({autoShown: false});
    f.replies.push(() => new Promise(resolve => {release = resolve;}));
    const interruptedOpening = f.launcher.open('/membership/monthly/checkout');
    f.events.click({preventDefault() {}, target: {closest(selector) {return selector === '[data-checkout-cancel]' ? {} : null;}}});
    assert(f.calls[0].options.signal.aborted, 'Cancel stops the request even during Bootstrap’s opening transition.');
    f.finishShow(); release(f.response('monthly')); await interruptedOpening;
    assert.strictEqual(f.dialog.opened, false);
    assert.strictEqual(f.background.inert, false);
    assert.strictEqual(f.content.innerHTML, 'loading-shell');

    f = fixture({autoReady: false}); f.replies.push(f.response('monthly'));
    const canceledMount = f.launcher.open('/membership/monthly/checkout'); await tick();
    assert(f.events['checkout:loaded']);
    f.dialog.hide(); await canceledMount;
    assert.strictEqual(f.events['checkout:loaded'], undefined, 'Closing tears down readiness listeners and their timer.');
    assert.strictEqual(f.timers.size, 0);
    assert.strictEqual(f.focused(), 0);
    assert.strictEqual(f.modal.dataset.checkoutState, 'loading');

    f = fixture({autoReady: false}); f.replies.push(f.response('monthly'));
    const fieldTimeout = f.launcher.open('/membership/monthly/checkout'); await tick();
    Array.from(f.timers.values()).pop()(); await fieldTimeout;
    assert.strictEqual(f.modal.dataset.checkoutState, 'error');
    assert.strictEqual(f.retry.hidden, false);
    assert.strictEqual(f.modal['aria-busy'], 'false');
    assert.strictEqual(f.content.innerHTML, 'loading-shell', 'Timed-out hosted fields are disposed instead of flashing an unfinished form.');

    f = fixture({cold: true, autoScripts: false}); f.replies.push(f.response('monthly'));
    const failedScript = f.launcher.open('/membership/monthly/checkout'); await tick();
    f.scriptNodes[0].onerror(); f.scriptNodes[1].onload(); await failedScript;
    assert.strictEqual(f.modal.dataset.checkoutState, 'error'); assert.strictEqual(f.retry.hidden, false);

    f = fixture({autoReady: false});
    f.modal.dataset.checkoutDirect = 'true'; f.launcher.direct = true;
    f.modal.dataset.checkoutController = '/js/views/membership-billing.js'; f.content.innerHTML += 'form-monthly';
    const directOpen = f.launcher.start(); await tick();
    assert.strictEqual(f.modal.dataset.checkoutState, 'loading');
    f.events['checkout:loaded'](); await directOpen;
    assert.strictEqual(f.modal.dataset.checkoutState, 'ready', 'Direct URLs use the same guarded reveal as AJAX checkout.');
    await tick();
};
if (require.main === module) module.exports().then(() => console.log('Payment checkout lifecycle regressions passed.')).catch(error => {console.error(error); process.exitCode = 1;});
