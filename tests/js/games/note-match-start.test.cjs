const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '../../../resources/js/games/games');
const source = file => fs.readFileSync(path.join(root, file), 'utf8').replace(/^import[\s\S]*?;\n/gm, '').replace(/^export /gm, '');

test('Note Match generates its first round using the current Note Nest target contract and duel random stream', () => {
    let chosenRandom, rendered;
    const ui = {hide() {return this;}, show() {return this;}, addClass() {return this;}, setShort() {return this;}, clearLong() {return this;}, hideDirection() {return this;}};
    const context = vm.createContext({BaseStaffGame: class {}, $: () => ui,
        pickChallengeClef: (pool, random) => {chosenRandom = random; return 'treble';}});
    vm.runInContext(source('notenest/NoteNest.js') + '\n' + source('notematch/NoteMatch.js') + '\nglobalThis.Match = NoteMatch;', context);
    const game = Object.create(context.Match.prototype);
    const random = () => 0.5;
    Object.assign(game, {_clefPool: ['treble'], _duelRandom: random,
        staff: {getClef: () => 'treble', clearNotes() {}},
        _resetPlayedNote() {}, _clearBlockMarker() {}, _syncPlayedNoteGate() {},
        _pickTargetNote: () => ({letter: 'F', accidentalClass: 'sharp', step: 1}),
        _renderTargetNote: target => {rendered = target;},
        $feedback: ui, $helpBtn: ui, $bonusBadge: ui, $doublePoints: ui, $accidentals: ui, prompt: ui});
    game.newChallenge();
    assert.equal(game._lastTargetName, 'F|sharp');
    assert.equal(rendered, game._targetNote);
    assert.equal(chosenRandom, random);
});
