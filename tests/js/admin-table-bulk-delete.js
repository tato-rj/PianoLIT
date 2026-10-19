const assert = require('assert');
const install = require('../../resources/js/components/admin-table-bulk-delete');

module.exports = function () {
    function fixture(preview = false) {
        const events = {}, tableEvents = {}, formEvents = {}, selectEvents = {};
        const all = {addEventListener: (name, fn) => { selectEvents[name] = fn; }};
        const count = {}, open = {}, submit = {}, confirmation = {};
        const form = {id: 'selection-form', hasAttribute: name => preview && name === 'data-bulk-preview',
            querySelector: () => submit, addEventListener: (name, fn) => { formEvents[name] = fn; }};
        const rows = Array.from({length: 3}, () => ({checked: false, disabled: false, form: preview ? null : form, getAttribute: () => form.id}));
        // An unrelated form's checkbox must never affect selection or submission.
        rows.push({checked: true, disabled: false, form: {}, getAttribute: () => 'other-form'});
        const table = {querySelectorAll: () => rows, addEventListener: (name, fn) => { tableEvents[name] = fn; }};
        const toolbar = {dataset: {tableId: 'table', formId: form.id}, querySelector: selector => ({
            '[data-bulk-select-all]': all, '[data-bulk-count]': count, '[data-bulk-open]': open
        })[selector]};
        const doc = {querySelectorAll: () => [toolbar], querySelector: () => confirmation,
            getElementById: id => id === form.id ? form : table};
        const view = {addEventListener: (name, fn) => { events[name] = fn; }};
        install(doc, view);
        function change(index, checked) { rows[index].checked = checked; tableEvents.change(); }
        function select(checked) { all.checked = checked; selectEvents.change(); }
        function prevented() { let result = false; formEvents.submit({preventDefault() { result = true; }}); return result; }
        return {all, count, open, submit, confirmation, rows, events, change, select, prevented, doc, view, toolbar};
    }
    const page = fixture();
    assert.strictEqual(page.count.textContent, '0 selected');
    assert.strictEqual(page.open.disabled, true);
    assert.strictEqual(page.prevented(), true);
    page.change(0, true);
    assert.strictEqual(page.count.textContent, '1 selected');
    assert.strictEqual(page.all.indeterminate, true);
    assert.strictEqual(page.open.disabled, false);
    assert.strictEqual(page.confirmation.textContent, 1);
    assert.strictEqual(page.prevented(), false);
    page.rows[2].disabled = true;
    page.select(true);
    assert.strictEqual(page.count.textContent, '2 selected');
    assert.strictEqual(page.all.checked, true);
    assert.strictEqual(page.all.indeterminate, false);
    assert.strictEqual(page.rows[2].checked, false);
    page.select(false);
    assert.strictEqual(page.count.textContent, '0 selected');
    assert.strictEqual(page.submit.disabled, true);
    page.rows[1].checked = true;
    page.events.pageshow();
    assert.strictEqual(page.count.textContent, '1 selected', 'Browser-restored selection updates its controls.');
    const original = page.toolbar.dataset.bulkReady;
    install(page.doc, page.view);
    assert.strictEqual(original, 'true');
    const preview = fixture(true);
    preview.select(true);
    assert.strictEqual(preview.count.textContent, '3 selected');
    assert.strictEqual(preview.prevented(), true, 'The Design System can never submit deletion.');
    console.log('Passed: bulk selection, partial/all/empty states, form isolation, disabled rows, restored selections and safe preview.');
};
