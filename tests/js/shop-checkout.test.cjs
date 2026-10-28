const assert = require('node:assert/strict');
const initialize = require('../../resources/js/views/checkout');
const deferred = () => { let resolve, reject; const promise = new Promise((a, b) => {resolve = a; reject = b;}); return {promise, resolve, reject}; };
function element(extra = {}) {
    const events = {}, classes = new Set();
    return Object.assign({
        dataset: {}, hidden: false, disabled: false, value: '', textContent: '', attrs: {}, children: [],
        classList: {add: c => classes.add(c), remove: (...cs) => cs.forEach(c => classes.delete(c))},
        addEventListener(name, fn) { (events[name] ||= []).push(fn); },
        fire(name, event = {}) { return Promise.all((events[name] || []).map(fn => fn(event))); },
        setAttribute(key, value) { this.attrs[key] = value; },
        appendChild(child) { this.children.push(child); child.remove = () => { this.children = this.children.filter(x => x !== child); }; },
    }, extra);
}
function fixture({saved = false, missingStripe = false} = {}) {
    const root = element({URL, AbortController, location: {href: 'https://shop.example.test/escores/book/checkout', reload() { root.reloaded = true; }}});
    const timers = new Map(); let timerId = 0;
    root.setTimeout = fn => { timers.set(++timerId, fn); return timerId; };
    root.clearTimeout = id => timers.delete(id);
    root.expire = () => { const pending = [...timers.values()]; timers.clear(); pending.forEach(fn => fn()); };
    root.getComputedStyle = () => ({fontSize: '16px', fontFamily: 'system-ui', color: '#343a40'});
    const checkout = element({dataset: {orderBase: '1200'}});
    const nodes = Object.fromEntries(['card-status', 'card-retry', 'order-total', 'order-discount-row', 'order-discount'].map(k => [k, element()]));
    const errors = element(), cardField = element();
    const paymentForm = element({id: 'payment-form', dataset: {key: 'pk_test_fixture'}, hidden: saved, submitted: 0});
    const savedForm = element({id: 'returning-payment-form', hidden: !saved, submitted: 0});
    const forms = saved ? [savedForm, paymentForm] : [paymentForm];
    forms.forEach(form => {
        form.button = element(); form.coupon = element(); form.csrf = element({value: 'unchanged-csrf'});
        form.querySelector = selector => selector === '[type="submit"]' ? form.button : selector === '[data-coupon-value]' ? form.coupon : null;
        form.querySelectorAll = () => form.children.filter(child => child.name === 'stripeToken');
        form.submit = () => { form.submitted++; };
    });
    const methods = saved ? [element({checked: true, dataset: {paymentTarget: 'returning-payment-form'}}), element({checked: false, dataset: {paymentTarget: 'payment-form'}})] : [];
    const coupon = element({dataset: {url: '/shop/validate-coupon'}});
    const couponNodes = Object.fromEntries(['apply', 'remove', 'loading', 'feedback'].map(k => [k, element()]));
    const input = element(); coupon.querySelector = selector => selector === 'input' ? input : couponNodes[selector.slice(13, -1)];
    checkout.querySelector = selector => selector === '[data-shop-coupon]' ? coupon : nodes[selector.slice(6, -1)];
    checkout.querySelectorAll = selector => selector === '[data-shop-payment]' ? forms : methods;
    root.document = {
        querySelector: () => checkout,
        getElementById: id => ({'payment-form': paymentForm, 'card-element': cardField, 'card-errors': errors})[id],
        createElement: () => element(),
    };
    const fetches = [];
    root.fetch = (url, options) => { const request = deferred(); fetches.push({url, options, ...request}); return request.promise; };
    const tokenizations = [], cards = [];
    const stripe = {
        elements() {return {create(type, options) { const card = element({type, options, mount() {}, destroy() { this.destroyed = true; }, focus() {this.focused = true;}}); cards.push(card); return card; }};},
        createToken(card) { const request = deferred(); tokenizations.push({card, ...request}); return request.promise; },
    };
    if (!missingStripe) root.Stripe = () => stripe;
    initialize(root);
    return {root, nodes, errors, paymentForm, savedForm, forms, methods, input, couponNodes, fetches, tokenizations, cards,
        async ready() { await cards.at(-1).fire('ready'); await cards.at(-1).fire('change', {complete: true}); },
        async respond(index, data) { fetches[index].resolve({ok: true, json: async () => data}); await Promise.resolve(); await Promise.resolve(); },
    };
}
function submitEvent() { return {prevented: false, preventDefault() {this.prevented = true;}}; }

async function run() {
    let f = fixture({saved: true});
    assert.equal(f.savedForm.button.disabled, false, 'saved cards remain usable while a new card field loads');
    assert.equal(f.paymentForm.button.disabled, true);
    await f.ready();
    f.methods[1].checked = true; f.methods[0].checked = false;
    await f.methods[1].fire('change');
    assert.equal(f.savedForm.hidden, true);
    assert.equal(f.paymentForm.hidden, false);
    assert.equal(f.savedForm.csrf.value, 'unchanged-csrf');
    assert.equal(f.paymentForm.csrf.value, 'unchanged-csrf');
    assert.equal(f.paymentForm.button.disabled, false);

    f.input.value = 'save25';
    let pending = f.couponNodes.apply.fire('click');
    assert.equal(f.paymentForm.button.disabled, true);
    assert.equal(new URL(f.fetches[0].url).searchParams.get('coupon'), 'SAVE25');
    await f.respond(0, {isValid: true, percentOff: 25}); await pending;
    assert.equal(f.nodes['order-total'].textContent, '$9.00');
    assert.equal(f.nodes['order-discount'].textContent, '−$3.00');
    assert.equal(f.savedForm.coupon.value, 'SAVE25');
    assert.equal(f.paymentForm.coupon.value, 'SAVE25');
    assert.equal(f.paymentForm.button.textContent, 'Pay $9.00');
    await f.couponNodes.remove.fire('click');
    assert.equal(f.nodes['order-total'].textContent, '$12.00');
    assert.equal(f.paymentForm.coupon.value, '');

    f.input.value = 'OLD25'; pending = f.couponNodes.apply.fire('click');
    f.input.value = 'NEW50'; await f.input.fire('input');
    const newer = f.couponNodes.apply.fire('click');
    await f.respond(2, {isValid: true, percentOff: 50}); await newer;
    await f.respond(1, {isValid: true, percentOff: 25}); await pending;
    assert.equal(f.nodes['order-total'].textContent, '$6.00', 'an older response must not replace the accepted code or total');
    assert.equal(f.paymentForm.coupon.value, 'NEW50');
    f.input.value = 'INVALID'; await f.input.fire('input'); pending = f.couponNodes.apply.fire('click');
    await f.respond(3, {isValid: false, message: 'Code expired'}); await pending;
    assert.equal(f.nodes['order-total'].textContent, '$12.00');
    assert.equal(f.couponNodes.feedback.textContent, 'Code expired');
    assert.equal(f.paymentForm.button.disabled, false);
    f.input.value = 'BROKEN'; pending = f.couponNodes.apply.fire('click');
    f.fetches[4].reject(new Error('Offline')); await pending;
    assert.equal(f.paymentForm.button.disabled, false);
    assert.match(f.couponNodes.feedback.textContent, /couldn’t check/);

    pending = f.paymentForm.fire('submit', submitEvent());
    assert.equal(f.paymentForm.button.disabled, true);
    f.tokenizations[0].reject(new Error('Card verification failed')); await pending;
    assert.equal(f.paymentForm.submitted, 0);
    assert.equal(f.paymentForm.button.disabled, false);
    assert.equal(f.errors.textContent, 'Card verification failed');
    pending = f.paymentForm.fire('submit', submitEvent());
    const duplicate = submitEvent(); await f.paymentForm.fire('submit', duplicate);
    assert.equal(duplicate.prevented, true);
    assert.equal(f.tokenizations.length, 2);
    f.tokenizations[1].resolve({token: {id: 'tok_fixture'}}); await pending;
    assert.equal(f.paymentForm.submitted, 1);
    assert.equal(f.paymentForm.children[0].name, 'stripeToken');
    assert.equal(f.paymentForm.children[0].value, 'tok_fixture');
    assert.equal(f.paymentForm.csrf.value, 'unchanged-csrf');

    f = fixture(); await f.ready();
    pending = f.paymentForm.fire('submit', submitEvent());
    await f.root.fire('pagehide');
    f.tokenizations[0].resolve({token: {id: 'tok_obsolete'}}); await pending;
    assert.equal(f.paymentForm.submitted, 0, 'late tokenization after leaving must never submit a purchase');
    await f.root.fire('pageshow', {persisted: true});
    assert.equal(f.paymentForm.button.disabled, false);
    pending = f.paymentForm.fire('submit', submitEvent()); f.root.expire();
    assert.match(f.errors.textContent, /couldn’t verify/);
    f.tokenizations[1].resolve({token: {id: 'tok_timed_out'}}); await pending;
    assert.equal(f.paymentForm.submitted, 0);

    f = fixture({saved: true});
    const savedSubmit = submitEvent(); await f.savedForm.fire('submit', savedSubmit);
    assert.equal(savedSubmit.prevented, false, 'saved-card checkout must keep its native authenticated POST');
    assert.equal(f.tokenizations.length, 0);
    assert.equal(f.savedForm.button.disabled, true);

    f = fixture({missingStripe: true});
    assert.match(f.errors.textContent, /couldn’t load/);
    assert.equal(f.nodes['card-retry'].hidden, false);
    await f.nodes['card-retry'].fire('click');
    assert.equal(f.root.reloaded, true);
    f = fixture(); f.root.expire();
    assert.equal(f.paymentForm.button.disabled, true);
    await f.nodes['card-retry'].fire('click');
    await f.cards[0].fire('ready');
    assert.equal(f.paymentForm.button.disabled, true);
    await f.ready();
    assert.equal(f.paymentForm.button.disabled, false);
    console.log('Passed: shop checkout card readiness, saved/new methods, CSRF, coupon totals/races/removal/failures, token retries, duplicate submits, timeouts and back navigation.');
}
if (require.main === module) run().catch(error => { console.error(error); process.exitCode = 1; });
module.exports = run;
