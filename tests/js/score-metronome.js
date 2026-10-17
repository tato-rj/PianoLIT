const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const element = (extra = {}) => Object.assign({events: {}, attributes: {}, hidden: false,
        addEventListener(name, callback) { this.events[name] = callback; },
        setAttribute(name, value) { this.attributes[name] = value; },
        focus() { this.focused = true; }}, extra);
    const button = element(), panel = element({hidden: true}), slider = element({value: '60'});
    const value = element(), close = element(), error = element({hidden: true});
    const controls = {'.score-metronome-button': button, '.score-metronome-panel': panel,
        '[data-metronome-tempo]': slider, '[data-metronome-value]': value,
        '[data-metronome-close]': close, '[data-metronome-error]': error};
    let visible = true, pendingResume = false, resolveResume, rejectResume;
    const container = {isConnected: true, querySelector: selector => controls[selector],
        getClientRects: () => visible ? [{}] : []};
    const document = element({hidden: false, querySelectorAll: () => [container]});
    const timers = new Map(), contexts = [];
    let timerId = 0;
    class AudioContext {
        constructor() {
            this.state = pendingResume ? 'suspended' : 'running';
            this.currentTime = 0; this.oscillators = []; contexts.push(this);
        }
        resume() { return new Promise((resolve, reject) => { resolveResume = resolve; rejectResume = reject; }); }
        close() { this.state = 'closed'; return Promise.resolve(); }
        createOscillator() {
            const oscillator = {frequency: {setValueAtTime() {}}, connect() {},
                disconnect() { this.disconnected = true; }, start(time) { this.time = time; },
                stop(time) { if (time === undefined) this.cancelled = true; }};
            this.oscillators.push(oscillator); return oscillator;
        }
        createGain() { return {connect() {}, disconnect() {}, gain: {
            setValueAtTime() {}, linearRampToValueAtTime() {}, exponentialRampToValueAtTime() {}}}; }
    }
    const window = element({AudioContext, setInterval(callback) { timers.set(++timerId, callback); return timerId; },
        clearInterval(id) { timers.delete(id); }});
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/components/score-metronome.js'), 'utf8'), {window, document});
    assert(panel.hidden && !contexts.length, 'Loading a score must not start audio');
    await button.events.click();
    const context = contexts[0];
    assert.strictEqual(panel.hidden, false);
    assert.strictEqual(button.attributes['aria-expanded'], 'true');
    assert.strictEqual(timers.size, 1);
    assert.strictEqual(context.oscillators.length, 1, 'Opening starts the first beat');
    assert.strictEqual(context.oscillators[0].time, 0.01);
    context.currentTime = 0.95; [...timers.values()][0]();
    assert.strictEqual(context.oscillators[1].time, 1.01, 'Default 60 BPM schedules one beat per second');
    context.currentTime = 1.1;
    slider.value = '120'; slider.events.input();
    assert.strictEqual(value.textContent, '120');
    assert.strictEqual(slider.attributes['aria-valuetext'], '120 beats per minute');
    context.currentTime = 1.45; [...timers.values()][0]();
    assert.strictEqual(context.oscillators[2].time, 1.51, 'Live speed changes reschedule the next beat');
    context.currentTime = 1.46;
    slider.value = '20'; slider.events.input();
    assert(context.oscillators[2].cancelled, 'An already queued beat is cancelled on a tempo change');
    context.currentTime = 3.95; [...timers.values()][0]();
    assert.strictEqual(context.oscillators[3].time, 4.01, '20 BPM uses a three-second interval');
    slider.value = '200'; slider.events.input();
    context.currentTime = 4.2; [...timers.values()][0]();
    assert(Math.abs(context.oscillators.at(-1).time - 4.26) < 0.00001, '200 BPM uses a 0.3-second interval');
    close.events.click();
    assert(panel.hidden && button.focused);
    assert.strictEqual(button.attributes['aria-expanded'], 'false');
    assert.strictEqual(context.state, 'closed');
    assert.strictEqual(timers.size, 0);
    assert(context.oscillators.every(node => node.cancelled && node.disconnected), 'Closing removes sounding and queued beats');
    await button.events.click();
    assert.strictEqual(slider.value, '200', 'Reopening keeps the selected tempo');
    await button.events.click();
    assert(panel.hidden && contexts.at(-1).state === 'closed', 'The same button also stops playback');

    // A delayed browser audio unlock must never restart playback after dismissal.
    pendingResume = true;
    const opening = button.events.click(), staleContext = contexts.at(-1), staleResolve = resolveResume;
    await button.events.click();
    const reopening = button.events.click(), currentContext = contexts.at(-1), currentResolve = resolveResume;
    staleResolve(); await opening;
    assert.strictEqual(staleContext.oscillators.length, 0);
    assert.strictEqual(timers.size, 0);
    assert(!panel.hidden, 'Stale resume does not close a newly opened panel');
    currentResolve(); await reopening;
    assert.strictEqual(currentContext.oscillators.length, 1);
    let prevented = false;
    panel.events.keydown({key: 'Escape', preventDefault() { prevented = true; }, stopPropagation() {}});
    assert(prevented && panel.hidden && timers.size === 0);
    const denied = button.events.click(); rejectResume(new Error('Audio unlock failed')); await denied;
    assert(panel.hidden && !error.hidden && timers.size === 0, 'Audio errors restore closed state and offer retry');
    pendingResume = false;
    for (const reason of ['score-tab', 'background', 'navigation', 'removed']) {
        await button.events.click();
        if (reason === 'score-tab') { visible = false; document.events['hidden.bs.tab'](); visible = true; }
        if (reason === 'background') { document.hidden = true; document.events.visibilitychange(); document.hidden = false; }
        if (reason === 'navigation') window.events.pagehide();
        if (reason === 'removed') { container.isConnected = false; [...timers.values()][0](); container.isConnected = true; }
        assert(panel.hidden && contexts.at(-1).state === 'closed' && timers.size === 0, reason + ' stops playback');
    }
    delete window.AudioContext;
    await button.events.click();
    assert(panel.hidden && !error.hidden && timers.size === 0, 'Unsupported browsers do not leave a playing state');
};
