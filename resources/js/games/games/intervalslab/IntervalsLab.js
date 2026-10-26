import { BaseStaffGame } from "../base/BaseStaffGame.js";
import {
  pickOne,
  pickWeighted,
  toArrayMaybe,
} from "../../staff/staffUtils.js";
import {
  accidentalClassFromOffset,
  fixedNoteToStaffPosition,
  normalizeClefPool,
  parseIntervalAbbr,
  pickChallengeClef,
} from "../shared/challengeUtils.js";

export class IntervalsLab extends BaseStaffGame {
  static INTERVAL_FULL_NAME_MAP = {
    m2: "minor 2nd",
    M2: "major 2nd",
    m3: "minor 3rd",
    M3: "major 3rd",
    P4: "perfect 4th",
    A4: "augmented 4th",
    d5: "diminished 5th",
    P5: "perfect 5th",
    m6: "minor 6th",
    M6: "major 6th",
    m7: "minor 7th",
    M7: "major 7th",
    P8: "perfect 8th",
  };

  constructor(options = {}) {
    const defaults = {
      staffEl: "#staff",
      basePoints: 1,
      firstTryBonus: 2,
      strictDirection: false,
      namespace: "intervalsLab",
    };

    const merged = {
      ...defaults,
      ...options,
      accidentalWeights: {
        ...(defaults.accidentalWeights || {}),
        ...(options.accidentalWeights || {}),
      },
      intervals: Array.isArray(options.intervals)
        ? options.intervals.slice()
        : Object.keys(IntervalsLab.INTERVAL_FULL_NAME_MAP),
    };

    const clefPool = normalizeClefPool(
      merged.clefs != null ? merged.clefs : merged.clef,
    );

    super({
      ...merged,
      initialClef: (clefPool && clefPool[0]) ? clefPool[0] : "treble",
    });

    this._clefPool = clefPool;

    this._currentIntervalAbbr = null;
    this._currentIntervalDirection = 1; // 1=up, -1=down
  }

  // ------------------------ interval UI ------------------------

  _deriveFullNameFromAbbr(abbr) {
    const m = String(abbr || "").trim().match(/^([PMAmd])(\d+)$/);
    if (!m) return abbr;

    const q = m[1];
    const n = m[2];

    const qWord =
      q === "m" ? "minor" :
      q === "M" ? "major" :
      q === "P" ? "perfect" :
      q === "A" ? "augmented" :
      q === "d" ? "diminished" :
      q;

    return `${qWord} ${n}`;
  }

  _fullNameForInterval(abbr) {
    const key = String(abbr || "").trim();
    return IntervalsLab.INTERVAL_FULL_NAME_MAP[key] || this._deriveFullNameFromAbbr(key);
  }

  _directionWord(direction) {
    return Number(direction) === -1 ? "down" : "up";
  }

  _setIntervalUI(intervalAbbr, direction = 1) {
    const abbr = String(intervalAbbr || "").trim();
    this._currentIntervalAbbr = abbr;
    this._currentIntervalDirection = Number(direction) === -1 ? -1 : 1;

    this.prompt.setShort(abbr);
    if (this._isStrictDirection()) this.prompt.showDirection(this._currentIntervalDirection);
    else this.prompt.hideDirection();

    const fullName = this._fullNameForInterval(abbr);
    this.prompt.setLong(
      this._isStrictDirection()
        ? `${fullName} ${this._directionWord(this._currentIntervalDirection)}`
        : fullName,
    );
  }

  _normalizeOnOff(v) {
    if (v === true) return true;
    if (v === false) return false;
    const s = String(v ?? "").trim().toLowerCase();
    return s === "on" || s === "true" || s === "1";
  }

  _isStrictDirection() {
    return this._normalizeOnOff(this.opts.strictDirection);
  }

  _pickIntervalDirection() {
    if (!this._isStrictDirection()) return 1;
    return (this._duelRandom?.() ?? Math.random()) < 0.5 ? -1 : 1;
  }

  _fixedToState(fixed) {
    if (!fixed || !Number.isFinite(fixed.step)) return null;
    const accidentalClass = fixed.accidentalClass || null;
    const accOff = this.staff._accidentalClassToOffset(accidentalClass);
    const midi = this.staff._stepToMidi(fixed.step) + (accOff || 0);
    return { step: fixed.step, accidentalClass, midi };
  }

  _computeTargetFromFixed(intervalAbbr, direction, fixedState) {
    if (!fixedState) return null;
    const parsed = parseIntervalAbbr(intervalAbbr);
    if (!parsed || !Number.isFinite(parsed.number) || parsed.number < 1) return null;

    const diatonicSteps = parsed.number - 1;
    const simpleNum = ((parsed.number - 1) % 7) + 1;
    const octaves = Math.floor((parsed.number - 1) / 7);
    const baseSemiSimple = this._intervalSemitones(parsed.quality, simpleNum);
    if (baseSemiSimple == null) return null;
    const semitones = baseSemiSimple + (12 * octaves);

    const dir = Number(direction) === -1 ? -1 : 1;
    const targetStep = fixedState.step + (dir * diatonicSteps);
    const minStep = this.staff.minStepAllowed();
    const maxStep = this.staff.maxStepAllowed();
    if (targetStep < minStep || targetStep > maxStep) return null;

    const targetMidi = fixedState.midi + (dir * semitones);
    const naturalTargetMidi = this.staff._stepToMidi(targetStep);
    const off = targetMidi - naturalTargetMidi;
    if (off < -2 || off > 2) return null;

    const accidentalClass = accidentalClassFromOffset(off);
    if (!accidentalClass) return null;

    return { step: targetStep, accidentalClass };
  }

  _isStrictChallengePlaceable(intervalAbbr, direction, fixed) {
    const fixedState = this._fixedToState(fixed);
    return !!this._computeTargetFromFixed(intervalAbbr, direction, fixedState);
  }

  _pickStrictPlaceableChallenge() {
    const maxAttempts = 240;
    for (let i = 0; i < maxAttempts; i += 1) {
      const interval = this._pickInterval();
      const direction = this._pickIntervalDirection();
      const fixed = this._pickFixedNote();
      if (this._isStrictChallengePlaceable(interval, direction, fixed)) {
        return { interval, direction, fixed };
      }
    }

    // deterministic fallback to guarantee playability
    const minStep = this.staff.minStepAllowed();
    const maxStep = this.staff.maxStepAllowed();
    const midStep = Math.floor((minStep + maxStep) / 2);
    const fixed = { step: midStep, accidentalClass: null };
    const interval = "m2";
    const direction = (midStep + 1 <= maxStep) ? 1 : -1;
    return { interval, direction, fixed };
  }

  // ------------------------ game flow ------------------------

  newChallenge() {
    this.$helpBtn.hide();
    this._fixedState = null;

    const clef = pickChallengeClef(this._clefPool, this._duelRandom);
    if (clef && clef !== this.staff.getClef()) this.staff.setClef(clef);

    this._madeMistakeThisRound = false;
    this._usedHintThisRound = false;
    this.$bonusBadge.hide();
    this.$doublePoints?.hide?.();

    const challenge = this._isStrictDirection()
      ? this._pickStrictPlaceableChallenge()
      : {
        interval: this._pickInterval(),
        direction: this._pickIntervalDirection(),
        fixed: this._pickFixedNote(),
      };
    const interval = challenge.interval;
    const direction = challenge.direction;
    const fixed = challenge.fixed;

    this.staff.clearNotes();
    this.$accidentals.removeClass("invisible");
    this.$feedback.hide();

    this.prompt.show();
    this._setIntervalUI(interval, direction);

    if (fixed) {
      const fixedId = this.staff.addFixedNote({
        step: fixed.step,
        accidentalClass: fixed.accidentalClass || null,
      });

      if (fixedId) this.staff._emitNoteState(fixedId, "fixed");
    }

    $("#check").show();
    $("#continue").hide();
  }

  _pickInterval() {
    const pool =
      Array.isArray(this.opts.intervals) && this.opts.intervals.length
        ? this.opts.intervals
        : ["M3"];
    return pool[Math.floor((this._duelRandom?.() ?? Math.random()) * pool.length)];
  }

  _pickFixedNote() {
    const fixedList = toArrayMaybe(this.opts.fixedNotes).filter(Boolean);
    if (fixedList.length) {
      const chosen = pickOne(fixedList, this._duelRandom);
      return fixedNoteToStaffPosition(this.staff, chosen);
    }

    const w = this.opts.accidentalWeights || {};
    const accidentalClass = pickWeighted([
      { value: null, weight: Number(w.natural) || 0 },
      { value: "music-font__sharp", weight: Number(w.sharp) || 0 },
      { value: "music-font__flat", weight: Number(w.flat) || 0 },
    ], this._duelRandom);

    return { step: this._randomFixedStep(), accidentalClass };
  }

  _randomFixedStep() {
    const min = 0;
    const max = 8;
    return Math.floor((this._duelRandom?.() ?? Math.random()) * (max - min + 1)) + min;
  }

  // ------------------------ evaluation ------------------------

  _notesOnStaffOrdered() {
    const $notes = this.$staffEl.find(".note").not(".preview").not(".hint");
    const notes = $notes.toArray().map((el) => {
      const id = el.getAttribute("data-note-id");
      const step = this.staff._stepOfNoteEl(el);
      const accCls = this.staff._getAttachedAccidentalClass(id);
      const accOff = this.staff._accidentalClassToOffset(accCls);
      return { id, step, accOff };
    });

    notes.sort((a, b) => a.step - b.step);
    return notes;
  }

  _qualityFor(simpleNum, semitones) {
    const isPerfectClass = (simpleNum === 1 || simpleNum === 4 || simpleNum === 5 || simpleNum === 8);
    const expectedMajorOrPerfect = { 1:0, 2:2, 3:4, 4:5, 5:7, 6:9, 7:11, 8:12 }[simpleNum];
    const delta = semitones - expectedMajorOrPerfect;

    if (isPerfectClass) {
      if (delta === 0) return "P";
      if (delta === 1) return "A";
      if (delta === -1) return "d";
      if (delta > 1) return "A".repeat(delta);
      if (delta < -1) return "d".repeat(-delta);
    } else {
      if (delta === 0) return "M";
      if (delta === -1) return "m";
      if (delta === 1) return "A";
      if (delta === -2) return "d";
      if (delta > 1) return "A".repeat(delta);
      if (delta < -2) return "d".repeat((-delta) - 1);
    }
    return "?";
  }

  _intervalNameBetween(noteA, noteB) {
    const midiLow = this.staff._stepToMidi(noteA.step) + (noteA.accOff || 0);
    const midiHigh = this.staff._stepToMidi(noteB.step) + (noteB.accOff || 0);

    const semitones = Math.abs(midiHigh - midiLow);
    const diatonicNum = Math.abs(noteB.step - noteA.step) + 1;

    const simpleNum = ((diatonicNum - 1) % 7) + 1;
    const octaves = Math.floor((diatonicNum - 1) / 7);
    const semitonesReduced = semitones - (12 * octaves);

    return this._qualityFor(simpleNum, semitonesReduced) + diatonicNum;
  }

  _userNotesOnStaff() {
    const $notes = this.$staffEl.find(".note").not(".fixed").not(".preview").not(".hint");
    const notes = $notes.toArray().map((el) => {
      const id = el.getAttribute("data-note-id");
      const step = this.staff._stepOfNoteEl(el);
      const accCls = this.staff._getAttachedAccidentalClass(id);
      const accOff = this.staff._accidentalClassToOffset(accCls);
      return { id, step, accOff };
    });
    notes.sort((a, b) => a.step - b.step);
    return notes;
  }

  _onCheck() {
    const notes = this._notesOnStaffOrdered();
    this.$checkBtn.disable();

    this._stats.checksTotal += 1;

    if (notes.length !== 2) {
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;

      this.prompt.setTone("red");
      this._shakeWrongUserStaffNotes();
      this._failAnimation(this.$checkWrap);

      this.$checkBtn[0] && this.$checkBtn[0].blur && this.$checkBtn[0].blur();
      this.$helpBtn.show();
      return;
    }

    const userNotes = this._userNotesOnStaff();
    if (!userNotes.length || !this._fixedState) {
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this.prompt.setTone("red");
      this._shakeWrongUserStaffNotes();
      this._failAnimation(this.$checkWrap);
      this.$helpBtn.show();
      return;
    }

    const userNote = userNotes[0];
    const fixedNote = {
      step: this._fixedState.step,
      accOff: this.staff._accidentalClassToOffset(this._fixedState.accidentalClass),
    };
    const name = this._intervalNameBetween(fixedNote, userNote);
    const expectedDir = this._currentIntervalDirection === -1 ? -1 : 1;
    const dirDelta = userNote.step - fixedNote.step;
    const directionMatches = this._isStrictDirection()
      ? (expectedDir === 1 ? dirDelta > 0 : dirDelta < 0)
      : true;
    const isCorrect = directionMatches && name === this.prompt.getShortText();

    if (isCorrect) {
      this._stats.checksCorrect += 1;
      this._pauseGameTimer();

      const { earned, bonusEarned } = this._awardPointsForCorrect();
      this._handleCorrectAnswerUi({
        isBonus: bonusEarned > 0,
        earned,
        $prompt: this.prompt.$root,
      });
    } else {
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;

      this.prompt.setTone("red");
      this._shakeWrongUserStaffNotes();
      this._failAnimation(this.$checkWrap);

      // restore interval color after fail animation completes
      this.$checkWrap
        .off(`animationend._failRestore.${this.ns} webkitAnimationEnd._failRestore.${this.ns}`)
        .one(`animationend._failRestore.${this.ns} webkitAnimationEnd._failRestore.${this.ns}`, () => {
          this.prompt.setTone("blue");
        });

      this.$helpBtn.show();
    }
  }

  // ------------------------ hint math (interval-specific) ------------------------

  _intervalSemitones(quality, simpleNum) {
    const baseMajorPerfect = { 1:0, 2:2, 3:4, 4:5, 5:7, 6:9, 7:11, 8:12 }[simpleNum];
    if (baseMajorPerfect == null) return null;

    const isPerfectClass = (simpleNum === 1 || simpleNum === 4 || simpleNum === 5 || simpleNum === 8);
    const q = String(quality || "").trim();

    if (isPerfectClass) {
      if (q === "P") return baseMajorPerfect;
      if (/^A+$/.test(q)) return baseMajorPerfect + q.length;
      if (/^d+$/.test(q)) return baseMajorPerfect - q.length;
      return null;
    }

    if (q === "M") return baseMajorPerfect;
    if (q === "m") return baseMajorPerfect - 1;
    if (/^A+$/.test(q)) return baseMajorPerfect + q.length;
    if (/^d+$/.test(q)) return baseMajorPerfect - (q.length + 1);
    return null;
  }

  _computeHintAnswer() {
    if (!this._fixedState) return null;

    const abbr =
      this._currentIntervalAbbr || this.prompt.getShortText() || "";
    const parsed = parseIntervalAbbr(abbr);
    if (!parsed || !Number.isFinite(parsed.number) || parsed.number < 1) return null;
    const fixedState = {
      step: this._fixedState.step,
      accidentalClass: this._fixedState.accidentalClass,
      midi: this._fixedState.midi,
    };

    const tryDir = (dir) => this._computeTargetFromFixed(
      abbr,
      dir,
      fixedState,
    );

    if (this._isStrictDirection()) {
      return tryDir(this._currentIntervalDirection === -1 ? -1 : +1);
    }
    return tryDir(+1) || tryDir(-1);
  }

}
