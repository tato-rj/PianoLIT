import { VoiceCapture, VOICE_RATE, voiceBase64, voiceBytes } from './voice-audio';

const WORDS = ['one', 'two', 'three', 'four'];

export class VoiceRecorder {
    constructor({ root, dialog, clock, soundInput, getBeats, useSound, onUpdate = () => {} }) {
        Object.assign(this, { root, dialog, clock, soundInput, getBeats, useSound, onUpdate });
        this.ui = name => root.querySelector(`[data-voice-${name}]`);
        this.emptyOption = soundInput.querySelector('[value="my-voice"]');
        this.emptyOption.disabled = true;
        this.emptyOption.hidden = true;
        this.voiceGroup = document.createElement('optgroup');
        this.voiceGroup.label = 'Recorded voices';
        this.sound = soundInput.value;
        this.flow = 0;
        this.selection = 0;
        this.loadVersion = 0;
        this.previewSources = [];
        this.saved = [];
        this.library = [];
        this.limit = 20;
        this.activeId = null;
        this.voiceVersion = 0;
        this.buffers = [];
        this.draft = [];
        this.revision = null;
        this.ready = false;
        this.saving = false;
        this.storageKey = `metronome.voice.${root.dataset.owner}.v1`;
        this.selectedKey = `metronome.voice.${root.dataset.owner}.selected.v1`;
        this.preferred = false;
        try {
            this.preferred = localStorage.getItem(this.storageKey) === '1';
            this.preferredId = localStorage.getItem(this.selectedKey);
        } catch (error) { /* Optional preference. */ }
        this.nameInput = root.querySelector('[name="metronome_voice_name"]');
        this.renameInput = root.querySelector('[name="metronome_voice_rename"]');
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        this.supported = !!(navigator.mediaDevices?.getUserMedia && window.MediaRecorder && AudioContext);
        this.capture = new VoiceCapture({ mediaDevices: navigator.mediaDevices, Recorder: window.MediaRecorder,
            createContext: () => new AudioContext({ latencyHint: 'interactive' }) });
        this.ui('start').addEventListener('click', () => this.record());
        this.ui('retry').addEventListener('click', () => this.record({ resume: true }));
        this.ui('cancel').addEventListener('click', () => this.cancel());
        this.ui('save').addEventListener('click', () => this.save());
        this.ui('preview-all').addEventListener('click', () => this.preview(this.draftBuffers));
        this.ui('redo').addEventListener('click', () => this.record({ redo: this.selected || 0 }));
        root.querySelectorAll('[data-voice-preview]').forEach(button => button.addEventListener('click', () => {
            this.selected = Number(button.dataset.voicePreview);
            this.ui('redo').textContent = `Redo ${this.selected + 1}`;
            this.preview([this.draftBuffers[this.selected]]);
        }));
        this.ui('rename-cancel').addEventListener('click', () => { this.ui('rename-panel').hidden = true; });
        this.ui('rename-save').addEventListener('click', () => this.rename());
        this.renameInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); this.rename(); }
        });
        this.nameInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); this.save(); }
        });
        this.ui('reload').addEventListener('click', () => { this.cancel(); this.load(); });
        this.load();
    }

    context() {
        return this.clock.getContext();
    }

    decodeVoice(bytes) {
        // Loading saved counts should not activate a second output device or
        // carry a microphone-era playback context into the next practice run.
        const OfflineContext = window.OfflineAudioContext || window.webkitOfflineAudioContext;
        if (!OfflineContext) return this.context().decodeAudioData(bytes);
        if (!this.decoder) this.decoder = new OfflineContext(1, 1, VOICE_RATE);
        return this.decoder.decodeAudioData(bytes);
    }

    remember(sound) {
        this.selection++;
        this.sound = sound;
        this.preferred = sound === 'my-voice';
        try {
            localStorage.setItem(this.storageKey, this.preferred ? '1' : '0');
            if (this.activeId) localStorage.setItem(this.selectedKey, this.activeId);
        } catch (error) { /* Optional preference. */ }
        this.controls();
    }

    voiceValue(id) { return `voice:${id}`; }

    syncSound(sound) {
        this.sound = sound;
        this.soundInput.value = sound === 'my-voice' && this.activeId ? this.voiceValue(this.activeId) : sound;
        if (sound !== 'my-voice') this.ui('rename-panel').hidden = true;
        this.controls();
    }

    selectSound(value) {
        if (value === 'my-voice' && !this.library.length) {
            this.syncSound(this.sound);
            this.record();
            return true;
        }
        const recording = this.library.find(voice => this.voiceValue(voice.id) === value);
        if (!recording) return false;
        this.selection++;
        this.choose(recording.id);
        return true;
    }

    soundOptions() {
        if (this.library.length) {
            this.emptyOption.remove();
            this.voiceGroup.replaceChildren(...this.library.map(voice => new Option(voice.name, this.voiceValue(voice.id))));
            if (this.voiceGroup.parentNode !== this.soundInput) this.soundInput.append(this.voiceGroup);
        } else {
            this.voiceGroup.remove();
            if (this.emptyOption.parentNode !== this.soundInput) this.soundInput.append(this.emptyOption);
        }
        this.syncSound(this.sound);
    }

    async request(method = 'GET', body, id = null) {
        const response = await fetch(this.root.dataset.url.replace(/\/$/, '') + (id ? '/' + encodeURIComponent(id) : ''), {
            method, credentials: 'same-origin', cache: 'no-store',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.root.dataset.csrf },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            const error = new Error(response.status === 409 ? 'Your recording changed in another tab. Reload it to continue.'
                : [401, 419].includes(response.status) ? 'Please sign in again to use your recording.'
                    : response.status === 404 ? 'This recording was deleted. Reload your voices to continue.'
                        : response.status === 422 ? Object.values(data.errors || {}).flat()[0] || 'Check the recording name and try again.'
                            : response.status === 429 ? 'Wait a moment, then try again.' : 'Couldn’t save your recording. Please try again.');
            error.status = response.status;
            throw error;
        }
        return response.json();
    }

    async load() {
        const version = ++this.loadVersion;
        this.voiceVersion++;
        this.loadingVoice = false;
        const selection = this.selection;
        this.ready = false;
        this.controls();
        this.ui('reload').hidden = true;
        this.ui('signin').hidden = true;
        this.ui('status').textContent = 'Loading your voices…';
        try {
            const data = await this.request();
            if (version !== this.loadVersion) return;
            this.library = data.recordings;
            this.limit = data.limit;
            this.ready = true;
            this.summary();
            const id = [this.activeId, this.preferredId].find(value => this.library.some(voice => voice.id === value)) || this.library[0]?.id;
            if (id) await this.choose(id, { activate: this.preferred && selection === this.selection });
            else { this.clearVoice(); this.summary(); }
        } catch (error) {
            if (version !== this.loadVersion) return;
            this.ui('status').textContent = [401, 419].includes(error.status) ? error.message : 'Your voices couldn’t load. Try again.';
            this.ui('reload').hidden = false;
            this.invalidateSession(error);
        }
    }

    summary() {
        this.soundOptions();
        this.controls();
        this.ui('status').textContent = this.library.length >= this.limit ? 'You have 20 saved voices. Delete one to make room.'
            : this.supported ? '' : window.isSecureContext
            ? 'This browser can’t record audio. Try another browser.' : 'Open this page over HTTPS to record.';
    }

    current() { return this.library.find(voice => voice.id === this.activeId); }

    controls() {
        this.ui('start').disabled = !this.ready || !this.supported || this.saving || this.loadingVoice || this.library.length >= this.limit;
        this.voiceGroup.disabled = !this.ready || this.saving;
        this.emptyOption.disabled = !this.ready || !this.supported || this.saving;
        this.emptyOption.hidden = !this.ready || !!this.library.length;
        this.onUpdate(this);
    }

    edit(id) {
        const voice = this.library.find(voice => voice.id === id);
        if (!voice || this.saving) return;
        this.renameId = id;
        this.renameInput.value = voice.name;
        this.ui('rename-panel').hidden = false;
        this.renameInput.focus();
    }

    confirmDelete(id) {
        const voice = this.library.find(voice => voice.id === id);
        if (!voice || this.saving) return;
        if (!window.confirm(`Delete “${voice.name}”?`)) return;
        this.ui('rename-panel').hidden = true;
        return this.remove(id);
    }

    clearVoice() {
        this.activeId = this.revision = null;
        this.saved = this.buffers = [];
        this.clock.setVoiceBuffers(null);
        if (this.sound === 'my-voice') this.useSound('click');
    }

    async choose(id, { activate = true } = {}) {
        if (!this.library.some(voice => voice.id === id)) return;
        const version = ++this.voiceVersion;
        const selection = this.selection;
        this.stopPreview();
        if (activate) this.clock.stop();
        this.activeId = id;
        this.loadingVoice = true;
        this.buffers = this.saved = [];
        this.clock.setVoiceBuffers(null);
        this.ui('rename-panel').hidden = true;
        this.controls();
        this.ui('status').textContent = 'Loading recording…';
        try {
            const data = await this.request('GET', null, id);
            const buffers = await Promise.all(data.clips.map(clip => this.decodeVoice(voiceBytes(clip))));
            if (version !== this.voiceVersion) return;
            this.library = this.library.map(voice => voice.id === id ? { id, name: data.name, revision: data.revision } : voice);
            this.revision = data.revision;
            this.saved = data.clips;
            this.buffers = buffers;
            this.clock.setVoiceBuffers(buffers);
            this.preferredId = id;
            try { localStorage.setItem(this.selectedKey, id); } catch (error) { /* Optional preference. */ }
            if (activate && selection === this.selection) {
                this.useSound('my-voice', false);
                this.remember('my-voice');
            }
            this.summary();
        } catch (error) {
            if (version !== this.voiceVersion) return;
            if (this.sound === 'my-voice') this.useSound('click');
            this.failure(error);
            this.ui('reload').hidden = false;
        } finally {
            if (version === this.voiceVersion) { this.loadingVoice = false; this.controls(); }
        }
    }

    async delay(milliseconds, flow) {
        await new Promise(resolve => setTimeout(resolve, milliseconds));
        if (flow !== this.flow) throw new DOMException('Canceled', 'AbortError');
    }

    cue(state, prompt, countdown = '') {
        this.ui('cue').dataset.state = state;
        this.ui('cue-label').textContent = { waiting: 'Wait', speaking: 'Speak now', success: 'Recorded', error: 'Try again' }[state] || '';
        if (this.ui('prompt').textContent !== prompt) this.ui('prompt').textContent = prompt;
        this.ui('countdown').textContent = countdown;
        this.ui('countdown').hidden = !countdown;
        this.ui('visual').hidden = ['idle', 'review'].includes(state);
        this.ui('meter').hidden = state !== 'speaking';
    }

    async record({ resume = false, redo = null } = {}) {
        if (!this.ready || !this.supported || this.saving || this.loadingVoice || this.library.length >= this.limit) return;
        const flow = ++this.flow;
        this.stopPreview();
        this.clock.stop();
        if (!resume) {
            if (redo === null) {
                this.draft = [];
                this.nameInput.value = `Voice ${this.library.length + 1}`;
            }
            this.nextIndex = redo === null ? 0 : redo;
            this.endIndex = redo === null ? 4 : redo + 1;
        }
        this.dialog.dataset.recording = 'true';
        this.ui('summary').hidden = true;
        this.ui('panel').hidden = false;
        this.ui('rename-panel').hidden = true;
        this.ui('review').hidden = true;
        this.ui('save').hidden = this.ui('retry').hidden = true;
        this.ui('number').textContent = String(this.nextIndex + 1);
        this.ui('step').textContent = 'Microphone';
        this.cue('waiting', 'Allow microphone access');
        this.ui('status').textContent = '';
        this.ui('reload').hidden = true;
        this.ui('cancel').focus();
        try {
            // Discard the playback device before the microphone changes its
            // route/sample rate. Playback and previews share a fresh context.
            await this.clock.releaseAudio();
            if (flow !== this.flow) return;
            await this.capture.open();
            for (; this.nextIndex < this.endIndex; this.nextIndex++) {
                const index = this.nextIndex;
                if (flow !== this.flow) return;
                this.ui('number').textContent = String(index + 1);
                this.ui('step').textContent = `${index + 1} of 4`;
                this.level(0);
                for (let remaining = 3; remaining > 0; remaining--) {
                    this.cue('waiting', 'Wait for green', `Ready in ${remaining}`);
                    await this.delay(500, flow);
                }
                this.cue('speaking', `Say “${WORDS[index]}”`);
                this.draft[index] = await this.capture.record(level => this.level(level));
                if (flow !== this.flow) return;
                this.cue('success', 'Got it!');
                await this.delay(600, flow);
            }
            await this.capture.cancel();
            if (flow !== this.flow) return;
            const draftBuffers = await Promise.all(this.draft.map(bytes => this.decodeVoice(bytes.slice(0))));
            if (flow !== this.flow) return;
            this.draftBuffers = draftBuffers;
            this.ui('step').textContent = '4 of 4 recorded';
            this.cue('review', 'How does it sound?');
            this.ui('review').hidden = this.ui('save').hidden = false;
            this.nameInput.focus();
        } catch (error) {
            if (flow !== this.flow) return;
            this.capture.cancel();
            this.level(0);
            this.cue('error', `Let’s try ${WORDS[this.nextIndex]} again`);
            this.ui('status').textContent = error.name === 'NotAllowedError' ? 'Allow the microphone in your browser, then try again.'
                : error.name === 'NotFoundError' ? 'Connect a microphone, then try again.'
                    : error.name === 'NotReadableError' ? 'Your microphone is busy. Close other recording apps and try again.'
                        : error.message.startsWith('I couldn’t') || error.message.startsWith('A little') ? error.message : 'Recording stopped. Please try again.';
            this.ui('retry').hidden = false;
            this.ui('retry').focus();
        }
    }

    level(value) {
        this.ui('level').style.width = `${value}%`;
        this.ui('meter').setAttribute('aria-valuenow', String(value));
    }

    cancel() {
        this.flow++;
        this.capture.cancel();
        this.stopPreview();
        this.ui('panel').hidden = this.ui('rename-panel').hidden = true;
        this.ui('summary').hidden = false;
        delete this.dialog.dataset.recording;
        this.draft = [];
        this.draftBuffers = [];
        this.renameId = null;
        this.cue('idle', '');
        this.summary();
    }

    stopPreview() {
        this.previewRequestVersion = (this.previewRequestVersion || 0) + 1;
        this.previewingId = null;
        this.previewGeneration = (this.previewGeneration || 0) + 1;
        this.previewSources.forEach(source => { source.stop(); source.disconnect(); });
        this.previewSources = [];
        if (!this.clock.running) this.clock.suspendAudio();
        this.onUpdate(this);
    }

    async previewRecording(id) {
        if (this.previewingId === id) { this.stopPreview(); return; }
        if (!this.ready || this.saving || !this.library.some(voice => voice.id === id)) return;
        this.stopPreview();
        const version = this.previewRequestVersion;
        this.clock.stop();
        this.previewingId = id;
        this.onUpdate(this);
        try {
            await this.clock.resumeAudio();
            if (version !== this.previewRequestVersion) return;
            const data = id === this.activeId && this.buffers.length === 4 ? null : await this.request('GET', null, id);
            const buffers = data ? await Promise.all(data.clips.map(clip => this.decodeVoice(voiceBytes(clip)))) : this.buffers;
            if (version !== this.previewRequestVersion) return;
            await this.preview(buffers.slice(0, this.getBeats()), id);
        } catch (error) {
            if (version !== this.previewRequestVersion) return;
            this.stopPreview();
            this.failure(error);
        }
    }

    async preview(buffers, id = null) {
        if (!buffers?.length) return;
        this.stopPreview();
        this.previewingId = id;
        this.onUpdate(this);
        this.clock.stop();
        const generation = this.previewGeneration;
        try {
            await this.clock.resumeAudio();
            if (generation !== this.previewGeneration) return;
            const audio = this.context();
            let time = audio.currentTime + 0.03;
            buffers.forEach(buffer => {
                const source = audio.createBufferSource();
                source.buffer = buffer;
                source.connect(audio.destination);
                source.onended = () => {
                    source.disconnect();
                    if (generation !== this.previewGeneration) return;
                    this.previewSources = this.previewSources.filter(item => item !== source);
                    if (!this.previewSources.length) this.stopPreview();
                };
                source.start(time);
                this.previewSources.push(source);
                time += Math.max(0.65, buffer.duration + 0.12);
            });
        } catch (error) { this.stopPreview(); this.ui('status').textContent = 'Sound couldn’t start. Tap Play to try again.'; }
    }

    async save() {
        if (this.saving || this.draft.length !== 4) return;
        const name = this.validName(this.nameInput);
        if (!name) return;
        const flow = this.flow;
        const clips = this.draft.map(voiceBase64);
        this.saving = true;
        this.controls();
        this.ui('save').disabled = true;
        this.ui('status').textContent = 'Saving your voice…';
        try {
            // Unlock preview audio in the button gesture, before the network.
            await this.clock.resumeAudio();
            if (flow !== this.flow) return;
            const data = await this.request('PUT', { name, clips });
            if (flow !== this.flow) {
                this.ready = false;
                if (this.dialog.getAttribute('aria-modal') === 'true') this.load();
                return;
            }
            this.revision = data.revision;
            this.activeId = this.preferredId = data.id;
            this.library.push({ id: data.id, name: data.name, revision: data.revision });
            this.saved = data.clips;
            this.buffers = this.draftBuffers;
            this.clock.setVoiceBuffers(this.buffers);
            this.soundOptions();
            this.useSound('my-voice');
            this.remember('my-voice');
            this.cancel();
            this.ui('status').textContent = 'Saved to your account.';
            await this.preview(this.buffers.slice(0, this.getBeats()));
        } catch (error) {
            if (flow === this.flow) this.failure(error);
        } finally {
            this.saving = false;
            this.ui('save').disabled = false;
            if (!this.previewSources.length && !this.clock.running) this.clock.suspendAudio();
            this.controls();
        }
    }

    validName(input) {
        const name = input.value.trim();
        if (!name || [...name].length > 60) {
            this.ui('status').textContent = name ? 'Keep the name to 60 characters.' : 'Give your recording a name.';
            input.focus();
            return null;
        }
        return name;
    }

    async rename() {
        const target = this.library.find(voice => voice.id === (this.renameId || this.activeId));
        if (this.saving || !target) return;
        const name = this.validName(this.renameInput);
        if (!name) return;
        const flow = this.flow;
        const { id, revision } = target;
        this.saving = true;
        this.controls();
        this.ui('rename-save').disabled = true;
        try {
            const data = await this.request('PATCH', { name, revision }, id);
            if (flow !== this.flow) {
                this.ready = false;
                if (this.dialog.getAttribute('aria-modal') === 'true') this.load();
                return;
            }
            this.library = this.library.map(voice => voice.id === id ? data : voice);
            if (id === this.activeId) this.revision = data.revision;
            this.renameId = null;
            this.ui('rename-panel').hidden = true;
            this.summary();
            this.ui('status').textContent = 'Name saved.';
        } catch (error) { if (flow === this.flow) this.failure(error); }
        finally { this.saving = false; this.ui('rename-save').disabled = false; this.controls(); }
    }

    async remove(recordingId = this.activeId) {
        const target = this.library.find(voice => voice.id === recordingId);
        if (this.saving || !target) return;
        const flow = this.flow;
        const { id, revision } = target;
        const active = id === this.activeId;
        this.saving = true;
        this.controls();
        this.stopPreview();
        if (active && this.sound === 'my-voice') this.clock.stop();
        try {
            await this.request('DELETE', { revision }, id);
            if (flow !== this.flow) {
                this.ready = false;
                if (this.dialog.getAttribute('aria-modal') === 'true') this.load();
                return;
            }
            const activate = this.sound === 'my-voice';
            this.library = this.library.filter(voice => voice.id !== id);
            if (active) this.clearVoice();
            this.cancel();
            if (active && this.library.length) await this.choose(this.library[0].id, { activate });
            else if (!this.library.length) this.remember(activate ? 'click' : this.sound);
        } catch (error) { if (flow === this.flow) this.failure(error); }
        finally { this.saving = false; this.controls(); }
    }

    failure(error) {
        this.ui('status').textContent = error.message;
        this.ui('reload').hidden = ![404, 409, 401, 419].includes(error.status);
        this.invalidateSession(error);
    }

    invalidateSession(error) {
        if (![401, 419].includes(error.status)) return;
        this.flow++;
        this.voiceVersion++;
        this.loadVersion++;
        this.capture.cancel();
        this.stopPreview();
        if (this.sound === 'my-voice') this.clock.stop();
        this.clock.setVoiceBuffers(null);
        this.ready = false;
        this.buffers = this.saved = [];
        this.library = [];
        this.activeId = null;
        this.draft = this.draftBuffers = [];
        this.ui('panel').hidden = true;
        this.ui('summary').hidden = false;
        delete this.dialog.dataset.recording;
        if (this.sound === 'my-voice') this.useSound('click');
        this.summary();
        this.ui('status').textContent = error.message;
        this.ui('signin').hidden = false;
        this.ui('start').disabled = true;
    }
}
