const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup() {
    let active = null;
    const inputs = Array.from({ length: 4 }, () => {
        const listeners = {};
        return {
            value: '',
            selected: false,
            addEventListener(type, handler) { listeners[type] = handler; },
            focus() { active = this; listeners.focus?.(); },
            select() { this.selected = true; },
            type(text) { this.value = text; listeners.input(); },
            paste(text) {
                let prevented = false;
                listeners.paste({ clipboardData: { getData: () => text }, preventDefault() { prevented = true; } });
                assert.equal(prevented, true);
            },
            key(key) {
                let prevented = false;
                listeners.keydown({ key, preventDefault() { prevented = true; } });
                return prevented;
            },
        };
    });
    const context = vm.createContext({});
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/codeInputs.js'), 'utf8');
    vm.runInContext(source.replace(/^export /gm, '') + '\nglobalThis.bind = bindDuelCodeInputs;', context);
    const code = context.bind({ querySelectorAll: () => inputs });
    return { inputs, code, active: () => active };
}

test('typing digits advances through all four boxes and preserves leading zeroes', () => {
    const { inputs, code, active } = setup();
    code.focus();
    assert.equal(active(), inputs[0]);
    for (const [index, digit] of [...'0123'].entries()) {
        inputs[index].type(digit);
        assert.equal(active(), inputs[Math.min(index + 1, 3)]);
    }
    assert.equal(code.value(), '0123');
});

test('non-numeric input stays in the same box and revisiting a digit selects it for replacement', () => {
    const { inputs, code, active } = setup();
    code.focus();
    inputs[0].type('x');
    assert.equal(code.value(), '');
    assert.equal(active(), inputs[0]);
    inputs[0].type('1');
    inputs[0].focus();
    assert.equal(inputs[0].selected, true);
    inputs[0].type('9');
    assert.equal(code.value(), '9');
    assert.equal(active(), inputs[1]);
});

test('backspace on an empty box clears and focuses the previous digit, with safe boundaries', () => {
    const { inputs, code, active } = setup();
    inputs[0].type('1');
    inputs[1].type('2');
    assert.equal(inputs[2].key('Backspace'), true);
    assert.equal(active(), inputs[1]);
    assert.equal(code.value(), '1');
    assert.equal(inputs[0].key('Backspace'), false);
    inputs[0].value = '';
    assert.equal(inputs[0].key('Backspace'), false);
});

test('arrow keys move between digits without changing the code', () => {
    const { inputs, code, active } = setup();
    inputs[0].paste('1234');
    assert.equal(inputs[3].key('ArrowLeft'), true);
    assert.equal(active(), inputs[2]);
    assert.equal(inputs[2].key('ArrowRight'), true);
    assert.equal(active(), inputs[3]);
    assert.equal(inputs[3].key('ArrowRight'), false);
    assert.equal(inputs[0].key('ArrowLeft'), false);
    assert.equal(code.value(), '1234');
});

test('pasting a complete code in any box fills all four digits and strips separators', () => {
    const { inputs, code, active } = setup();
    inputs[2].paste('0 1-2 3');
    assert.equal(code.value(), '0123');
    assert.equal(active(), inputs[3]);
});

test('partial pasted codes start at the current box and stop at the final box', () => {
    const { inputs, code, active } = setup();
    inputs[0].type('1');
    inputs[1].paste('23');
    assert.equal(code.value(), '123');
    assert.equal(active(), inputs[3]);
    inputs[3].paste('45');
    assert.equal(code.value(), '1234');
});

test('autofilled input distributes a full code and reopening focuses the first empty digit', () => {
    const { inputs, code, active } = setup();
    inputs[0].type('1');
    inputs[1].type('2');
    code.focus();
    assert.equal(active(), inputs[2]);
    inputs[0].type('0987');
    assert.equal(code.value(), '0987');
    code.focus();
    assert.equal(active(), inputs[0]);
});
