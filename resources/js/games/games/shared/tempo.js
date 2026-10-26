export const MIN_METRONOME_BPM = 50;
export const MAX_METRONOME_BPM = 160;
export const DEFAULT_METRONOME_BPM = 80;

export function normalizeMetronomeBpm(value) {
  const bpm = Number(value);
  if (!Number.isFinite(bpm)) return DEFAULT_METRONOME_BPM;
  return Math.min(MAX_METRONOME_BPM, Math.max(MIN_METRONOME_BPM, bpm));
}

export function beatMsForBpm(value) {
  return 60000 / normalizeMetronomeBpm(value);
}

export function eighthNoteMsForBpm(value) {
  return beatMsForBpm(value) / 2;
}
