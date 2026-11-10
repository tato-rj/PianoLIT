const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function node(properties = {}) {
        return Object.assign({listeners: {}, addEventListener(name, callback) { this.listeners[name] = callback; }, focus() { this.focused = true; }}, properties);
    }
    const status = {textContent: 'Changes take effect when you save.'};
    const save = {disabled: false};
    const image = node({name: '', value: 'chosen.jpg'});
    const cancel = node(); const confirm = node(); const add = node();
    const rows = node({children: []});
    function row(index) {
        const input = node({name: 'references[' + index + ']', value: 'Source ' + index});
        const item = {dataset: {referenceIndex: String(index)}, input,
            querySelector() { return input; },
            remove() { rows.children.splice(rows.children.indexOf(item), 1); },
            get nextElementSibling() { return rows.children[rows.children.indexOf(item) + 1]; },
            get previousElementSibling() { return rows.children[rows.children.indexOf(item) - 1]; }};
        item.removeButton = {closest(selector) { return selector === '[data-remove-reference]' ? this : item; }};
        return item;
    }
    rows.children = [row(0), row(1), row(2)];
    rows.querySelectorAll = () => rows.children;
    Object.defineProperty(rows, 'lastElementChild', {get() { return rows.children[rows.children.length - 1]; }});
    rows.insertAdjacentHTML = (position, html) => rows.children.push(row(Number(html.match(/references\[(\d+)\]/)[1])));
    const template = {innerHTML: '<input name="references[__INDEX__]" id="post-reference-__INDEX__">'};
    const nodes = {'[data-save-status]': status, '#submit-button': save, '#image-input': image,
        '#cancel-button': cancel, '#confirm-button': confirm, '[data-reference-rows]': rows,
        '[data-reference-template]': template, '[data-add-reference]': add};
    const form = node({querySelector: selector => nodes[selector]});
    let cropperOptions; const events = {}; const editor = node({id: 'editor', on(name, callback) { this.changed = callback; }});
    const window = {
        addEventListener(name, callback) { events[name] = callback; },
        SimpleCropper: function (options) { cropperOptions = options; this.create = function () {}; },
        tinymce: {get() { return editor; }, on() {}}
    };
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-post-editor.js', 'utf8'), {document: {querySelector() { return form; }}, window});
    assert.strictEqual(cropperOptions.submitButton, '#post-editor #submit-button');
    image.listeners.change(); assert.strictEqual(image.name, 'cover_image');
    cancel.listeners.click(); assert.strictEqual(image.value, ''); assert.strictEqual(image.name, '');
    rows.listeners.click({target: rows.children[1].removeButton});
    add.listeners.click();
    assert.deepStrictEqual(rows.children.map(item => item.input.name), ['references[0]', 'references[2]', 'references[3]']);
    assert.strictEqual(rows.children[1].input.value, 'Source 2', 'Removing a middle row never overwrites the later citation.');
    rows.listeners.click({target: rows.children[2].removeButton}); add.listeners.click();
    assert.strictEqual(rows.children[2].input.name, 'references[4]', 'Reference indexes are never reused.');
    assert.strictEqual(rows.children[2].input.focused, true);
    assert.strictEqual(status.textContent, 'Unsaved changes');
    editor.changed(); assert.strictEqual(status.textContent, 'Unsaved changes');
    let prevented = false;
    save.disabled = true; form.listeners.submit({preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true, 'The crop must be confirmed before implicit submission.');
    assert.strictEqual(confirm.focused, true);
    save.disabled = false; form.listeners.submit({preventDefault() { throw new Error('Valid save blocked'); }});
    events.beforeunload({preventDefault() { throw new Error('Saving should not warn'); }});
    events.pageshow();
    const leaving = {preventDefault() { this.warned = true; }}; events.beforeunload(leaving);
    assert.strictEqual(leaving.warned, true, 'Returning from the page cache restores the unsaved-change guard.');
    let tinyConfig, tinyChanged;
    const tinyEvents = {}, tinyDocumentEvents = {};
    const tinyWindow = {app: {routes: {blogImageUpload: '/upload'}}, addEventListener(name, callback) { tinyEvents[name] = callback; }};
    vm.runInNewContext(fs.readFileSync('resources/js/tinyeditor/tiny.js', 'utf8'), {
        window: tinyWindow,
        document: {addEventListener(name, callback) { tinyDocumentEvents[name] = callback; }},
        tinymce: {init(options) { tinyConfig = options; }}
    });
    tinyConfig.setup({on(name, callback) { tinyChanged = callback; }, ui: {registry: {addMenuItem() {}}}});
    tinyChanged();
    tinyDocumentEvents.submit({defaultPrevented: true, target: {querySelector() { return true; }}});
    const stillDirty = {preventDefault() { this.warned = true; }}; tinyWindow.onbeforeunload(stillDirty);
    assert.strictEqual(stillDirty.warned, true, 'A prevented submit must keep the rich-text leave guard.');
    tinyDocumentEvents.submit({defaultPrevented: false, target: {querySelector() { return true; }}});
    tinyWindow.onbeforeunload({preventDefault() { throw new Error('Implicit rich-text save should not warn.'); }});
    tinyEvents.pageshow();
    const restored = {preventDefault() { this.warned = true; }}; tinyWindow.onbeforeunload(restored);
    assert.strictEqual(restored.warned, true);
    console.log('Passed blog editor citation indexes, crop cancellation, save status and submission checks.');
};
