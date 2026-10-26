import {
  ACCIDENTAL_CLASSES,
  CLEF_LAYOUT_VARS,
  pxFromCss,
  getPointerPageXY,
  getPointerId,
  normalizeClef,
  stepToLetterOctave,
  toolTypeFromEl,
  nextAccidentalClass,
  isMaxedDouble,
} from "./staffUtils.js";
import { StaffAnimations } from "./StaffAnimations.js";
import { GameAudio } from "../games/shared/GameAudio.js";

/**
 * Staff engine: draws staff, manages note interactions, emits events.
 * Requires: jQuery, jQuery UI (draggable/droppable), optional Tone.js.
 */
export class Staff {
  constructor($el, opts) {
    this.$el = $el;
    const css = getComputedStyle($el[0]);

    this.opts = $.extend(
      {
        paddingX: pxFromCss(css, "--staff-padding-x", 20),
        lineGap: pxFromCss(css, "--staff-line-gap", 16),
        lineThickness: pxFromCss(css, "--staff-line-thickness", 3),
        noteOverlapGap: pxFromCss(css, "--note-overlap-gap", -6),

        noteIdPrefix: "n",

        /** If you want default clef URLs, pass them in from page/game:
         *  clefUrls: { treble,bass,alto,tenor }
         */
        clefUrls: null,

        clef: null,
        clefUrl: null,
        autoClef: true,
        maxLedgerAbove: 2,
        maxLedgerBelow: 2,

        accidentalTopPx: 20,
        accidentalGapPx: 16,

        getMaxUserNotes: () => Infinity,

        sound: true,
        showLineNames: false,
        formatLineName: null,
        accSnapMaxPx: pxFromCss(css, "--staff-line-gap", 25) * 1.2,
      },
      opts || {},
    );

    this.opts.clef = this.opts.clef == null ? null : normalizeClef(this.opts.clef);
    if (this.opts.clef && !this.opts.clefUrl) {
      this.opts.clefUrl = this._clefUrlFor(this.opts.clef);
    }

    this.opts.stepSize = this.opts.lineGap / 2;

    this.$el.css("position", "relative");
    this._baseHeightPx = this.$el.height();
    this._idCounter = 1;

    this._drag = {
      isDragging: false,
      movedPx: 0,
      startPageY: 0,
      noteId: null,
      thresholdPx: 5,
      swallowClick: false,
      startStep: null,
      lastTargetStep: null,
      lastSoundStep: null,
      dropOnOccupied: false,
      outOfRange: false,
    };

    this._previewState = { active: false, step: null };
    this._preview = null;
    this._previewStep = null;

    this._audioReady = false;
    this._synth = null;
    this._heldMidi = null;
    this._holdSoundTimer = null;
    this._pendingHeldStep = null;
    this._pendingHeldAccidentalOffset = 0;
    this._holdSoundDelayMs = 140;
    this._accDragSound = { noteId: null, step: null, toolType: null, prospectiveCls: null };
    this._accSnap = { noteId: null, dist: null, localY: null };

    this._suppressNextClick = { noteId: null, until: 0 };
    this._animations = new StaffAnimations(this.$el);
    this._blockedSteps = new Set();

    this._applyClefCssVars(this.opts.clef);
    this._computeLayout();
    this._drawLines();

    if (this.opts.autoClef && !this.opts.clef) {
      this.setClef("treble");
    } else if (this.opts.clef) {
      this._applyClefCssVars(this.opts.clef);
      this.relayout();
    }
  }

  _clefUrlFor(clef) {
    const c = normalizeClef(clef);
    if (!c) return null;

    const urls = this.opts.clefUrls || {};
    return urls[c] || urls.treble || null;
  }

  setSoundEnabled(enabled) {
    this.opts.sound = !!enabled;
    if (!this.opts.sound && window.Tone) {
      try { Tone.Transport && Tone.Transport.stop(); } catch (_) {}
      try { Tone.context && Tone.context.suspend && Tone.context.suspend(); } catch (_) {}
      try { this._synth && this._synth.releaseAll && this._synth.releaseAll(); } catch (_) {}
      this._releaseHeldStep();
      this._audioReady = false;
    }
  }

  isSoundEnabled() { return !!this.opts.sound; }
  _soundEnabled() { return !!this.opts.sound; }

  _applyClefCssVars(clef) {
    const c = normalizeClef(clef);
    const vars = CLEF_LAYOUT_VARS[c] || CLEF_LAYOUT_VARS.treble;
    const el = this.$el[0];
    Object.keys(vars).forEach((k) => el.style.setProperty(k, vars[k]));
  }

  setClef(clef) {
    this.opts.clef = normalizeClef(clef);
    this.opts.clefUrl = this._clefUrlFor(this.opts.clef);
    this._applyClefCssVars(this.opts.clef);
    this.relayout();
  }

  getClef() { return this.opts.clef || "treble"; }

  isStepAllowed(step) {
    return this._isStepAllowed(step);
  }

  setBlockedSteps(steps) {
    const next = new Set();
    const list = Array.isArray(steps) ? steps : [];
    for (let i = 0; i < list.length; i++) {
      const step = Number(list[i]);
      if (Number.isFinite(step)) next.add(step);
    }
    this._blockedSteps = next;
  }

  clearBlockedSteps() {
    this._blockedSteps.clear();
  }

  isStepBlocked(step, excludeId = null) {
    if (excludeId && this.isNoteFixed(excludeId)) return false;
    return this._blockedSteps.has(Number(step));
  }

  ledgerStepsFor(step) {
    return this._ledgerStepsFor(step);
  }

  _maxUserNotes() {
    const v = this.opts.getMaxUserNotes ? this.opts.getMaxUserNotes() : Infinity;
    return Number.isFinite(v) ? v : Infinity;
  }

  _userNoteCount() {
    return this.$el.find(".note").not(".fixed").not(".preview").not(".hint").length;
  }

  _computeLayout() {
    const h = Number.isFinite(this._baseHeightPx) && this._baseHeightPx > 0
      ? this._baseHeightPx
      : this.$el.height();
    const staffHeight = this.opts.lineGap * 4;
    const topLineY = Math.round((h - staffHeight) / 2);
    this.opts.bottomLineY = topLineY + staffHeight;
  }

  _syncDynamicHeight() {
    const baseHeight = Number.isFinite(this._baseHeightPx) && this._baseHeightPx > 0
      ? this._baseHeightPx
      : this.$el.height();
    let requiredHeight = baseHeight;

    this.$el.find(".note, .ledger").each((_, el) => {
      const $node = $(el);
      const top = parseFloat($node.css("top"));
      if (!Number.isFinite(top)) return;
      const outerHeight = $node.outerHeight() || 0;
      const bottom = top + outerHeight + this.opts.lineGap;
      if (bottom > requiredHeight) requiredHeight = bottom;
    });

    const finalHeight = Math.max(baseHeight, Math.ceil(requiredHeight));
    if (this.$el.height() !== finalHeight) this.$el.height(finalHeight);
  }

  _drawLines() {
    this.$el.find(".staff-line, .staff-clef, #clef-wrapper").remove();
    for (let i = 0; i < 5; i++) {
      const y = this.opts.bottomLineY - (4 - i) * this.opts.lineGap;
      const step = (4 - i) * 2;
      const durationSec = (0.12 + (Math.random() * 0.36)).toFixed(3); // ~0.12s..0.48s
      const $line = $('<div class="staff-line"></div>')
        .css({
          top: `${y}px`,
          animationDuration: `${durationSec}s`,
        });

      const lineName = this._lineNameForStep(step);
      if (lineName) {
        $('<span class="line-note-name"></span>')
          .text(lineName)
          .appendTo($line);
      }

      $line.appendTo(this.$el);
    }
    this._drawClef();
  }

  _lineNameForStep(step) {
    if (!this.opts.showLineNames) return "";

    const noteState = stepToLetterOctave(this, step);
    const letter = String(noteState?.letter || "").trim().toUpperCase();
    if (!letter) return "";

    const formatter = this.opts.formatLineName;
    if (typeof formatter === "function") {
      return String(formatter(letter, {
        step,
        octave: noteState?.octave,
        clef: this.getClef(),
      }) || "");
    }

    return letter;
  }

  _drawClef() {
    if (!this.opts.clefUrl) return;

    const clef = normalizeClef(this.opts.clef);
    const $img = $('<img alt="">').attr("src", this.opts.clefUrl);

    $('<div id="clef-wrapper"></div>')
      .addClass(`${clef}-clef`)
      .append($img)
      .appendTo(this.$el);
  }

  relayout() {
    this._computeLayout();
    this._drawLines();
    this._resolveNoteOverlaps();
    this._repositionAllAccidentals();
    this._syncDynamicHeight();
  }

  centerX() { return this.$el.width() / 2; }
  stepToY(step) { return this.opts.bottomLineY - step * this.opts.stepSize; }
  yToStep(y) { return Math.round((this.opts.bottomLineY - y) / this.opts.stepSize); }
  _pageYToLocalY(pageY) { return pageY - this.$el.offset().top; }

  minStepAllowed() { return 0 - this.opts.maxLedgerBelow * 2; }
  maxStepAllowed() { return 8 + this.opts.maxLedgerAbove * 2; }
  _isStepAllowed(step) { return step >= this.minStepAllowed() && step <= this.maxStepAllowed(); }

  _ledgerStepsFor(step) {
    const ledgers = [];
    const topMost = 8 + this.opts.maxLedgerAbove * 2;
    const bottomMost = 0 - this.opts.maxLedgerBelow * 2;

    if (step > 8) {
      const capped = Math.min(step, topMost);
      for (let s = 10; s <= capped; s += 2) ledgers.push(s);
    } else if (step < 0) {
      const capped = Math.max(step, bottomMost);
      for (let s = -2; s >= capped; s -= 2) ledgers.push(s);
    }
    return ledgers;
  }

  _renderLedgers(id, x, step) {
    this.$el.find(`.ledger[data-for-note-id="${id}"]`).remove();
    const isDragging = this.$el.find(`.note[data-note-id="${id}"]`).hasClass("dragging");
    const steps = this._ledgerStepsFor(step);

    for (let i = 0; i < steps.length; i++) {
      const $l = $('<div class="ledger"></div>')
        .attr("data-for-note-id", id)
        .css({ left: `${x}px`, top: `${this.stepToY(steps[i])}px` });

      if (isDragging) $l.addClass("dragging");
      $l.appendTo(this.$el);
    }
  }

  _previewLedgersClear() { this.$el.find(".ledger.preview").remove(); }

  _previewLedgersSet(step) {
    this._previewLedgersClear();
    const x = this.centerX();
    const steps = this._ledgerStepsFor(step);
    for (let i = 0; i < steps.length; i++) {
      $('<div class="ledger preview"></div>')
        .css({ left: `${x}px`, top: `${this.stepToY(steps[i])}px` })
        .appendTo(this.$el);
    }
  }

  _stepOfNoteEl(el) {
    const topStr = el.style.top || window.getComputedStyle(el).top;
    return this.yToStep(parseFloat(topStr));
  }

  _noteLocksX(elOrId) {
    if (!elOrId) return false;
    const el = typeof elOrId === "string"
      ? this.$el.find(`.note[data-note-id="${elOrId}"]`)[0]
      : elOrId;
    if (!el) return false;
    const attr = String(el.getAttribute("data-lock-x") || "").trim().toLowerCase();
    return attr === "true" || attr === "1" || $(el).hasClass("lock-x");
  }

  _isStepOccupied(step, excludeId) {
    if (this.isStepBlocked(step, excludeId)) return true;
    const nodes = this.$el.find(".note").toArray();
    for (let i = 0; i < nodes.length; i++) {
      const el = nodes[i];
      if (!el) continue;
      const id = el.getAttribute("data-note-id");
      if (excludeId && id === excludeId) continue;
      if (this._stepOfNoteEl(el) === step) return true;
    }
    return false;
  }

  _getNoteIdAtStep(step, excludeId) {
    if (this.isStepBlocked(step, excludeId)) return null;
    const nodes = this.$el.find(".note").toArray();
    for (let i = 0; i < nodes.length; i++) {
      const el = nodes[i];
      if (!el) continue;
      const id = el.getAttribute("data-note-id");
      if (excludeId && id === excludeId) continue;
      if (this._stepOfNoteEl(el) === step) return id;
    }
    return null;
  }

  _isCenteredX(noteId) {
    const $n = this.$el.find(`.note[data-note-id="${noteId}"]`);
    if (!$n.length) return true;
    return Math.abs(parseFloat($n.css("left")) - this.centerX()) <= 0.5;
  }

  isNoteFixed(noteId) {
    const $note = this.$el.find(`.note[data-note-id="${noteId}"]`);
    return $note.length ? $note.hasClass("fixed") : false;
  }

  _nearestEditableNoteByLocalY(localY) {
    const maxD = Number.isFinite(this.opts.accSnapMaxPx) ? this.opts.accSnapMaxPx : this.opts.lineGap * 1.2;
    let best = null;

    const nodes = this.$el.find(".note").not(".preview").toArray();
    for (let i = 0; i < nodes.length; i++) {
      const el = nodes[i];
      const id = el.getAttribute("data-note-id");
      if (!id) continue;
      if ($(el).hasClass("fixed")) continue;

      const top = parseFloat(el.style.top || window.getComputedStyle(el).top);
      const d = Math.abs(top - localY);
      if (best == null || d < best.dist) best = { noteId: id, dist: d };
    }

    if (!best || best.dist > maxD) return null;
    return best;
  }

  _removeAccidentalForNote(noteId) {
    this.$el.find(`.accidental[data-for-note-id="${noteId}"]`).remove();
  }

  _accidentalAnchorXForNote(noteLeftPx) {
    const cx = this.centerX();
    const EPS = 0.5;
    if (Number.isFinite(noteLeftPx) && noteLeftPx > cx + EPS) return cx;
    return noteLeftPx;
  }

  _positionAccidentalForNote(noteId) {
    const $note = this.$el.find(`.note[data-note-id="${noteId}"]`);
    const $acc = this.$el.find(`.accidental[data-for-note-id="${noteId}"]`);
    if (!$note.length || !$acc.length) return;

    const noteLeft = parseFloat($note.css("left"));
    const noteTop = parseFloat($note.css("top"));
    const anchorX = this._accidentalAnchorXForNote(noteLeft);

    $acc.css({
      left: `${anchorX - this.opts.accidentalGapPx}px`,
      top: `${noteTop - this.opts.accidentalTopPx}px`,
    });
  }

  _rectsOverlap(a, b) {
    return !(a.right <= b.left || a.left >= b.right || a.bottom <= b.top || a.top >= b.bottom);
  }

  _repositionAllAccidentals() {
    this.$el.find(".accidental").each((_, node) => {
      const id = node.getAttribute("data-for-note-id");
      if (id) this._positionAccidentalForNote(id);
    });

    const noteEls = this.$el.find(".note").not(".preview").toArray();
    const stepToNoteId = {};
    for (let i = 0; i < noteEls.length; i++) {
      const el = noteEls[i];
      const id = el.getAttribute("data-note-id");
      if (!id) continue;
      stepToNoteId[this._stepOfNoteEl(el)] = id;
    }

    const steps = Object.keys(stepToNoteId)
      .map((s) => parseInt(s, 10))
      .sort((a, b) => a - b);

    for (let i = 0; i < steps.length; i++) {
      const lowerStep = steps[i];
      const upperStep = lowerStep + 1;
      const lowerId = stepToNoteId[lowerStep];
      const upperId = stepToNoteId[upperStep];
      if (!lowerId || !upperId) continue;

      const $lowerAcc = this.$el.find(`.accidental[data-for-note-id="${lowerId}"]`);
      const $upperAcc = this.$el.find(`.accidental[data-for-note-id="${upperId}"]`);
      if (!$lowerAcc.length || !$upperAcc.length) continue;

      const lowerRect = $lowerAcc[0].getBoundingClientRect();
      const upperRect = $upperAcc[0].getBoundingClientRect();
      if (!this._rectsOverlap(lowerRect, upperRect)) continue;

      const overlapPx = Math.max(0, upperRect.right - lowerRect.left);
      const padPx = -6;
      const curLeft = parseFloat($upperAcc.css("left"));
      if (!Number.isFinite(curLeft)) continue;

      $upperAcc.css("left", `${curLeft - (overlapPx + padPx)}px`);
    }
  }

  _getAttachedAccidentalClass(noteId) {
    const $acc = this.$el.find(`.accidental[data-for-note-id="${noteId}"]`);
    if (!$acc.length) return null;
    for (let i = 0; i < ACCIDENTAL_CLASSES.length; i++) {
      const cls = ACCIDENTAL_CLASSES[i];
      if ($acc.hasClass(cls)) return cls;
    }
    return null;
  }

  attachAccidentalToNote(noteId, accidentalClass) {
    if (!noteId || this.isNoteFixed(noteId)) return;
    this._removeAccidentalForNote(noteId);

    const $acc = $('<div class="accidental music-font"></div>')
      .addClass(accidentalClass)
      .attr("data-for-note-id", noteId);

    this.$el.append($acc);
    this._repositionAllAccidentals();
  }

  _hintIgnoredAccidental(noteId) {
    const $note = this.$el.find(`.note[data-note-id="${noteId}"]`);
    if (!$note.length) return;

    $note.removeClass("animate__animated animate__headShake");
    void $note[0].offsetWidth;
    $note.addClass("animate__animated animate__headShake");

    $note
      .off("animationend._hint webkitAnimationEnd._hint oAnimationEnd._hint MSAnimationEnd._hint")
      .one("animationend._hint webkitAnimationEnd._hint oAnimationEnd._hint MSAnimationEnd._hint", () => {
        $note.removeClass("animate__animated animate__headShake");
      });
  }

  applyAccidentalToolToNote(noteId, toolType) {
    if (!noteId || this.isNoteFixed(noteId)) return false;

    const currentCls = this._getAttachedAccidentalClass(noteId);

    if (isMaxedDouble(currentCls, toolType)) {
      this._hintIgnoredAccidental(noteId);
      return false;
    }

    const nextCls = nextAccidentalClass(currentCls, toolType);
    if (toolType === "natural" && currentCls === "music-font__natural") return false;
    if (nextCls === currentCls) return false;

    this.attachAccidentalToNote(noteId, nextCls);
    this._emitNoteState(noteId, "user");
    return true;
  }

  async _ensureAudio() {
    if (!this._soundEnabled()) return;
    if (this._audioReady) return;
    if (!window.Tone) return;

    await Tone.start();
    this._synth = GameAudio.createStaffNoteSynth();
    this._audioReady = true;
  }

  _stepToMidi(step) {
    const diatonic = [0, 2, 4, 5, 7, 9, 11];

    let baseC;
    let baseIndex;

    switch (this.opts.clef) {
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
    const noteIndex = ((idx % 7) + 7) % 7;

    return baseC + diatonic[noteIndex] + octaveShift * 12;
  }

  _accidentalClassToOffset(cls) {
    if (!cls) return 0;
    if (cls.includes("music-font__doublesharp")) return +2;
    if (cls.includes("music-font__sharp")) return +1;
    if (cls.includes("music-font__doubleflat")) return -2;
    if (cls.includes("music-font__flat")) return -1;
    return 0;
  }

  _emitNoteState(noteId, source) {
    const $note = this.$el.find(`.note[data-note-id="${noteId}"]`);
    if (!$note.length) return;

    const step = this.yToStep(parseFloat($note.css("top")));
    const accCls = this._getAttachedAccidentalClass(noteId);
    const accOff = this._accidentalClassToOffset(accCls);
    const midi = this._stepToMidi(step) + accOff;

    this.$el.trigger("staff:noteState", {
      noteId,
      step,
      accidentalClass: accCls,
      midi,
      source: source || "unknown",
    });
  }

  async _playStep(step, accidentalOffset) {
    if (!this._soundEnabled() || !Number.isFinite(step)) return;
    await this._ensureAudio();
    if (!this._synth) return;
    const midi = this._stepToMidi(step) + (accidentalOffset || 0);
    if (this._synth.triggerRelease) this._synth.triggerRelease();
    this._synth.triggerAttackRelease(Tone.Frequency(midi, "midi"), 0.5, undefined, GameAudio.scale("staffNote", 1));
  }

  async playStep(step, accidentalOffset = 0) {
    await this._playStep(step, accidentalOffset);
  }

  _releaseHeldStep() {
    if (this._holdSoundTimer) {
      window.clearTimeout(this._holdSoundTimer);
      this._holdSoundTimer = null;
    }
    this._pendingHeldStep = null;
    this._pendingHeldAccidentalOffset = 0;
    if (this._heldMidi != null && this._synth?.triggerRelease) this._synth.triggerRelease();
    this._heldMidi = null;
  }

  async _startHeldStep(step, accidentalOffset = 0) {
    if (!this._soundEnabled() || !Number.isFinite(step)) return;
    await this._ensureAudio();
    if (!this._synth) return;
    if (this._pendingHeldStep !== step) return;

    const midi = this._stepToMidi(step) + (accidentalOffset || 0);
    if (this._heldMidi === midi) return;

    if (this._synth.triggerRelease) this._synth.triggerRelease();
    this._synth.triggerAttack(Tone.Frequency(midi, "midi"), undefined, GameAudio.scale("staffNote", 1));
    this._heldMidi = midi;
  }

  _scheduleHeldStep(step, accidentalOffset = 0) {
    if (!this._soundEnabled() || !Number.isFinite(step)) return;

    this._pendingHeldStep = step;
    this._pendingHeldAccidentalOffset = accidentalOffset || 0;
    if (this._heldMidi != null) {
      void this._startHeldStep(step, this._pendingHeldAccidentalOffset);
      return;
    }
    if (this._holdSoundTimer) return;

    this._holdSoundTimer = window.setTimeout(() => {
      this._holdSoundTimer = null;
      void this._startHeldStep(this._pendingHeldStep, this._pendingHeldAccidentalOffset);
    }, this._holdSoundDelayMs);
  }

  setNoteFixed(noteId, fixed) {
    const on = !!fixed;
    this.$el.find(`.note[data-note-id="${noteId}"]`).toggleClass("fixed", on);
    this.$el.find(`.ledger[data-for-note-id="${noteId}"]`).toggleClass("fixed", on);
    this.$el.find(`.accidental[data-for-note-id="${noteId}"]`).toggleClass("fixed", on);
  }

  addFixedNote(cfg) {
    const c = cfg || {};
    const id = this.addNote({
      step: c.step,
      y: c.y,
      x: c.x,
      id: c.id,
      ledger: c.ledger,
      className: c.className || "",
    });

    if (!id) return null;
    if (c.accidentalClass) this.attachAccidentalToNote(id, c.accidentalClass);
    this.setNoteFixed(id, true);
    return id;
  }

  clearNotes() {
    this.$el.find(".note, .ledger, .accidental").remove();
    this._previewClear();
    this._syncDynamicHeight();
    this.$el.trigger("staff:userNotesChanged", { count: this._userNoteCount() });
  }

  removeNote(id, opts = {}) {
    if (opts.smoke === true) {
      const noteEl = this.$el.find(`.note[data-note-id="${id}"]`)[0];
      this._animations.playNoteRemoveSmoke(noteEl);
    }
    this.$el.find(`.note[data-note-id="${id}"]`).remove();
    this.$el.find(`.ledger[data-for-note-id="${id}"]`).remove();
    this._removeAccidentalForNote(id);
    this._resolveNoteOverlaps();
    this._syncDynamicHeight();
    this.$el.trigger("staff:userNotesChanged", { count: this._userNoteCount() });
  }

  addNote(cfg) {
    const c = cfg || {};
    const step = Number.isFinite(c.step) ? Number(c.step) : null;

    if (Number.isFinite(step) && !this._isStepAllowed(step)) return null;
    if (Number.isFinite(step) && !c.allowOccupied && this._isStepOccupied(step, null)) return null;

    const y = Number.isFinite(c.y)
      ? Number(c.y)
      : Number.isFinite(step)
        ? this.stepToY(step)
        : null;

    if (!Number.isFinite(y)) throw new Error("addNote: provide either y or step");

    const x = Number.isFinite(c.x) ? Number(c.x) : this.centerX();
    const id = c.id || `${this.opts.noteIdPrefix}${this._idCounter++}`;

    const $note = $('<div class="note"><span class="lettername"></span></div>')
      .attr("data-note-id", id)
      .css({ left: `${x}px`, top: `${y}px` });

    if (c.className) $note.addClass(c.className);
    this.$el.append($note);

    if ((c.ledger === true) || (c.ledger !== false && Number.isFinite(step))) {
      this._renderLedgers(id, x, step);
    }

    if (!c.skipResolve) this._resolveNoteOverlaps();
    else this._repositionAllAccidentals();
    this._syncDynamicHeight();

    return id;
  }

  moveNote(id, pos) {
    const p = pos || {};
    const $note = this.$el.find(`.note[data-note-id="${id}"]`);
    if (!$note.length) return;

    const x = Number.isFinite(p.x) ? Number(p.x) : null;
    const step = Number.isFinite(p.step) ? Number(p.step) : null;
    if (Number.isFinite(step) && !this._isStepAllowed(step)) return;

    const y = Number.isFinite(p.y)
      ? Number(p.y)
      : Number.isFinite(step)
        ? this.stepToY(step)
        : null;

    if (Number.isFinite(x)) $note.css("left", `${x}px`);
    if (Number.isFinite(y)) $note.css("top", `${y}px`);

    if (Number.isFinite(step)) {
      const noteX = Number.isFinite(x) ? x : parseFloat($note.css("left"));
      this._renderLedgers(id, noteX, step);
    }

    this._repositionAllAccidentals();
    this._syncDynamicHeight();
  }

  _previewSet(step) {
    if (!this._preview) this._preview = $('<div class="note preview"></div>').appendTo(this.$el);
    this._preview.css({ left: `${this.centerX()}px`, top: `${this.stepToY(step)}px` });
    this._previewStep = step;
    this._previewLedgersSet(step);
    this._syncDynamicHeight();
  }

  _previewClear() {
    if (this._preview) {
      this._preview.remove();
      this._preview = null;
      this._previewStep = null;
    }
    this._previewLedgersClear();
    this._syncDynamicHeight();
  }

  _resolveNoteOverlaps() {
    const self = this;
    const GAP = this.opts.noteOverlapGap;
    const MAX_ITERS = 20;

    function notesArray() {
      return self.$el.find(".note").toArray().map((el) => {
        const $el = $(el);
        return {
          el,
          $el,
          step: self.yToStep(parseFloat($el.css("top"))),
          lockX: self._noteLocksX(el),
        };
      });
    }

    function noteByStep(list) {
      const map = {};
      for (let i = 0; i < list.length; i++) {
        const s = list[i].step;
        (map[s] || (map[s] = [])).push(list[i]);
      }
      return map;
    }

    function centerAll(list) {
      const cx = self.centerX();
      for (let i = 0; i < list.length; i++) {
        if (list[i].lockX) continue;
        const id = list[i].$el.attr("data-note-id");
        self.moveNote(id, { x: cx, step: list[i].step });
        list[i]._shifted = false;
      }
    }

    function shiftUpperToTouch(upper, stepMap) {
      if (upper.lockX) return false;
      const upperRect = upper.el.getBoundingClientRect();
      const lowerEls = stepMap[upper.step - 1];
      if (!lowerEls || !lowerEls.length) return false;

      const lowerRect = lowerEls[0].el.getBoundingClientRect();
      if (!self._rectsOverlap(upperRect, lowerRect)) return false;

      const dx = (lowerRect.right - upperRect.left) + GAP;
      const id = upper.$el.attr("data-note-id");
      self.moveNote(id, { x: parseFloat(upper.$el.css("left")) + dx, step: upper.step });
      return true;
    }

    for (let iter = 0; iter < MAX_ITERS; iter++) {
      const list = notesArray();
      if (!list.length) return;

      centerAll(list);

      const stepMap = noteByStep(list);
      const steps = Object.keys(stepMap).map((s) => parseInt(s, 10)).sort((a, b) => a - b);

      let changed = false;

      for (let i = 0; i < steps.length; i++) {
        const s = steps[i];
        if (!stepMap[s] || !stepMap[s + 1]) continue;

        const lower = stepMap[s][0];
        const upper = stepMap[s + 1][0];

        if (!lower._shifted) {
          if (shiftUpperToTouch(upper, stepMap)) changed = true;
          upper._shifted = true;
        }
      }

      if (!changed) {
        self._repositionAllAccidentals();
        return;
      }
    }

    self._repositionAllAccidentals();
  }

  _setDraggingVisual(noteId, on) {
    const $note = this.$el.find(`.note[data-note-id="${noteId}"]`);
    const $ledgers = this.$el.find(`.ledger[data-for-note-id="${noteId}"]`);
    const $acc = this.$el.find(`.accidental[data-for-note-id="${noteId}"]`);
    $note.toggleClass("dragging", !!on);
    $ledgers.toggleClass("dragging", !!on);
    $acc.toggleClass("dragging", !!on);
  }

  _applyDraggedAdjacencyX(dragId) {
    if (this._noteLocksX(dragId)) return;
    const $drag = this.$el.find(`.note[data-note-id="${dragId}"]`);
    if (!$drag.length) return;

    const dragStep = this.yToStep(parseFloat($drag.css("top")));
    const center = this.centerX();
    const gap = this.opts.noteOverlapGap;

    this.moveNote(dragId, { x: center });

    const lowerId = this._getNoteIdAtStep(dragStep - 1, dragId);
    if (!lowerId) return;
    if (!this._isCenteredX(lowerId)) return;

    const $lower = this.$el.find(`.note[data-note-id="${lowerId}"]`);
    if (!$lower.length) return;

    const upperRect = $drag[0].getBoundingClientRect();
    const lowerRect = $lower[0].getBoundingClientRect();
    if (!this._rectsOverlap(upperRect, lowerRect)) return;

    const dx = (lowerRect.right - upperRect.left) + gap;
    this.moveNote(dragId, { x: center + dx });
  }

  enableGhostClickCreate() {
    const self = this;

    this.$el.off(".previewCreate");
    $(window).off("blur.previewCreate");

    this.$el.on("pointerdown.previewCreate", function (e) {
      if ($(e.target).closest(".note, .accidental").length) return;
      if (self._userNoteCount() >= self._maxUserNotes()) return;

      e.preventDefault();

      const { y: pageY } = getPointerPageXY(e);
      const initialStep = self.yToStep(self._pageYToLocalY(pageY));
      if (!self._isStepAllowed(initialStep)) return;
      if (self.isStepBlocked(initialStep, null)) return;

      self._previewState.active = true;
      self._previewState.step = initialStep;
      self._previewState.lastSoundStep = initialStep;
      self._previewSet(initialStep);

      if (self._soundEnabled()) {
        self._ensureAudio();
        self._playStep(initialStep, 0);
        self._scheduleHeldStep(initialStep, 0);
      }

      const pointerId = getPointerId(e);
      if (this.setPointerCapture && pointerId != null) this.setPointerCapture(pointerId);

      self.$el.off("pointermove.previewCreate").on("pointermove.previewCreate", function (ev) {
        if (!self._previewState.active) return;

        if (self._userNoteCount() >= self._maxUserNotes()) {
          self._previewState.active = false;
          self._releaseHeldStep();
          self._previewClear();
          self.$el.off("pointermove.previewCreate pointerup.previewCreate pointercancel.previewCreate");
          return;
        }

        const { y: py } = getPointerPageXY(ev);
        const s = self.yToStep(self._pageYToLocalY(py));
        if (!self._isStepAllowed(s)) return;
        if (self.isStepBlocked(s, null)) {
          self._previewState.step = null;
          self._releaseHeldStep();
          self._previewClear();
          return;
        }

        self._previewState.step = s;
        self._previewSet(s);

        if (self._soundEnabled() && s !== self._previewState.lastSoundStep) {
          self._previewState.lastSoundStep = s;
          self._scheduleHeldStep(s, 0);
        }
      });

      self.$el
        .off("pointerup.previewCreate pointercancel.previewCreate")
        .on("pointerup.previewCreate pointercancel.previewCreate", function () {
          if (!self._previewState.active) return;

          self._previewState.active = false;
          const finalStep = self._previewState.step;

          self._releaseHeldStep();
          self._previewClear();
          self.$el.off("pointermove.previewCreate pointerup.previewCreate pointercancel.previewCreate");

          if (!Number.isFinite(finalStep)) return;
          if (!self._isStepAllowed(finalStep)) return;
          if (self.isStepBlocked(finalStep, null)) return;
          if (self._isStepOccupied(finalStep, null)) return;
          if (self._userNoteCount() >= self._maxUserNotes()) return;

          const createdId = self.addNote({ step: finalStep });
          if (createdId) {
            self.$el.trigger("staff:userNoteAdded", { noteId: createdId, step: finalStep });
            self._emitNoteState(createdId, "user");
            self._suppressNextClick.noteId = createdId;
            self._suppressNextClick.until = Date.now() + 700;
          }
        });
    });

    $(window).on("blur.previewCreate", function () {
      if (!self._previewState.active) return;
      self._previewState.active = false;
      self._releaseHeldStep();
      self._previewClear();
      self.$el.off("pointermove.previewCreate pointerup.previewCreate pointercancel.previewCreate");
    });
  }

  enableNoteDragAndClickDelete() {
    const self = this;
    const d = this._drag;

    this.$el.off(".noteDrag");

    function startDragFromNoteEl(noteEl, e) {
      e.preventDefault();
      if ($(noteEl).hasClass("fixed")) return;

      if (e.stopImmediatePropagation) e.stopImmediatePropagation();
      else e.stopPropagation();

      const pointerId = getPointerId(e);
      const $note = $(noteEl);

      d.isDragging = false;
      d.movedPx = 0;
      d.startPageY = getPointerPageXY(e).y;
      d.noteId = $note.attr("data-note-id");

      d.startStep = self.yToStep(parseFloat($note.css("top")));
      d.lastTargetStep = d.startStep;
      d.lastSoundStep = d.startStep;
      d.dropOnOccupied = false;
      d.outOfRange = false;

      self._setDraggingVisual(d.noteId, true);
      if (self._soundEnabled()) {
        const accCls = self._getAttachedAccidentalClass(d.noteId);
        const accOff = self._accidentalClassToOffset(accCls);
        self._scheduleHeldStep(d.startStep, accOff);
      }

      const capEl = e.currentTarget && e.currentTarget.setPointerCapture ? e.currentTarget : null;
      if (capEl && pointerId != null) capEl.setPointerCapture(pointerId);

      self.$el.off("pointermove.noteDrag").on("pointermove.noteDrag", function (ev) {
        const evPointerId = getPointerId(ev);
        if (pointerId != null && evPointerId != null && evPointerId !== pointerId) return;

        const py = getPointerPageXY(ev).y;
        const dy = py - d.startPageY;
        d.movedPx = Math.max(d.movedPx, Math.abs(dy));

        if (!d.isDragging && d.movedPx >= d.thresholdPx) {
          d.isDragging = true;
          const accCls = self._getAttachedAccidentalClass(d.noteId);
          const accOff = self._accidentalClassToOffset(accCls);
          self._pendingHeldStep = d.lastTargetStep;
          self._pendingHeldAccidentalOffset = accOff;
          void self._startHeldStep(d.lastTargetStep, accOff);
        }
        if (!d.isDragging) return;

        const targetStep = self.yToStep(self._pageYToLocalY(py));
        if (!self._isStepAllowed(targetStep)) {
          d.outOfRange = true;
          return;
        }
        d.outOfRange = false;

        d.lastTargetStep = targetStep;

        self.moveNote(d.noteId, { step: targetStep });
        d.dropOnOccupied = self._isStepOccupied(targetStep, d.noteId);
        self._applyDraggedAdjacencyX(d.noteId);

        if (self._soundEnabled() && targetStep !== d.lastSoundStep) {
          d.lastSoundStep = targetStep;
          const accCls = self._getAttachedAccidentalClass(d.noteId);
          const accOff = self._accidentalClassToOffset(accCls);
          self._scheduleHeldStep(targetStep, accOff);
        }
      });

      self.$el
        .off("pointerup.noteDrag pointercancel.noteDrag")
        .on("pointerup.noteDrag pointercancel.noteDrag", function (ev2) {
          const evPointerId = getPointerId(ev2);
          if (pointerId != null && evPointerId != null && evPointerId !== pointerId) return;

          self.$el.off("pointermove.noteDrag pointerup.noteDrag pointercancel.noteDrag");

          const hadHeldSound = self._heldMidi != null;
          self._releaseHeldStep();
          d.swallowClick = d.isDragging || hadHeldSound;
          self._setDraggingVisual(d.noteId, false);

          if (d.outOfRange || d.dropOnOccupied) {
            self.removeNote(d.noteId);
          } else {
            self.moveNote(d.noteId, { step: d.lastTargetStep });
            self._resolveNoteOverlaps();
            self._emitNoteState(d.noteId, "user");
          }

          d.noteId = null;
          d.isDragging = false;
          d.movedPx = 0;
          d.startStep = null;
          d.lastTargetStep = null;
          d.lastSoundStep = null;
          d.dropOnOccupied = false;
          d.outOfRange = false;
        });
    }

    this.$el.on("pointerdown.noteDrag", ".accidental", function (e) { e.stopPropagation(); });
    this.$el.on("pointerdown.noteDrag", ".note", function (e) { startDragFromNoteEl(this, e); });

    this.$el.on("pointerdown.noteDrag", function (e) {
      if ($(e.target).closest(".accidental").length) return;
      if ($(e.target).closest(".note").length) return;

      const { y: pageY } = getPointerPageXY(e);
      const step = self.yToStep(self._pageYToLocalY(pageY));
      const idAtStep = self._getNoteIdAtStep(step, null);
      if (!idAtStep) return;

      const noteEl = self.$el.find(`.note[data-note-id="${idAtStep}"]`)[0];
      if (!noteEl) return;

      startDragFromNoteEl(noteEl, e);
    });

    this.$el.on("click.noteDrag", ".accidental", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const noteId = this.getAttribute("data-for-note-id");
      if (!noteId) return;
      if (self.isNoteFixed(noteId)) return;
      self._removeAccidentalForNote(noteId);
      self._repositionAllAccidentals();
      self._emitNoteState(noteId, "user");
    });

    this.$el.on("click.noteDrag", ".note", function (e) {
      const clickedId = $(this).attr("data-note-id");

      if (
        self._suppressNextClick.noteId &&
        clickedId === self._suppressNextClick.noteId &&
        Date.now() < self._suppressNextClick.until
      ) {
        self._suppressNextClick.noteId = null;
        self._suppressNextClick.until = 0;
        e.preventDefault();
        e.stopPropagation();
        return;
      }

      if (d.swallowClick) {
        e.preventDefault();
        e.stopPropagation();
        d.swallowClick = false;
        return;
      }

      self.removeNote(clickedId, { smoke: true });
    });
  }

  enableAccidentalDrag($toolEls) {
    const self = this;
    $toolEls.addClass("accidental-tool");

    function resetAccDrag() {
      self._accDragSound.noteId = null;
      self._accDragSound.step = null;
      self._accDragSound.toolType = null;
      self._accDragSound.prospectiveCls = null;
      self._accSnap.noteId = null;
      self._accSnap.dist = null;
      self._accSnap.localY = null;
    }

    $toolEls.draggable({
      helper: "clone",
      appendTo: "body",
      zIndex: 9999,
      revert: "invalid",
      scroll: false,

      start: function (_event, ui) {
        ui.helper.addClass("dragging accidental-tool");
        if (self._soundEnabled()) self._ensureAudio();
        resetAccDrag();
        self._accDragSound.toolType = toolTypeFromEl($(this));
      },

      drag: function (event) {
        const toolType = self._accDragSound.toolType;
        if (!toolType) return;

        let pageX = event.pageX;
        let pageY = event.pageY;
        if (!Number.isFinite(pageY) && event.originalEvent) pageY = event.originalEvent.pageY;
        if (!Number.isFinite(pageX) && event.originalEvent) pageX = event.originalEvent.pageX;

        const off = self.$el.offset();
        const x = pageX - off.left;
        const y = pageY - off.top;

        self._accSnap.localY = y;

        if (x < 0 || y < 0 || x > self.$el.width() || y > self.$el.height()) {
          resetAccDrag();
          self._accDragSound.toolType = toolType;
          return;
        }

        const nearest = self._nearestEditableNoteByLocalY(y);
        if (!nearest) {
          self._accSnap.noteId = null;
          self._accSnap.dist = null;
          self._accDragSound.noteId = null;
          self._accDragSound.step = null;
          self._accDragSound.prospectiveCls = null;
          return;
        }

        const noteId = nearest.noteId;
        self._accSnap.noteId = noteId;
        self._accSnap.dist = nearest.dist;

        const $note = self.$el.find(`.note[data-note-id="${noteId}"]`);
        if (!$note.length) return;

        const step = self.yToStep(parseFloat($note.css("top")));
        if (!self._isStepAllowed(step)) return;

        const currentCls = self._getAttachedAccidentalClass(noteId);
        const prospectiveCls = nextAccidentalClass(currentCls, toolType);

        if (isMaxedDouble(currentCls, toolType)) {
          self._accDragSound.noteId = null;
          self._accDragSound.step = null;
          self._accDragSound.prospectiveCls = null;
          return;
        }

        if (
          noteId === self._accDragSound.noteId &&
          step === self._accDragSound.step &&
          prospectiveCls === self._accDragSound.prospectiveCls
        ) return;

        self._accDragSound.noteId = noteId;
        self._accDragSound.step = step;
        self._accDragSound.prospectiveCls = prospectiveCls;

        if (self._soundEnabled()) {
          const accOff = self._accidentalClassToOffset(prospectiveCls);
          self._playStep(step, accOff);
        }
      },

      stop: function () { resetAccDrag(); },
    });
  }

  enableAccidentalDropOnStaff() {
    const self = this;

    this.$el.droppable({
      accept: ".accidental-tool",
      tolerance: "pointer",
      drop: function (event, ui) {
        const toolType = toolTypeFromEl(ui.draggable);
        if (!toolType) return;

        let localY;
        if (self._accSnap && self._accSnap.localY != null) localY = self._accSnap.localY;
        else {
          let pageY = event.pageY;
          if (!Number.isFinite(pageY) && event.originalEvent) pageY = event.originalEvent.pageY;
          localY = self._pageYToLocalY(pageY);
        }

        const nearest = self._nearestEditableNoteByLocalY(localY);
        if (!nearest) return;

        self.applyAccidentalToolToNote(nearest.noteId, toolType);
      },
    });
  }
}
