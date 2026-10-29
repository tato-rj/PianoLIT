const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

module.exports = async function () {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/views/merchandise-checkout.js'), 'utf8');
    const settle = async () => { for (let n = 0; n < 12; n++) await Promise.resolve(); };
    function fixture({digital = false, prefilled = false, mode = 'demo'} = {}) {
        let now = Date.parse('2026-10-28T16:00:00Z'), sequence = 0;
        const timers = new Map(), requests = [], redirects = [], confirmations = [], documentEvents = {};
        function node(extra = {}) {
            return Object.assign({events: {}, dataset: {}, textContent: '', hidden: false, disabled: false, value: '',
                addEventListener(name, fn) { this.events[name] = fn; }, setAttribute(name, value) { this[name] = value; },
                dispatchEvent() {}}, extra);
        }
        function field(name, value, required = false, select = false) {
            const control = node({name, value, required, select, options: [], add(option) {
                this.options.push(option); if (this.options.length === 1) this.value = option.value;
            }, replaceChildren() { this.options = []; this.value = ''; }});
            return control;
        }
        const inputs = {
            _token: field('_token', 'review-csrf'), kind: field('kind', digital ? 'digital' : 'merchandise'),
            email: field('email', 'customer@example.test', true), name: field('name', 'Jamie', true),
            address1: field('address1', prefilled ? '123 Test Street' : '', true), address2: field('address2', ''),
            country_code: field('country_code', prefilled ? 'GB' : '', true, true),
            state_code: field('state_code', ''), city: field('city', prefilled ? 'London' : '', true), zip: field('zip', prefilled ? 'SW1A 1AA' : '')
        };
        if (digital) {
            inputs.product_type = field('product_type', 'ebooks'); inputs.product_id = field('product_id', '1'); inputs.coupon = field('coupon', '');
        } else { inputs.state_code.select = true; inputs.shipping_id = field('shipping_id', '', false, true); }
        const country = inputs.country_code;
        country.options = ['', 'US', 'GB', 'CH'].map(value => ({value}));
        const retry = node({hidden: true}), status = node(), error = node(), pay = node(), label = node(), orderLink = node({hidden: true});
        const stateContainer = node(), shippingOptions = node({hidden: true});
        const totals = Object.fromEntries(['total', 'shipping', 'tax', 'discount-line', 'discount', 'subtotal'].map(key => [key, node()]));
        const shipping = node({querySelector(selector) {
            if (selector === '[data-delivery-retry]') return retry;
            if (selector === '[data-delivery-status]') return status;
            if (selector === '[data-shipping-options]') return shippingOptions;
            return inputs[selector.match(/name="([^"]+)"/)?.[1]];
        }, querySelectorAll(selector) {
            return Object.values(inputs).filter(input => selector !== '[required]' || input.required);
        }, checkValidity() {
            return Object.values(inputs).every(input => input.disabled || !input.required || input.value !== '')
                && /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(inputs.email.value);
        }});
        const nativeCardInputs = [field('_token', 'review-csrf'), field('cardholder', 'Jamie', true), field('postal_code', '')];
        const stripeNodes = ['cardNumber', 'cardExpiry', 'cardCvc'].map(type => node({dataset: {stripeField: type}}));
        const form = node({reportValidity: () => true, querySelector(selector) {
            if (selector === '[type="submit"]') return pay;
            if (selector === '[data-pay-label]') return label;
            if (selector === '[data-checkout-error]') return error;
            if (selector === '[data-checkout-order-link]') return orderLink;
            if (selector === '[data-card-status]') return node();
            return nativeCardInputs.find(input => selector === '[name="' + input.name + '"]') || null;
        }, querySelectorAll(selector) { return selector === '[data-stripe-field]' ? stripeNodes : selector === 'input' ? nativeCardInputs : []; }});
        const surface = node({querySelector: selector => totals[selector.match(/data-checkout-([^\]]+)/)?.[1]]});
        const root = node({dataset: {mode, kind: digital ? 'digital' : 'merchandise', key: 'pk_test_review', quoteUrl: '/quote', prepareUrl: '/prepare', completeUrl: '/complete',
            countries: JSON.stringify([{code: 'US', states: [{code: 'NY', name: 'New York'}, {code: 'CA', name: 'California'}]}, {code: 'GB', states: null}, {code: 'CH', states: null}])},
        closest: selector => selector === '[data-checkout-surface]' ? surface : node(),
        querySelector(selector) {
            return {'[data-shipping-form]': shipping, '[data-merchandise-payment]': form, '[data-state-field]': stateContainer}[selector];
        }});
        function timeout(fn, delay) { const id = ++sequence; timers.set(id, {fn, at: now + delay}); return id; }
        const window = {setTimeout: timeout, clearTimeout: id => timers.delete(id), AbortController,
            CustomEvent: class { constructor(type) { this.type = type; } }, Option: class { constructor(text, value) { this.text = text; this.value = value; } },
            FormData: class { constructor() { return Object.entries(inputs).filter(([, input]) => !input.disabled && (!input.select || input.options.length)).map(([name, input]) => [name, input.value]); } },
            location: {origin: 'http://shop.example.test', assign: url => redirects.push(url)}, getComputedStyle: () => ({}),
            Stripe: () => ({elements: () => ({create: () => {
                const events = {};
                return {on: (type, fn) => { events[type] = fn; }, mount() { events.ready(); events.change({complete: true}); }, update() {}, destroy() {}, focus() {}};
            }}), confirmCardPayment: async secret => { confirmations.push(secret); return {}; }}),
            fetch: (url, options) => new Promise((resolve, reject) => {
                requests.push({url, options, data: JSON.parse(options.body), resolve, reject});
                options.signal.addEventListener('abort', () => reject(new Error('Aborted')));
            })};
        class ClockDate extends Date { static now() { return now; } }
        vm.runInNewContext(source, {window, Date: ClockDate, document: {addEventListener(name, fn) { documentEvents[name] = fn; }}});
        window.PianoLITMerchandiseCheckout.mount(root);
        function edit(name, value, type = 'input') { inputs[name].value = value; shipping.events[type]({target: inputs[name]}); }
        function fill() {
            edit('address1', '123 Test Street'); edit('country_code', 'US'); edit('state_code', 'NY'); edit('city', 'New York'); edit('zip', '10001');
        }
        async function tick(milliseconds) {
            const end = now + milliseconds;
            for (;;) {
                const next = [...timers.entries()].filter(([, timer]) => timer.at <= end).sort((a, b) => a[1].at - b[1].at)[0];
                if (!next) break;
                now = next[1].at; timers.delete(next[0]); next[1].fn(); await settle();
            }
            now = end; await settle();
        }
        async function respond(index, data = {}, ok = true) {
            const payload = {order_id: 'review-order', subtotal: 2800, shipping: 500, tax: 0, total: 3300, discount: 0,
                shipping_id: 'STANDARD', expires_at: new Date(now + 900000).toISOString(),
                rates: [{id: 'STANDARD', name: 'Standard', amount: 500}, {id: 'EXPRESS', name: 'Express', amount: 1200}], ...data};
            requests[index].resolve({ok, json: async () => payload}); await settle();
        }
        function destroy() { documentEvents['checkout:dispose']({target: {querySelector: () => root}}); }
        const submit = () => form.events.submit({preventDefault() {}});
        return {inputs, shipping, form, totals, retry, pay, error, status, requests, redirects, confirmations, orderLink, edit, fill, tick, respond, destroy, submit};
    }

    let f = fixture();
    await f.tick(5000); assert.equal(f.requests.length, 0, 'An incomplete address does not call the server');
    f.fill(); await f.tick(700); f.edit('city', 'New Yor'); await f.tick(700); f.edit('city', 'New York');
    await f.tick(999); assert.equal(f.requests.length, 0, 'Typing and moving between fields restart the quiet period');
    await f.tick(1); assert.equal(f.requests.length, 1); assert.equal(f.requests[0].options.headers['X-CSRF-TOKEN'], 'review-csrf');
    await f.respond(0); assert.equal(f.totals.total.textContent, '$33.00'); assert.equal(f.pay.disabled, false);
    f.shipping.events.change({}); f.shipping.events.input({}); f.edit('address1', ' 123 Test Street ');
    await f.tick(2000); assert.equal(f.requests.length, 1, 'Identical change/input/blur values and harmless whitespace do not quote again');

    f = fixture(); f.fill(); await f.tick(1000);
    f.edit('city', 'Boston'); await f.tick(2000);
    assert.equal(f.requests.length, 1, 'There is only one provider calculation in flight');
    await f.respond(0, {total: 9999}); assert.equal(f.totals.total.textContent, '—', 'A stale address total is never displayed');
    await f.tick(0); assert.equal(f.requests.length, 2); assert.equal(f.requests[1].data.city, 'Boston');
    await f.respond(1); await f.tick(2000); assert.equal(f.requests.length, 2);

    f = fixture(); f.fill(); await f.tick(1000); f.edit('city', 'Boston'); await f.tick(100); await f.respond(0);
    await f.tick(899); assert.equal(f.requests.length, 1, 'Finishing an obsolete request does not bypass the latest typing pause');
    await f.tick(1); assert.equal(f.requests.length, 2);

    f = fixture(); f.fill(); f.edit('name', '   '); await f.tick(2000); assert.equal(f.requests.length, 0);
    f.edit('name', 'Jamie'); f.edit('email', 'invalid'); await f.tick(2000); assert.equal(f.requests.length, 0);
    f.edit('email', 'customer@example.test'); f.shipping.events.compositionstart({}); f.edit('city', '東京'); await f.tick(2000);
    assert.equal(f.requests.length, 0, 'IME composition must finish before quoting');
    f.shipping.events.compositionend({}); await f.tick(1000); assert.equal(f.requests.length, 1);

    f = fixture({prefilled: true}); await f.tick(1000); assert.equal(f.requests.length, 1, 'Complete prefilled addresses calculate automatically');
    await f.respond(0); f.edit('shipping_id', 'EXPRESS'); f.shipping.events.change({}); await f.tick(1000);
    assert.equal(f.requests.length, 2); assert.equal(f.requests[1].data.shipping_id, 'EXPRESS'); await f.respond(1, {shipping_id: 'EXPRESS', shipping: 1200, total: 4000});
    f.edit('city', 'New York'); f.edit('country_code', 'US'); await f.tick(2000);
    assert.equal(f.requests.length, 2, 'A changed country requires an explicit valid state');
    f.edit('state_code', 'NY'); await f.tick(1000); assert.equal(f.requests.length, 3);
    assert.equal(f.requests[2].data.shipping_id, undefined, 'A changed destination discards obsolete shipping choices');

    f = fixture({digital: true}); f.fill(); await f.tick(1000); await f.respond(0, {shipping: 0, total: 2800});
    f.edit('coupon', 'HAL'); await f.tick(400); f.edit('coupon', 'HALF'); await f.tick(1000);
    assert.equal(f.requests.length, 2); assert.equal(f.requests[1].data.coupon, 'HALF', 'Digital billing/coupon totals follow the same settled-input behavior');

    f = fixture(); f.fill(); await f.tick(500); f.destroy(); await f.tick(1000); assert.equal(f.requests.length, 0, 'Closing checkout cancels queued calculations');
    f = fixture(); f.fill(); await f.tick(1000); f.destroy(); await f.respond(0); await f.tick(2000);
    assert.equal(f.requests[0].options.signal.aborted, true); assert.equal(f.totals.total.textContent, '—'); assert.equal(f.requests.length, 1);

    f = fixture(); f.fill(); await f.tick(1000); await f.respond(0, {message: 'Address unavailable'}, false);
    assert.equal(f.retry.hidden, false); assert.equal(f.pay.disabled, true); await f.tick(5000); assert.equal(f.requests.length, 1, 'Provider failures do not create a retry loop');
    f.retry.events.click({}); await f.tick(0); assert.equal(f.requests.length, 2); await f.respond(1); assert.equal(f.retry.hidden, true);

    f = fixture(); f.fill(); await f.tick(1000); await f.respond(0, {expires_at: '2026-10-28T15:00:00Z'}); f.submit(); await f.tick(0);
    assert.equal(f.requests[1].url, '/quote', 'An expired total is refreshed before any payment preparation');

    f = fixture({mode: 'sandbox'}); f.fill(); await f.tick(1000); await f.respond(0); f.submit(); await settle();
    assert.equal(f.requests[1].url, '/prepare'); await f.respond(1, {refresh_quote: true, message: 'Review the updated quote'}, false); await f.tick(0);
    assert.equal(f.requests[2].url, '/quote'); assert.equal(f.confirmations.length, 0, 'A changed server quote never confirms a payment');

    f = fixture(); f.fill(); await f.tick(1000); await f.respond(0); f.submit(); await settle(); await f.respond(1, {demo: true});
    assert.equal(f.requests[2].url, '/complete'); await f.respond(2, {message: 'Confirmation unavailable'}, false); await f.tick(5000);
    assert.equal(f.requests.length, 3, 'An ambiguous payment completion never silently starts a replacement quote/payment');
    assert.equal(f.orderLink.hidden, false);
    console.log('Passed: automatic settled-address/coupon quotes, validation, deduplication, serialized/stale requests, country/rate changes, composition, disposal, retry and payment safety.');
};
