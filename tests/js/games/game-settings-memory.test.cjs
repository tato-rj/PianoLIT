const assert = require('node:assert/strict');
const {test} = require('node:test');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../../resources/js/games/gameSettingsMemory.js'), 'utf8').replace(/^export /gm, '');
const context = vm.createContext({URLSearchParams, Event: class {constructor(type) {this.type = type;}}});
vm.runInContext(source, context);
function storage() {
    const values = new Map([['pianolit.games.leaderboard.profile.v1', 'keep avatar']]);
    return {values, getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value)};
}

test('each game remembers its latest settings independently, including disabled switches and choices', () => {
    const saved = storage();
    const defaults = {sound: true, rounds: 4, clefs: ['treble', 'bass']};
    context.rememberGameSettings('note-nest', {sound: false, rounds: 7, clefs: ['alto']}, () => saved);
    context.rememberGameSettings('beat-hero', {bpm: 110, sound: true}, () => saved);
    assert.deepEqual(JSON.parse(JSON.stringify(context.savedGameSettings('note-nest', defaults, () => saved))), {sound: false, rounds: 7, clefs: ['alto']});
    context.rememberGameSettings('note-nest', {sound: true, rounds: 2, clefs: ['bass']}, () => saved);
    assert.equal(context.savedGameSettings('note-nest', defaults, () => saved).rounds, 2);
    assert.equal(context.savedGameSettings('beat-hero', {bpm: 80}, () => saved).bpm, 110);
    assert.equal(saved.values.get('pianolit.games.leaderboard.profile.v1'), 'keep avatar');
});

test('new games and unavailable or corrupt storage keep the setup flow', () => {
    for (const getStorage of [() => storage(), () => ({getItem: () => '{broken'}), () => {throw Error('blocked');}, () => ({getItem: () => '{"note-nest":{"unknown":"bad","rounds":{}}}'})]) {
        assert.equal(context.savedGameSettings('note-nest', {rounds: 4}, getStorage), null);
    }
    assert.equal(context.rememberGameSettings('note-nest', {rounds: 4}, () => {throw Error('blocked');}), false);
});

test('replay URLs keep repeated choices and explicit off switches without reusing old query parameters', () => {
    const url = context.replayGameUrl('https://theory.test/note-nest?old=1', {sound: false, timer: true, numOfChallenges: 7, clefs: ['bass', 'alto'], fixedNotes: []});
    const parsed = new URL(url);
    assert.equal(parsed.pathname, '/note-nest');
    assert.equal(parsed.searchParams.get('old'), null);
    assert.equal(parsed.searchParams.get('sound'), '0');
    assert.equal(parsed.searchParams.get('timer'), '1');
    assert.equal(parsed.searchParams.get('numOfChallenges'), '7');
    assert.deepEqual(parsed.searchParams.getAll('clefs[]'), ['bass', 'alto']);
    assert.deepEqual(parsed.searchParams.getAll('fixedNotes[]'), ['']);
});

test('restoring settings updates ranges and choice styling without changing hidden off values', () => {
    const range = {name: 'rounds', type: 'range', dispatchEvent(event) {this.event = event.type;}};
    const hidden = {name: 'sound', type: 'hidden', value: '0'};
    const sound = {name: 'sound', type: 'checkbox', hasAttribute: () => false};
    function choice(value) {
        const classes = new Set();
        return {name: 'clefs[]', value, type: 'checkbox', hasAttribute: () => true, classes,
            parentElement: {querySelector: () => ({classList: {toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name)}})}};
    }
    const bass = choice('bass'), alto = choice('alto');
    context.restoreGameSettingsForm({elements: [range, hidden, sound, bass, alto]}, {rounds: 7, sound: true, clefs: ['alto']});
    assert.equal(range.value, '7');
    assert.equal(range.event, 'input');
    assert.equal(hidden.value, '0');
    assert.equal(sound.checked, true);
    assert.equal(bass.checked, false);
    assert.equal(alto.checked, true);
    assert.equal(alto.classes.has('btn-secondary'), true);
    assert.equal(bass.classes.has('btn-white'), true);
});

test('resetting saved settings immediately restores setup buttons and default form values', () => {
    const saved = storage();
    context.rememberGameSettings('note-nest', {rounds: 8, sound: false}, () => saved);
    function node() {
        return { classes: new Set(), get classList() { return {toggle: (name, on) => on ? this.classes.add(name) : this.classes.delete(name)}; }, removeAttribute(name) { delete this[name]; } };
    }
    const setup = node(), replay = node(), settings = node();
    const rounds = {name: 'rounds', type: 'range', dispatchEvent() {}};
    const sound = {name: 'sound', type: 'checkbox', hasAttribute: () => false};
    const form = {elements: [rounds, sound]};
    const elements = {'[data-card-setup]': setup, '[data-card-replay]': replay, '[data-card-settings]': settings, 'form[method="GET"]': form};
    const card = {dataset: {gameCard: 'note-nest', gameUrl: '/note-nest', gameDefaults: '{"rounds":4,"sound":true}'}, querySelector: selector => elements[selector]};
    const root = {querySelectorAll: () => [card]};
    context.refreshGameCards(root, () => saved);
    assert.equal(setup.classes.has('d-none'), true);
    assert.equal(rounds.value, '8');
    assert.equal(sound.checked, false);
    assert.match(replay.href, /rounds=8/);
    saved.values.delete('pianolit.games.game.settings.v1');
    context.refreshGameCards(root, () => saved);
    assert.equal(setup.classes.has('d-none'), false);
    assert.equal(replay.classes.has('d-none'), true);
    assert.equal(settings.classes.has('d-none'), true);
    assert.equal(replay.href, undefined);
    assert.equal(rounds.value, '4');
    assert.equal(sound.checked, true);
});
