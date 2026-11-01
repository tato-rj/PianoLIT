// Private recordings stay untouched. The playback copy removes unequal quiet
// lead-ins so every word's attack, including Two -> One, meets the beat grid.
export function alignVoiceOnset(buffer, createBuffer) {
    const samples = buffer.getChannelData(0);
    let peak = 0;
    for (const sample of samples) peak = Math.max(peak, Math.abs(sample));
    if (peak < 0.0001) return buffer;
    const threshold = Math.max(0.0001, peak * 0.005);
    let first = 0;
    while (first < samples.length && Math.abs(samples[first]) < threshold) first++;
    // Give *every* clip the same short attack lead, including words that start
    // immediately. Merely trimming long prefixes leaves a small alternating gap.
    const start = first - Math.round(buffer.sampleRate * 0.003);
    if (!start) return buffer;
    const result = createBuffer(buffer.numberOfChannels, buffer.length - start, buffer.sampleRate);
    for (let channel = 0; channel < buffer.numberOfChannels; channel++) {
        result.getChannelData(channel).set(buffer.getChannelData(channel).subarray(Math.max(0, start)), Math.max(0, -start));
    }
    return result;
}
