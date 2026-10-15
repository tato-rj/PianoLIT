const assert = require('assert');
const initialize = require('../../resources/js/views/admin-user-profile');

module.exports = function () {
    function element(attributes = {}) {
        const classes = new Set();
        return {attributes, events: {}, disabled: false, checked: false, value: '', textContent: '',
            getAttribute(name) { return this.attributes[name]; },
            addEventListener(name, callback) { this.events[name] = callback; },
            classList: {add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)}
        };
    }
    const toggle = element({'data-url': '/users/7/super-status'});
    const feedback = element();
    const status = element({'data-base-label': 'Ended or inactive'});
    const access = element({'data-billing-access': '0'});
    const form = element({'data-confirm-email': 'target@example.test'});
    const input = element();
    const submit = element({'data-billing-blocked': '0'});
    form.querySelector = selector => selector === '[name="confirmation_email"]' ? input : submit;
    const open = element();
    let modalOpened = 0;
    const page = {
        querySelector: selector => ({'[data-user-super-status]': toggle, '[data-super-status-message]': feedback,
            '[data-profile-status]': status, '[data-profile-access]': access, '[data-removal-modal][data-open-on-load]': open}[selector]),
        querySelectorAll: () => [form]
    };
    const requests = [];
    const $ = {ajax(options) {
        const request = {options, done(fn) { this.success = fn; return this; }, fail(fn) { this.failure = fn; return this; }, always(fn) { this.finish = fn; return this; }};
        requests.push(request); return request;
    }};
    initialize({document: {querySelector: () => page}, bootstrap: {Modal: {getOrCreateInstance: target => ({show() { assert.strictEqual(target, open); modalOpened++; }})}}}, $);
    assert.strictEqual(modalOpened, 1, 'Server validation reopens the matching dialog.');
    assert.strictEqual(submit.disabled, true);
    input.value = 'other@example.test'; input.events.input(); assert.strictEqual(submit.disabled, true);
    input.value = ' TARGET@example.test '; input.events.input(); assert.strictEqual(submit.disabled, false);
    submit.attributes['data-billing-blocked'] = '1'; input.events.input(); assert.strictEqual(submit.disabled, true);
    let prevented = 0;
    form.events.submit({preventDefault() { prevented++; }}); assert.strictEqual(prevented, 1);
    submit.attributes['data-billing-blocked'] = '0'; input.events.input();
    form.events.submit({preventDefault() { prevented++; }});
    assert.strictEqual(submit.disabled, true); assert.strictEqual(submit.textContent, 'Removing account…');
    form.events.submit({preventDefault() { prevented++; }}); assert.strictEqual(prevented, 2, 'Duplicate submissions are blocked.');

    toggle.checked = true; toggle.events.change();
    assert.strictEqual(requests.length, 1); assert.strictEqual(toggle.disabled, true);
    assert.strictEqual(requests[0].options.type, 'PATCH'); assert.strictEqual(requests[0].options.url, '/users/7/super-status');
    requests[0].success({status: 'Updated'}); requests[0].finish();
    assert.strictEqual(status.textContent, 'Super user'); assert.strictEqual(access.textContent, 'Full media access'); assert.strictEqual(toggle.disabled, false);
    toggle.checked = false; toggle.events.change(); requests[1].failure(); requests[1].finish();
    assert.strictEqual(toggle.checked, true, 'A failed save restores the previous value.');
    assert.strictEqual(status.textContent, 'Super user'); assert.strictEqual(toggle.disabled, false); assert(feedback.classList.contains('is-error'));
    toggle.checked = false; toggle.events.change(); requests[2].success({status: 'Updated'}); requests[2].finish();
    assert.strictEqual(status.textContent, 'Ended or inactive'); assert.strictEqual(access.textContent, 'Preview access');
    access.attributes['data-billing-access'] = '1'; toggle.events.change(); requests[3].success({status: 'Updated'}); requests[3].finish();
    assert.strictEqual(access.textContent, 'Full media access', 'Removing the override preserves paid access.');
};
