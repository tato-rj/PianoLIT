const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup() {
    const classes = new Set(['fw-bold']); const listeners = new Map(); const timers = new Map();
    let nextTimer = 0;
    const label = { offsetWidth: 100,
        classList: { add: (...names) => names.forEach(name => classes.add(name)), remove: (...names) => names.forEach(name => classes.delete(name)) },
        addEventListener: (name, callback) => listeners.set(name, callback),
        removeEventListener: name => listeners.delete(name),
    };
    const context = vm.createContext({
        getComputedStyle: () => ({ animationDuration: '1.3s', animationDelay: '0s' }),
        setTimeout: callback => { timers.set(++nextTimer, callback); return nextTimer; },
        clearTimeout: id => timers.delete(id),
    });
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/answerFeedback.js'), 'utf8').replace(/^export /gm, '');
    vm.runInContext(source + '\nglobalThis.animate = animateAnswerFeedback;', context);
    return { label, classes, listeners, timers, animate: context.animate };
}

test('heartbeat applies outcome color only until animation completes', () => {
    for (const correct of [true, false]) {
        const { label, classes, listeners, timers, animate } = setup();
        animate(label, correct);
        assert.ok(classes.has('animate__heartBeat'));
        assert.ok(classes.has(correct ? 'text-green' : 'text-red'));
        listeners.get('animationend')({ target: label });
        assert.deepEqual([...classes], ['fw-bold']);
        assert.equal(listeners.size, 0); assert.equal(timers.size, 0);
    }
});

test('rapid subsequent answers replace color and cancel the preceding cleanup timer', () => {
    const { label, classes, listeners, timers, animate } = setup();
    animate(label, true); animate(label, false);
    assert.equal(timers.size, 1);
    assert.equal(classes.has('text-green'), false);
    assert.ok(classes.has('text-red')); assert.ok(classes.has('animate__heartBeat'));
    listeners.get('animationend')({ target: {} });
    assert.ok(classes.has('text-red'), 'child animations cannot clear feedback');
    [...timers.values()][0]();
    assert.deepEqual([...classes], ['fw-bold']);
});

test('leaving or cancelling animation restores the original name style immediately', () => {
    const { label, classes, timers, animate } = setup();
    const cancel = animate(label, false); cancel();
    assert.deepEqual([...classes], ['fw-bold']); assert.equal(timers.size, 0);
});
