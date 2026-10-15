const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/membership-billing.js'), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));

function element(attributes = {}) {
    return {attributes, events: {}, disabled: false, textContent: '', style: {}, hidden: true,
        getAttribute(name) {return this.attributes[name];}, setAttribute(name, value) {this.attributes[name] = value;},
        addEventListener(name, callback) {this.events[name] = callback;}};
}
function fixture(purpose = 'purchase', invoiceMode = false) {
    const requests = [], redirects = [], confirmations = [], replies = [];
    const button = element(), errors = element(), coupon = element({'data-coupon-url': '/coupon'}), feedback = element(); coupon.value = '';
    const total = element({'data-base-price': '799'}), mount = element();
    const form = element({'data-operation': 'operation-review', 'data-purpose': purpose, 'data-key': 'pk_review', 'data-prepare': '/prepare', 'data-complete': '/complete'});
    form.action = '/submit';
    form.querySelector = selector => ({'button[type="submit"]': button, '#card-errors': errors, '#card-element': mount,
        'input[name="coupon"]': purpose === 'purchase' ? coupon : null, '[data-coupon-feedback]': feedback}[selector]);
    const card = {mount() {}, on(name, callback) { this.change = callback; }};
    const stripe = {elements() {return {create() {return card;}};},
        async confirmCardSetup(secret) { confirmations.push(['setup', secret]); return stripe.setupResult || {setupIntent: {id: 'seti_review'}}; },
        async confirmCardPayment(secret, details) { confirmations.push(['payment', secret, details]); return stripe.paymentResult || {paymentIntent: {status: 'succeeded'}}; }};
    const fetch = (url, options) => {
        requests.push({url, options});
        const reply = replies.shift();
        if (reply instanceof Error) return Promise.reject(reply);
        if (typeof reply === 'function') return reply(url, options);
        if (!reply) throw new Error('Unexpected request '+url);
        return Promise.resolve({ok: reply.ok !== false, json: async () => reply.data || reply});
    };
    const invoiceStatus = element(), invoiceMore = element(), invoiceRetry = element(), invoiceBody = {innerHTML: '', insertAdjacentHTML(where, html) {this.innerHTML += html;}};
    const invoiceContent = {querySelector() {return invoiceBody;}};
    Object.defineProperty(invoiceContent, 'innerHTML', {set(value) {invoiceBody.innerHTML = value;}, get() {return invoiceBody.innerHTML;}});
    const invoiceRoot = element({'data-url': '/invoices'});
    invoiceRoot.querySelector = selector => ({'[data-invoice-status]': invoiceStatus, '[data-invoice-more]': invoiceMore, '[data-invoice-retry]': invoiceRetry, '[data-invoice-content]': invoiceContent}[selector]);
    let shown;
    const jquery = selector => ({on(events, callback) {if (selector.includes('a[')) shown = callback; return this;}});
    const DOMParser = class {parseFromString(html) {return {querySelector() {return {innerHTML: html};}};}};
    const document = {querySelector(selector) {return {'meta[name="csrf-token"]': {content: 'csrf-review'}, '[data-membership-payment]': purpose === 'none' ? null : form, '[data-membership-total]': total, '[data-membership-invoices]': invoiceMode ? invoiceRoot : null}[selector] || null;}, querySelectorAll() {return [];}};
    vm.runInNewContext(source, {document, DOMParser, Stripe: () => stripe, fetch, window: {jQuery: invoiceMode ? jquery : null, location: {hash: '', assign(url) { redirects.push(url); }, reload() { redirects.push('reload'); }}}});
    return {form, button, errors, coupon, invoiceStatus, invoiceMore, invoiceRetry, invoiceBody, openInvoices() {shown({target:{getAttribute() {return '#invoices-tab';}}});}, feedback, total, stripe, card, requests, redirects, confirmations, replies,
        submit() {return form.events.submit({preventDefault() {}});}};
}
module.exports = async function () {
    let f = fixture('none');
    assert.strictEqual(f.requests.length, 0, 'Canceled/ended pages with no card form do not initialize Stripe.');
    f = fixture();
    f.replies.push({operation_id: 'owned-operation', setup_secret: 'setup-secret'}, {complete: false, payment_secret: 'payment-secret'}, {redirect: '/welcome'});
    const first = f.submit(); const second = f.submit();
    await Promise.all([first, second]);
    assert.deepStrictEqual(f.requests.map(r => r.url), ['/prepare', '/submit', '/complete']);
    assert.strictEqual(f.requests[0].options.credentials, 'same-origin');
    assert.strictEqual(f.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-review');
    assert.strictEqual(JSON.parse(f.requests[1].options.body).operation_id, 'owned-operation');
    assert.strictEqual(JSON.parse(f.requests[1].options.body).setup_intent, 'seti_review');
    assert(!('stripeToken' in JSON.parse(f.requests[1].options.body)));
    assert.deepStrictEqual(f.confirmations.map(c => c[0]), ['setup', 'payment']);
    assert.deepStrictEqual(f.redirects, ['/welcome']);

    f = fixture(); f.stripe.setupResult = {error: {message: 'Authentication declined'}};
    f.replies.push({operation_id: 'op', setup_secret: 'setup-secret'});
    await f.submit();
    assert.strictEqual(f.requests.length, 1); assert.strictEqual(f.errors.textContent, 'Authentication declined');
    assert.strictEqual(f.button.disabled, false); assert.strictEqual(f.button.attributes['aria-busy'], 'false');

    f = fixture(); f.replies.push(new Error('Connection interrupted')); await f.submit();
    assert.strictEqual(f.button.disabled, false); assert.strictEqual(f.errors.textContent, 'Connection interrupted');

    f = fixture(); f.stripe.paymentResult = {error: {message: 'Payment requires another card'}};
    f.replies.push({operation_id: 'op', complete: false, payment_secret: 'existing-payment'});
    await f.submit();
    assert.strictEqual(f.requests.length, 1, 'An existing incomplete subscription is confirmed without creating another subscription.');
    assert.strictEqual(f.errors.textContent, 'Payment requires another card');
    f.stripe.paymentResult = null; f.card.change({complete: true});
    f.replies.push({operation_id: 'op', complete: false, payment_secret: 'existing-payment'}, {redirect: '/welcome'});
    await f.submit();
    assert(f.confirmations[1][2].payment_method.card, 'A retry can supply the replacement card to the existing invoice.');
    assert.deepStrictEqual(f.redirects, ['/welcome']);

    f = fixture();
    f.replies.push({operation_id: 'op', complete: false, subscription_setup_secret: 'trial-setup-secret'}, {redirect: '/welcome'});
    await f.submit(); assert.deepStrictEqual(f.confirmations.map(c => c[0]), ['setup']);
    assert.deepStrictEqual(f.redirects, ['/welcome']);

    f = fixture('card'); f.replies.push({operation_id: 'op', setup_secret: 'setup-secret'}, {redirect: '/membership/edit'});
    await f.submit(); assert.deepStrictEqual(f.redirects, ['/membership/edit']);
    assert.strictEqual(f.requests.length, 2);

    f = fixture('card'); f.replies.push({operation_id: 'op', setup_secret: 'setup-secret', setup_complete: true, setup_intent: 'seti_confirmed'}, {redirect: '/membership/edit'});
    await f.submit(); assert.strictEqual(f.confirmations.length, 0, 'A retry never reconfirms an already successful SetupIntent.');
    assert.strictEqual(JSON.parse(f.requests[1].options.body).setup_intent, 'seti_confirmed');

    f = fixture(); let releaseOld;
    f.coupon.value = 'FIRST';
    f.replies.push(() => new Promise(resolve => {releaseOld = resolve;}));
    const old = f.coupon.events.blur();
    f.coupon.value = 'SECOND'; f.coupon.events.input();
    f.replies.push({message: 'Second coupon applied', amount: 399});
    await f.coupon.events.blur();
    releaseOld({ok: true, json: async () => ({message: 'Stale first coupon', amount: 1})}); await old;
    assert.strictEqual(f.feedback.textContent, 'Second coupon applied'); assert.strictEqual(f.total.textContent, '$3.99');
    assert.strictEqual(f.button.disabled, false);
    f.coupon.value = 'THIRD'; f.coupon.events.input(); f.replies.push(new Error('Coupon check failed'));
    await f.coupon.events.blur(); assert.strictEqual(f.feedback.textContent, 'Coupon check failed'); assert.strictEqual(f.button.disabled, false);
    assert.strictEqual(f.total.textContent, '$7.99');

    f = fixture('none', true);
    assert.strictEqual(f.requests.length, 0, 'Invoice history is lazy.');
    f.replies.push({html: 'first invoice rows', after: 'in_older'});
    f.openInvoices(); await flush();
    assert.strictEqual(f.invoiceBody.innerHTML, 'first invoice rows');
    f.replies.push(new Error('Invoice request interrupted'));
    f.invoiceMore.events.click(); await flush();
    assert.strictEqual(f.invoiceRetry.hidden, false); assert.strictEqual(f.invoiceMore.disabled, false);
    f.replies.push({html: ' plus older invoice rows', after: null});
    f.invoiceRetry.events.click(); await flush();
    assert.strictEqual(f.invoiceBody.innerHTML, 'first invoice rows plus older invoice rows');
    assert.strictEqual(f.invoiceMore.hidden, true);
    assert.strictEqual(f.requests[2].url, '/invoices?after=in_older');
};
if (require.main === module) module.exports().then(() => console.log('Membership billing JavaScript regressions passed.')).catch(error => {console.error(error); process.exitCode = 1;});
