const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const root = path.join(__dirname, '../../resources/js');
const read = name => fs.readFileSync(path.join(root, name), 'utf8')
    .replace(/^import .*;\n/gm, '')
    .replace(/^export /gm, '');
const source = ['gesture', 'settings', 'conductor', 'voice-stretch', 'clock'].map(name => read(`metronome/${name}.js`)).join('\n');

function voiceBuffer(seconds, frequency = 180, rate = 24000) {
    const data = Float32Array.from({ length: Math.round(seconds * rate) }, (_, i) =>
        0.5 * Math.sin(2 * Math.PI * frequency * i / rate));
    return { length: data.length, sampleRate: rate, numberOfChannels: 1,
        duration: data.length / rate, getChannelData: () => data };
}

function setup({ deferredResume = false, resumeError = false } = {}) {
    const timers = new Map();
    const timeouts = new Map();
    let wallTime = 0;
    let timerId = 0;
    const oscillators = [];
    const states = [];
    const errors = [];
    const beatIndices = [];
    let beatCount = 0;
    let resolveResume;
    const audioParam = () => ({
        setValueAtTime(value, time) { this.value = value; this.time = time; },
        linearRampToValueAtTime() {},
        exponentialRampToValueAtTime() {},
        cancelScheduledValues() {},
    });
    const audio = {
        currentTime: 0,
        state: 'suspended',
        destination: {},
        suspend() { this.state = 'suspended'; return Promise.resolve(); },
        close() { this.state = 'closed'; return Promise.resolve(); },
        resume() {
            if (resumeError) return Promise.reject(new Error('Blocked'));
            if (deferredResume) return new Promise(resolve => {
                resolveResume = () => { this.state = 'running'; resolve(); };
            });
            this.state = 'running';
            return Promise.resolve();
        },
        createOscillator() {
            const oscillator = {
                frequency: audioParam(),
                connect() {},
                disconnect() { this.disconnected = true; },
                start(time) { this.startTime = time; },
                stop(time) { this.stopTime = time === undefined ? audio.currentTime : time; },
            };
            oscillators.push(oscillator);
            return oscillator;
        },
        createGain() {
            return { gain: audioParam(), connect() {}, disconnect() {} };
        },
        createBufferSource() {
            const source = this.createOscillator();
            source.playbackRate = audioParam();
            return source;
        },
        createBuffer(channels, length, rate) {
            const data = Array.from({ length: channels }, () => new Float32Array(length));
            return { length, sampleRate: rate, numberOfChannels: channels, duration: length / rate,
                getChannelData: channel => data[channel] };
        },
    };
    const context = vm.createContext({
        setInterval(fn) { timers.set(++timerId, fn); return timerId; },
        clearInterval(id) { timers.delete(id); },
        setTimeout(fn, delay) {
            timeouts.set(++timerId, { fn, at: wallTime + delay });
            return timerId;
        },
        clearTimeout(id) { timeouts.delete(id); },
        requestAnimationFrame() { return ++timerId; },
        cancelAnimationFrame() {},
    });
    vm.runInContext(`${source}\nglobalThis.api = { TempoGesture, MetronomeClock, normalizeSettings, loadSettings, loadTempo, colorPalette, conductorPoint, conductorPreparationPoint, conductorPreparationBeats, CONDUCTING_PATTERNS };`, context);
    const clock = new context.api.MetronomeClock({
        createContext: () => audio,
        onStateChange: value => states.push(value),
        onBeat: index => { beatCount++; beatIndices.push(index); },
        onError: error => errors.push(error),
    });
    return {
        context, clock, audio, timers, oscillators, states, errors, beatIndices,
        get beatCount() { return beatCount; },
        resume: () => resolveResume(),
        tick(milliseconds) {
            const until = wallTime + milliseconds;
            while (timeouts.size) {
                const [id, timeout] = [...timeouts].sort((a, b) => a[1].at - b[1].at)[0];
                if (timeout.at > until) break;
                wallTime = timeout.at;
                timeouts.delete(id);
                timeout.fn();
            }
            wallTime = until;
        },
        advance(time) {
            audio.currentTime = time;
            for (const tick of timers.values()) tick();
        },
    };
}

test('slow swipes allow fine changes; fast swipes cover more tempo in either direction', () => {
    const { TempoGesture } = setup().context.api;
    const slow = new TempoGesture(120, 0, 200, 0);
    const fast = new TempoGesture(120, 0, 200, 0);
    assert.equal(slow.move(0, 160, 400), 129);
    assert.equal(fast.move(0, 160, 20), 152);
    assert.equal(slow.move(0, 200, 800), 120);
    assert.equal(fast.move(0, 200, 40), 120);
});

test('constant-speed swipes are independent of pointer event frequency', () => {
    const { TempoGesture } = setup().context.api;
    const one = new TempoGesture(120, 0, 200, 0);
    const many = new TempoGesture(120, 0, 200, 0);
    const expected = one.move(0, 100, 100);
    let actual;
    for (let step = 1; step <= 20; step++) actual = many.move(0, 200 - step * 5, step * 5);
    assert.equal(actual, expected);
});

test('tap jitter keeps the tempo unchanged and bounds can be reversed immediately', () => {
    const { TempoGesture } = setup().context.api;
    const tap = new TempoGesture(120, 100, 100, 0);
    assert.equal(tap.move(102, 98, 100), 120);
    assert.equal(tap.moved, false);
    const swipe = new TempoGesture(120, 0, 500, 0);
    assert.equal(swipe.move(0, 0, 20), 300);
    assert.ok(swipe.move(0, 10, 40) < 300);
    assert.equal(swipe.move(0, 1000, 60), 20);
    assert.ok(swipe.move(0, 990, 80) > 20);
});

test('clicks follow the audio clock at 120 BPM and stop cancels queued sound', async () => {
    const page = setup();
    await page.clock.start();
    assert.equal(page.oscillators[0].startTime, 0.02);
    page.advance(0.45);
    assert.equal(page.oscillators[1].startTime, 0.52);
    page.advance(0.95);
    assert.equal(page.oscillators[2].startTime, 1.02);
    page.clock.stop();
    assert.equal(page.timers.size, 0);
    assert.equal(page.clock.running, false);
    assert.ok(page.oscillators.every(note => note.disconnected));
    assert.equal(page.clock.beats.length, 0);
});

test('stop/start keeps a stable playback session and suspends unused output until recording releases it', async () => {
    const page = setup();
    const session = { type: 'auto' };
    page.context.navigator = { audioSession: session };
    page.clock.createContext = () => {
        assert.equal(session.type, 'playback', 'Silent Mode must not mute the metronome');
        return page.audio;
    };
    assert.equal(session.type, 'auto', 'Loading the metronome leaves the audio session alone');
    await page.clock.start();
    assert.equal(session.type, 'playback');
    assert.equal(page.clock.ready, true);
    page.clock.stop();
    assert.equal(session.type, 'playback', 'Stopping must not reconfigure the phone audio route');
    assert.equal(page.audio.state, 'suspended', 'Unused output releases audio focus');
    await page.clock.start();
    assert.equal(session.type, 'playback', 'Reusing an audio context requests playback again');
    page.audio.state = 'interrupted';
    page.audio.onstatechange();
    assert.equal(page.clock.running, false);
    assert.equal(session.type, 'playback');
    await page.clock.releaseAudio();
    assert.equal(session.type, 'auto', 'Recording releases the playback session');
    assert.equal(page.audio.state, 'closed');
    assert.equal(page.clock.context, null);
});

test('failed/canceled starts stay stopped and release their session when disposed', async () => {
    for (const options of [{ resumeError: true }, { deferredResume: true }]) {
        const page = setup(options);
        const session = { type: 'ambient' };
        page.context.navigator = { audioSession: session };
        const started = page.clock.start();
        if (options.deferredResume) {
            assert.equal(session.type, 'playback');
            page.clock.stop();
            page.resume();
        }
        await started;
        await page.clock.releaseAudio();
        assert.equal(session.type, 'ambient');
        assert.equal(page.clock.ready, false);
        assert.equal(page.timers.size, 0);
    }
});

test('missing or restricted audio-session APIs preserve ordinary playback', async () => {
    const restricted = { get audioSession() { throw new Error('Unavailable'); } };
    const readOnly = { audioSession: { get type() { return 'auto'; }, set type(value) { throw new Error('Read only'); } } };
    for (const navigator of [{}, restricted, readOnly]) {
        const page = setup();
        page.context.navigator = navigator;
        await page.clock.start();
        assert.equal(page.clock.ready, true);
        assert.equal(page.errors.length, 0);
        assert.ok(page.oscillators.length > 0);
        page.clock.stop();
    }
});

test('stopping preserves a session type changed by another audio feature', async () => {
    const page = setup();
    const session = { type: 'auto' };
    page.context.navigator = { audioSession: session };
    await page.clock.start();
    session.type = 'play-and-record';
    page.clock.stop();
    assert.equal(session.type, 'play-and-record');
});

test('a tempo change preserves beat progress and replaces queued clicks', async () => {
    const page = setup();
    await page.clock.start();
    page.advance(0.45);
    const oldClick = page.oscillators[1];
    page.clock.setTempo(60);
    assert.equal(oldClick.disconnected, true);
    // 86% through the old beat leaves 14% of a one-second beat.
    assert.ok(Math.abs(page.clock.nextBeatTime - 0.59) < 0.000001);
    page.advance(0.51);
    assert.ok(Math.abs(page.oscillators[2].startTime - 0.59) < 0.000001);
    assert.equal(page.beatCount, 1);
});

test('instrument and recorded-voice visuals follow delayed speaker output while sound keeps its exact tempo', async () => {
    for (const sound of ['click', 'woodblock', 'beep', 'cowbell', 'my-voice']) {
        const page = setup();
        page.audio.outputLatency = 0.25;
        page.clock.setBeats(4);
        page.clock.setVoiceBuffers([1, 2, 3, 4].map(number => ({ number, duration: 0.3 })));
        page.clock.setSound(sound);
        await page.clock.start();
        for (let tick = 1; tick <= 10; tick++) page.advance(tick * 0.025);
        assert.equal(page.beatCount, 0, 'Buffered sound has not reached the speaker yet');
        page.advance(0.27);
        assert.deepEqual(page.beatIndices, [0]);
        assert.ok(page.clock.getBeatPosition().progress < 1e-8);
        for (let tick = 12; tick <= 30; tick++) page.advance(tick * 0.025);
        assert.equal(page.clock.getBeatPosition().beat, 0, 'The next rendered count is still buffered');
        page.advance(0.77);
        assert.deepEqual(page.beatIndices, [0, 1]);
        assert.ok(page.clock.getBeatPosition().progress < 1e-8);
        const times = [...new Set(page.oscillators.map(note => note.startTime))];
        assert.deepEqual(times, [0.02, 0.52], 'Visual compensation must never delay or retime sound');
        page.clock.stop();
    }
});

test('output timestamps interpolate device time and invalid timestamps fall back to reported latency', async () => {
    const page = setup();
    page.clock.setBeats(4);
    await page.clock.start();
    page.advance(0.45);
    page.audio.outputLatency = 0.4;
    page.context.performance = { now: () => 1020 };
    page.audio.getOutputTimestamp = () => ({ contextTime: 0.5, performanceTime: 1000 });
    page.advance(0.75);
    assert.equal(page.clock.getBeatPosition().beat, 1);
    assert.equal(page.clock.getBeatPosition().progress, 0);
    for (const stamp of [{ contextTime: 0, performanceTime: 0 }, { contextTime: -1, performanceTime: 1000 },
        { contextTime: NaN, performanceTime: 1000 }, { contextTime: 0.5, performanceTime: -1000 }]) {
        page.audio.getOutputTimestamp = () => stamp;
        assert.equal(page.clock.outputTime(), 0.35);
    }
    page.audio.getOutputTimestamp = () => { throw new Error('Unsupported'); };
    assert.equal(page.clock.outputTime(), 0.35);
    page.clock.stop();
});

test('tempo edits retain the audible beat until the buffered change reaches the speaker', async () => {
    const page = setup();
    page.audio.outputLatency = 0.2;
    page.clock.setBeats(4);
    await page.clock.start();
    for (let tick = 1; tick <= 18; tick++) page.advance(tick * 0.025);
    page.advance(0.47);
    const oldClick = page.oscillators.at(-1);
    const before = page.clock.getBeatPosition();
    page.clock.setTempo(60);
    assert.equal(oldClick.disconnected, true);
    assert.equal(page.clock.getBeatPosition().progress, before.progress);
    assert.equal(page.clock.getBeatPosition().tempo, 120);
    for (let tick = 20; tick <= 26; tick++) page.advance(tick * 0.025);
    page.advance(0.67);
    assert.equal(page.clock.getBeatPosition().tempo, 60);
    assert.ok(Math.abs(page.clock.getBeatPosition().progress - 0.9) < 1e-8);
    page.advance(0.77);
    assert.deepEqual(page.beatIndices, [0, 1]);
    assert.ok(page.clock.getBeatPosition().progress < 1e-8);
    assert.ok(Math.abs(page.oscillators.at(-1).startTime - 0.57) < 1e-8);
    page.clock.stop();
});

test('a late context close cannot reset the session of a newer playback start', async () => {
    const page = setup();
    const session = { type: 'auto' };
    page.context.navigator = { audioSession: session };
    await page.clock.start();
    let finishClose;
    page.audio.close = () => new Promise(resolve => { finishClose = resolve; });
    const closing = page.clock.releaseAudio();
    await page.clock.start();
    finishClose();
    await closing;
    assert.equal(session.type, 'playback');
    assert.equal(page.clock.ready, true);
    page.clock.stop();
});

test('stopping while audio unlocks cannot restart playback asynchronously', async () => {
    const page = setup({ deferredResume: true });
    const starting = page.clock.start();
    page.clock.stop();
    page.resume();
    await starting;
    assert.equal(page.clock.running, false);
    assert.equal(page.timers.size, 0);
    assert.equal(page.oscillators.length, 0);
});

test('audio failures restore the stopped state and report the failure', async () => {
    const page = setup({ resumeError: true });
    await page.clock.start();
    assert.equal(page.clock.running, false);
    assert.equal(page.timers.size, 0);
    assert.equal(page.errors.length, 1);
    assert.deepEqual(page.states, [true, false]);
});

test('a stalled scheduler resumes without a burst of missed beats', async () => {
    const page = setup();
    await page.clock.start();
    page.advance(10);
    assert.equal(page.oscillators.length, 2);
    assert.equal(page.oscillators[1].startTime, 10.01);
});

test('personal voice counts wrap at the chosen beat count and preserve audio-clock timing', async () => {
    for (const beats of [1, 2, 3, 4]) {
        const page = setup();
        page.clock.setVoiceBuffers([1, 2, 3, 4].map(number => ({ number, duration: 0.3 })));
        page.clock.setSound('my-voice');
        page.clock.setBeats(beats);
        await page.clock.start();
        for (let i = 1; i <= beats; i++) page.advance(i * 0.5 - 0.05);
        assert.deepEqual(page.oscillators.map(source => source.buffer.number), [...Array(beats).keys()].map(i => i + 1).concat(1));
        page.oscillators.forEach((source, i) => {
            assert.ok(Math.abs(source.startTime - (0.02 + i * 0.5)) < 1e-8);
            assert.equal(source.playbackRate.value, 1);
        });
        page.clock.stop();
        assert.ok(page.oscillators.every(source => source.disconnected));
    }
});

test('fast personal counting fits each beat and deleting a voice restores an instrument', async () => {
    const page = setup();
    page.clock.setVoiceBuffers([1, 2, 3, 4].map(number => voiceBuffer(0.5, 100 + number * 40)));
    page.clock.setSound('my-voice');
    page.clock.setTempo(300);
    await page.clock.start();
    page.advance(0.15);
    assert.ok(page.oscillators[0].stopTime < page.oscillators[1].startTime);
    assert.equal(page.oscillators[0].playbackRate.value, 1);
    assert.ok(page.oscillators[0].buffer.duration <= 60 / 300 * 0.94);
    page.clock.setVoiceBuffers(null);
    assert.equal(page.clock.sound, 'click');
    page.clock.setSound('my-voice');
    assert.equal(page.clock.sound, 'click');
});

test('voice compression is reused between beats and slower tempos restore the exact original recording', async () => {
    const page = setup();
    const original = [1, 2, 3, 4].map(number => voiceBuffer(0.5, 100 + number * 40));
    page.clock.setVoiceBuffers(original);
    page.clock.setSound('my-voice');
    page.clock.setTempo(300);
    await page.clock.start();
    const compressed = page.oscillators[0].buffer;
    page.audio.createBuffer = () => { throw new Error('Compression must not run in the beat scheduler'); };
    for (let tick = 1; tick <= 30; tick++) page.advance(tick * 0.025);
    assert.equal(page.oscillators.at(-1).buffer, compressed);
    assert.ok(page.oscillators.every(source => source.playbackRate.value === 1));
    page.clock.setTempo(60);
    page.advance(1.6);
    assert.equal(page.oscillators.at(-1).buffer, original[0]);
    assert.equal(page.oscillators.at(-1).playbackRate.value, 1);
    page.clock.stop();
});

function setupPage(savedSettings = null, { reducedMotion = false, savedTempo = null } = {}) {
    const page = setup();
    const listeners = {};
    const attributes = {};
    const classes = new Set();
    const animations = [];
    const animate = function (keyframes, options) {
        let startTime = page.audio.currentTime * 1000;
        const animation = { target: this, keyframes, options: { ...options }, canceled: false, seeks: 0,
            get startTime() { return startTime; },
            set startTime(value) { startTime = value; },
            get currentTime() { return page.audio.currentTime * 1000 - startTime; },
            set currentTime(value) { startTime = page.audio.currentTime * 1000 - value; this.seeks++; },
            effect: {
                updateTiming(timing) { Object.assign(animation.options, timing); },
                setKeyframes(frames) { animation.keyframes = frames; },
            },
            cancel() { this.canceled = true; }, updatePlaybackRate() {}, playState: 'running' };
        animations.push(animation);
        return animation;
    };
    function element(extra = {}) {
        const attrs = {};
        return {
            listeners: {}, dataset: {}, attributes: attrs, value: '', checked: false,
            classList: { toggle() {}, remove() {} },
            style: { setProperty(name, value) { this[name] = value; } },
            setAttribute: (name, value) => { attrs[name] = value; },
            focus() { this.focused = true; },
            addEventListener(name, handler) { this.listeners[name] = handler; },
            ...extra,
        };
    }
    const surface = {
        dataset: {},
        animate,
        setAttribute: (name, value) => { attributes[name] = value; },
        setPointerCapture() {},
        addEventListener: (name, callback) => { listeners[name] = callback; },
        classList: {
            toggle: (name, on) => on ? classes.add(name) : classes.delete(name),
            remove: name => classes.delete(name),
        },
    };
    const edges = [-1, 1].map(step => ({
        dataset: { tempoStep: String(step) },
        listeners: {},
        setAttribute() {},
        setPointerCapture() {},
        closest: selector => selector.includes('[data-tempo-step]'),
        addEventListener(name, callback) { this.listeners[name] = callback; },
    }));
    const number = { textContent: '120', dataset: {}, animate };
    const pulseLayer = element({ animate });
    const message = { hidden: true };
    const documentListeners = {};
    const windowListeners = {};
    const settingsButton = element();
    const motionPreference = {
        matches: reducedMotion,
        addEventListener(name, handler) { this.onchange = handler; },
    };
    const settingsDialog = element({ open: false, querySelector: () => null });
    const hubMenu = element();
    page.context.initializeLogoTilt = () => {};
    // Bootstrap owns transitions and dismissal; expose its public lifecycle here.
    page.context.Offcanvas = class {
        constructor(panel) { this.panel = panel; page.offcanvas = this; }
        show() {
            if (this.panel.open) return;
            this.panel.open = true;
            this.panel.listeners['show.bs.offcanvas']();
        }
        hide() {
            if (!this.panel.open || this.closing) return;
            this.closing = true;
            this.panel.listeners['hide.bs.offcanvas']();
            page.context.setTimeout(() => {
                this.panel.open = false;
                this.closing = false;
                this.panel.listeners['hidden.bs.offcanvas']();
            }, 300);
        }
    };
    const closeButton = element();
    closeButton.listeners.click = () => page.offcanvas.hide();
    const beatInputs = [1, 2, 3, 4].map(value => element({ value: String(value) }));
    const typeInputs = ['classic', 'conductor'].map(value => element({ value }));
    const markerInputs = ['dot', 'hand'].map(value => element({ value }));
    const conductorOptions = element({ hidden: true });
    const colorInput = element();
    const colorValue = element();
    const colorReset = element();
    const soundInput = element();
    const fontInput = element();
    const pathInput = element();
    const conductorAnimations = [];
    const beatNumbers = [1, 2, 3, 4].map(value => element({ textContent: String(value), animate }));
    const conductorParts = Object.fromEntries(['path', 'ball'].map(name => [name, element()]));
    conductorParts.ball.animate = (keyframes, options) => {
        const animation = animate.call(conductorParts.ball, keyframes, options);
        conductorAnimations.push(animation);
        return animation;
    };
    const svg = element({
        querySelector: selector => conductorParts[selector.replace('[data-conductor-', '').replace(']', '')],
        querySelectorAll: selector => selector === '[data-conductor-count]' ? beatNumbers : [],
    });
    const theme = element();
    const storage = new Map(savedSettings ? [['metronome.settings.v1', savedSettings]] : []);
    if (savedTempo !== null) storage.set('metronome.tempo.v1', savedTempo);
    page.context.document = {
        documentElement: element(),
        querySelectorAll: selector => ({
            '[data-tempo-step]': edges,
            '[name="metronome-beats"]': beatInputs,
            '[name="metronome-type"]': typeInputs,
            '[name="metronome-marker"]': markerInputs,
            '[data-settings-close]': [closeButton],
        })[selector] || [],
        querySelector: selector => ({
            '[data-metronome]': surface, '[data-tempo]': number, '[data-metronome-message]': message,
            '[data-metronome-pulse]': pulseLayer,
            '[data-settings-open]': settingsButton, '[data-settings-panel]': settingsDialog,
            '#public-hub-menu': hubMenu,
            '[data-settings-color]': colorInput, '[data-color-value]': colorValue,
            '[data-settings-color-reset]': colorReset,
            '#metronome-sound': soundInput, '[data-conductor]': svg, 'meta[name="theme-color"]': theme,
            '#metronome-font': fontInput,
            '[name="metronome-path"]': pathInput,
            '[data-conductor-options]': conductorOptions,
        })[selector],
        addEventListener: (name, callback) => { documentListeners[name] = callback; },
    };
    page.context.window = {
        AudioContext: function () { return page.audio; },
        matchMedia: () => motionPreference,
        localStorage: { getItem: key => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value) },
        addEventListener: (name, callback) => { windowListeners[name] = callback; },
    };
    page.context.getComputedStyle = () => ({ transform: 'matrix(1, 0, 0, 1, 0, 120)' });
    vm.runInContext(read('metronome.js'), page.context);
    const pointer = (type, x, y, time, extra = {}) => listeners[type]({
        pointerId: 1, isPrimary: true, button: 0, clientX: x, clientY: y, timeStamp: time, ...extra,
    });
    const click = () => listeners.click({ detail: 1, preventDefault() {} });
    const edge = (index, type, extra = {}) => edges[index].listeners[type]({
        pointerId: 1, isPrimary: true, button: 0, detail: 1, target: edges[index],
        preventDefault() {}, ...extra,
    });
    return { ...page, edges, edge, attributes, number, pointer, click, listeners, documentListeners, windowListeners,
        settingsButton, settingsDialog, hubMenu, closeButton, beatInputs, typeInputs, markerInputs, conductorOptions,
        colorInput, soundInput, fontInput, pathInput, storage, surface,
        conductorParts, conductorAnimations, beatNumbers, animations, pulseLayer, theme, motionPreference };
}

function ballPoint(transform) {
    return transform.match(/[-\d.]+(?=%)/g).map(value => (Number(value) / 100 + 0.5) * 44);
}

function animatedBallPoint(page) {
    const animation = page.conductorAnimations.at(-1);
    if (!animation || animation.canceled) return ballPoint(page.conductorParts.ball.style.transform);
    const { duration } = animation.options;
    const phase = (animation.currentTime % duration) / duration;
    const afterIndex = animation.keyframes.findIndex(frame => frame.offset > phase);
    const before = animation.keyframes[afterIndex - 1];
    const after = animation.keyframes[afterIndex];
    const t = (phase - before.offset) / (after.offset - before.offset);
    const from = ballPoint(before.transform);
    const to = ballPoint(after.transform);
    return from.map((value, axis) => value + (to[axis] - value) * t);
}

function assertBallNear(actual, expected) {
    assert.ok(Math.hypot(actual[0] - expected[0], actual[1] - expected[1]) < 0.1,
        `Expected ball at ${expected}, received ${actual}`);
}

test('taps toggle playback and releasing a swipe does not toggle it', async () => {
    const page = setupPage();
    page.pointer('pointerdown', 100, 200, 0);
    page.pointer('pointerup', 100, 200, 50);
    page.click();
    await Promise.resolve();
    assert.equal(page.attributes['aria-pressed'], 'true');
    page.pointer('pointerdown', 100, 200, 100);
    page.pointer('pointermove', 100, 150, 125);
    page.pointer('pointerup', 100, 100, 150);
    page.click();
    assert.equal(page.number.textContent, '200');
    assert.equal(page.attributes['aria-pressed'], 'true');
    page.pointer('pointerdown', 100, 200, 200);
    page.pointer('pointerup', 100, 200, 250);
    page.click();
    assert.equal(page.attributes['aria-pressed'], 'false');
});

test('canceled gestures do not toggle playback; keyboard remains usable', () => {
    const page = setupPage();
    page.pointer('pointerdown', 100, 200, 0);
    page.pointer('pointercancel', 100, 200, 50);
    page.click();
    assert.equal(page.attributes['aria-pressed'], undefined);
    const key = (key, extra = {}) => page.documentListeners.keydown({ key, preventDefault() {}, ...extra });
    key('ArrowUp');
    assert.equal(page.number.textContent, '121');
    key('ArrowDown', { shiftKey: true });
    assert.equal(page.number.textContent, '111');
    key('Enter');
    assert.equal(page.attributes['aria-pressed'], 'true');
    key('Escape');
    assert.equal(page.attributes['aria-pressed'], 'false');
});

test('edge taps step once without toggling playback or repeating after release', async () => {
    const page = setupPage();
    page.edge(0, 'pointerdown');
    assert.equal(page.number.textContent, '119');
    page.edge(0, 'pointerup');
    page.edge(0, 'click');
    page.tick(1000);
    assert.equal(page.number.textContent, '119');
    assert.equal(page.attributes['aria-pressed'], undefined);

    page.click();
    await Promise.resolve();
    page.edge(1, 'pointerdown');
    page.edge(1, 'pointerup');
    page.edge(1, 'click');
    assert.equal(page.number.textContent, '120');
    assert.equal(page.attributes['aria-pressed'], 'true');
});

test('holding an edge repeats after a delay and release stops further changes', () => {
    const page = setupPage();
    page.edge(1, 'pointerdown');
    assert.equal(page.number.textContent, '121');
    page.tick(300);
    assert.equal(page.number.textContent, '121');
    page.tick(500);
    assert.ok(Number(page.number.textContent) > 125);
    page.edge(1, 'pointerup');
    page.edge(1, 'click');
    const releasedTempo = page.number.textContent;
    page.tick(2000);
    assert.equal(page.number.textContent, releasedTempo);
});

test('edge holds stop on cancellation, capture loss, window blur, hiding, or navigation', () => {
    for (const interrupt of [
        page => page.edge(1, 'pointercancel'),
        page => page.edge(1, 'lostpointercapture'),
        page => page.windowListeners.blur(),
        page => { page.context.document.hidden = true; page.documentListeners.visibilitychange(); },
        page => page.windowListeners.pagehide(),
    ]) {
        const page = setupPage();
        page.edge(1, 'pointerdown');
        page.tick(500);
        interrupt(page);
        const stoppedTempo = page.number.textContent;
        page.tick(2000);
        assert.equal(page.number.textContent, stoppedTempo);
    }
});

test('edge controls respect tempo limits, ignore secondary pointers, and support keyboard clicks', () => {
    const page = setupPage();
    page.edge(0, 'pointerdown', { isPrimary: false, pointerId: 2 });
    page.tick(1000);
    assert.equal(page.number.textContent, '120');
    page.edge(0, 'pointerdown');
    page.tick(30000);
    assert.equal(page.number.textContent, '20');
    page.edge(0, 'pointerup');
    page.edge(1, 'pointerdown');
    page.tick(30000);
    assert.equal(page.number.textContent, '300');
    page.edge(1, 'pointerup');
    page.documentListeners.keydown({
        key: 'Enter', target: page.edges[0],
        preventDefault() { assert.fail('Native edge activation should be allowed.'); },
    });
    page.edge(0, 'click', { detail: 0 });
    assert.equal(page.number.textContent, '299');
    assert.equal(page.attributes['aria-pressed'], undefined);
});

test('each beat group plays a normal first beat and lower remaining beats, then repeats', async () => {
    for (const count of [1, 2, 3, 4]) {
        const page = setup();
        page.clock.setBeats(count);
        await page.clock.start();
        for (let i = 1; i < count * 2; i++) page.advance(i * 0.5 - 0.05);
        const frequencies = page.oscillators.map(oscillator => oscillator.frequency.value);
        assert.deepEqual(frequencies, Array.from({ length: count * 2 }, (_, i) => i % count === 0 ? 1400 : 1400 * 0.82));
        page.clock.stop();
        await page.clock.start();
        assert.equal(page.oscillators.at(-1).frequency.value, 1400);
    }
});

test('all four sound options produce distinct voices and preserve the lower beat pitch', async () => {
    const signatures = new Set();
    for (const sound of ['click', 'woodblock', 'beep', 'cowbell']) {
        const page = setup();
        page.clock.setSound(sound);
        page.clock.setBeats(2);
        await page.clock.start();
        const first = page.oscillators.slice();
        signatures.add(first.map(voice => `${voice.type}:${voice.frequency.value}`).join(','));
        page.advance(0.45);
        page.oscillators.slice(first.length).forEach((voice, i) => {
            assert.ok(Math.abs(voice.frequency.value / first[i].frequency.value - 0.82) < 0.00001);
        });
        page.clock.stop();
        assert.ok(page.oscillators.every(voice => voice.disconnected));
    }
    assert.equal(signatures.size, 4);
});

test('changing beat groups or sounds replaces queued audio and begins the new group on a normal beat', async () => {
    const page = setup();
    page.clock.setBeats(4);
    await page.clock.start();
    page.advance(0.45);
    const queued = page.oscillators[1];
    page.clock.setBeats(3);
    assert.equal(queued.disconnected, true);
    assert.equal(page.oscillators.at(-1).startTime, 0.52);
    assert.equal(page.oscillators.at(-1).frequency.value, 1400);
    const replacement = page.oscillators.at(-1);
    page.clock.setSound('beep');
    assert.equal(replacement.disconnected, true);
    assert.equal(page.oscillators.at(-1).startTime, 0.52);
    assert.equal(page.oscillators.at(-1).frequency.value, 1000);
});

test('tempo edits preserve beat grouping and conductor progress', async () => {
    const page = setup();
    page.clock.setBeats(3);
    await page.clock.start();
    page.advance(0.27);
    assert.equal(page.clock.getBeatPosition().beat, 0);
    assert.equal(page.clock.getBeatPosition().progress, 0.5);
    page.clock.setTempo(60);
    assert.equal(page.clock.getBeatPosition().progress, 0.5);
    page.advance(0.7);
    assert.equal(page.oscillators.at(-1).frequency.value, 1400 * 0.82);
    page.advance(0.8);
    assert.equal(page.clock.getBeatPosition().beat, 1);
});

test('a tempo edit before the first click keeps that first click immediate', async () => {
    const page = setup();
    await page.clock.start();
    page.clock.setTempo(80);
    assert.equal(page.oscillators.at(-1).startTime, 0.02);
});

test('conductor playback stays continuous at beat boundaries and completes one path per bar', () => {
    const { conductorPoint, CONDUCTING_PATTERNS } = setup().context.api;
    for (const count of [1, 2, 3, 4]) {
        assert.deepEqual(Array.from(conductorPoint(count, 0, 0)), Array.from(CONDUCTING_PATTERNS[count][0].point));
        for (let beat = 0; beat < count; beat++) {
            assert.deepEqual(Array.from(conductorPoint(count, beat, 1)), Array.from(conductorPoint(count, (beat + 1) % count, 0)));
        }
    }
    const points = CONDUCTING_PATTERNS[4].map(segment => segment.point);
    assert.ok(points[0][1] > points[1][1] && points[0][1] > points[2][1]);
    assert.ok(points[1][0] < points[0][0] && points[2][0] > points[0][0]);
    assert.ok(points[3][1] < points[0][1]);
});

test('settings isolate keyboard and edge controls from playback and tempo', async () => {
    const page = setupPage();
    page.click();
    await Promise.resolve();
    page.settingsButton.listeners.click();
    assert.equal(page.settingsDialog.open, true);
    for (const key of ['ArrowUp', 'ArrowDown', 'Enter', 'Escape']) {
        page.documentListeners.keydown({ key, preventDefault() { assert.fail('Dialog keys should keep their native behavior.'); } });
    }
    page.edge(0, 'pointerdown');
    page.edge(0, 'click', { detail: 0 });
    page.tick(1000);
    assert.equal(page.number.textContent, '120');
    assert.equal(page.attributes['aria-pressed'], 'true');
    page.closeButton.listeners.click();
    assert.equal(page.settingsDialog.open, true, 'The modal remains active during its exit.');
    page.tick(300);
    assert.equal(page.settingsDialog.open, false);
    assert.equal(page.attributes['aria-pressed'], 'true');
});

test('Bootstrap settings dismissal isolates controls until hidden, then restores the opener', () => {
    const page = setupPage();
    page.settingsButton.listeners.click();
    assert.equal(page.settingsButton.attributes['aria-expanded'], 'true');
    page.offcanvas.hide();
    page.offcanvas.hide();
    page.click();
    page.documentListeners.keydown({ key: 'ArrowUp', preventDefault() { assert.fail('Panel keys stay isolated during dismissal.'); } });
    assert.equal(page.number.textContent, '120');
    assert.equal(page.clock.running, false);
    assert.equal(page.settingsButton.attributes['aria-expanded'], 'true');
    page.settingsButton.listeners.click();
    page.tick(300);
    assert.equal(page.settingsDialog.open, false);
    assert.equal(page.settingsButton.attributes['aria-expanded'], 'false');
    assert.equal(page.settingsButton.focused, true);
    page.settingsButton.listeners.click();
    assert.equal(page.settingsDialog.open, true, 'Settings can reopen after the Bootstrap hidden event.');
});

test('opening the shared settings panel stops an active tempo hold', () => {
    const page = setupPage();
    page.edge(1, 'pointerdown');
    page.tick(180);
    const tempo = page.number.textContent;
    page.settingsButton.listeners.click();
    page.tick(1500);
    assert.equal(page.number.textContent, tempo);
    page.offcanvas.hide();
    page.tick(300);
    page.documentListeners.keydown({ key: 'ArrowUp', preventDefault() {} });
    assert.equal(Number(page.number.textContent), Number(tempo) + 1);
});

test('settings apply immediately, stay readable for light colors, and persist across visits', () => {
    const page = setupPage();
    page.beatInputs[3].checked = true;
    page.beatInputs[3].listeners.change();
    page.typeInputs[1].checked = true;
    page.typeInputs[1].listeners.change();
    page.colorInput.value = '#ffffff';
    page.colorInput.listeners.input();
    page.soundInput.value = 'cowbell';
    page.soundInput.listeners.change();
    page.pathInput.checked = false;
    page.pathInput.listeners.change();
    page.markerInputs[1].checked = true;
    page.markerInputs[1].listeners.change();
    assert.equal(page.surface.dataset.mode, 'conductor');
    assert.equal(page.context.document.documentElement.style['--metronome-ink'], '#112037');
    assert.equal(page.context.document.documentElement.style['--site-header-ink'], '#000000');
    assert.equal(page.theme.attributes.content, '#ffffff');
    const saved = page.storage.get('metronome.settings.v1');
    assert.deepEqual(JSON.parse(saved), {
        beats: 4, color: '#ffffff', sound: 'cowbell', type: 'conductor', showPath: false, conductorMarker: 'hand', font: 'nunito',
    });
    const next = setupPage(saved);
    assert.equal(next.beatInputs[3].checked, true);
    assert.equal(next.surface.dataset.mode, 'conductor');
    assert.equal(next.soundInput.value, 'cowbell');
    assert.equal(next.colorInput.value, '#ffffff');
    assert.equal(next.pathInput.checked, false);
    assert.equal(next.surface.dataset.showPath, 'false');
    assert.equal(next.markerInputs[1].checked, true);
    assert.equal(next.surface.dataset.conductorMarker, 'hand');
});

test('hub navigation uses contrasting ink and isolates metronome keyboard controls until hidden', () => {
    const page = setupPage();
    const playbackState = page.attributes['aria-pressed'];
    assert.equal(page.context.document.documentElement.style['--site-header-ink'], '#ffffff');
    const tempo = page.number.textContent;
    page.edge(1, 'pointerdown');
    page.hubMenu.listeners['show.bs.offcanvas']();
    page.tick(600);
    assert.equal(Number(page.number.textContent), Number(tempo) + 1);
    const beforeMenuKey = page.number.textContent;
    page.documentListeners.keydown({ key: 'ArrowUp', preventDefault() { throw new Error('Menu arrow intercepted'); } });
    page.documentListeners.keydown({ key: 'Enter', preventDefault() { throw new Error('Menu link intercepted'); } });
    page.edge(1, 'pointerdown');
    assert.equal(page.number.textContent, beforeMenuKey);
    assert.equal(page.attributes['aria-pressed'], playbackState);
    page.hubMenu.listeners['hidden.bs.offcanvas']();
    page.documentListeners.keydown({ key: 'ArrowUp', preventDefault() {} });
    assert.equal(Number(page.number.textContent), Number(beforeMenuKey) + 1);
});

test('conductor indicator choices appear only in conductor mode and changing them preserves motion', async () => {
    const page = setupPage();
    assert.equal(page.conductorOptions.hidden, true);
    assert.equal(page.markerInputs[0].checked, true);
    page.typeInputs[1].checked = true;
    page.typeInputs[1].listeners.change();
    assert.equal(page.conductorOptions.hidden, false);
    page.click();
    await new Promise(setImmediate);
    page.advance(0.2);
    page.advance(0.26);
    page.advance(0.6);
    const animation = page.conductorAnimations.at(-1);
    const position = animatedBallPoint(page);
    page.markerInputs[1].checked = true;
    page.markerInputs[1].listeners.change();
    assert.equal(page.surface.dataset.conductorMarker, 'hand');
    assert.equal(page.conductorAnimations.at(-1), animation);
    assert.equal(animation.canceled, false);
    assert.deepEqual(animatedBallPoint(page), position);
    page.advance(0.7);
    assert.notDeepEqual(animatedBallPoint(page), position);
    page.markerInputs[0].checked = true;
    page.markerInputs[0].listeners.change();
    assert.equal(page.surface.dataset.conductorMarker, 'dot');
    assert.equal(page.conductorAnimations.at(-1), animation);
    page.typeInputs[0].checked = true;
    page.typeInputs[0].listeners.change();
    assert.equal(page.conductorOptions.hidden, true);
});

test('path visibility defaults off for existing settings and toggling it keeps the ball moving', async () => {
    const page = setupPage(JSON.stringify({ type: 'conductor', beats: 4 }));
    assert.equal(page.pathInput.checked, false);
    assert.equal(page.surface.dataset.showPath, 'false');
    page.click();
    await new Promise(setImmediate);
    page.advance(0.6);
    const animation = page.conductorAnimations.at(-1);
    const before = animatedBallPoint(page);
    page.pathInput.checked = true;
    page.pathInput.listeners.change();
    assert.equal(page.surface.dataset.showPath, 'true');
    assert.equal(animation.canceled, false);
    assert.equal(page.conductorAnimations.at(-1), animation);
    assert.equal(setupPage(page.storage.get('metronome.settings.v1')).pathInput.checked, true);
    page.advance(0.7);
    assert.notDeepEqual(animatedBallPoint(page), before);
    page.pathInput.checked = false;
    page.pathInput.listeners.change();
    assert.equal(page.surface.dataset.showPath, 'false');
    assert.equal(animation.canceled, false);
    assert.equal(JSON.parse(page.storage.get('metronome.settings.v1')).showPath, false);
});

test('tempo saves after five unchanged seconds, restarts the delay on edits, and restores on reopening', () => {
    const key = 'metronome.tempo.v1';
    const page = setupPage(null, { savedTempo: '90' });
    assert.equal(page.number.textContent, '90');
    assert.equal(vm.runInContext('clock.tempo', page.context), 90);
    page.edge(1, 'click', { detail: 0 });
    page.tick(4999);
    assert.equal(page.storage.get(key), '90');
    page.edge(1, 'click', { detail: 0 });
    page.tick(4999);
    assert.equal(page.storage.get(key), '90', 'A new edit must restart the full five-second delay.');
    assert.equal(setupPage(null, { savedTempo: page.storage.get(key) }).number.textContent, '90');
    page.tick(1);
    assert.equal(page.storage.get(key), '92');
    const restored = setupPage(null, { savedTempo: page.storage.get(key) });
    assert.equal(restored.number.textContent, '92');
    assert.equal(restored.attributes['aria-label'], 'Start metronome. 92 beats per minute.');
    assert.equal(vm.runInContext('clock.tempo', restored.context), 92);
    assert.equal(vm.runInContext('clock.running', restored.context), false);
});

test('settings edits and playback controls preserve the tempo save deadline', async () => {
    const page = setupPage(null, { savedTempo: '90' });
    page.edge(1, 'click', { detail: 0 });
    page.click();
    await new Promise(setImmediate);
    page.tick(3000);
    page.colorInput.value = '#601c8e';
    page.colorInput.listeners.input();
    page.click();
    assert.equal(page.storage.get('metronome.tempo.v1'), '90');
    assert.equal(JSON.parse(page.storage.get('metronome.settings.v1')).color, '#601c8e');
    page.tick(1999);
    assert.equal(page.storage.get('metronome.tempo.v1'), '90');
    page.tick(1);
    assert.equal(page.storage.get('metronome.tempo.v1'), '91');
    const restored = setupPage(page.storage.get('metronome.settings.v1'), {
        savedTempo: page.storage.get('metronome.tempo.v1'),
    });
    assert.equal(restored.number.textContent, '91');
    assert.equal(restored.colorInput.value, '#601c8e');
});

test('tempo persistence ignores unchanged values at the limit and tolerates unavailable storage', () => {
    const page = setupPage(null, { savedTempo: '299' });
    page.edge(1, 'click', { detail: 0 });
    page.tick(3000);
    page.edge(1, 'click', { detail: 0 });
    page.tick(2000);
    assert.equal(page.number.textContent, '300');
    assert.equal(page.storage.get('metronome.tempo.v1'), '300');

    const blocked = setupPage();
    blocked.context.window.localStorage.setItem = () => { throw new Error('Blocked'); };
    blocked.edge(1, 'click', { detail: 0 });
    assert.doesNotThrow(() => blocked.tick(5000));
    assert.equal(blocked.number.textContent, '121');
});

test('missing, corrupt, or out-of-range saved tempos fall back to 120 BPM', () => {
    for (const savedTempo of [null, '', '{invalid', 'null', '"150"', 'true', '{}', '[]', '19', '301', '120.5']) {
        assert.equal(setupPage(null, { savedTempo }).number.textContent, '120');
    }
    for (const savedTempo of ['20', '300']) {
        assert.equal(setupPage(null, { savedTempo }).number.textContent, savedTempo);
    }
    assert.equal(setup().context.api.loadTempo({ getItem() { throw new Error('Blocked'); } }), 120);
});

test('invalid or unavailable saved settings fall back to usable defaults', () => {
    const { normalizeSettings, loadSettings } = setup().context.api;
    const defaults = {
        beats: 1, color: '#0055fe', sound: 'click', type: 'classic', showPath: false, conductorMarker: 'dot', font: 'nunito',
    };
    assert.deepEqual(JSON.parse(JSON.stringify(normalizeSettings({ beats: 99, color: 'invalid', sound: 'toString', type: 'unknown' }))), defaults);
    assert.deepEqual(JSON.parse(JSON.stringify(loadSettings({ getItem() { throw new Error('Blocked'); } }))), defaults);
    assert.equal(setupPage('{invalid json').surface.dataset.mode, 'classic');
});


test('conductor preparation drops from the top to beat one for every pattern', () => {
    const { conductorPreparationPoint, conductorPoint, CONDUCTING_PATTERNS } = setup().context.api;
    for (const count of [1, 2, 3, 4]) {
        const start = conductorPreparationPoint(count, 0);
        const end = conductorPreparationPoint(count, 1);
        assert.ok(start[1] < end[1]);
        assert.deepEqual(Array.from(end), Array.from(conductorPoint(count, 0, 0)));
        if (count > 1) assert.deepEqual(Array.from(start), Array.from(CONDUCTING_PATTERNS[count][count - 1].point));
        let previousY = start[1];
        for (let step = 1; step <= 20; step++) {
            const position = conductorPreparationPoint(count, step / 20);
            assert.ok(position[1] > previousY, 'The preparation stroke should descend without reversing.');
            previousY = position[1];
        }
    }
});

test('conductor lead-in is silent and tempo edits keep the first click aligned with the drop', async () => {
    const page = setup();
    page.clock.setBeats(4);
    await page.clock.start({ leadInBeats: 1 });
    assert.equal(page.clock.getBeatPosition().beat, 3);
    assert.equal(page.clock.getBeatPosition().preparing, true);
    assert.equal(page.oscillators.length, 0);
    page.advance(0.25);
    assert.equal(page.clock.getBeatPosition().progress, 0.5);
    page.clock.setTempo(60);
    assert.equal(page.clock.getBeatPosition().progress, 0.5);
    page.advance(0.7);
    assert.equal(page.oscillators[0].startTime, 0.75);
    assert.equal(page.beatCount, 0);
    page.advance(0.75);
    assert.equal(page.clock.getBeatPosition().preparing, false);
    assert.equal(page.clock.getBeatPosition().beat, 0);
    assert.equal(page.clock.getBeatPosition().progress, 0);
    assert.deepEqual(page.beatIndices, [0]);
    assert.equal(page.oscillators[0].frequency.value, 1400);
    page.clock.stop();
    assert.equal(page.clock.getBeatPosition().preparing, true);
    assert.equal(page.clock.getBeatPosition().progress, 0);
});

test('the conductor dot waits at the top and returns there when stopped', async () => {
    for (const count of [1, 2, 3, 4]) {
        const page = setupPage(JSON.stringify({ beats: count, type: 'conductor' }));
        const clock = vm.runInContext('clock', page.context);
        const { conductorPreparationPoint, conductorPreparationBeats, conductorPoint } = page.context.api;
        const dropDuration = conductorPreparationBeats(count) * 0.5;
        const position = () => animatedBallPoint(page);
        assertBallNear(position(), conductorPreparationPoint(count, 0));
        page.click();
        await new Promise(setImmediate);
        page.advance(dropDuration / 2);
        assertBallNear(position(), conductorPreparationPoint(count, 0.5));
        page.advance(dropDuration - 0.05);
        page.advance(dropDuration);
        assertBallNear(position(), conductorPoint(count, 0, 0));
        page.click();
        assert.equal(clock.running, false);
        assertBallNear(position(), conductorPreparationPoint(count, 0));
        page.click();
        await new Promise(setImmediate);
        assert.equal(clock.getBeatPosition().preparing, true);
        assertBallNear(position(), conductorPreparationPoint(count, 0));
    }
});

test('the conductor, pulse and counting number reach the beat when delayed audio reaches the speaker', async () => {
    const page = setupPage(JSON.stringify({ beats: 4, type: 'conductor' }));
    page.audio.outputLatency = 0.2;
    page.click();
    await new Promise(setImmediate);
    assert.equal(page.conductorAnimations.length, 0, 'Wait for the output device before beginning the drop');
    for (let tick = 1; tick <= 18; tick++) page.advance(tick * 0.025);
    const clock = vm.runInContext('clock', page.context);
    assert.ok(Math.abs(page.oscillators[0].startTime - 0.25) < 1e-8);
    assert.equal(clock.getBeatPosition().beat, 0);
    assertBallNear(animatedBallPoint(page), page.context.api.conductorPoint(4, 0, 0));
    const pulse = page.animations.filter(animation => animation.target === page.pulseLayer && !animation.canceled).at(-1);
    const number = page.animations.filter(animation => animation.target === page.number && !animation.canceled).at(-1);
    assert.equal(pulse.startTime, page.conductorAnimations.at(-1).startTime);
    assert.equal(number.startTime, pulse.startTime);
    page.click();
});


test('opening drops match the repeating stroke speed at slow, normal, and fast tempos', async () => {
    for (const count of [1, 2, 3, 4]) {
        for (const tempo of [40, 120, 240]) {
            const page = setup();
            const { conductorPreparationBeats, conductorPreparationPoint, conductorPoint } = page.context.api;
            const fraction = conductorPreparationBeats(count);
            const interval = 60 / tempo;
            page.clock.setBeats(count);
            page.clock.setTempo(tempo);
            await page.clock.start({ leadInBeats: fraction });
            for (let step = 0; step < 10; step++) {
                const elapsed = step / 10 * fraction * interval;
                page.advance(elapsed);
                const position = page.clock.getBeatPosition();
                const opening = conductorPreparationPoint(count, position.progress);
                const repeating = conductorPoint(count, count - 1, 1 - fraction + elapsed / interval);
                opening.forEach((value, axis) => assert.ok(Math.abs(value - repeating[axis]) < 0.000001));
            }
            page.advance(fraction * interval - 0.05);
            const first = page.oscillators[0];
            assert.ok(Math.abs(first.startTime - fraction * interval) < 0.000001);
        }
    }
});

test('changing tempo during a half-beat drop preserves its phase and then resumes full beats', async () => {
    const page = setup();
    await page.clock.start({ leadInBeats: 0.5 });
    page.advance(0.125);
    page.clock.setTempo(60);
    assert.equal(page.clock.getBeatPosition().progress, 0.5);
    page.advance(0.3);
    assert.equal(page.oscillators[0].startTime, 0.375);
    page.advance(0.375);
    assert.equal(page.clock.getBeatPosition().progress, 0);
    assert.equal(page.clock.getBeatPosition().preparing, false);
    page.advance(1.3);
    assert.equal(page.oscillators[1].startTime, 1.375);
});

test('the dot follows scheduled click times between scheduler ticks without pausing at the beat', async () => {
    const page = setup();
    page.clock.setBeats(4);
    await page.clock.start({ leadInBeats: 1 });
    page.advance(0.45);
    page.audio.currentTime = 0.51;
    const position = page.clock.getBeatPosition();
    assert.equal(position.beat, 0);
    assert.equal(position.preparing, false);
    assert.ok(Math.abs(position.progress - 0.02) < 0.000001);
    assert.equal(page.beatCount, 0, 'Reading the animation position does not fire beat callbacks.');
    page.advance(0.51);
    assert.equal(page.beatCount, 1);
    assert.equal(page.clock.getBeatPosition().progress, position.progress);
});

function conductorTravel(pointAt, from, to) {
    let previous = pointAt(from);
    let distance = 0;
    for (let sample = 1; sample <= 250; sample++) {
        const point = pointAt(from + (to - from) * sample / 250);
        distance += Math.hypot(point[0] - previous[0], point[1] - previous[1]);
        previous = point;
    }
    return distance;
}

test('flowing gestures share velocity through turns and soften sharp reversals', () => {
    const { conductorPoint } = setup().context.api;
    for (const count of [1, 2, 3, 4]) {
        const pointAt = phase => conductorPoint(count, Math.floor(phase), phase - Math.floor(phase));
        const dt = 0.00001;
        for (let time = 0; time <= count; time += 0.5) {
            const before = pointAt(time - dt);
            const at = pointAt(time);
            const after = pointAt(time + dt);
            const incoming = at.map((value, axis) => (value - before[axis]) / dt);
            const outgoing = after.map((value, axis) => (value - at[axis]) / dt);
            assert.ok(Math.hypot(...incoming.map((value, axis) => value - outgoing[axis])) < 2,
                `Pattern ${count} at ${time}: a turn must not snap to a different velocity.`);
            if (time === 0 || time === count - 0.5 || time === count) {
                assert.ok(Math.hypot(...incoming) < 0.2 && Math.hypot(...outgoing) < 0.2,
                    'The top and bottom reverse gently without a pause or a teleport.');
            }
        }
        for (let time = 0; time < count; time += 0.05) {
            assert.ok(conductorTravel(pointAt, time, time + 0.05) > 1,
                'The ball must keep making progress instead of dwelling at a turn.');
        }
    }
});

test('four-beat gestures reach the side loops on the clicks and flow through their rounded turns', () => {
    const { conductorPoint, CONDUCTING_PATTERNS } = setup().context.api;
    const pattern = CONDUCTING_PATTERNS[4];
    assertBallNear(conductorPoint(4, 1, 0), pattern[1].point);
    assertBallNear(conductorPoint(4, 2, 0), pattern[2].point);
    assertBallNear(conductorPoint(4, 3, 0.5), pattern[3].point);
    const pointAt = phase => conductorPoint(4, Math.floor(phase), phase % 1);
    for (const turn of [0.5, 1, 1.5, 2, 2.5, 3]) {
        const atTurn = conductorTravel(pointAt, turn - 0.01, turn + 0.01) / 0.02;
        const afterTurn = conductorTravel(pointAt, turn + 0.15, turn + 0.25) / 0.1;
        assert.ok(atTurn > 100, 'Rounded corners keep momentum rather than stopping.');
        assert.ok(atTurn < afterTurn, 'The ball eases through the corner before gently picking up speed.');
    }
});

test('opening drops use the same gentle motion as the repeating downstroke and reach beat one exactly', () => {
    const { conductorPoint, conductorPreparationPoint, conductorPreparationBeats, CONDUCTING_PATTERNS } = setup().context.api;
    for (const count of [1, 2, 3, 4]) {
        const pointAt = progress => conductorPreparationPoint(count, progress);
        assert.ok(Math.abs(pointAt(0)[1] - 36) < 0.000001);
        assert.deepEqual(Array.from(pointAt(1)), Array.from(CONDUCTING_PATTERNS[count][0].point));
        const duration = conductorPreparationBeats(count);
        assert.equal(duration, 0.5);
        const start = conductorTravel(pointAt, 0, 0.1);
        const middle = conductorTravel(pointAt, 0.45, 0.55);
        const end = conductorTravel(pointAt, 0.9, 1);
        assert.ok(start < middle / 2 && end < middle / 2,
            'The drop accelerates out of the top and eases into the bottom reversal.');
        for (const progress of [0.1, 0.5, 0.9]) {
            const running = conductorPoint(count, count - 1, 1 - duration + duration * progress);
            pointAt(progress).forEach((value, axis) => assert.ok(Math.abs(value - running[axis]) < 0.000001));
        }
    }
});

test('browser motion follows every pattern without per-frame JavaScript or beat restarts', async () => {
    for (const count of [1, 2, 3, 4]) {
        const page = setupPage(JSON.stringify({ beats: count, type: 'conductor' }));
        const { conductorPoint, conductorPreparationBeats } = page.context.api;
        page.click();
        await new Promise(setImmediate);
        const animation = page.conductorAnimations[0];
        const drop = conductorPreparationBeats(count) * 0.5;
        assert.equal(animation.options.iterations, Infinity);
        assert.equal(animation.options.easing, 'linear');
        // The browser timeline advances even when no JS frame or scheduler runs.
        for (let frame = 0; frame <= 240; frame++) {
            const phase = count * frame / 240;
            page.audio.currentTime = drop + phase * 0.5;
            assertBallNear(animatedBallPoint(page), conductorPoint(count, Math.floor(phase), phase % 1));
        }

        page.click();
        page.click();
        await new Promise(setImmediate);
        const restarted = page.conductorAnimations.at(-1);
        const start = page.audio.currentTime;
        for (let tick = 1; tick <= 200; tick++) page.advance(start + tick * 0.025);
        assert.equal(page.conductorAnimations.length, 2, 'Ordinary clicks keep the same looping animation.');
        assert.equal(restarted.seeks, 1, 'Audio scheduler rounding must not seek on each click.');
    }
});

test('tempo changes retime the opening drop and repeating animation without moving the ball', async () => {
    const page = setupPage(JSON.stringify({ beats: 4, type: 'conductor' }));
    const clock = vm.runInContext('clock', page.context);
    const { conductorPoint, conductorPreparationPoint } = page.context.api;
    page.click();
    await new Promise(setImmediate);
    for (const [time, tempo] of [[0.1, 60], [1.1, 240], [1.3, 40], [2.5, 180]]) {
        while (page.audio.currentTime + 0.025 < time) page.advance(page.audio.currentTime + 0.025);
        page.advance(time);
        const before = animatedBallPoint(page);
        vm.runInContext(`setTempo(${tempo})`, page.context);
        assertBallNear(animatedBallPoint(page), before);
        assert.equal(page.conductorAnimations.length, 1);
        assert.equal(page.conductorAnimations[0].options.duration, 4 * 60000 / tempo);
        page.audio.currentTime += 0.04;
        const position = clock.getBeatPosition();
        const expected = position.preparing ? conductorPreparationPoint(4, position.progress)
            : conductorPoint(4, position.beat, position.progress);
        assertBallNear(animatedBallPoint(page), expected);
    }
});

test('hidden pages, settings, reduced motion, and classic mode cancel continuous motion and resume in phase', async () => {
    const page = setupPage(JSON.stringify({ beats: 3, type: 'conductor' }));
    const clock = vm.runInContext('clock', page.context);
    page.click();
    await new Promise(setImmediate);
    const current = () => page.conductorAnimations.at(-1);
    page.context.document.hidden = true;
    page.documentListeners.visibilitychange();
    assert.equal(current().canceled, true);
    for (let tick = 1; tick <= 80; tick++) page.advance(tick * 0.025);
    page.context.document.hidden = false;
    page.documentListeners.visibilitychange();
    assert.equal(current().canceled, false);
    const position = clock.getBeatPosition();
    assertBallNear(animatedBallPoint(page), page.context.api.conductorPoint(3, position.beat, position.progress));
    page.settingsButton.listeners.click();
    assert.equal(current().canceled, true);
    page.tick(400);
    page.closeButton.listeners.click();
    page.tick(300);
    assert.equal(current().canceled, false);
    page.motionPreference.matches = true;
    page.motionPreference.onchange();
    assert.equal(current().canceled, true);
    page.motionPreference.matches = false;
    page.motionPreference.onchange();
    assert.equal(current().canceled, false);
    page.typeInputs[0].checked = true;
    page.typeInputs[0].listeners.change();
    assert.equal(current().canceled, true);
});

test('a stalled audio scheduler re-aligns the continuous motion with the recovered clicks', async () => {
    const page = setupPage(JSON.stringify({ beats: 4, type: 'conductor' }));
    const clock = vm.runInContext('clock', page.context);
    page.click();
    await new Promise(setImmediate);
    page.advance(10);
    page.advance(10.025);
    const position = clock.getBeatPosition();
    assertBallNear(animatedBallPoint(page), page.context.api.conductorPoint(4, position.beat, position.progress));
    assert.equal(page.conductorAnimations.length, 1);
});

test('downbeat darkening animates only the background layer opacity for every beat group', async () => {
    for (const count of [1, 2, 3, 4]) {
        const page = setupPage(JSON.stringify({ beats: count }));
        page.click();
        await new Promise(setImmediate);
        for (let tick = 1; tick <= count * 20; tick++) page.advance(tick * 0.025);
        const pulses = page.animations.filter(animation => animation.target === page.pulseLayer);
        assert.equal(pulses.length, 1);
        assert.deepEqual(JSON.parse(JSON.stringify(pulses[0].keyframes)), [{ opacity: 1 }, { opacity: 0 }]);
        assert.equal(pulses[0].options.duration, 500);
        assert.equal(page.animations.some(animation => animation.target === page.surface), false);
    }
});

function visualPhase(animation) {
    const elapsed = animation.currentTime - (animation.options.delay || 0);
    return elapsed < 0 ? null : (elapsed % animation.options.duration) / animation.options.duration;
}

test('the conductor downbeat flash and number pulse reach the bottom on the same frame without waiting for a timer callback', async () => {
    for (const count of [1, 2, 3, 4]) {
        const page = setupPage(JSON.stringify({ beats: count, type: 'conductor' }));
        const clock = vm.runInContext('clock', page.context);
        page.click();
        await new Promise(setImmediate);
        const ball = page.conductorAnimations[0];
        const background = page.animations.find(animation => animation.target === page.pulseLayer);
        const number = page.animations.find(animation => animation.target === page.number);
        assert.equal(background.startTime, ball.startTime);
        assert.equal(number.startTime, ball.startTime);
        page.advance(0.2); // Audio is scheduled, but its visual callback has not run.
        page.audio.currentTime = 0.249;
        assert.equal(visualPhase(background), null, 'The opening drop must not flash early.');
        page.audio.currentTime = 0.25;
        assert.equal(clock.lastBeatIndex, null, 'The scheduler has not delivered the beat callback yet.');
        assertBallNear(animatedBallPoint(page), [200, 540]);
        assert.equal(visualPhase(background), 0);
        assert.equal(visualPhase(number), 0);

        // Check another bar with a callback deliberately delayed by 20ms.
        const downbeat = 0.25 + count * 0.5;
        for (let time = 0.275; time < downbeat - 0.02; time += 0.025) page.advance(time);
        page.audio.currentTime = downbeat;
        assertBallNear(animatedBallPoint(page), [200, 540]);
        assert.ok(visualPhase(background) < 0.000001);
        assert.ok(visualPhase(number) < 0.000001);
        assert.equal(page.animations.length, 3 + count, 'Beats reuse continuous effects, including one per count digit.');
    }
});

test('conductor flashes only on beat one while the number pulses on every beat', async () => {
    const page = setupPage(JSON.stringify({ beats: 4, type: 'conductor' }));
    page.click();
    await new Promise(setImmediate);
    const background = page.animations.find(animation => animation.target === page.pulseLayer);
    const number = page.animations.find(animation => animation.target === page.number);
    for (let beat = 1; beat <= 3; beat++) {
        page.audio.currentTime = 0.25 + beat * 0.5;
        assert.equal(visualPhase(background), beat / 4);
        assert.equal(visualPhase(number), 0);
        const restingFrames = background.keyframes.filter(frame => frame.offset >= 1 / 4);
        assert.ok(restingFrames.every(frame => frame.opacity === 0));
    }
});

function countOpacity(animation) {
    const phase = visualPhase(animation);
    if (phase === null) return 0;
    const before = animation.keyframes.filter(frame => frame.offset <= phase).at(-1);
    const after = animation.keyframes.find(frame => frame.offset > phase);
    return before.opacity + (after.opacity - before.opacity) * (phase - before.offset) / (after.offset - before.offset);
}

test('conductor counts wrap at the selected beats and fade over one beat at slow and fast tempos', async () => {
    for (const count of [1, 2, 3, 4]) {
        for (const tempo of [20, 120, 300]) {
            const page = setupPage(JSON.stringify({ beats: count, type: 'conductor' }), { savedTempo: String(tempo) });
            page.click();
            await new Promise(setImmediate);
            const counters = page.animations.filter(animation => page.beatNumbers.includes(animation.target));
            assert.equal(counters.length, count);
            const interval = 60 / tempo;
            const firstBeat = interval / 2;
            page.audio.currentTime = firstBeat - 0.001;
            assert.ok(counters.every(animation => countOpacity(animation) === 0), 'The silent opening drop has no count.');
            for (let beat = 0; beat <= count; beat++) {
                let previousOpacity = 0.12;
                for (const progress of [0.0001, 0.25, 0.75, 0.9999]) {
                    page.audio.currentTime = firstBeat + (beat + progress) * interval;
                    const visible = counters.filter(animation => countOpacity(animation) > 0);
                    assert.equal(visible.length, 1, 'Only the current count is visible.');
                    assert.equal(visible[0].target.textContent, String(beat % count + 1));
                    const opacity = countOpacity(visible[0]);
                    assert.ok(opacity < previousOpacity, 'The current digit fades throughout its beat.');
                    previousOpacity = opacity;
                }
            }
        }
    }
});

test('tempo edits, recovered stalls, and reopened settings keep all conductor effects on one timeline', async () => {
    const page = setupPage(JSON.stringify({ beats: 4, type: 'conductor' }));
    page.click();
    await new Promise(setImmediate);
    const live = target => page.animations.filter(animation => animation.target === target && !animation.canceled).at(-1);
    const check = () => {
        const ball = page.conductorAnimations.at(-1);
        const background = live(page.pulseLayer);
        const number = live(page.number);
        assert.equal(background.startTime, ball.startTime);
        assert.equal(number.startTime, ball.startTime);
        assert.equal(background.options.duration, ball.options.duration);
        assert.equal(number.options.duration, ball.options.duration / 4);
        assert.ok(Math.abs(number.keyframes[1].offset * number.options.duration - 180) < 0.000001);
        for (const digit of page.beatNumbers) {
            const count = live(digit);
            assert.equal(count.startTime, ball.startTime);
            assert.equal(count.options.duration, ball.options.duration);
            assert.equal(count.options.delay, background.options.delay);
        }
    };
    page.advance(0.1);
    vm.runInContext('setTempo(80)', page.context);
    check();
    page.advance(0.5);
    vm.runInContext('setTempo(240)', page.context);
    check();
    page.advance(10);
    page.advance(10.025);
    check();
    page.settingsButton.listeners.click();
    assert.equal(live(page.pulseLayer), undefined);
    assert.equal(live(page.number), undefined);
    assert.ok(page.beatNumbers.every(digit => !live(digit)));
    page.tick(400);
    page.closeButton.listeners.click();
    page.tick(300);
    check();
    page.motionPreference.matches = true;
    page.motionPreference.onchange();
    assert.equal(live(page.pulseLayer), undefined);
    assert.equal(live(page.number), undefined);
    assert.ok(page.beatNumbers.every(digit => !live(digit)));
    page.motionPreference.matches = false;
    page.motionPreference.onchange();
    check();
    page.click();
    assert.equal(live(page.pulseLayer), undefined);
    assert.equal(live(page.number), undefined);
    assert.ok(page.beatNumbers.every(digit => !live(digit)));
});

test('public counting and temporary effects share the beat clock without private-library interference', async () => {
    const { clock, oscillators } = setup();
    const publicVoice = [1, 2, 3, 4].map(() => voiceBuffer(0.15));
    const effects = [1, 2, 3, 4].map(() => voiceBuffer(0.14, 260));
    clock.setSpokenBuffers(publicVoice);
    clock.setSound('spoken'); clock.setBeats(4);
    await clock.start();
    const prepared = clock.preparedVoices;
    clock.setVoiceBuffers([1, 2, 3, 4].map(() => voiceBuffer(0.3))); // An unrelated private library may finish loading.
    assert.equal(clock.sound, 'spoken');
    assert.equal(clock.preparedVoices, prepared);
    clock.playClick(0.52, 1);
    assert.equal(oscillators.at(-1).buffer, publicVoice[1]);
    clock.setVoiceEffectBuffers(effects);
    assert.equal(clock.running, true);
    assert.equal(clock.voiceSamples(), effects);
    clock.setTempo(300);
    assert.equal(clock.voiceSamples(), effects);
    clock.setBeats(3);
    assert.equal(clock.voiceSamples(), effects);
    clock.stop();
    await clock.start();
    assert.equal(oscillators.at(-1).buffer, effects[0]);
    clock.setSound('click');
    assert.equal(clock.effectBuffers, null);
    clock.stop();
});
