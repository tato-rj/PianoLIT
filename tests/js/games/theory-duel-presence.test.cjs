const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup(beaconAccepted = true) {
    const events = new Map(); const intervals = new Map(); const requests = []; const beacons = []; const fallbacks = []; const errors = []; const states = [];
    let random = 0; let timerId = 0;
    const context = vm.createContext({ Uint8Array, FormData,
        window: { crypto: { getRandomValues: bytes => bytes.fill(++random) },
            addEventListener: (name, handler) => events.set(name, handler), removeEventListener: name => events.delete(name) },
        document: { querySelector: () => ({ content: 'csrf-secret' }) },
        navigator: { sendBeacon: (url, body) => { beacons.push({ url, body }); return beaconAccepted; } },
        fetch: (url, options) => { fallbacks.push({ url, options }); return Promise.resolve(); },
        setInterval: handler => { intervals.set(++timerId, handler); return timerId; }, clearInterval: id => intervals.delete(id),
    });
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/presence.js'), 'utf8').replace(/^export /gm, '');
    vm.runInContext(source + '\nglobalThis.Presence = DuelPresence;', context);
    const transport = { config: { base: '/duels' }, request: async (url, data) => { requests.push({ url, data }); return { status: 'playing' }; } };
    const presence = new context.Presence(transport, 'room', state => states.push(state), error => errors.push(error));
    return { presence, transport, events, intervals, requests, beacons, fallbacks, errors, states };
}

test('page exit sends one authenticated departure and stops heartbeats without immediately leaving', async () => {
    const { presence, events, intervals, requests, beacons } = setup();
    await presence.start();
    const id = presence.connectionId;
    await presence.heartbeat();
    assert.equal(requests.at(-1).url, '/room/heartbeat');
    assert.equal(requests.at(-1).data.connection_id, id);
    events.get('pagehide')(); events.get('pagehide')();
    assert.equal(beacons.length, 1);
    assert.equal(beacons[0].url, '/duels/room/depart');
    assert.equal(beacons[0].body.get('_token'), 'csrf-secret');
    assert.equal(beacons[0].body.get('connection_id'), id);
    assert.equal(intervals.size, 0);
    assert.ok(requests.every(request => !request.url.endsWith('/leave')));
    const before = requests.length; await presence.heartbeat(); assert.equal(requests.length, before);
});

test('back-forward restoration registers a fresh connection and resumes heartbeats', async () => {
    const { presence, events, intervals, requests } = setup();
    await presence.start(); const old = presence.connectionId;
    events.get('pagehide')();
    await events.get('pageshow')({ persisted: true });
    assert.notEqual(presence.connectionId, old);
    assert.equal(requests.at(-1).url, '/room/connect');
    assert.equal(intervals.size, 1);
    assert.equal(presence.hidden, false);
});

test('failed beacon uses a same-origin keepalive request with the CSRF token', async () => {
    const { presence, events, fallbacks } = setup(false);
    await presence.start(); events.get('pagehide')();
    assert.equal(fallbacks.length, 1);
    assert.equal(fallbacks[0].options.keepalive, true);
    assert.equal(fallbacks[0].options.credentials, 'same-origin');
    assert.equal(fallbacks[0].options.body.get('_token'), 'csrf-secret');
});

test('explicit leave or a finished Duel removes exit detection and heartbeat timers', async () => {
    const { presence, events, intervals, beacons } = setup();
    await presence.start(); presence.accept({ status: 'finished' }); presence.depart();
    assert.equal(events.size, 0); assert.equal(intervals.size, 0); assert.equal(beacons.length, 0);
});

test('a failed initial registration is retried instead of silently losing browser-close detection', async () => {
    const { presence, transport, errors, requests } = setup();
    const original = transport.request; let fail = true;
    transport.request = (...args) => { if (fail) { fail = false; return Promise.reject(new Error('network')); } return original(...args); };
    await presence.start(); assert.equal(errors.length, 1);
    await presence.heartbeat(); assert.equal(presence.claimed, true); assert.equal(requests.at(-1).url, '/room/connect');
});
