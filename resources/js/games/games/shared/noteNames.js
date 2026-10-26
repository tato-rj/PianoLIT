export const NATURAL_NOTE_ORDER = ["C", "D", "E", "F", "G", "A", "B"];

export const NATURAL_PITCH_CLASS = {
  C: 0,
  D: 2,
  E: 4,
  F: 5,
  G: 7,
  A: 9,
  B: 11,
};

export const PITCH_CLASS_TO_NOTE = ["C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"];

// Keep ASCII spellings for pitch calculations and input values; use these only
// when showing a note name to a player.
export function displayAccidental(offset) {
  return { 2: "𝄪", 1: "♯", [-1]: "♭", [-2]: "𝄫" }[offset] || "";
}

export function displayNoteName(noteName) {
  const raw = String(noteName ?? "");
  return raw.replace(/^((?:Do|Re|Mi|Fa|Sol|La|Si|[A-G]))(##|bb|#|b)(?=$|-?\d+$)/i,
    (_, base, accidental) => `${base}${{ "##": "𝄪", bb: "𝄫", "#": "♯", b: "♭" }[accidental]}`);
}

export function pitchClassFromMidi(midi) {
  if (!Number.isFinite(midi)) return null;
  return ((midi % 12) + 12) % 12;
}

export function octaveFromMidi(midi) {
  if (!Number.isFinite(midi)) return null;
  return Math.floor(midi / 12) - 1;
}

export function noteNameFromMidi(midi) {
  const pitchClass = pitchClassFromMidi(midi);
  const octave = octaveFromMidi(midi);
  if (!Number.isInteger(pitchClass) || !Number.isFinite(octave)) return "";

  return `${PITCH_CLASS_TO_NOTE[pitchClass]}${octave}`;
}

export function naturalNoteNameFromMidi(midi) {
  const pitchClass = pitchClassFromMidi(midi);
  const octave = octaveFromMidi(midi);
  if (!Number.isInteger(pitchClass) || !Number.isFinite(octave)) return "";

  const letter = Object.keys(NATURAL_PITCH_CLASS)
    .find((key) => NATURAL_PITCH_CLASS[key] === pitchClass);
  return letter ? `${letter}${octave}` : "";
}

export function naturalMidiFromLetterOctave(letter, octave) {
  const pitchClass = NATURAL_PITCH_CLASS[String(letter || "").toUpperCase()];
  if (!Number.isInteger(pitchClass) || !Number.isFinite(octave)) return null;

  return ((octave + 1) * 12) + pitchClass;
}

export function naturalMidiFromNoteName(noteName) {
  const match = String(noteName || "").trim().match(/^([A-G])(-?\d+)$/);
  if (!match) return null;

  return naturalMidiFromLetterOctave(match[1], Number(match[2]));
}
