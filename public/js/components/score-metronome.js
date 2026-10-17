(function (root) {
    'use strict';

    class ScoreMetronome {
        constructor(container) {
            this.root = container;
            this.button = container.querySelector('.score-metronome-button');
            this.panel = container.querySelector('.score-metronome-panel');
            this.slider = container.querySelector('[data-metronome-tempo]');
            this.value = container.querySelector('[data-metronome-value]');
            this.error = container.querySelector('[data-metronome-error]');
            this.bpm = 60;
            this.context = null;
            this.timer = null;
            this.ticks = new Set();
            this.button.addEventListener('click', () => this.panel.hidden ? this.open() : this.close());
            container.querySelector('[data-metronome-close]').addEventListener('click', () => this.close(true));
            this.slider.addEventListener('input', () => this.setTempo(this.slider.value));
            this.panel.addEventListener('keydown', event => {
                if (event.key !== 'Escape') return;
                event.preventDefault();
                event.stopPropagation();
                this.close(true);
            });
            document.addEventListener('hidden.bs.tab', () => {
                if (!this.root.getClientRects().length) this.close();
            });
            document.addEventListener('visibilitychange', () => { if (document.hidden) this.close(); });
            root.addEventListener('pagehide', () => this.close());
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
                if (context.state === 'suspended') await context.resume();
                // Closing/reopening while resume is pending must not start a stale loop.
                if (this.context !== context) return;
                this.nextBeat = context.currentTime + 0.01;
                this.lastBeat = this.nextBeat - 60 / this.bpm;
                this.schedule();
                if (this.context === context) this.timer = root.setInterval(() => this.schedule(), 25);
            } catch (error) {
                if (context && this.context !== context) return;
                this.close();
                this.error.textContent = AudioContext ? 'Could not start the metronome. Please try again.' : 'Metronome playback is not supported in this browser.';
                this.error.hidden = false;
            }
        }
        setTempo(value) {
            const number = Number(value);
            if (!Number.isFinite(number)) return;
            const previousInterval = 60 / this.bpm;
            this.bpm = Math.max(20, Math.min(200, Math.round(number)));
            this.slider.value = String(this.bpm);
            this.value.textContent = String(this.bpm);
            this.slider.setAttribute('aria-valuetext', this.bpm + ' beats per minute');
            if (this.timer === null) return;
            const now = this.context.currentTime;
            this.ticks.forEach(tick => { if (tick.time > now) this.cancelTick(tick); });
            if (this.lastBeat > now) this.lastBeat -= previousInterval;
            this.nextBeat = Math.max(now + 0.01, this.lastBeat + 60 / this.bpm);
            this.schedule();
        }
        schedule() {
            if (!this.context) return;
            if (!this.root.isConnected || !this.root.getClientRects().length || document.hidden) {
                this.close();
                return;
            }
            const context = this.context;
            // Schedule against the audio clock so timer jitter does not move the beat.
            this.nextBeat = Math.max(this.nextBeat, context.currentTime);
            while (this.nextBeat < context.currentTime + 0.1) {
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                const time = this.nextBeat;
                oscillator.frequency.setValueAtTime(1100, time);
                gain.gain.setValueAtTime(0, time);
                gain.gain.linearRampToValueAtTime(0.5, time + 0.002);
                gain.gain.exponentialRampToValueAtTime(0.001, time + 0.035);
                oscillator.connect(gain);
                gain.connect(context.destination);
                const tick = {oscillator, gain, time};
                this.ticks.add(tick);
                oscillator.onended = () => {
                    oscillator.disconnect(); gain.disconnect(); this.ticks.delete(tick);
                };
                oscillator.start(time);
                oscillator.stop(time + 0.04);
                this.lastBeat = time;
                this.nextBeat += 60 / this.bpm;
            }
        }
        cancelTick(tick) {
            tick.oscillator.stop();
            tick.oscillator.disconnect();
            tick.gain.disconnect();
            this.ticks.delete(tick);
        }
        close(focusButton = false) {
            this.panel.hidden = true;
            this.button.setAttribute('aria-expanded', 'false');
            root.clearInterval(this.timer);
            this.timer = null;
            this.ticks.forEach(tick => this.cancelTick(tick));
            const context = this.context;
            this.context = null;
            if (context && context.state !== 'closed') context.close().catch(function () {});
            if (focusButton) this.button.focus();
        }
    }

    root.ScoreMetronome = ScoreMetronome;
    document.querySelectorAll('.score-editor').forEach(container => {
        if (container.querySelector('.score-metronome-button')) new ScoreMetronome(container);
    });
})(window);
