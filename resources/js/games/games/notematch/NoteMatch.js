import { NoteNest } from "../notenest/NoteNest.js";
import { pickChallengeClef } from "../shared/challengeUtils.js";

export class NoteMatch extends NoteNest {
  constructor(options = {}) {
    super({
      ...options,
      namespace: "noteMatch",
      requirePlayedNote: true,
      usePianoKeyboard: false,
      maxUserNotes: 0,
      instructionsAfterUserNotes: 1,
      checkAfterUserNotes: 0,
    });
  }

  _requiresPlayedNote() {
    return true;
  }

  _syncPlayedNoteGate() {
    if (!this.$playNoteWrap?.length) return;

    this.$playNoteWrap.show().removeClass("invisible");
    this._showPlayNoteActions();
    if (this._lastPlayedNote && this._playedNoteConfirmed) {
      $("#check").show().removeClass("invisible");
    } else {
      $("#check").hide().addClass("invisible");
    }
  }

  _setPromptForTarget() {
    this.prompt.setShort("", { html: false }).clearLong().hideDirection().hide();
  }

  _removeInstructions() {
    if (this._hideInstructionsForMic) {
      this.$instructions.hide();
      this._instructionsRemoved = true;
    } else {
      this.$instructions.show();
      this._instructionsRemoved = false;
    }
  }

  _renderTargetNote(target) {
    if (!target || !Number.isFinite(target.step)) return;

    const fixedId = this.staff.addFixedNote({
      step: target.step,
      accidentalClass: target.accidentalClass || null,
    });

    if (fixedId) this.staff._emitNoteState(fixedId, "fixed");
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
    this.$accidentals.addClass("invisible");

    this._targetNote = this._pickTargetNote();
    this._lastTargetName = this._targetName(this._targetNote);

    this._setPromptForTarget(this._targetNote);
    this._renderTargetNote(this._targetNote);

    this._syncPlayedNoteGate();
    $("#continue").hide();
  }

  _isUserAnswerCorrect() {
    const targetMidi = this._noteMidi(this._targetNote);
    const playedMidi = Number(this._lastPlayedNote?.midi);
    return (
      this._playedNoteConfirmed &&
      Number.isFinite(targetMidi) &&
      playedMidi === targetMidi
    );
  }

  _playedNoteWrongFeedbackText() {
    const playedMidi = Number(this._lastPlayedNote?.midi);
    const targetMidi = this._noteMidi(this._targetNote);
    const pitchClass = (midi) => ((midi % 12) + 12) % 12;

    if (
      Number.isFinite(playedMidi) &&
      Number.isFinite(targetMidi) &&
      pitchClass(playedMidi) === pitchClass(targetMidi)
    ) {
      const playedName = this._playedNoteFeedbackNameWithoutOctave(playedMidi);
      return playedName
        ? `You played ${playedName} in the wrong octave...`
        : "You played the right note in the wrong octave...";
    }

    const playedName = this._playedNoteFeedbackNameWithoutOctave(playedMidi);
    return playedName ? `You played ${playedName}...` : "That was the wrong note...";
  }

  _computeHintAnswers() {
    return [];
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
    this._setPlayFeedbackState("wrong", this._playedNoteWrongFeedbackText());
    this._lastPlayedNote = null;
    this._playedNoteConfirmed = false;
    this._setPlayNoteButtonLabel("tryAgain");
    this.$helpBtn.hide();
    this._syncPlayedNoteGate();
    this._failAnimation(this.$playNoteStart);
  }
}
