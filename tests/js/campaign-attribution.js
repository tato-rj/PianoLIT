const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function fixture(consent = '', token = 'opaque-page-token', rejects = false, present = true) {
    const attributes = {'data-consent': consent, 'data-token': token, 'data-url': '/attribution/consent', 'data-csrf': 'csrf-fixture'};
    const root = {getAttribute(name) { return attributes[name]; }};
    const requests = [];
    const fetch = (url, options) => {
        requests.push({url, options});
        return rejects ? Promise.reject(new Error('Network unavailable')) : Promise.resolve({ok: true});
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/campaign-attribution.js'), 'utf8'), {
        document: {querySelector: () => present ? root : null}, fetch
    });
    return {requests, attributes};
}

module.exports = async function () {
    const flush = () => new Promise(resolve => setImmediate(resolve));
    for (const consent of ['', 'denied']) {
        assert.strictEqual(fixture(consent).requests.length, 0, 'No conversion acknowledgement without an existing opt-in.');
    }
    assert.strictEqual(fixture('granted', '').requests.length, 0, 'Ordinary navigation sends no acknowledgement.');
    assert.strictEqual(fixture('granted', 'opaque-page-token', false, false).requests.length, 0, 'Pages without campaign metadata do nothing.');
    const f = fixture('granted');
    assert.strictEqual(f.requests.length, 1, 'A previously granted choice still acknowledges a tagged landing without visible controls.');
    assert.strictEqual(f.requests[0].url, '/attribution/consent');
    assert.deepStrictEqual(JSON.parse(f.requests[0].options.body), {choice: 'granted', token: 'opaque-page-token'});
    assert.strictEqual(f.requests[0].options.credentials, 'same-origin');
    assert.strictEqual(f.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-fixture');
    const callbacks = {}, requests = [], timers = [];
    const attributes = {'data-consent': 'granted', 'data-token': '', 'data-url': '/attribution/consent', 'data-csrf': 'csrf-fixture', 'data-measurement-id': 'G-REVIEW'};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/campaign-attribution.js'), 'utf8'), {
        document: {querySelector: () => ({getAttribute: name => attributes[name]})},
        window: {gtag: (...args) => { assert.strictEqual(args[0], 'get'); assert.strictEqual(args[1], 'G-REVIEW'); callbacks[args[2]] = args[3]; }},
        fetch: (url, options) => { requests.push({url, options}); return Promise.resolve({ok: true}); },
        setTimeout: callback => { timers.push(callback); return 1; }, clearTimeout: () => {}
    });
    assert.strictEqual(requests.length, 0, 'Wait for both supported Google ID callbacks.');
    callbacks.session_id('1793124000');
    callbacks.client_id('123.456');
    assert.deepStrictEqual(JSON.parse(requests[0].options.body), {choice: 'granted', token: null, client_id: '123.456', session_id: '1793124000'});
    timers[0](); callbacks.client_id('999.111');
    assert.strictEqual(requests.length, 1, 'Callbacks and timeout cannot duplicate acknowledgement or emit conversions.');
    await flush();
    const failed = fixture('granted', 'opaque-page-token', true);
    await flush();
    assert.strictEqual(failed.requests.length, 1, 'A failed optional acknowledgement does not loop or produce an unhandled rejection.');
    assert.strictEqual(failed.attributes['data-consent'], 'granted', 'Network errors do not change a saved choice.');
};

if (require.main === module) module.exports().then(() => console.log('Campaign attribution JavaScript regressions passed.')).catch(error => { console.error(error); process.exitCode = 1; });
