const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function fixture(errorSection = '', restored = false) {
        const listeners = {}, windowListeners = {}, revoked = [];
        const sections = ['pianist-profile', 'pianist-biography', 'pianist-music'];
        const selected = {value: sections[0]}, status = {}, saves = [{}, {}];
        const controls = [{name: 'name', value: 'Clara', type: 'text'}, {name: 'biography', value: 'A biography', type: 'textarea'}, {name: 'cover', type: 'file', files: [], addEventListener(event, callback) {this.changed = callback;}}];
        let firstInvalid = null;
        const portrait = {getAttribute() {return '/original.jpg';}}, portraitStatus = {};
        const tabs = sections.map((id, index) => ({id, active: index === 0, getAttribute() {return '#' + id;}, classList: {contains() {return tabs[index].active;}}}));
        const form = {
            getAttribute(name) {return name === 'data-restored' ? String(restored) : errorSection;},
            addEventListener(event, callback) {listeners[event] = callback;},
            querySelectorAll(selector) {return selector === '[role="tab"]' ? tabs : controls;},
            querySelector(selector) {
                return {'[data-pianist-section]': selected, '[data-save-status]': status, '[data-pianist-portrait]': portrait,
                    '[name="cover"]': controls[2], '[data-portrait-status]': portraitStatus}[selector] || firstInvalid;
            },
        };
        const document = {querySelector(selector) {return selector === '[data-pianist-editor]' ? form : null;}, querySelectorAll() {return saves;}};
        const window = {addEventListener(event, callback) {windowListeners[event] = callback;}, bootstrap: {Tab: {getOrCreateInstance(tab) {return {show() {
            tabs.forEach(item => {item.active = item === tab;}); listeners['shown.bs.tab']();
        }};}}}};
        class PreviewURL extends URL {}
        PreviewURL.createObjectURL = file => 'blob:' + file.name;
        PreviewURL.revokeObjectURL = url => revoked.push(url);
        vm.runInNewContext(fs.readFileSync('resources/js/views/admin-pianists.js', 'utf8'), {document, window, URL: PreviewURL});
        return {listeners, windowListeners, selected, status, saves, controls, tabs, portrait, portraitStatus, revoked,
            invalid(control, panel) {firstInvalid = control; control.closest = () => ({id: panel}); listeners.invalid({target: control});},
        };
    }
    const state = fixture();
    assert.strictEqual(state.status.textContent, 'All changes saved');
    state.controls[0].value = 'Clara revised'; state.listeners.input();
    assert.strictEqual(state.status.textContent, 'Unsaved changes');
    state.controls[0].value = 'Clara'; state.listeners.input();
    assert.strictEqual(state.status.textContent, 'All changes saved', 'Undo restores the saved state.');
    state.tabs[0].active = false; state.tabs[2].active = true; state.listeners['shown.bs.tab']();
    assert.strictEqual(state.selected.value, 'pianist-music');
    assert.strictEqual(state.controls[1].value, 'A biography', 'Tab changes retain the same form controls.');
    state.invalid(state.controls[1], 'pianist-biography');
    assert.strictEqual(state.selected.value, 'pianist-biography', 'Native validation reveals the first invalid control.');
    state.controls[2].files = [{name: 'portrait.jpg', type: 'image/jpeg', size: 42, lastModified: 1}];
    state.controls[2].changed(); state.listeners.change();
    assert.strictEqual(state.portrait.src, 'blob:portrait.jpg');
    state.controls[2].files = []; state.controls[2].changed();
    assert.strictEqual(state.portrait.src, '/original.jpg');
    assert.deepStrictEqual(state.revoked, ['blob:portrait.jpg']);
    state.listeners.submit({preventDefault() {assert.fail('First submit proceeds.');}});
    assert(state.saves.every(button => button.disabled));
    assert.strictEqual(state.status.textContent, 'Saving changes…');
    let duplicates = 0;
    state.listeners.submit({preventDefault() {duplicates++;}});
    assert.strictEqual(duplicates, 1);
    state.windowListeners.pageshow();
    assert(state.saves.every(button => !button.disabled), 'Back navigation restores Save controls.');
    const errors = fixture('pianist-biography', true);
    assert.strictEqual(errors.selected.value, 'pianist-biography');
    assert.strictEqual(errors.status.textContent, 'Unsaved changes');
    console.log('Passed: pianist editor tabs, native/server validation, draft status, portrait previews and save recovery.');
};
