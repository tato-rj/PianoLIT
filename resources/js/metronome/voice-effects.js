import { stretchVoice } from './voice-stretch';

// All effects are local DSP, never speech synthesis or character impersonations.
import effects from '../../data/metronome-voice-effects.json';
export const VOICE_EFFECTS = effects;

export function sampleBuffer(channels, length, sampleRate) {
    const data = Array.from({ length: channels }, () => new Float32Array(length));
    return { length, sampleRate, numberOfChannels: channels, duration: length / sampleRate,
        getChannelData: channel => data[channel] };
}

// Pure, bounded processing, run in a worker once per choice/source, never per beat.
export function applyVoiceEffect(buffer, id) {
    const effect = VOICE_EFFECTS[id];
    if (!effect) throw new Error('Unknown voice effect');
    if (id === 'classic') return buffer;
    const rate = buffer.sampleRate;
    const input = buffer.getChannelData(0);
    const pitched = sampleBuffer(1, Math.round(input.length / effect.pitch), rate);
    const resampled = pitched.getChannelData(0);
    for (let i = 0; i < resampled.length; i++) {
        const at = i * effect.pitch, left = Math.floor(at), mix = at - left;
        resampled[i] = (input[left] || 0) * (1 - mix) + (input[left + 1] || 0) * mix;
    }
    // Restore articulation length after pitch shifting; do not just speed up playback.
    const result = stretchVoice(pitched, buffer.duration / (effect.tempo || 1), sampleBuffer);
    const output = result.getChannelData(0);
    let low = 0, high = 0, previous = 0;
    const lowAlpha = effect.lowpass ? 1 - Math.exp(-2 * Math.PI * effect.lowpass / rate) : 1;
    const highAlpha = effect.highpass ? Math.exp(-2 * Math.PI * effect.highpass / rate) : 0;
    for (let i = 0; i < output.length; i++) {
        let value = output[i];
        if (effect.highpass) { high = highAlpha * (high + value - previous); previous = value; value = high; }
        low += lowAlpha * (value - low);
        value = low;
        if (effect.brightness) value += effect.brightness * (value - (output[i - 1] || 0));
        if (effect.robot) value *= 0.3 + 0.7 * Math.abs(Math.sin(2 * Math.PI * 45 * i / rate));
        output[i] = value;
    }
    if (effect.echo) {
        const dry = output.slice();
        for (const [milliseconds, scale] of [[28, 1], [57, 0.45]]) {
            const delay = Math.round(rate * milliseconds / 1000);
            for (let i = delay; i < output.length; i++) output[i] += dry[i - delay] * effect.echo * scale;
        }
    }
    let sourceEnergy = 0, energy = 0, peak = 0;
    for (const value of input) sourceEnergy += value * value;
    for (const value of output) { energy += value * value; peak = Math.max(peak, Math.abs(value)); }
    const gain = Math.min(Math.sqrt(sourceEnergy / input.length / Math.max(1e-12, energy / output.length)), 0.85 / Math.max(1e-6, peak));
    const fade = Math.round(rate * 0.005);
    for (let i = 0; i < output.length; i++) {
        output[i] *= gain * Math.min(1, i / fade, (output.length - 1 - i) / fade);
    }
    return result;
}
