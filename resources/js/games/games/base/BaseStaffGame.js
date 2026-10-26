// resources/js/music/games/base/BaseStaffGame.js
import { Staff } from "../../staff/Staff.js";
import {
  pickOne,
  spellNoteFromState,
  spellNoteTextFromState,
  stepToLetterOctave,
} from "../../staff/staffUtils.js";
import {
  renderFinalResultsOverlay,
  queueFinalResultsReveal,
} from "../shared/finalResults.js";
import { PromptUi } from "../shared/PromptUi.js";
import { GameAudio } from "../shared/GameAudio.js";
import { PianoKeyboardUi } from "../shared/PianoKeyboardUi.js";
import { InstructionsUi } from "../shared/InstructionsUi.js";
import { displayNoteName } from "../shared/noteNames.js";

export const PAGE_OPENED_AT_MS = Date.now();

/**
 * BaseStaffGame
 * Shared runtime for staff-based games:
 * - Staff wiring (notes, accidentals, noteState events)
 * - Controls wiring (clear/check/help/continue)
 * - Scoring, progress, final overlay (CountUp), 2x perfect-game bonus
 * - UI SFX (Tone.js) + success/fail/final/perfect-game sounds
 *
 * Subclasses must implement:
 * - newChallenge()
 * - _onCheck()
 * Optionally override:
 * - _computeHintAnswer()
 * - _wireAccidentalPalette()
 * - _onFixedNoteState()
 */
export class BaseStaffGame {
  static MIN_CHALLENGES = 2;
  static MAX_CHALLENGES = 12;
  static LETTER_TO_SOLFEGE = {
    C: "Do",
    D: "Re",
    E: "Mi",
    F: "Fa",
    G: "Sol",
    A: "La",
    B: "Si",
  };

  constructor(options = {}) {
    const defaults = {
      staffEl: "#staff",
      basePoints: 1,
      firstTryBonus: 2,
      sound: true,
      showNoteNames: false,
      clefUrls: null,
      initialClef: "treble",
      maxUserNotes: Infinity,
      numOfChallenges: 10,
      practiceMode: false,
      timer: false,
      levelName: "",
      successPhrases: [
        "Awesome",
        "Nicely done",
        "Well done",
        "Great job",
        "Hooray",
        "Fantastic",
        "Nice work",
        "Looks good",
        "Good one",
        "Splendid",
        "Way to go",
        "Nailed it",
        "Brilliant",
        "Excellent",
        "Superb",
        "Right on",
        "You got it",
        "Perfect",
        "Spot on",
        "Impressive",
        "Top notch",
        "That’s it"
      ],
      // namespace for jQuery event handlers (avoid collisions if multiple games exist)
      namespace: "staffGame",
      // UI gating: how many USER notes before removing instructions / enabling Check
      instructionsAfterUserNotes: 1,
      checkAfterUserNotes: 1,
    };

    this.opts = { ...defaults, ...(options || {}) };
    this.ns = this.opts.namespace || "staffGame";
    this.opts.numOfChallenges = this._normalizeNumOfChallenges(this.opts.numOfChallenges);

    // UI SFX
    this._uiSfxReady = false;
    this._uiSfxSynth = null;
    this._uiSfxNoise = null;
    this._uiTimerSfxSynth = null;

    // DOM refs
    this.$staffEl = $(this.opts.staffEl);
    this.$accidentals = $("#accidentals");
    this.$controls = $("#controls");
    this.$instructions = $("#instructions");
    this.$feedback = $("#feedback-success");
    this.$bonusBadge = this.$feedback.find(".bonus");
    this.$points = $("#points");
    this.$increment = $("#increment");

    this.$checkBtn = $("#check button");
    this.$skipWrap = $("#skip");
    this.$skipBtn = this.$skipWrap.find("button");
    this.$finalOverlay = $("#final-overlay");
    this.$doublePoints = $("#double-points");
    this.$checkWrap = this.$checkBtn.parent();
    this.$progressBar = $("#progress-bar");
    this.$progressCounter = $("#progress-counter");
    this.$helpBtn = $("#help");
    this.$timeupMessage = $("#timeup-message");
    this.$handPointer = $("#hand-pointer");
    this.$timer = $("#timer");
    this.$timerBox = this.$timer.children("div").first();
    this.$timerText = this.$timer.find("span");
    this.prompt = new PromptUi("#prompt");
    this.instructionsUi = new InstructionsUi("#instructions");
    this.$keyboard = $("#keyboard").first();
    this.$keyboardWrap = $("#keyboard-wrapper").first();
    this.$pianoToggleBtn = $("#piano-toggle button").first();

    // Game state
    this.successPhrases = this.opts.successPhrases;
    this.maxUserNotes = Number(this.opts.maxUserNotes);
    this.numOfChallenges = this.opts.numOfChallenges;
    this.levelName = this.opts.levelName;

    this.points = 0;
    this._madeMistakeThisRound = false;
    this._madeAnyMistake = false;
    this._continueBound = false;
    this._usedHintThisRound = false;
    this._correctStreak = 0;

    this._stats = {
      checksTotal: 0,
      checksCorrect: 0,
      finishedAtMs: null,
    };

    this._timerTimeoutId = null;
    this._timerRemainingSec = 0;
    this._audioUnlockArmed = false;
    this._timerEndsAtMs = 0;
    this._finalMetricsSfxTimeouts = [];
    this._finalCountupTimeouts = [];
    this._checkWrongStateTimeout = null;
    this._keyboardSyncPatched = false;
    this._keyboardAccidentalPreviewPatched = false;

    this.keyboard = this._shouldUsePianoKeyboard()
      ? new PianoKeyboardUi({
        rootSelector: "#keyboard",
        namespace: `${this.ns}.keyboard`,
        onKeyClick: (data) => this._onPianoKeyboardKeyClick?.(data),
        canPlayNote: () => this.staff?.isSoundEnabled?.(),
      })
      : null;

    // Staff
    this.staff = new Staff(this.$staffEl, {
      clef: this.opts.initialClef || "treble",
      clefUrls: this.opts.clefUrls || window.__clefUrls,
      autoClef: false,
      getMaxUserNotes: () =>
        Number.isFinite(this.maxUserNotes) ? this.maxUserNotes : Infinity,
      sound: !!this.opts.sound,
      showLineNames: this._normalizeOnOff(this.opts.showLineNames),
      formatLineName: (letter) => this._toDisplayNoteName(letter),
    });

    this.showNoteNames = !!this.opts.showNoteNames;
    this.$staffEl.toggleClass("show-letternames", this.showNoteNames);

    // Fixed note state (for hints)
    this._fixedNote = { letterWithAcc: "?", letterOnly: "?" };
    this._fixedState = null;

    // init UI
    this.$increment.hide();
    this.$bonusBadge.hide();
    this.$doublePoints?.hide?.();
    this.$points.text(String(this.points));
  }

  _normalizeOnOff(value) {
    if (value === true) return true;
    if (value === false) return false;
    const s = String(value ?? "").trim().toLowerCase();
    return s === "on" || s === "true" || s === "1";
  }

  _showSolfegeNoteNames() {
    return this._normalizeOnOff(this.opts.solfege);
  }

  _shouldUsePianoKeyboard() {
    if (this.opts.usePianoKeyboard === false) return false;
    if (this._normalizeOnOff(this.opts.usePianoKeyboard)) return true;
    return !!this.$keyboard?.length;
  }

  _keyboardStartNoteForClef(clef) {
    const cleanClef = String(clef || "").trim().toLowerCase();
    if (cleanClef === "bass") return "C3";
    if (cleanClef === "alto") return "C3";
    if (cleanClef === "tenor") return "C3";
    return "C4";
  }

  _syncPianoKeyboardStartNote() {
    if (!this.keyboard || !this.staff) return;
    this.keyboard.setStartNote(this._keyboardStartNoteForClef(this.staff.getClef()));
  }

  _collectKeyboardSyncNotes() {
    return this.$staffEl.find(".note").not(".preview").not(".hint").toArray()
      .map((el) => {
        const $note = $(el);
        const noteId = String($note.attr("data-note-id") || "");
        if (!noteId) return null;
        const top = parseFloat($note.css("top"));
        const step = Number.isFinite(top) ? this.staff.yToStep(top) : null;
        const accidentalClass = this.staff._getAttachedAccidentalClass?.(noteId) || null;
        return {
          noteId,
          step,
          accidentalClass,
          fixed: $note.hasClass("fixed"),
        };
      })
      .filter(Boolean);
  }

  _collectUserStaffNotes() {
    return this._collectKeyboardSyncNotes()
      .filter((note) => !note.fixed && Number.isFinite(note.step));
  }

  _normalizedAccidentalClass(value) {
    return String(value || "");
  }

  _answerMatchesUserStaffNote(answer, note) {
    if (!answer || !note) return false;
    if (!Number.isFinite(answer.step) || !Number.isFinite(note.step)) return false;
    return (
      Number(answer.step) === Number(note.step) &&
      this._normalizedAccidentalClass(answer.accidentalClass) === this._normalizedAccidentalClass(note.accidentalClass)
    );
  }

  _wrongUserStaffNotes() {
    const notes = this._collectUserStaffNotes();
    if (!notes.length) return [];

    const answers = this._computeHintAnswers?.() || [];
    const remaining = Array.isArray(answers)
      ? answers.filter((answer) => Number.isFinite(answer?.step))
      : [];

    if (!remaining.length) return notes;

    const wrong = [];
    notes.forEach((note) => {
      const matchIndex = remaining.findIndex((answer) => this._answerMatchesUserStaffNote(answer, note));
      if (matchIndex >= 0) {
        remaining.splice(matchIndex, 1);
      } else {
        wrong.push(note);
      }
    });

    return wrong;
  }

  _shakeUserStaffNotes(notes = null) {
    const list = Array.isArray(notes) ? notes : this._collectUserStaffNotes();
    if (!list.length) return;

    list.forEach(({ noteId }) => {
      if (!noteId) return;

      const $targets = this.$staffEl
        .find(`.note[data-note-id="${noteId}"], .accidental[data-for-note-id="${noteId}"], .ledger[data-for-note-id="${noteId}"]`);

      if (!$targets.length) return;

      $targets.removeClass("animate__animated animate__shakeX");
      $targets.each((_, el) => {
        // eslint-disable-next-line no-unused-expressions
        el.offsetWidth;
      });
      $targets.addClass("animate__animated animate__shakeX");

      $targets
        .off(`animationend._wrongStaffNote.${this.ns} webkitAnimationEnd._wrongStaffNote.${this.ns} oAnimationEnd._wrongStaffNote.${this.ns} MSAnimationEnd._wrongStaffNote.${this.ns}`)
        .one(`animationend._wrongStaffNote.${this.ns} webkitAnimationEnd._wrongStaffNote.${this.ns} oAnimationEnd._wrongStaffNote.${this.ns} MSAnimationEnd._wrongStaffNote.${this.ns}`, () => {
          $targets.removeClass("animate__animated animate__shakeX");
        });
    });
  }

  _shakeWrongUserStaffNotes() {
    this._shakeUserStaffNotes(this._wrongUserStaffNotes());
  }

  _keyboardPrimaryNote(notes) {
    const list = Array.isArray(notes) ? notes : [];
    const userNotes = list.filter((note) => !note.fixed);
    if (userNotes.length) return userNotes[userNotes.length - 1];
    return list.length ? list[list.length - 1] : null;
  }

  _keyboardNoteNameForState(step, accidentalClass) {
    if (!Number.isFinite(step) || !this.keyboard) return "";
    const noteState = stepToLetterOctave(this.staff, step);
    const letter = String(noteState?.letter || "").trim().toUpperCase();
    const octave = Number(noteState?.octave);
    if (!letter || !Number.isFinite(octave)) return "";

    const accidentalOffset = this.staff._accidentalClassToOffset(accidentalClass) || 0;
    const naturalPitchClass = {
      C: 0,
      D: 2,
      E: 4,
      F: 5,
      G: 7,
      A: 9,
      B: 11,
    }[letter];

    if (!Number.isInteger(naturalPitchClass)) return "";

    const midi = ((octave + 1) * 12) + naturalPitchClass + accidentalOffset;
    return this.keyboard._noteNameFromMidi?.(midi) || "";
  }

  _keyboardMarkerLabelForState(step, accidentalClass) {
    if (!this.showNoteNames) return "";
    const spelledNote = spellNoteTextFromState(this.staff, step, accidentalClass);
    return this._toDisplayNoteName(spelledNote.replace(/-?\d+$/, ""));
  }

  _syncPianoKeyboardMarkerFromStaff() {
    if (!this.keyboard || !this.staff) return;

    const notes = this._collectKeyboardSyncNotes().filter((note) => Number.isFinite(note.step));
    const primary = this._keyboardPrimaryNote(notes);
    if (!primary) {
      this.keyboard.syncActiveKeys([]);
      return;
    }

    const primaryState = stepToLetterOctave(this.staff, primary.step);
    const keys = [
      this.keyboard.keyForNote(
        primaryState?.letter,
        primary.accidentalClass,
        primaryState?.octave,
      ),
    ];
    const markerEntries = notes
      .map((note) => {
        const noteName = this._keyboardNoteNameForState(note.step, note.accidentalClass);
        return {
          noteName,
          markerLabel: this._keyboardMarkerLabelForState(note.step, note.accidentalClass),
          tone: note.fixed ? "secondary" : "primary",
          $key: note.noteId === primary.noteId ? keys[0] : null,
        };
      })
      .filter((entry) => entry.noteName);

    notes.forEach((note) => {
      if (note.noteId === primary.noteId) return;
      const noteState = stepToLetterOctave(this.staff, note.step);
      const $key = this.keyboard.keyForNoteIfVisible(
        noteState?.letter,
        note.accidentalClass,
        noteState?.octave,
      );
      if ($key.length) {
        keys.push($key);
        const entry = markerEntries.find((item) => item.noteName === this._keyboardNoteNameForState(note.step, note.accidentalClass));
        if (entry) entry.$key = $key;
      }
    });

    this.keyboard.syncActiveMarkers(markerEntries);
  }

  _syncPianoKeyboardMarkerFromAccidentalPreview() {
    if (!this.keyboard || !this.staff) return;

    const dragState = this.staff?._accDragSound || {};
    const noteId = String(dragState.noteId || "");
    const previewAccidentalClass = dragState.prospectiveCls || null;

    if (!noteId || !previewAccidentalClass) {
      this._syncPianoKeyboardMarkerFromStaff();
      return;
    }

    const $note = this.$staffEl.find(`.note[data-note-id="${noteId}"]`).first();
    if (!$note.length) {
      this._syncPianoKeyboardMarkerFromStaff();
      return;
    }

    const top = parseFloat($note.css("top"));
    const step = Number.isFinite(top) ? this.staff.yToStep(top) : null;
    if (!Number.isFinite(step)) {
      this._syncPianoKeyboardMarkerFromStaff();
      return;
    }

    const notes = this._collectKeyboardSyncNotes().filter((note) => Number.isFinite(note.step));
    const primary = this._keyboardPrimaryNote(notes) || { noteId, step, accidentalClass: previewAccidentalClass, fixed: false };
    const primaryState = stepToLetterOctave(this.staff, primary.step);
    const primaryAccidental = primary.noteId === noteId ? previewAccidentalClass : primary.accidentalClass;
    const keys = [
      this.keyboard.keyForNote(
        primaryState?.letter,
        primaryAccidental,
        primaryState?.octave,
      ),
    ];
    const markerEntries = notes
      .map((note) => {
        const noteName = this._keyboardNoteNameForState(
          note.step,
          note.noteId === noteId ? previewAccidentalClass : note.accidentalClass,
        );
        return {
          noteName,
          markerLabel: this._keyboardMarkerLabelForState(
            note.step,
            note.noteId === noteId ? previewAccidentalClass : note.accidentalClass,
          ),
          tone: note.fixed ? "secondary" : "primary",
          $key: note.noteId === primary.noteId ? keys[0] : null,
        };
      })
      .filter((entry) => entry.noteName);

    notes.forEach((note) => {
      if (note.noteId === primary.noteId) return;
      const noteState = stepToLetterOctave(this.staff, note.step);
      const accidentalClass = note.noteId === noteId ? previewAccidentalClass : note.accidentalClass;
      const $key = this.keyboard.keyForNoteIfVisible(
        noteState?.letter,
        accidentalClass,
        noteState?.octave,
      );
      if ($key.length) {
        keys.push($key);
        const entry = markerEntries.find((item) => item.noteName === this._keyboardNoteNameForState(note.step, accidentalClass));
        if (entry) entry.$key = $key;
      }
    });

    this.keyboard.syncActiveMarkers(markerEntries);
  }

  _wirePianoKeyboardSync() {
    if (!this.keyboard || !this.staff || this._keyboardSyncPatched) return;
    this._keyboardSyncPatched = true;

    const baseMoveNote = this.staff.moveNote.bind(this.staff);
    this.staff.moveNote = (...args) => {
      const out = baseMoveNote(...args);
      this._syncPianoKeyboardMarkerFromStaff();
      return out;
    };

    const baseRemoveNote = this.staff.removeNote.bind(this.staff);
    this.staff.removeNote = (...args) => {
      const out = baseRemoveNote(...args);
      this._syncPianoKeyboardMarkerFromStaff();
      return out;
    };

    const baseClearNotes = this.staff.clearNotes.bind(this.staff);
    this.staff.clearNotes = (...args) => {
      const out = baseClearNotes(...args);
      this._syncPianoKeyboardMarkerFromStaff();
      return out;
    };

    this.$staffEl
      .off(`staff:noteState.${this.ns}.keyboard`)
      .on(`staff:noteState.${this.ns}.keyboard`, (e, data) => {
        this._syncPianoKeyboardMarkerFromStaff();
      });
  }

  _wirePianoKeyboardAccidentalPreview() {
    if (!this.keyboard || this._keyboardAccidentalPreviewPatched) return;
    this._keyboardAccidentalPreviewPatched = true;

    const $tools = $("#accidentals .accidental-tool");
    if (!$tools.length || !$tools.draggable) return;

    const originalDrag = $tools.draggable("option", "drag");
    const originalStop = $tools.draggable("option", "stop");

    $tools.draggable("option", "drag", (event, ui) => {
      if (typeof originalDrag === "function") originalDrag.call(event.currentTarget, event, ui);
      this._syncPianoKeyboardMarkerFromAccidentalPreview();
    });

    $tools.draggable("option", "stop", (event, ui) => {
      if (typeof originalStop === "function") originalStop.call(event.currentTarget, event, ui);
      this._syncPianoKeyboardMarkerFromStaff();
    });
  }

  _syncPianoKeyboardToggleUi() {
    if (!this.$pianoToggleBtn?.length || !this.$keyboardWrap?.length) return;
    const visible = this.$keyboardWrap.is(":visible");
    if (visible) this.$pianoToggleBtn.attr("selected", "selected");
    else this.$pianoToggleBtn.removeAttr("selected");
  }

  _wirePianoKeyboardToggle() {
    if (!this.$keyboardWrap?.length) return;

    if (this.$pianoToggleBtn?.length) this.$keyboardWrap.hide();
    else this.$keyboardWrap.show();

    if (this.$pianoToggleBtn?.length) {
      this.$pianoToggleBtn
        .off(`click.${this.ns}.pianoToggle`)
        .on(`click.${this.ns}.pianoToggle`, (e) => {
          e.preventDefault();
          this.$keyboardWrap.toggle();
          this._syncPianoKeyboardToggleUi();
        });
    }

    this._syncPianoKeyboardToggleUi();
  }

  _toDisplayNoteName(letterWithAccidentals) {
    const raw = String(letterWithAccidentals || "").trim();
    if (!raw) return raw;
    if (!this._showSolfegeNoteNames()) return displayNoteName(raw);

    const m = raw.match(/^([A-G])(.*)$/i);
    if (!m) return raw;

    const letter = String(m[1] || "").toUpperCase();
    const acc = String(m[2] || "");
    const sol = BaseStaffGame.LETTER_TO_SOLFEGE[letter] || letter;
    return displayNoteName(`${sol}${acc}`);
  }

  _normalizeNumOfChallenges(raw) {
    const n = Number(raw);
    const fallback = Number(this.opts?.numOfChallenges ?? 10);
    const safe = Number.isFinite(n) ? Math.trunc(n) : (Number.isFinite(fallback) ? Math.trunc(fallback) : 10);
    const min = BaseStaffGame.MIN_CHALLENGES;
    const max = BaseStaffGame.MAX_CHALLENGES;

    if (safe < min) return min;
    if (safe > max) return max;
    return safe;
  }

  // ------------------------ sound controls ------------------------

  setSoundEnabled(enabled) {
    this.opts.sound = !!enabled;
    this.staff.setSoundEnabled(!!enabled);

    if (!this.opts.sound && window.Tone) {
      try {
        this._uiSfxSynth && this._uiSfxSynth.releaseAll && this._uiSfxSynth.releaseAll();
      } catch (_) {}
      try {
        this._uiSfxSynth && this._uiSfxSynth.dispose && this._uiSfxSynth.dispose();
      } catch (_) {}
      try {
        this._uiSfxNoise && this._uiSfxNoise.dispose && this._uiSfxNoise.dispose();
      } catch (_) {}
      try {
        this._uiTimerSfxSynth && this._uiTimerSfxSynth.dispose && this._uiTimerSfxSynth.dispose();
      } catch (_) {}

      this._uiSfxSynth = null;
      this._uiSfxNoise = null;
      this._uiTimerSfxSynth = null;
      this._uiSfxReady = false;

      try {
        Tone.context && Tone.context.suspend && Tone.context.suspend();
      } catch (_) {}
    }

    return this;
  }

  isSoundEnabled() {
    return this.staff.isSoundEnabled();
  }

  async _ensureUiSfxAudio() {
    if (!this.isSoundEnabled()) return;
    if (this._uiSfxReady) return;
    if (!window.Tone) return;

    try {
      await Tone.start();
    } catch (_) {
      // Browser may block audio start until a user gesture.
      return;
    }

    this._uiSfxSynth = GameAudio.createUiPolySynth();
    this._uiSfxNoise = GameAudio.createUiNoiseSynth();
    this._uiTimerSfxSynth = GameAudio.createUiTimerSynth();

    this._uiSfxReady = true;
  }

  _armUiSfxOnFirstGesture() {
    if (this._audioUnlockArmed) return;
    if (!this.isSoundEnabled() || !window.Tone) return;

    this._audioUnlockArmed = true;
    const ns = `.uiSfxUnlock.${this.ns}`;
    const unlock = () => {
      this._ensureUiSfxAudio().finally(() => {
        $(document).off(ns);
      });
    };

    $(document)
      .off(ns)
      .one(`pointerdown${ns} keydown${ns} touchstart${ns}`, unlock);
  }

  _playSuccessSfxBasic() {
    if (!this.isSoundEnabled() || !window.Tone) return;

    this._ensureUiSfxAudio().then(() => {
      if (!this._uiSfxSynth) return;

      const now = Tone.now();
      const variants = [
        ["C6", "E6", "G6"],
        ["D6", "F#6", "A6"],
        ["E6", "G6", "B6"],
        ["G5", "B5", "D6", "G6"],
        ["A5", "C6", "E6", "A6"],
        ["C6", "D6", "G6"],
        ["F5", "A5", "C6", "F6"],
        ["E6", "A6", "C7"],
        ["B5", "D6", "G6"],
        ["C6", "G6", "E7"],
      ];

      const picked = variants[Math.floor(Math.random() * variants.length)];
      picked.forEach((n, i) => {
        this._uiSfxSynth.triggerAttackRelease(n, 0.07, now + i * 0.05, GameAudio.scale("successBasic", 0.42));
      });
    });
  }

  _playSuccessSfxBonus() {
    if (!this.isSoundEnabled() || !window.Tone) return;

    this._ensureUiSfxAudio().then(() => {
      if (!this._uiSfxSynth) return;

      const now = Tone.now();
      const oldEnv = { ...this._uiSfxSynth.get().envelope };
      const oldOsc = this._uiSfxSynth.get().oscillator?.type;

      try {
        this._uiSfxSynth.set({
          oscillator: { type: "sine" },
          envelope: { attack: 0.004, decay: 0.12, sustain: 0.15, release: 0.65 },
        });
      } catch (_) {}

      const streakLevel = Math.max(1, Math.min(24, Number(this._correctStreak) || 1));
      const semitoneShift = streakLevel - 1;
      const toNote = (midi) => Tone.Frequency(midi, "midi").toNote();

      // Start slightly higher (D4 root) and climb one semitone per streak level up to 24.
      const arp = [62, 66, 69, 73, 74].map((m) => toNote(m + semitoneShift));
      const hit = [62, 69, 74, 78].map((m) => toNote(m + semitoneShift));

      arp.forEach((n, i) => {
        this._uiSfxSynth.triggerAttackRelease(n, 0.06, now + i * 0.045, GameAudio.scale("successBonus", 0.45));
      });
      hit.forEach((n) => {
        this._uiSfxSynth.triggerAttackRelease(n, 0.12, now + 0.26, GameAudio.scale("successBonus", 0.30));
      });

      setTimeout(() => {
        try {
          this._uiSfxSynth.set({
            oscillator: { type: oldOsc || "triangle" },
            envelope: oldEnv,
          });
        } catch (_) {}
      }, 600);
    });
  }

  _playFailSfx() {
    if (!this.isSoundEnabled() || !window.Tone) return;

    this._ensureUiSfxAudio().then(() => {
      const now = Tone.now();

      if (this._uiSfxNoise) {
        this._uiSfxNoise.triggerAttackRelease(0.06, now, GameAudio.scale("failNoise", 0.45));
      }
      if (this._uiSfxSynth) {
        this._uiSfxSynth.triggerAttackRelease("A2", 0.10, now + 0.01, GameAudio.scale("failNote", 0.55));
        this._uiSfxSynth.triggerAttackRelease("G2", 0.12, now + 0.08, GameAudio.scale("failNote", 0.6));
      }
    });
  }

  _playFinalSfx() {
    if (!this.isSoundEnabled() || !window.Tone) return;

    this._ensureUiSfxAudio().then(() => {
      GameAudio.playFinalResults();
    });
  }

  _clearFinalMetricsSfxTimers() {
    if (!Array.isArray(this._finalMetricsSfxTimeouts)) {
      this._finalMetricsSfxTimeouts = [];
      return;
    }
    this._finalMetricsSfxTimeouts.forEach((id) => clearTimeout(id));
    this._finalMetricsSfxTimeouts = [];
  }

  _clearFinalCountupTimers() {
    if (!Array.isArray(this._finalCountupTimeouts)) {
      this._finalCountupTimeouts = [];
      return;
    }
    this._finalCountupTimeouts.forEach((id) => clearTimeout(id));
    this._finalCountupTimeouts = [];
  }

  _playFinalMetricPopSfx(index) {
    if (!this.isSoundEnabled() || !window.Tone) return;
    if (!this._uiSfxReady) return;

    const now = Tone.now();
    const safeIdx = Math.max(0, Number(index) || 0);
    const midi = Math.min(96, 67 + safeIdx * 2); // rises as each box appears
    const synth = this._uiTimerSfxSynth || this._uiSfxSynth;
    if (!synth) return;

    synth.triggerAttackRelease(Tone.Frequency(midi, "midi"), 0.055, now, GameAudio.scale("finalMetric", 0.44));
    synth.triggerAttackRelease(Tone.Frequency(midi + 5, "midi"), 0.045, now + 0.03, GameAudio.scale("finalMetric", 0.34));
  }

  _animateFinalMetricsWithSfx() {
    const $boxes = this.$finalOverlay.find("#metrics-boxes > div");
    if (!$boxes.length) return;
    $boxes.css({ position: "relative", zIndex: 6 });

    const BASE_DELAY_MS = 260;
    const STEP_DELAY_MS = 260; // wider spacing between boxes

    this._clearFinalMetricsSfxTimers();

    $boxes.each((i, el) => {
      const delayMs = BASE_DELAY_MS + (i * STEP_DELAY_MS);
      el.style.animationDelay = `${delayMs}ms`;

      const tid = setTimeout(() => {
        this._playFinalMetricPopSfx(i);
      }, delayMs);
      this._finalMetricsSfxTimeouts.push(tid);
    });
  }

  _playPerfectGameBonusSfx() {
    if (!this.isSoundEnabled() || !window.Tone) return;

    this._ensureUiSfxAudio().then(() => {
      if (!this._uiSfxSynth) return;

      const oldEnv = { ...this._uiSfxSynth.get().envelope };
      const oldOsc = this._uiSfxSynth.get().oscillator?.type;

      try {
        this._uiSfxSynth.set({
          oscillator: { type: "triangle" },
          envelope: { attack: 0.01, decay: 0.18, sustain: 0.25, release: 0.8 },
        });
      } catch (_) {}

      const now = Tone.now();

      const fanfare = ["C5", "E5", "G5", "C6", "E6", "G6", "C7"];
      fanfare.forEach((n, i) => {
        this._uiSfxSynth.triggerAttackRelease(n, 0.09, now + i * 0.06, GameAudio.scale("perfectBonus", 0.62));
      });

      const hit = ["C6", "G6", "C7", "E7"];
      hit.forEach((n) => this._uiSfxSynth.triggerAttackRelease(n, 0.35, now + 0.48, GameAudio.scale("perfectBonus", 0.46)));

      setTimeout(() => {
        try {
          this._uiSfxSynth.set({
            oscillator: { type: oldOsc || "triangle" },
            envelope: oldEnv,
          });
        } catch (_) {}
      }, 1400);
    });
  }

  _playRunStartFanfareSfx() {
    if (!this.isSoundEnabled() || !window.Tone) return;

    this._ensureUiSfxAudio().then(() => {
      if (!this._uiSfxSynth) return;

      const now = Tone.now();
      // French-overture style: dotted long-short gestures, ceremonial but "opening".
      // 4 rhythmic motifs x 5 tonal centers = 20 variants.
      const motifs = [
        // Motif A: tonic -> chromatic lift -> suspended color
        [
          [[0, 7], 0.00, 0.19, 0.20],
          [[3, 7], 0.24, 0.06, 0.18],
          [[0, 5, 10], 0.38, 0.18, 0.22],
          [[2, 5, 9], 0.62, 0.06, 0.18],
          [[0, 7, 12], 0.76, 0.15, 0.22],
        ],
        // Motif B: minor color then bright turn
        [
          [[0, 3, 7], 0.00, 0.18, 0.20],
          [[2, 5, 9], 0.23, 0.06, 0.18],
          [[0, 7], 0.36, 0.18, 0.22],
          [[4, 7, 11], 0.60, 0.06, 0.18],
          [[0, 5, 9], 0.74, 0.15, 0.22],
        ],
        // Motif C: fanfare call/response
        [
          [[0, 12], 0.00, 0.20, 0.19],
          [[7, 12], 0.25, 0.06, 0.17],
          [[2, 9, 14], 0.39, 0.18, 0.21],
          [[5, 9, 12], 0.63, 0.06, 0.17],
          [[0, 7, 10], 0.77, 0.16, 0.21],
        ],
        // Motif D: stately dotted march
        [
          [[0, 5], 0.00, 0.19, 0.20],
          [[2, 5], 0.24, 0.06, 0.18],
          [[0, 4, 9], 0.38, 0.18, 0.22],
          [[-1, 4, 7], 0.62, 0.06, 0.18],
          [[0, 7, 11], 0.76, 0.15, 0.22],
        ],
      ];
      const roots = [53, 55, 57, 58, 60]; // F3..C4
      const variantIndex = Math.floor(Math.random() * 20);
      const motif = motifs[Math.floor(variantIndex / roots.length)];
      const root = roots[variantIndex % roots.length];
      const toNote = (m) => Tone.Frequency(m, "midi").toNote();

      motif.forEach(([intervals, t, dur, vel]) => {
        const notes = intervals.map((i) => toNote(root + i));
        this._uiSfxSynth.triggerAttackRelease(notes, dur, now + t, GameAudio.scale("runStart", vel));
      });
    });
  }

  // ------------------------ lifecycle ------------------------

  _isTimerEnabled() {
    const v = this.opts.timer;
    const s = String(v || "").trim().toLowerCase();
    return v === true || s === "true" || s === "on";
  }

  _formatTimerDisplay(totalSec) {
    const sec = Math.max(0, Math.floor(Number(totalSec) || 0));
    const mm = String(Math.floor(sec / 60)).padStart(2, "0");
    const ss = String(sec % 60).padStart(2, "0");
    return `${mm}:${ss}`;
  }

  _renderTimerDisplay() {
    if (!this.$timerText?.length) return;
    this.$timerText.text(this._formatTimerDisplay(this._timerRemainingSec));
    this._syncTimerWarningStyle();
  }

  _syncTimerWarningStyle() {
    if (!this.$timerBox?.length) return;
    const warning = this._timerRemainingSec <= 5;
    this.$timerBox.toggleClass("bg-red", warning);
    this.$timerBox.toggleClass("bg-primary", !warning);
  }

  _setTimedOutInteractivityDisabled(disabled) {
    const on = !!disabled;
    const $staff = $("#staff");
    const $staffWrapper = $("#staff-wrapper");
    const $accidentals = $("#accidentals");

    if (on) {
      $staff.attr("disabled", "disabled");
      $staffWrapper.attr("disabled", "disabled");
      $accidentals.attr("disabled", "disabled");
      $staff.attr("aria-disabled", "true");
      $staffWrapper.attr("aria-disabled", "true");
      $accidentals.attr("aria-disabled", "true");
      $staff.css("pointer-events", "none");
      $staffWrapper.css("pointer-events", "none");
      $accidentals.css("pointer-events", "none");
    } else {
      $staff.removeAttr("disabled");
      $staffWrapper.removeAttr("disabled");
      $accidentals.removeAttr("disabled");
      $staff.removeAttr("aria-disabled");
      $staffWrapper.removeAttr("aria-disabled");
      $accidentals.removeAttr("aria-disabled");
      $staff.css("pointer-events", "");
      $staffWrapper.css("pointer-events", "");
      $accidentals.css("pointer-events", "");
    }
  }

  _showSkipRoundButton() {
    if (!this.$skipWrap?.length) return;
    this.$skipWrap.show();
  }

  _hideSkipRoundButton() {
    if (!this.$skipWrap?.length) return;
    this.$skipWrap.hide();
  }

  _hideTimeUpMessage() {
    if (!this.$timeupMessage?.length) return;
    this.$timeupMessage
      .removeClass("animate__animated animate__flash")
      .hide();
  }

  _showTimeUpMessage() {
    if (!this.$timeupMessage?.length) return;
    this.$timeupMessage
      .removeClass("animate__animated animate__flash")
      .show();
    // eslint-disable-next-line no-unused-expressions
    this.$timeupMessage[0] && this.$timeupMessage[0].offsetWidth;
    this.$timeupMessage.addClass("animate__animated animate__flash");
  }

  _wouldReachLastRoundAfterAdvance() {
    if (this._isPracticeMode()) return false;
    const steps = Math.max(1, this.numOfChallenges || 1);
    const increment = 100 / steps;
    const current = parseFloat(this.$progressBar.data("progress")) || 0;
    return (current + increment) >= 100;
  }

  _finishRoundAsTimedOut() {
    this._clearCorrectStreak();
    this._madeAnyMistake = true;
    this._madeMistakeThisRound = true;
    this._stats.checksTotal += 1;

    const reachedEnd = this._updateProgressBar() >= 100;
    if (reachedEnd && !this._isPracticeMode()) {
      this._stats.finishedAtMs = Date.now();
      queueFinalResultsReveal({
        $button: this.$checkBtn,
        showFinalResults: () => this._showFinalResults(),
      });
      return;
    }

    this._setTimedOutInteractivityDisabled(false);
    this._hideTimeUpMessage();
    this._hideSkipRoundButton();
    this.prompt.setTone("blue");
    this._resetRoundTimerIfEnabled();
    this.$accidentals.removeClass("invisible");
    this.newChallenge();
    this._armUiGates({ resetInstructions: false });
    this.$checkBtn.enable();
  }

  _pulseTimerWarning() {
    if (!this.$timer?.length) return;
    this.$timer.removeClass("animate__animated animate__pulse");
    // eslint-disable-next-line no-unused-expressions
    this.$timer[0] && this.$timer[0].offsetWidth;
    this.$timer.addClass("animate__animated animate__pulse");
  }

  _playTimerWarningBeep() {
    if (!this.isSoundEnabled() || !window.Tone) return;
    if (!this._uiSfxReady || !this._uiTimerSfxSynth) return;
    const now = Tone.now();
    this._uiTimerSfxSynth.triggerAttackRelease("C6", 0.06, now, GameAudio.scale("timerBeep", 0.5));
  }

  _playTimerTimeUpSfx() {
    if (!this.isSoundEnabled() || !window.Tone) return;
    if (!this._uiSfxReady || !this._uiTimerSfxSynth) return;
    const now = Tone.now();
    if (this._uiSfxNoise) {
      this._uiSfxNoise.triggerAttackRelease(0.12, now, GameAudio.scale("timerTimeUp", 0.2));
    }
    this._uiTimerSfxSynth.triggerAttackRelease("G4", 0.11, now, GameAudio.scale("timerTimeUp", 0.72));
    this._uiTimerSfxSynth.triggerAttackRelease("E4", 0.13, now + 0.10, GameAudio.scale("timerTimeUp", 0.76));
    this._uiTimerSfxSynth.triggerAttackRelease("C4", 0.18, now + 0.22, GameAudio.scale("timerTimeUp", 0.82));
  }

  _removeAllStaffNotesWithSmoke() {
    if (!this.$staffEl?.length || !this.staff?.removeNote) return;

    const noteIds = this.$staffEl
      .find(".note")
      .not(".preview")
      .not(".hint")
      .map((_, el) => String(el.getAttribute("data-note-id") || ""))
      .get()
      .filter(Boolean);

    if (!noteIds.length) return;

    noteIds.forEach((id) => {
      const delay = Math.floor(Math.random() * 140); // tiny natural stagger
      setTimeout(() => {
        this.staff.removeNote(id, { smoke: true });
      }, delay);
    });
  }

  _stopGameTimer() {
    if (this._timerTimeoutId != null) {
      clearTimeout(this._timerTimeoutId);
      this._timerTimeoutId = null;
    }
    this._timerEndsAtMs = 0;
    this._timerRemainingSec = 0;
    this._syncTimerWarningStyle();
    this.$timer?.removeClass?.("animate__animated animate__pulse");
  }

  _pauseGameTimer() {
    if (this._timerTimeoutId != null) {
      clearTimeout(this._timerTimeoutId);
      this._timerTimeoutId = null;
    }
    this._timerEndsAtMs = 0;
    this.$timer?.removeClass?.("animate__animated animate__pulse");
  }

  _runGameTimerTick() {
    if (!this._timerEndsAtMs) return;

    const prev = this._timerRemainingSec;
    const msLeft = this._timerEndsAtMs - Date.now();
    const next = Math.max(0, Math.ceil(msLeft / 1000));

    this._timerRemainingSec = next;
    this._renderTimerDisplay();

    if (next > 0 && next <= 5 && next !== prev) {
      this._pulseTimerWarning();
      this._playTimerWarningBeep();
    } else if (next === 0 && prev !== 0) {
      $("#check").hide();
      this.$helpBtn.hide();
      this._removeAllStaffNotesWithSmoke();
      this._setTimedOutInteractivityDisabled(true);
      this._pulseTimerWarning();
      this._playTimerTimeUpSfx();
      this._showTimeUpMessage();
      this.$checkBtn.disable();

      if (this._wouldReachLastRoundAfterAdvance()) {
        this._hideSkipRoundButton();
        this._finishRoundAsTimedOut();
      } else {
        this._showSkipRoundButton();
      }
    }

    if (next <= 0) {
      this._stopGameTimer();
      return;
    }

    const nextChangeAt = this._timerEndsAtMs - ((next - 1) * 1000);
    const delay = Math.max(16, nextChangeAt - Date.now());
    this._timerTimeoutId = setTimeout(() => this._runGameTimerTick(), delay);
  }

  _startGameTimer(startSec = 10) {
    this._stopGameTimer();
    const total = Math.max(0, Math.floor(Number(startSec) || 0));
    this._timerEndsAtMs = Date.now() + (total * 1000);
    this._timerRemainingSec = total;
    this._renderTimerDisplay();
    this._runGameTimerTick();
  }

  _timerLimitSeconds() {
    const raw = Number(
      this.opts.timeLimit != null ? this.opts.timeLimit : this.opts.timerLimit,
    );
    if (!Number.isFinite(raw)) return 10;
    return Math.max(0, Math.floor(raw));
  }

  _resetRoundTimerIfEnabled() {
    if (!this._isTimerEnabled()) return;
    this.$timer.show();
    this._startGameTimer(this._timerLimitSeconds());
  }

  start() {
    this._madeAnyMistake = false;

    this.instructionsUi.show().replay();
    $("#controls").show();

    this._instructionsRemoved = false;

    this._wireAccidentalPalette();
    this._wireStaffTools();
    this.keyboard?.bind?.();
    this._wirePianoKeyboardSync();
    this._wirePianoKeyboardAccidentalPreview();
    this._wirePianoKeyboardToggle();
    this._wireControls();
    this._resetProgress();
    this._setTimedOutInteractivityDisabled(false);
    this._hideTimeUpMessage();
    this._hideSkipRoundButton();

    if (this._isTimerEnabled()) {
      this.$timer.show();
      this._armUiSfxOnFirstGesture();
      this._resetRoundTimerIfEnabled();
    } else {
      this._stopGameTimer();
      this.$timer.hide();
    }

    this.$accidentals.removeClass("invisible");
    this.newChallenge();
    this._syncPianoKeyboardStartNote();
    this._syncPianoKeyboardMarkerFromStaff();
    this._armUiGates({ resetInstructions: true });

    $("#page-wrapper").fadeIn("fast");
  }

  // Subclasses must implement
  newChallenge() {
    throw new Error("newChallenge() not implemented");
  }

  // Subclasses implement their own check logic
  _onCheck() {
    throw new Error("_onCheck() not implemented");
  }

  // ------------------------ wiring ------------------------

  _wireAccidentalPalette() {
    $("#accidentals .music-font__doublesharp, #accidentals .music-font__doubleflat").addClass("d-none");
    this.$accidentals.removeClass("invisible");
  }

  _wireStaffTools() {
    this.staff.enableNoteDragAndClickDelete();
    this.staff.enableGhostClickCreate();
    this.staff.enableAccidentalDrag(
      $("#accidentals .music-font__sharp, #accidentals .music-font__flat, #accidentals .music-font__natural"),
    );
    this.staff.enableAccidentalDropOnStaff();

    this.$staffEl
      .off(`staff:noteState._log.${this.ns}`)
      .on(`staff:noteState._log.${this.ns}`, (e, data) => {
        const full = spellNoteFromState(this.staff, data.step, data.accidentalClass);
        const letterOnly = full.replace(/\d+$/, "");
        const displayName = this._toDisplayNoteName(letterOnly);

        if (this.showNoteNames) {
          this.$staffEl
            .find(`.note[data-note-id="${data.noteId}"] .lettername`)
            .html(displayName);
        }

        if (data.source === "fixed") {
          this._fixedNote = {
            letterWithAcc: letterOnly,
            letterOnly: letterOnly.replace(/[#b]+$/, ""),
          };

          this._fixedState = {
            step: data.step,
            accidentalClass: data.accidentalClass || null,
            midi: data.midi,
          };

          this._onFixedNoteState?.(data, full, letterOnly);
        }

        // Keep your debug logging behavior
        // eslint-disable-next-line no-console
        console.log(data.source === "fixed" ? "Fixed note:" : "User note:", full, {
          midi: data.midi,
          noteId: data.noteId,
          step: data.step,
          clef: this.staff.getClef(),
        });
      });
  }

  _wireControls() {
    $("#clear")
      .off(`click.${this.ns}`)
      .on(`click.${this.ns}`, () => this.staff.clearNotes());

    this.$checkBtn
      .off(`click.${this.ns}`)
      .on(`click.${this.ns}`, () => this._onCheck());

    this.$helpBtn
      .off(`click.${this.ns}Help`)
      .on(`click.${this.ns}Help`, () => {
        this._usedHintThisRound = true;
        this._showHintNote();
        this.$helpBtn.hide();
      });

    this.$skipBtn
      .off(`click.${this.ns}Skip`)
      .on(`click.${this.ns}Skip`, (e) => {
        e.preventDefault();
        this._finishRoundAsTimedOut();
      });

    if (!this._continueBound) {
      this._continueBound = true;
      $("#continue button")
        .off(`click.${this.ns}`)
        .on(`click.${this.ns}`, () => {
          $("#continue").hide();
          this._hideSkipRoundButton();
          this._hideTimeUpMessage();
          this._setTimedOutInteractivityDisabled(false);
          this._resetRoundTimerIfEnabled();
          this.prompt.setTone("blue");
          this.$accidentals.removeClass("invisible");
          this.newChallenge();
          this._syncPianoKeyboardStartNote();
          this._syncPianoKeyboardMarkerFromStaff();
          this._armUiGates({ resetInstructions: true });
          this.$checkBtn.enable();
        });
    }
  }

  _hideHelpButtonOnAnswerEdit(data = {}) {
    if (data?.source === "fixed") return;
    this.$helpBtn?.hide?.();
  }


  // ------------------------ UI gating ------------------------

  _instructionsAfterUserNotes() {
    const v = Number(this.opts.instructionsAfterUserNotes);
    return Number.isFinite(v) && v >= 0 ? Math.floor(v) : 1;
  }

  _checkAfterUserNotes() {
    const v = Number(this.opts.checkAfterUserNotes);
    return Number.isFinite(v) && v >= 0 ? Math.floor(v) : 1;
  }

  _currentUserNoteCount() {
    if (this.staff && typeof this.staff._userNoteCount === "function") {
      return this.staff._userNoteCount();
    }

    return this.$staffEl.find(".note").not(".fixed").not(".preview").not(".hint").length;
  }

  _armUiGates({ resetInstructions } = {}) {
    const needForInstructions = this._instructionsAfterUserNotes();
    const needForCheck = this._checkAfterUserNotes();

    if (resetInstructions) {
      this._instructionsRemoved = false;
      this.$instructions.show();
    }

    if (needForCheck <= 0) $("#check").show().removeClass("invisible");
    else $("#check").hide().addClass("invisible");

    this._userNotesSinceGate = 0;

    const syncUiGate = (count = this._currentUserNoteCount()) => {
      const userNoteCount = Number.isFinite(count) ? count : this._currentUserNoteCount();

      if (needForInstructions > 0 && userNoteCount < needForInstructions) {
        this._restoreInstructions();
      } else if (!this._instructionsRemoved && userNoteCount >= needForInstructions) {
        this._removeInstructions();
      }

      if (needForCheck <= 0 || userNoteCount >= needForCheck) {
        $("#check").show().removeClass("invisible");
      } else {
        $("#check").hide().addClass("invisible");
      }
    };

    syncUiGate();

    this.$staffEl
      .off(`staff:userNoteAdded._uiGate.${this.ns} staff:userNotesChanged._uiGate.${this.ns}`)
      .on(`staff:userNoteAdded._uiGate.${this.ns}`, () => {
        this._userNotesSinceGate += 1;
        this._hideHelpButtonOnAnswerEdit();

        syncUiGate();
      })
      .on(`staff:userNotesChanged._uiGate.${this.ns}`, (e, data) => {
        this._hideHelpButtonOnAnswerEdit(data);
        syncUiGate(Number(data?.count));
      });

    this.$staffEl
      .off(`staff:noteState._hideHelp.${this.ns}`)
      .on(`staff:noteState._hideHelp.${this.ns}`, (e, data) => {
        this._hideHelpButtonOnAnswerEdit(data);
      });
  }

  // ------------------------ fixed note selection ------------------------

  _fixedStepBounds() {
    return {
      min: this.staff.minStepAllowed(),
      max: this.staff.maxStepAllowed(),
    };
  }

  _randomFixedStep() {
    const { min, max } = this._fixedStepBounds();
    return Math.floor((this._duelRandom?.() ?? Math.random()) * (max - min + 1)) + min;
  }

  // ------------------------ hints ------------------------


  /**
   * Compute one hint note (legacy API).
   *
   * @returns {{step:number, accidentalClass?:string}|null}
   */
  _computeHintAnswer() {
    return null; // subclasses override
  }

  /**
   * Compute one or more hint notes.
   *
   * Subclasses can override this to return multiple notes (e.g. triads).
   *
   * @returns {Array<{id?:string, step:number|null, accidentalClass?:string|null}>}
   */
  _computeHintAnswers() {
    const single = this._computeHintAnswer();
    return single ? [single] : [];
  }

  _removeAllHintNotes() {
    const ids = Array.isArray(this._activeHintIds) ? this._activeHintIds : [];
    ids.forEach((id) => this.staff.removeNote(id));
    this._activeHintIds = [];
  }

  _removeAllUserNotesForHint() {
    const $userNotes = this.$staffEl.find(".note").not(".fixed").not(".preview").not(".hint");
    $userNotes.each((_, el) => {
      const id = el.getAttribute("data-note-id");
      if (id) this.staff.removeNote(id);
    });
  }

  _randomFreeStep() {
    const min = this.staff.minStepAllowed();
    const max = this.staff.maxStepAllowed();

    for (let tries = 0; tries < 50; tries++) {
      const step = Math.floor(Math.random() * (max - min + 1)) + min;
      if (!this.staff._isStepOccupied(step, null)) return step;
    }
    return null;
  }

  _attachHintBlinkRemoval(noteId) {
    const $note = this.$staffEl.find(`.note[data-note-id="${noteId}"]`);
    if (!$note.length) return;

    $note
      .off(`animationend.hint.${noteId} webkitAnimationEnd.hint.${noteId}`)
      .one(`animationend.hint.${noteId} webkitAnimationEnd.hint.${noteId}`, () => {
        this.staff.removeNote(noteId);
        this._activeHintIds = (this._activeHintIds || []).filter((x) => x !== noteId);
      });
  }

  _showHintNote() {
    this._removeAllHintNotes();
    this._removeAllUserNotesForHint();

    const answers = this._computeHintAnswers();
    const specs = (Array.isArray(answers) && answers.length) ? answers : [{ step: null, accidentalClass: null }];

    this._activeHintIds = [];

    for (let i = 0; i < specs.length; i++) {
      const ans = specs[i] || {};
      const id = ans.id || (specs.length === 1 ? "hint" : `hint${i + 1}`);

      let step = Number.isFinite(ans.step) ? Number(ans.step) : null;
      let accidentalClass = ans.accidentalClass || null;

      if (step == null) step = this._randomFreeStep();
      if (step == null) continue;

      const createdId = this.staff.addNote({
        id,
        step,
        className: "hint blink",
        allowOccupied: true,
        skipResolve: true,
      });

      if (!createdId) continue;

      this._activeHintIds.push(id);

      if (accidentalClass) {
        this.staff.attachAccidentalToNote(id, accidentalClass);
        this.$staffEl.find(`.accidental[data-for-note-id="${id}"]`).addClass("hint blink");
      }

      this.$staffEl.find(`.ledger[data-for-note-id="${id}"]`).addClass("hint blink");
      this._attachHintBlinkRemoval(id);
    }
  }

  // ------------------------ scoring / progress ------------------------

  _isPracticeMode() {
    const v = this.opts.practiceMode;
    return v === true || String(v || "").trim().toLowerCase() === "on";
  }

  _syncPracticeUi() {
    const practice = this._isPracticeMode();
    const $score = $("#score");

    if (practice) {
      $score.hide();
      this.$progressBar.parent().addClass("opacity-1");
    } else {
      $score.show();
      this.$progressBar.parent().removeClass("opacity-1");
    }
  }

  _successAnimation({ isBonus } = {}) {
    if (isBonus) this._playSuccessSfxBonus();
    else this._playSuccessSfxBasic();

    this.$helpBtn.hide();
    this.$accidentals.addClass("invisible");
    this.$feedback.find(".message span").text(pickOne(this.successPhrases));
    this.$feedback.stop(true, true).fadeIn("fast");
  }

  _handleCorrectAnswerUi({
    isBonus = false,
    earned = 0,
    $prompt = this.prompt.$root,
    $extraHide = $(),
    finalDelayMs = 1600,
  } = {}) {
    this._successAnimation({ isBonus });

    if (!this._instructionsRemoved) {
      this.$instructions.hide();
      this._instructionsRemoved = true;
    }

    if ($prompt?.length) $prompt.hide();
    if ($extraHide?.length) $extraHide.hide();

    $("#score").animateCSS && $("#score").animateCSS("heartBeat");
    if (earned > 0) this._showIncrement(earned);

    if (this._updateProgressBar() >= 100) {
      this._stats.finishedAtMs = Date.now();
      queueFinalResultsReveal({
        $button: this.$checkBtn,
        showFinalResults: () => this._showFinalResults(),
        delayMs: finalDelayMs,
      });
    } else {
      $("#check").hide();
      $("#continue").show();
    }
  }

  _runSuccessFeedbackTransition({ $interval = null, delayMs = 800, onDone = null } = {}) {
    if (this._successFeedbackTimeoutId != null) {
      clearTimeout(this._successFeedbackTimeoutId);
      this._successFeedbackTimeoutId = null;
    }

    if ($interval?.length) $interval.hide();

    const safeDelay = Math.max(0, Number(delayMs) || 0);
    this._successFeedbackTimeoutId = setTimeout(() => {
      this._successFeedbackTimeoutId = null;
      this.$feedback?.hide?.();
      if ($interval?.length) $interval.show();
      if (typeof onDone === "function") onDone();
    }, safeDelay);

    return this._successFeedbackTimeoutId;
  }

  _showIncrement(earned) {
    const $inc = this.$increment;
    if (!$inc || !$inc.length) return;

    $inc.stop(true, true);
    $inc.text(`+${earned}`).show();

    if ($inc.animateCSS) {
      $inc.animateCSS("fadeOutUp").then(() => $inc.hide());
    } else {
      setTimeout(() => $inc.hide(), 800);
    }
  }

  _showBonusBadge(bonusAmount) {
    if (!this.$bonusBadge || !this.$bonusBadge.length) return;

    if (!bonusAmount || bonusAmount <= 0) {
      this.$bonusBadge.hide();
      return;
    }

    this.$bonusBadge.text(`+${bonusAmount} BONUS`).show();

    if (this.$bonusBadge.animateCSS) {
      this.$bonusBadge.animateCSS("tada");
    }
  }

  _awardPointsForCorrect() {
    if (this._isPracticeMode()) {
      this._clearCorrectStreak();
      this.$points.text("0");
      this._showBonusBadge(0);
      return { earned: 0, firstTry: false, bonusEarned: 0 };
    }

    if (this._usedHintThisRound) {
      this._clearCorrectStreak();
      this._showBonusBadge(0);
      return { earned: 0, firstTry: false, bonusEarned: 0 };
    }

    const firstTry = !this._madeMistakeThisRound;
    const base = Number.isFinite(this.opts.basePoints) ? this.opts.basePoints : 1;
    const bonus = Number.isFinite(this.opts.firstTryBonus) ? this.opts.firstTryBonus : 0;

    const earned = firstTry ? base + bonus : base;
    const bonusEarned = firstTry ? bonus : 0;
    this._applyCorrectStreakForOutcome({ firstTry });

    this.points += earned;
    this.$points.text(String(this.points));
    this._showBonusBadge(bonusEarned);

    return { earned, firstTry, bonusEarned };
  }

  getCorrectStreak() {
    return Math.max(0, Number(this._correctStreak) || 0);
  }

  _syncStreakBarClass() {
    if (!this.$progressBar?.length) return;
    const active = this.getCorrectStreak() >= 3;
    this.$progressBar.toggleClass("streak-bar", !!active);
  }

  _applyCorrectStreakForOutcome({ firstTry } = {}) {
    if (!firstTry) {
      this._clearCorrectStreak();
      return;
    }
    this._correctStreak += 1;
    this._syncStreakBarClass();
    if (this._correctStreak <= 1) {
      // eslint-disable-next-line no-console
      console.log("[BaseStaffGame] Correct answer. No streak yet.");
    } else {
      // eslint-disable-next-line no-console
      console.log(`[BaseStaffGame] Streak ${this._correctStreak}x`);
    }
  }

  _clearCorrectStreak() {
    this._correctStreak = 0;
    this._syncStreakBarClass();
  }

  _resetProgress() {
    this._syncPracticeUi();
    this._clearCorrectStreak();
    this._madeAnyMistake = false;
    this.$progressBar.data("progress", 0);
    this.$progressBar.css({ width: "0%" });
    this.$progressBar.removeClass("streak-bar");
    if (this._isPracticeMode()) this.$progressCounter.text("Practice");
    else this.$progressCounter.text("");
  }

  _updateProgressBar() {
    if (this._isPracticeMode()) {
      this.$progressBar.data("progress", 0);
      this.$progressBar.css({ width: "0%" });
      this.$progressCounter.text("Practice");
      return 0;
    }

    const steps = Math.max(1, this.numOfChallenges || 1);
    const increment = 100 / steps;
    let current = parseFloat(this.$progressBar.data("progress")) || 0;

    current = Math.min(100, current + increment);

    this.$progressBar.data("progress", current);
    this.$progressBar.css({ width: `${current}%` });

    const completed = Math.min(steps, Math.max(0, Math.round(current / increment)));
    this.$progressCounter.text(`${completed} of ${steps}`);

    return current;
  }

  _isCheckFailTarget($target) {
    if (!$target?.length || !this.$checkBtn?.length) return false;
    return $target.is(this.$checkBtn) || $target.has(this.$checkBtn).length > 0;
  }

  _showCheckWrongState() {
    if (!this.$checkBtn?.length) return;

    if (this._checkWrongStateTimeout != null) {
      clearTimeout(this._checkWrongStateTimeout);
      this._checkWrongStateTimeout = null;
    }

    this.$checkBtn
      .removeClass("animate__animated animate__shakeX")
      .attr("state", "wrong")
      .attr("disabled", "disabled")
      .prop("disabled", true);

    this._checkWrongStateTimeout = setTimeout(() => {
      this.$checkBtn
        .attr("state", "waiting")
        .removeAttr("disabled")
        .prop("disabled", false);
      this._checkWrongStateTimeout = null;
    }, 2000);
  }

  _failAnimation($shakeTarget) {
    this._clearCorrectStreak();
    this._playFailSfx();
    this._removeInstructions();

    const $target = $shakeTarget || this.$checkWrap;

    if (this._isCheckFailTarget($target)) {
      $target.removeClass("animate__animated animate__shakeX");
      this._showCheckWrongState();
      return;
    }

    $target.removeClass("animate__animated animate__shakeX");
    // eslint-disable-next-line no-unused-expressions
    $target[0] && $target[0].offsetWidth;
    $target.addClass("animate__animated animate__shakeX");

    $target
      .off(`animationend._fail.${this.ns} webkitAnimationEnd._fail.${this.ns} oAnimationEnd._fail.${this.ns} MSAnimationEnd._fail.${this.ns}`)
      .one(`animationend._fail.${this.ns} webkitAnimationEnd._fail.${this.ns} oAnimationEnd._fail.${this.ns} MSAnimationEnd._fail.${this.ns}`, () => {
        $target.removeClass("animate__animated animate__shakeX");
        this.$checkBtn.enable();
      });
  }

  _removeInstructions() {
    if (this._instructionsRemoved) return;
    this.$instructions.hide();
    this._instructionsRemoved = true;
  }

  _restoreInstructions() {
    if (!this._instructionsRemoved) return;
    this.$instructions.show();
    this._instructionsRemoved = false;
  }

  _bonusPointSettingKeys() {
    return Array.isArray(this.opts?.bonusPoints)
      ? this.opts.bonusPoints.map((key) => String(key || "").trim()).filter(Boolean)
      : [];
  }

  _hasConfiguredScoreDoubleBonus(accuracy) {
    if (Number(accuracy) !== 100) return false;
    const keys = BaseStaffGame.prototype._bonusPointSettingKeys.call(this);
    return keys.some((key) => BaseStaffGame.prototype._normalizeOnOff.call(this, this.opts?.[key]));
  }

  _buildFinalScoreSummary({ basePoints = 0, accuracy = 0, perfectGame = false } = {}) {
    const normalizedBasePoints = Number(basePoints) || 0;
    const initialScore = perfectGame ? normalizedBasePoints * 2 : normalizedBasePoints;
    const settingsBonus = BaseStaffGame.prototype._hasConfiguredScoreDoubleBonus.call(this, accuracy);
    const finalScore = settingsBonus ? initialScore * 2 : initialScore;

    return {
      perfectGame: !!perfectGame,
      settingsBonus,
      initialScore,
      finalScore,
    };
  }

  _showFinalResults() {
    if (this._isPracticeMode()) return;

    const total = Math.max(0, this._stats.checksTotal ?? 0);
    const correct = Math.max(0, this._stats.checksCorrect ?? 0);
    const accuracy = total ? Math.round((correct / total) * 100) : 0;

    const endMs = this._stats.finishedAtMs ?? Date.now();
    const totalSeconds = Math.max(0, Math.floor((endMs - (window.__activeDuel ? Date.parse(window.__activeDuel.state.starts_at) : PAGE_OPENED_AT_MS)) / 1000));

    const perfectGame = total > 0 && !this._madeAnyMistake;
    const scoreSummary = this._buildFinalScoreSummary({
      basePoints: this.points,
      accuracy,
      perfectGame,
    });

    if (perfectGame) {
      setTimeout(() => {
        this.$doublePoints?.show?.();
        this._playPerfectGameBonusSfx();
      }, 1750);

      // eslint-disable-next-line no-console
      console.log("[BaseStaffGame] Perfect game! 2x bonus applied.", {
        basePoints: this.points,
        finalPoints: scoreSummary.initialScore,
      });
    } else {
      // eslint-disable-next-line no-console
      console.log("[BaseStaffGame] No perfect-game bonus.", {
        basePoints: this.points,
        finalPoints: scoreSummary.initialScore,
        madeAnyMistake: this._madeAnyMistake,
      });
    }

    if (scoreSummary.settingsBonus) {
      // eslint-disable-next-line no-console
      console.log("[BaseStaffGame] Extra doubling is happening because the user selected challenging settings.", {
        scoreBeforeSettingsBonus: scoreSummary.initialScore,
        finalPoints: scoreSummary.finalScore,
      });
    }

    renderFinalResultsOverlay({
      $finalOverlay: this.$finalOverlay,
      rounds: this.numOfChallenges,
      score: scoreSummary.finalScore,
      accuracy,
      durationSec: totalSeconds,
      settingsBonus: scoreSummary.settingsBonus,
      clearCountupTimers: () => this._clearFinalCountupTimers(),
      countupTimers: this._finalCountupTimeouts,
      animateMetrics: () => this._animateFinalMetricsWithSfx(),
      playFinalSfx: () => this._playFinalSfx(),
    });
  }
}
