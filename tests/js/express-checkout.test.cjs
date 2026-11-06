const assert = require('node:assert/strict');
const initialize = require('../../resources/js/components/express-checkout');

module.exports = async function () {
    function fixture(mode = 'payment', protocol = 'https:', extra = {}, confirmHandler) {
        const events = {}, confirmations = [], shell = {hidden: true, dataset: {}, querySelector: () => ({})};
        const element = {on(name, fn) { events[name] = fn; }, mount() {}, destroy() { this.destroyed = true; }, update(value) { this.updated = value; }};
        const elements = {create(type, options) { this.options = options; return element; }, update(values) { this.updated = values; }, async submit() { return elements.submission || {}; }};
        const stripe = {elements(options) { elements.configuration = options; return elements; },
            async confirmPayment(options) { confirmations.push(['payment', options]); return {paymentIntent: {status: 'succeeded'}}; },
            async confirmSetup(options) { confirmations.push(['setup', options]); return {setupIntent: {id: 'seti_wallet'}}; }};
        const win = {location: {protocol}, getComputedStyle: () => ({fontFamily: 'Test font'})};
        let failure;
        const api = initialize(win).mount(stripe, {querySelector: () => shell}, {mode, amount: 3300, returnUrl: 'https://shop.example.test/orders/test', error: error => { failure = error; }, ...extra},
            confirmHandler || (payment => payment.confirm('secret_from_server', mode)));
        return {api, events, shell, element, elements, confirmations, failure: () => failure};
    }
    let f = fixture();
    assert.equal(f.elements.configuration.amount, 3300);
    assert.equal(f.elements.options.paymentMethods.applePay, 'auto');
    assert.equal(f.elements.options.paymentMethods.googlePay, 'auto');
    f.events.ready({availablePaymentMethods: {applePay: true, googlePay: false}});
    assert.equal(f.shell.hidden, false);
    let failed;
    await f.events.confirm({paymentFailed(value) { failed = value; }});
    assert.equal(failed, undefined);
    assert.equal(f.confirmations[0][0], 'payment');
    assert.equal(f.confirmations[0][1].clientSecret, 'secret_from_server');
    assert.equal(f.confirmations[0][1].redirect, 'if_required');
    f.elements.submission = {error: {message: 'Wallet was canceled'}};
    await f.events.confirm({paymentFailed(value) { failed = value; }});
    assert.equal(failed.reason, 'fail');
    assert.equal(f.confirmations.length, 1, 'A rejected wallet never confirms an intent');
    assert.equal(f.failure().message, 'Wallet was canceled');
    f.api.destroy();
    assert.equal(f.element.destroyed, true);
    f.events.ready({availablePaymentMethods: {applePay: true}});
    assert.equal(f.shell.hidden, true, 'A late ready event cannot restore a closed wallet');
    f = fixture('setup');
    await f.events.confirm({});
    assert.equal(f.elements.configuration.amount, undefined);
    assert.equal(f.confirmations[0][0], 'setup', 'Memberships confirm their verified SetupIntent through the same wallet component');
    f.events.ready({availablePaymentMethods: null});
    assert.equal(f.shell.hidden, true, 'Unsupported wallets leave the normal card form available');
    assert.equal(fixture('payment', 'http:').api, null, 'HTTP does not display pretend wallet buttons');
    let resolved, rejected = false;
    f = fixture('payment', 'https:', {collection: {emailRequired: true, shippingAddressRequired: true, allowedShippingCountries: ['US']},
        shippingAddressChange: async () => ({amount: 3500, lineItems: [{name: 'Products and delivery', amount: 3500}], shippingRates: [{id: 'standard', amount: 500, displayName: 'Standard'}]})});
    assert.equal(f.elements.options.shippingAddressRequired, true);
    await f.events.shippingaddresschange({resolve(value) { resolved = value; }, reject() { rejected = true; }});
    assert.equal(f.elements.updated.amount, 3500);
    assert.equal(resolved.shippingRates[0].id, 'standard');
    assert.equal(rejected, false);
    f.api.update({amount: 3900, applePay: {test: true}});
    assert.equal(f.elements.updated.amount, 3900);
    assert.deepEqual(f.element.updated, {applePay: {test: true}});
    f = fixture('payment', 'https:', {shippingAddressChange: async () => { throw new Error('No delivery available'); }});
    await f.events.shippingaddresschange({resolve() { assert.fail('Unavailable delivery cannot resolve'); }, reject() { rejected = true; }});
    assert.equal(rejected, true);
    assert.equal(f.failure().message, 'No delivery available');
    let resume;
    f = fixture('payment', 'https:', {}, async payment => {
        await new Promise(resolve => { resume = resolve; });
        await payment.confirm('secret', 'payment', 'https://shop.example.test/orders/actual');
    });
    const pending = f.events.confirm({paymentFailed() {}});
    f.events.cancel(); resume(); await pending;
    assert.equal(f.confirmations.length, 0, 'Canceling while the server prepares payment prevents Stripe confirmation');
    f = fixture('payment', 'https:', {}, payment => payment.confirm('secret', 'payment', 'https://shop.example.test/orders/actual'));
    await f.events.confirm({});
    assert.equal(f.confirmations[0][1].confirmParams.return_url, 'https://shop.example.test/orders/actual');
    console.log('Passed: shared payment/setup wallets, capability detection, failures, disposal and HTTPS requirements.');
};
