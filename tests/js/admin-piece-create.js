const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = async function () {
    const status = {textContent: '', hidden: false};
    const error = {hidden: true};
    const results = {innerHTML: '', querySelector() { return this.innerHTML.includes('<li>') ? {} : null; }};
    const nodes = {'[data-duplicate-status]': status, '[data-duplicate-results]': results, '[data-duplicate-error]': error};
    const panel = {busy: 'false', querySelector(selector) { return nodes[selector]; }, setAttribute(name, value) { this.busy = value; }};
    const fields = Object.fromEntries(['name', 'collection_name', 'catalogue_number', 'collection_number', 'movement_number', '_token'].map(name => [name, {name, value: ''}]));
    fields._token.value = 'fixture-token';
    const listeners = {}, events = {}, requests = [];
    const warning = {hidden: true};
    let timer, tagCount = 0;
    const form = {
        dataset: {validateNameUrl: '/pieces/validate-name'},
        elements: {namedItem(name) { return fields[name]; }},
        querySelector(selector) { return selector === '#validation-results' ? panel : warning; },
        querySelectorAll() { return Array(tagCount); },
        addEventListener(event, handler) { listeners[event] = handler; }
    };
    const window = {
        setTimeout(callback) { timer = callback; return 1; }, clearTimeout() { timer = null; },
        addEventListener(event, handler) { events[event] = handler; },
        axios: {
            CancelToken: {source() { return {token: 'fixture', cancel() {}}; }},
            isCancel(error) { return !!error.canceled; },
            post(url, data, options) {
                assert.strictEqual(url, '/pieces/validate-name');
                assert.strictEqual(options.headers['X-CSRF-TOKEN'], 'fixture-token');
                return new Promise((resolve, reject) => requests.push({data, resolve, reject}));
            }
        }
    };
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-piece-create.js', 'utf8'), {document: {querySelector() { return form; }}, window});
    function input(value) { fields.name.value = value; listeners.input({target: fields.name}); }
    function runTimer() { const callback = timer; timer = null; callback(); }
    const settle = () => new Promise(resolve => setImmediate(resolve));

    input('Old name'); runTimer();
    assert.strictEqual(panel.busy, 'true');
    input('Current name'); runTimer();
    requests[1].resolve({data: '<ul><li>Current result</li></ul>'}); await settle();
    assert.strictEqual(results.innerHTML, '<ul><li>Current result</li></ul>');
    assert.strictEqual(panel.busy, 'false');
    requests[0].resolve({data: '<ul><li>Obsolete result</li></ul>'}); await settle();
    assert.strictEqual(results.innerHTML, '<ul><li>Current result</li></ul>', 'A late response must not replace current matches.');

    input('No matches'); runTimer(); requests[2].resolve({data: '<ul></ul>'}); await settle();
    assert.strictEqual(status.textContent, 'No similar pieces found.');
    input('Request fails'); runTimer(); requests[3].reject(new Error('Unavailable')); await settle();
    assert.strictEqual(error.hidden, false);
    assert.strictEqual(panel.busy, 'false');
    input('Retry'); runTimer(); requests[4].resolve({data: '<ul><li>Retry result</li></ul>'}); await settle();
    assert.strictEqual(error.hidden, true);
    input(''); runTimer();
    assert.strictEqual(requests.length, 5, 'Empty names do not send validation requests.');
    assert.strictEqual(results.innerHTML, '');
    assert.strictEqual(status.hidden, false);

    tagCount = 8; listeners.change({target: {name: 'tags[]'}}); assert.strictEqual(warning.hidden, false);
    tagCount = 7; listeners.change({target: {name: 'tags[]'}}); assert.strictEqual(warning.hidden, true);
    input('Leave page'); runTimer(); events.pagehide();
    requests[5].resolve({data: '<ul><li>Late after exit</li></ul>'}); await settle();
    assert.strictEqual(results.innerHTML, '', 'Page exit invalidates pending responses.');
    console.log('Passed: create-piece duplicate checks reject stale responses, recover from failure, skip empty names and cancel on exit.');
};
