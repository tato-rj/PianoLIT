const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const root = path.join(__dirname, '../../resources/js/metronome');
const read = name => fs.readFileSync(path.join(root, name + '.js'), 'utf8').replace(/^import .*;\n/gm, '').replace(/^export /gm, '');
const context = vm.createContext({ Float32Array, Uint8Array, ArrayBuffer, DataView, DOMException, Blob,
    btoa, atob, setInterval, clearInterval, setTimeout });
vm.runInContext(read('voice-audio') + '\nglobalThis.api = { prepareVoiceClip, encodeVoiceWav, VoiceCapture, voiceBase64, voiceBytes };', context);
const { prepareVoiceClip, VoiceCapture } = context.api;

function audio({ seconds = 1, speechStart = 0.3, speechEnd = 0.6, rate = 48000, gain = 0.2, noise = 0 } = {}) {
    const samples = Float32Array.from({ length: Math.round(seconds * rate) }, (_, i) => Math.sin(i * 0.71) * noise
        + (i >= speechStart * rate && i < speechEnd * rate ? Math.sin(i * 0.15) * gain : 0));
    return { sampleRate: rate, length: samples.length, numberOfChannels: 1, getChannelData: () => samples };
}

test('recorded counts trim silence, resample to portable PCM and fade their edges', () => {
    const wav = prepareVoiceClip(audio());
    const view = new DataView(wav);
    assert.equal(view.getUint32(24, true), 24000);
    assert.equal(view.getUint16(22, true), 1);
    assert.equal(view.getUint16(34, true), 16);
    assert.equal(view.getUint32(40, true), wav.byteLength - 44);
    assert.ok((wav.byteLength - 44) / 48000 >= 0.34 && (wav.byteLength - 44) / 48000 <= 0.38);
    assert.equal(view.getInt16(44, true), 0);
    assert.equal(view.getInt16(wav.byteLength - 2, true), 0);
});

test('silence, incidental short noises and overlong speech are rejected before saving', () => {
    assert.throws(() => prepareVoiceClip(audio({ gain: 0 })), /hear/);
    assert.throws(() => prepareVoiceClip(audio({ speechEnd: 0.31 })), /hear/);
    assert.throws(() => prepareVoiceClip(audio({ seconds: 3, speechStart: 0.1, speechEnd: 2.5 })), /shorter/);
});

test('quiet speech is trimmed and brought to a clear playback level without clipping', () => {
    const wav = prepareVoiceClip(audio({ gain: 0.003, noise: 0.0002 }));
    const view = new DataView(wav);
    const duration = (wav.byteLength - 44) / 48000;
    assert.ok(duration >= 0.34 && duration <= 0.38);
    let peak = 0;
    for (let offset = 44; offset < wav.byteLength; offset += 2) peak = Math.max(peak, Math.abs(view.getInt16(offset, true)) / 32767);
    assert.ok(peak > 0.75 && peak <= 0.86, `Playback peak was ${peak}`);
});

test('steady background noise is not normalized into a recorded number', () => {
    assert.throws(() => prepareVoiceClip(audio({ gain: 0, noise: 0.006 })), /hear/);
    assert.throws(() => prepareVoiceClip(audio({ gain: 0.0001, noise: 0.00002 })), /hear/);
});

function captureHarness({ background = 0.0002, speech = 0.003, decoded = audio({ gain: 0.004 }) } = {}) {
    let time = 0;
    let started = null;
    let stoppedAt = null;
    let constraints;
    let trackStopped = false;
    const intervals = new Set();
    class Recorder {
        static isTypeSupported() { return true; }
        start() { this.state = 'recording'; started = time; }
        stop() { this.state = 'inactive'; stoppedAt = time; this.onstop(); }
    }
    const scope = vm.createContext({ Float32Array, Uint8Array, ArrayBuffer, DataView, DOMException, Blob,
        setInterval(callback) { intervals.add(callback); return callback; }, clearInterval(callback) { intervals.delete(callback); } });
    vm.runInContext(read('voice-audio') + '\nglobalThis.VoiceCapture = VoiceCapture;', scope);
    const capture = new scope.VoiceCapture({ Recorder, now: () => time,
        mediaDevices: { getUserMedia: async value => {
            constraints = value;
            return { getTracks: () => [{ stop() { trackStopped = true; } }] };
        } },
        createContext: () => ({ state: 'running', resume: async () => {}, close: async () => {},
            decodeAudioData: async () => decoded,
            createMediaStreamSource: () => ({ connect() {}, disconnect() {} }),
            createAnalyser: () => ({ fftSize: 2048, disconnect() {}, getFloatTimeDomainData(samples) {
                const elapsed = started === null ? 0 : time - started;
                samples.fill(elapsed >= 150 && elapsed <= 450 ? speech : background);
            } }),
        }),
    });
    return { capture, intervals, get constraints() { return constraints; }, get trackStopped() { return trackStopped; },
        get duration() { return stoppedAt === null ? null : stoppedAt - started; },
        tick(milliseconds) { for (let i = 0; i < milliseconds / 50; i++) { time += 50; [...intervals].forEach(callback => callback()); } },
    };
}

test('normal quiet input registers on the meter and automatically finishes after the word', async () => {
    const mic = captureHarness();
    await mic.capture.open();
    assert.equal(mic.constraints.audio.autoGainControl, true);
    mic.tick(1200);
    const levels = [];
    const recording = mic.capture.record(level => levels.push(level));
    mic.tick(1500);
    assert.ok(mic.duration >= 850 && mic.duration <= 1000, `Stopped after ${mic.duration}ms`);
    assert.ok(Math.max(...levels) >= 25, 'Quiet speech should visibly move the meter');
    assert.ok((await recording).byteLength > 44);
    mic.capture.cancel();
    assert.equal(mic.intervals.size, 0);
});

test('calibration keeps steady room noise from triggering automatic speech detection', async () => {
    const mic = captureHarness({ background: 0.006, speech: 0.006, decoded: audio({ gain: 0, noise: 0.006 }) });
    await mic.capture.open();
    mic.tick(1200);
    const recording = mic.capture.record(() => {});
    mic.tick(1500);
    assert.equal(mic.duration, null);
    mic.tick(2000);
    await assert.rejects(recording, /hear/);
    assert.equal(mic.duration, 3500);
    mic.capture.cancel();
});

test('canceling during Get ready also releases the background calibration timer', async () => {
    const mic = captureHarness();
    await mic.capture.open();
    mic.tick(600);
    assert.equal(mic.intervals.size, 1);
    mic.capture.cancel();
    assert.equal(mic.intervals.size, 0);
    assert.equal(mic.trackStopped, true);
});

test('canceling a pending microphone prompt stops a late stream and never attaches it', async () => {
    let allow;
    let stopped = false;
    let connected = false;
    const capture = new VoiceCapture({
        mediaDevices: { getUserMedia: () => new Promise(resolve => { allow = resolve; }) },
        Recorder: {},
        createContext: () => ({ state: 'running', resume: async () => {}, close: async () => {},
            createMediaStreamSource() { connected = true; } }),
    });
    const opening = capture.open();
    for (let i = 0; i < 10 && !allow; i++) await Promise.resolve();
    capture.cancel();
    allow({ getTracks: () => [{ stop() { stopped = true; } }] });
    await assert.rejects(opening, { name: 'AbortError' });
    assert.equal(stopped, true);
    assert.equal(connected, false);
});

test('recording cancellation settles the capture and releases the microphone', async () => {
    let stopped = false;
    let disconnected = false;
    class Recorder {
        static isTypeSupported() { return true; }
        start() { this.state = 'recording'; }
        stop() { this.state = 'inactive'; queueMicrotask(() => this.onstop()); }
    }
    const capture = new VoiceCapture({ Recorder, now: () => 0,
        mediaDevices: { getUserMedia: async () => ({ getTracks: () => [{ stop() { stopped = true; } }] }) },
        createContext: () => ({ state: 'running', resume: async () => {}, close: async () => {},
            createMediaStreamSource: () => ({ connect() {}, disconnect() { disconnected = true; } }),
            createAnalyser: () => ({ fftSize: 2048, disconnect() {}, getFloatTimeDomainData() {} }),
        }),
    });
    await capture.open();
    const recording = capture.record(() => {});
    capture.cancel();
    await assert.rejects(recording, { name: 'AbortError' });
    assert.equal(stopped, true);
    assert.equal(disconnected, true);
});

function recorderHarness({ saved = false, preferred = false, confirmDeletion = true } = {}) {
    const element = properties => ({ hidden: false, disabled: false, dataset: {}, textContent: '', value: '', style: {}, listeners: {}, children: [], parentNode: null,
        append(...children) { children.forEach(child => { child.remove(); this.children.push(child); child.parentNode = this; }); },
        replaceChildren(...children) { this.children.forEach(child => { child.parentNode = null; }); this.children = []; this.append(...children); },
        remove() { if (this.parentNode) this.parentNode.children = this.parentNode.children.filter(child => child !== this); this.parentNode = null; },
        addEventListener(event, callback) { this.listeners[event] = callback; }, setAttribute() {}, focus() {}, ...properties });
    const nodes = new Map();
    const node = name => {
        if (!nodes.has(name)) nodes.set(name, element({ value: name === 'name-input' ? 'Calm counting' : '' }));
        return nodes.get(name);
    };
    const requests = [];
    const sounds = [];
    const confirmations = [];
    const storage = new Map([['metronome.voice.10.v1', preferred ? '1' : '0']]);
    const root = { dataset: { url: '/voice', owner: 10, csrf: 'csrf' }, querySelector: selector => selector.startsWith('[name=') ? node(({ '[name="metronome_voice_name"]': 'name-input', '[name="metronome_voice_rename"]': 'rename-input' })[selector]) : node(selector.slice(12, -1)), querySelectorAll: () => [] };
    const dialog = { dataset: {}, getAttribute() { return this.open ? 'true' : null; } };
    const option = element({ value: 'my-voice', text: 'My voice' });
    const soundInput = element({ value: 'click', querySelector: () => option });
    soundInput.append(...['click', 'woodblock', 'beep', 'cowbell'].map(value => element({value})), option);
    const clock = { stop() {}, setVoiceBuffers(buffers) { this.buffers = buffers; } };
    const audioContext = { state: 'running', resume: async () => {}, decodeAudioData: async () => ({ duration: 0.2 }),
        createBufferSource: () => ({ connect() {}, disconnect() {}, start() {}, stop() {} }), currentTime: 0 };
    Object.assign(clock, { getContext: () => audioContext, resumeAudio: () => audioContext.resume(),
        suspendAudio() {}, releaseAudio: async () => {} });
    const scope = vm.createContext({ console, DOMException, Uint8Array, ArrayBuffer, atob, btoa,
        Option: function (text, value) { return element({ text, value }); },
        document: { createElement: tag => element({ tagName: tag.toUpperCase() }) },
        voiceBase64: context.api.voiceBase64, voiceBytes: context.api.voiceBytes, VOICE_RATE: 24000,
        localStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) },
        window: { AudioContext: function () { return audioContext; }, MediaRecorder: {}, isSecureContext: true,
            OfflineAudioContext: function () { return { decodeAudioData: audioContext.decodeAudioData }; },
            confirm(message) { confirmations.push(message); return confirmDeletion; } },
        navigator: { mediaDevices: { getUserMedia() {} } }, setTimeout: fn => fn(),
        fetch: (url, options) => new Promise(resolve => requests.push({ url, options, resolve })),
        VoiceCapture: class { cancel() {} },
    });
    vm.runInContext(read('voice-recorder') + '\nglobalThis.VoiceRecorder = VoiceRecorder;', scope);
    let recorder;
    recorder = new scope.VoiceRecorder({ root, dialog, clock, soundInput, getBeats: () => 3,
        useSound(sound) { recorder.syncSound(sound); sounds.push(sound); } });
    const detail = (id = 'first', name = 'Calm counting') => ({ id, name, revision: 'old', clips: ['YQ==', 'YQ==', 'YQ==', 'YQ=='] });
    const data = () => ({ recordings: saved ? [{ id: 'first', name: 'Calm counting', revision: 'old' }] : [], limit: 20 });
    const resolve = (request, body, status = 200) => request.resolve({ ok: status === 200, status, json: async () => body });
    return { recorder, nodes, node, requests, sounds, confirmations, storage, resolve, data, detail, clock, dialog, soundInput, option, group: recorder.voiceGroup, scope, audioContext };
}
const settle = async () => { for (let i = 0; i < 25; i++) await Promise.resolve(); };
const load = async page => {
    page.resolve(page.requests[0], page.data());
    await settle();
    if (page.requests[1]) { page.resolve(page.requests[1], page.detail()); await settle(); }
};

test('recording closes old playback before capture and waits for the microphone to close before fresh shared playback', async () => {
    const page = recorderHarness();
    await load(page);
    const outputs = [];
    let finishPlaybackClose, finishCaptureClose;
    let microphoneOpen = false;
    page.scope.setInterval = () => 1;
    page.scope.clearInterval = () => {};
    page.scope.navigator.audioSession = { type: 'auto' };
    vm.runInContext(['gesture', 'settings', 'clock'].map(read).join('\n') + '\nglobalThis.MetronomeClock = MetronomeClock;', page.scope);
    const clock = new page.scope.MetronomeClock({ onStateChange() {}, onBeat() {}, onError(error) { throw error; },
        createContext() {
            assert.equal(microphoneOpen, false, 'Never reopen playback while microphone shutdown is pending');
            const output = { ...page.audioContext, state: 'suspended', sources: [],
                resume() { this.state = 'running'; return Promise.resolve(); },
                suspend() { this.state = 'suspended'; return Promise.resolve(); },
                close() {
                    if (outputs.length === 1) return new Promise(resolve => {
                        finishPlaybackClose = () => { this.state = 'closed'; resolve(); };
                    });
                    this.state = 'closed'; return Promise.resolve();
                },
                createBufferSource() { const source = page.audioContext.createBufferSource(); this.sources.push(source); return source; },
            };
            outputs.push(output);
            return output;
        },
    });
    page.recorder.clock = clock;
    await clock.resumeAudio();
    await page.recorder.preview([{ duration: 0.2 }]);
    assert.equal(outputs.length, 1, 'Counting and preview share one playback device');
    page.recorder.capture = {
        async open() { assert.equal(outputs[0].state, 'closed'); microphoneOpen = true; },
        async record() { return new ArrayBuffer(44); },
        cancel() { return new Promise(resolve => {
            finishCaptureClose = () => { microphoneOpen = false; resolve(); };
        }); },
    };
    page.recorder.delay = async () => {};
    const recording = page.recorder.record();
    await settle();
    assert.equal(microphoneOpen, false);
    finishPlaybackClose();
    for (let step = 0; step < 100 && !finishCaptureClose; step++) await Promise.resolve();
    assert.equal(microphoneOpen, true);
    assert.equal(outputs.length, 1, 'Decoding waits for capture shutdown');
    finishCaptureClose();
    await recording;
    assert.equal(outputs.length, 1, 'Review decoding does not activate any playback device');
    await page.recorder.preview(page.recorder.draftBuffers);
    assert.equal(outputs.length, 2, 'The fresh device is also reused for preview');
    assert.equal(outputs[1].sources.length, 4);
    assert.equal(page.scope.navigator.audioSession.type, 'playback');
    page.recorder.stopPreview();
    assert.equal(outputs[1].state, 'suspended');
    await clock.releaseAudio();
});

test('cancel while playback is closing prevents a late microphone request', async () => {
    const page = recorderHarness();
    await load(page);
    let closed, opened = false;
    page.clock.releaseAudio = () => new Promise(resolve => { closed = resolve; });
    page.recorder.capture.open = async () => { opened = true; };
    const recording = page.recorder.record();
    page.recorder.cancel();
    closed();
    await recording;
    assert.equal(opened, false);
    assert.equal(page.recorder.draft.length, 0);
});

test('a late voice load cannot override a sound the user just chose', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    page.recorder.remember('woodblock');
    page.soundInput.value = 'woodblock';
    await load(page);
    assert.equal(page.group.disabled, false);
    assert.equal(page.option.parentNode, null);
    assert.equal(page.soundInput.value, 'woodblock');
    assert.equal(page.storage.get('metronome.voice.10.v1'), '0');
});

test('voice preferences are restored only for the current account after its clips load', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    assert.equal(page.option.disabled, true);
    await load(page);
    assert.equal(page.soundInput.value, page.recorder.voiceValue(page.recorder.activeId));
    assert.equal(page.clock.buffers.length, 4);
    assert.equal(page.requests[0].options.credentials, 'same-origin');
    assert.equal(page.requests[0].options.cache, 'no-store');
});

test('failed saves keep all draft clips for retry; a successful save selects the voice', async () => {
    const page = recorderHarness();
    await load(page);
    page.recorder.draft = [1, 2, 3, 4].map(() => new Uint8Array([1, 2]).buffer);
    page.recorder.draftBuffers = [1, 2, 3, 4].map(() => ({ duration: 0.2 }));
    const saving = page.recorder.save();
    await settle();
    page.resolve(page.requests[1], {}, 500);
    await saving;
    assert.equal(page.recorder.draft.length, 4);
    assert.equal(page.recorder.saved.length, 0);
    assert.equal(page.node('save').disabled, false);
    const retry = page.recorder.save();
    await settle();
    assert.equal(page.requests[2].options.headers['X-CSRF-TOKEN'], 'csrf');
    page.resolve(page.requests[2], { id: 'new-id', name: 'Calm counting', revision: 'new', clips: ['a', 'b', 'c', 'd'] });
    await retry;
    assert.equal(page.recorder.revision, 'new');
    assert.equal(page.soundInput.value, page.recorder.voiceValue(page.recorder.activeId));
    assert.equal(page.storage.get('metronome.voice.10.v1'), '1');
});

test('closing during a save cannot reopen the recorder or play a late preview', async () => {
    const page = recorderHarness();
    await load(page);
    page.recorder.draft = [1, 2, 3, 4].map(() => new Uint8Array([1]).buffer);
    const saving = page.recorder.save();
    await settle();
    page.recorder.cancel();
    page.resolve(page.requests[1], { id: 'new-id', name: 'Calm counting', revision: 'new', clips: ['a', 'b', 'c', 'd'] });
    await saving;
    assert.equal(page.recorder.ready, false);
    assert.equal(page.soundInput.value, 'click');
    assert.equal(page.recorder.previewSources.length, 0);
});

test('an expired session disables private playback and offers sign-in', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    await load(page);
    const reload = page.recorder.load();
    page.resolve(page.requests[2], {}, 401);
    await reload;
    assert.equal(page.soundInput.value, 'click');
    assert.equal(page.clock.buffers, null);
    assert.equal(page.option.disabled, true);
    assert.equal(page.node('signin').hidden, false);
});

test('microphone denial offers a clear retry without replacing a saved voice', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.recorder.capture.open = async () => { throw new DOMException('Denied', 'NotAllowedError'); };
    await page.recorder.record();
    assert.match(page.node('status').textContent, /Allow the microphone/);
    assert.equal(page.node('retry').hidden, false);
    assert.equal(page.recorder.saved.length, 4);
    assert.equal(page.requests.length, 2);
});

test('a retry resumes at the missed count and keeps the earlier numbers', async () => {
    const page = recorderHarness();
    await load(page);
    let recordings = 0;
    page.recorder.capture.open = async () => {};
    page.recorder.capture.record = async () => {
        recordings++;
        if (recordings === 3) throw new Error('I couldn’t hear that number. Try again.');
        return Uint8Array.of(recordings).buffer;
    };
    await page.recorder.record();
    assert.equal(page.recorder.nextIndex, 2);
    assert.equal(page.recorder.draft.length, 2);
    await page.recorder.record({ resume: true });
    assert.deepEqual(Array.from(page.recorder.draft, bytes => new Uint8Array(bytes)[0]), [1, 2, 4, 5]);
    assert.equal(page.node('save').hidden, false);
    assert.equal(page.node('review').hidden, false);
});

test('saving another named recording keeps the first and remembers the new selection', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.recorder.draft = [1, 2, 3, 4].map(() => Uint8Array.of(2).buffer);
    page.recorder.draftBuffers = [1, 2, 3, 4].map(() => ({ duration: 0.3 }));
    page.node('name-input').value = '  Gentle counting  ';
    const saving = page.recorder.save();
    await settle();
    assert.equal(JSON.parse(page.requests[2].options.body).name, 'Gentle counting');
    assert.equal(JSON.parse(page.requests[2].options.body).revision, undefined);
    page.resolve(page.requests[2], { ...page.detail('second', 'Gentle counting'), revision: 'second-revision' });
    await saving;
    assert.deepEqual(Array.from(page.recorder.library, voice => voice.name), ['Calm counting', 'Gentle counting']);
    assert.equal(page.soundInput.value, 'voice:second');
    assert.equal(page.storage.get('metronome.voice.10.selected.v1'), 'second');
    assert.equal(page.soundInput.value, page.recorder.voiceValue(page.recorder.activeId));
    assert.equal(page.clock.buffers[0].duration, 0.3);
});

test('the selected named voice restores on a new visit without fetching every recording', async () => {
    const page = recorderHarness({ preferred: true });
    page.recorder.preferredId = 'second';
    page.resolve(page.requests[0], { recordings: [
        { id: 'first', name: 'First', revision: 'first-rev' }, { id: 'second', name: 'Second', revision: 'second-rev' },
    ], limit: 20 });
    await settle();
    assert.equal(page.requests.length, 2);
    assert.equal(page.requests[1].url, '/voice/second');
    page.resolve(page.requests[1], page.detail('second', 'Second'));
    await settle();
    assert.equal(page.recorder.activeId, 'second');
    assert.equal(page.soundInput.value, page.recorder.voiceValue(page.recorder.activeId));
});

test('switching voices ignores an earlier response and a later instrument choice', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.recorder.library.push({ id: 'second', name: 'Second', revision: 'second-rev' });
    const older = page.recorder.choose('first');
    const latest = page.recorder.choose('second');
    page.resolve(page.requests[3], page.detail('second', 'Second'));
    await latest;
    page.resolve(page.requests[2], page.detail('first'));
    await older;
    assert.equal(page.recorder.activeId, 'second');
    assert.equal(page.soundInput.value, 'voice:second');
    const pending = page.recorder.choose('first');
    page.soundInput.value = 'cowbell';
    page.recorder.remember('cowbell');
    page.resolve(page.requests[4], page.detail());
    await pending;
    assert.equal(page.soundInput.value, 'cowbell');
});

test('renaming changes only the selected name and uses its optimistic revision', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    const buffers = page.clock.buffers;
    page.node('rename-input').value = 'Warm voice';
    const renaming = page.recorder.rename();
    assert.equal(page.requests[2].url, '/voice/first');
    assert.equal(page.requests[2].options.method, 'PATCH');
    assert.equal(JSON.parse(page.requests[2].options.body).revision, 'old');
    page.resolve(page.requests[2], { id: 'first', name: 'Warm voice', revision: 'new' });
    await renaming;
    assert.equal(page.recorder.library[0].name, 'Warm voice');
    assert.equal(page.group.children[0].text, 'Warm voice');
    assert.equal(page.recorder.revision, 'new');
    assert.equal(page.clock.buffers, buffers);
    assert.equal(page.node('rename-panel').hidden, true);
});

test('blank names and stale rename responses preserve the library', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.node('rename-input').value = '   ';
    await page.recorder.rename();
    assert.equal(page.requests.length, 2);
    assert.match(page.node('status').textContent, /name/);
    page.node('rename-input').value = 'New name';
    const renaming = page.recorder.rename();
    page.resolve(page.requests[2], {}, 409);
    await renaming;
    assert.equal(page.recorder.library[0].name, 'Calm counting');
    assert.equal(page.node('reload').hidden, false);
    assert.equal(page.node('rename-save').disabled, false);
});

test('deleting the selected voice keeps other recordings and selects the next one', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    await load(page);
    page.recorder.library.push({ id: 'second', name: 'Second', revision: 'second-rev' });
    const deleting = page.recorder.remove();
    assert.equal(page.requests[2].url, '/voice/first');
    assert.equal(page.requests[2].options.method, 'DELETE');
    page.resolve(page.requests[2], { deleted: 'first' });
    await settle();
    assert.equal(page.requests[3].url, '/voice/second');
    page.resolve(page.requests[3], page.detail('second', 'Second'));
    await deleting;
    assert.equal(page.recorder.library.length, 1);
    assert.equal(page.recorder.activeId, 'second');
    assert.equal(page.soundInput.value, page.recorder.voiceValue(page.recorder.activeId));
});

test('deleting the last recording restores an instrument and leaves recording available', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    await load(page);
    const deleting = page.recorder.remove();
    page.resolve(page.requests[2], { deleted: 'first' });
    await deleting;
    assert.equal(page.recorder.library.length, 0);
    assert.equal(page.option.disabled, false);
    assert.equal(page.option.parentNode, page.soundInput);
    assert.equal(page.soundInput.value, 'click');
    assert.equal(page.node('start').disabled, false);
});

test('closing during a rename cannot show a late success or change the playing voice', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.node('rename-input').value = 'New name';
    const renaming = page.recorder.rename();
    page.recorder.cancel();
    page.resolve(page.requests[2], { id: 'first', name: 'New name', revision: 'new' });
    await renaming;
    assert.equal(page.recorder.ready, false);
    assert.equal(page.recorder.library[0].name, 'Calm counting');
    assert.equal(page.node('rename-panel').hidden, true);
});

test('server validation errors keep the named draft available for correction', async () => {
    const page = recorderHarness();
    await load(page);
    page.recorder.draft = [1, 2, 3, 4].map(() => Uint8Array.of(2).buffer);
    page.node('name-input').value = 'Voice';
    const saving = page.recorder.save();
    await settle();
    page.resolve(page.requests[1], { errors: { name: ['You can save up to 20 voices. Delete one to make room.'] } }, 422);
    await saving;
    assert.match(page.node('status').textContent, /20 voices/);
    assert.equal(page.recorder.draft.length, 4);
    assert.equal(page.node('save').disabled, false);
});

test('capture starts only after the yellow countdown and each count gets clear success feedback', async () => {
    const page = recorderHarness();
    await load(page);
    const cues = [];
    const cue = page.recorder.cue.bind(page.recorder);
    page.recorder.cue = (state, prompt, countdown) => { cues.push([state, countdown]); cue(state, prompt, countdown); };
    page.recorder.capture.open = async () => {};
    let recordings = 0;
    page.recorder.capture.record = async () => {
        assert.equal(page.node('cue').dataset.state, 'speaking');
        assert.equal(page.node('meter').hidden, false);
        assert.equal(page.node('cue-label').textContent, 'Speak now');
        assert.deepEqual(cues.slice(-4).map(cue => cue[0]), ['waiting', 'waiting', 'waiting', 'speaking']);
        assert.deepEqual(cues.slice(-4, -1).map(cue => cue[1]), ['Ready in 3', 'Ready in 2', 'Ready in 1']);
        recordings++;
        return Uint8Array.of(recordings).buffer;
    };
    await page.recorder.record();
    assert.equal(recordings, 4);
    assert.equal(cues.filter(cue => cue[0] === 'success').length, 4);
    assert.equal(page.node('cue').dataset.state, 'review');
    assert.equal(page.node('visual').hidden, true);
    assert.equal(page.node('meter').hidden, true);
});

test('a missed number stays red and retry returns through yellow before recording again', async () => {
    const page = recorderHarness();
    await load(page);
    page.recorder.capture.open = async () => {};
    page.recorder.capture.record = async () => { throw new Error('I couldn’t hear that number. Try again.'); };
    await page.recorder.record();
    assert.equal(page.node('cue').dataset.state, 'error');
    assert.equal(page.node('cue-label').textContent, 'Try again');
    assert.equal(page.node('meter').hidden, true);
    assert.equal(page.node('retry').hidden, false);
    const cues = [];
    const cue = page.recorder.cue.bind(page.recorder);
    page.recorder.cue = (state, ...args) => { cues.push(state); cue(state, ...args); };
    page.recorder.capture.record = async () => Uint8Array.of(1).buffer;
    await page.recorder.record({ resume: true });
    assert.deepEqual(cues.slice(0, 5), ['waiting', 'waiting', 'waiting', 'waiting', 'speaking']);
    assert.equal(page.node('cue').dataset.state, 'review');
});

test('canceling the countdown never turns the cue green or starts a late recording', async () => {
    const page = recorderHarness();
    await load(page);
    let release;
    let captured = false;
    page.recorder.capture.open = async () => {};
    page.recorder.delay = async (milliseconds, flow) => {
        await new Promise(resolve => { release = resolve; });
        if (flow !== page.recorder.flow) throw new DOMException('Canceled', 'AbortError');
    };
    page.recorder.capture.record = async () => { captured = true; };
    const recording = page.recorder.record();
    await settle();
    assert.equal(page.node('cue').dataset.state, 'waiting');
    page.recorder.cancel();
    release();
    await recording;
    assert.equal(captured, false);
    assert.equal(page.node('cue').dataset.state, 'idle');
    assert.equal(page.node('panel').hidden, true);
});

test('Sound keeps the four instruments and groups every recording by name without My voice', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.recorder.library.push({ id: 'second', name: 'Calm counting', revision: 'second-rev' });
    page.recorder.summary();
    assert.equal(page.option.parentNode, null);
    assert.equal(page.group.parentNode, page.soundInput);
    assert.equal(page.group.label, 'Recorded voices');
    assert.deepEqual(page.soundInput.children.slice(0, 4).map(option => option.value), ['click', 'woodblock', 'beep', 'cowbell']);
    assert.deepEqual(Array.from(page.group.children, option => option.value), ['voice:first', 'voice:second']);
    assert.deepEqual(Array.from(page.group.children, option => option.text), ['Calm counting', 'Calm counting']);
    assert.equal(page.soundInput.value, 'click');
});

test('choosing a named Sound loads that recording and other settings keep its displayed name', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.recorder.library.push({ id: 'second', name: 'Second voice', revision: 'second-rev' });
    page.recorder.summary();
    page.soundInput.value = 'voice:second';
    assert.equal(page.recorder.selectSound(page.soundInput.value), true);
    assert.equal(page.requests[2].url, '/voice/second');
    page.resolve(page.requests[2], page.detail('second', 'Second voice'));
    await settle();
    assert.equal(page.soundInput.value, 'voice:second');
    assert.equal(page.recorder.sound, 'my-voice');
    page.recorder.syncSound('my-voice');
    assert.equal(page.soundInput.value, 'voice:second');
    page.recorder.syncSound('beep');
    page.recorder.remember('beep');
    assert.equal(page.soundInput.value, 'beep');
    assert.equal(page.recorder.selectSound('beep'), false);
});

test('My voice is available only for an empty library and starts the recorder without changing the instrument', async () => {
    const page = recorderHarness();
    await load(page);
    assert.equal(page.option.parentNode, page.soundInput);
    assert.equal(page.option.hidden, false);
    assert.equal(page.group.parentNode, null);
    let recording = false;
    page.recorder.record = () => { recording = true; };
    page.soundInput.value = 'my-voice';
    assert.equal(page.recorder.selectSound('my-voice'), true);
    assert.equal(recording, true);
    assert.equal(page.soundInput.value, 'click');
    assert.equal(page.clock.buffers, null);
});

test('a row preview plays another recording without replacing the selected sound', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    await load(page);
    page.recorder.library.push({ id: 'second', name: 'Second voice', revision: 'second-rev' });
    const playing = page.recorder.previewRecording('second');
    await settle();
    assert.equal(page.requests[2].url, '/voice/second');
    page.resolve(page.requests[2], page.detail('second', 'Second voice'));
    await playing;
    assert.equal(page.recorder.activeId, 'first');
    assert.equal(page.soundInput.value, 'voice:first');
    assert.equal(page.recorder.previewingId, 'second');
    assert.equal(page.recorder.previewSources.length, 3);
    await page.recorder.previewRecording('second');
    assert.equal(page.recorder.previewingId, null);
    assert.equal(page.recorder.previewSources.length, 0);
});

test('closing a menu during preview loading prevents late sound', async () => {
    const page = recorderHarness({ saved: true });
    await load(page);
    page.recorder.library.push({ id: 'second', name: 'Second', revision: 'second-rev' });
    const playing = page.recorder.previewRecording('second');
    await settle();
    page.recorder.stopPreview();
    page.resolve(page.requests[2], page.detail('second'));
    await playing;
    assert.equal(page.recorder.previewSources.length, 0);
    assert.equal(page.recorder.previewingId, null);
    assert.equal(page.soundInput.value, 'click');
});

test('the edit row action renames its target while preserving another selected voice', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    await load(page);
    const buffers = page.clock.buffers;
    page.recorder.library.push({ id: 'second', name: 'Second', revision: 'second-rev' });
    page.recorder.edit('second');
    assert.equal(page.node('rename-input').value, 'Second');
    page.node('rename-input').value = 'Gentle voice';
    const renaming = page.recorder.rename();
    assert.equal(page.requests[2].url, '/voice/second');
    assert.equal(JSON.parse(page.requests[2].options.body).revision, 'second-rev');
    page.resolve(page.requests[2], { id: 'second', name: 'Gentle voice', revision: 'new-second-rev' });
    await renaming;
    assert.equal(page.recorder.library[1].name, 'Gentle voice');
    assert.equal(page.recorder.activeId, 'first');
    assert.equal(page.soundInput.value, 'voice:first');
    assert.equal(page.clock.buffers, buffers);
});

test('the delete row action removes only its target while preserving the selected voice', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    await load(page);
    const buffers = page.clock.buffers;
    page.recorder.library.push({ id: 'second', name: 'Second', revision: 'second-rev' });
    const deleting = page.recorder.confirmDelete('second');
    assert.deepEqual(page.confirmations, ['Delete “Second”?']);
    assert.equal(page.requests[2].url, '/voice/second');
    page.resolve(page.requests[2], { deleted: 'second' });
    await deleting;
    assert.equal(page.recorder.library.length, 1);
    assert.equal(page.recorder.activeId, 'first');
    assert.equal(page.soundInput.value, 'voice:first');
    assert.equal(page.clock.buffers, buffers);
});


test('canceling the native delete confirmation preserves every voice and sends no request', async () => {
    const page = recorderHarness({ saved: true, preferred: true, confirmDeletion: false });
    await load(page);
    page.recorder.library.push({ id: 'second', name: 'Second', revision: 'second-rev' });
    const buffers = page.clock.buffers;
    await page.recorder.confirmDelete('second');
    assert.deepEqual(page.confirmations, ['Delete “Second”?']);
    assert.equal(page.requests.length, 2);
    assert.equal(page.recorder.library.length, 2);
    assert.equal(page.recorder.activeId, 'first');
    assert.equal(page.soundInput.value, 'voice:first');
    assert.equal(page.clock.buffers, buffers);
});
