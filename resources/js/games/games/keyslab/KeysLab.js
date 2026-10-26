import { BaseStaffGame } from "../base/BaseStaffGame.js";
import { playSmokePuffAtElement } from "../shared/mojsEffects.js";
import { normalizeClefPool, pickChallengeClef } from "../shared/challengeUtils.js";
import { displayNoteName } from "../shared/noteNames.js";

export class KeysLab extends BaseStaffGame {
  static MAJOR_KEYS = ["C", "G", "D", "A", "E", "B", "F#", "C#", "F", "Bb", "Eb", "Ab", "Db", "Gb", "Cb"];
  static MINOR_KEYS = ["A", "E", "B", "F#", "C#", "G#", "D#", "A#", "D", "G", "C", "F", "Bb", "Eb", "Ab"];
  static SHARP_MAJOR_ORDER = ["G", "D", "A", "E", "B", "F#", "C#"];
  static FLAT_MAJOR_ORDER = ["F", "Bb", "Eb", "Ab", "Db", "Gb", "Cb"];
  static SHARP_MINOR_ORDER = ["E", "B", "F#", "C#", "G#", "D#", "A#"];
  static FLAT_MINOR_ORDER = ["D", "G", "C", "F", "Bb", "Eb", "Ab"];
  static NOTE_LETTERS = ["C", "D", "E", "F", "G", "A", "B"];
  static SHARP_NOTE_ORDER = ["F", "C", "G", "D", "A", "E", "B"];
  static FLAT_NOTE_ORDER = ["B", "E", "A", "D", "G", "C", "F"];
  static MODES = ["ionian", "dorian", "phrygian", "lydian", "mixolydian", "aeolian", "locrian"];
  static KEYSIG_STEPS = {
    treble: {
      sharp: [8, 5, 9, 6, 3, 7, 4],
      flat: [4, 7, 3, 6, 2, 5, 1],
    },
    bass: {
      sharp: [6, 3, 7, 4, 1, 5, 2],
      flat: [2, 5, 8, 4, 0, 3, 6],
    },
  };

  constructor(options = {}) {
    const defaults = {
      staffEl: "#staff",
      basePoints: 1,
      firstTryBonus: 2,
      initialClef: "treble",
      clefUrls: null,
      sound: true,
      keyQualities: ["major", "minor"],
      numberOfAccidentals: 2,
      modes: false,
      namespace: "keysLab",
    };

    const merged = { ...defaults, ...(options || {}) };
    super(merged);

    this._clefPool = normalizeClefPool(
      merged.clefs != null ? merged.clefs : merged.clef,
    );
    this._activeClef = (this._clefPool && this._clefPool[0]) || merged.initialClef || "treble";
    this._toolsWired = false;
    this._anchorCounter = 1;
    this._accidentalRepositionPatched = false;
    this._laneDragWired = false;
    this._laneDrag = {
      noteId: null,
      pointerId: null,
      fromAccidental: false,
      startPageX: 0,
      startPageY: 0,
      startNoteX: null,
      movedPx: 0,
      isDragging: false,
      thresholdPx: 0,
      lastTargetStep: null,
      lastTargetX: null,
      lastSoundStep: null,
    };
    this._swallowAccidentalClick = { noteId: null, until: 0 };
  }

  _normalizeKeyQualities() {
    const src = Array.isArray(this.opts.keyQualities) && this.opts.keyQualities.length
      ? this.opts.keyQualities
      : ["major", "minor"];

    const allowed = src
      .map((q) => String(q || "").trim().toLowerCase())
      .filter((q) => q === "major" || q === "minor");

    return allowed.length ? allowed : ["major", "minor"];
  }

  _pickKeyPrompt() {
    const qualities = this._normalizeOnOff(this.opts.modes)
      ? KeysLab.MODES
      : this._normalizeKeyQualities();
    const quality = qualities[Math.floor((this._duelRandom?.() ?? Math.random()) * qualities.length)];
    if (KeysLab.MODES.includes(quality)) {
      const parents = this._filterKeysByAccidentalLimit(KeysLab.MAJOR_KEYS, "major");
      const parentMajor = parents[Math.floor((this._duelRandom?.() ?? Math.random()) * parents.length)];
      const tonic = this._modeTonicFromMajor(parentMajor, KeysLab.MODES.indexOf(quality));
      return { tonic, quality, parentMajor, full: `${tonic} ${quality}` };
    }
    const pool = this._filterKeysByAccidentalLimit(
      quality === "minor" ? KeysLab.MINOR_KEYS : KeysLab.MAJOR_KEYS,
      quality,
    );
    const tonic = pool[Math.floor((this._duelRandom?.() ?? Math.random()) * pool.length)];
    const full = `${tonic} ${quality}`;
    return { tonic, quality, full };
  }

  _modeTonicFromMajor(parentMajor, degree) {
    const firstLetter = String(parentMajor || "").charAt(0);
    const start = KeysLab.NOTE_LETTERS.indexOf(firstLetter);
    if (start < 0) return "";
    const letter = KeysLab.NOTE_LETTERS[(start + degree) % 7];
    const signature = this._signatureForKey(parentMajor, "major");
    const order = signature.type === "sharp" ? KeysLab.SHARP_NOTE_ORDER : KeysLab.FLAT_NOTE_ORDER;
    const accidental = signature.type && order.slice(0, signature.count).includes(letter)
      ? (signature.type === "sharp" ? "#" : "b")
      : "";
    return `${letter}${accidental}`;
  }

  _maxAllowedAccidentals() {
    const limit = Number(this.opts.numberOfAccidentals);
    return Number.isInteger(limit) ? Math.max(1, Math.min(7, limit)) : 2;
  }

  _checkAfterUserNotes() {
    // A natural key signature is a complete answer with an empty staff.
    return 0;
  }

  _signatureCountForKey(tonic, quality) {
    return this._signatureForKey(tonic, quality).count;
  }

  _filterKeysByAccidentalLimit(keys, quality) {
    const maxAccidentals = this._maxAllowedAccidentals();
    const filtered = (Array.isArray(keys) ? keys : []).filter(
      (tonic) => this._signatureCountForKey(tonic, quality) <= maxAccidentals,
    );
    return filtered.length ? filtered : (Array.isArray(keys) ? keys : []);
  }

  _renderKeyPrompt() {
    const picked = this._pickKeyPrompt();
    this._currentKeyPrompt = picked;
    const visibleName = `${displayNoteName(picked.tonic)} ${picked.quality}`;
    this.prompt.setShort(visibleName);
    this.prompt.setLong(`Key of ${visibleName}`);
  }

  _expectedSignatureForPrompt() {
    const prompt = this._currentKeyPrompt || this._pickKeyPrompt();
    return this._signatureForKey(prompt.parentMajor || prompt.tonic,
      prompt.parentMajor ? "major" : prompt.quality);
  }

  _signatureForKey(tonic, quality) {
    const cleanTonic = String(tonic || "").trim();
    const cleanQuality = String(quality || "").trim().toLowerCase();

    if (cleanQuality === "major") {
      if (cleanTonic === "C") return { type: null, count: 0 };
      const s = KeysLab.SHARP_MAJOR_ORDER.indexOf(cleanTonic);
      if (s >= 0) return { type: "sharp", count: s + 1 };
      const f = KeysLab.FLAT_MAJOR_ORDER.indexOf(cleanTonic);
      if (f >= 0) return { type: "flat", count: f + 1 };
      return { type: null, count: 0 };
    }

    if (cleanQuality === "minor") {
      if (cleanTonic === "A") return { type: null, count: 0 };
      const s = KeysLab.SHARP_MINOR_ORDER.indexOf(cleanTonic);
      if (s >= 0) return { type: "sharp", count: s + 1 };
      const f = KeysLab.FLAT_MINOR_ORDER.indexOf(cleanTonic);
      if (f >= 0) return { type: "flat", count: f + 1 };
      return { type: null, count: 0 };
    }

    return { type: null, count: 0 };
  }

  _signatureStepsForCurrentClef(type) {
    const clef = String(this.staff.getClef() || "treble").toLowerCase();
    const map = KeysLab.KEYSIG_STEPS[clef] || KeysLab.KEYSIG_STEPS.treble;
    return type === "flat" ? map.flat.slice() : map.sharp.slice();
  }

  _collectUserSignatureEntries() {
    this._placeAccidentalsInsideClefWrapper();

    return this.$staffEl.find(".accidental").toArray().map((el) => {
      const $acc = $(el);
      const noteId = String($acc.attr("data-for-note-id") || "");
      const cls = $acc.hasClass("music-font__flat")
        ? "music-font__flat"
        : $acc.hasClass("music-font__sharp")
          ? "music-font__sharp"
          : null;
      const step = this._stepOfNoteId(noteId);
      const $note = this.$staffEl.find(`.note[data-note-id="${noteId}"]`).first();
      const accLeft = parseFloat($acc.css("left"));
      const noteLeft = $note.length ? parseFloat($note.css("left")) : NaN;
      const left = Number.isFinite(accLeft) ? accLeft : noteLeft;
      return { cls, step, left };
    });
  }

  _buildExpectedEntries() {
    const sig = this._expectedSignatureForPrompt();
    if (!sig.type || sig.count <= 0) return [];
    const cls = sig.type === "flat" ? "music-font__flat" : "music-font__sharp";
    const steps = this._signatureStepsForCurrentClef(sig.type);
    const out = [];
    for (let i = 0; i < sig.count && i < steps.length; i += 1) {
      out.push({ cls, accidentalClass: cls, step: steps[i] });
    }
    return out;
  }

  _isRegularStaffStep(step) {
    return Number.isFinite(step) && step >= -1 && step <= 9;
  }

  _isUserSignatureCorrect() {
    const expected = this._buildExpectedEntries();
    const user = this._collectUserSignatureEntries()
      .sort((a, b) => {
        const leftDiff = (a.left || 0) - (b.left || 0);
        if (leftDiff !== 0) return leftDiff;
        return (a.step || 0) - (b.step || 0);
      });

    if (user.some((e) => !e.cls || !Number.isFinite(e.step) || !Number.isFinite(e.left))) return false;
    if (expected.length !== user.length) return false;

    for (let i = 0; i < expected.length; i += 1) {
      const want = expected[i];
      const got = user[i];
      if (!want || !got) return false;
      if (want.cls !== got.cls) return false;
      if (want.step !== got.step) return false;
    }
    return true;
  }

  _computeHintAnswers() {
    return this._buildExpectedEntries();
  }

  _showHintNote() {
    this._removeAllHintNotes();
    this._removeAllUserNotesForHint();

    const specs = this._buildExpectedEntries();
    this._activeHintIds = [];

    for (let i = 0; i < specs.length; i += 1) {
      const ans = specs[i] || {};
      const step = Number.isFinite(ans.step) ? Number(ans.step) : null;
      const accidentalClass = ans.accidentalClass || null;
      if (step == null || !accidentalClass) continue;

      const id = `hint${i + 1}`;
      const createdId = this.staff.addNote({
        id,
        step,
        className: "hint blink",
        allowOccupied: true,
        skipResolve: true,
      });
      if (!createdId) continue;

      this._activeHintIds.push(id);
      this.staff.attachAccidentalToNote(id, accidentalClass);

      this.$staffEl.find(`.note[data-note-id="${id}"]`).css({
        opacity: 0,
        width: 0,
        height: 0,
        pointerEvents: "none",
      });
      this.$staffEl.find(`.ledger[data-for-note-id="${id}"]`).css({
        opacity: 0,
        pointerEvents: "none",
      });
      this.$staffEl.find(`.accidental[data-for-note-id="${id}"]`).addClass("hint blink");
      // eslint-disable-next-line no-console
      console.log("[KeysLab] Hint accidental placed", {
        noteId: id,
        accidentalClass,
        step,
        clef: this.staff.getClef(),
      });
      this._attachHintBlinkRemoval(id);
    }
  }

  _wireStaffTools() {
    this._wireAccidentalTools();
  }

  newChallenge() {
    const clef = pickChallengeClef(this._clefPool, this._duelRandom);
    if (clef && clef !== this.staff.getClef()) this.staff.setClef(clef);

    this._activeClef = this.staff.getClef();
    this._madeMistakeThisRound = false;
    this._usedHintThisRound = false;

    this.staff.clearNotes();
    this.$accidentals.removeClass("invisible");
    this.$feedback.hide();
    this.$helpBtn.hide();
    this.$bonusBadge.hide();
    this.$doublePoints?.hide?.();

    this.prompt.show();
    $("#check").show().removeClass("invisible");
    $("#continue").hide();
    this._renderKeyPrompt();
  }

  _onCheck() {
    this.$checkBtn.disable();
    this._stats.checksTotal += 1;

    const ok = this._isUserSignatureCorrect();
    if (ok) {
      this._stats.checksCorrect += 1;
      this._pauseGameTimer();

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
    this._shakeWrongUserStaffNotes();
    this._failAnimation(this.$checkWrap);
    this.$helpBtn.show();
  }

  _accidentalClassFromTool($tool) {
    if (!$tool?.length) return null;
    if ($tool.hasClass("music-font__sharp")) return "music-font__sharp";
    if ($tool.hasClass("music-font__flat")) return "music-font__flat";
    return null;
  }

  _wireAccidentalTools() {
    if (this._toolsWired) return;
    const $tools = $("#accidentals .music-font__sharp, #accidentals .music-font__flat");
    this.staff.enableAccidentalDrag($tools);
    this._augmentAccidentalToolDragSound($tools);
    this._patchAccidentalRepositionForClefWrapper();

    // KeysLab uses free accidental placement (not "drop onto existing note").
    this.$staffEl.droppable({
      accept: ".accidental-tool",
      tolerance: "pointer",
      drop: (event, ui) => {
        const accClass = this._accidentalClassFromTool(ui.draggable);
        if (!accClass) return;
        if (this.$staffEl.find(".accidental").length >= 7) return;

        let pageY = event.pageY;
        let pageX = event.pageX;
        if (!Number.isFinite(pageY) && event.originalEvent) pageY = event.originalEvent.pageY;
        if (!Number.isFinite(pageX) && event.originalEvent) pageX = event.originalEvent.pageX;

        const localY = this.staff._pageYToLocalY(pageY);
        const step = this.staff.yToStep(localY);
        if (!this._isRegularStaffStep(step)) return;

        const off = this.$staffEl.offset();
        const localXRaw = Number(pageX) - Number(off?.left || 0);
        const localX = Math.max(0, Math.min(this.$staffEl.width(), localXRaw));

        const id = `keysig-anchor-${this._anchorCounter++}`;
        const noteId = this.staff.addNote({
          id,
          step,
          x: localX,
          allowOccupied: true,
          skipResolve: true,
        });
        if (!noteId) return;

        this.staff.attachAccidentalToNote(noteId, accClass);

        // Keep only the accidental visible.
        this.$staffEl.find(`.note[data-note-id="${noteId}"]`).css({
          opacity: 0,
          width: 0,
          height: 0,
          pointerEvents: "none",
        });
        this.$staffEl.find(`.ledger[data-for-note-id="${noteId}"]`).css({
          opacity: 0,
          pointerEvents: "none",
        });
        $("#check").show().removeClass("invisible");
      },
    });

    // Allow removing placed key-signature accidentals by tap/click.
    this.$staffEl
      .off(`click.${this.ns}.accidentalRemove`)
      .on(`click.${this.ns}.accidentalRemove`, ".accidental", (e) => {
        const noteId = String($(e.currentTarget).attr("data-for-note-id") || "");
        if (
          this._swallowAccidentalClick.noteId &&
          noteId === this._swallowAccidentalClick.noteId &&
          Date.now() < this._swallowAccidentalClick.until
        ) {
          this._swallowAccidentalClick.noteId = null;
          this._swallowAccidentalClick.until = 0;
          e.preventDefault();
          e.stopPropagation();
          return;
        }

        e.preventDefault();
        e.stopPropagation();

        const $acc = $(e.currentTarget);
        if (!noteId) return;

        playSmokePuffAtElement($acc[0], { parentEl: document.body });
        this.staff.removeNote(noteId);
      });

    this._wireLineSpaceAccidentalDrag();
    this._toolsWired = true;
  }

  _findNearestAccidentalNoteIdByLocalY(localY) {
    const maxDist = Number.isFinite(this.staff?.opts?.lineGap) ? (this.staff.opts.lineGap * 0.75) : 14;
    let bestId = null;
    let bestDist = Infinity;

    const ids = this.$staffEl
      .find(".accidental")
      .toArray()
      .map((el) => String(el.getAttribute("data-for-note-id") || ""))
      .filter(Boolean);

    for (let i = 0; i < ids.length; i += 1) {
      const id = ids[i];
      if (this.staff.isNoteFixed(id)) continue;
      const $note = this.$staffEl.find(`.note[data-note-id="${id}"]`);
      if (!$note.length) continue;
      const noteTop = parseFloat($note.css("top"));
      if (!Number.isFinite(noteTop)) continue;
      const d = Math.abs(noteTop - localY);
      if (d < bestDist) {
        bestDist = d;
        bestId = id;
      }
    }
    if (!bestId || bestDist > maxDist) return null;
    return bestId;
  }

  _stepOfNoteId(noteId) {
    const $note = this.$staffEl.find(`.note[data-note-id="${noteId}"]`);
    if (!$note.length) return null;
    const top = parseFloat($note.css("top"));
    if (!Number.isFinite(top)) return null;
    return this.staff.yToStep(top);
  }

  _playAccidentalAnchorStep(noteId) {
    if (!noteId || !this.staff._soundEnabled?.()) return;
    const step = this._stepOfNoteId(noteId);
    if (!Number.isFinite(step)) return;
    const accCls = this.staff._getAttachedAccidentalClass?.(noteId) || null;
    this._playAccidentalStep(step, accCls);
  }

  _playAccidentalStep(step, accClass) {
    if (!this.staff._soundEnabled?.()) return;
    if (!Number.isFinite(step)) return;
    const accOff = this.staff._accidentalClassToOffset?.(accClass) || 0;
    this.staff._playStep?.(step, accOff);
  }

  _previewToolAccidentalStep(pageX, pageY, accClass) {
    if (!this.staff._soundEnabled?.()) return;
    if (!Number.isFinite(pageX) || !Number.isFinite(pageY)) return;

    const off = this.$staffEl.offset();
    const localX = pageX - Number(off?.left || 0);
    const localY = pageY - Number(off?.top || 0);
    if (localX < 0 || localY < 0 || localX > this.$staffEl.width() || localY > this.$staffEl.height()) {
      this._toolDragPreviewStep = null;
      return;
    }

    const step = this.staff.yToStep(this.staff._pageYToLocalY(pageY));
    if (!this._isRegularStaffStep(step)) return;
    if (step === this._toolDragPreviewStep) return;

    this._toolDragPreviewStep = step;
    this._playAccidentalStep(step, accClass);
  }

  _augmentAccidentalToolDragSound($tools) {
    const originalStart = $tools.draggable("option", "start");
    const originalDrag = $tools.draggable("option", "drag");
    const originalStop = $tools.draggable("option", "stop");

    const toolTypeToClass = (toolType) => {
      if (toolType === "sharp") return "music-font__sharp";
      if (toolType === "flat") return "music-font__flat";
      return null;
    };

    $tools.draggable("option", "start", (event, ui) => {
      this._toolDragPreviewStep = null;
      if (typeof originalStart === "function") originalStart.call(event.currentTarget, event, ui);
    });

    $tools.draggable("option", "drag", (event, ui) => {
      if (typeof originalDrag === "function") originalDrag.call(event.currentTarget, event, ui);

      const accClass =
        toolTypeToClass(this.staff?._accDragSound?.toolType) ||
        this._accidentalClassFromTool($(ui?.helper)) ||
        this._accidentalClassFromTool($(event.currentTarget));
      let pageX = event.pageX;
      let pageY = event.pageY;
      if (!Number.isFinite(pageY) && event.originalEvent) pageY = event.originalEvent.pageY;
      if (!Number.isFinite(pageX) && event.originalEvent) pageX = event.originalEvent.pageX;

      this._previewToolAccidentalStep(pageX, pageY, accClass);
    });

    $tools.draggable("option", "stop", (event, ui) => {
      this._toolDragPreviewStep = null;
      if (typeof originalStop === "function") originalStop.call(event.currentTarget, event, ui);
    });
  }

  _wireLineSpaceAccidentalDrag() {
    if (this._laneDragWired) return;
    const ns = `${this.ns}.accidentalLaneDrag`;
    const getPointerId = (ev) => {
      if (ev.pointerId != null) return ev.pointerId;
      if (ev.originalEvent && ev.originalEvent.pointerId != null) return ev.originalEvent.pointerId;
      const oe = ev.originalEvent || ev;
      if (oe && oe.changedTouches && oe.changedTouches.length) {
        return oe.changedTouches[0].identifier ?? null;
      }
      return null;
    };
    const getPageY = (ev) => {
      if (Number.isFinite(ev.pageY)) return ev.pageY;
      const oe = ev.originalEvent || ev;
      if (oe && Number.isFinite(oe.pageY)) return oe.pageY;
      if (oe && oe.touches && oe.touches.length) return oe.touches[0].pageY;
      if (oe && oe.changedTouches && oe.changedTouches.length) return oe.changedTouches[0].pageY;
      return null;
    };
    const getPageX = (ev) => {
      if (Number.isFinite(ev.pageX)) return ev.pageX;
      const oe = ev.originalEvent || ev;
      if (oe && Number.isFinite(oe.pageX)) return oe.pageX;
      if (oe && oe.touches && oe.touches.length) return oe.touches[0].pageX;
      if (oe && oe.changedTouches && oe.changedTouches.length) return oe.changedTouches[0].pageX;
      return null;
    };

    this.$staffEl
      .off(`pointerdown.${ns} mousedown.${ns} touchstart.${ns}`)
      .on(`pointerdown.${ns} mousedown.${ns} touchstart.${ns}`, (e) => {
        if (this._laneDrag.noteId) return;
        const pageY = getPageY(e);
        if (!Number.isFinite(pageY)) return;

        const localY = this.staff._pageYToLocalY(pageY);
        const $accTarget = $(e.target).closest(".accidental");
        const noteIdFromAccidental = $accTarget.length
          ? String($accTarget.attr("data-for-note-id") || "")
          : null;
        const noteId = noteIdFromAccidental || this._findNearestAccidentalNoteIdByLocalY(localY);
        if (!noteId) return;

        const noteStep = this._stepOfNoteId(noteId);
        const step = Number.isFinite(noteStep)
          ? noteStep
          : this.staff.yToStep(localY);

        this._laneDrag.noteId = noteId;
        this._laneDrag.pointerId = getPointerId(e);
        this._laneDrag.fromAccidental = !!noteIdFromAccidental;
        this._laneDrag.startPageX = getPageX(e) || 0;
        this._laneDrag.startPageY = pageY;
        const $note = this.$staffEl.find(`.note[data-note-id="${noteId}"]`).first();
        const startNoteX = $note.length ? parseFloat($note.css("left")) : NaN;
        this._laneDrag.startNoteX = Number.isFinite(startNoteX) ? startNoteX : null;
        this._laneDrag.movedPx = 0;
        this._laneDrag.isDragging = false;
        this._laneDrag.lastTargetStep = step;
        this._laneDrag.lastTargetX = null;
        this._laneDrag.lastSoundStep = step;

        this.staff._setDraggingVisual(noteId, true);
        if (this.staff._soundEnabled?.()) this.staff._ensureAudio?.();
        e.preventDefault();
        e.stopPropagation();

        $(document)
          .off(`pointermove.${ns} mousemove.${ns} touchmove.${ns} pointerup.${ns} pointercancel.${ns} mouseup.${ns} touchend.${ns} touchcancel.${ns}`)
          .on(`pointermove.${ns} mousemove.${ns} touchmove.${ns}`, (ev) => {
            if (ev.preventDefault) ev.preventDefault();
            const pid = getPointerId(ev);
            if (
              this._laneDrag.pointerId != null &&
              pid != null &&
              pid !== this._laneDrag.pointerId
            ) {
              return;
            }

            const py = getPageY(ev);
            const px = getPageX(ev);
            if (!Number.isFinite(py) || !this._laneDrag.noteId) return;
            const dy = py - this._laneDrag.startPageY;
            const dx = Number.isFinite(px) ? (px - this._laneDrag.startPageX) : 0;
            this._laneDrag.movedPx = Math.max(this._laneDrag.movedPx, Math.abs(dy), Math.abs(dx));
            if (!this._laneDrag.isDragging && this._laneDrag.movedPx >= this._laneDrag.thresholdPx) {
              this._laneDrag.isDragging = true;
            }
            if (!this._laneDrag.isDragging) return;

            const targetStep = this.staff.yToStep(this.staff._pageYToLocalY(py));
            if (!this._isRegularStaffStep(targetStep)) return;
            this._laneDrag.lastTargetStep = targetStep;
            const movePos = { step: targetStep };
            if (Number.isFinite(px) && Number.isFinite(this._laneDrag.startNoteX)) {
              const nextX = this._laneDrag.startNoteX + dx;
              movePos.x = Math.max(0, Math.min(this.$staffEl.width(), nextX));
              this._laneDrag.lastTargetX = movePos.x;
            }
            this.staff.moveNote(this._laneDrag.noteId, movePos);
            if (this.staff._soundEnabled?.() && targetStep !== this._laneDrag.lastSoundStep) {
              this._laneDrag.lastSoundStep = targetStep;
              this._playAccidentalAnchorStep(this._laneDrag.noteId);
            }
          })
          .on(`pointerup.${ns} pointercancel.${ns} mouseup.${ns} touchend.${ns} touchcancel.${ns}`, (ev) => {
            const pid = getPointerId(ev);
            if (
              this._laneDrag.pointerId != null &&
              pid != null &&
              pid !== this._laneDrag.pointerId
            ) {
              return;
            }

            $(document).off(
              `pointermove.${ns} mousemove.${ns} touchmove.${ns} pointerup.${ns} pointercancel.${ns} mouseup.${ns} touchend.${ns} touchcancel.${ns}`,
            );

            const draggedNoteId = this._laneDrag.noteId;
            const dragged = !!this._laneDrag.isDragging;
            if (draggedNoteId) {
              this.staff._setDraggingVisual(draggedNoteId, false);
              if (dragged && Number.isFinite(this._laneDrag.lastTargetStep)) {
                const finalPos = { step: this._laneDrag.lastTargetStep };
                if (Number.isFinite(this._laneDrag.lastTargetX)) {
                  finalPos.x = this._laneDrag.lastTargetX;
                }
                this.staff.moveNote(draggedNoteId, finalPos);
                this.staff._emitNoteState(draggedNoteId, "user");
                this._swallowAccidentalClick.noteId = draggedNoteId;
                this._swallowAccidentalClick.until = Date.now() + 350;
              } else if (this._laneDrag.fromAccidental) {
                const $acc = this.$staffEl.find(`.accidental[data-for-note-id="${draggedNoteId}"]`).first();
                if ($acc.length) playSmokePuffAtElement($acc[0], { parentEl: document.body });
                this.staff.removeNote(draggedNoteId);
                this._swallowAccidentalClick.noteId = draggedNoteId;
                this._swallowAccidentalClick.until = Date.now() + 350;
              }
            }

            this._laneDrag.noteId = null;
            this._laneDrag.pointerId = null;
            this._laneDrag.fromAccidental = false;
            this._laneDrag.startPageX = 0;
            this._laneDrag.startPageY = 0;
            this._laneDrag.startNoteX = null;
            this._laneDrag.movedPx = 0;
            this._laneDrag.isDragging = false;
            this._laneDrag.lastTargetStep = null;
            this._laneDrag.lastTargetX = null;
            this._laneDrag.lastSoundStep = null;
          });
      });

    this._laneDragWired = true;
  }

  _patchAccidentalRepositionForClefWrapper() {
    if (this._accidentalRepositionPatched) return;
    const baseReposition = this.staff._repositionAllAccidentals.bind(this.staff);
    this.staff._repositionAllAccidentals = (...args) => {
      const out = baseReposition(...args);
      this._placeAccidentalsInsideClefWrapper();
      return out;
    };
    this._accidentalRepositionPatched = true;
  }

  _placeAccidentalsInsideClefWrapper() {
    const $wrapper = this.$staffEl.find("#clef-wrapper").first();
    if (!$wrapper.length) return;
    $wrapper.css("pointer-events", "auto");
    $wrapper.find("img").css("pointer-events", "none");

    const wrapperLeft = parseFloat($wrapper.css("left")) || 0;
    const wrapperTop = parseFloat($wrapper.css("top")) || 0;

    this.$staffEl.find(".accidental").each((_, el) => {
      const $acc = $(el);
      const noteId = String($acc.attr("data-for-note-id") || "");
      if (!noteId) return;

      const $note = this.$staffEl.find(`.note[data-note-id="${noteId}"]`);
      if (!$note.length) return;

      const noteLeft = parseFloat($note.css("left"));
      const noteTop = parseFloat($note.css("top"));
      const anchorX = this.staff._accidentalAnchorXForNote(noteLeft);

      $acc.css({
        left: `${anchorX - this.staff.opts.accidentalGapPx - wrapperLeft}px`,
        top: `${noteTop - this.staff.opts.accidentalTopPx - wrapperTop}px`,
      });

      if ($acc.parent()[0] !== $wrapper[0]) $wrapper.append($acc);
    });
  }

}
