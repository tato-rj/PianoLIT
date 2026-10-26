const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

async function main() {
    function element() {
        const classes = new Set();
        return {checked: false, disabled: false, hidden: false, events: {}, attributes: {}, textContent: '',
            addEventListener(name, handler) { this.events[name] = handler; },
            setAttribute(name, value) { this.attributes[name] = value; },
            removeAttribute(name) { delete this.attributes[name]; },
            classList: {add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)}};
    }
    const input = element(), save = element(), status = element(), all = element(), autosave = element(), form = element();
    form.action = 'https://pianolit.test/profile/email-lists/7';
    form.querySelector = selector => selector === '[role="switch"]' ? input : save;
    const area = {querySelectorAll: () => [form], querySelector: selector => ({'[data-email-status]': status, '[data-unsubscribe-all]': all, '[data-profile-autosave]': autosave}[selector])};
    const requests = [];
    const fetch = (url, options) => new Promise((resolve, reject) => requests.push({url, options, resolve, reject}));
    class Data {constructor() { this.values = {_token: 'session-token', _method: 'PATCH'}; } set(name, value) { this.values[name] = value; }}
    vm.runInNewContext(fs.readFileSync(require.resolve('../../resources/js/views/profile.js'), 'utf8'), {
        document: {querySelector: selector => selector === '[data-profile-emails]' ? area : null}, window: {fetch, FormData: Data, location: {search: '', hash: ''}},
        fetch, FormData: Data, URLSearchParams
    });
    assert.strictEqual(save.hidden, true);
    assert.strictEqual(all.disabled, true);
    input.checked = true;
    const event = {preventDefault() {}};
    const first = input.events.change(event);
    assert.strictEqual(input.disabled, true);
    assert.strictEqual(all.disabled, true, 'Bulk unsubscribe stays disabled until pending saves finish.');
    assert.strictEqual(requests[0].options.body.values.enabled, '1');
    assert.strictEqual(requests[0].options.body.values._method, 'PATCH');
    assert.strictEqual(requests[0].options.credentials, 'same-origin');
    await input.events.change(event);
    assert.strictEqual(requests.length, 1, 'Repeated events cannot send concurrent saves for one switch.');
    requests[0].resolve({ok: true, json: async () => ({subscribed: true})}); await first;
    assert.strictEqual(input.checked, true); assert.strictEqual(input.disabled, false); assert.strictEqual(all.disabled, false);
    input.checked = false;
    const second = input.events.change(event);
    requests[1].resolve({ok: false}); await second;
    assert.strictEqual(input.checked, true, 'HTTP failure restores the last confirmed state.');
    assert.strictEqual(input.disabled, false); assert(status.classList.contains('text-danger'));
    input.checked = false;
    const third = input.events.change(event);
    requests[2].reject(new Error('network offline')); await third;
    assert.strictEqual(input.checked, true, 'Network failure also restores the confirmed state.');
    input.checked = false;
    const fourth = input.events.change(event);
    requests[3].resolve({ok: true, json: async () => ({subscribed: false})}); await fourth;
    assert.strictEqual(input.checked, false); assert.strictEqual(all.disabled, true);
    console.log('Passed: profile email preferences, explicit state, retry handling, bulk-action locking and failure recovery.');
}
module.exports = main;
if (require.main === module) main().catch(error => { console.error(error); process.exitCode = 1; });
