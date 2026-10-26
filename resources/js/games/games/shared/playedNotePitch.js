export function createStablePitchState() {
  return { midi: null, frequency: null, count: 0, samples: [], settled: false };
}

export const PLAYED_NOTE_STABLE_DURATION_MS = 700;
export const DEFAULT_MIC_SENSITIVITY = 65;
const MIN_PITCH_HZ = 40;
const MAX_PITCH_HZ = 4300;
const PITCH_REFERENCE_HZ = 261.6256; // Middle C
const MIN_PITCH_LEVEL_SCALE = 0.02;
const MAX_PITCH_LEVEL_SCALE = 3;
const MIN_STABLE_SAMPLES = 5;
const MAX_SAMPLE_GAP_MS = 250;
const MAX_PITCH_SPREAD_CENTS = 70;
const MAX_PITCH_DRIFT_CENTS = 25;

export function frequencyToMidi(frequency) {
  return Math.round(69 + (12 * Math.log2(frequency / 440)));
}

export function updateStablePitchState(stablePitch, frequency, timestamp = performance.now(), settleMs = PLAYED_NOTE_STABLE_DURATION_MS) {
  if (!Number.isFinite(frequency) || frequency <= 0 || !Number.isFinite(timestamp)) {
    return createStablePitchState();
  }

  const current = stablePitch || createStablePitchState();
  const stableDuration = Number.isFinite(settleMs) ? Math.max(300, Math.min(2500, settleMs)) : PLAYED_NOTE_STABLE_DURATION_MS;
  const previousSamples = current.samples || [];
  const lastSample = previousSamples[previousSamples.length - 1];
  if (lastSample?.timestamp === timestamp) return current;
  const gap = lastSample ? timestamp - lastSample.timestamp : Infinity;
  const samples = gap > 0 && gap <= MAX_SAMPLE_GAP_MS ? [...previousSamples] : [];
  samples.push({ cents: 1200 * Math.log2(frequency / 440), timestamp });

  // Keep one sample at the start of the time window, independent of frame rate.
  while (samples.length > MIN_STABLE_SAMPLES && timestamp - samples[1].timestamp >= stableDuration) {
    samples.shift();
  }
  // A changed note or a wide glide starts a fresh settling window.
  while (samples.length > 1) {
    const pitches = samples.map(sample => sample.cents);
    if (Math.max(...pitches) - Math.min(...pitches) <= MAX_PITCH_SPREAD_CENTS) break;
    samples.shift();
  }

  const sortedPitches = samples.map(sample => sample.cents).sort((a, b) => a - b);
  const middle = Math.floor(sortedPitches.length / 2);
  const medianCents = sortedPitches.length % 2
    ? sortedPitches[middle]
    : (sortedPitches[middle - 1] + sortedPitches[middle]) / 2;
  const settledFrequency = 440 * (2 ** (medianCents / 1200));
  const duration = timestamp - samples[0].timestamp;
  let settled = duration >= stableDuration && samples.length >= MIN_STABLE_SAMPLES;

  if (settled) {
    // A narrow range alone can still be a slow slide. Measure its overall drift
    // while allowing small oscillations (such as vibrato) around a steady note.
    const meanTime = samples.reduce((sum, sample) => sum + sample.timestamp - samples[0].timestamp, 0) / samples.length;
    const meanPitch = samples.reduce((sum, sample) => sum + sample.cents, 0) / samples.length;
    let covariance = 0;
    let timeVariance = 0;
    for (const sample of samples) {
      const timeOffset = sample.timestamp - samples[0].timestamp - meanTime;
      covariance += timeOffset * (sample.cents - meanPitch);
      timeVariance += timeOffset ** 2;
    }
    const drift = timeVariance > 0 ? (covariance / timeVariance) * duration : Infinity;
    settled = Math.abs(drift) <= MAX_PITCH_DRIFT_CENTS;
  }

  return {
    midi: frequencyToMidi(settledFrequency),
    frequency: settledFrequency,
    count: samples.length,
    samples,
    settled,
  };
}

export function inputLevel(buffer) {
  let rms = 0;
  let peak = 0;

  for (let i = 0; i < buffer.length; i += 1) {
    const sample = Math.abs(buffer[i]);
    rms += buffer[i] * buffer[i];
    if (sample > peak) peak = sample;
  }

  rms = Math.sqrt(rms / buffer.length);
  return { rms, peak };
}

export function microphoneThresholds(sensitivity = DEFAULT_MIC_SENSITIVITY) {
  const level = Number.isFinite(sensitivity) ? Math.max(0, Math.min(100, sensitivity)) : DEFAULT_MIC_SENSITIVITY;
  // The low end deliberately requires a loud, nearby source. Keep the default
  // sensitive enough for a piano on a stand, and reserve the high end for soft notes.
  const thresholdScale = level <= DEFAULT_MIC_SENSITIVITY
    ? 50 ** ((DEFAULT_MIC_SENSITIVITY - level) / DEFAULT_MIC_SENSITIVITY)
    : 8 ** ((DEFAULT_MIC_SENSITIVITY - level) / (100 - DEFAULT_MIC_SENSITIVITY));
  return {
    minRms: 0.0008 * thresholdScale,
    minPeak: 0.0018 * thresholdScale,
    minConfidence: 0.18 + (0.52 * Math.max(0, (DEFAULT_MIC_SENSITIVITY - level) / DEFAULT_MIC_SENSITIVITY)),
  };
}

export function pitchLevelScale(frequency) {
  return Math.max(MIN_PITCH_LEVEL_SCALE, Math.min(MAX_PITCH_LEVEL_SCALE, (PITCH_REFERENCE_HZ / frequency) ** 2));
}

export function detectPlayedNotePitch(buffer, sampleRate, { sensitivity = DEFAULT_MIC_SENSITIVITY } = {}) {
  const { minRms, minPeak, minConfidence } = microphoneThresholds(sensitivity);
  const trimThreshold = minPeak * MIN_PITCH_LEVEL_SCALE * 0.75;
  const { rms, peak } = inputLevel(buffer);
  const levelRatio = Math.min(rms / minRms, peak / minPeak);
  if (levelRatio < MIN_PITCH_LEVEL_SCALE) return null;

  // Quiet input can only qualify if it is high pitched. Limit the lag search
  // accordingly so room noise does not require a full autocorrelation scan.
  const lowestEligibleFrequency = levelRatio >= MAX_PITCH_LEVEL_SCALE
    ? MIN_PITCH_HZ
    : Math.max(MIN_PITCH_HZ, PITCH_REFERENCE_HZ / Math.sqrt(levelRatio));

  let start = 0;
  let end = buffer.length - 1;

  for (let i = 0; i < buffer.length / 2; i += 1) {
    if (Math.abs(buffer[i]) < trimThreshold) {
      start = i;
      break;
    }
  }

  for (let i = 1; i < buffer.length / 2; i += 1) {
    if (Math.abs(buffer[buffer.length - i]) < trimThreshold) {
      end = buffer.length - i;
      break;
    }
  }

  const trimmed = buffer.slice(start, end);
  const trimmedSize = trimmed.length;
  if (trimmedSize < 32) return null;

  const minLag = Math.max(1, Math.floor(sampleRate / MAX_PITCH_HZ));
  const maxLag = Math.min(trimmedSize - 1, Math.ceil(sampleRate / lowestEligibleFrequency));
  if (maxLag < minLag) return null;
  const correlations = new Float64Array(maxLag + 2);
  const energy = new Float64Array(trimmedSize + 1);
  for (let i = 0; i < trimmedSize; i += 1) {
    energy[i + 1] = energy[i] + (trimmed[i] * trimmed[i]);
  }
  if (energy[trimmedSize] <= 0) return null;

  // Normalize for overlap length, otherwise the shortest lag can win simply
  // because it contains more samples than the actual note period.
  for (let lag = Math.max(1, minLag - 1); lag <= Math.min(trimmedSize - 1, maxLag + 1); lag += 1) {
    let correlation = 0;
    for (let i = 0; i < trimmedSize - lag; i += 1) {
      correlation += trimmed[i] * trimmed[i + lag];
    }
    const leftEnergy = energy[trimmedSize - lag];
    const rightEnergy = energy[trimmedSize] - energy[lag];
    correlations[lag] = leftEnergy > 0 && rightEnergy > 0
      ? correlation / Math.sqrt(leftEnergy * rightEnergy)
      : 0;
  }

  const peaks = [];
  for (let lag = minLag; lag <= maxLag; lag += 1) {
    if (correlations[lag] > correlations[lag - 1] && correlations[lag] >= correlations[lag + 1]) {
      peaks.push(lag);
    }
  }
  if (!peaks.length) return null;
  const strongest = Math.max(...peaks.map(lag => correlations[lag]));
  if (strongest < minConfidence) return null;
  const maxPosition = peaks.find(lag => correlations[lag] >= strongest * 0.92);
  const confidence = correlations[maxPosition];

  const x1 = correlations[maxPosition - 1] || 0;
  const x2 = correlations[maxPosition] || 0;
  const x3 = correlations[maxPosition + 1] || 0;
  const divisor = (2 * x2) - x1 - x3;
  const shift = divisor ? (x3 - x1) / (2 * divisor) : 0;
  const frequency = sampleRate / (maxPosition + shift);

  if (!Number.isFinite(frequency) || frequency < MIN_PITCH_HZ || frequency > MAX_PITCH_HZ) return null;
  if (levelRatio < pitchLevelScale(frequency)) return null;

  return { frequency };
}
