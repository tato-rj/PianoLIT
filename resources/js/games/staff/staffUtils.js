// resources/js/music/staff/staffUtils.js

export const ACCIDENTAL_CLASSES = [
  "music-font__sharp",
  "music-font__doublesharp",
  "music-font__flat",
  "music-font__doubleflat",
  "music-font__natural",
];

export const CLEF_LAYOUT_VARS = {
  treble: {
    "--clef-width": "140px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "34px",
    "--clef-left-nudge": "28px",
  },
  bass: {
    "--clef-width": "76px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "15px",
    "--clef-left-nudge": "-12px",
  },
  alto: {
    "--clef-width": "88px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "27.5px",
    "--clef-left-nudge": "-10px",
  },
  tenor: {
    "--clef-width": "88px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "2.5px",
    "--clef-left-nudge": "-10px",
  },
};

export function pxFromCss(css, varName, fallback) {
  const v = css.getPropertyValue(varName);
  const n = parseFloat(v);
  return Number.isFinite(n) ? n : fallback;
}

export function getPointerPageXY(e) {
  const oe = e.originalEvent || e;
  if (oe.touches && oe.touches.length) return { x: oe.touches[0].pageX, y: oe.touches[0].pageY };
  if (oe.changedTouches && oe.changedTouches.length) return { x: oe.changedTouches[0].pageX, y: oe.changedTouches[0].pageY };
  return { x: oe.pageX, y: oe.pageY };
}

export function getPointerId(e) {
  const oe = e.originalEvent || e;
  return oe && oe.pointerId != null ? oe.pointerId : null;
}

export function randomInt(min, maxInclusive, random = Math.random) {
  return Math.floor(random() * (maxInclusive - min + 1)) + min;
}

export function pickOne(arr, random = Math.random) {
  if (!Array.isArray(arr) || !arr.length) return null;
  return arr[Math.floor(random() * arr.length)];
}

export function pickWeighted(items, random = Math.random) {
  const list = Array.isArray(items)
    ? items.filter((x) => x && Number.isFinite(x.weight) && x.weight > 0)
    : [];
  if (!list.length) return null;

  const total = list.reduce((sum, x) => sum + x.weight, 0);
  let r = random() * total;

  for (let i = 0; i < list.length; i++) {
    r -= list[i].weight;
    if (r <= 0) return list[i].value;
  }
  return list[list.length - 1].value;
}

export function toArrayMaybe(v) {
  if (v == null) return [];
  return Array.isArray(v) ? v : [v];
}

export function normalizeClef(clef) {
  if (clef == null) return null;
  const c = String(clef || "treble").toLowerCase();
  if (c === "bass") return "bass";
  if (c === "alto") return "alto";
  if (c === "tenor") return "tenor";
  return "treble";
}

export function toolTypeFromEl($el) {
  if ($el.hasClass("music-font__sharp")) return "sharp";
  if ($el.hasClass("music-font__flat")) return "flat";
  if ($el.hasClass("music-font__natural")) return "natural";
  return null;
}

export function nextAccidentalClass(currentCls, toolType) {
  if (toolType === "sharp") {
    if (currentCls === "music-font__doublesharp") return "music-font__doublesharp";
    if (currentCls === "music-font__sharp") return "music-font__doublesharp";
    return "music-font__sharp";
  }
  if (toolType === "flat") {
    if (currentCls === "music-font__doubleflat") return "music-font__doubleflat";
    if (currentCls === "music-font__flat") return "music-font__doubleflat";
    return "music-font__flat";
  }
  if (toolType === "natural") return "music-font__natural";
  return currentCls || null;
}

export function isMaxedDouble(currentCls, toolType) {
  return (
    (toolType === "sharp" && currentCls === "music-font__doublesharp") ||
    (toolType === "flat" && currentCls === "music-font__doubleflat")
  );
}

export function accidentalClassToText(cls) {
  if (!cls) return "";
  if (cls.includes("music-font__doublesharp")) return "<span class='doublesharp-symbol'>𝄪</span>";
  if (cls.includes("music-font__sharp")) return "<span class='sharp-symbol'>♯</span>";
  if (cls.includes("music-font__doubleflat")) return "<span class='doubleflat-symbol'>𝄫</span>";
  if (cls.includes("music-font__flat")) return "<span class='flat-symbol'>♭</span>";
  return "";
}

export function accidentalClassToSymbol(cls) {
  if (!cls) return "";
  if (cls.includes("music-font__doublesharp")) return "𝄪";
  if (cls.includes("music-font__sharp")) return "♯";
  if (cls.includes("music-font__doubleflat")) return "𝄫";
  if (cls.includes("music-font__flat")) return "♭";
  return "";
}

export function stepToLetterOctave(staff, step) {
  const letters = ["C", "D", "E", "F", "G", "A", "B"];

  let baseC;
  let baseIndex;
  switch (staff.getClef()) {
    case "bass":
      baseC = 36; baseIndex = 4; break;
    case "alto":
      baseC = 48; baseIndex = 3; break;
    case "tenor":
      baseC = 48; baseIndex = 1; break;
    case "treble":
    default:
      baseC = 60; baseIndex = 2; break;
  }

  const idx = baseIndex + step;
  const octaveShift = Math.floor(idx / 7);
  const letterIndex = ((idx % 7) + 7) % 7;

  const baseOctave = Math.floor(baseC / 12) - 1;
  const octave = baseOctave + octaveShift;

  return { letter: letters[letterIndex], octave };
}

export function spellNoteFromState(staff, step, accidentalClass) {
  const { letter, octave } = stepToLetterOctave(staff, step);
  const acc = accidentalClassToText(accidentalClass);
  return `${letter}${acc}${octave}`;
}

export function spellNoteTextFromState(staff, step, accidentalClass) {
  const { letter, octave } = stepToLetterOctave(staff, step);
  const acc = accidentalClassToSymbol(accidentalClass);
  return `${letter}${acc}${octave}`;
}
