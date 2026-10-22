import { VoiceCapture, voiceBase64, voiceBytes } from './voice-audio';

const WORDS = ['one', 'two', 'three', 'four'];

export class VoiceRecorder {
    constructor({ root, dialog, clock, soundInput, getBeats, useSound }) {
        Object.assign(this, { root, dialog, clock, soundInput, getBeats, useSound });
        this.ui = name => root.querySelector(`[data-voice-${name}]`);
        this.option = soundInput.querySelector('[value="my-voice"]');
        this.option.disabled = true;
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
        this.storageKey = `metronome.voice.${root.dataset.owner}.v1`;
        this.selectedKey = `metronome.voice.${root.dataset.owner}.selected.v1`;
        this.preferred = false;
        try {
            this.preferred = localStorage.getItem(this.storageKey) === '1';
            this.preferredId = localStorage.getItem(this.selectedKey);
        } catch (error) { /* Optional preference. */ }
        this.choiceInput = root.querySelector('[name="metronome_voice_choice"]');
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
        this.ui('listen').addEventListener('click', () => this.preview(this.buffers.slice(0, this.getBeats())));
        this.ui('preview-all').addEventListener('click', () => this.preview(this.draftBuffers));
        this.ui('redo').addEventListener('click', () => this.record({ redo: this.selected || 0 }));
        root.querySelectorAll('[data-voice-preview]').forEach(button => button.addEventListener('click', () => {
            this.selected = Number(button.dataset.voicePreview);
            this.ui('redo').textContent = `Redo ${this.selected + 1}`;
            this.preview([this.draftBuffers[this.selected]]);
        }));
        this.ui('delete').addEventListener('click', () => { this.ui('delete-confirm').hidden = false; });
        this.choiceInput.addEventListener('change', () => this.choose(this.choiceInput.value));
        this.ui('rename').addEventListener('click', () => {
            this.renameInput.value = this.current()?.name || '';
            this.ui('rename-panel').hidden = false;
            this.ui('delete-confirm').hidden = true;
            this.renameInput.focus();
        });
        this.ui('rename-cancel').addEventListener('click', () => { this.ui('rename-panel').hidden = true; });
        this.ui('rename-save').addEventListener('click', () => this.rename());
        this.renameInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); this.rename(); }
        });
        this.nameInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); this.save(); }
        });
        this.ui('keep').addEventListener('click', () => { this.ui('delete-confirm').hidden = true; });
        this.ui('confirm-delete').addEventListener('click', () => this.remove());
        this.ui('reload').addEventListener('click', () => { this.cancel(); this.load(); });
        this.load();
    }

    context() {
        if (!this.audio || this.audio.state === 'closed') {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audio = new AudioContext({ latencyHint: 'interactive' });
        }
        return this.audio;
    }

    remember(sound) {
        this.selection++;
        this.preferred = sound === 'my-voice';
        try {
            localStorage.setItem(this.storageKey, this.preferred ? '1' : '0');
            if (this.activeId) localStorage.setItem(this.selectedKey, this.activeId);
        } catch (error) { /* Optional preference. */ }
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
        this.ui('summary-text').textContent = this.library.length ? 'Your counting voices' : 'Count in your own voice.';
        this.ui('start-label').textContent = this.library.length ? 'New recording' : 'Record my voice';
        this.ui('library').hidden = !this.library.length;
        this.choiceInput.replaceChildren(...this.library.map(voice => new Option(voice.name, voice.id)));
        this.choiceInput.value = this.activeId || '';
        this.ui('delete-label').textContent = `Delete “${this.current()?.name || 'this recording'}”?`;
        this.controls();
        this.ui('status').textContent = this.library.length >= this.limit ? 'You have 20 saved voices. Delete one to make room.'
            : this.supported ? '' : window.isSecureContext
            ? 'This browser can’t record audio. Try another browser.' : 'Open this page over HTTPS to record.';
    }

    current() { return this.library.find(voice => voice.id === this.activeId); }

    controls() {
        this.ui('start').disabled = !this.ready || !this.supported || this.saving || this.loadingVoice || this.library.length >= this.limit;
        this.choiceInput.disabled = !this.ready || this.saving;
        this.ui('listen').disabled = !this.buffers.length || this.loadingVoice || this.saving;
        ['rename', 'delete'].forEach(name => { this.ui(name).disabled = !this.current() || this.loadingVoice || this.saving; });
    }

    clearVoice() {
        this.activeId = this.revision = null;
        this.saved = this.buffers = [];
        this.clock.setVoiceBuffers(null);
        this.option.disabled = true;
        if (this.soundInput.value === 'my-voice') this.useSound('click');
    }

    async choose(id, { activate = true } = {}) {
        if (!this.library.some(voice => voice.id === id)) return;
        const version = ++this.voiceVersion;
        const selection = this.selection;
        this.stopPreview();
        this.clock.stop();
        this.activeId = id;
        this.loadingVoice = true;
        this.buffers = this.saved = [];
        this.clock.setVoiceBuffers(null);
        this.option.disabled = true;
        this.ui('rename-panel').hidden = this.ui('delete-confirm').hidden = true;
        this.controls();
        this.ui('status').textContent = 'Loading recording…';
        try {
            const data = await this.request('GET', null, id);
            const buffers = await Promise.all(data.clips.map(clip => this.context().decodeAudioData(voiceBytes(clip))));
            if (version !== this.voiceVersion) return;
            this.library = this.library.map(voice => voice.id === id ? { id, name: data.name, revision: data.revision } : voice);
            this.revision = data.revision;
            this.saved = data.clips;
            this.buffers = buffers;
            this.clock.setVoiceBuffers(buffers);
            this.option.disabled = false;
            this.preferredId = id;
            try { localStorage.setItem(this.selectedKey, id); } catch (error) { /* Optional preference. */ }
            if (activate && selection === this.selection) {
                this.useSound('my-voice', false);
                this.remember('my-voice');
            }
            this.summary();
        } catch (error) {
            if (version !== this.voiceVersion) return;
            if (this.soundInput.value === 'my-voice') this.useSound('click');
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
        this.ui('delete-confirm').hidden = true;
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
            this.capture.cancel();
            const draftBuffers = await Promise.all(this.draft.map(bytes => this.context().decodeAudioData(bytes.slice(0))));
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
        this.ui('panel').hidden = this.ui('delete-confirm').hidden = this.ui('rename-panel').hidden = true;
        this.ui('summary').hidden = false;
        delete this.dialog.dataset.recording;
        this.draft = [];
        this.draftBuffers = [];
        this.cue('idle', '');
        this.summary();
    }

    stopPreview() {
        this.previewGeneration = (this.previewGeneration || 0) + 1;
        this.previewSources.forEach(source => { source.stop(); source.disconnect(); });
        this.previewSources = [];
    }

    async preview(buffers) {
        if (!buffers?.length) return;
        this.stopPreview();
        this.clock.stop();
        const generation = this.previewGeneration;
        try {
            const audio = this.context();
            await audio.resume();
            if (generation !== this.previewGeneration) return;
            let time = audio.currentTime + 0.03;
            buffers.forEach(buffer => {
                const source = audio.createBufferSource();
                source.buffer = buffer;
                source.connect(audio.destination);
                source.onended = () => {
                    source.disconnect();
                    this.previewSources = this.previewSources.filter(item => item !== source);
                };
                source.start(time);
                this.previewSources.push(source);
                time += Math.max(0.65, buffer.duration + 0.12);
            });
        } catch (error) { this.ui('status').textContent = 'Sound couldn’t start. Tap Listen to try again.'; }
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
            await this.context().resume();
            if (flow !== this.flow) return;
            const data = await this.request('PUT', { name, clips });
            if (flow !== this.flow) {
                this.ready = false;
                if (this.dialog.open) this.load();
                return;
            }
            this.revision = data.revision;
            this.activeId = this.preferredId = data.id;
            this.library.push({ id: data.id, name: data.name, revision: data.revision });
            this.saved = data.clips;
            this.buffers = this.draftBuffers;
            this.clock.setVoiceBuffers(this.buffers);
            this.option.disabled = false;
            this.useSound('my-voice');
            this.remember('my-voice');
            this.cancel();
            this.ui('status').textContent = 'Saved to your account.';
            this.preview(this.buffers.slice(0, this.getBeats()));
        } catch (error) {
            if (flow === this.flow) this.failure(error);
        } finally {
            this.saving = false;
            this.ui('save').disabled = false;
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
        if (this.saving || !this.current()) return;
        const name = this.validName(this.renameInput);
        if (!name) return;
        const flow = this.flow;
        const { id, revision } = this.current();
        this.saving = true;
        this.controls();
        this.ui('rename-save').disabled = true;
        try {
            const data = await this.request('PATCH', { name, revision }, id);
            if (flow !== this.flow) {
                this.ready = false;
                if (this.dialog.open) this.load();
                return;
            }
            this.library = this.library.map(voice => voice.id === id ? data : voice);
            this.revision = data.revision;
            this.ui('rename-panel').hidden = true;
            this.summary();
            this.ui('status').textContent = 'Name saved.';
        } catch (error) { if (flow === this.flow) this.failure(error); }
        finally { this.saving = false; this.ui('rename-save').disabled = false; this.controls(); }
    }

    async remove() {
        if (this.saving || !this.current()) return;
        const flow = this.flow;
        const { id, revision } = this.current();
        this.saving = true;
        this.controls();
        this.ui('confirm-delete').disabled = true;
        this.stopPreview();
        this.clock.stop();
        try {
            await this.request('DELETE', { revision }, id);
            if (flow !== this.flow) {
                this.ready = false;
                if (this.dialog.open) this.load();
                return;
            }
            const activate = this.soundInput.value === 'my-voice';
            this.library = this.library.filter(voice => voice.id !== id);
            this.clearVoice();
            this.cancel();
            if (this.library.length) await this.choose(this.library[0].id, { activate });
            else this.remember('click');
        } catch (error) { if (flow === this.flow) this.failure(error); }
        finally { this.saving = false; this.ui('confirm-delete').disabled = false; this.controls(); }
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
        this.clock.stop();
        this.clock.setVoiceBuffers(null);
        this.option.disabled = true;
        this.ready = false;
        this.buffers = this.saved = [];
        this.library = [];
        this.activeId = null;
        this.draft = this.draftBuffers = [];
        this.ui('panel').hidden = true;
        this.ui('summary').hidden = false;
        delete this.dialog.dataset.recording;
        if (this.soundInput.value === 'my-voice') this.useSound('click');
        this.summary();
        this.ui('status').textContent = error.message;
        this.ui('signin').hidden = false;
        this.ui('start').disabled = true;
    }
}
