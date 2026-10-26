const assert = require('node:assert/strict');
const {test} = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup({min = 2, max = 12, value = '4', thumbWidth} = {}) {
    const output = {style: {}};
    const listeners = {};
    const range = {min, max, value, dataset: {},
        closest: () => ({querySelector: () => output}),
        addEventListener: (event, listener) => {
            (listeners[event] ||= []).push(listener);
        }};
    if (thumbWidth) range.dataset.valueThumbWidth = thumbWidth;
    let ready;
    const document = {
        querySelectorAll: selector => {
            assert.equal(selector, '.music-games input[data-value-thumb-range]');
            return [range];
        },
        addEventListener: (event, listener) => { assert.equal(event, 'DOMContentLoaded'); ready = listener; },
    };
    const context = vm.createContext({document});
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../../resources/js/games/valueThumbRange.js'), 'utf8'), context);
    return {range, output, listeners, ready};
}

test('numbered range handles stay inside the track at both limits and support wide BPM handles', () => {
    for (const thumbWidth of [52, 80]) {
        const {range, output, listeners, ready} = setup({thumbWidth});
        ready();
        range.value = '2';
        listeners.input[0]();
        assert.equal(output.textContent, '2');
        assert.equal(output.style.left, `calc(0% + ${thumbWidth / 2}px)`);
        range.value = '12';
        listeners.change[0]();
        assert.equal(output.textContent, '12');
        assert.equal(output.style.left, `calc(100% + ${-thumbWidth / 2}px)`);
    }
});

test('initialization and restored settings synchronize the visible value without duplicate listeners', () => {
    const {range, output, listeners, ready} = setup();
    ready();
    assert.equal(output.textContent, '4');
    assert.equal(output.style.left, 'calc(20% + 15.6px)');
    range.value = '7';
    // Saved settings dispatch input after restoring the range's native value.
    listeners.input[0]();
    assert.equal(output.textContent, '7');
    assert.equal(output.style.left, 'calc(50% + 0px)');
    ready();
    assert.equal(listeners.input.length, 1);
    assert.equal(listeners.change.length, 1);
});
