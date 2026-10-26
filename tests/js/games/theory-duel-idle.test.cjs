const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup() {
    let now = 0; let expired = 0; let resumed = 0; let closed = 0;
    const prompts = []; const events = new Map(); const intervals = new Set();
    const button = { addEventListener() {}, removeEventListener() {}, focus() {} };
    const context = vm.createContext({ Date: { now: () => now },
        document: { addEventListener: (name, handler) => events.set(name, handler), removeEventListener: name => events.delete(name), querySelector: () => button },
        setInterval: callback => { intervals.add(callback); return callback; }, clearInterval: callback => intervals.delete(callback),
        dialog: options => prompts.push(options), closeDialog: () => closed++,
    });
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/idle.js'), 'utf8').replace(/^import.*;\n/gm, '').replace(/^export /gm, '');
    vm.runInContext(source + '\nglobalThis.Idle = DuelIdle;', context);
    const idle = new context.Idle({ onExpire: () => expired++, onResume: () => resumed++ }).start();
    return { idle, prompts, events, intervals, at: value => { now = value; }, expired: () => expired, resumed: () => resumed, closed: () => closed };
}

test('30 seconds of inactivity prompts with 10 seconds, then expiry fires exactly once', () => {
    const s = setup(); s.at(29999); s.idle.check(); assert.equal(s.prompts.length, 0);
    s.at(30000); s.idle.check(); assert.equal(s.prompts.at(-1).seconds, 10);
    s.at(31000); s.idle.check(); assert.equal(s.prompts.at(-1).seconds, 9);
    s.at(39000); s.idle.check(); assert.equal(s.prompts.at(-1).seconds, 1); assert.equal(s.expired(), 0);
    s.at(40000); s.idle.check(); s.idle.check(); assert.equal(s.expired(), 1);
    assert.equal(s.events.size, 0); assert.equal(s.intervals.size, 0);
});

test('real activity resets inactivity while synthetic activity cannot keep a Duel alive', () => {
    const s = setup(); s.at(29000); s.events.get('pointerdown')({ isTrusted: true, type: 'pointerdown' });
    s.at(30000); s.idle.check(); assert.equal(s.prompts.length, 0);
    s.at(58000); s.events.get('pointermove')({ isTrusted: false, type: 'pointermove' });
    s.at(59000); s.idle.check(); assert.equal(s.prompts.at(-1).seconds, 10);
});

test('warning requires explicit confirmation and can recur after another idle period', () => {
    const s = setup(); s.at(30000); s.idle.check();
    s.at(35000); s.events.get('pointermove')({ isTrusted: true, type: 'pointermove' });
    assert.equal(s.idle.warning, true); assert.equal(s.idle.lastActivity, 0);
    s.idle.confirm(); assert.equal(s.resumed(), 1); assert.equal(s.idle.warning, false);
    s.at(64999); s.idle.check(); assert.equal(s.idle.warning, false);
    s.at(65000); s.idle.check(); assert.equal(s.idle.warning, true); assert.equal(s.prompts.at(-1).seconds, 10);
});

test('background timer delays do not restart the countdown or allow a late confirmation', () => {
    const s = setup(); s.at(30000); s.idle.check();
    s.at(45000); s.idle.confirm(); assert.equal(s.expired(), 1); assert.equal(s.resumed(), 0);
    const hidden = setup(); hidden.at(60000); hidden.events.get('visibilitychange')(); assert.equal(hidden.expired(), 1);
});

test('stopping after match completion prevents prompts and automatic departure', () => {
    const s = setup(); s.idle.stop(); s.at(60000); s.idle.check();
    assert.equal(s.prompts.length, 0); assert.equal(s.expired(), 0);
});

test('arrow keys cannot control Note Python through the idle modal', () => {
    const s = setup(); s.at(30000); s.idle.check(); let blocked = 0;
    s.events.get('keydown')({ isTrusted: true, type: 'keydown', key: 'ArrowRight', preventDefault: () => blocked++, stopImmediatePropagation: () => blocked++ });
    assert.equal(blocked, 2); assert.equal(s.idle.warning, true);
});

test('automatic departure uses a CSRF-protected beacon with keepalive fallback', () => {
    const requests = []; const beacons = []; let accepted = true;
    const context = vm.createContext({ FormData,
        document: { querySelector: () => ({ content: 'csrf' }) },
        navigator: { sendBeacon: (url, body) => { beacons.push({ url, body }); return accepted; } },
        fetch: (url, options) => { requests.push({ url, options }); return Promise.resolve(); },
    });
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/transport.js'), 'utf8').replace(/^import.*;\n/gm, '').replace(/^export /gm, '');
    vm.runInContext(source + '\nglobalThis.Transport = DuelTransport;', context);
    const transport = new context.Transport({ base: '/duels' });
    transport.leaveOnExit('room'); assert.equal(beacons[0].url, '/duels/room/leave'); assert.equal(beacons[0].body.get('_token'), 'csrf');
    accepted = false; transport.leaveOnExit('room'); assert.equal(requests[0].options.keepalive, true);
    assert.equal(requests[0].options.credentials, 'same-origin'); assert.equal(requests[0].options.body.get('_token'), 'csrf');
});
