const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function node(properties = {}) {
        return Object.assign({listeners: {}, addEventListener(event, callback) { this.listeners[event] = callback; }}, properties);
    }
    const body = node({children: [], insertBefore(row, sibling) {
        this.children.splice(this.children.indexOf(row), 1);
        this.children.splice(this.children.indexOf(sibling), 0, row);
    }});
    function row(id) {
        const record = {dataset: {playlistId: String(id)}, position: {}};
        record.up = {dataset: {playlistMove: 'up'}, closest() { return record; }, focus() { this.focused = true; }};
        record.down = {dataset: {playlistMove: 'down'}, closest() { return record; }, focus() { this.focused = true; }};
        record.querySelector = selector => selector === '[data-playlist-position]' ? record.position
            : selector === '[data-playlist-move="up"]' ? record.up
            : selector === '[data-playlist-move="down"]' ? record.down : (record.up.disabled ? record.down : record.up);
        Object.defineProperties(record, {
            previousElementSibling: {get() { return body.children[body.children.indexOf(record) - 1]; }},
            nextElementSibling: {get() { return body.children[body.children.indexOf(record) + 1]; }},
        });
        return record;
    }
    body.children = [row(1), row(2), row(3)];
    const first = body.children[0], status = {}, save = {};
    const form = node({querySelector(selector) { return selector === 'tbody' ? body : selector === '[data-save-status]' ? status : save; }});
    let sortable, restored = 0;
    const jQuery = element => ({sortable(options) { assert.strictEqual(element, body); sortable = options; }});
    jQuery.fn = {sortable() {}};
    const window = node({jQuery, bootstrap: {Modal: {getOrCreateInstance() { return {show() { restored++; }}; }}}});
    const document = {querySelector(selector) { return selector === '#add-modal.autoshow' ? {} : form; }};
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-playlists.js', 'utf8'), {window, document});
    assert.strictEqual(restored, 1, 'Failed creation reopens its own modal.');
    assert.strictEqual(status.textContent, 'All changes saved');
    assert.strictEqual(first.up.disabled, true);
    body.listeners.click({target: {closest() { return first.down; }}});
    assert.deepStrictEqual(body.children.map(item => item.dataset.playlistId), ['2', '1', '3']);
    assert.strictEqual(first.position.textContent, 2);
    assert.strictEqual(first.down.focused, true);
    assert.strictEqual(status.textContent, 'Unsaved changes');
    body.listeners.click({target: {closest() { return first.up; }}});
    assert.strictEqual(status.textContent, 'All changes saved', 'Undoing the move restores the saved state.');
    assert.strictEqual(first.down.focused, true, 'Focus stays in the moved row when its Up button becomes disabled.');
    body.insertBefore(body.children[2], body.children[0]);
    sortable.stop();
    assert.deepStrictEqual(body.children.map(item => item.position.textContent), [1, 2, 3]);
    assert.strictEqual(status.textContent, 'Unsaved changes');
    form.listeners.submit({preventDefault() { assert.fail('The first submission should proceed.'); }});
    assert.strictEqual(save.disabled, true);
    let prevented = false;
    form.listeners.submit({preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true, 'Repeated submissions are prevented.');
    window.listeners.pageshow();
    assert.strictEqual(save.disabled, false, 'Back navigation restores Save.');
    assert.strictEqual(status.textContent, 'Unsaved changes');
    console.log('Passed: playlist order arrows, drag completion, positions, boundaries, focus, save state and create-modal recovery.');
};
