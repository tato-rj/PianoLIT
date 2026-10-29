const assert = require('node:assert/strict');
const test = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '../..');
const read = name => fs.readFileSync(`${root}/resources/js/metronome/${name}.js`, 'utf8').replace(/^import .*;\n/gm, '').replace(/^export /gm, '');
const context = vm.createContext({ Float32Array, Map, Promise, effects: require('../../resources/data/metronome-voice-effects.json') });
vm.runInContext(['voice-stretch', 'voice-effects', 'voice-effect-selection', 'spoken-voices', 'settings-pages'].map(read).join('\n') + '\nglobalThis.api = {applyVoiceEffect, sampleBuffer, VoiceEffectSelection, SpokenVoiceLibrary, SpokenCounting, SettingsPages};', context);
const { applyVoiceEffect, sampleBuffer, VoiceEffectSelection, SpokenVoiceLibrary, SpokenCounting, SettingsPages } = context.api;
const tone = (duration = 0.4) => {
    const buffer = sampleBuffer(1, Math.round(24000 * duration), 24000);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < data.length; i++) data[i] = 0.5 * Math.sin(2 * Math.PI * 180 * i / 24000);
    return buffer;
};
function frequency(buffer) {
    const data = buffer.getChannelData(0);
    let crossings = 0;
    const from = Math.round(data.length * 0.1), to = Math.round(data.length * 0.9);
    for (let i = from; i < to; i++) if (data[i] <= 0 && data[i + 1] > 0) crossings++;
    return crossings / ((to - from) / buffer.sampleRate);
}
const turn = () => new Promise(resolve => setImmediate(resolve));

test('Classic is byte-for-byte original; every effect preserves the stored samples', () => {
    const original = tone(), before = original.getChannelData(0).slice();
    assert.equal(applyVoiceEffect(original, 'classic'), original);
    for (const id of Object.keys(context.effects)) {
        const result = applyVoiceEffect(original, id);
        assert.deepEqual(original.getChannelData(0), before);
        assert.ok(result.getChannelData(0).every(value => Number.isFinite(value) && Math.abs(value) <= 0.85));
        if (id !== 'classic') {
            assert.equal(result.getChannelData(0)[0], 0);
            assert.equal(Math.abs(result.getChannelData(0).at(-1)), 0);
        }
    }
});

test('high and deep styles change pitch independently of word duration', () => {
    for (const [id, ratio] of [['cartoon', 1.3], ['deep-space', 0.68], ['chipmunk', 1.75]]) {
        const result = applyVoiceEffect(tone(), id);
        assert.ok(Math.abs(frequency(result) - 180 * ratio) < 9, `${id}: ${frequency(result)}`);
        assert.ok(Math.abs(result.duration - (id === 'chipmunk' ? 0.4 / 1.1 : 0.4)) < 0.001);
    }
});

test('effects retain short/long words, silence and bounded amplitude without added tails', () => {
    for (const seconds of [0.06, 0.2, 2]) {
        for (const id of Object.keys(context.effects)) {
            const result = applyVoiceEffect(tone(seconds), id);
            assert.ok(result.duration <= seconds + 0.001);
            assert.ok(result.getChannelData(0).every(Number.isFinite));
        }
    }
    for (const id of Object.keys(context.effects)) {
        const silent = applyVoiceEffect(sampleBuffer(1, 4800, 24000), id);
        assert.ok(silent.getChannelData(0).every(value => value === 0));
    }
    assert.throws(() => applyVoiceEffect(tone(), 'unknown'));
});

test('effect choice survives source/settings changes and always resets in a new page session', async () => {
    let jobs = 0;
    const used = [];
    const source = [tone(), tone(), tone(), tone()];
    const selection = new VoiceEffectSelection({ process: clips => { jobs++; return { promise: Promise.resolve(clips), cancel() {} }; }, useBuffers: buffers => used.push(buffers) });
    selection.setSource(source);
    assert.equal(jobs, 0);
    selection.select('robot');
    await turn();
    selection.setSource(source); // Repeated settings/BPM edits do not regenerate.
    assert.equal(jobs, 1);
    assert.equal(used.at(-1), source);
    selection.setSource(null);
    assert.equal(selection.effect, 'robot');
    selection.setSource(source);
    await turn();
    assert.equal(jobs, 2);
    assert.equal(new VoiceEffectSelection({}).effect, 'classic');
});

test('new effect choices cancel work and discard late results; leaving disposes pending work', async () => {
    const jobs = [], used = [];
    const selection = new VoiceEffectSelection({ process: () => {
        const job = { canceled: false, cancel() { this.canceled = true; } };
        job.promise = new Promise(resolve => { job.resolve = resolve; }); jobs.push(job); return job;
    }, useBuffers: value => used.push(value) });
    selection.setSource([tone(), tone(), tone(), tone()]);
    selection.select('ghost'); selection.select('cartoon');
    assert.equal(jobs[0].canceled, true);
    jobs[0].resolve(['stale']); await turn();
    assert.equal(used.at(-1), null);
    jobs[1].resolve(['current']); await turn();
    assert.deepEqual(used.at(-1), ['current']);
    selection.select('robot'); selection.dispose();
    assert.equal(jobs[2].canceled, true);
    jobs[2].resolve(['late']); await turn();
    assert.notDeepEqual(used.at(-1), ['late']);
});

test('effect failure restores the regular voice', async () => {
    let errors = 0;
    const selection = new VoiceEffectSelection({ process: () => ({ promise: Promise.reject(Error('worker failed')), cancel() {} }), useBuffers() {}, onError: () => errors++ });
    selection.setSource([tone(), tone(), tone(), tone()]);
    selection.select('robot'); await turn();
    assert.equal(selection.effect, 'classic'); assert.equal(errors, 1);
});

const voice = { id: 'classic', files: [1, 2, 3, 4].map(n => `/audio/metronome/spoken/classic-${n}-1234567890abcdef.wav`) };
test('original samples load only on demand, share pending requests and reuse decoded buffers', async () => {
    let downloads = 0, decodes = 0;
    const library = new SpokenVoiceLibrary({ voices: [voice], fetchAudio: async () => { downloads++; return new ArrayBuffer(100); }, decode: async () => { decodes++; return tone(); } });
    assert.equal(downloads, 0);
    const first = library.load('classic'), second = library.load('classic');
    assert.equal(first, second);
    const buffers = await first;
    assert.equal(await library.load('classic'), buffers);
    assert.equal(downloads, 4); assert.equal(decodes, 4);
    await assert.rejects(library.load('missing'));
});

test('bad samples and network failures are rejected, and failed downloads can retry', async () => {
    let fail = true;
    const library = new SpokenVoiceLibrary({ voices: [voice], fetchAudio: async () => { if (fail) throw Error('offline'); return new ArrayBuffer(100); }, decode: async () => tone() });
    await assert.rejects(library.load('classic')); fail = false;
    assert.equal((await library.load('classic')).length, 4);
    const invalid = new SpokenVoiceLibrary({ voices: [voice], fetchAudio: async () => new ArrayBuffer(59000), decode: () => assert.fail('Oversize is rejected before decoding') });
    await assert.rejects(invalid.load('classic'));
});

test('changing sound or dismissing while loading cannot apply stale spoken samples', async () => {
    let resolve, uses = 0, errors = 0;
    const choice = new SpokenCounting({ library: { load: () => new Promise(r => { resolve = r; }) }, onUse: () => uses++, onError: () => errors++ });
    const pending = choice.select('classic'); choice.cancel(); resolve([tone()]); await pending;
    assert.equal(uses, 0); assert.equal(errors, 0);
});

test('focused settings pages show one view and return focus to the parent choice', () => {
    const item = extra => ({ hidden: false, textContent: '', addEventListener(event, fn) { this[event] = fn; }, setAttribute() {}, focus() { this.focused = true; }, ...extra });
    const pages = ['home', 'sound', 'rhythm', 'appearance', 'effects'].map(name => item({ dataset: { settingsPage: name } }));
    const links = ['sound', 'rhythm', 'appearance', 'effects'].map(name => item({ dataset: { settingsGo: name } }));
    const back = item(), title = item(), scroll = item();
    const root = item({ querySelectorAll: selector => selector === '[data-settings-page]' ? pages : links,
        querySelector: selector => selector === '#settings-title' ? title : selector === '[data-settings-back]' ? back : selector === '[data-settings-scroll]' ? scroll : links.find(link => selector.includes(`"${link.dataset.settingsGo}"`)) });
    const flow = new SettingsPages(root);
    flow.show('sound'); flow.show('effects');
    assert.equal(pages.filter(page => !page.hidden).length, 1);
    assert.equal(title.textContent, 'Voice effect');
    back.click(); assert.equal(flow.current, 'sound'); assert.equal(links[3].focused, true);
    back.click(); assert.equal(flow.current, 'home'); assert.equal(links[0].focused, true);
});
