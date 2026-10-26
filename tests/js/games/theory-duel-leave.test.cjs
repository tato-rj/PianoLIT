const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function setup() {
    const dialogs = []; const feedback = []; const renderedResults = []; const buttons = [{ disabled: false }, { disabled: false }];
    const resultElements = new Map();
    const results = { hidden: false, dataset: {}, querySelector: selector => {
        if (!resultElements.has(selector)) resultElements.set(selector, {});
        return resultElements.get(selector);
    } };
    const hud = { querySelector: selector => selector === '[data-duel-results]' ? results : {} };
    const window = { location: { href: '/duel' }, __duelConfig: { home: '/games' }, addEventListener() {} };
    const context = vm.createContext({ Date, Promise, setTimeout, clearInterval, setInterval: () => 1, window,
        document: { body: { classList: { add() {}, remove() {} } }, getElementById: () => hud,
            querySelector: selector => selector === '#progress-bar' ? undefined : { addEventListener() {} }, querySelectorAll: () => buttons, addEventListener() {} },
        $: () => ({ attr() {}, show() {}, removeAttr() {} }),
        DuelIdle: class { start() { return this; } stop() {} },
        DuelPresence: class { start() {} stop() {} },
        dialog: options => { dialogs.push(options); return { querySelector: () => ({ classList: { add() {} } }) }; },
        closeDialog() {},
        renderDuelResults: (root, state) => { root.hidden = false; renderedResults.push(state.status); },
        animateAnswerFeedback: (label, correct) => { feedback.push(correct); return () => {}; },
        DuelTransport: class { constructor() { this.offset = 0; } },
    });
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/DuelClient.js'), 'utf8')
        .replace(/^import.*;\n/gm, '').replace(/^export /gm, '');
    vm.runInContext(source + '\nglobalThis.Client = DuelClient;', context);
    const state = { id: 'room', seed: 'old-seed', role: 'host', revision: 1, status: 'playing', total: 4, sequence: 0,
        starts_at: new Date(Date.now() - 1000).toISOString(),
        players: [{ role: 'host', progress: 0 }, { role: 'guest', progress: 0 }] };
    let creates = 0;
    const client = new context.Client(state, () => { creates++; });
    client.render = () => {};
    return { client, state, dialogs, feedback, buttons, results, renderedResults, window, creates: () => creates };
}

test('refreshing a completed Duel restores results and subscribes for rematches without starting gameplay', () => {
    const { client, renderedResults, results, creates } = setup();
    let subscriptions = 0;
    client.state.status = 'finished';
    client.transport.subscribe = () => subscriptions++;
    client.begin();
    assert.equal(subscriptions, 1);
    client.showResults();
    assert.equal(renderedResults.at(-1), 'finished');
    assert.equal(results.hidden, false);
    assert.equal(client.hud.hidden, true);
    assert.equal(creates(), 0);
});

test('rematch requests share the existing room and reject duplicate clicks', async () => {
    const { client, state, results } = setup();
    client.state.status = 'finished';
    let resolveRequest; let calls = 0;
    client.transport.request = (route, data) => {
        assert.equal(route, '/room/rematch'); assert.equal(data.seed, 'old-seed'); calls++;
        return new Promise(resolve => { resolveRequest = resolve; });
    };
    const first = client.rematch();
    await Promise.resolve();
    await client.rematch();
    assert.equal(calls, 1);
    assert.equal(results.querySelector('[data-duel-rematch]').disabled, true);
    resolveRequest({ ...state, status: 'finished', revision: 2, players: [{ role: 'host', progress: 4, rematch: true }, { role: 'guest', progress: 4 }] });
    await first;
    await client.rematch();
    assert.equal(calls, 1);
});

test('a new match resets client state and restarts the same engine without replacing the connection', async () => {
    const { client, state, results, creates } = setup();
    let restarts = 0;
    client.state.status = 'finished'; client.finishPending = true; client.sequence = 4; client.localProgress = 4;
    client.game = { _restartDuel: () => restarts++ };
    client.answerIds.add('old-answer'); results.dataset.outcome = 'win';
    client.transport.subscribe = () => assert.fail('rematch must retain the existing subscription');
    const next = { ...state, seed: 'new-seed', status: 'playing', revision: 3, sequence: 0, checkpoint: null };
    client.receive(next);
    assert.equal(results.hidden, true);
    assert.equal(results.dataset.outcome, undefined);
    assert.equal(client.finishPending, false);
    assert.equal(client.sequence, 0); assert.equal(client.localProgress, 0); assert.equal(client.answerIds.size, 0);
    client.transport.request = async () => next;
    await client.tick(); await client.tick();
    assert.equal(restarts, 1); assert.equal(creates(), 0);
    client.receive({ ...state, status: 'finished', revision: 2 });
    assert.equal(client.state.seed, 'new-seed');
});

test('failed rematch remains on results and offers a retry with an error message', async () => {
    const { client, results } = setup(); client.state.status = 'finished';
    client.transport.request = async () => { throw new Error('Connection unavailable'); };
    await client.rematch();
    assert.equal(results.querySelector('[data-duel-rematch]').disabled, false);
    assert.equal(results.querySelector('[data-duel-rematch-error]').hidden, false);
    assert.equal(results.querySelector('[data-duel-rematch-error]').textContent, 'Connection unavailable');
});

test('late gameplay failures from the previous match do not interrupt a rematch', async () => {
    const { client, dialogs } = setup();
    let rejectRequest;
    client.transport.request = () => new Promise((resolve, reject) => { rejectRequest = reject; });
    const pending = client.enqueue('progress', { progress: 1, score: 3, sequence: 1 });
    await Promise.resolve();
    client.state = { ...client.state, seed: 'new-match' };
    rejectRequest(Object.assign(new Error('Previous match update'), { status: 409 }));
    await pending;
    assert.equal(dialogs.length, 0);
});

test('opponent leaving while the first finisher waits hides the results and shows the departure', () => {
    const { client, state, results, dialogs } = setup();
    client.showResults();
    assert.equal(results.hidden, false);
    client.receive({ ...state, revision: 2, status: 'cancelled', left_by: 'guest' });
    assert.equal(results.hidden, true);
    assert.equal(dialogs.at(-1).message, 'Opponent left the Duel.');
});

test('remote departure stops gameplay, explains who left, and blocks further gameplay mutations', () => {
    const { client, state, dialogs, results } = setup();
    let stops = 0;
    client.game = { _stopLoop: () => stops++, _stopGameTimer: () => stops++, _cancelTimers: () => stops++ };
    client.receive({ ...state, revision: 2, status: 'cancelled', left_by: 'guest' });
    assert.equal(stops, 3);
    assert.equal(results.hidden, true);
    assert.equal(dialogs.at(-1).message, 'Opponent left the Duel.');
    assert.equal(dialogs.at(-1).exit, true);
    client.enqueue = () => assert.fail('ended games must not submit more gameplay');
    client.progress(1, 3, {}); client.finished({ score: 3, accuracy: 100 });
});

test('refreshing an ended Duel shows the departure without subscribing to a closed channel', () => {
    const { client, dialogs } = setup();
    client.state.status = 'cancelled'; client.state.left_by = 'guest';
    client.transport.subscribe = () => assert.fail('closed Duels do not need a private subscription');
    client.begin();
    assert.equal(dialogs.at(-1).message, 'Opponent left the Duel.');
});

test('Leave Duel bypasses a stalled gameplay queue, rejects double clicks and navigates home after saving', async () => {
    const { client, state, buttons, window } = setup();
    client.queue = new Promise(() => {});
    let resolveRequest; let calls = 0; let disconnects = 0;
    client.transport = { echo: { disconnect: () => disconnects++ }, request: route => {
        assert.equal(route, '/room/leave'); calls++;
        return new Promise(resolve => { resolveRequest = resolve; });
    } };
    const first = client.leave(); const duplicate = client.leave();
    assert.equal(calls, 1);
    assert.equal(buttons.every(button => button.disabled), true);
    assert.equal(window.location.href, '/duel');
    resolveRequest({ ...state, revision: 2, status: 'cancelled', left_by: 'host' });
    await Promise.all([first, duplicate]);
    assert.equal(window.location.href, '/games');
    assert.equal(disconnects, 1);
});

test('a stale start response cannot start gameplay after the opponent leaves during countdown', async () => {
    const { client, state, creates } = setup();
    let resolveRequest;
    client.transport.request = () => new Promise(resolve => { resolveRequest = resolve; });
    const starting = client.tick();
    client.receive({ ...state, revision: 2, status: 'cancelled', left_by: 'guest' });
    resolveRequest(state);
    await starting;
    assert.equal(creates(), 0);
    assert.equal(client.state.status, 'cancelled');
});


test('only new answer feedback from this Duel opponent animates the label', () => {
    const { client, feedback } = setup();
    const event = { duel_id: 'room', role: 'guest', correct: true, id: 'first' };
    client.receiveAnswer({ ...event, role: 'host' });
    client.receiveAnswer({ ...event, duel_id: 'other-room' });
    client.receiveAnswer(event); client.receiveAnswer(event);
    client.receiveAnswer({ ...event, correct: false, id: 'second' });
    client.state.status = 'cancelled';
    client.receiveAnswer({ ...event, id: 'third' });
    assert.deepEqual(feedback, [true, false]);
});


test('idle expiry sends an authenticated leave and navigates immediately without waiting on gameplay requests', async () => {
    const { client, window } = setup();
    const calls = []; client.queue = new Promise(() => {});
    client.idle = { stop: () => calls.push('idle-stop') };
    client.presence = { stop: () => calls.push('presence-stop') };
    client.transport = { leaveOnExit: id => calls.push(`leave:${id}`), echo: { disconnect: () => calls.push('disconnect') } };
    await client.leave({ automatic: true }); await client.leave({ automatic: true });
    assert.equal(window.location.href, '/games');
    assert.deepEqual(calls, ['idle-stop', 'presence-stop', 'leave:room', 'disconnect']);
});

test('ready updates and countdown ticks cannot overwrite the idle confirmation', async () => {
    const { client, state, dialogs, creates } = setup();
    client.idle = { warning: true };
    client.receive({ ...state, revision: 2, status: 'ready' });
    assert.equal(dialogs.length, 0);
    client.state.status = 'countdown'; client.state.starts_at = new Date(Date.now() + 3000).toISOString();
    await client.tick(); assert.equal(dialogs.length, 0); assert.equal(creates(), 0);
});
