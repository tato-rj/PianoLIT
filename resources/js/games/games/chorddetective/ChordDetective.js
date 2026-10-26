// resources/js/music/games/chorddetective/ChordDetective.js
import { BaseStaffGame } from "../base/BaseStaffGame.js";
import {
  pickOne,
  pickWeighted,
  randomInt,
  toArrayMaybe,
} from "../../staff/staffUtils.js";
import { GameAudio } from "../shared/GameAudio.js";
import {
  accidentalClassFromOffset,
  fixedNoteToStaffPosition,
  normalizeClefPool,
  pickChallengeClef,
} from "../shared/challengeUtils.js";

/**
 * Chord Detective
 *
 * Rules:
 * - Staff note sounds are always disabled (no click/drag audio).
 * - SFX follow the global sound toggle (BaseStaffGame / opts.sound).
 * - Dictation playback always plays (ignores opts.sound) via its own synth.
 * - The player enters the two heard notes that complete the triad from the fixed anchor.
 * - Answer checking is pitch-only for those notes (enharmonic spellings are accepted).
 */
export class ChordDetective extends BaseStaffGame {
  static BASS_MIN_STEP = 4; // Third line of the bass staff (D3).

  static TRIAD_QUALITY_FULL_NAME_MAP = {
    major: "major",
    minor: "minor",
    augmented: "augmented",
    diminished: "diminished",
  };

  constructor(options = {}) {
    const defaults = {
      staffEl: "#staff",
      basePoints: 1,
      firstTryBonus: 2,
      namespace: "chordDetective",
      allowInversions: false,

      // Dictation: two user notes complete the heard triad.
      maxUserNotes: 2,

      // UI gating: show Check after 2 user notes.
      instructionsAfterUserNotes: 2,
      checkAfterUserNotes: 2,
    };

    const merged = {
      ...defaults,
      ...options,
      accidentalWeights: {
        ...(defaults.accidentalWeights || {}),
        ...(options.accidentalWeights || {}),
      },
      triadQualities:
        Array.isArray(options.triadQualities) && options.triadQualities.length
          ? options.triadQualities.slice()
          : Object.keys(ChordDetective.TRIAD_QUALITY_FULL_NAME_MAP),
    };
    merged.maxUserNotes = 2;
    merged.instructionsAfterUserNotes = 2;
    merged.checkAfterUserNotes = 2;

    const clefPool = normalizeClefPool(
      merged.clefs != null ? merged.clefs : merged.clef,
    );

    super({
      ...merged,
      initialClef: clefPool && clefPool[0] ? clefPool[0] : "treble",
    });

    this._clefPool = clefPool;

    // Dictation state
    this._expectedFirst = null; // { noteId, step, accidentalClass }
    this._expectedChordNotes = []; // [{ step, accidentalClass, accOff, midi }]
    this._currentTriadQuality = null;
    this._currentChordDirection = 1; // 1=up, -1=down
    this._currentChordInversion = 0; // 0=root position, 1=first inversion, 2=second inversion

    // Dictation playback
    this._dictationTimeouts = [];
    this._dictSynth = null;
    this._dictAudioReady = false;

    // Play/Stop UI
    this.$playWrap = null;
    this.$playPlayBtn = null;
    this.$playStopBtn = null;
    this._hasPlayedThisRound = false;
  }

  // ------------------------ lifecycle ------------------------

  start() {
    super.start();

    // Dictation: never play sounds for user-added/moved staff notes.
    if (this.staff && typeof this.staff._soundEnabled === "function") {
      this.staff._soundEnabled = () => false;
    }

    this.$playWrap = $("#play");
    this.$playPlayBtn = this.$playWrap.find('button[action="play"]');
    this.$playStopBtn = this.$playWrap.find('button[action="stop"]');

    this.$playPlayBtn
      .off(`click.${this.ns}Play`)
      .on(`click.${this.ns}Play`, (e) => {
        e.preventDefault();
        this.playDictation();
      });

    this.$playStopBtn
      .off(`click.${this.ns}Stop`)
      .on(`click.${this.ns}Stop`, (e) => {
        e.preventDefault();
        this._stopDictationPlayback();
        this._setPlayButtons(false);
        this._setInstructions("Press Play whenever you’d like to hear it again.");
      });

    this.$staffEl
      .off(`staff:userNoteAdded._answerControls.${this.ns} staff:userNotesChanged._answerControls.${this.ns}`)
      .on(
        `staff:userNoteAdded._answerControls.${this.ns} staff:userNotesChanged._answerControls.${this.ns}`,
        (e, data) => this._syncAnswerControls(Number(data?.count)),
      );

    // Ensure Play is shown on Continue (new round).
    $("#continue button")
      .off(`click.${this.ns}ShowPlay`)
      .on(`click.${this.ns}ShowPlay`, () => {
        this._setPlayButtons(false);
        this._syncAnswerControls(0);
      });

    this._setPlayButtons(false);
    this._syncAnswerControls();
  }

  // ------------------------ play/stop UI ------------------------

  _setPlayButtons(isPlaying) {
    if (this.$playPlayBtn?.length) this.$playPlayBtn.toggle(!isPlaying);
    if (this.$playStopBtn?.length) this.$playStopBtn.toggle(!!isPlaying);
  }

  _setInstructions(message) {
    this.instructionsUi.show().setHtml(message, { animate: false });
  }

  _removeInstructions() {
    this.$instructions.show();
    this._instructionsRemoved = true;
  }

  _restoreInstructions() {
    this.$instructions.show();
    this._instructionsRemoved = false;
  }

  _syncAnswerControls(count = this._currentUserNoteCount()) {
    const userNoteCount = Number.isFinite(count) ? count : this._currentUserNoteCount();
    const hasAnswer = userNoteCount >= this._checkAfterUserNotes();
    const roundControlsHidden =
      this.$staffEl?.attr?.("aria-disabled") === "true" ||
      $("#continue").is(":visible") ||
      $("#final-overlay").is(":visible");
    const showPlay = !roundControlsHidden && !hasAnswer;
    const showCheck = !roundControlsHidden && hasAnswer;

    if (showCheck) {
      this._stopDictationPlayback();
      this._setPlayButtons(false);
    }

    this.$playWrap?.toggle?.(showPlay);
    this.$checkWrap?.toggle?.(showCheck).toggleClass?.("invisible", !showCheck);

    if (!roundControlsHidden) {
      let message = "Press Play when you’re ready.";
      if (hasAnswer) message = "When you’re ready, check your answer.";
      else if (userNoteCount === 1) message = "Add one more note.";
      else if (this._hasPlayedThisRound) message = "Add the two notes you heard.";
      this._setInstructions(message);
    }
  }

  // ------------------------ dictation playback ------------------------

  playDictation() {
    if (!this._expectedFirst || !this._expectedChordNotes.length) return;

    this._stopDictationPlayback();
    this._setPlayButtons(true);
    this._hasPlayedThisRound = true;
    this._setInstructions("Listen closely…");

    const firstMidi =
      this.staff._stepToMidi(this._expectedFirst.step) +
      (this.staff._accidentalClassToOffset(this._expectedFirst.accidentalClass) || 0);
    const chordMidis = [firstMidi, ...this._expectedChordNotes.map((note) => note.midi)];

    // Sequence: anchor -> chord tone -> chord tone -> chord tone -> anchor -> full triad.
    this._playMidi(firstMidi, 0.6, 0.0, () => this._scheduleInitialNoteFlicker(0.6, 0.0));
    this._playMidi(this._expectedChordNotes[0].midi, 0.6, 1.0);
    this._playMidi(this._expectedChordNotes[1].midi, 0.6, 2.0);
    this._playMidi(this._expectedChordNotes[0].midi, 0.6, 3.0);
    this._playMidi(firstMidi, 0.6, 4.0, () => this._scheduleInitialNoteFlicker(0.6, 4.0));

    this._playMidiGroup(chordMidis, 0.8, 5.0, () => this._scheduleInitialNoteFlicker(0.8, 5.0));

    this._dictationTimeouts.push(
      setTimeout(() => {
        // eslint-disable-next-line no-console
        console.log("Dictation: user can now write the chord tones on the staff.");
        this._setPlayButtons(false);
        this._setInstructions("Add the two notes you heard.");
      }, 5200),
    );
  }

  _stopDictationPlayback() {
    if (Array.isArray(this._dictationTimeouts)) {
      this._dictationTimeouts.forEach((t) => clearTimeout(t));
    }
    this._dictationTimeouts = [];
    this._clearInitialNoteFlicker();

    if (this._dictSynth && this._dictSynth.dispose) {
      try {
        this._dictSynth.dispose();
      } catch (_) {}
    }
    this._dictSynth = null;
    this._dictAudioReady = false;
  }

  async _ensureDictationAudio() {
    if (!window.Tone) return;
    if (this._dictAudioReady) return;

    await Tone.start();

    // Poly synth so we can play two notes together.
    this._dictSynth = GameAudio.createDictationSynth();

    this._dictAudioReady = true;
  }

  _playMidi(midi, durSeconds, atSecondsFromNow, onPlay = null) {
    if (!window.Tone) return;

    this._ensureDictationAudio().then(() => {
      if (!this._dictSynth) return;
      const now = Tone.now();
      const when = now + (Number(atSecondsFromNow) || 0);
      const dur = Number(durSeconds) || 0.6;
      this._dictSynth.triggerAttackRelease(
        Tone.Frequency(midi, "midi"),
        dur,
        when,
        GameAudio.scale("dictation", 1),
      );
      if (typeof onPlay === "function") onPlay();
    });
  }

  _playMidiGroup(midis, durSeconds, atSecondsFromNow, onPlay = null) {
    if (!window.Tone) return;
    const notes = Array.isArray(midis)
      ? midis.filter((midi) => Number.isFinite(midi))
      : [];
    if (!notes.length) return;

    this._ensureDictationAudio().then(() => {
      if (!this._dictSynth) return;
      const now = Tone.now();
      const when = now + (Number(atSecondsFromNow) || 0);
      const dur = Number(durSeconds) || 0.8;
      this._dictSynth.triggerAttackRelease(
        notes.map((midi) => Tone.Frequency(midi, "midi")),
        dur,
        when,
        GameAudio.scale("dictation", 1),
      );
      if (typeof onPlay === "function") onPlay();
    });
  }

  _initialNoteEl() {
    const noteId = this._expectedFirst?.noteId;
    if (!noteId) return $();

    return this.$staffEl.find(".note").filter((_, el) => (
      String(el.getAttribute("data-note-id") || "") === String(noteId)
    )).first();
  }

  _clearInitialNoteFlicker() {
    this._initialNoteEl().removeClass("flickering");
  }

  _scheduleInitialNoteFlicker(durSeconds, atSecondsFromNow) {
    const delayMs = Math.max(0, (Number(atSecondsFromNow) || 0) * 1000);
    const durationMs = Math.max(0, (Number(durSeconds) || 0.6) * 1000);

    const startId = setTimeout(() => {
      const $note = this._initialNoteEl();
      if (!$note.length) return;

      if (!$note.find(".note-playback-icon").length) {
        $note.append('<i class="fa-solid fa-volume-high note-playback-icon" aria-hidden="true"></i>');
      }
      $note.addClass("flickering");

      const endId = setTimeout(() => {
        $note.removeClass("flickering");
      }, durationMs);

      this._dictationTimeouts.push(endId);
    }, delayMs);

    this._dictationTimeouts.push(startId);
  }

  // ------------------------ selection ------------------------

  _pickTriadQuality() {
    const pool =
      Array.isArray(this.opts.triadQualities) && this.opts.triadQualities.length
        ? this.opts.triadQualities
        : Object.keys(ChordDetective.TRIAD_QUALITY_FULL_NAME_MAP);
    return pool[Math.floor((this._duelRandom?.() ?? Math.random()) * pool.length)];
  }

  _directionPool() {
    const raw = Array.isArray(this.opts.direction)
      ? this.opts.direction
      : this.opts.direction != null
        ? [this.opts.direction]
        : ["up", "down"];

    const pool = raw
      .map((direction) => String(direction || "").trim().toLowerCase())
      .filter((direction) => direction === "up" || direction === "down");

    return pool.length ? [...new Set(pool)] : ["up", "down"];
  }

  _pickDirection() {
    return pickOne(this._directionPool(), this._duelRandom);
  }

  _directionValue(direction) {
    return String(direction || "").trim().toLowerCase() === "down" ? -1 : 1;
  }

  _allowInitialAccidentals() {
    return this._normalizeOnOff(this.opts.allowAccidentals);
  }

  _pickInitialAccidentalClass() {
    if (!this._allowInitialAccidentals()) return null;

    const w = this.opts.accidentalWeights || {};
    return pickWeighted([
      { value: null, weight: Number(w.natural) || 0 },
      { value: "music-font__sharp", weight: Number(w.sharp) || 0 },
      { value: "music-font__flat", weight: Number(w.flat) || 0 },
    ], this._duelRandom);
  }

  _normalizeInitialNoteAccidental(note) {
    if (!note || this._allowInitialAccidentals()) return note;
    return { ...note, accidentalClass: null };
  }

  _initialNoteAccidentalAllowed(note) {
    if (this._allowInitialAccidentals()) return true;
    const accidentalClass = note?.accidentalClass || null;
    return accidentalClass == null || accidentalClass === "music-font__natural";
  }

  _pickFixedNote() {
    const fixedList = toArrayMaybe(this.opts.fixedNotes).filter(Boolean);
    if (fixedList.length) {
      const eligibleFixedNotes = fixedList
        .map((note) => this._normalizeInitialNoteAccidental(
          fixedNoteToStaffPosition(this.staff, note),
        ))
        .filter((note) => note && this._isChallengeStepAllowed(note.step));

      if (eligibleFixedNotes.length) return pickOne(eligibleFixedNotes, this._duelRandom);
    }

    return {
      step: this._randomFixedStep(),
      accidentalClass: this._pickInitialAccidentalClass(),
    };
  }

  _randomFixedStep() {
    const { min, max } = this._challengeStepBounds();
    return Math.floor((this._duelRandom?.() ?? Math.random()) * (max - min + 1)) + min;
  }

  _challengeStepBounds() {
    const staffMin = this.staff.minStepAllowed();
    const min = this.staff.getClef() === "bass"
      ? Math.max(staffMin, ChordDetective.BASS_MIN_STEP)
      : staffMin;

    return { min, max: this.staff.maxStepAllowed() };
  }

  _isChallengeStepAllowed(step) {
    const { min, max } = this._challengeStepBounds();
    return Number.isFinite(step) && step >= min && step <= max;
  }

  // ------------------------ triad math ------------------------

  _triadExpectedSemisForQuality(quality) {
    const triads = {
      major: [4, 7],
      minor: [3, 7],
      augmented: [4, 8],
      diminished: [3, 6],
    };
    return triads[String(quality || "").trim()] || null;
  }

  _toneAccidentalClass(rootMidi, targetStep, targetSemis) {
    const naturalMidi = this.staff._stepToMidi(targetStep);
    let offset = (rootMidi + targetSemis) - naturalMidi;

    while (offset > 6) offset -= 12;
    while (offset < -6) offset += 12;

    const accClass = accidentalClassFromOffset(offset);
    if (accClass == null && offset !== 0) return null;
    return accClass;
  }

  _computeChordNotesFromRoot(quality, rootStep, rootMidi, direction) {
    const expected = this._triadExpectedSemisForQuality(quality);
    if (!expected) return null;

    const dir = this._directionValue(direction);
    const { min: minStep, max: maxStep } = this._challengeStepBounds();
    const roles = [
      { stepOffset: 2, semis: expected[0] },
      { stepOffset: 4, semis: expected[1] },
    ];

    const notes = [];
    for (const role of roles) {
      const targetStep = rootStep + dir * role.stepOffset;
      if (targetStep < minStep || targetStep > maxStep) return null;

      const targetSemis = dir * role.semis;
      const accidentalClass = this._toneAccidentalClass(rootMidi, targetStep, targetSemis);
      if (accidentalClass == null && targetSemis !== 0) return null;

      const midi = rootMidi + targetSemis;
      const accOff = midi - this.staff._stepToMidi(targetStep);
      notes.push({ step: targetStep, accidentalClass, accOff, midi });
    }

    return notes;
  }

  _triadRoleSpecs(expected) {
    return [
      { role: 0, stepOffset: 0, semis: 0 },
      { role: 2, stepOffset: 2, semis: expected[0] },
      { role: 4, stepOffset: 4, semis: expected[1] },
    ];
  }

  _voicingToneFromRoot(rootStep, rootMidi, roleSpecs, roleIndex, octaveShift = 0) {
    const role = roleSpecs[roleIndex];
    if (!role) return null;

    const step = rootStep + role.stepOffset + (7 * octaveShift);
    const semis = role.semis + (12 * octaveShift);
    const { min: minStep, max: maxStep } = this._challengeStepBounds();
    if (step < minStep || step > maxStep) return null;

    const accidentalClass = this._toneAccidentalClass(rootMidi, step, semis);
    if (accidentalClass == null && semis !== 0) return null;

    const midi = rootMidi + semis;
    const accOff = midi - this.staff._stepToMidi(step);
    return { step, accidentalClass, accOff, midi };
  }

  _computeChordVoicingFromRoot(quality, rootStep, rootMidi, direction, inversion = 0) {
    const expected = this._triadExpectedSemisForQuality(quality);
    if (!expected) return null;

    const roleSpecs = this._triadRoleSpecs(expected);
    const dir = this._directionValue(direction);
    const anchorIndex = Math.max(0, Math.min(2, Number(inversion) || 0));
    const fixed = this._voicingToneFromRoot(rootStep, rootMidi, roleSpecs, anchorIndex, 0);
    if (!fixed) return null;

    const chordNotes = [];
    for (let offset = 1; offset <= 2; offset += 1) {
      const rawIndex = anchorIndex + (dir * offset);
      const octaveShift = Math.floor(rawIndex / roleSpecs.length);
      const roleIndex = ((rawIndex % roleSpecs.length) + roleSpecs.length) % roleSpecs.length;
      const note = this._voicingToneFromRoot(rootStep, rootMidi, roleSpecs, roleIndex, octaveShift);
      if (!note) return null;
      chordNotes.push(note);
    }

    return { fixed, chordNotes };
  }

  _pickInvertedVoicingFromFixed(quality, fixed, direction) {
    const expected = this._triadExpectedSemisForQuality(quality);
    if (!expected || !fixed) return null;

    const roleSpecs = this._triadRoleSpecs(expected);
    const anchor = this._normalizeInitialNoteAccidental(fixed);
    const fixedAccOff = this.staff._accidentalClassToOffset(anchor.accidentalClass) || 0;
    const fixedMidi = this.staff._stepToMidi(anchor.step) + fixedAccOff;
    const candidates = [];

    roleSpecs.forEach((role, inversion) => {
      const rootStep = anchor.step - role.stepOffset;
      const rootMidi = fixedMidi - role.semis;
      const rootOff = rootMidi - this.staff._stepToMidi(rootStep);
      const rootAccidentalClass = accidentalClassFromOffset(rootOff);
      if (rootAccidentalClass == null && rootOff !== 0) return;

      const voicing = this._computeChordVoicingFromRoot(
        quality,
        rootStep,
        rootMidi,
        direction,
        inversion,
      );
      if (!voicing?.chordNotes?.length) return;

      candidates.push({
        inversion,
        fixed: {
          ...anchor,
          midi: fixedMidi,
          accOff: fixedAccOff,
        },
        chordNotes: voicing.chordNotes,
      });
    });

    return candidates.length ? pickOne(candidates, this._duelRandom) : null;
  }

  _pickRandomInvertedVoicing(quality, direction) {
    const expected = this._triadExpectedSemisForQuality(quality);
    if (!expected) return null;

    const minStep = this.staff.minStepAllowed();
    const maxStep = this.staff.maxStepAllowed();

    for (let i = 0; i < 80; i += 1) {
      const rootStep = randomInt(minStep, maxStep, this._duelRandom);
      const rootAccidentalClass = this._pickInitialAccidentalClass();
      const rootMidi =
        this.staff._stepToMidi(rootStep) +
        (this.staff._accidentalClassToOffset(rootAccidentalClass) || 0);
      const inversion = pickOne([0, 1, 2], this._duelRandom);
      const voicing = this._computeChordVoicingFromRoot(
        quality,
        rootStep,
        rootMidi,
        direction,
        inversion,
      );
      if (!voicing?.chordNotes?.length) continue;
      if (!this._initialNoteAccidentalAllowed(voicing.fixed)) continue;

      return {
        inversion,
        fixed: voicing.fixed,
        chordNotes: voicing.chordNotes,
      };
    }

    return null;
  }

  // ------------------------ game flow ------------------------

  newChallenge() {
    this._hasPlayedThisRound = false;
    this._setPlayButtons(false);
    this._syncAnswerControls(0);

    this.$helpBtn.hide();
    this._fixedState = null;
    this._clearInitialNoteFlicker();
    this._expectedFirst = null;
    this._expectedChordNotes = [];
    this._currentTriadQuality = null;
    this._currentChordInversion = 0;

    const clef = pickChallengeClef(this._clefPool, this._duelRandom);
    if (clef && clef !== this.staff.getClef()) this.staff.setClef(clef);

    this._madeMistakeThisRound = false;
    this._usedHintThisRound = false;
    this.$bonusBadge.hide();
    this.$doublePoints?.hide?.();

    const quality = this._pickTriadQuality();
    const direction = this._pickDirection();
    this._currentTriadQuality = quality;
    this._currentChordDirection = this._directionValue(direction);

    // Retry until we find a playable triad within staff bounds/accidentals.
    let fixed = null;
    let chordNotes = null;
    let inversion = 0;

    for (let i = 0; i < 80; i += 1) {
      if (this.opts.allowInversions) {
        const fixedList = toArrayMaybe(this.opts.fixedNotes).filter(Boolean);
        const voicing = fixedList.length
          ? this._pickInvertedVoicingFromFixed(quality, this._pickFixedNote(), direction)
          : this._pickRandomInvertedVoicing(quality, direction);
        if (!voicing?.fixed || !voicing?.chordNotes?.length) continue;

        fixed = voicing.fixed;
        chordNotes = voicing.chordNotes;
        inversion = voicing.inversion || 0;
        break;
      }

      fixed = this._pickFixedNote();
      if (!fixed) continue;

      const fixedAccOff = this.staff._accidentalClassToOffset(fixed.accidentalClass) || 0;
      const fixedMidi = this.staff._stepToMidi(fixed.step) + fixedAccOff;

      chordNotes = this._computeChordNotesFromRoot(quality, fixed.step, fixedMidi, direction);
      if (chordNotes?.length === 2) break;
    }

    if (!fixed || !chordNotes?.length) return;
    this._currentChordInversion = inversion;

    this.staff.clearNotes();
    this.$accidentals.removeClass("invisible");
    this.$feedback.hide();

    // Show the initial note immediately (suppress natural glyph).
    const fixedAcc =
      fixed.accidentalClass === "music-font__natural" ? null : (fixed.accidentalClass || null);

    const fixedId = this.staff.addFixedNote({
      step: fixed.step,
      accidentalClass: fixedAcc,
    });
    if (fixedId) this.staff._emitNoteState(fixedId, "fixed");

    this._expectedFirst = {
      noteId: fixedId,
      step: fixed.step,
      accidentalClass: fixed.accidentalClass || null,
    };
    this._expectedChordNotes = chordNotes;

    $("#continue").hide();
  }

  // ------------------------ notes + hint ------------------------

  _notesOnStaffOrdered() {
    const $notes = this.$staffEl.find(".note").not(".preview").not(".hint");
    const notes = $notes.toArray().map((el) => {
      const id = el.getAttribute("data-note-id");
      const step = this.staff._stepOfNoteEl(el);
      const accCls = this.staff._getAttachedAccidentalClass(id);
      const accOff = this.staff._accidentalClassToOffset(accCls) || 0;
      const fixed = el.classList.contains("fixed");
      return { id, step, accOff, fixed };
    });

    notes.sort((a, b) => a.step - b.step);
    return notes;
  }

  _computeHintAnswers() {
    return this._expectedChordNotes.map((note, index) => ({
      id: `hint${index + 1}`,
      step: note.step,
      accidentalClass: note.accidentalClass || null,
    }));
  }

  _showHintNote() {
    super._showHintNote();
    this._setInstructions("Here’s a hint—watch the correct notes.");
  }

  _showTimeUpMessage() {
    super._showTimeUpMessage();
    this._setInstructions("Time’s up. Let’s try another one.");
  }

  // ------------------------ evaluation ------------------------

  _onCheck() {
    this._stopDictationPlayback();
    this._setPlayButtons(false);

    const notes = this._notesOnStaffOrdered();
    this.$checkBtn.disable();

    this._stats.checksTotal += 1;

    if (!this._expectedChordNotes.length) return;

    // fixed root + 2 user notes
    if (notes.length !== 3) {
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this._shakeWrongUserStaffNotes();
      this._failAnimation(this.$checkWrap);
      this.$helpBtn.show();
      this._setInstructions("Not quite—adjust the notes and try again.");
      return;
    }

    const userNotes = notes.filter((n) => !n.fixed);
    if (userNotes.length !== 2) {
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this._shakeWrongUserStaffNotes();
      this._failAnimation(this.$checkWrap);
      this.$helpBtn.show();
      this._setInstructions("Not quite—adjust the notes and try again.");
      return;
    }

    // Pitch-only correctness (enharmonics accepted), order-independent.
    const expectedMidis = this._expectedChordNotes
      .map((note) => note.midi)
      .sort((a, b) => a - b);
    const userMidis = userNotes
      .map((note) => this.staff._stepToMidi(note.step) + (note.accOff || 0))
      .sort((a, b) => a - b);
    const ok =
      expectedMidis.length === userMidis.length &&
      expectedMidis.every((midi, index) => midi === userMidis[index]);

    if (ok) {
      this._stats.checksCorrect += 1;
      this._pauseGameTimer();

      const { earned, bonusEarned } = this._awardPointsForCorrect();
      this._handleCorrectAnswerUi({
        isBonus: bonusEarned > 0,
        earned,
        $prompt: this.prompt.$root,
        $extraHide: this.$playWrap,
      });
      this._setInstructions("You got it! Continue when you’re ready.");
    } else {
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this._shakeWrongUserStaffNotes();
      this._failAnimation(this.$checkWrap);
      this.$helpBtn.show();
      this._setInstructions("Not quite—adjust the notes and try again.");
    }
  }
}
