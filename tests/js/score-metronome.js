const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = async function () {
    const element = (extra = {}) => Object.assign({events: {}, attributes: {}, hidden: false,
        addEventListener(name, callback) { this.events[name] = callback; },
        setAttribute(name, value) { this.attributes[name] = value; },
        classList: {toggle() {}}, focus() { this.focused = true; }, getAttribute(name) { return this.attributes[name]; }}, extra);
    const button = element(), panel = element({hidden: true}), slider = element({value: '60'});
    const value = element(), close = element(), error = element({hidden: true});
    const pauseIcon = element(), playIcon = element({hidden: true});
    const pause = element({querySelector: selector => selector === '[data-metronome-pause-icon]' ? pauseIcon : playIcon});
    const sound = element({value: 'woodblock'}), status = element();
    const saveStatus = element({hidden: true}), saveRetry = element({hidden: true});
    const beats = [1,2,3,4].map(count => element({attributes: {'data-metronome-beats': String(count)}}));
    const settings = element({querySelector: selector => ({'[data-metronome-sound]': sound,
        '[data-metronome-settings-status]': status, '[data-metronome-save-status]': saveStatus,
        '[data-metronome-save-retry]': saveRetry})[selector], querySelectorAll: () => beats});
    const settingsButton = element({attributes: {'aria-controls': 'score-metronome-settings'}});
    for (let number=1; number<=4; number++) {
        sound.attributes['data-count-url-'+number] = '/count-'+number+'.wav';
        sound.attributes['data-female-count-url-'+number] = '/female-count-'+number+'.wav';
    }
    const controls = {'.score-metronome-button': button, '.score-metronome-panel': panel,
        '[data-metronome-tempo]': slider, '[data-metronome-value]': value,
        '[data-metronome-settings]': settingsButton, '[data-metronome-pause]': pause, '[data-metronome-close]': close, '[data-metronome-error]': error};
    let visible = true, pendingResume = false, resolveResume, rejectResume;
    const attributes = {};
    const container = {isConnected: true, querySelector: selector => controls[selector],
        getAttribute: name => attributes[name], getClientRects: () => visible ? [{}] : []};
    const document = element({hidden: false, querySelectorAll: () => [], getElementById: () => settings});
    const timers = new Map(), timeouts = new Map(), contexts = [];
    let timerId = 0;
    class AudioContext {
        constructor() {
            this.state = pendingResume ? 'suspended' : 'running';
            this.currentTime = 0; this.sources = []; this.oscillators = []; this.sampleRate=24000; contexts.push(this);
        }
        resume() { return new Promise((resolve, reject) => { resolveResume = () => { if (this.state !== 'closed') this.state = 'running'; resolve(); }; rejectResume = reject; }); }
        close() { this.state = 'closed'; return Promise.resolve(); }
        createOscillator() {
            const oscillator = {frequency: {setValueAtTime(value) { this.value=value; }}, connect() {},
                disconnect() { this.disconnected = true; }, start(time) { this.time = time; },
                stop(time) { if (time === undefined) this.cancelled = true; }};
            this.oscillators.push(oscillator); return oscillator;
        }
        createBuffer(channels, length, sampleRate) { const data = new Float32Array(length); return {duration:length/sampleRate, getChannelData: () => data}; }
        createBufferSource() { const source = this.createOscillator(); this.oscillators.pop(); source.playbackRate={setValueAtTime(value){this.value=value;}}; this.sources.push(source); return source; }
        decodeAudioData(data) { const bytes = new Uint8Array(data); return Promise.resolve({word:bytes[0],voice:bytes[1],duration:0.33}); }
        createGain() { return {connect() {}, disconnect() {}, gain: {
            setValueAtTime() {}, linearRampToValueAtTime() {}, cancelScheduledValues() {}, setTargetAtTime() {}}}; }
    }
    const window = element({AudioContext, setInterval(callback) { timers.set(++timerId, callback); return timerId; },
        clearInterval(id) { timers.delete(id); },
        setTimeout(callback) { timeouts.set(++timerId, callback); return timerId; },
        clearTimeout(id) { timeouts.delete(id); }, app: {csrfToken: 'test-csrf'}});
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/components/score-metronome.js'), 'utf8'), {window, document});
    const metronome = new window.ScoreMetronome(container);
    assert(panel.hidden && !contexts.length, 'Loading a score must not start audio');
    await button.events.click();
    const context = contexts[0];
    assert.strictEqual(panel.hidden, false);
    assert.strictEqual(button.attributes['aria-expanded'], 'true');
    assert.strictEqual(timers.size, 1);
    assert.strictEqual(context.sources.length, 1, 'Opening starts the first beat');
    assert.strictEqual(context.sources[0].time, 0.01);
    context.currentTime = 0.95; [...timers.values()][0]();
    assert.strictEqual(context.sources[1].time, 1.01, 'Default 60 BPM schedules one beat per second');
    context.currentTime = 1.1;
    slider.value = '120'; slider.events.input();
    assert.strictEqual(value.textContent, '120');
    assert.strictEqual(slider.attributes['aria-valuetext'], '120 beats per minute');
    context.currentTime = 1.45; [...timers.values()][0]();
    assert.strictEqual(context.sources[2].time, 1.51, 'Live speed changes reschedule the next beat');
    context.currentTime = 1.46;
    slider.value = '20'; slider.events.input();
    assert(context.sources[2].cancelled, 'An already queued beat is cancelled on a tempo change');
    context.currentTime = 3.95; [...timers.values()][0]();
    assert.strictEqual(context.sources[3].time, 4.01, '20 BPM uses a three-second interval');
    slider.value = '200'; slider.events.input();
    context.currentTime = 4.2; [...timers.values()][0]();
    assert(Math.abs(context.sources.at(-1).time - 4.26) < 0.00001, '200 BPM uses a 0.3-second interval');
    close.events.click();
    assert(panel.hidden && button.focused);
    assert.strictEqual(button.attributes['aria-expanded'], 'false');
    assert.strictEqual(context.state, 'closed');
    assert.strictEqual(timers.size, 0);
    assert(context.sources.every(node => node.cancelled && node.disconnected), 'Closing removes sounding and queued beats');
    await button.events.click();
    assert.strictEqual(slider.value, '200', 'Reopening keeps the selected tempo');
    await button.events.click();
    assert(panel.hidden && contexts.at(-1).state === 'closed', 'The same button also stops playback');

    metronome.setTempo(60); metronome.setBeatCount(3);
    await button.events.click();
    const pausedAudio = contexts.at(-1);
    pausedAudio.currentTime = 0.95; [...timers.values()][0]();
    pause.events.click();
    assert(!panel.hidden && pausedAudio.state === 'running', 'Pause keeps the panel and its settings open');
    assert.strictEqual(pause.attributes['aria-label'], 'Resume metronome');
    assert.strictEqual(pause.attributes['aria-pressed'], 'true');
    assert(pauseIcon.hidden && !playIcon.hidden);
    assert(pausedAudio.sources.every(source => source.cancelled && source.disconnected), 'Pause immediately stops sounding and queued counts');
    const pausedCount = pausedAudio.sources.length;
    pausedAudio.currentTime = 50; [...timers.values()][0]();
    assert.strictEqual(pausedAudio.sources.length, pausedCount, 'Paused time does not schedule or advance counts');
    pause.events.click();
    assert.strictEqual(pausedAudio.sources.length, pausedCount + 1, 'Resume schedules one beat without catching up');
    assert.strictEqual(pausedAudio.sources.at(-1).playbackRate.value, 0.75, 'The cancelled second beat resumes without skipping a number');
    assert.strictEqual(pause.attributes['aria-label'], 'Pause metronome');
    assert(!pauseIcon.hidden && playIcon.hidden);
    pause.events.click();
    metronome.setTempo(200); metronome.setBeatCount(4); await metronome.setSound('claves');
    const beforeSettings = pausedAudio.sources.length;
    settings.events['hide.bs.modal']();
    assert(metronome.paused, 'Closing settings preserves an explicit count pause');
    assert.strictEqual(pausedAudio.sources.length, beforeSettings, 'Settings, tempo and sound changes do not unpause the count');
    pause.events.click();
    assert.strictEqual(pausedAudio.sources.at(-1).playbackRate.value, 1, 'Changing the group while paused resumes at beat one');
    pause.events.click(); await button.events.click();
    assert(!metronome.paused && !pauseIcon.hidden && playIcon.hidden, 'Closing resets the pause control for the next opening');
    await metronome.setSound('woodblock');

    // A delayed browser audio unlock must never restart playback after dismissal.
    pendingResume = true;
    const opening = button.events.click(), staleContext = contexts.at(-1), staleResolve = resolveResume;
    await button.events.click();
    const reopening = button.events.click(), currentContext = contexts.at(-1), currentResolve = resolveResume;
    staleResolve(); await opening;
    assert.strictEqual(staleContext.sources.length, 0);
    assert.strictEqual(timers.size, 0);
    assert(!panel.hidden, 'Stale resume does not close a newly opened panel');
    currentResolve(); await reopening;
    assert.strictEqual(currentContext.sources.length, 1);
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
    // Each grouping restarts on a higher first beat, then repeats lower beats.
    for (const kind of ['woodblock','click','claves','hi-hat']) {
        for (let count=1; count<=4; count++) {
            metronome.setTempo(60); metronome.setBeatCount(count); await metronome.setSound(kind);
            await button.events.click();
            const audio=contexts.at(-1);
            for(let beat=1; beat<8; beat++) { audio.currentTime=beat-0.05; [...timers.values()][0](); }
            assert.strictEqual(audio.sources.length,8);
            assert.deepStrictEqual(audio.sources.map(source=>source.playbackRate.value),Array.from({length:8},(_,beat)=>beat%count===0?1:0.75),kind+' grouping '+count);
            assert.strictEqual(beats.filter(button=>button.attributes['aria-pressed']==='true').length,1);
            assert.strictEqual(beats[count-1].attributes['aria-pressed'],'true');
            await button.events.click();
        }
    }
    const fetched=[];
    window.fetch=async url=>({ok:true,arrayBuffer:async()=>{fetched.push(url);return new Uint8Array([Number(url.match(/count-(\d)/)[1]),url.startsWith('/female-')?2:1]).buffer;}});
    for(let count=1;count<=4;count++) {
        metronome.setBeatCount(count); metronome.setTempo(200); await button.events.click();
        await metronome.setSound('voice');
        const audio=contexts.at(-1),first=audio.sources.length;
        for(let beat=1;beat<=8;beat++){audio.currentTime=beat*0.3-0.05; [...timers.values()][0]();}
        const spoken=audio.sources.slice(first);
        assert.deepStrictEqual(spoken.map(source=>source.buffer.word),Array.from({length:8},(_,beat)=>(beat+1)%count+1),'Human counting follows the selected group');
        assert(spoken.every(source=>source.buffer.duration/source.playbackRate.value<=0.281),'Spoken words fit within a beat at 200 BPM');
        await button.events.click();
    }
    assert.strictEqual(fetched.length,4,'Recorded words load once and are reused across audio contexts');
    const maleBuffers = metronome.voiceBuffers.voice;
    for(let count=1;count<=4;count++) {
        metronome.setBeatCount(count); await button.events.click();
        await metronome.setSound('female-voice');
        const audio=contexts.at(-1),first=audio.sources.length;
        for(let beat=1;beat<=8;beat++){audio.currentTime=beat*0.3-0.05; [...timers.values()][0]();}
        const spoken=audio.sources.slice(first);
        assert.deepStrictEqual(spoken.map(source=>source.buffer.word),Array.from({length:8},(_,beat)=>(beat+1)%count+1),'Female counting repeats each selected group');
        assert(spoken.every(source=>source.buffer.voice===2),'Female selection uses its own recorded words');
        assert(spoken.every(source=>source.buffer.duration/source.playbackRate.value<=0.281),'Female words remain separate at 200 BPM');
        await button.events.click();
    }
    assert.strictEqual(fetched.length,8,'Each voice loads its own four clips only once');
    assert.strictEqual(metronome.voiceBuffers.voice,maleBuffers,'Adding a female voice preserves the existing male buffers');
    assert.notStrictEqual(metronome.voiceBuffers['female-voice'],maleBuffers);
    await metronome.setSound('voice');
    assert.strictEqual(fetched.length,8,'Switching back reuses the original male recording');
    // Failed and stale recordings cannot replace the current sound or resurrect audio.
    metronome.voiceBuffers={};metronome.voiceBytes={};await metronome.setSound('click');
    await button.events.click(); window.fetch=async()=>({ok:false});
    await metronome.setSound('voice');
    assert.strictEqual(metronome.sound,'click');assert(!sound.disabled && !status.hidden && timers.size===1,'Failed voice loading keeps the current sound playing');
    metronome.voiceBytes={};
    let unlock;const deferred=new Promise(resolve=>{unlock=resolve;});window.fetch=()=>deferred;
    const staleLoad=metronome.setSound('voice');assert(sound.disabled);await button.events.click();
    unlock({ok:true,arrayBuffer:async()=>new ArrayBuffer(1)});await staleLoad;
    assert.strictEqual(metronome.sound,'click');assert(!sound.disabled && panel.hidden && timers.size===0);
    metronome.voiceBytes={};
    let releaseFemale;
    const femaleRequest=new Promise(resolve=>{releaseFemale=resolve;});
    window.fetch=url=>url.startsWith('/female-')?femaleRequest:Promise.resolve({ok:true,arrayBuffer:async()=>new Uint8Array([Number(url.match(/count-(\d)/)[1]),1]).buffer});
    await button.events.click();const pendingFemale=metronome.setSound('female-voice');await button.events.click();
    await button.events.click();await metronome.setSound('voice');
    releaseFemale({ok:true,arrayBuffer:async()=>new Uint8Array([1,2]).buffer});await pendingFemale;
    assert.strictEqual(metronome.sound,'voice','A stale female load cannot replace a newer male selection');
    assert(!panel.hidden && !sound.disabled && timers.size===1);
    await button.events.click();
    assert.strictEqual(timeouts.size, 0, 'Guests never queue persistence requests');

    // Signed-in settings restore without starting audio or re-saving the initial state.
    attributes['data-metronome-preferences-url'] = '/metronome/preferences';
    attributes['data-metronome-preferences'] = JSON.stringify({tempo: 174, beats: 4, sound: 'female-voice'});
    const requests = [], audioFetches = [];
    let saveResponse = async () => ({ok: true, json: async () => ({preferences: {tempo: 20}})});
    window.fetch = async (url, options) => {
        if (options && options.method === 'PATCH') { requests.push({url, ...options}); return saveResponse(); }
        audioFetches.push(url);
        return {ok: true, arrayBuffer: async () => new Uint8Array([Number(url.match(/count-(\d)/)[1]), 2]).buffer};
    };
    const beforeRestore = contexts.length;
    const signedIn = new window.ScoreMetronome(container);
    assert.strictEqual(slider.value, '174');
    assert.strictEqual(value.textContent, '174');
    assert.strictEqual(beats[3].attributes['aria-pressed'], 'true');
    assert.strictEqual(sound.value, 'female-voice');
    assert.strictEqual(contexts.length, beforeRestore);
    assert.strictEqual(audioFetches.length, 0, 'Restoring a voice does not fetch/play audio before opening');
    assert.strictEqual(timeouts.size, 0, 'Restoring does not save defaults over account choices');
    await button.events.click();
    assert.strictEqual(audioFetches.length, 4);
    assert.strictEqual(contexts.at(-1).sources[0].buffer.voice, 2, 'Opening plays the restored voice');
    assert.strictEqual(requests.length, 0);
    signedIn.setTempo(190); signedIn.setBeatCount(2); await signedIn.setSound('claves');
    assert.strictEqual(timeouts.size, 1, 'Rapid edits share one debounce');
    assert.strictEqual(requests.length, 0);
    await [...timeouts.values()][0]();
    assert.strictEqual(requests.length, 1);
    assert.deepStrictEqual(JSON.parse(requests[0].body), {preferences: {tempo: 190, beats: 2, sound: 'claves'}});
    assert.strictEqual(requests[0].credentials, 'same-origin');
    assert.strictEqual(requests[0].headers['X-CSRF-TOKEN'], 'test-csrf');
    assert(requests[0].keepalive);
    assert.strictEqual(slider.value, '190', 'Server acknowledgement does not replace newer control values');

    let releaseSave;
    saveResponse = () => new Promise(resolve => { releaseSave = resolve; });
    signedIn.setTempo(130);
    const saving = signedIn.flushPreferences(); await Promise.resolve();
    signedIn.setTempo(150); signedIn.setBeatCount(3);
    signedIn.flushPreferences();
    assert.strictEqual(requests.length, 2, 'A second write waits for the active request');
    saveResponse = async () => ({ok: true, json: async () => ({preferences: {tempo: 130, beats: 2}})});
    releaseSave(await saveResponse()); await saving;
    assert.strictEqual(requests.length, 3);
    assert.deepStrictEqual(JSON.parse(requests[2].body), {preferences: {tempo: 150, beats: 3}});
    assert.strictEqual(slider.value, '150');
    assert.strictEqual(beats[2].attributes['aria-pressed'], 'true');

    saveResponse = async () => ({ok: false, status: 500});
    signedIn.setTempo(160); await signedIn.flushPreferences();
    assert(!saveStatus.hidden && !saveRetry.hidden && !saveRetry.disabled, 'Failed saves expose a retry without blocking playback');
    assert(!panel.hidden && contexts.at(-1).state === 'running');
    signedIn.setTempo(170);
    saveResponse = async () => ({ok: true, json: async () => ({preferences: {tempo: 170}})});
    await saveRetry.events.click();
    assert.deepStrictEqual(JSON.parse(requests.at(-1).body), {preferences: {tempo: 170}});
    assert(saveStatus.hidden && saveRetry.hidden);
    signedIn.setBeatCount(4); await settings.events['hide.bs.modal']();
    assert.deepStrictEqual(JSON.parse(requests.at(-1).body), {preferences: {beats: 4}}, 'Dismissing settings flushes the last edit');
    signedIn.setTempo(180); close.events.click(); await signedIn.preferenceRequest;
    assert.deepStrictEqual(JSON.parse(requests.at(-1).body), {preferences: {tempo: 180}}, 'Panel close flushes pending tempo');

    saveResponse = async () => ({ok: false, status: 419});
    signedIn.setTempo(190); await signedIn.flushPreferences();
    assert.strictEqual(signedIn.preferencesUrl, null);
    assert(!saveStatus.hidden && saveRetry.hidden, 'An expired session asks for refresh instead of retrying unauthorized writes');
    const afterExpiry = requests.length;
    signedIn.setTempo(200); signedIn.setBeatCount(1); await signedIn.setSound('click');
    await signedIn.flushPreferences();
    assert.strictEqual(requests.length, afterExpiry);
    assert.strictEqual(slider.value, '200', 'The metronome remains usable after the session expires');

    delete attributes['data-metronome-preferences-url'];
    delete attributes['data-metronome-preferences'];
    const guest = new window.ScoreMetronome(container);
    assert.strictEqual(slider.value, '60');
    assert.strictEqual(sound.value, 'woodblock');
    assert.strictEqual(beats[0].attributes['aria-pressed'], 'true');
    guest.setTempo(100); guest.setBeatCount(2); await guest.setSound('hi-hat');
    guest.close(); await guest.flushPreferences();
    assert.strictEqual(requests.length, afterExpiry, 'A guest never persists earlier or new account choices');
    assert.strictEqual(timeouts.size, 0);
    delete window.AudioContext;
    await button.events.click();
    assert(panel.hidden && !error.hidden && timers.size === 0, 'Unsupported browsers do not leave a playing state');
};
