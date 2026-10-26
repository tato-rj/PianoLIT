const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function setup(reducedMotion = false) {
    const elements = new Map();
    let bursts = 0; let focus = 0; let sounds = 0;
    const root = { dataset: {}, hidden: true, style: { setProperty() {} },
        querySelector: selector => {
            if (!elements.has(selector)) elements.set(selector, { textContent: '', hidden: false, focus: () => focus++ });
            return elements.get(selector);
        } };
    const body = { append: node => { node.parentElement = body; }, classList: { add() {} } };
    const context = vm.createContext({ document: { body }, window: {
        matchMedia: () => ({ matches: reducedMotion }), Confetti: () => bursts++,
    }, chooseResultVariant: () => 0, GameAudio: { playFinalResults: () => sounds++ } });
    const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/duel/results.js'), 'utf8')
        .replace(/^import.*;\n/gm, '').replace(/^export /gm, '');
    vm.runInContext(source + '\nglobalThis.api = { duelOutcome, duelDuration, renderDuelResults };', context);
    return { ...context.api, root, body, bursts: () => bursts, focus: () => focus, sounds: () => sounds };
}
function state() {
    return { status: 'finished', role: 'host', total: 4, starts_at: '2026-09-26T12:00:00Z', players: [
        { role: 'host', progress: 4, score: 20, result: { accuracy: 80 }, finished_at: '2026-09-26T12:01:05Z' },
        { role: 'guest', progress: 4, score: 10, result: { accuracy: 100 }, finished_at: '2026-09-26T12:00:30Z' },
    ] };
}

test('both perspectives agree on the higher score winner regardless of finishing order', () => {
    const { duelOutcome } = setup(); const match = state();
    assert.equal(duelOutcome(match).kind, 'win');
    match.role = 'guest'; match.players.reverse();
    assert.equal(duelOutcome(match).kind, 'loss');
    assert.equal(duelOutcome(match).winner, 'host');
});
test('accuracy breaks tied scores; exact ties including zero scores are draws', () => {
    const { duelOutcome } = setup(); const match = state();
    match.players[0].score = 10;
    assert.equal(duelOutcome(match).reason, 'accuracy');
    assert.equal(duelOutcome(match).winner, 'guest');
    match.players[0].result.accuracy = 100;
    assert.equal(duelOutcome(match).kind, 'draw');
    match.players.forEach(player => { player.score = 0; player.result.accuracy = 0; });
    assert.equal(duelOutcome(match).kind, 'draw');
});
test('first finisher waits without announcing a winner and then reveals both saved results once', () => {
    const api = setup(); const match = state();
    const guest = { ...match.players[1] };
    match.status = 'playing'; match.players[1].finished_at = null; match.players[1].result = null;
    api.renderDuelResults(api.root, match);
    assert.equal(api.root.dataset.outcome, 'waiting');
    assert.equal(api.root.parentElement, api.body);
    assert.equal(api.root.hidden, false);
    assert.equal(api.root.querySelector('[data-duel-result-home]').hidden, true);
    assert.equal(api.root.querySelector('[data-duel-result-rematch]').hidden, true);
    assert.equal(api.bursts(), 0);
    match.status = 'finished'; match.players[1] = guest;
    api.renderDuelResults(api.root, match);
    api.renderDuelResults(api.root, match); // duplicate state / reconnect
    assert.equal(api.bursts(), 1);
    assert.equal(api.sounds(), 1);
    assert.equal(api.root.querySelector('[data-duel-result-rematch]').hidden, false);
    assert.equal(api.focus(), 2);
    assert.equal(api.root.querySelector('[data-duel-result-leave]').hidden, true);
    assert.equal(api.root.querySelector('[data-duel-metric="you-time"]').textContent, '01:05');
    assert.equal(api.root.querySelector('[data-duel-metric="opponent-accuracy"]').textContent, '100%');
});
test('refresh restores the losing perspective with encouragement and no winner confetti', () => {
    const api = setup(); const match = state(); match.role = 'guest';
    api.renderDuelResults(api.root, match);
    assert.equal(api.root.dataset.outcome, 'loss');
    assert.equal(api.root.querySelector('span[name="score"]').textContent, 10);
    assert.match(api.root.querySelector('[data-duel-outcome]').textContent, /Opponent won/);
    assert.match(api.root.querySelector('[data-duel-result-title]').textContent, /comeback/);
    assert.equal(api.bursts(), 0);
    assert.equal(api.sounds(), 0);
});

test('completion cue respects muted settings and plays for draws and losses once', () => {
    for (const kind of ['loss', 'draw', 'muted']) {
        const api = setup(); const match = state(); match.role = 'guest';
        if (kind === 'draw') { match.players[0].score = 10; match.players[0].result.accuracy = 100; }
        match.options = { sound: kind !== 'muted' };
        const finished = structuredClone(match);
        match.status = 'playing'; match.players[0].finished_at = null; match.players[0].result = null;
        api.renderDuelResults(api.root, match);
        api.renderDuelResults(api.root, finished);
        api.renderDuelResults(api.root, finished);
        assert.equal(api.sounds(), kind === 'muted' ? 0 : 1);
    }
});

test('rematch acceptance shows who is waiting and prevents another submission', () => {
    const api = setup(); const match = state(); match.players[1].rematch = true;
    api.renderDuelResults(api.root, match);
    assert.match(api.root.querySelector('[data-duel-rematch-label]').textContent, /Opponent wants/);
    assert.equal(api.root.querySelector('[data-duel-rematch]').disabled, false);
    match.players[0].rematch = true;
    api.renderDuelResults(api.root, match);
    assert.equal(api.root.querySelector('[data-duel-rematch]').disabled, true);
    assert.match(api.root.querySelector('[data-duel-rematch-label]').textContent, /Waiting/);
    assert.equal(api.sounds(), 0);
});
test('reduced motion suppresses confetti and missing finish times never invent a duration', () => {
    const api = setup(true); api.renderDuelResults(api.root, state());
    assert.equal(api.bursts(), 0);
    assert.equal(api.duelDuration(state().starts_at, null), '—');
    assert.equal(api.duelDuration(state().starts_at, 'invalid'), '—');
});
