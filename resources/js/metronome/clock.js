import { clampTempo, DEFAULT_TEMPO } from './gesture';
import { SOUND_PROFILES } from './settings';
import { compressVoice } from './voice-stretch';

export class MetronomeClock {
    constructor({ createContext, onStateChange, onBeat, onError, onReady = () => {}, onTick = () => {} }) {
        this.createContext = createContext;
        this.onStateChange = onStateChange;
        this.onBeat = onBeat;
        this.onError = onError;
        this.onReady = onReady;
        this.onTick = onTick;
        this.tempo = DEFAULT_TEMPO;
        this.running = false;
        this.ready = false;
        this.generation = 0;
        this.context = null;
        this.audioSession = null;
        this.timer = null;
        this.notes = new Set();
        this.beats = [];
        this.presentation = [];
        this.presentedBeat = null;
        this.beatAnchor = null;
        this.beatSpan = 1;
        this.beatsPerBar = 1;
        this.sound = 'click';
        this.voiceBuffers = null;
        this.spokenBuffers = null;
        this.effectBuffers = null;
        this.preparedVoices = null;
        this.preparedVoiceTempo = null;
        this.nextBeatIndex = 0;
        this.lastBeatIndex = null;
    }

    async start({ leadInBeats = 0 } = {}) {
        if (this.running) return;
        const generation = ++this.generation;
        this.running = true;
        this.onStateChange(true);

        try {
            await this.resumeAudio();
            // A second tap can stop playback while the browser unlocks audio.
            if (!this.running || generation !== this.generation) {
                if (!this.running) this.suspendAudio();
                return;
            }
            if (this.context.state !== 'running') throw new Error('Audio is unavailable.');

            this.prepareVoices();
            this.ready = true;
            this.beatSpan = leadInBeats || 1;
            this.beatAnchor = leadInBeats ? this.context.currentTime : null;
            this.nextBeatIndex = 0;
            this.lastBeatIndex = null;
            this.presentation = [{ at: this.context.currentTime, anchor: this.beatAnchor,
                index: null, beat: null, span: this.beatSpan, tempo: this.tempo }];
            this.presentedBeat = null;
            this.nextBeatTime = this.context.currentTime + (leadInBeats ? leadInBeats * 60 / this.tempo : 0.02);
            this.schedule(false);
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
        this.presentation = [];
        this.presentedBeat = null;
        this.beatAnchor = null;
        this.lastBeatIndex = null;
        this.suspendAudio();
        this.onStateChange(false);
    }

    getContext() {
        if (!this.context || this.context.state === 'closed') {
            const context = this.context = this.createContext();
            context.onstatechange = () => {
                if (this.ready && this.context === context && context.state !== 'running') this.stop();
            };
        }
        return this.context;
    }

    resumeAudio() {
        this.requestPlaybackAudio();
        return this.getContext().resume();
    }

    suspendAudio() {
        if (this.context?.state === 'running') this.context.suspend?.().catch(() => {});
    }

    async releaseAudio() {
        this.stop();
        const generation = this.generation;
        const context = this.context;
        this.context = null;
        if (context) context.onstatechange = null;
        if (context && context.state !== 'closed') await context.close().catch(() => {});
        if (generation === this.generation && !this.context) this.restoreAudioSession();
    }

    requestPlaybackAudio() {
        try {
            const session = typeof navigator === 'undefined' ? null : navigator.audioSession;
            if (!session) return;
            if (this.audioSession?.session === session && session.type === 'playback') return;
            const previousType = this.audioSession?.previousType ?? session.type;
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

        this.prepareVoices();
        const now = this.context.currentTime;
        this.updateBeat(now);
        if (this.beatAnchor === null && this.beats.length) this.nextBeatTime = this.beats[0].time;
        for (const note of this.notes) {
            if (note.time > now) this.cancelNote(note);
        }
        this.beats = [];
        this.presentation = this.presentation.filter(beat => beat.at <= now);
        this.nextBeatIndex = this.lastBeatIndex === null ? 0 : (this.lastBeatIndex + 1) % this.beatsPerBar;

        // Preserve the progress through this beat when tempo changes mid-swipe.
        if (this.beatAnchor !== null) {
            const progress = Math.min(1, Math.max(0, (now - this.beatAnchor) / (this.beatSpan * 60 / previousTempo)));
            const interval = this.beatSpan * 60 / tempo;
            this.beatAnchor = now - progress * interval;
            this.nextBeatTime = now + (1 - progress) * interval;
        }
        const previous = this.presentation[this.presentation.length - 1];
        this.presentation.push({ at: now, anchor: this.beatAnchor, span: this.beatSpan,
            index: this.lastBeatIndex, beat: previous?.beat ?? null, tempo });
        this.schedule();
    }

    setBeats(value) {
        if (![1, 2, 3, 4].includes(value) || value === this.beatsPerBar) return;
        this.beatsPerBar = value;
        this.reschedulePending(true);
    }

    setSound(value) {
        if (!(value === 'my-voice' && this.voiceBuffers) && !(value === 'spoken' && this.spokenBuffers) && !Object.prototype.hasOwnProperty.call(SOUND_PROFILES, value)) return;
        if (value === this.sound) return;
        this.sound = value;
        this.effectBuffers = null;
        this.reschedulePending();
    }

    setVoiceBuffers(buffers) {
        if (buffers !== this.voiceBuffers && this.sound === 'my-voice') {
            this.preparedVoices = null;
            this.effectBuffers = null;
        }
        this.voiceBuffers = buffers?.length === 4 ? buffers : null;
        if (!this.voiceBuffers && this.sound === 'my-voice') this.setSound('click');
        else if (this.sound === 'my-voice') this.reschedulePending();
    }

    setSpokenBuffers(buffers) {
        if (buffers === this.spokenBuffers) return;
        if (this.sound === 'spoken') this.effectBuffers = null;
        this.spokenBuffers = buffers?.length === 4 ? buffers : null;
        if (!this.spokenBuffers && this.sound === 'spoken') this.setSound('click');
        else if (this.sound === 'spoken') this.reschedulePending();
    }

    setVoiceEffectBuffers(buffers) {
        if (buffers === this.effectBuffers) return;
        this.effectBuffers = buffers?.length === 4 ? buffers : null;
        this.reschedulePending();
    }

    voiceSamples() {
        if (['spoken', 'my-voice'].includes(this.sound) && this.effectBuffers) return this.effectBuffers;
        return this.sound === 'spoken' ? this.spokenBuffers : this.sound === 'my-voice' ? this.voiceBuffers : null;
    }

    reschedulePending(restartBar = false) {
        if (!this.ready) return;
        this.prepareVoices();
        const now = this.context.currentTime;
        this.updateBeat(now);
        if (this.beats.length) this.nextBeatTime = this.beats[0].time;
        for (const note of this.notes) {
            if (note.time > now) this.cancelNote(note);
        }
        this.beats = [];
        this.presentation = this.presentation.filter(beat => beat.at <= now);
        this.nextBeatIndex = restartBar || this.lastBeatIndex === null
            ? 0 : (this.lastBeatIndex + 1) % this.beatsPerBar;
        if (restartBar) this.lastBeatIndex = null;
        this.schedule();
    }

    getBeatPosition() {
        const now = this.ready ? this.outputTime() : 0;
        const state = this.presentationAt(now);
        const beat = state?.index ?? null;
        const anchor = state?.anchor ?? null;
        const tempo = state?.tempo ?? this.tempo;
        return {
            beat: beat ?? this.beatsPerBar - 1,
            preparing: beat === null,
            waiting: this.ready && (!state || now < state.at),
            tempo,
            progress: this.ready && anchor !== null
                ? Math.min(1, Math.max(0, (now - anchor) / (state.span * 60 / tempo)))
                : 0,
        };
    }

    outputTime() {
        const context = this.context;
        // currentTime is the rendering head, ahead of the sound at the speaker.
        // Map the device's timestamp to now, including after a route change.
        try {
            const stamp = context.getOutputTimestamp?.();
            if (stamp && stamp.performanceTime > 0 && Number.isFinite(stamp.contextTime) && stamp.contextTime >= 0) {
                const age = (performance.now() - stamp.performanceTime) / 1000;
                if (age >= 0 && age < 1) return Math.min(context.currentTime, stamp.contextTime + age);
            }
        } catch (error) { /* Older browsers may not expose an output timestamp. */ }
        const latency = context.outputLatency ?? context.baseLatency ?? 0;
        return context.currentTime - (Number.isFinite(latency) && latency > 0 ? latency : 0);
    }

    presentationAt(now) {
        let state = null;
        for (const entry of this.presentation) {
            if (entry.at > now) break;
            state = entry;
        }
        return state;
    }

    updatePresentation(notify = true) {
        const now = this.outputTime();
        while (this.presentation.length > 1 && this.presentation[1].at <= now) this.presentation.shift();
        const state = this.presentationAt(now);
        if (state?.beat && state.beat !== this.presentedBeat) {
            this.presentedBeat = state.beat;
            this.onBeat(state.index);
        }
        if (notify) this.onTick();
    }

    updateBeat(now) {
        while (this.beats.length && this.beats[0].time <= now) {
            const beat = this.beats.shift();
            this.beatAnchor = beat.time;
            this.beatSpan = 1;
            this.lastBeatIndex = beat.index;
        }
    }

    schedule(notify = true) {
        if (!this.ready) return;
        const now = this.context.currentTime;
        this.updateBeat(now);

        // Schedule against the audio clock, so rendering cannot move the clicks.
        // If a tab was stalled, resume once instead of replaying missed beats.
        if (this.nextBeatTime < now) this.nextBeatTime = now + 0.01;
        while (this.nextBeatTime < now + 0.1) {
            this.playClick(this.nextBeatTime, this.nextBeatIndex);
            this.beats.push({ time: this.nextBeatTime, index: this.nextBeatIndex });
            this.presentation.push({ at: this.nextBeatTime, anchor: this.nextBeatTime,
                index: this.nextBeatIndex, beat: {}, span: 1, tempo: this.tempo });
            this.nextBeatIndex = (this.nextBeatIndex + 1) % this.beatsPerBar;
            this.nextBeatTime += 60 / this.tempo;
        }
        this.updatePresentation(notify);
    }

    playClick(time, beatIndex) {
        if (this.voiceSamples()) return this.playVoice(time, beatIndex);
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

    prepareVoices() {
        const buffers = this.voiceSamples();
        if (!buffers) return;
        if (this.preparedVoices && this.preparedVoiceSource === buffers && this.preparedVoiceTempo === this.tempo) return;
        // Prepare once before starting/rescheduling, never inside the beat
        // scheduler. Keep only this tempo's four clips to bound memory usage.
        const duration = 60 / this.tempo * 0.94;
        this.preparedVoiceSource = buffers;
        this.preparedVoices = buffers.map(buffer => compressVoice(buffer, duration,
            (channels, length, rate) => this.context.createBuffer(channels, length, rate)));
        this.preparedVoiceTempo = this.tempo;
    }

    playVoice(time, beatIndex) {
        const buffer = this.preparedVoices[beatIndex];
        const duration = buffer.duration;
        // Finish an already playing syllable before the next beat after a tempo
        // change; a stale long clip must never overlap the new count.
        for (const note of this.notes) {
            if (note.voiceEnd > time) {
                note.gain.gain.cancelScheduledValues(Math.max(note.time, time - 0.005));
                note.gain.gain.setValueAtTime(1, Math.max(note.time + 0.003, time - 0.005));
                note.gain.gain.linearRampToValueAtTime(0, time);
                note.oscillators.forEach(source => source.stop(time));
                note.voiceEnd = time;
            }
        }
        const source = this.context.createBufferSource();
        source.buffer = buffer;
        source.playbackRate.setValueAtTime(1, time);
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
