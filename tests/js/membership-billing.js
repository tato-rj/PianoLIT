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
function fixture(purpose = 'purchase', invoiceMode = false, split = false) {
    const requests = [], redirects = [], confirmations = [], replies = [];
    const button = element(), errors = element(), coupon = element({'data-coupon-url': '/coupon'}), feedback = element(); coupon.value = '';
    const total = element({'data-base-price': '799'}), mount = element();
    const due = element(), renewal = element(), consent = element({'data-trial-days': '0', 'data-interval': 'month'}), charge = element();
    const label = element(), status = element(), apply = element(), name = element(), postal = element();
    label.textContent = 'Subscribe for $7.99'; name.value = 'Review Pianist'; postal.value = '10001';
    const mounts = ['cardNumber', 'cardExpiry', 'cardCvc'].map(type => element({'data-stripe-field': type}));
    const form = element({'data-operation': 'operation-review', 'data-purpose': purpose, 'data-key': 'pk_review', 'data-prepare': '/prepare', 'data-complete': '/complete'});
    form.action = '/submit';
    form.hasAttribute = attribute => split && attribute === 'data-split-card';
    form.querySelectorAll = () => mounts;
    form.querySelector = selector => ({'button[type="submit"]': button, '#card-errors': errors, '#card-element': mount,
        'input[name="coupon"]': purpose === 'purchase' ? coupon : null, '[data-coupon-feedback]': feedback,
        '[data-membership-consent]': split ? consent : null, '[data-membership-charge]': split ? charge : null,
        '[data-submit-label]': split ? label : null, '[data-card-status]': split ? status : null,
        '[data-apply-coupon]': split ? apply : null, '[name="cardholder"]': split ? name : null,
        '[name="postal_code"]': split ? postal : null}[selector]);
    const card = {mount() {}, on(name, callback) { this.change = callback; }};
    const hosted = {};
    const stripe = {elements() {return {create(type, options) {
        if (!split) return card;
        const field = {events: {}, options, mount() {}, update(options) {this.disabled = options.disabled;}, on(event, callback) {this.events[event] = callback;}};
        hosted[type] = field; return field;
    }};},
        async confirmCardSetup(secret, details) { confirmations.push(['setup', secret, details]); return stripe.setupResult || {setupIntent: {id: 'seti_review'}}; },
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
    const document = {querySelector(selector) {return {'meta[name="csrf-token"]': {content: 'csrf-review'}, '[data-membership-payment]': purpose === 'none' ? null : form, '[data-membership-total]': total, '[data-membership-due]': split ? due : null, '[data-membership-renewal]': split ? renewal : null, '[data-membership-invoices]': invoiceMode ? invoiceRoot : null}[selector] || null;}, querySelectorAll() {return [];}};
    vm.runInNewContext(source, {document, DOMParser, Stripe: () => stripe, fetch, window: {getComputedStyle() {return {color: '#273244', fontFamily: 'system-ui', fontSize: '16px', lineHeight: '24px', getPropertyValue() {return '#697386';}};}, jQuery: invoiceMode ? jquery : null, location: {hash: '', assign(url) { redirects.push(url); }, reload() { redirects.push('reload'); }}}});
    return {form, button, errors, coupon, invoiceStatus, invoiceMore, invoiceRetry, invoiceBody, openInvoices() {shown({target:{getAttribute() {return '#invoices-tab';}}});}, feedback, total, stripe, card, requests, redirects, confirmations, replies,
        due, renewal, consent, charge, label, status, name, postal, hosted,
        ready() {Object.values(hosted).forEach(field => field.events.ready());},
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

    f = fixture('purchase', false, true);
    assert(f.button.disabled, 'Hosted fields must be ready before checkout is enabled.');
    await f.submit(); assert.strictEqual(f.requests.length, 0, 'Submitting before hosted fields are ready cannot prepare a purchase.');
    f.ready(); assert.strictEqual(f.button.disabled, false);
    assert.strictEqual(f.hosted.cardNumber.options.style.base.fontSize, '16px');
    assert.strictEqual(f.hosted.cardNumber.options.showIcon, true);
    f.hosted.cardNumber.events.change({complete: false, error: {message: 'Card number is incomplete'}});
    f.hosted.cardExpiry.events.change({complete: true});
    assert.strictEqual(f.errors.textContent, 'Card number is incomplete', 'A valid expiry cannot hide a card-number error.');
    f.hosted.cardNumber.events.change({complete: true});
    f.hosted.cardCvc.events.change({complete: true});
    assert.strictEqual(f.errors.textContent, '');
    f.replies.push({operation_id: 'op', setup_secret: 'secret'}, {complete: true}, {redirect: '/welcome'});
    await f.submit();
    assert.strictEqual(f.confirmations[0][2].payment_method.card, f.hosted.cardNumber);
    assert.strictEqual(f.confirmations[0][2].payment_method.billing_details.name, 'Review Pianist');
    assert.strictEqual(f.confirmations[0][2].payment_method.billing_details.address.postal_code, '10001');
    assert.strictEqual(f.name.disabled, false); assert.strictEqual(f.hosted.cardNumber.disabled, false);
    assert.deepStrictEqual(f.redirects, ['/welcome']);

    f = fixture('purchase', false, true); f.ready();
    f.coupon.value = 'SAVE'; f.coupon.events.input();
    f.replies.push({message: 'Coupon applied', amount: 399}); await f.coupon.events.blur();
    assert.strictEqual(f.due.textContent, '$3.99');
    assert.strictEqual(f.label.textContent, 'Subscribe for $3.99');
    assert(f.charge.textContent.includes('$3.99'));
    assert(f.renewal.textContent.includes('Later renewals follow the coupon terms.'));
    f.coupon.value = ''; f.coupon.events.input();
    assert.strictEqual(f.due.textContent, '$7.99'); assert.strictEqual(f.label.textContent, 'Subscribe for $7.99');

    f = fixture('purchase', false, true); f.ready();
    f.coupon.value = 'INVALID'; f.coupon.events.input(); f.replies.push({ok: false, message: 'Invalid coupon'});
    await f.submit(); assert.deepStrictEqual(f.requests.map(r => r.url), ['/coupon']);
    assert(f.errors.textContent.includes('remove the code'));
    assert.strictEqual(f.button.disabled, false);

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
