const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    let options;
    const handlers = {};
    const reloads = [];
    const inputs = ['with_videos', 'with_sections', 'with_synthesia'].map(name => ({name, checked: true}));
    const error = {hidden: true, prop(name, value) { this[name] = value; }};
    const head = {invisible: true, removeClass() { this.invisible = false; }};
    const wrapper = {loading: false, addClass() { this.loading = true; }, removeClass() { this.loading = false; }};
    const tableApi = {table: () => ({container: () => wrapper}), ajax: {reload: (callback, reset) => reloads.push(reset)}};
    const table = {
        length: 1, find: () => head, closest: () => wrapper,
        on(name, callback) { handlers[name] = callback; },
        DataTable(config) { options = config; return tableApi; }
    };
    const filters = {
        find: () => ({each(callback) { inputs.forEach(input => callback.call(input)); }}),
        on(name, selector, callback) { handlers.filter = callback; }
    };
    const retry = {on(name, callback) { handlers.retry = callback; }};
    function $(selector) {
        if (typeof selector === 'function') return selector();
        if (selector === wrapper) return wrapper;
        return {'#pieces-table': table, '[data-piece-table-filters]': filters, '[data-piece-table-error]': error, '[data-piece-table-retry]': retry}[selector];
    }
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-pieces.js', 'utf8'), {jQuery: $, window: {location: {href: '/pieces'}}});
    const data = {draw: 1, start: 0};
    options.ajax.data(data);
    assert.deepStrictEqual({...data}, {draw: 1, start: 0, with_videos: 1, with_sections: 1, with_synthesia: 1});
    inputs[1].checked = false;
    handlers.filter();
    options.ajax.data(data);
    assert.strictEqual(data.with_sections, 0, 'Unchecked filters are sent explicitly, overriding server defaults');
    assert.deepStrictEqual(reloads, [true], 'Filter changes return to the first page');
    inputs.forEach(input => { input.checked = false; });
    options.ajax.data(data);
    assert.strictEqual(data.with_videos + data.with_sections + data.with_synthesia, 0);
    const current = {}, stale = {};
    handlers['xhr.dt']({}, {jqXHR: current}, null, stale);
    assert.strictEqual(error.hidden, true, 'Stale failures cannot replace the latest request status');
    assert.strictEqual(wrapper.loading, true);
    assert.strictEqual(handlers['xhr.dt']({}, {jqXHR: current}, null, current), true);
    assert.strictEqual(error.hidden, false);
    assert.strictEqual(wrapper.loading, false, 'Initial failures release the loading overlay');
    assert.strictEqual(head.invisible, false);
    handlers.retry();
    assert.deepStrictEqual(reloads, [true, false], 'Retry preserves the current page');
    handlers['xhr.dt']({}, {jqXHR: current}, {data: []}, current);
    assert.strictEqual(error.hidden, true, 'A successful retry clears the error');
    handlers['xhr.dt']({}, {jqXHR: current}, {error: 'Unavailable'}, current);
    assert.strictEqual(error.hidden, false);
    console.log('Passed: pieces filter reloads, explicit disabled filters, stale failures, spinner recovery and retry.');
};
