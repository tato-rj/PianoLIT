import { stretchVoice } from './voice-stretch';

// All effects are local DSP, never speech synthesis or character impersonations.
import effects from '../../data/metronome-voice-effects.json';
export const VOICE_EFFECTS = effects;

export function sampleBuffer(channels, length, sampleRate) {
    const data = Array.from({ length: channels }, () => new Float32Array(length));
    return { length, sampleRate, numberOfChannels: channels, duration: length / sampleRate,
        getChannelData: channel => data[channel] };
}

function interpolate(samples, position) {
    const at = Math.max(0, Math.min(samples.length - 1, position));
    const left = Math.floor(at), mix = at - left;
    return samples[left] * (1 - mix) + (samples[left + 1] || 0) * mix;
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
    if (effect.vibrato) {
        const dry = output.slice();
        const speed = 2 * Math.PI * effect.vibrato.rate / rate;
        const depth = effect.vibrato.depth / speed;
        for (let i = 0; i < output.length; i++) {
            // Pin the word's edges while bending the pitch through its middle.
            const envelope = Math.sin(Math.PI * i / Math.max(1, output.length - 1));
            output[i] = interpolate(dry, i + depth * Math.sin(speed * i) * envelope);
        }
    }
    let low = 0, high = 0, previous = 0;
    const lowAlpha = effect.lowpass ? 1 - Math.exp(-2 * Math.PI * effect.lowpass / rate) : 1;
    const highAlpha = effect.highpass ? Math.exp(-2 * Math.PI * effect.highpass / rate) : 0;
    for (let i = 0; i < output.length; i++) {
        let value = output[i];
        if (effect.highpass) { high = highAlpha * (high + value - previous); previous = value; value = high; }
        low += lowAlpha * (value - low);
        value = low;
        if (effect.brightness) value += effect.brightness * (value - (output[i - 1] || 0));
        // Bipolar ring modulation creates metallic sidebands, rather than the
        // previous shallow volume pulse that left most of the natural voice.
        if (effect.robot) value *= 1 - effect.robot.mix + effect.robot.mix * Math.sin(2 * Math.PI * effect.robot.rate * i / rate);
        if (effect.tremolo) value *= 1 - effect.tremolo.depth * (0.5 + 0.5 * Math.sin(2 * Math.PI * effect.tremolo.rate * i / rate));
        output[i] = value;
    }
    if (effect.chorus) {
        const dry = output.slice(), { delay, depth, rate: speed, mix } = effect.chorus;
        for (let i = 0; i < output.length; i++) {
            const sweep = depth * Math.sin(2 * Math.PI * speed * i / rate);
            const first = i - rate * (delay + sweep) / 1000;
            const second = i - rate * (delay * 1.7 - sweep) / 1000;
            const voices = ((first >= 0 ? interpolate(dry, first) : 0) + (second >= 0 ? interpolate(dry, second) : 0)) / 2;
            output[i] = dry[i] * (1 - mix) + voices * mix;
        }
    }
    if (effect.echo) {
        const dry = output.slice();
        const delays = effect.echoDelays || [28, 57];
        for (const [milliseconds, scale] of [[delays[0], 1], [delays[1], 0.45]]) {
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
