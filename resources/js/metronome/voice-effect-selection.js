// Keep only the original and the current four processed samples in memory.
// Effect identity deliberately never enters settings, storage, or server writes.
export class VoiceEffectSelection {
    constructor({ process, useBuffers, onChange = () => {}, onError = () => {} }) {
        Object.assign(this, { process, useBuffers, onChange, onError });
        this.effect = 'classic';
        this.source = null;
        this.buffers = null;
        this.pending = null;
        this.generation = 0;
    }

    setSource(source) {
        if (source === this.source) return;
        // A result from another recording is never a valid fallback.
        this.useBuffers(null);
        this.source = source;
        this.pending = this.render();
    }

    select(effect) {
        if (this.effect !== effect) {
            this.effect = effect;
            this.pending = this.render();
        }
        // Re-clicking the selected effect reuses its result, including an
        // in-flight job, so a preview never regenerates the same four clips.
        return this.pending || Promise.resolve(this.buffers);
    }

    async render() {
        const generation = ++this.generation;
        this.job?.cancel();
        this.job = null;
        this.buffers = null;
        const processing = !!this.source && this.effect !== 'classic';
        this.onChange(this.effect, processing, !!this.source);
        if (!processing) {
            this.useBuffers(null);
            return this.buffers = this.source;
        }
        try {
            this.job = this.process(this.source, this.effect);
            const result = await this.job.promise;
            if (generation !== this.generation) return;
            this.useBuffers(result);
            this.onChange(this.effect, false, true);
            this.job = null;
            return this.buffers = result;
        } catch (error) {
            if (generation !== this.generation) return;
            this.useBuffers(null);
            this.effect = 'classic';
            this.onChange(this.effect, false, true);
            this.onError(error);
            this.job = null;
            return this.buffers = this.source;
        }
    }

    dispose() {
        this.generation++;
        this.job?.cancel();
        this.job = null;
        this.pending = this.buffers = null;
    }
}

export function processVoiceInWorker(buffers, effect, workerUrl, createBuffer) {
    const worker = new Worker(workerUrl);
    let timer;
    let rejectJob;
    const cancel = () => {
        clearTimeout(timer);
        worker.terminate();
        rejectJob?.(new Error('Canceled'));
    };
    const promise = new Promise((resolve, reject) => {
        rejectJob = reject;
        const finish = () => { clearTimeout(timer); worker.terminate(); };
        worker.onmessage = ({ data }) => {
            finish();
            if (data.error) { reject(new Error(data.error)); return; }
            try {
                resolve(data.clips.map(({ samples, rate }) => {
                    const buffer = createBuffer(1, samples.length, rate);
                    buffer.getChannelData(0).set(samples);
                    return buffer;
                }));
            } catch (error) { reject(error); }
        };
        worker.onerror = () => { finish(); reject(new Error('Voice effect unavailable')); };
        timer = setTimeout(() => { finish(); reject(new Error('Voice effect timed out')); }, 10000);
        const clips = buffers.map(buffer => ({ samples: buffer.getChannelData(0).slice(), rate: buffer.sampleRate }));
        worker.postMessage({ effect, clips }, clips.map(clip => clip.samples.buffer));
    });
    return { promise, cancel };
}
