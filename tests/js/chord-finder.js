const assert = require('assert');
const tool = require('../../resources/js/components/chord-finder');

module.exports = function () {
    assert.strictEqual(tool.pitch('C++'), tool.pitch('D'));
    assert.strictEqual(tool.pitch('C--'), tool.pitch('B-'));
    assert.strictEqual(tool.pitch('B+'), tool.pitch('C'));
    assert.strictEqual(tool.pitch('F-'), tool.pitch('E'));
    assert.strictEqual(tool.pitch('invalid'), null);
    for (const note of ['C', 'C#', 'C##', 'Db', 'Ebb', 'F#', 'Bbb']) assert.strictEqual(tool.human(tool.machine(note)), note);
    assert.deepStrictEqual(tool.voice(['c', 'e', 'g']).map(n => n.tone), ['C3', 'E3', 'G3']);
    assert.deepStrictEqual(tool.voice(['b', 'd', 'f', 'a']).map(n => n.tone), ['B3', 'D4', 'F4', 'A4']);
    assert.deepStrictEqual(tool.voice(['c', 'e', 'g', 'b-', 'd2']).map(n => n.tone), ['C3', 'E3', 'G3', 'A#3', 'D4']);
    assert.deepStrictEqual(tool.voice(['c', 'e', 'g', 'b-', 'd']).map(n => n.tone), ['C3', 'E3', 'G3', 'A#3', 'D4']);
    assert.deepStrictEqual(tool.voice(['c', 'e', 'g'], 'e').map(n => n.tone), ['E3', 'G3', 'C4']);
    assert.deepStrictEqual(tool.voice(['g-', 'b-', 'd-']).map(n => n.tone), ['F#3', 'A#3', 'C#4']);
    assert.strictEqual(tool.voice(['invalid'])[0], null);
    const aliases = {c: ['b#', 'c', 'dbb'], 'c#': ['db', 'c#'], d: ['ebb', 'd', 'c##'], 'd#': ['eb', 'd#'], e: ['d##', 'e', 'fb'], f: ['e#', 'f', 'gbb'], 'f#': ['gb', 'f#'], g: ['f##', 'g', 'abb'], 'g#': ['ab', 'g#'], a: ['g##', 'a', 'bbb'], 'a#': ['bb', 'a#'], b: ['a##', 'b', 'cb']};
    const spelling = notes => tool.spellings(notes.map(name => ({name, aliases: aliases[name]})));
    assert.deepStrictEqual(spelling(['c', 'd#', 'g']), ['c', 'eb', 'g'], 'A keyboard C-minor chord needs its minor-third spelling.');
    assert.deepStrictEqual(spelling(['c', 'e', 'g', 'a#']), ['c', 'e', 'g', 'bb']);
    assert.deepStrictEqual(spelling(['c#', 'f', 'g#']), ['db', 'f', 'ab'], 'Prefer a conventional spelling with fewer accidentals.');
    assert.deepStrictEqual(spelling(['f#', 'a#', 'c#']), ['f#', 'a#', 'c#']);
    assert.deepStrictEqual(spelling(['b', 'd', 'f', 'g#']), ['b', 'd', 'f', 'ab']);
    assert.deepStrictEqual(spelling(['c', 'd', 'e', 'g', 'a#']), ['c', 'd', 'e', 'g', 'bb']);
    assert.deepStrictEqual(spelling(['c', 'c#', 'g']), ['c', 'c#', 'g'], 'Leave unusual sets available for explicit spelling.');
    assert.deepStrictEqual(spelling([]), []);
    console.log('Passed: chord spellings, double accidentals, ascending inversions, extended notes and slash-chord bass playback.');
};
