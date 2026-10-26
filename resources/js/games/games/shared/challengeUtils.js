import { normalizeClef } from "../../staff/staffUtils.js";

export function normalizeClefPool(clefsOrSingle) {
  const raw = Array.isArray(clefsOrSingle)
    ? clefsOrSingle
    : clefsOrSingle != null
      ? [clefsOrSingle]
      : ["treble", "bass"];

  const normalized = raw.map((c) => normalizeClef(c)).filter(Boolean);
  const uniq = [];

  for (let i = 0; i < normalized.length; i += 1) {
    if (!uniq.includes(normalized[i])) uniq.push(normalized[i]);
  }

  return uniq.length ? uniq : ["treble", "bass"];
}

export function pickChallengeClef(clefPool, random = Math.random) {
  const pool = Array.isArray(clefPool) && clefPool.length ? clefPool : ["treble", "bass"];
  if (pool.length === 1) return pool[0];
  return pool[Math.floor(random() * pool.length)];
}

export function accidentalClassFromOffset(off) {
  if (off === 2) return "music-font__doublesharp";
  if (off === 1) return "music-font__sharp";
  if (off === 0) return "music-font__natural";
  if (off === -1) return "music-font__flat";
  if (off === -2) return "music-font__doubleflat";
  return null;
}

export function parseIntervalAbbr(abbr) {
  const s = String(abbr || "").trim();
  const m = s.match(/^([PMAmd]+)(\d+)$/);
  if (!m) return null;
  return { quality: m[1], number: parseInt(m[2], 10) };
}

export function parsePitch(pitch) {
  const s = String(pitch || "").trim();
  const m = s.match(/^([A-Ga-g])((?:#{1,2})|(?:b{1,2})|)?(\d+)$/);
  if (!m) return null;

  const letter = m[1].toUpperCase();
  const acc = m[2] || "";
  const octave = parseInt(m[3], 10);

  const baseSemitoneFromC = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 }[letter];
  const accOffset =
    acc === "𝄪" ? 2 :
    acc === "#" ? 1 :
    acc === "bb" ? -2 :
    acc === "b" ? -1 :
    0;

  const accidentalClass = accidentalClassFromOffset(accOffset);
  const midi = 12 * (octave + 1) + baseSemitoneFromC + accOffset;

  return { midi, accOffset, accidentalClass };
}

export function fixedNoteToStaffPosition(staff, noteStr) {
  const parsed = parsePitch(noteStr);
  if (!parsed) return null;

  const { midi, accOffset, accidentalClass } = parsed;

  for (let step = staff.minStepAllowed(); step <= staff.maxStepAllowed(); step += 1) {
    const baseMidi = staff._stepToMidi(step);
    if (baseMidi + accOffset === midi) return { step, accidentalClass };
  }

  let best = null;
  for (let step = staff.minStepAllowed(); step <= staff.maxStepAllowed(); step += 1) {
    const baseMidi = staff._stepToMidi(step);
    const dist = Math.abs(baseMidi + accOffset - midi);
    if (!best || dist < best.dist) best = { step, dist };
  }

  return best ? { step: best.step, accidentalClass } : null;
}
