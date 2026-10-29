import { applyVoiceEffect } from './voice-effects';

self.onmessage = event => {
    try {
        const { effect, clips } = event.data;
        if (!Array.isArray(clips) || clips.length !== 4) throw new Error('Four numbers required');
        const output = clips.map(({ samples, rate }) => {
            if (!(samples instanceof Float32Array) || rate < 8000 || rate > 96000 || samples.length / rate > 2) throw new Error('Invalid recording');
            const processed = applyVoiceEffect({ length: samples.length, duration: samples.length / rate,
                sampleRate: rate, numberOfChannels: 1, getChannelData: () => samples }, effect);
            return { samples: processed.getChannelData(0), rate };
        });
        self.postMessage({ clips: output }, output.map(clip => clip.samples.buffer));
    } catch (error) {
        self.postMessage({ error: 'Voice effect unavailable' });
    }
};
