const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

module.exports = function () {
    function field(maxFiles, disabled = false) {
        const classes = new Set(), events = {}, inputEvents = {}, formEvents = {};
        const selection = {};
        const input = {dataset: {maxFiles: String(maxFiles), maxBytes: '10485760'}, disabled, files: [], validationMessage: '',
            addEventListener(name, fn) { inputEvents[name] = fn; },
            dispatchEvent(event) { inputEvents[event.type](event); },
            setCustomValidity(message) { this.validationMessage = message; }, reportValidity() { this.reported = true; },
            form: {addEventListener(name, fn) { formEvents[name] = fn; }}};
        const root = {querySelector(selector) { return selector === 'input[type="file"]' ? input : selector === '.file-dropzone' ? {classList: {add: value => classes.add(value), remove: value => classes.delete(value)}} : selection; },
            addEventListener(name, fn) { events[name] = fn; }};
        return {root, input, inputEvents, formEvents, selection, events, classes};
    }
    const multiple = field(10), single = field(1), disabled = field(1, true);
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/components/file-dropzone.js'), 'utf8'), {
        document: {querySelectorAll: () => [multiple.root, single.root, disabled.root]},
        Event: class { constructor(type) { this.type = type; } }
    });
    const front = {name: 'front.png', size: 5000}, back = {name: '<back>.webp', size: 6000};
    function event(files = [], types = ['Files']) { return {dataTransfer: {files, types}, preventDefault() { this.prevented = true; }}; }
    multiple.events.dragenter(event());
    multiple.events.dragenter(event());
    multiple.events.dragleave();
    assert(multiple.classes.has('is-dragging'), 'Moving across children keeps the drag target active');
    multiple.events.drop(event([front, back]));
    assert.equal(multiple.classes.has('is-dragging'), false);
    assert.equal(multiple.input.files.length, 2);
    assert.equal(multiple.selection.textContent, 'front.png, <back>.webp', 'Filenames remain plain text');
    assert.equal(single.input.files.length, 0, 'A color upload never changes the close-up input');
    single.events.drop(event([front, back]));
    assert.equal(single.input.validationMessage, 'Choose up to 1 image.');
    single.input.files = [front]; single.inputEvents.change();
    assert.equal(single.input.validationMessage, '', 'Selecting a valid replacement clears the error');
    multiple.input.files = Array.from({length: 11}, () => front); multiple.inputEvents.change();
    assert.equal(multiple.input.validationMessage, 'Choose up to 10 images.');
    multiple.input.files = [{name: 'large.jpg', size: 10485761}]; multiple.inputEvents.change();
    assert.equal(multiple.input.validationMessage, 'Choose images up to 10 MB each.');
    multiple.formEvents.reset();
    assert.equal(multiple.input.validationMessage, '');
    assert.equal(multiple.selection.textContent, 'No files selected');
    disabled.events.drop(event([front]));
    assert.equal(disabled.input.files.length, 0);
    const textDrag = event([], ['text/plain']); multiple.events.dragenter(textDrag);
    assert.equal(textDrag.prevented, undefined, 'Text drags do not activate file handling');
    console.log('Passed: shared file drop targets, native selection, per-field isolation, limits, disabled states and resets.');
};
