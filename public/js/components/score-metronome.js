(function (root) {
    'use strict';
    const sounds = ['woodblock', 'click', 'claves', 'hi-hat', 'voice', 'female-voice'];
    const isVoice = sound => sound === 'voice' || sound === 'female-voice';

    class ScoreMetronome {
        constructor(container) {
            this.root = container;
            this.button = container.querySelector('.score-metronome-button');
            this.panel = container.querySelector('.score-metronome-panel');
            this.slider = container.querySelector('[data-metronome-tempo]');
            this.value = container.querySelector('[data-metronome-value]');
            this.error = container.querySelector('[data-metronome-error]');
            this.pauseButton = container.querySelector('[data-metronome-pause]');
            this.settings = document.getElementById(container.querySelector('[data-metronome-settings]').getAttribute('aria-controls'));
            this.soundSelect = this.settings.querySelector('[data-metronome-sound]');
            this.settingsStatus = this.settings.querySelector('[data-metronome-settings-status]');
            this.saveStatus = this.settings.querySelector('[data-metronome-save-status]');
            this.saveRetry = this.settings.querySelector('[data-metronome-save-retry]');
            this.beatButtons = this.settings.querySelectorAll('[data-metronome-beats]');
            this.bpm = 60;
            this.beatCount = 1;
            this.sound = 'woodblock';
            this.soundRequest = 0;
            this.voiceBytes = {};
            this.voiceBuffers = {};
            this.preferencesUrl = container.getAttribute('data-metronome-preferences-url') || null;
            this.pendingPreferences = {};
            this.preferenceTimer = null;
            this.preferenceRequest = null;
            this.context = null;
            this.timer = null;
            this.paused = false;
            this.ticks = new Set();
            this.button.addEventListener('click', () => this.panel.hidden ? this.open() : this.close());
            container.querySelector('[data-metronome-close]').addEventListener('click', () => this.close(true));
            this.pauseButton.addEventListener('click', () => this.setPaused(!this.paused));
            this.slider.addEventListener('input', () => this.setTempo(this.slider.value));
            this.beatButtons.forEach(button => button.addEventListener('click', () => this.setBeatCount(Number(button.getAttribute('data-metronome-beats')))));
            this.soundSelect.addEventListener('change', () => this.setSound(this.soundSelect.value));
            this.saveRetry.addEventListener('click', () => this.flushPreferences());
            this.settings.addEventListener('hide.bs.modal', () => this.flushPreferences());
            this.panel.addEventListener('keydown', event => {
                if (event.key !== 'Escape') return;
                event.preventDefault(); event.stopPropagation(); this.close(true);
            });
            document.addEventListener('hidden.bs.tab', () => {
                if (!this.root.getClientRects().length) this.close();
            });
            document.addEventListener('visibilitychange', () => { if (document.hidden) this.close(); });
            root.addEventListener('pagehide', () => this.close());
            let preferences;
            try { preferences = JSON.parse(container.getAttribute('data-metronome-preferences') || '{}'); } catch (error) { preferences = {}; }
            preferences = preferences && typeof preferences === 'object' ? preferences : {};
            this.setTempo(Number.isInteger(preferences.tempo) && preferences.tempo >= 20 && preferences.tempo <= 200 ? preferences.tempo : 60, false);
            this.setBeatCount(Number.isInteger(preferences.beats) && preferences.beats >= 1 && preferences.beats <= 4 ? preferences.beats : 1, false);
            this.preferredSound = sounds.includes(preferences.sound) ? preferences.sound : 'woodblock';
            this.soundSelect.value = this.preferredSound;
        }
        async open() {
            if (!this.panel.hidden) return;
            this.error.hidden = true;
            this.panel.hidden = false;
            this.button.setAttribute('aria-expanded', 'true');
            const AudioContext = root.AudioContext || root.webkitAudioContext;
            let context;
            try {
                if (!AudioContext) throw new Error('Web Audio is unavailable');
                // Create/resume directly from the click, including on mobile Safari.
                context = this.context = new AudioContext();
                this.percussion = {};
                if (context.state === 'suspended') await context.resume();
                // Closing/reopening while resume is pending must not start a stale loop.
                if (this.context !== context) return;
                await this.setSound(this.preferredSound, false);
                if (this.context !== context) return;
                this.nextBeat = context.currentTime + 0.01;
                this.lastBeat = this.nextBeat - 60 / this.bpm;
                this.nextBeatNumber = 0;
                this.schedule();
                if (this.context === context) this.timer = root.setInterval(() => this.schedule(), 25);
            } catch (error) {
                if (context && this.context !== context) return;
                this.close();
                this.error.textContent = AudioContext ? 'Could not start the metronome. Please try again.' : 'Metronome playback is not supported in this browser.';
                this.error.hidden = false;
            }
        }
        setTempo(value, persist = true) {
            const number = Number(value);
            if (!Number.isFinite(number)) return;
            const previousInterval = 60 / this.bpm;
            const previous = this.bpm;
            this.bpm = Math.max(20, Math.min(200, Math.round(number)));
            this.slider.value = String(this.bpm);
            this.value.textContent = String(this.bpm);
            this.slider.setAttribute('aria-valuetext', this.bpm + ' beats per minute');
            this.reschedule(false, previousInterval);
            if (persist && this.bpm !== previous) this.queuePreferences({tempo: this.bpm});
        }
        updatePauseButton() {
            const label = this.paused ? 'Resume metronome' : 'Pause metronome';
            this.pauseButton.setAttribute('aria-label', label);
            this.pauseButton.setAttribute('title', label);
            this.pauseButton.setAttribute('aria-pressed', String(this.paused));
            this.pauseButton.querySelector('[data-metronome-pause-icon]').hidden = this.paused;
            this.pauseButton.querySelector('[data-metronome-play-icon]').hidden = !this.paused;
        }
        setPaused(paused) {
            if (this.panel.hidden || this.paused === paused) return;
            this.paused = paused;
            this.updatePauseButton();
            if (paused) {
                this.ticks.forEach(tick => {
                    if (tick.time > this.context.currentTime) this.nextBeatNumber = tick.beat;
                    this.cancelTick(tick);
                });
            } else if (this.context && this.context.state === 'running') {
                // Continue with the next number, without catching up missed beats.
                this.nextBeat = this.context.currentTime + 0.01;
                this.lastBeat = this.nextBeat - 60 / this.bpm;
                this.schedule();
            }
        }
        setBeatCount(count, persist = true) {
            if (!Number.isInteger(count) || count < 1 || count > 4) return;
            const previous = this.beatCount;
            this.beatCount = count;
            this.beatButtons.forEach(button => {
                const selected = Number(button.getAttribute('data-metronome-beats')) === count;
                button.setAttribute('aria-pressed', String(selected));
                button.classList.toggle('active', selected);
            });
            this.reschedule(true);
            if (persist && count !== previous) this.queuePreferences({beats: count});
        }
        settingsMessage(message, error = false) {
            this.settingsStatus.textContent = message;
            this.settingsStatus.hidden = !message;
            this.settingsStatus.classList.toggle('text-danger', error);
            this.settingsStatus.classList.toggle('text-muted', !error);
        }
        async setSound(sound, persist = true) {
            if (!sounds.includes(sound)) { this.soundSelect.value = this.sound; return; }
            const request = ++this.soundRequest, context = this.context;
            this.settingsMessage('');
            try {
                if (isVoice(sound) && !this.voiceBuffers[sound]) {
                    this.soundSelect.disabled = true;
                    this.settingsMessage('Loading ' + (sound === 'female-voice' ? 'female' : 'male') + ' counting…');
                    if (!this.voiceBytes[sound]) {
                        const attribute = sound === 'female-voice' ? 'data-female-count-url-' : 'data-count-url-';
                        this.voiceBytes[sound] = Promise.all([1, 2, 3, 4].map(async number => {
                            const response = await root.fetch(this.soundSelect.getAttribute(attribute + number));
                            if (!response.ok) throw new Error('Counting sample unavailable');
                            return response.arrayBuffer();
                        })).catch(error => { delete this.voiceBytes[sound]; throw error; });
                    }
                    const bytes = await this.voiceBytes[sound];
                    if (request !== this.soundRequest || context !== this.context) return;
                    const buffers = await Promise.all(bytes.map(data => context.decodeAudioData(data.slice(0))));
                    if (request !== this.soundRequest || context !== this.context) return;
                    this.voiceBuffers[sound] = buffers;
                }
                if (request !== this.soundRequest) return;
                this.sound = sound;
                const previous = this.preferredSound;
                this.preferredSound = sound;
                this.soundSelect.value = sound;
                this.settingsMessage('');
                this.reschedule(true);
                if (persist && sound !== previous) this.queuePreferences({sound});
            } catch (error) {
                if (request === this.soundRequest) this.settingsMessage('Could not load human counting. Choose it again to retry.', true);
            } finally {
                if (request === this.soundRequest) {
                    this.soundSelect.disabled = false;
                    this.soundSelect.value = this.sound;
                }
            }
        }
        queuePreferences(changes) {
            // Guests keep only this page's in-memory choices; never use browser storage.
            if (!this.preferencesUrl) return;
            Object.assign(this.pendingPreferences, changes);
            if (this.preferenceTimer !== null) root.clearTimeout(this.preferenceTimer);
            this.preferenceTimer = root.setTimeout(() => this.flushPreferences(), 300);
        }
        flushPreferences() {
            if (this.preferenceTimer !== null) root.clearTimeout(this.preferenceTimer);
            this.preferenceTimer = null;
            if (this.preferenceRequest) return this.preferenceRequest;
            if (!this.preferencesUrl || !Object.keys(this.pendingPreferences).length) return Promise.resolve();
            const changes = this.pendingPreferences, url = this.preferencesUrl;
            this.pendingPreferences = {};
            this.saveRetry.disabled = true;
            let succeeded = false;
            // Serialize writes and never apply an older acknowledgement over newer controls.
            this.preferenceRequest = Promise.resolve().then(() => root.fetch(url, {
                method: 'PATCH', credentials: 'same-origin', keepalive: true, cache: 'no-store',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': root.app && root.app.csrfToken || ''},
                body: JSON.stringify({preferences: changes})
            })).then(async response => {
                if ([401, 403, 419].includes(response.status)) {
                    this.preferencesUrl = null;
                    this.pendingPreferences = {};
                    throw new Error('The web session changed');
                }
                if (!response.ok || !(await response.json()).preferences) throw new Error('Preferences were not saved');
                succeeded = true;
                this.saveStatus.hidden = this.saveRetry.hidden = true;
            }).catch(() => {
                if (this.preferencesUrl) this.pendingPreferences = Object.assign({}, changes, this.pendingPreferences);
                this.saveStatus.textContent = this.preferencesUrl ? 'Could not save your settings. Your current choices still work.' : 'Refresh or sign in again to save your settings.';
                this.saveStatus.hidden = false;
                this.saveRetry.hidden = !this.preferencesUrl;
            }).finally(() => {
                this.preferenceRequest = null;
                this.saveRetry.disabled = false;
                if (succeeded && Object.keys(this.pendingPreferences).length) return this.flushPreferences();
            });
            return this.preferenceRequest;
        }
        reschedule(resetCount = false, previousInterval = 60 / this.bpm) {
            if (this.paused) { if (resetCount) this.nextBeatNumber = 0; return; }
            if (this.timer === null) return;
            const now = this.context.currentTime;
            this.ticks.forEach(tick => {
                if (tick.time <= now) return;
                this.nextBeatNumber = tick.beat;
                this.cancelTick(tick);
            });
            if (this.lastBeat > now) this.lastBeat -= previousInterval;
            this.nextBeat = Math.max(now + 0.01, this.lastBeat + 60 / this.bpm);
            if (resetCount) this.nextBeatNumber = 0;
            this.schedule();
        }
        percussionBuffer(sound) {
            if (this.percussion[sound]) return this.percussion[sound];
            const context = this.context, duration = sound === 'hi-hat' ? 0.09 : 0.065;
            const buffer = context.createBuffer(1, Math.ceil(context.sampleRate * duration), context.sampleRate);
            const data = buffer.getChannelData(0);
            let seed = 37, previousNoise = 0, peak = 0;
            for (let i = 0; i < data.length; i++) {
                const t = i / context.sampleRate, sine = frequency => Math.sin(2 * Math.PI * frequency * t);
                let sample;
                if (sound === 'woodblock') sample = (sine(900) + 0.6 * sine(1370) + 0.25 * sine(2530)) * Math.exp(-t * 85);
                else if (sound === 'claves') sample = (sine(2400) + 0.4 * sine(3370)) * Math.exp(-t * 140);
                else if (sound === 'hi-hat') {
                    seed = (Math.imul(seed, 1664525) + 1013904223) >>> 0;
                    const noise = seed / 2147483648 - 1;
                    sample = (0.65 * (noise - previousNoise) + 0.2 * (Math.sign(sine(4200)) + Math.sign(sine(5870)))) * Math.exp(-t * 75);
                    previousNoise = noise;
                } else sample = sine(1100) * Math.exp(-t * 125);
                sample *= Math.min(1, t / 0.001) * Math.min(1, (duration - t) / 0.005);
                data[i] = sample; peak = Math.max(peak, Math.abs(sample));
            }
            for (let i = 0; i < data.length; i++) data[i] *= 0.65 / peak;
            return this.percussion[sound] = buffer;
        }
        schedule() {
            if (!this.context) return;
            if (!this.root.isConnected || !this.root.getClientRects().length || document.hidden) { this.close(); return; }
            if (this.paused) return;
            const context = this.context;
            // Schedule against the audio clock so timer jitter does not move the beat.
            this.nextBeat = Math.max(this.nextBeat, context.currentTime);
            while (this.nextBeat < context.currentTime + 0.1) {
                const source = context.createBufferSource(), gain = context.createGain();
                const time = this.nextBeat, beat = this.nextBeatNumber;
                source.buffer = isVoice(this.sound) ? this.voiceBuffers[this.sound][beat] : this.percussionBuffer(this.sound);
                // Keep spoken words clear and separate even at 200 BPM.
                source.playbackRate.setValueAtTime(isVoice(this.sound) ? Math.max(1, source.buffer.duration / (60 / this.bpm - 0.02)) : (beat === 0 ? 1 : 0.75), time);
                gain.gain.setValueAtTime(0.8, time);
                source.connect(gain); gain.connect(context.destination);
                const tick = {source, gain, time, beat};
                this.ticks.add(tick);
                source.onended = () => { source.disconnect(); gain.disconnect(); this.ticks.delete(tick); };
                source.start(time);
                this.lastBeat = time;
                this.nextBeat += 60 / this.bpm;
                this.nextBeatNumber = (beat + 1) % this.beatCount;
            }
        }
        cancelTick(tick) {
            tick.source.stop(); tick.source.disconnect(); tick.gain.disconnect(); this.ticks.delete(tick);
        }
        close(focusButton = false) {
            this.panel.hidden = true;
            this.button.setAttribute('aria-expanded', 'false');
            root.clearInterval(this.timer);
            this.timer = null;
            this.paused = false;
            this.updatePauseButton();
            this.ticks.forEach(tick => this.cancelTick(tick));
            const context = this.context;
            this.context = null;
            if (context && context.state !== 'closed') context.close().catch(function () {});
            ++this.soundRequest;
            this.soundSelect.disabled = false;
            this.soundSelect.value = this.preferredSound;
            this.settingsMessage('');
            this.flushPreferences();
            if (focusButton) this.button.focus();
        }
    }

    root.ScoreMetronome = ScoreMetronome;
    document.querySelectorAll('.score-editor').forEach(container => {
        if (container.querySelector('.score-metronome-button')) new ScoreMetronome(container);
    });
})(window);
