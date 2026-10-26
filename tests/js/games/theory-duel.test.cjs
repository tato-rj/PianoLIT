const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const dir = path.join(__dirname, '../../../resources/js/games');
function source(file) { return fs.readFileSync(path.join(dir, file), 'utf8').replace(/^import[\s\S]*?;\n/gm, '').replace(/^export /gm, ''); }
const context = vm.createContext({ Math, queueMicrotask, $: () => ({ hide() {} }) });
vm.runInContext(source('duel/random.js') + '\nglobalThis.seededRandom = seededRandom;', context);
const random = context.seededRandom;

test('same seed and round produce identical challenge randomness, independent of cosmetic Math.random calls', () => {
    const host = random('duel-seed', 3); const guest = random('duel-seed', 3);
    for (let i = 0; i < 100; i++) { Math.random(); Math.random(); assert.equal(host(), guest()); }
    assert.notEqual(random('duel-seed', 3)(), random('duel-seed', 4)());
    assert.notEqual(random('duel-seed', 3)(), random('other-room', 3)());
});

vm.runInContext(source('duel/adapters.js') + '\nglobalThis.connectGame = connectGame;', context);
function makeGame() {
    let progress = 0;
    const game = {
        opts: { numOfChallenges: 4 }, points: 0, _stats: { checksTotal: 0, checksCorrect: 0 },
        $points: { text() {} }, $progressCounter: { text() {} },
        $progressBar: { data(key, value) { if (value === undefined) return progress; progress = value; return this; }, css() { return this; } },
        _resetProgress() { progress = 0; this.points = 0; },
        newChallenge() { this.challenge = this._duelRandom(); },
        _updateProgressBar() { progress += 25; return progress; },
        start() { this._resetProgress(); this.newChallenge(); },
    };
    return game;
}
function makeDuel(progress = 0, score = 0) {
    const updates = []; const answers = [];
    return { state: { game: 'intervals-lab', total: 4, starts_at: '2026-09-26T12:00:00Z', seed: 'same-seed', checkpoint: null },
        you: () => ({ progress, score }), progress: (...data) => updates.push(data), answer: correct => answers.push(correct), answers, updates };
}

test('the common adapter restores earned progress and score before generating the resumed round', () => {
    const game = makeGame(); const duel = makeDuel(2, 6);
    context.connectGame(game, duel);
    assert.equal(game.points, 6);
    assert.equal(game.$progressBar.data('progress'), 50);
    assert.equal(game.challenge, random('same-seed', 2)());
    assert.equal(game._finalStartMs, Date.parse(duel.state.starts_at));
});

test('one round completion emits one meaningful progress update and an immutable checkpoint', async () => {
    const game = makeGame(); const duel = makeDuel(); context.connectGame(game, duel);
    game.points = 3; game._stats.checksCorrect = 1; game._updateProgressBar();
    await new Promise(resolve => queueMicrotask(resolve));
    assert.equal(duel.updates.length, 1);
    assert.equal(duel.updates[0][0], 1);
    assert.equal(duel.updates[0][1], 3);
    game._stats.checksCorrect = 99;
    assert.equal(duel.updates[0][2]._stats.checksCorrect, 1);
});

test('a refreshed player and an uninterrupted opponent generate the same next round', async () => {
    const uninterrupted = makeGame(); const duel = makeDuel(); context.connectGame(uninterrupted, duel);
    uninterrupted._updateProgressBar(); await new Promise(resolve => queueMicrotask(resolve)); uninterrupted.newChallenge();
    const refreshed = makeGame(); context.connectGame(refreshed, makeDuel(1, 0));
    assert.equal(refreshed.challenge, uninterrupted.challenge);
});

test('rematches clear score, accuracy and sequence history and start at round zero with the new seed', async () => {
    const game = makeGame(); const duel = makeDuel();
    game._targetSequence = [];
    context.connectGame(game, duel);
    game.points = 12; game._stats.checksTotal = 4; game._stats.checksCorrect = 3;
    game._targetSequence.push('C4'); game._updateProgressBar();
    await new Promise(resolve => queueMicrotask(resolve));
    duel.state.seed = 'rematch-seed';
    game._restartDuel();
    assert.equal(game.points, 0);
    assert.equal(game._stats.checksTotal, 0);
    assert.equal(game._targetSequence.length, 0);
    assert.equal(game.$progressBar.data('progress'), 0);
    assert.equal(game.challenge, random('rematch-seed', 0)());
    game.points = 3; game._updateProgressBar();
    await new Promise(resolve => queueMicrotask(resolve));
    assert.equal(duel.updates.at(-1)[0], 1);
    assert.equal(duel.updates.at(-1)[1], 3);
});

test('refresh between the last round and results reuses engine scoring without starting another round', () => {
    const game = makeGame(); const duel = makeDuel(4, 12);
    duel.state.checkpoint = { _stats: { checksTotal: 4, checksCorrect: 4 }, _madeAnyMistake: false };
    game._madeAnyMistake = true;
    game.start = () => assert.fail('a completed run must not restart');
    game._showFinalResults = () => {
        assert.equal(game.points, 12);
        assert.equal(game._stats.checksCorrect, 4);
        assert.equal(game._madeAnyMistake, false);
    };
    context.connectGame(game, duel, { finishOnly: true });
});

test('Note Python crash restarts preserve completed rounds, earned score and accuracy', async () => {
    const game = makeGame(); const duel = makeDuel(1, 3); duel.state.game = 'note-python';
    game._pointsValue = 0; game._roundsCompleted = 0;
    game.$playWrap = { hide() {} };
    for (const method of ['_setPlayButtons', '_placeInitialSnake', '_spawnFoods', '_spawnBombs', '_ensureTargetFoodPresent', '_renderEntities', '_startLoop']) game[method] = () => {};
    game._showBombs = () => false;
    game._showStandardGameUi = () => {};
    game.start = () => {
        game._showStandardGameUi(); game._pointsValue = 0; game._roundsCompleted = 0;
        game._stats = { checksTotal: 0, checksCorrect: 0 };
    };
    context.connectGame(game, duel);
    game._pointsValue = 6; game._roundsCompleted = 2;
    game._stats = { checksTotal: 2, checksCorrect: 2 };
    game._updateProgressBar(); await new Promise(resolve => queueMicrotask(resolve));
    game.start();
    assert.equal(game._pointsValue, 6);
    assert.equal(game._roundsCompleted, 2);
    assert.equal(game._stats.checksCorrect, 2);
    assert.equal(game.$progressBar.data('progress'), 50);
    duel.state.seed = 'new-python-match';
    game._restartDuel();
    assert.equal(game._pointsValue, 0);
    assert.equal(game._roundsCompleted, 0);
    assert.equal(game._stats.checksCorrect, 0);
    assert.equal(game.$progressBar.data('progress'), 0);
});

test('Tone Trek skipped rounds advance Duel progress so a timed run can finish', async () => {
    const game = makeGame(); const duel = makeDuel(); duel.state.game = 'tone-trek';
    game._points = 0; game._currentRound = 1; game._madeAnyMistake = false;
    game._resetRunUi = game._resetProgress;
    game._startRound = game.newChallenge;
    game._finishRoundAsTimedOut = () => { game._currentRound += 1; };
    game.start = () => { game._resetRunUi(); game._startRound(); };
    context.connectGame(game, duel);
    for (let i = 0; i < 4; i++) {
        game._finishRoundAsTimedOut(); await new Promise(resolve => queueMicrotask(resolve));
    }
    assert.deepEqual(duel.updates.map(update => update[0]), [1, 2, 3, 4]);
    assert.equal(duel.updates[3][2]._madeAnyMistake, true);
    assert.equal(game.$progressBar.data('progress'), 100);
    duel.state.seed = 'new-trek-match';
    game._restartDuel();
    assert.equal(game._currentRound, 1);
    assert.equal(game._points, 0);
    assert.equal(game.$progressBar.data('progress'), 0);
    game._finishRoundAsTimedOut(); await new Promise(resolve => queueMicrotask(resolve));
    assert.equal(duel.updates.at(-1)[0], 1);
});

const helpers = vm.createContext({ Math });
vm.runInContext(source('staff/staffUtils.js') + '\nglobalThis.helpers = { pickOne, pickWeighted, randomInt };', helpers);
test('shared challenge helpers accept seeded randomness without replacing global randomness', () => {
    const h = random('abc'); const g = random('abc');
    for (let i = 0; i < 20; i++) {
        assert.equal(helpers.helpers.pickOne(['C', 'D', 'E'], h), helpers.helpers.pickOne(['C', 'D', 'E'], g));
        assert.equal(helpers.helpers.randomInt(-2, 10, h), helpers.helpers.randomInt(-2, 10, g));
        assert.equal(helpers.helpers.pickWeighted([{ value: 'natural', weight: 8 }, { value: 'sharp', weight: 2 }], h),
            helpers.helpers.pickWeighted([{ value: 'natural', weight: 8 }, { value: 'sharp', weight: 2 }], g));
    }
});


test('confirmed correct and incorrect staff answers emit feedback, locked clicks do not', () => {
    const game = makeGame(); const duel = makeDuel();
    game._onCheck = outcome => {
        if (outcome === null) return 'locked';
        game._stats.checksTotal++;
        if (outcome) game._stats.checksCorrect++;
        return 'checked';
    };
    context.connectGame(game, duel);
    assert.equal(game._onCheck(true), 'checked');
    game._onCheck(false); game._onCheck(null);
    assert.deepEqual(duel.answers, [true, false]);
});

test('Beat Hero audio previews cannot echo a later answer tap', async () => {
    const game = makeGame(); const duel = makeDuel(); duel.state.game = 'beat-hero';
    game._correctTaps = 0; game._wrongTaps = 0;
    game._startRound = game.newChallenge; game._updateProgress = game._updateProgressBar;
    game.start = () => game._startRound();
    let previewDone;
    game._handleCardTap = async outcome => {
        if (outcome === 'preview') { await new Promise(resolve => { previewDone = resolve; }); return; }
        if (outcome) game._correctTaps++; else game._wrongTaps++;
    };
    context.connectGame(game, duel);
    const preview = game._handleCardTap('preview');
    await game._handleCardTap(true); await game._handleCardTap(false);
    previewDone(); await preview;
    assert.deepEqual(duel.answers, [true, false]);
});

test('Note Python reports food answers without sending every movement tick', () => {
    const game = makeGame(); const duel = makeDuel(); duel.state.game = 'note-python';
    game.start = () => {}; game._showStandardGameUi = () => {}; game.$playWrap = { hide() {} };
    for (const method of ['_setPlayButtons', '_placeInitialSnake', '_spawnFoods', '_ensureTargetFoodPresent', '_renderEntities', '_startLoop']) game[method] = () => {};
    game._showBombs = () => false;
    game._advanceSnake = food => { if (food == null) return; game._stats.checksTotal++; if (food) game._stats.checksCorrect++; };
    context.connectGame(game, duel);
    game._advanceSnake(null); game._advanceSnake(true); game._advanceSnake(null); game._advanceSnake(false);
    assert.deepEqual(duel.answers, [true, false]);
});
