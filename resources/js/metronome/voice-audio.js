export const VOICE_RATE = 24000;
const MIN_SPEECH_LEVEL = 0.001;

const rms = samples => Math.sqrt(samples.reduce((sum, value) => sum + value * value, 0) / samples.length);
const noiseFloor = (levels, fraction = 0.2) => levels.length ? [...levels].sort((a, b) => a - b)[Math.floor(levels.length * fraction)] : 0;

export function prepareVoiceClip(decoded) {
    const rate = decoded.sampleRate;
    const samples = new Float32Array(decoded.length);
    for (let channel = 0; channel < decoded.numberOfChannels; channel++) {
        const data = decoded.getChannelData(channel);
        for (let i = 0; i < samples.length; i++) samples[i] += data[i] / decoded.numberOfChannels;
    }
    const windowSize = Math.max(1, Math.round(rate * 0.01));
    const levels = [];
    for (let start = 0; start < samples.length; start += windowSize) {
        let sum = 0;
        const end = Math.min(samples.length, start + windowSize);
        for (let i = start; i < end; i++) sum += samples[i] ** 2;
        levels.push(Math.sqrt(sum / (end - start)));
    }
    const peakLevel = Math.max(0, ...levels);
    // Quiet microphones still count; steady room noise does not become a word.
    const threshold = Math.max(MIN_SPEECH_LEVEL, noiseFloor(levels, 0.1) * 2.5, peakLevel * 0.12);
    const first = levels.findIndex(level => level >= threshold);
    let last = levels.length - 1;
    while (last >= 0 && levels[last] < threshold) last--;
    if (first < 0 || (last - first + 1) * windowSize / rate < 0.06) {
        throw new Error('I couldn’t hear that number. Try again.');
    }
    const start = Math.max(0, first * windowSize - Math.round(rate * 0.02));
    const end = Math.min(samples.length, (last + 1) * windowSize + Math.round(rate * 0.04));
    if ((end - start) / rate > 2) throw new Error('A little shorter—say just the number.');
    const output = new Float32Array(Math.max(1440, Math.ceil((end - start) / rate * VOICE_RATE)));
    let peak = 0;
    for (let i = start; i < end; i++) peak = Math.max(peak, Math.abs(samples[i]));
    const gain = 0.85 / Math.max(peak, MIN_SPEECH_LEVEL);
    for (let i = 0; i < output.length; i++) {
        const at = start + i * rate / VOICE_RATE;
        const left = Math.floor(at);
        const fraction = at - left;
        const value = (samples[left] || 0) * (1 - fraction) + (samples[left + 1] || 0) * fraction;
        const fade = Math.min(1, i / 120, (output.length - 1 - i) / 120);
        output[i] = Math.max(-1, Math.min(1, value * gain)) * fade;
    }
    return encodeVoiceWav(output);
}

export function encodeVoiceWav(samples) {
    const bytes = new ArrayBuffer(44 + samples.length * 2);
    const view = new DataView(bytes);
    const text = (offset, value) => [...value].forEach((letter, i) => view.setUint8(offset + i, letter.charCodeAt(0)));
    text(0, 'RIFF'); view.setUint32(4, bytes.byteLength - 8, true); text(8, 'WAVE');
    text(12, 'fmt '); view.setUint32(16, 16, true); view.setUint16(20, 1, true);
    view.setUint16(22, 1, true); view.setUint32(24, VOICE_RATE, true); view.setUint32(28, VOICE_RATE * 2, true);
    view.setUint16(32, 2, true); view.setUint16(34, 16, true); text(36, 'data');
    view.setUint32(40, samples.length * 2, true);
    samples.forEach((value, i) => view.setInt16(44 + i * 2, Math.round(Math.max(-1, Math.min(1, value)) * 32767), true));
    return bytes;
}

export function voiceBase64(bytes) {
    let text = '';
    for (const byte of new Uint8Array(bytes)) text += String.fromCharCode(byte);
    return btoa(text);
}

export function voiceBytes(encoded) {
    return Uint8Array.from(atob(encoded), letter => letter.charCodeAt(0)).buffer;
}

export class VoiceCapture {
    constructor({ mediaDevices, Recorder, createContext, now = () => performance.now() }) {
        Object.assign(this, { mediaDevices, Recorder, createContext, now });
        this.generation = 0;
    }

    async open() {
        this.cancel();
        const generation = this.generation;
        this.context = this.createContext();
        await this.context.resume();
        if (generation !== this.generation) throw new DOMException('Canceled', 'AbortError');
        const stream = await this.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true, channelCount: 1 }, video: false });
        if (generation !== this.generation) {
            stream.getTracks().forEach(track => track.stop());
            throw new DOMException('Canceled', 'AbortError');
        }
        this.stream = stream;
        this.source = this.context.createMediaStreamSource(stream);
        this.analyser = this.context.createAnalyser();
        this.analyser.fftSize = 2048;
        this.source.connect(this.analyser);
        // Use the existing Get ready pause to learn this microphone's room level.
        this.noiseLevels = [];
        this.noiseFloor = 0;
        const samples = new Float32Array(this.analyser.fftSize);
        this.calibrationTimer = setInterval(() => {
            this.analyser.getFloatTimeDomainData(samples);
            this.noiseLevels.push(rms(samples));
        }, 50);
    }

    record(onLevel) {
        clearInterval(this.calibrationTimer);
        if (this.noiseLevels.length) this.noiseFloor = noiseFloor(this.noiseLevels);
        const speechThreshold = Math.max(MIN_SPEECH_LEVEL, this.noiseFloor * 2.5);
        const generation = this.generation;
        const context = this.context;
        const mimeType = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus']
            .find(type => this.Recorder.isTypeSupported(type));
        const recorder = new this.Recorder(this.stream, mimeType ? { mimeType } : {});
        this.recorder = recorder;
        const chunks = [];
        const started = this.now();
        let speech = 0;
        let lastSpeech = started;
        let timer;
        const samples = new Float32Array(this.analyser.fftSize);
        return new Promise((resolve, reject) => {
            const finish = () => { if (recorder.state !== 'inactive') recorder.stop(); };
            recorder.ondataavailable = event => { if (event.data.size) chunks.push(event.data); };
            recorder.onerror = () => { clearInterval(timer); reject(new Error('Recording stopped. Please try again.')); };
            recorder.onstop = async () => {
                clearInterval(timer);
                if (generation !== this.generation) return reject(new DOMException('Canceled', 'AbortError'));
                try {
                    const blob = new Blob(chunks, { type: recorder.mimeType });
                    const decoded = await context.decodeAudioData(await blob.arrayBuffer());
                    if (generation !== this.generation) throw new DOMException('Canceled', 'AbortError');
                    resolve(prepareVoiceClip(decoded));
                } catch (error) { reject(error); }
            };
            recorder.start();
            this.timer = timer = setInterval(() => {
                if (generation !== this.generation) return finish();
                this.analyser.getFloatTimeDomainData(samples);
                const level = rms(samples);
                onLevel(Math.min(100, Math.round(Math.sqrt(Math.max(0, level - this.noiseFloor)) * 500)));
                const now = this.now();
                if (level >= speechThreshold) { speech += 50; lastSpeech = now; }
                if ((speech >= 100 && now - lastSpeech >= 450 && now - started >= 600) || now - started >= 3500) finish();
            }, 50);
        });
    }

    cancel() {
        this.generation++;
        clearInterval(this.timer);
        clearInterval(this.calibrationTimer);
        if (this.recorder?.state === 'recording') this.recorder.stop();
        this.recorder = null;
        this.stream?.getTracks().forEach(track => track.stop());
        this.stream = null;
        this.source?.disconnect();
        this.analyser?.disconnect();
        this.source = this.analyser = null;
        if (this.context && this.context.state !== 'closed') this.context.close().catch(() => {});
        this.context = null;
    }
}
