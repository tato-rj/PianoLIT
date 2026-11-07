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
    const scheduled = [], events = [];
    const clock = {
        setTimeout: (callback, delay) => { const timer = {callback, delay}; scheduled.push(timer); return timer; },
        clearTimeout: timer => { timer.canceled = true; }
    };
    const voiced = tool.voice(['g', 'bb', 'd']);
    const callbacks = {
        play: (notes, duration) => events.push(['audio', notes, duration]),
        highlight: notes => events.push(['dots', notes.map(note => note.tone)]),
        finish: () => events.push(['restore selection'])
    };
    tool.playbackSequence(voiced, callbacks, clock);
    assert.deepStrictEqual(scheduled.map(timer => timer.delay), [0, 220, 440, 760, 2760]);
    scheduled.forEach(timer => timer.callback());
    assert.deepStrictEqual(events, [
        ['dots', []],
        ['audio', 'G3', '8n'], ['dots', ['G3']],
        ['audio', 'A#3', '8n'], ['dots', ['A#3']],
        ['audio', 'D4', '8n'], ['dots', ['D4']],
        ['audio', ['G3', 'A#3', 'D4'], '1n'], ['dots', ['G3', 'A#3', 'D4']],
        ['restore selection']
    ], 'Audio and dots advance together, followed by restoration of the input selection.');
    scheduled.length = 0; events.length = 0;
    const cancel = tool.playbackSequence(voiced, callbacks, clock);
    scheduled[0].callback();
    cancel();
    const afterStop = events.slice();
    scheduled.forEach(timer => timer.callback());
    assert(scheduled.every(timer => timer.canceled));
    assert.deepStrictEqual(events, afterStop, 'Stop, switching chords and dismissal cannot leave old audio or dot updates queued.');
    console.log('Passed: synchronized note/chord highlights, natural completion and canceled playback callbacks.');
    console.log('Passed: chord spellings, double accidentals, ascending inversions, extended notes and slash-chord bass playback.');
};
