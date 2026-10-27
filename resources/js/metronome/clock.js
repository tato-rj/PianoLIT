import { clampTempo, DEFAULT_TEMPO } from './gesture';
import { SOUND_PROFILES } from './settings';

export class MetronomeClock {
    constructor({ createContext, onStateChange, onBeat, onError, onReady = () => {} }) {
        this.createContext = createContext;
        this.onStateChange = onStateChange;
        this.onBeat = onBeat;
        this.onError = onError;
        this.onReady = onReady;
        this.tempo = DEFAULT_TEMPO;
        this.running = false;
        this.ready = false;
        this.generation = 0;
        this.context = null;
        this.audioSession = null;
        this.timer = null;
        this.notes = new Set();
        this.beats = [];
        this.beatAnchor = null;
        this.beatSpan = 1;
        this.beatsPerBar = 1;
        this.sound = 'click';
        this.voiceBuffers = null;
        this.nextBeatIndex = 0;
        this.lastBeatIndex = null;
    }

    async start({ leadInBeats = 0 } = {}) {
        if (this.running) return;
        const generation = ++this.generation;
        this.running = true;
        this.onStateChange(true);

        try {
            this.requestPlaybackAudio();
            if (!this.context || this.context.state === 'closed') {
                this.context = this.createContext();
                this.context.onstatechange = () => {
                    if (this.ready && this.context.state !== 'running') this.stop();
                };
            }
            await this.context.resume();
            // A second tap can stop playback while the browser unlocks audio.
            if (!this.running || generation !== this.generation) return;
            if (this.context.state !== 'running') throw new Error('Audio is unavailable.');

            this.ready = true;
            this.beatSpan = leadInBeats || 1;
            this.beatAnchor = leadInBeats ? this.context.currentTime : null;
            this.nextBeatIndex = 0;
            this.lastBeatIndex = null;
            this.nextBeatTime = this.context.currentTime + (leadInBeats ? leadInBeats * 60 / this.tempo : 0.02);
            this.schedule();
            this.timer = setInterval(() => this.schedule(), 25);
            this.onReady();
        } catch (error) {
            if (generation !== this.generation) return;
            this.stop();
            this.onError(error);
        }
    }

    stop() {
        this.generation++;
        this.running = false;
        this.ready = false;
        clearInterval(this.timer);
        this.timer = null;
        for (const note of this.notes) this.cancelNote(note);
        this.beats = [];
        this.beatAnchor = null;
        this.lastBeatIndex = null;
        this.restoreAudioSession();
        this.onStateChange(false);
    }

    requestPlaybackAudio() {
        try {
            const session = typeof navigator === 'undefined' ? null : navigator.audioSession;
            if (!session) return;
            const previousType = session.type;
            // iPhone Web Audio defaults to ambient, which obeys Silent Mode.
            session.type = 'playback';
            this.audioSession = { session, previousType };
        } catch (error) {
            // This optional browser API must never prevent normal playback.
        }
    }

    restoreAudioSession() {
        const audioSession = this.audioSession;
        this.audioSession = null;
        if (!audioSession) return;
        try {
            const { session, previousType } = audioSession;
            if (session.type === 'playback') session.type = previousType;
        } catch (error) { /* The browser may no longer allow session changes. */ }
    }

    setTempo(value) {
        const tempo = Math.round(clampTempo(value));
        if (tempo === this.tempo) return;
        const previousTempo = this.tempo;
        this.tempo = tempo;
        if (!this.ready) return;

        const now = this.context.currentTime;
        this.updateBeat(now);
        if (this.beatAnchor === null && this.beats.length) this.nextBeatTime = this.beats[0].time;
        for (const note of this.notes) {
            if (note.time > now) this.cancelNote(note);
        }
        this.beats = [];
        this.nextBeatIndex = this.lastBeatIndex === null ? 0 : (this.lastBeatIndex + 1) % this.beatsPerBar;

        // Preserve the progress through this beat when tempo changes mid-swipe.
        if (this.beatAnchor !== null) {
            const progress = Math.min(1, Math.max(0, (now - this.beatAnchor) / (this.beatSpan * 60 / previousTempo)));
            const interval = this.beatSpan * 60 / tempo;
            this.beatAnchor = now - progress * interval;
            this.nextBeatTime = now + (1 - progress) * interval;
        }
        this.schedule();
    }

    setBeats(value) {
        if (![1, 2, 3, 4].includes(value) || value === this.beatsPerBar) return;
        this.beatsPerBar = value;
        this.reschedulePending(true);
    }

    setSound(value) {
        if (!(value === 'my-voice' && this.voiceBuffers) && !Object.prototype.hasOwnProperty.call(SOUND_PROFILES, value)) return;
        if (value === this.sound) return;
        this.sound = value;
        this.reschedulePending();
    }

    setVoiceBuffers(buffers) {
        this.voiceBuffers = buffers?.length === 4 ? buffers : null;
        if (!this.voiceBuffers && this.sound === 'my-voice') this.setSound('click');
        else if (this.sound === 'my-voice') this.reschedulePending();
    }

    reschedulePending(restartBar = false) {
        if (!this.ready) return;
        const now = this.context.currentTime;
        this.updateBeat(now);
        if (this.beats.length) this.nextBeatTime = this.beats[0].time;
        for (const note of this.notes) {
            if (note.time > now) this.cancelNote(note);
        }
        this.beats = [];
        this.nextBeatIndex = restartBar || this.lastBeatIndex === null
            ? 0 : (this.lastBeatIndex + 1) % this.beatsPerBar;
        if (restartBar) this.lastBeatIndex = null;
        this.schedule();
    }

    getBeatPosition() {
        const now = this.ready ? this.context.currentTime : 0;
        let beat = this.lastBeatIndex;
        let anchor = this.beatAnchor;
        let span = this.beatSpan;
        // A click can occur between scheduler ticks. Render from its actual audio
        // time immediately instead of holding the dot at the end of the stroke.
        for (const queued of this.beats) {
            if (queued.time > now) break;
            beat = queued.index;
            anchor = queued.time;
            span = 1;
        }
        return {
            beat: beat ?? this.beatsPerBar - 1,
            preparing: beat === null,
            progress: this.ready && anchor !== null
                ? Math.min(1, Math.max(0, (now - anchor) / (span * 60 / this.tempo)))
                : 0,
        };
    }

    updateBeat(now) {
        let played = false;
        while (this.beats.length && this.beats[0].time <= now) {
            const beat = this.beats.shift();
            this.beatAnchor = beat.time;
            this.beatSpan = 1;
            this.lastBeatIndex = beat.index;
            played = true;
        }
        if (played) this.onBeat(this.lastBeatIndex);
    }

    schedule() {
        if (!this.ready) return;
        const now = this.context.currentTime;
        this.updateBeat(now);

        // Schedule against the audio clock, so rendering cannot move the clicks.
        // If a tab was stalled, resume once instead of replaying missed beats.
        if (this.nextBeatTime < now) this.nextBeatTime = now + 0.01;
        while (this.nextBeatTime < now + 0.1) {
            this.playClick(this.nextBeatTime, this.nextBeatIndex);
            this.beats.push({ time: this.nextBeatTime, index: this.nextBeatIndex });
            this.nextBeatIndex = (this.nextBeatIndex + 1) % this.beatsPerBar;
            this.nextBeatTime += 60 / this.tempo;
        }
    }

    playClick(time, beatIndex) {
        if (this.sound === 'my-voice' && this.voiceBuffers) return this.playVoice(time, beatIndex);
        const profile = SOUND_PROFILES[this.sound];
        const pitch = beatIndex === 0 ? 1 : 0.82;
        const gain = this.context.createGain();
        // Percussive sounds decay immediately after a crisp attack. Only the
        // electronic beep holds its tone; high peaks retain the louder count
        // without stretching the ticks or clipping the combined voices.
        const peak = profile.volume / profile.ratios.length;
        gain.gain.setValueAtTime(0, time);
        gain.gain.linearRampToValueAtTime(peak, time + profile.attack);
        if (profile.hold) gain.gain.setValueAtTime(peak, time + profile.attack + profile.hold);
        gain.gain.exponentialRampToValueAtTime(0.001, time + profile.duration - 0.002);
        gain.gain.linearRampToValueAtTime(0, time + profile.duration);
        gain.connect(this.context.destination);

        const oscillators = profile.ratios.map(ratio => {
            const oscillator = this.context.createOscillator();
            oscillator.type = profile.type;
            const frequency = profile.frequency * pitch * ratio;
            oscillator.frequency.setValueAtTime(frequency, time);
            oscillator.connect(gain);
            return oscillator;
        });
        const note = { oscillators, gain, time };
        this.notes.add(note);
        let remaining = oscillators.length;
        oscillators.forEach(oscillator => {
            oscillator.onended = () => {
                oscillator.disconnect();
                if (--remaining === 0) {
                    gain.disconnect();
                    this.notes.delete(note);
                }
            };
            oscillator.start(time);
            oscillator.stop(time + profile.duration);
        });
    }

    playVoice(time, beatIndex) {
        const buffer = this.voiceBuffers[beatIndex];
        const duration = Math.min(buffer.duration, 60 / this.tempo * 0.94);
        // Finish an already playing syllable before the next beat after a tempo
        // change; a stale long clip must never overlap the new count.
        for (const note of this.notes) {
            if (note.voiceEnd > time) note.oscillators.forEach(source => source.stop(time));
        }
        const source = this.context.createBufferSource();
        source.buffer = buffer;
        source.playbackRate.setValueAtTime(buffer.duration / duration, time);
        const gain = this.context.createGain();
        gain.gain.setValueAtTime(0, time);
        gain.gain.linearRampToValueAtTime(1, time + 0.003);
        gain.gain.setValueAtTime(1, time + Math.max(0.003, duration - 0.005));
        gain.gain.linearRampToValueAtTime(0, time + duration);
        source.connect(gain);
        gain.connect(this.context.destination);
        const note = { oscillators: [source], gain, time, voiceEnd: time + duration };
        this.notes.add(note);
        source.onended = () => { source.disconnect(); gain.disconnect(); this.notes.delete(note); };
        source.start(time);
        source.stop(time + duration);
    }

    cancelNote(note) {
        note.oscillators.forEach(oscillator => {
            oscillator.stop();
            oscillator.disconnect();
        });
        note.gain.disconnect();
        this.notes.delete(note);
    }
}
