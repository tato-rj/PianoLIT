const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

module.exports = function () {
    function field(maxFiles, disabled = false) {
        const classes = new Set(), events = {}, inputEvents = {}, formEvents = {};
        const selection = {};
        const clear = {hidden: true, events: {}, addEventListener(name, fn) { this.events[name] = fn; }};
        const message = {hidden: false};
        const previews = {children: [], hidden: true, replaceChildren() { this.children = []; }, appendChild(image) { this.children.push(image); image.parent = this; }};
        const input = {dataset: {maxFiles: String(maxFiles), maxBytes: '10485760'}, multiple: maxFiles > 1, disabled, files: [], validationMessage: '',
            addEventListener(name, fn) { inputEvents[name] = fn; },
            dispatchEvent(event) { inputEvents[event.type](event); },
            setCustomValidity(message) { this.validationMessage = message; }, reportValidity() { this.reported = true; },
            form: {addEventListener(name, fn) { formEvents[name] = fn; }}};
        Object.defineProperty(input, 'value', {set(value) { if (value === '') this.files = []; }});
        const root = {querySelector(selector) { return selector === 'input[type="file"]' ? input : selector === '.file-dropzone' ? {classList: {add: value => classes.add(value), remove: value => classes.delete(value)}} : selector === '[data-file-previews]' ? previews : selector === '.file-dropzone__message' ? message : selector === '[data-file-clear]' ? clear : selection; },
            addEventListener(name, fn) { events[name] = fn; }};
        return {root, input, inputEvents, formEvents, selection, events, classes, previews, message, clear};
    }
    const multiple = field(10), single = field(1), disabled = field(1, true), audio = field(1);
    Object.assign(audio.input.dataset, {fileLabel: 'audio file', filePluralLabel: 'audio files', maxBytes: '2097152'});
    const urls = new Set(), windowEvents = {};
    let sequence = 0;
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/components/file-dropzone.js'), 'utf8'), {
        document: {querySelectorAll: () => [multiple.root, single.root, disabled.root, audio.root], createElement() {
            return {events: {}, addEventListener(name, fn) { this.events[name] = fn; }, remove() { this.parent.children = this.parent.children.filter(image => image !== this); }};
        }},
        URL: {createObjectURL() { const url = 'blob:preview-' + (++sequence); urls.add(url); return url; }, revokeObjectURL(url) { urls.delete(url); }},
        DataTransfer: class { constructor() { this.files = []; this.items = {add: file => this.files.push(file)}; } },
        window: {addEventListener(name, fn) { (windowEvents[name] ||= []).push(fn); }},
        Event: class { constructor(type) { this.type = type; } }
    });
    const front = {name: 'front.png', type: 'image/png', size: 5000}, back = {name: '<back>.webp', type: 'image/webp', size: 6000};
    function event(files = [], types = ['Files']) { return {dataTransfer: {files, types}, preventDefault() { this.prevented = true; }}; }
    multiple.events.dragenter(event());
    multiple.events.dragenter(event());
    multiple.events.dragleave();
    assert(multiple.classes.has('is-dragging'), 'Moving across children keeps the drag target active');
    multiple.events.drop(event([front, back]));
    assert.equal(multiple.classes.has('is-dragging'), false);
    assert.equal(multiple.input.files.length, 2);
    assert.equal(multiple.selection.textContent, 'front.png, <back>.webp', 'Filenames remain plain text');
    assert.equal(multiple.previews.children.length, 2, 'Every selected image appears inside its own upload box');
    assert.equal(multiple.previews.children[1].alt, '<back>.webp');
    assert.equal(multiple.message.hidden, true);
    assert.equal(multiple.previews.hidden, false);
    const oldImages = [...multiple.previews.children];
    const side = {name: 'side.jpg', type: 'image/jpeg', size: 7000};
    multiple.events.drop(event([side]));
    assert.deepEqual(multiple.input.files.map(file => file.name), ['front.png', '<back>.webp', 'side.jpg'], 'A later drop adds to the actual multipart file input');
    assert.equal(multiple.previews.children.length, 3, 'One-at-a-time drops keep earlier previews');
    const detail = {...side, name: 'detail.jpg'}, angle = {...side, name: 'angle.jpg'};
    multiple.events.drop(event([detail, angle]));
    assert.equal(multiple.previews.children.length, 5, 'A batch can be added to earlier drops');
    multiple.input.files = [{...side, name: 'label.jpg'}]; multiple.inputEvents.change();
    assert.equal(multiple.input.files.length, 6, 'The picker also adds to the multi-image selection');
    multiple.events.drop(event([front]));
    assert.equal(multiple.input.files.length, 6, 'Adding the exact same file again does not duplicate it');
    assert.equal(multiple.clear.hidden, false);
    assert.equal(single.input.files.length, 0, 'A color upload never changes the close-up input');
    single.events.drop(event([front, back]));
    assert.equal(single.input.validationMessage, 'Choose up to 1 image.');
    single.input.files = [front]; single.inputEvents.change();
    assert.equal(single.input.validationMessage, '', 'Selecting a valid replacement clears the error');
    assert.equal(single.previews.children.length, 1, 'The close-up gets an independent preview');
    single.input.files = [back]; single.inputEvents.change();
    assert.equal(single.input.files.length, 1, 'Single-image close-ups still replace their selection');
    assert.equal(single.previews.children[0].alt, back.name);
    multiple.clear.events.click();
    assert.equal(multiple.input.files.length, 0, 'Clear selection clears the actual upload payload');
    assert.equal(multiple.previews.children.length, 0);
    assert.equal(multiple.clear.hidden, true);
    multiple.input.files = Array.from({length: 11}, (_, index) => ({...front, name: index + '.png'})); multiple.inputEvents.change();
    assert.equal(multiple.input.validationMessage, 'Choose up to 10 images.');
    assert.equal(multiple.previews.children.length, 0, 'Invalid oversized selections do not allocate image previews');
    assert.equal(urls.size, 1, 'Replacing a selection releases its old previews and preserves other fields');
    oldImages[0].events.error();
    assert.equal(single.previews.hidden, false, 'Late image errors cannot affect another field');
    multiple.clear.events.click();
    multiple.input.files = [{name: 'large.jpg', size: 10485761}]; multiple.inputEvents.change();
    assert.equal(multiple.input.validationMessage, 'Choose images up to 10 MB each.');
    multiple.formEvents.reset();
    multiple.input.files = [];
    assert.equal(multiple.input.validationMessage, '');
    assert.equal(multiple.selection.textContent, 'No files selected');
    single.previews.children[0].events.error();
    assert.equal(single.previews.hidden, true);
    assert.equal(single.message.hidden, false, 'A failed image leaves the picker usable');
    assert.equal(urls.size, 0);
    multiple.input.files = [front]; multiple.inputEvents.change();
    const replaced = multiple.previews.children[0];
    multiple.clear.events.click();
    multiple.input.files = [back]; multiple.inputEvents.change();
    replaced.events.error();
    assert.equal(multiple.previews.hidden, false, 'A delayed error from a replaced image cannot hide the new preview');
    multiple.formEvents.reset();
    multiple.input.files = [];
    assert.equal(urls.size, 0, 'Reset releases the selected preview');
    assert.equal(multiple.message.hidden, false);
    multiple.input.files = [front]; multiple.inputEvents.change();
    windowEvents.pagehide.forEach(fn => fn());
    assert.equal(urls.size, 0, 'Leaving the page releases every preview');
    windowEvents.pageshow.forEach(fn => fn({persisted: true}));
    assert.equal(multiple.previews.children.length, 1, 'Browser back/forward restoration rebuilds the retained selection');
    multiple.clear.events.click();
    multiple.input.files = [{name: 'unsafe.svg', type: 'image/svg+xml', size: 100}]; multiple.inputEvents.change();
    assert.equal(multiple.previews.children.length, 0, 'Unsupported image formats are not rendered');
    disabled.events.drop(event([front]));
    assert.equal(disabled.input.files.length, 0);
    const textDrag = event([], ['text/plain']); multiple.events.dragenter(textDrag);
    assert.equal(textDrag.prevented, undefined, 'Text drags do not activate file handling');
    audio.events.drop(event([{name: 'interval.wav', type: 'audio/wav', size: 1024}]));
    assert.equal(audio.selection.textContent, 'interval.wav');
    assert.equal(audio.previews.children.length, 0, 'Audio uses the same picker without image previews');
    audio.input.files = [{name: 'large.wav', type: 'audio/wav', size: 2097153}]; audio.inputEvents.change();
    assert.equal(audio.input.validationMessage, 'Choose audio files up to 2 MB each.');
    console.log('Passed: sequential and batch file additions, complete upload payloads/previews, field isolation, clear/replace behavior, URL cleanup and limits.');
};
