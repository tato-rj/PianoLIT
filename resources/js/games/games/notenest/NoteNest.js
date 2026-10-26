import { BaseStaffGame } from "../base/BaseStaffGame.js";
import { normalizeClefPool, pickChallengeClef } from "../shared/challengeUtils.js";
import { displayNoteName, noteNameFromMidi } from "../shared/noteNames.js";
import {
  createStablePitchState,
  detectPlayedNotePitch,
  frequencyToMidi,
  updateStablePitchState,
} from "../shared/playedNotePitch.js";
import { accidentalClassToText, pickWeighted, stepToLetterOctave } from "../../staff/staffUtils.js";

export class NoteNest extends BaseStaffGame {
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
      namespace: "noteNest",
    };

    const merged = { ...defaults, ...(options || {}) };
    const clefPool = normalizeClefPool(
      merged.clefs != null ? merged.clefs : merged.clef,
    );

    super({
      ...merged,
      initialClef: (clefPool && clefPool[0]) ? clefPool[0] : "treble",
    });

    this._clefPool = clefPool;
    this._targetNote = null;
    this._lastTargetName = null;
    this._blockMarkerClass = "block-marker";
    this._lastPlayedNote = null;
    this.$playNoteWrap = $("#play-note");
    this.$playNoteStart = this.$playNoteWrap.find("#play-note-start");
    this.$playNoteBtn = this.$playNoteStart.find("button");
    this.$playNoteStatus = this.$playNoteWrap.find("#play-note-status");
    this._playNoteButtonDefaultHtml = this.$playNoteBtn.html();
    if (this._requiresPlayedNote()) this.$playNoteStatus.after(this.$checkWrap);
    this.$playFeedback = $("#play-feedback");
    this.$playFeedbackText = this.$playFeedback.find(".play-feedback-text");
    this._playedNoteConfirmed = false;
    this._pitchAudioContext = null;
    this._pitchStream = null;
    this._pitchSource = null;
    this._pitchAnalyser = null;
    this._pitchData = null;
    this._pitchFrame = null;
    this._pitchStartFrame = null;
    this._heardStatusTimer = null;
    this._pitchRequestId = 0;
    this._pitchInputStarting = false;
    this._pitchInputUnavailable = false;
    this._hideInstructionsForMic = false;
    this._stablePitch = createStablePitchState();
    this._ignoreAppAudioUntil = 0;
    this._microphoneSettings = merged.microphoneSettings || {};
  }

  start() {
    super.start();
    this._wirePlayedNoteTracking();
    this._bindBlockMarkerDismiss();
    this.prompt.show();
    this._setPromptForTarget(this._targetNote);
  }

  _requiresPlayedNote() {
    return this._normalizeOnOff(this.opts.requirePlayedNote);
  }

  _resetPlayedNote() {
    this._stopPitchInput();
    this._hideInstructionsForMic = false;
    this._lastPlayedNote = null;
    this._playedNoteConfirmed = false;
    this._setPlayFeedbackState("idle");
    this._setPlayNoteButtonLabel("default");
    this._showPlayNoteActions();
  }

  _setPlayFeedbackState(state = "idle", detail = "") {
    const $feedback = this.$playFeedback;
    if (!$feedback?.length) return;

    $feedback.removeClass("wrong animate__animated animate__heartBeat animate__flash");
    $feedback.find(".play-feedback-note-name, .play-feedback-wrong-note").remove();
    this.$playFeedbackText?.empty?.();
    this._setPlayFeedbackIcon(state);

    if (state === "wrong") {
      $feedback.css("display", "inline-block").addClass("wrong");
      if (detail) {
        const $target = this.$playFeedbackText?.length ? this.$playFeedbackText : $feedback.find(".d-center").first();
        const $detail = $('<span class="play-feedback-wrong-note ml-2 small"></span>');
        const playedNoteMatch = String(detail).match(/^You played\s+([^\s.]+)(.*)$/);
        if (playedNoteMatch) {
          $detail.append(document.createTextNode("You played "));
          $("<strong></strong>").text(playedNoteMatch[1]).appendTo($detail);
          if (playedNoteMatch[2]) $detail.append(document.createTextNode(playedNoteMatch[2]));
        } else {
          $detail.text(detail);
        }

        if (this.$playFeedbackText?.length) $target.empty().append($detail.contents());
        else ($target.length ? $target : $feedback).append($detail);
      }
      void $feedback[0]?.offsetWidth;
      $feedback.addClass("animate__animated animate__flash");
      return;
    }

    $feedback.hide();
  }

  _setPlayFeedbackIcon(state = "idle") {
    const $feedback = this.$playFeedback;
    if (!$feedback?.length) return;

    const useFrown = state === "wrong";
    const fromIcon = useFrown ? "microphone" : "face-frown";
    const toIcon = useFrown ? "face-frown" : "microphone";
    const $icon = $feedback.find(`[data-icon="${fromIcon}"], .fa-${fromIcon}`).first();
    if (!$icon.length) return;

    $icon.attr("data-icon", toIcon);
    if ($icon.attr("data-prefix") != null) $icon.attr("data-prefix", "fas");
    $icon
      .removeClass(`fa-${fromIcon} far fas`)
      .addClass(`fa-${toIcon} fas`);
  }

  _setPlayNoteButtonLabel(state = "default") {
    if (!this.$playNoteBtn?.length) return;

    if (state === "tryAgain") {
      this.$playNoteBtn.html(
        `${this._playNoteButtonDefaultHtml || ""}`.replace(
          "Tap here and play the note",
          "Tap here and try again",
        ),
      );
      return;
    }

    this.$playNoteBtn.html(this._playNoteButtonDefaultHtml);
  }

  _showPlayNoteStatus(color, message) {
    this.$playNoteStatus
      .removeClass("bg-grey-lighter bg-yellow-lighter bg-green-lighter")
      .addClass(`bg-${color}-lighter`)
      .text(message);
    if (color === "yellow") {
      this.$playNoteStatus.prepend('<i class="fas fa-microphone-lines text-yellow me-2" aria-hidden="true"></i>');
    } else if (color === "green") {
      this.$playNoteStatus.prepend('<i class="fas fa-circle-check text-green me-2" aria-hidden="true"></i>');
    }
    this.$checkWrap.hide().addClass("invisible");
    this.$playNoteStart.hide();
    this.$playNoteStatus.show();
  }

  _showPlayNoteActions() {
    if (this._pitchInputUnavailable) {
      this.$playNoteStart.hide();
      return;
    }
    this.$playNoteStatus.hide();
    this.$playNoteStart.show();
  }

  _hasEnoughUserNotesForCheck(count = this._currentUserNoteCount()) {
    const userNoteCount = Number.isFinite(count) ? count : this._currentUserNoteCount();
    return this._checkAfterUserNotes() <= 0 || userNoteCount >= this._checkAfterUserNotes();
  }

  _syncPlayedNoteGate(count = this._currentUserNoteCount()) {
    if (!this.$playNoteWrap?.length) return;

    if (!this._requiresPlayedNote()) {
      this.$playNoteWrap.hide().addClass("invisible");
      return;
    }

    const readyForPlayedNote = this._hasEnoughUserNotesForCheck(count);
    $("#check").hide().addClass("invisible");

    if (readyForPlayedNote) {
      this.$playNoteWrap.show().removeClass("invisible");
      this._showPlayNoteActions();
      if (this._lastPlayedNote && this._playedNoteConfirmed) {
        $("#check").show().removeClass("invisible");
      }
    } else {
      this.$playNoteWrap.hide().addClass("invisible");
    }
  }

  _midiToNoteName(midi) {
    return noteNameFromMidi(midi);
  }

  _playedNoteFeedbackName(midi) {
    const raw = this._midiToNoteName(midi);
    const match = String(raw || "").match(/^([A-G])([#b]?)(-?\d+)$/);
    if (!match) return raw;

    const [, letter, accidental, octave] = match;
    const base = this._showSolfegeNoteNames()
      ? (NoteNest.LETTER_TO_SOLFEGE[letter] || letter)
      : letter;
    return displayNoteName(`${base}${accidental}${octave}`);
  }

  _playedNoteFeedbackNameWithoutOctave(midi) {
    const raw = this._midiToNoteName(midi);
    const match = String(raw || "").match(/^([A-G])([#b]?)(-?\d+)$/);
    if (!match) return raw;

    const [, letter, accidental] = match;
    const base = this._showSolfegeNoteNames()
      ? (NoteNest.LETTER_TO_SOLFEGE[letter] || letter)
      : letter;
    return displayNoteName(`${base}${accidental}`);
  }

  _playedNoteWrongFeedbackText() {
    const playedMidi = Number(this._lastPlayedNote?.midi);
    const [note] = this._collectUserNotes();
    const targetMidi = this._noteMidi(note);
    const pitchClass = (midi) => ((midi % 12) + 12) % 12;

    if (
      Number.isFinite(playedMidi) &&
      Number.isFinite(targetMidi) &&
      pitchClass(playedMidi) === pitchClass(targetMidi)
    ) {
      const playedName = this._playedNoteFeedbackNameWithoutOctave(playedMidi);
      return playedName
        ? `You played ${playedName} in the wrong octave...`
        : "You played the wrong octave...";
    }

    const playedName = this._playedNoteFeedbackNameWithoutOctave(playedMidi);
    return playedName ? `You played ${playedName}...` : "That was the wrong note...";
  }

  _noteMidi(note) {
    if (!note || !this.staff) return null;
    const naturalMidi = this.staff._stepToMidi?.(note.step);
    if (!Number.isFinite(naturalMidi)) return null;
    const accidentalOffset = this.staff._accidentalClassToOffset?.(note.accidentalClass) || 0;
    return naturalMidi + accidentalOffset;
  }

  _ignoreAppAudioFor(durationMs = 900) {
    this._ignoreAppAudioUntil = Math.max(
      this._ignoreAppAudioUntil || 0,
      Date.now() + durationMs,
    );
    this._stablePitch = createStablePitchState();
  }

  _isIgnoringAppAudio() {
    return Date.now() < (this._ignoreAppAudioUntil || 0);
  }

  _onPianoKeyboardKeyClick(data) {
    if (!this._requiresPlayedNote()) return;
    this._ignoreAppAudioFor();
  }

  _wirePlayedNoteTracking() {
    this.$staffEl
      .off(`staff:noteState.${this.ns}.playedNote staff:userNotesChanged.${this.ns}.playedNote`)
      .on(`staff:noteState.${this.ns}.playedNote staff:userNotesChanged.${this.ns}.playedNote`, (e, data) => {
        if (!this._requiresPlayedNote()) return;
        if (e.type === "staff:noteState" && data?.source === "fixed") return;
        if (e.type === "staff:noteState") this._ignoreAppAudioFor();
        this._resetPlayedNote();
        this._syncPlayedNoteGate(Number(data?.count));
      });

    this.$playNoteBtn
      ?.off?.(`click.${this.ns}.playedNote`)
      ?.on?.(`click.${this.ns}.playedNote`, (e) => {
        e.preventDefault();
        this._beginPitchRecording();
      });
  }

  _armUiGates(args) {
    super._armUiGates(args);
    this._syncPlayedNoteGate();
  }

  _restoreInstructions() {
    if (this._hideInstructionsForMic) return;
    super._restoreInstructions();
  }

  _displayNameForLetter(letter) {
    const clean = String(letter || "").trim().toUpperCase();
    if (this._showSolfegeNoteNames()) return NoteNest.LETTER_TO_SOLFEGE[clean] || clean;
    return clean;
  }

  _handlePlayedNoteHeard(midi, noteName, frequency) {
    this._lastPlayedNote = { midi, noteName, frequency };
    this._playedNoteConfirmed = false;
    this._stopPitchInput();
    this._showPlayNoteStatus("green", "Note heard!");
    this._heardStatusTimer = setTimeout(() => {
      this._heardStatusTimer = null;
      if (!this._lastPlayedNote) return;
      this._playedNoteConfirmed = true;
      this._setPlayNoteButtonLabel("tryAgain");
      this._syncPlayedNoteGate();
    }, 1500);
  }

  _beginPitchRecording() {
    this._stopPitchInput();
    this._hideInstructionsForMic = true;
    this.$instructions.hide();
    this._lastPlayedNote = null;
    this._playedNoteConfirmed = false;
    this._setPlayFeedbackState("idle");
    this._showPlayNoteStatus("grey", "Connecting to the mic...");
    this._stablePitch = createStablePitchState();
    if (this._pitchStartFrame) cancelAnimationFrame(this._pitchStartFrame);
    this._pitchStartFrame = requestAnimationFrame(() => {
      this._pitchStartFrame = null;
      this._startPitchInput();
    });
  }

  _startPitchInput() {
    if (this._pitchInputStarting || this._pitchAnalyser) return Promise.resolve();

    if (!window.isSecureContext) {
      this._showPitchInputError("Mic unavailable. Use HTTPS or localhost.");
      return Promise.resolve();
    }

    if (!navigator.mediaDevices?.getUserMedia) {
      this._showPitchInputError("This browser can't access the mic.");
      return Promise.resolve();
    }

    const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextCtor) {
      this._showPitchInputError("This browser can't analyze mic audio.");
      return Promise.resolve();
    }

    this._pitchInputStarting = true;
    this._stablePitch = createStablePitchState();
    const requestId = this._pitchRequestId;

    return navigator.mediaDevices.getUserMedia({
      audio: {
        echoCancellation: false,
        noiseSuppression: false,
        autoGainControl: false,
      },
    }).then((stream) => {
      if (requestId !== this._pitchRequestId) {
        stream.getTracks().forEach((track) => track.stop());
        return;
      }
      this._pitchStream = stream;
      this._pitchAudioContext = new AudioContextCtor();
      this._pitchAudioContext.resume?.();
      this._pitchSource = this._pitchAudioContext.createMediaStreamSource(stream);
      this._pitchAnalyser = this._pitchAudioContext.createAnalyser();
      this._pitchAnalyser.fftSize = 8192;
      this._pitchData = new Float32Array(this._pitchAnalyser.fftSize);
      this._pitchSource.connect(this._pitchAnalyser);
      this._pitchInputStarting = false;
      this._showPlayNoteStatus("yellow", "Go ahead, I'm listening");
      this._listenForPitch();
    }).catch(() => {
      if (requestId !== this._pitchRequestId) return;
      this._stopPitchInput();
      this._showPitchInputError("Mic blocked. Allow access, then refresh the page.");
    });
  }

  _showPitchInputError(message) {
    this._pitchInputUnavailable = true;
    this._showPlayNoteStatus("grey", message);
  }

  _stopPitchInput() {
    this._pitchRequestId += 1;
    if (this._heardStatusTimer) {
      clearTimeout(this._heardStatusTimer);
      this._heardStatusTimer = null;
    }

    if (this._pitchFrame) {
      cancelAnimationFrame(this._pitchFrame);
      this._pitchFrame = null;
    }
    if (this._pitchStartFrame) {
      cancelAnimationFrame(this._pitchStartFrame);
      this._pitchStartFrame = null;
    }

    this._pitchStream?.getTracks?.().forEach((track) => track.stop());
    this._pitchAudioContext?.close?.();
    this._pitchAudioContext = null;
    this._pitchStream = null;
    this._pitchSource = null;
    this._pitchAnalyser = null;
    this._pitchData = null;
    this._pitchInputStarting = false;
    this._stablePitch = createStablePitchState();
  }

  _listenForPitch() {
    if (!this._pitchAnalyser || !this._pitchData || !this._pitchAudioContext) return;

    if (this._isIgnoringAppAudio()) {
      this._stablePitch = createStablePitchState();
      this._pitchFrame = requestAnimationFrame(() => this._listenForPitch());
      return;
    }

    this._pitchAnalyser.getFloatTimeDomainData(this._pitchData);
    const pitch = this._detectPitch(this._pitchData, this._pitchAudioContext.sampleRate);
    const frequency = pitch?.frequency;

    if (Number.isFinite(frequency)) {
      this._stablePitch = updateStablePitchState(this._stablePitch, frequency, this._pitchAudioContext.currentTime * 1000, this._microphoneSettings?.settleMs);

      if (this._stablePitch.settled) {
        const stableMidi = this._frequencyToMidi(this._stablePitch.frequency);
        this._handlePlayedNoteHeard(stableMidi, this._midiToNoteName(stableMidi), this._stablePitch.frequency);
        return;
      }
    } else {
      this._stablePitch = createStablePitchState();
    }

    this._pitchFrame = requestAnimationFrame(() => this._listenForPitch());
  }

  _frequencyToMidi(frequency) {
    return frequencyToMidi(frequency);
  }

  _detectPitch(buffer, sampleRate) {
    return detectPlayedNotePitch(buffer, sampleRate, {
      sensitivity: this._microphoneSettings?.sensitivity,
    });
  }

  _targetAccidentalClass() {
    if (!this._normalizeOnOff(this.opts.allowAccidentals)) return null;

    const w = this.opts.accidentalWeights || {};
    const naturalWeight = Number(w.natural);
    const sharpWeight = Number(w.sharp);
    const flatWeight = Number(w.flat);

    if (Number.isFinite(naturalWeight) || Number.isFinite(sharpWeight) || Number.isFinite(flatWeight)) {
      return pickWeighted([
        { value: null, weight: Number.isFinite(naturalWeight) ? naturalWeight : 0 },
        { value: "music-font__sharp", weight: Number.isFinite(sharpWeight) ? sharpWeight : 0 },
        { value: "music-font__flat", weight: Number.isFinite(flatWeight) ? flatWeight : 0 },
      ], this._duelRandom);
    }

    return (this._duelRandom?.() ?? Math.random()) < 0.5 ? "music-font__sharp" : "music-font__flat";
  }

  _isBlockNoteEnabled() {
    return this._normalizeOnOff(this.opts.blockNote);
  }

  _blockIconClassName() {
    const raw = String(this.opts.blockIcon || "").trim();
    const clean = raw.replace(/^fa-/, "").replace(/[^a-z0-9-]/gi, "");
    return clean ? `fa-solid fa-${clean}` : "fa-solid fa-lock";
  }

  _blockTooltipNoteName(target) {
    if (!target) return "";
    const acc = String(target.accidentalClass || "");
    const accText = acc.includes("doublesharp")
      ? " double sharp"
      : acc.includes("sharp")
        ? " sharp"
        : acc.includes("doubleflat")
          ? " double flat"
          : acc.includes("flat")
            ? " flat"
            : "";
    return `${this._displayNameForLetter(target.letter)}${accText}`;
  }

  _blockTooltipText(target) {
    return `This note is blocked, can you find another ${this._blockTooltipNoteName(target)}?`;
  }

  _alternateStepsForTarget(target) {
    if (!target || !this.staff) return [];

    const minStep = this.staff.minStepAllowed();
    const maxStep = this.staff.maxStepAllowed();
    const out = [];

    for (let step = minStep; step <= maxStep; step += 1) {
      if (step === target.step) continue;
      const noteState = stepToLetterOctave(this.staff, step);
      if (String(noteState?.letter || "") !== String(target.letter || "")) continue;
      out.push(step);
    }

    return out;
  }

  _pickHintStepForTarget(target) {
    const alternates = this._alternateStepsForTarget(target);
    if (alternates.length) return alternates[Math.floor((this._duelRandom?.() ?? Math.random()) * alternates.length)];
    return target?.step ?? null;
  }

  _clearBlockMarker() {
    const $marker = this.$staffEl.find(`.${this._blockMarkerClass}`);
    if ($marker.length && $.fn.tooltip) {
      try {
        $marker.tooltip("dispose");
      } catch (_) {}
    }
    $marker.remove();
    this.staff?.clearBlockedSteps?.();
  }

  _hideBlockMarkerTooltip() {
    const $marker = this.$staffEl.find(`.${this._blockMarkerClass}`);
    if (!$marker.length || !$.fn.tooltip) return;
    try {
      $marker.tooltip("hide");
    } catch (_) {}
  }

  _bindBlockMarkerDismiss() {
    $(document)
      .off(`pointerdown.${this.ns}.blockTooltip mousedown.${this.ns}.blockTooltip`, "*")
      .on(`pointerdown.${this.ns}.blockTooltip mousedown.${this.ns}.blockTooltip`, (e) => {
        if ($(e.target).closest(`.${this._blockMarkerClass}`).length) return;
        this._hideBlockMarkerTooltip();
      });
  }

  _renderBlockMarker(target) {
    this._clearBlockMarker();
    if (!this._isBlockNoteEnabled() || !target || !Number.isFinite(target.step)) return;

    this.staff.setBlockedSteps([target.step]);

    const $marker = $("<span></span>")
      .addClass(`block-icon ${this._blockMarkerClass}`)
      .attr("aria-hidden", "true")
      .attr("tabindex", "0")
      .attr("data-toggle", "tooltip")
      .attr("data-trigger", "click")
      .attr("data-placement", "top")
      .attr("title", this._blockTooltipText(target))
      .css({
        left: `${this.staff.centerX()}px`,
        top: `${this.staff.stepToY(target.step) + 3}px`,
      })
      .html(`<i class="${this._blockIconClassName()}"></i>`)
      .appendTo(this.$staffEl);

    if ($.fn.tooltip) {
      $marker.tooltip({
        trigger: "click",
      });
    }
  }

  _pickTargetStep() {
    const minStep = 0;
    const maxStep = 8;
    return Math.floor((this._duelRandom?.() ?? Math.random()) * (maxStep - minStep + 1)) + minStep;
  }

  _setPromptForTarget(target) {
    if (!target) return;
    const shortName = `${this._displayNameForLetter(target.letter)}${accidentalClassToText(target.accidentalClass)}`;
    this.prompt.setShort(shortName, { html: true });
    // this.prompt.setLong("Find this note on the staff");
  }

  _targetName(target) {
    if (!target) return "";
    return [
      String(target.letter || ""),
      String(target.accidentalClass || ""),
    ].join("|");
  }

  _pickTargetNote() {
    const maxAttempts = 24;

    const validTarget = target =>
      this._targetName(target) !== this._lastTargetName &&
      (!this._isBlockNoteEnabled() || this._alternateStepsForTarget(target).length > 0);

    const targetAt = step => {
      const noteState = stepToLetterOctave(this.staff, step);
      return {
        step,
        letter: noteState.letter,
        octave: noteState.octave,
        accidentalClass: this._targetAccidentalClass(),
      };
    };

    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
      const target = targetAt(this._pickTargetStep());
      if (validTarget(target)) return target;
    }

    // A stuck random source must not repeat the previous prompt.
    for (let step = 0; step <= 8; step += 1) {
      const target = targetAt(step);
      if (validTarget(target)) return target;
    }

    throw new Error("Note Nest has no different note available");
  }

  newChallenge() {
    const clef = pickChallengeClef(this._clefPool, this._duelRandom);
    if (clef && clef !== this.staff.getClef()) this.staff.setClef(clef);

    this._madeMistakeThisRound = false;
    this._usedHintThisRound = false;
    this._resetPlayedNote();

    this._clearBlockMarker();
    this.staff.clearNotes();
    this.$feedback.hide();
    this.$helpBtn.hide();
    this.$bonusBadge.hide();
    this.$doublePoints?.hide?.();

    this._targetNote = this._pickTargetNote();
    this._lastTargetName = this._targetName(this._targetNote);

    this.prompt.show();
    this._setPromptForTarget(this._targetNote);
    this._renderBlockMarker(this._targetNote);

    $("#check").show().removeClass("invisible");
    this._syncPlayedNoteGate(0);
    $("#continue").hide();
  }

  _collectUserNotes() {
    return this.$staffEl.find(".note").toArray()
      .map((el) => {
        const $note = $(el);
        const noteId = String($note.attr("data-note-id") || "");
        if (!noteId || this.staff.isNoteFixed(noteId)) return null;
        const top = parseFloat($note.css("top"));
        const step = Number.isFinite(top) ? this.staff.yToStep(top) : null;
        const accidentalClass = this.staff._getAttachedAccidentalClass?.(noteId) || null;
        return { noteId, step, accidentalClass };
      })
      .filter(Boolean);
  }

  _isUserAnswerCorrect() {
    const target = this._targetNote;
    if (!target) return false;

    const notes = this._collectUserNotes();
    if (notes.length !== 1) return false;

    const [note] = notes;
    return (
      this._isStaffNoteCorrect(note) &&
      this._isPlayedNoteCorrect(note)
    );
  }

  _isStaffNoteCorrect(note) {
    const target = this._targetNote;
    if (!target || !note) return false;

    const noteState = stepToLetterOctave(this.staff, note.step);
    return (
      String(noteState?.letter || "") === String(target.letter || "") &&
      String(note.accidentalClass || "") === String(target.accidentalClass || "")
    );
  }

  _isPlayedNoteCorrect(note) {
    if (!this._requiresPlayedNote()) return true;
    if (!this._playedNoteConfirmed) return false;
    const targetMidi = this._noteMidi(note);
    const playedMidi = Number(this._lastPlayedNote?.midi);
    return Number.isFinite(targetMidi) && playedMidi === targetMidi;
  }

  _isPlayedNoteMistake() {
    if (!this._requiresPlayedNote()) return false;

    const notes = this._collectUserNotes();
    if (notes.length !== 1) return false;

    const [note] = notes;
    return this._isStaffNoteCorrect(note) && !this._isPlayedNoteCorrect(note);
  }

  _computeHintAnswers() {
    if (!this._targetNote) return [{ step: null, accidentalClass: null }];
    return [{
      step: this._isBlockNoteEnabled()
        ? this._pickHintStepForTarget(this._targetNote)
        : this._targetNote.step,
      accidentalClass: this._targetNote.accidentalClass || null,
    }];
  }

  _onCheck() {
    this.$checkBtn.disable();
    this._stats.checksTotal += 1;

    if (this._isUserAnswerCorrect()) {
      this._stats.checksCorrect += 1;
      this._pauseGameTimer();
      this._stopPitchInput();
      this.$playNoteWrap?.hide?.().addClass?.("invisible");
      this._setPlayFeedbackState("idle");

      const { earned, bonusEarned } = this._awardPointsForCorrect();
      this._handleCorrectAnswerUi({
        isBonus: bonusEarned > 0,
        earned,
        $prompt: this.prompt.$root,
      });
      return;
    }

    this._madeAnyMistake = true;
    this._madeMistakeThisRound = true;
    if (this._isPlayedNoteMistake()) {
      this._setPlayFeedbackState("wrong", this._playedNoteWrongFeedbackText());
      this._lastPlayedNote = null;
      this._playedNoteConfirmed = false;
      this._setPlayNoteButtonLabel("tryAgain");
      this.$helpBtn.hide();
      this._syncPlayedNoteGate();
      this._failAnimation(this.$playNoteStart);
      return;
    }
    this._shakeWrongUserStaffNotes();
    this._failAnimation(this.$checkWrap);
    this.$helpBtn.show();
  }
}
