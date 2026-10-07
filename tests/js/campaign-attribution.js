const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function fixture(consent = '', token = 'opaque-page-token', succeeds = true) {
    const attributes = {'data-consent': consent, 'data-token': token, 'data-url': '/attribution/consent', 'data-csrf': 'csrf-fixture'};
    function element(choice) {
        return {hidden: true, disabled: false, textContent: '', events: {},
            addEventListener(name, fn) { this.events[name] = fn; },
            getAttribute() { return choice; }, setAttribute(name, value) { this[name] = value; }};
    }
    const panel = element(), settings = element(), status = element();
    const allow = element('granted'), deny = element('denied');
    const root = {
        getAttribute(name) { return attributes[name]; }, setAttribute(name, value) { attributes[name] = value; },
        querySelector(name) { return {'#campaign-measurement-choice': panel, '[data-campaign-settings]': settings, '[data-campaign-status]': status}[name]; },
        querySelectorAll() { return [allow, deny]; }
    };
    const requests = [];
    const fetch = (url, options) => {
        requests.push({url, options});
        return Promise.resolve({ok: succeeds, json: () => Promise.resolve({choice: JSON.parse(options.body).choice})});
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/campaign-attribution.js'), 'utf8'), {
        document: {querySelector: () => root}, fetch
    });
    return {requests, attributes, panel, settings, status, allow, deny};
}

module.exports = async function () {
    const flush = () => new Promise(resolve => setImmediate(resolve));
    let f = fixture();
    assert.strictEqual(f.requests.length, 0, 'No acknowledgement before consent.');
    f.allow.events.click();
    f.allow.events.click();
    assert.strictEqual(f.requests.length, 1, 'Busy controls prevent duplicate requests.');
    assert.strictEqual(f.allow.disabled, true);
    assert.deepStrictEqual(JSON.parse(f.requests[0].options.body), {choice: 'granted', token: 'opaque-page-token'});
    assert.strictEqual(f.requests[0].options.credentials, 'same-origin');
    assert.strictEqual(f.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-fixture');
    await flush();
    assert.strictEqual(f.panel.hidden, true);
    assert.strictEqual(f.attributes['data-consent'], 'granted');
    assert.strictEqual(f.allow.disabled, false);
    f.settings.events.click();
    assert.strictEqual(f.panel.hidden, false);
    assert.strictEqual(f.settings['aria-expanded'], 'true');
    f.deny.events.click();
    await flush();
    assert.deepStrictEqual(JSON.parse(f.requests[1].options.body), {choice: 'denied', token: null});
    assert.strictEqual(f.attributes['data-consent'], 'denied');

    f = fixture('granted');
    assert.strictEqual(f.requests.length, 1, 'Previous consent acknowledges a tagged rendered page.');
    await flush();
    f = fixture('denied');
    assert.strictEqual(f.requests.length, 0);
    f = fixture('granted', '');
    assert.strictEqual(f.requests.length, 0, 'Ordinary navigation sends no arrival.');
    f = fixture('', 'opaque-page-token', false);
    f.allow.events.click();
    await flush();
    assert.strictEqual(f.panel.hidden, false);
    assert.strictEqual(f.allow.disabled, false);
    assert(f.status.textContent.includes('could not be saved'));
    assert.strictEqual(f.attributes['data-consent'], '', 'A failed save never reports consent as saved.');
};

if (require.main === module) module.exports().then(() => console.log('Campaign attribution JavaScript regressions passed.')).catch(error => { console.error(error); process.exitCode = 1; });
