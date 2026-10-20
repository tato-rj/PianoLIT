const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function node(properties = {}) {
        return Object.assign({listeners: {}, addEventListener(event, callback) { this.listeners[event] = callback; }}, properties);
    }
    const now = node({value: 'now', checked: true});
    const later = node({value: 'later', checked: false});
    const date = {value: '2026-10-22T09:00', disabled: false, required: false, classList: {remove() {}}};
    const calendar = {}, submit = {}, label = {}, title = {}, error = {hidden: false};
    const nodes = {'[data-freepick-calendar]': calendar, '[data-freepick-submit]': submit,
        '[data-freepick-submit-label]': label, '[data-freepick-title]': title, '[value="now"]': now, '[value="later"]': later};
    let valid = true, restored = 0;
    const form = node({action: '/pieces/1/schedule', dataset: {restore: 'true'}, elements: {namedItem() { return date; }},
        querySelector(selector) { return selector === '[name="mode"]:checked' ? (later.checked ? later : now) : nodes[selector]; },
        querySelectorAll(selector) { return selector === '[name="mode"]' ? [now, later] : [error]; },
        getAttribute() { return this.action; }, checkValidity() { return valid; }});
    const modal = node({querySelector() { return form; }});
    const window = node({bootstrap: {Modal: {getOrCreateInstance() { return {show() { restored++; }}; }}}});
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-freepick-schedule.js', 'utf8'), {document: {getElementById() { return modal; }}, window});
    assert.strictEqual(restored, 1, 'A server validation failure reopens the modal.');
    assert.strictEqual(date.disabled, true, 'Immediate selection omits stale calendar input.');
    later.checked = true; now.checked = false; later.listeners.change();
    assert.strictEqual(calendar.hidden, false);
    assert.strictEqual(date.required, true);
    assert.strictEqual(date.disabled, false);
    assert.strictEqual(label.textContent, 'Schedule Freepick');
    let prevented = 0;
    valid = false; form.listeners.submit({preventDefault() { prevented++; }});
    assert.strictEqual(prevented, 1);
    valid = true; form.listeners.submit({preventDefault() { prevented++; }});
    assert.strictEqual(submit.disabled, true);
    form.listeners.submit({preventDefault() { prevented++; }});
    assert.strictEqual(prevented, 2, 'Repeated keyboard submission cannot schedule twice.');
    modal.listeners['show.bs.modal']({relatedTarget: {dataset: {freepickUrl: '/pieces/2/schedule?sort=id_asc', freepickName: '<script>Piece two</script>'}}});
    assert.strictEqual(form.action, '/pieces/2/schedule?sort=id_asc');
    assert.strictEqual(title.textContent, '<script>Piece two</script>', 'Titles are inserted as text.');
    assert.strictEqual(now.checked, true);
    assert.strictEqual(later.checked, false);
    assert.strictEqual(date.value, '');
    assert.strictEqual(error.hidden, true, 'An error on another piece is cleared when opening a new selection.');
    assert.strictEqual(submit.disabled, false);
    window.listeners.pageshow();
    assert.strictEqual(submit.disabled, false, 'Back navigation restores the submit control.');
    console.log('Passed: Freepick scheduling choices, validation restoration, identity, safe titles and duplicate submission.');
};
