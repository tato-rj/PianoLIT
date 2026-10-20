const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = async function () {
    function control(properties = {}) {
        return Object.assign({listeners: {}, addEventListener(event, callback) { this.listeners[event] = callback; }}, properties);
    }
    const image = control({name: '', value: ''});
    const cancel = control();
    const sample = control({disabled: false});
    const description = {value: 'Existing description', readOnly: false};
    const error = {hidden: true};
    const status = {textContent: 'All changes saved'};
    const nodes = {'fieldset': {disabled: false}, '#image-input': image, '#cancel-button': cancel,
        '[data-editor-save-status]': status, '[data-sample-description]': sample, '[data-description-error]': error};
    const form = control({querySelector(selector) { return nodes[selector] || null; },
        elements: {namedItem(name) { return name === 'description' ? description : null; }},
        dataset: {pieceId: '7', descriptionUrl: '/description'}});
    let cropperOptions, fail = true;
    const window = {
        SimpleCropper: function (options) { cropperOptions = options; this.create = function () {}; },
        axios: {get(url, options) {
            assert.strictEqual(url, '/description');
            assert.strictEqual(options.params.piece_id, '7');
            return fail ? Promise.reject(new Error('Fixture failure')) : Promise.resolve({data: 'Sample description'});
        }}
    };
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-piece-editor.js', 'utf8'), {document: {querySelector() { return form; }}, window});
    assert.strictEqual(cropperOptions.submitButton, '#edit-form [data-save-piece]', 'Cropping disables only the editor save action.');
    image.value = 'fixture.jpg';
    image.listeners.change.call(image);
    assert.strictEqual(image.name, 'cover_image');
    cancel.listeners.click();
    assert.strictEqual(image.value, '', 'Canceling a crop removes the pending file.');
    assert.strictEqual(image.name, '', 'A canceled cover cannot be submitted later.');

    sample.listeners.click();
    assert.strictEqual(sample.disabled, true);
    assert.strictEqual(description.readOnly, true);
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(sample.disabled, false, 'The sample action recovers after failure.');
    assert.strictEqual(description.readOnly, false);
    assert.strictEqual(error.hidden, false);
    assert.strictEqual(description.value, 'Existing description', 'Failure preserves the current description.');
    fail = false;
    sample.listeners.click();
    await new Promise(resolve => setImmediate(resolve));
    assert.strictEqual(error.hidden, true);
    assert.strictEqual(description.value, 'Sample description');
    assert.strictEqual(status.textContent, 'Unsaved changes');
    console.log('Passed: piece editor crop cancellation, scoped save controls and description failure/retry.');
};
