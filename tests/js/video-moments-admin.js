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
            focus() { focused = this; },
            classList: {add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)}
        };
    }
    const rows = {
        children: [],
        get lastElementChild() { return this.children[this.children.length - 1]; },
        appendChild(fragment) { this.children.push(fragment.row); },
        insertBefore(row, before) {
            this.children.splice(this.children.indexOf(row), 1);
            this.children.splice(this.children.indexOf(before), 0, row);
        }
    };
    function row(start = '', end = '') {
        const inputs = {start_time: input('start_time', start), end_time: input('end_time', end)};
        const buttons = {up: {}, down: {}};
        return {
            inputs, buttons,
            querySelector(selector) {
                return selector.includes('data-field') ? inputs[selector.match(/"([^"]+)"/)[1]] : buttons[selector.match(/"([^"]+)"/)[1]];
            },
            querySelectorAll() { return Object.values(inputs); },
            get previousElementSibling() { return rows.children[rows.children.indexOf(this) - 1]; },
            get nextElementSibling() { return rows.children[rows.children.indexOf(this) + 1]; },
            remove() { rows.children.splice(rows.children.indexOf(this), 1); }
        };
    }
    const first = row('00:12', '00:19.5');
    const second = row('00:28', '00:35.125');
    rows.children = [first, second];
    const alert = {hidden: true, textContent: ''};
    const events = {};
    const media = {};
    let preview;
    let attached;
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
        document: {querySelector: selector => selector === '[data-moments-editor]' ? form : media},
        window: {
            jQuery: inputs => ({inputmask: options => masks.push({inputs, options})}),
            Plyr: function (video, options) { preview = {video, options}; },
            VideoMoments: {markerOptions: () => ({markers: {enabled: true}}), attach: (player, video) => { attached = video; }}
        }
    });
    assert.strictEqual(preview.video, media);
    assert.strictEqual(preview.options.ratio, '16:9');
    assert.strictEqual(preview.options.markers.enabled, true);
    assert.strictEqual(attached, media, 'The admin preview reuses the shared guide');
    function action(name, targetRow) {
        const button = {getAttribute: () => name, closest: () => targetRow};
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
    action('up', third);
    assert.strictEqual(submit(), false, 'Reordering overlapping moments is allowed');
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
    console.log('Passed: admin Plyr reuses the shared guide; editor masks/prefills times, permits overlaps, and blocks invalid timestamps.');
};
