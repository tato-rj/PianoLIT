export class SpokenVoiceLibrary {
    constructor({ voices, fetchAudio, decode }) {
        this.voices = new Map(voices.map(voice => [voice.id, voice]));
        this.fetchAudio = fetchAudio;
        this.decode = decode;
        this.cache = new Map();
    }

    load(id) {
        const voice = this.voices.get(id);
        if (!voice || voice.files?.length !== 4) return Promise.reject(new Error('Voice unavailable'));
        if (!this.cache.has(id)) {
            const pending = Promise.all(voice.files.map(async url => {
                // Public assets are same-host and fingerprinted; never fetch a submitted URL.
                if (!/^\/audio\/metronome\/spoken\/[a-z0-9-]+\.wav$/.test(url)) throw new Error('Invalid voice asset');
                const bytes = await this.fetchAudio(url);
                if (bytes.byteLength < 44 || bytes.byteLength > 58000) throw new Error('Invalid voice size');
                const buffer = await this.decode(bytes);
                if (buffer.numberOfChannels !== 1 || buffer.duration < 0.06 || buffer.duration > 1.2) throw new Error('Invalid voice sample');
                return buffer;
            })).catch(error => { this.cache.delete(id); throw error; });
            this.cache.set(id, pending);
        }
        return this.cache.get(id);
    }
}

// Selection has its own generation: a slow load must never replace a newer choice.
export class SpokenCounting {
    constructor({ library, onUse, onLoading = () => {}, onError = () => {} }) {
        Object.assign(this, { library, onUse, onLoading, onError });
        this.generation = 0;
    }

    cancel() {
        this.generation++;
        this.onLoading(false);
    }

    async select(id) {
        const generation = ++this.generation;
        this.onLoading(true);
        try {
            const buffers = await this.library.load(id);
            if (generation !== this.generation) return;
            this.onUse(id, buffers);
        } catch (error) {
            if (generation === this.generation) this.onError(id, error);
        } finally {
            if (generation === this.generation) this.onLoading(false);
        }
    }
}

export async function fetchSpokenAudio(url) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        const response = await fetch(url, { credentials: 'omit', signal: controller.signal });
        if (!response.ok) throw new Error('Voice download failed');
        if (Number(response.headers.get('Content-Length')) > 58000) throw new Error('Voice file too large');
        return await response.arrayBuffer();
    } finally {
        clearTimeout(timeout);
    }
}
