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

function recorderHarness({ saved = false, preferred = false } = {}) {
    const nodes = new Map();
    const node = name => {
        if (!nodes.has(name)) nodes.set(name, { hidden: false, disabled: false, textContent: '', style: {}, listeners: {},
            addEventListener(event, callback) { this.listeners[event] = callback; }, setAttribute() {}, focus() {} });
        return nodes.get(name);
    };
    const requests = [];
    const sounds = [];
    const storage = new Map([['metronome.voice.10.v1', preferred ? '1' : '0']]);
    const root = { dataset: { url: '/voice', owner: 10, csrf: 'csrf' }, querySelector: selector => node(selector.slice(12, -1)), querySelectorAll: () => [] };
    const dialog = { dataset: {} };
    const option = {};
    const soundInput = { value: 'click', querySelector: () => option };
    const clock = { stop() {}, setVoiceBuffers(buffers) { this.buffers = buffers; } };
    const audioContext = { state: 'running', resume: async () => {}, decodeAudioData: async () => ({ duration: 0.2 }),
        createBufferSource: () => ({ connect() {}, disconnect() {}, start() {}, stop() {} }), currentTime: 0 };
    const scope = vm.createContext({ console, DOMException, Uint8Array, ArrayBuffer, atob, btoa,
        voiceBase64: context.api.voiceBase64, voiceBytes: context.api.voiceBytes,
        localStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) },
        window: { AudioContext: function () { return audioContext; }, MediaRecorder: {}, isSecureContext: true },
        navigator: { mediaDevices: { getUserMedia() {} } }, setTimeout: fn => fn(),
        fetch: (url, options) => new Promise(resolve => requests.push({ url, options, resolve })),
        VoiceCapture: class { cancel() {} },
    });
    vm.runInContext(read('voice-recorder') + '\nglobalThis.VoiceRecorder = VoiceRecorder;', scope);
    const recorder = new scope.VoiceRecorder({ root, dialog, clock, soundInput, getBeats: () => 3,
        useSound(sound) { soundInput.value = sound; sounds.push(sound); } });
    const data = () => ({ revision: saved ? 'old' : null, clips: saved ? ['YQ==', 'YQ==', 'YQ==', 'YQ=='] : [] });
    const resolve = (request, body, status = 200) => request.resolve({ ok: status === 200, status, json: async () => body });
    return { recorder, nodes, node, requests, sounds, storage, resolve, data, clock, dialog, soundInput, option };
}
const settle = async () => { for (let i = 0; i < 15; i++) await Promise.resolve(); };

test('a late voice load cannot override a sound the user just chose', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    page.recorder.remember('woodblock');
    page.soundInput.value = 'woodblock';
    page.resolve(page.requests[0], page.data());
    await settle();
    assert.equal(page.option.disabled, false);
    assert.equal(page.soundInput.value, 'woodblock');
    assert.equal(page.storage.get('metronome.voice.10.v1'), '0');
});

test('voice preferences are restored only for the current account after its clips load', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    assert.equal(page.option.disabled, true);
    page.resolve(page.requests[0], page.data());
    await settle();
    assert.equal(page.soundInput.value, 'my-voice');
    assert.equal(page.clock.buffers.length, 4);
    assert.equal(page.requests[0].options.credentials, 'same-origin');
    assert.equal(page.requests[0].options.cache, 'no-store');
});

test('failed saves keep all draft clips for retry; a successful save selects the voice', async () => {
    const page = recorderHarness();
    page.resolve(page.requests[0], page.data());
    await settle();
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
    page.resolve(page.requests[2], { revision: 'new', clips: ['a', 'b', 'c', 'd'] });
    await retry;
    assert.equal(page.recorder.revision, 'new');
    assert.equal(page.soundInput.value, 'my-voice');
    assert.equal(page.storage.get('metronome.voice.10.v1'), '1');
});

test('closing during a save cannot reopen the recorder or play a late preview', async () => {
    const page = recorderHarness();
    page.resolve(page.requests[0], page.data());
    await settle();
    page.recorder.draft = [1, 2, 3, 4].map(() => new Uint8Array([1]).buffer);
    const saving = page.recorder.save();
    await settle();
    page.recorder.cancel();
    page.resolve(page.requests[1], { revision: 'new', clips: ['a', 'b', 'c', 'd'] });
    await saving;
    assert.equal(page.recorder.ready, false);
    assert.equal(page.soundInput.value, 'click');
    assert.equal(page.recorder.previewSources.length, 0);
});

test('an expired session disables private playback and offers sign-in', async () => {
    const page = recorderHarness({ saved: true, preferred: true });
    page.resolve(page.requests[0], page.data());
    await settle();
    const reload = page.recorder.load();
    page.resolve(page.requests[1], {}, 401);
    await reload;
    assert.equal(page.soundInput.value, 'click');
    assert.equal(page.clock.buffers, null);
    assert.equal(page.option.disabled, true);
    assert.equal(page.node('signin').hidden, false);
});

test('microphone denial offers a clear retry without replacing a saved voice', async () => {
    const page = recorderHarness({ saved: true });
    page.resolve(page.requests[0], page.data());
    await settle();
    page.recorder.capture.open = async () => { throw new DOMException('Denied', 'NotAllowedError'); };
    await page.recorder.record();
    assert.match(page.node('status').textContent, /Allow the microphone/);
    assert.equal(page.node('retry').hidden, false);
    assert.equal(page.recorder.saved.length, 4);
    assert.equal(page.requests.length, 1);
});

test('a retry resumes at the missed count and keeps the earlier numbers', async () => {
    const page = recorderHarness();
    page.resolve(page.requests[0], page.data());
    await settle();
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
