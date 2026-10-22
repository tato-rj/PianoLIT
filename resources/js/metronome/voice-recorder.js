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
        this.buffers = [];
        this.draft = [];
        this.revision = null;
        this.ready = false;
        this.storageKey = `metronome.voice.${root.dataset.owner}.v1`;
        this.preferred = false;
        try { this.preferred = localStorage.getItem(this.storageKey) === '1'; } catch (error) { /* Optional preference. */ }
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
        try { localStorage.setItem(this.storageKey, this.preferred ? '1' : '0'); } catch (error) { /* Optional preference. */ }
    }

    async request(method = 'GET', body) {
        const response = await fetch(this.root.dataset.url, {
            method, credentials: 'same-origin', cache: 'no-store',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.root.dataset.csrf },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        if (!response.ok) {
            const error = new Error(response.status === 409 ? 'Your recording changed in another tab. Reload it to continue.'
                : [401, 419].includes(response.status) ? 'Please sign in again to use your recording.'
                    : response.status === 429 ? 'Wait a moment, then try again.' : 'Couldn’t save your recording. Please try again.');
            error.status = response.status;
            throw error;
        }
        return response.json();
    }

    async load() {
        const version = ++this.loadVersion;
        const selection = this.selection;
        this.ui('start').disabled = true;
        this.ui('reload').hidden = true;
        this.ui('signin').hidden = true;
        this.ui('status').textContent = 'Loading your voice…';
        try {
            const data = await this.request();
            const buffers = data.clips.length ? await Promise.all(data.clips.map(clip => this.context().decodeAudioData(voiceBytes(clip)))) : [];
            if (version !== this.loadVersion) return;
            this.revision = data.revision;
            this.saved = data.clips;
            this.buffers = buffers;
            this.ready = true;
            this.clock.setVoiceBuffers(buffers);
            this.option.disabled = buffers.length !== 4;
            if (buffers.length === 4 && this.preferred && selection === this.selection) this.useSound('my-voice', false);
            if (!buffers.length && this.soundInput.value === 'my-voice') this.useSound('click');
            this.summary();
        } catch (error) {
            if (version !== this.loadVersion) return;
            this.ui('status').textContent = [401, 419].includes(error.status) ? error.message : 'Your voice couldn’t load. Try again.';
            this.ui('reload').hidden = false;
            this.invalidateSession(error);
        }
    }

    summary() {
        this.ui('summary-text').textContent = this.saved.length ? 'Your counting voice is ready.' : 'Count in your own voice.';
        this.ui('start-label').textContent = this.saved.length ? 'Record again' : 'Record my voice';
        this.ui('start').disabled = !this.ready || !this.supported;
        this.ui('listen').hidden = !this.saved.length;
        this.ui('delete').hidden = !this.saved.length;
        this.ui('status').textContent = this.supported ? '' : window.isSecureContext
            ? 'This browser can’t record audio. Try another browser.' : 'Open this page over HTTPS to record.';
    }

    async delay(milliseconds, flow) {
        await new Promise(resolve => setTimeout(resolve, milliseconds));
        if (flow !== this.flow) throw new DOMException('Canceled', 'AbortError');
    }

    async record({ resume = false, redo = null } = {}) {
        if (!this.ready || !this.supported || this.saving) return;
        const flow = ++this.flow;
        this.stopPreview();
        this.clock.stop();
        if (!resume) {
            if (redo === null) this.draft = [];
            this.nextIndex = redo === null ? 0 : redo;
            this.endIndex = redo === null ? 4 : redo + 1;
        }
        this.dialog.dataset.recording = 'true';
        this.ui('summary').hidden = true;
        this.ui('panel').hidden = false;
        this.ui('delete-confirm').hidden = true;
        this.ui('review').hidden = true;
        this.ui('save').hidden = this.ui('retry').hidden = true;
        this.ui('number').hidden = this.ui('meter').hidden = false;
        this.ui('number').textContent = String(this.nextIndex + 1);
        this.ui('step').textContent = 'Microphone';
        this.ui('prompt').textContent = 'Allow microphone access';
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
                this.ui('prompt').textContent = 'Get ready…';
                this.level(0);
                await this.delay(index === 0 || resume || redo !== null ? 1200 : 700, flow);
                this.ui('prompt').textContent = `Say “${WORDS[index]}”`;
                this.draft[index] = await this.capture.record(level => this.level(level));
                if (flow !== this.flow) return;
                this.ui('prompt').textContent = 'Got it';
                await this.delay(400, flow);
            }
            this.capture.cancel();
            const draftBuffers = await Promise.all(this.draft.map(bytes => this.context().decodeAudioData(bytes.slice(0))));
            if (flow !== this.flow) return;
            this.draftBuffers = draftBuffers;
            this.ui('number').hidden = this.ui('meter').hidden = true;
            this.ui('step').textContent = '4 of 4 recorded';
            this.ui('prompt').textContent = 'How does it sound?';
            this.ui('review').hidden = this.ui('save').hidden = false;
            this.ui('save').focus();
        } catch (error) {
            if (flow !== this.flow) return;
            this.capture.cancel();
            this.level(0);
            this.ui('prompt').textContent = `Let’s try ${WORDS[this.nextIndex]} again`;
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
        this.ui('panel').hidden = this.ui('delete-confirm').hidden = true;
        this.ui('summary').hidden = false;
        delete this.dialog.dataset.recording;
        this.draft = [];
        this.draftBuffers = [];
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
        const flow = this.flow;
        const clips = this.draft.map(voiceBase64);
        this.saving = true;
        this.ui('save').disabled = true;
        this.ui('status').textContent = 'Saving your voice…';
        try {
            // Unlock preview audio in the button gesture, before the network.
            await this.context().resume();
            if (flow !== this.flow) return;
            const data = await this.request('PUT', { revision: this.revision, clips });
            if (flow !== this.flow) {
                this.ready = false;
                if (this.dialog.open) this.load();
                return;
            }
            this.revision = data.revision;
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
        }
    }

    async remove() {
        if (this.saving || !this.revision) return;
        this.saving = true;
        this.ui('confirm-delete').disabled = true;
        this.stopPreview();
        this.clock.stop();
        try {
            await this.request('DELETE', { revision: this.revision });
            this.clock.setVoiceBuffers(null);
            if (this.soundInput.value === 'my-voice') this.useSound('click');
            this.remember('click');
            this.saved = this.buffers = [];
            this.revision = null;
            this.option.disabled = true;
            this.cancel();
        } catch (error) { this.failure(error); }
        finally { this.saving = false; this.ui('confirm-delete').disabled = false; }
    }

    failure(error) {
        this.ui('status').textContent = error.message;
        this.ui('reload').hidden = ![409, 401, 419].includes(error.status);
        this.invalidateSession(error);
    }

    invalidateSession(error) {
        if (![401, 419].includes(error.status)) return;
        this.flow++;
        this.capture.cancel();
        this.stopPreview();
        this.clock.stop();
        this.clock.setVoiceBuffers(null);
        this.option.disabled = true;
        this.ready = false;
        this.buffers = this.saved = [];
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
