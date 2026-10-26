const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../../resources/js/games/duel/transport.js'), 'utf8').replace(/^export /gm, '');
const flush = () => new Promise(resolve => setImmediate(resolve));
function setup(responses) {
    const timers = new Map(), requests = [], updates = [], answers = [], errors = [];
    let serial = 0, connections = 0, disconnections = 0;
    const context = vm.createContext({Date, Error, Object, JSON, Set, Math, AbortController,
        window: {}, document: {querySelector: () => ({content: 'session-csrf'})},
        setTimeout: (callback, delay) => {timers.set(++serial, {callback, delay}); return serial;},
        clearTimeout: id => timers.delete(id),
        fetch: async (url, options) => {
            requests.push({url, options});
            const next = responses.shift(); if (next instanceof Error) throw next;
            return {ok: !next.status, status: next.status || 200, json: async () => next.body || next};
        },
    });
    vm.runInContext(source + '\nglobalThis.Transport = DuelTransport;', context);
    const transport = new context.Transport({base: '/duels', poll_ms: 800});
    const subscribe = id => transport.subscribe(id, {update: state => updates.push(state), answer: event => answers.push(event),
        connected: () => connections++, disconnected: () => disconnections++, error: error => errors.push(error)});
    const tick = async () => {const [id, timer] = [...timers].find(([, timer]) => timer.delay < 10000); timers.delete(id); timer.callback(); await flush();};
    return {transport, requests, updates, answers, timers, errors, subscribe, tick, connections: () => connections, disconnections: () => disconnections};
}

test('snapshots use same-origin sessions, emit feedback once and stop cleanly', async () => {
    const state = {id: 'room', revision: 3, players: [{answer: {id: 'answer', role: 'guest', correct: true}}]};
    const suite = setup([state, state]); suite.subscribe('room'); await flush(); await suite.tick();
    assert.equal(suite.updates.length, 2); assert.equal(suite.answers.length, 1); assert.equal(suite.connections(), 1);
    assert.equal(suite.requests[0].options.credentials, 'same-origin');
    assert.equal(suite.requests[0].options.headers['X-CSRF-TOKEN'], 'session-csrf');
    suite.transport.disconnect(); assert.equal(suite.timers.size, 0);
});

test('temporary disconnects back off and automatically recover', async () => {
    const suite = setup([{id: 'room'}, new Error('offline'), {id: 'room'}]);
    suite.subscribe('room'); await flush(); await suite.tick();
    assert.equal(suite.disconnections(), 1); assert.equal([...suite.timers.values()][0].delay, 1000);
    await suite.tick(); assert.equal(suite.connections(), 2); assert.equal(suite.updates.length, 2);
    suite.transport.disconnect();
});

test('expired participant sessions stop retries and surface the server error', async () => {
    const suite = setup([{status: 403, body: {message: 'Not a participant'}}]);
    suite.subscribe('room'); await flush();
    assert.equal(suite.errors[0].status, 403); assert.equal(suite.errors[0].message, 'Not a participant');
    assert.equal(suite.timers.size, 0);
});
