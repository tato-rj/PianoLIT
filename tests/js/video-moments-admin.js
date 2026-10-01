const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    let focused;
    const masks = [];
    function input(field, value = '') {
        const classes = new Set();
        return {
            value, name: '', attrs: {}, message: '', required: field === 'start_time',
            getAttribute: key => key === 'data-field' ? field : null,
            setAttribute(key, value) { this.attrs[key] = value; },
            removeAttribute(key) { delete this.attrs[key]; },
            setCustomValidity(message) { this.message = message; },
            focus() { focused = this; doc.activeElement = this; },
            classList: {add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)}
        };
    }
    const rows = {
        children: [],
        get lastElementChild() { return this.children[this.children.length - 1]; },
        appendChild(fragment) {
            const node = fragment.row || fragment;
            const index = this.children.indexOf(node);
            if (index >= 0) this.children.splice(index, 1);
            this.children.push(node);
        },
        contains(node) { return this.children.some(row => row.contains(node)); },
        insertBefore(row, before) {
            this.children.splice(this.children.indexOf(row), 1);
            this.children.splice(this.children.indexOf(before), 0, row);
        }
    };
    function row(start = '', end = '') {
        const inputs = {start_time: input('start_time', start), end_time: input('end_time', end)};
        const number = {};
        const result = {
            inputs,
            querySelector(selector) {
                return selector.includes('data-field') ? inputs[selector.match(/"([^"]+)"/)[1]] : number;
            },
            querySelectorAll() { return Object.values(inputs); },
            contains(node) { return Object.values(inputs).includes(node) || node === this; },
            get previousElementSibling() { return rows.children[rows.children.indexOf(this) - 1]; },
            get nextElementSibling() { return rows.children[rows.children.indexOf(this) + 1]; },
            remove() { rows.children.splice(rows.children.indexOf(this), 1); }
        };
        Object.values(inputs).forEach(input => { input.closest = () => result; });
        return result;
    }
    const first = row('00:12', '00:19.5');
    const second = row('00:28', '00:35.125');
    rows.children = [first, second];
    const alert = {hidden: true, textContent: ''};
    const events = {};
    const media = {};
    let preview;
    let attached;
    const doc = {querySelector: selector => selector === '[data-moments-editor]' ? form : media};
    const form = {
        querySelector(selector) {
            if (selector === '[data-moment-rows]') return rows;
            if (selector === '[data-moment-alert]') return alert;
            return {content: {cloneNode: () => ({row: row()})}};
        },
        addEventListener(type, callback) { events[type] = callback; },
        reportValidity() { return rows.children.every(row => Object.values(row.inputs).every(input => !input.message && (!input.required || input.value))); }
    };
    vm.runInNewContext(fs.readFileSync('resources/js/views/video-moments-admin.js', 'utf8'), {
        document: doc,
        window: {
            jQuery: inputs => ({inputmask: (options, value) => {
                if (options === 'setvalue') inputs.value = value;
                else masks.push({inputs, options});
            }}),
            Plyr: function (video, options) { preview = {video, options}; },
            VideoMoments: {markerOptions: () => ({markers: {enabled: true}}), attach: (player, video) => { attached = video; }}
        }
    });
    assert.strictEqual(preview.video, media);
    assert.strictEqual(preview.options.ratio, '16:9');
    assert.strictEqual(preview.options.markers.enabled, true);
    assert.strictEqual(attached, media, 'The admin preview reuses the shared guide');
    function action(name, targetRow, field, step) {
        const button = {getAttribute: key => ({'data-moment-action': name, 'data-time-field': field, 'data-time-step': step})[key], closest: () => targetRow};
        events.click({target: {closest: () => button}});
    }
    function submit() {
        let prevented = false;
        events.submit({preventDefault() { prevented = true; }});
        return prevented;
    }
    assert.strictEqual(alert.hidden, true);
    assert.strictEqual(masks.length, 2);
    action('add');
    const third = rows.lastElementChild;
    assert.strictEqual(third.inputs.start_time.value, '00:35.125');
    assert.strictEqual(focused, third.inputs.start_time);
    assert.strictEqual(masks.length, 3, 'New rows receive the same mask');
    assert.strictEqual(third.inputs.start_time.name, 'moments[2][start_time]');
    assert.strictEqual(submit(), false, 'Touching endpoints are valid');
    third.inputs.start_time.value = '00:34.999';
    events.input();
    assert.strictEqual(alert.hidden, true);
    assert.strictEqual(submit(), false, 'Overlapping moments can be saved');
    third.inputs.start_time.value = '00:01';
    third.inputs.start_time.focus();
    events.focusout({target: third.inputs.start_time});
    assert.strictEqual(rows.children[0], third, 'Rows automatically sort by start time after editing');
    assert.strictEqual(focused, third.inputs.start_time, 'Sorting retains the focused field');
    action('delete', third);
    assert.strictEqual(alert.hidden, true, 'Deleting the conflicting range clears validation');
    second.inputs.start_time.value = '00:19.5';
    masks[1].options.onKeyValidation();
    assert.strictEqual(alert.hidden, true, 'Inputmask keyboard callbacks refresh validation without native input events');
    assert.strictEqual(submit(), false);
    second.inputs.end_time.value = '00:18';
    assert.strictEqual(submit(), true, 'End before start is invalid');
    second.inputs.end_time.value = '00:3_';
    assert.strictEqual(submit(), true, 'Incomplete masks are not silently cleared');
    second.inputs.end_time.value = '';
    events.input();
    assert.strictEqual(submit(), false, 'An optional end can remain empty');
    action('add');
    assert.strictEqual(rows.lastElementChild.inputs.start_time.value, '', 'No end means no invented next start');
    rows.lastElementChild.inputs.start_time.value = '00:19.5';
    assert.strictEqual(submit(), false, 'Equal starts can be saved; display order breaks ties');
    rows.children = [];
    action('add');
    assert.strictEqual(rows.lastElementChild.inputs.start_time.value, '00:00');
    rows.lastElementChild.inputs.end_time.value = '120:00.125';
    action('add');
    assert.strictEqual(rows.lastElementChild.inputs.start_time.value, '120:00.125');
    assert.strictEqual(submit(), false, 'Long video times and milliseconds are retained');
    const stepped = rows.children[0];
    stepped.inputs.start_time.value = '00:59.125';
    action('step', stepped, 'start_time', 1);
    assert.strictEqual(stepped.inputs.start_time.value, '01:00.125');
    action('step', stepped, 'start_time', -1);
    assert.strictEqual(stepped.inputs.start_time.value, '00:59.125');
    stepped.inputs.start_time.value = '00:00';
    action('step', stepped, 'start_time', -1);
    assert.strictEqual(stepped.inputs.start_time.value, '00:00', 'Time cannot become negative');
    stepped.inputs.end_time.value = '';
    action('step', stepped, 'end_time', 1);
    assert.strictEqual(stepped.inputs.end_time.value, '00:01', 'An empty end steps from the start');
    events.keydown({target: stepped.inputs.end_time, key: 'ArrowDown', preventDefault() {}, stopPropagation() {}});
    assert.strictEqual(stepped.inputs.end_time.value, '00:00');
    stepped.inputs.start_time.value = '00:3_';
    action('step', stepped, 'start_time', 1);
    assert.strictEqual(stepped.inputs.start_time.value, '00:3_', 'Incomplete typed values are preserved for correction');
    console.log('Passed: admin preview, masked time entry, chronological sorting, one-second chevrons/keyboard steps, boundaries and validation.');
};
