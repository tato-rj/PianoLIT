// Compress a short count with waveform-similar overlap/add. Copying samples at
// their original spacing preserves pitch; only the distance between grains
// changes. Work on a new buffer so saved recordings and previews stay intact.
export function compressVoice(buffer, maxDuration, createBuffer) {
    if (buffer.duration <= maxDuration) return buffer;
    const rate = buffer.sampleRate;
    const length = Math.max(1, Math.floor(maxDuration * rate));
    const frame = Math.min(Math.round(rate * 0.04), Math.floor(length / 2));
    const hop = Math.max(1, Math.floor(frame / 2));
    const radius = Math.round(rate * 0.012);
    const lastInput = buffer.length - frame;
    const lastOutput = length - frame;
    const steps = Math.ceil(lastOutput / hop);
    const result = createBuffer(buffer.numberOfChannels, length, rate);
    const inputs = Array.from({ length: buffer.numberOfChannels }, (_, channel) => buffer.getChannelData(channel));
    const outputs = inputs.map((_, channel) => result.getChannelData(channel));
    outputs.forEach((output, channel) => output.set(inputs[channel].subarray(0, frame)));
    let previousInput = 0;
    let previousOutput = 0;

    for (let step = 1; step <= steps; step++) {
        const at = Math.round(step * lastOutput / steps);
        const overlap = previousOutput + frame - at;
        const expected = Math.round(at * lastInput / lastOutput);
        // Pin the first and last grains to retain the word's onset and ending.
        const offset = at === lastOutput ? lastInput : matchingOffset(
            inputs[0], outputs[0], at, overlap, expected,
            Math.max(previousInput + 1, expected - radius), Math.min(lastInput, expected + radius), radius
        );
        outputs.forEach((output, channel) => {
            const input = inputs[channel];
            for (let i = 0; i < overlap; i++) {
                const mix = i / overlap;
                output[at + i] = output[at + i] * (1 - mix) + input[offset + i] * mix;
            }
            output.set(input.subarray(offset + overlap, offset + frame), at + overlap);
        });
        previousInput = offset;
        previousOutput = at;
    }
    return result;
}

function matchingOffset(input, output, at, overlap, expected, first, last, radius) {
    let energy = 0;
    for (let i = 0; i < overlap; i += 4) energy += output[at + i] ** 2;
    if (energy < 1e-9) return expected;

    const score = (offset, stride) => {
        let dot = 0, left = 0, right = 0;
        for (let i = 0; i < overlap; i += stride) {
            const a = output[at + i], b = input[offset + i];
            dot += a * b;
            left += a * a;
            right += b * b;
        }
        // Prefer a nearby match when several periods of a vowel are alike.
        return dot / Math.sqrt(left * right + 1e-20) - Math.abs(offset - expected) / radius * 0.005;
    };
    let best = Math.max(first, Math.min(last, expected));
    let bestScore = score(best, 4);
    for (let offset = first; offset <= last; offset += 4) {
        const value = score(offset, 4);
        if (value > bestScore) { best = offset; bestScore = value; }
    }
    const coarse = best;
    bestScore = score(best, 1);
    for (let offset = Math.max(first, coarse - 3); offset <= Math.min(last, coarse + 3); offset++) {
        const value = score(offset, 1);
        if (value > bestScore) { best = offset; bestScore = value; }
    }
    return best;
}
