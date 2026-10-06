const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function element() {
        return {value: '', disabled: false, attrs: {}, events: {}, textContent: '',
            classList: {toggle() {}},
            addEventListener(type, fn) { this.events[type] = fn; },
            dispatchEvent(event) { if (this.events[event.type]) this.events[event.type](event); },
            setAttribute(key, value) { this.attrs[key] = value; },
            getAttribute(key) { return this.attrs[key]; },
            removeAttribute(key) { delete this.attrs[key]; }, focus() { this.focused = true; }};
    }
    const button = element(), bio = element(), status = element(), save = element(), form = element();
    const token = {value: 'session-csrf'};
    const requests = [];
    button.closest = () => form;
    button.attrs['data-url'] = 'https://admin.pianolit.com/composers/1/regenerate-biography';
    form.querySelector = selector => selector === '[name="biography"]' ? bio : token;
    form.querySelectorAll = () => [save];
    bio.value = 'Original bio';
    vm.runInNewContext(fs.readFileSync('resources/js/views/composer-biography-admin.js', 'utf8'), {
        document: {getElementById: id => id === 'regenerate-biography' ? button : status},
        Event: function (type) { this.type = type; },
        $: {ajax(options) {
            const request = {options, done(fn) { this.success = fn; return this; },
                fail(fn) { this.failure = fn; return this; }, always(fn) { this.finish = fn; return this; }};
            requests.push(request); return request;
        }}
    });
    button.events.click(); button.events.click();
    assert.strictEqual(requests.length, 1, 'Repeated clicks make only one request');
    assert.strictEqual(requests[0].options.headers['X-CSRF-TOKEN'], 'session-csrf');
    assert.strictEqual(requests[0].options.data.biography, 'Original bio');
    assert.strictEqual(requests[0].options.method, 'POST');
    assert.strictEqual(button.disabled, true); assert.strictEqual(save.disabled, true);
    let prevented = false;
    form.events.submit({preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true);
    requests[0].success({biography: 'New simple bio'}); requests[0].finish();
    assert.strictEqual(bio.value, 'New simple bio');
    assert(status.textContent.includes('Review it'));
    assert.strictEqual(button.disabled, false); assert.strictEqual(save.disabled, false);
    assert.strictEqual(bio.attrs['aria-busy'], undefined);

    button.events.click();
    bio.value = 'My latest edit'; bio.events.input();
    requests[1].success({biography: 'Stale response'}); requests[1].finish();
    assert.strictEqual(bio.value, 'My latest edit');
    assert(status.textContent.includes('changed'));
    button.events.click(); bio.events.input(); // Editing and restoring the same text still invalidates the request.
    requests[2].success({biography: 'Stale response'}); requests[2].finish();
    assert.strictEqual(bio.value, 'My latest edit');

    for (const xhr of [{status: 500}, {status: 419}, {status: 429},
        {status: 503, responseJSON: {message: 'Configure the API key'}},
        {status: 422, responseJSON: {errors: {biography: ['Source is too long']}}}]) {
        button.events.click(); const request = requests[requests.length - 1];
        request.failure(xhr); request.finish();
        assert.strictEqual(bio.value, 'My latest edit');
        assert.strictEqual(button.disabled, false); assert.strictEqual(save.disabled, false);
        assert(status.textContent.length > 0);
    }
    save.disabled = true; button.events.click();
    requests[requests.length - 1].success({}); requests[requests.length - 1].finish();
    assert.strictEqual(save.disabled, true, 'Restore the prior state of save controls');
    assert.strictEqual(bio.value, 'My latest edit');
    bio.value = '  '; const count = requests.length;
    button.events.click(); assert.strictEqual(requests.length, count); assert.strictEqual(bio.focused, true);
    console.log('Passed: composer bio drafts, CSRF, duplicate prevention, editing races, failures and restored controls.');
};
