const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

module.exports = function () {
    const events = {}, rowEvents = {}, imageEvents = {}, cancelEvents = {};
    const status = {textContent: 'All changes saved'};
    const add = {addEventListener(name, fn) { this[name] = fn; }, focus() { this.focused = true; }};
    let cropperOptions;
    function question(index) {
        const heading = {}, legend = {}, input = {name: 'questions[' + index + '][0]', value: 'Saved question ' + index, focus() { this.focused = true; }};
        const row = {dataset: {questionIndex: String(index)}, heading, legend, input,
            querySelector(selector) { return {'[data-question-number]': heading, '[data-question-legend]': legend, '[data-remove-question]': remove, input}[selector]; },
            remove() { rows.items = rows.items.filter(item => item !== this); }};
        const remove = {setAttribute(name, value) { this[name] = value; }, closest() { return row; }};
        row.removeButton = remove;
        return row;
    }
    const rows = {items: [question(0), question(1), question(2)],
        querySelectorAll() { return this.items; }, addEventListener(name, fn) { rowEvents[name] = fn; },
        insertAdjacentHTML(position, html) { this.items.push(question(Number(html.match(/data-question-index="(\d+)"/)[1]))); },
        get lastElementChild() { return this.items.at(-1); }};
    const image = {name: '', files: [], value: '', addEventListener(name, fn) { imageEvents[name] = fn; }};
    const cancel = {addEventListener(name, fn) { cancelEvents[name] = fn; }};
    const form = {addEventListener(name, fn) { events[name] = fn; }, querySelector(selector) {
        return {'[data-quiz-questions]': rows, '[data-quiz-question-template]': {innerHTML: '<fieldset data-question-index="__INDEX__">'}, '[data-add-question]': add, '[data-save-status]': status, '#image-input': image, '#cancel-button': cancel}[selector];
    }};
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-quiz-editor.js', 'utf8'), {
        document: {querySelector() { return form; }}, window: {SimpleCropper: class {constructor(options) { cropperOptions = options; } create() {}}}
    });
    assert.equal(cropperOptions.submitButton, '#quiz-editor [data-save-quiz]', 'Cropping only disables the quiz save action.');
    rowEvents.click({target: {closest() { return rows.items[1].removeButton; }}});
    assert.deepEqual(rows.items.map(row => row.dataset.questionIndex), ['0', '2']);
    add.click();
    assert.deepEqual(rows.items.map(row => row.dataset.questionIndex), ['0', '2', '3'], 'Adding after removing the middle question never reuses another saved index.');
    assert.equal(rows.items[1].input.name, 'questions[2][0]');
    assert.equal(rows.items[1].input.value, 'Saved question 2', 'Renumbering visible labels preserves saved fields and values.');
    assert.equal(rows.items[1].heading.textContent, 'Question 2');
    assert.equal(rows.items[2].removeButton['aria-label'], 'Remove question 3');
    assert(rows.items[2].input.focused);
    assert.equal(status.textContent, 'Unsaved changes');
    image.files = [{name: 'cover.jpg'}]; imageEvents.change();
    assert.equal(image.name, 'cover_image');
    cancelEvents.click();
    assert.equal(image.name, '', 'Canceled crops do not submit an unwanted replacement.');
    assert.equal(image.value, '');
    image.files = []; imageEvents.change();
    assert.equal(image.name, '');
    console.log('Passed: quiz question insertion/removal, unique indices, retained values, accessible numbering and canceled cover replacement.');
};
