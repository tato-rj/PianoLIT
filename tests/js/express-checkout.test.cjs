const assert = require('node:assert/strict');
const initialize = require('../../resources/js/components/express-checkout');

module.exports = async function () {
    function fixture(mode = 'payment', protocol = 'https:') {
        const events = {}, confirmations = [], shell = {hidden: true, dataset: {}, querySelector: () => ({})};
        const element = {on(name, fn) { events[name] = fn; }, mount() {}, destroy() { this.destroyed = true; }, update(value) { this.updated = value; }};
        const elements = {create(type, options) { this.options = options; return element; }, async submit() { return elements.submission || {}; }};
        const stripe = {elements(options) { elements.configuration = options; return elements; },
            async confirmPayment(options) { confirmations.push(['payment', options]); return {paymentIntent: {status: 'succeeded'}}; },
            async confirmSetup(options) { confirmations.push(['setup', options]); return {setupIntent: {id: 'seti_wallet'}}; }};
        const win = {location: {protocol}, getComputedStyle: () => ({fontFamily: 'Test font'})};
        let failure;
        const api = initialize(win).mount(stripe, {querySelector: () => shell}, {mode, amount: 3300, returnUrl: 'https://shop.example.test/orders/test', error: error => { failure = error; }},
            payment => payment.confirm('secret_from_server', mode));
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
    console.log('Passed: shared payment/setup wallets, capability detection, failures, disposal and HTTPS requirements.');
};
