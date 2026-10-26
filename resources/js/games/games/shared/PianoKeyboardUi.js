import { GameAudio } from "./GameAudio.js";
import {
  NATURAL_NOTE_ORDER,
  NATURAL_PITCH_CLASS,
  PITCH_CLASS_TO_NOTE,
  naturalMidiFromLetterOctave,
  naturalMidiFromNoteName,
  naturalNoteNameFromMidi,
  noteNameFromMidi,
  displayNoteName,
} from "./noteNames.js";

export class PianoKeyboardUi {
  static NATURAL_ORDER = NATURAL_NOTE_ORDER;
  static NATURAL_PITCH_CLASS = NATURAL_PITCH_CLASS;
  static PITCH_CLASS_TO_NOTE = PITCH_CLASS_TO_NOTE;

  constructor({
    rootSelector = "#keyboard",
    namespace = "pianoKeyboard",
    onKeyClick = null,
    canPlayNote = null,
    visibleWhiteKeys = 7,
    initialStartNote = "C4",
  } = {}) {
    this.rootSelector = rootSelector;
    this.ns = namespace;
    this.onKeyClick = typeof onKeyClick === "function" ? onKeyClick : null;
    this.canPlayNote = typeof canPlayNote === "function" ? canPlayNote : null;
    this.visibleWhiteKeys = Math.max(1, Number(visibleWhiteKeys) || 7);
    this._startWhiteMidi = this._naturalMidiFromNoteName(initialStartNote) ?? 60;
    this._activeMarkers = new Map();
    this._renderTimeoutId = null;
    this._audioReady = false;
    this._synth = null;
    this._drag = {
      active: false,
      pointerId: null,
      startPageX: 0,
      originWhiteMidi: this._startWhiteMidi,
      lastStepOffset: 0,
      didMove: false,
      suppressClickUntil: 0,
    };
  }

  setStartNote(noteName) {
    const midi = this._naturalMidiFromNoteName(noteName);
    if (!Number.isFinite(midi)) return this;
    this._startWhiteMidi = midi;
    this.clearActive();
    this.render();
    return this;
  }

  bind() {
    this.render();

    $(document)
      .off(`click.${this.ns}`, this.rootSelector)
      .on(`click.${this.ns}`, this.rootSelector, (e) => {
        if (Date.now() < this._drag.suppressClickUntil) return;
        e.preventDefault();

        this._activateKeyFromTarget(e.target);
      });

    $(document)
      .off(`pointerdown.${this.ns}Drag`, this.rootSelector)
      .on(`pointerdown.${this.ns}Drag`, this.rootSelector, (e) => {
        e.preventDefault();

        const pointerId = e.originalEvent?.pointerId;
        const $key = this._resolveKeyFromTarget(e.target);
        if ($key.length) {
          this._drag.suppressClickUntil = Date.now() + 250;
          this._activateKeyFromTarget($key[0]);
        }

        this._drag.active = true;
        this._drag.pointerId = pointerId != null ? pointerId : null;
        this._drag.startPageX = e.pageX;
        this._drag.originWhiteMidi = this._startWhiteMidi;
        this._drag.lastStepOffset = 0;
        this._drag.didMove = false;

        $(e.currentTarget).addClass("dragging");
        if (e.currentTarget.setPointerCapture && pointerId != null) {
          e.currentTarget.setPointerCapture(pointerId);
        }
      });

    $(document)
      .off(`pointermove.${this.ns}Drag`)
      .on(`pointermove.${this.ns}Drag`, (e) => {
        if (!this._drag.active) return;

        const pointerId = e.originalEvent?.pointerId;
        if (this._drag.pointerId != null && pointerId != null && pointerId !== this._drag.pointerId) return;

        const whiteKeyWidth = this._whiteKeyWidth();
        if (!Number.isFinite(whiteKeyWidth) || whiteKeyWidth <= 0) return;

        const deltaX = e.pageX - this._drag.startPageX;
        const nextOffset = this._stepOffsetFromDeltaX(deltaX, whiteKeyWidth);
        if (nextOffset === this._drag.lastStepOffset) return;

        this._drag.lastStepOffset = nextOffset;
        this._drag.didMove = true;
        this._startWhiteMidi = this._shiftNaturalMidi(this._drag.originWhiteMidi, -nextOffset);
        this.render();
      });

    $(document)
      .off(`pointerup.${this.ns}Drag pointercancel.${this.ns}Drag`)
      .on(`pointerup.${this.ns}Drag pointercancel.${this.ns}Drag`, (e) => {
        const pointerId = e.originalEvent?.pointerId;
        if (this._drag.pointerId != null && pointerId != null && pointerId !== this._drag.pointerId) return;
        this._finishDrag();
      });

    return this;
  }

  unbind() {
    $(document).off(`click.${this.ns}`, this.rootSelector);
    $(document).off(`pointerdown.${this.ns}Drag`, this.rootSelector);
    $(document).off(`pointermove.${this.ns}Drag`);
    $(document).off(`pointerup.${this.ns}Drag pointercancel.${this.ns}Drag`);
    return this;
  }

  render() {
    const $root = $(this.rootSelector).first();
    if (!$root.length) return this;

    const whiteNotes = this._visibleWhiteNotes();
    const nextIds = whiteNotes.map((white) => String(white.midi));
    const currentIds = $root.children(".key-wrapper").map((_, el) => String(el.getAttribute("data-white-midi") || "")).get();
    const changed = currentIds.join(",") !== nextIds.join(",");

    if (!changed) return this;

    const existing = new Map();
    $root.children(".key-wrapper").each((_, el) => {
      const $el = $(el);
      existing.set(String($el.attr("data-white-midi") || ""), $el);
    });

    $root.children(".key-wrapper").each((_, el) => {
      const id = String(el.getAttribute("data-white-midi") || "");
      if (!nextIds.includes(id)) $(el).remove();
    });

    const newWrappers = [];
    for (let i = 0; i < whiteNotes.length; i += 1) {
      const white = whiteNotes[i];
      const id = String(white.midi);
      let $wrapper = existing.get(id);
      if (!$wrapper || !$wrapper.length) {
        $wrapper = this._buildWrapper(white);
        newWrappers.push($wrapper);
      }
      $root.append($wrapper);
    }

    if (!currentIds.length) {
      $root.find(".white-key, .black-key").show();
      this._reapplyActiveMarkers();
      return this;
    }

    newWrappers.forEach(($wrapper) => {
      $wrapper.find(".white-key, .black-key").show();
    });

    this._reapplyActiveMarkers();

    return this;
  }

  noteNameForKey($key) {
    return $key?.length ? String($key.attr("data-note") || "") : "";
  }

  _activateKeyFromTarget(target) {
    const $key = this._resolveKeyFromTarget(target);
    if (!$key.length) return;
    const noteName = this.noteNameForKey($key);
    void this._playNoteName(noteName);
    if (this.onKeyClick) this.onKeyClick({ $key, noteName, manual: true });
  }

  _activateKeyFromPointerEvent(event) {
    const pointerTarget = this._pointerEventTarget(event);
    this._activateKeyFromTarget(pointerTarget || event?.target);
  }

  keyForNote(letter, accidentalClass, octave) {
    const targetMidi = this._midiForNoteSpec(letter, accidentalClass, octave);
    if (!Number.isFinite(targetMidi)) return $();

    this._ensureMidiVisible(targetMidi);
    const selector = `${this.rootSelector} [data-midi="${targetMidi}"]`;
    return $(selector).first();
  }

  keyForNoteIfVisible(letter, accidentalClass, octave) {
    const targetMidi = this._midiForNoteSpec(letter, accidentalClass, octave);
    if (!Number.isFinite(targetMidi) || !this._isMidiVisible(targetMidi)) return $();

    const selector = `${this.rootSelector} [data-midi="${targetMidi}"]`;
    return $(selector).first();
  }

  clickKey($key) {
    if ($key?.length) $key.trigger("click");
    return this;
  }

  clearActive() {
    this._hideAllMarkers();
    this._activeMarkers = new Map();
    return this;
  }

  syncActiveKey($nextKey) {
    return this.syncActiveKeys($nextKey?.length ? [$nextKey] : []);
  }

  syncActiveKeys(keys) {
    const nextKeys = Array.isArray(keys) ? keys.filter(($key) => $key?.length) : [];
    const entries = nextKeys
      .map(($key) => {
        const noteName = this.noteNameForKey($key);
        if (!noteName) return null;
        return { noteName, tone: "primary", $key };
      })
      .filter(Boolean);

    return this.syncActiveMarkers(entries);
  }

  syncActiveNoteNames(noteNames, visibleKeys = []) {
    const nextNoteNames = noteNames instanceof Set
      ? [...noteNames].filter(Boolean)
      : (Array.isArray(noteNames) ? noteNames : []).filter(Boolean);
    const nextKeys = Array.isArray(visibleKeys) ? visibleKeys.filter(($key) => $key?.length) : [];

    const entries = nextNoteNames.map((noteName) => {
      const $key = nextKeys.find(($candidate) => this.noteNameForKey($candidate) === noteName) || null;
      return { noteName, markerLabel: this._markerLabelFromNoteName(noteName), tone: "primary", $key };
    });

    return this.syncActiveMarkers(entries);
  }

  syncActiveMarkers(entries) {
    const list = Array.isArray(entries) ? entries.filter(Boolean) : [];
    const nextMarkers = new Map();

    list.forEach((entry) => {
      const noteName = String(entry.noteName || "").trim();
      if (!noteName) return;
      const hasMarkerLabel = Object.prototype.hasOwnProperty.call(entry, "markerLabel");
      nextMarkers.set(noteName, {
        tone: entry.tone === "secondary" ? "secondary" : "primary",
        label: hasMarkerLabel
          ? String(entry.markerLabel ?? "").trim()
          : this._markerLabelFromNoteName(noteName),
        color: String(entry.markerColor || "").trim(),
      });
    });

    if (this._markerMapsEqual(this._activeMarkers, nextMarkers)) {
      list.forEach((entry) => {
        if (!entry?.$key?.length) return;
        const marker = nextMarkers.get(String(entry.noteName || "").trim()) || {};
        const $marker = entry.$key.find(".key-marker").first();
        this._applyMarkerTone($marker, marker.tone, marker.color);
        this._showMarker($marker, marker.label);
      });
      return this;
    }

    this._hideAllMarkers();
    this._activeMarkers = new Map(nextMarkers);

    list.forEach((entry) => {
      if (!entry?.$key?.length) return;
      const noteName = String(entry.noteName || "").trim();
      const marker = this._activeMarkers.get(noteName);
      if (!noteName || !marker) return;
      const $marker = entry.$key.find(".key-marker").first();
      this._applyMarkerTone($marker, marker.tone, marker.color);
      this._showMarker($marker, marker.label);
    });

    return this;
  }

  toggleKey($key) {
    if (!$key?.length) return this;

    const noteName = this.noteNameForKey($key);
    // eslint-disable-next-line no-console
    console.log("[PianoKeyboardUi] Keyboard key clicked", { note: noteName });
    if (this.onKeyClick) this.onKeyClick({ $key, noteName, manual: true });
    return this;
  }

  _markerHtml() {
    return `
      <div class="key-marker">
        <div class="d-center w-100">
          <span class="bg-primary"></span>
        </div>
      </div>
    `;
  }

  _buildWrapper(white) {
    const black = this._blackNoteAfter(white.midi);
    const $wrapper = $('<div class="position-relative key-wrapper"></div>').attr("data-white-midi", String(white.midi));

    if (black) {
      const $black = $('<div class="black-key"></div>').attr({
        "data-note": black.note,
        "data-midi": String(black.midi),
      });
      $black.append('<button type="button" class="btn btn-dark"></button>');
      $black.append(this._markerHtml());
      $wrapper.append($black);
    }

    const $white = $('<div class="white-key"></div>').attr({
      "data-note": white.note,
      "data-midi": String(white.midi),
    });
    $white.append('<button type="button" class="btn btn-white"></button>');
    $white.append(this._markerHtml());
    $wrapper.append($white);

    return $wrapper;
  }

  _resolveKeyFromTarget(target) {
    let $key = $(target).closest(".white-key, .black-key");
    if ($key.length) return $key;

    const $wrapper = $(target).closest(`${this.rootSelector} .key-wrapper`);
    if ($wrapper.length) $key = $wrapper.find(".black-key, .white-key").first();
    return $key;
  }

  _pointerEventTarget(event) {
    const source = event?.originalEvent || event;
    const clientX = Number(source?.clientX);
    const clientY = Number(source?.clientY);
    if (!Number.isFinite(clientX) || !Number.isFinite(clientY) || typeof document?.elementFromPoint !== "function") {
      return null;
    }
    return document.elementFromPoint(clientX, clientY);
  }

  _visibleWhiteNotes() {
    const out = [];
    let midi = this._startWhiteMidi;
    for (let i = 0; i < this.visibleWhiteKeys; i += 1) {
      out.push({ midi, note: this._naturalNoteNameFromMidi(midi) });
      midi = this._nextNaturalMidi(midi);
    }
    return out;
  }

  _blackNoteAfter(whiteMidi) {
    const pitchClass = ((whiteMidi % 12) + 12) % 12;
    if (pitchClass === 4 || pitchClass === 11) return null; // E/B have no black key above

    const midi = whiteMidi + 1;
    return { midi, note: this._noteNameFromMidi(midi) };
  }

  _ensureMidiVisible(targetMidi) {
    if (!Number.isFinite(targetMidi)) return;

    while (targetMidi < this._startWhiteMidi) {
      this._startWhiteMidi = this._prevNaturalMidi(this._startWhiteMidi);
      this.render();
    }

    while (true) {
      const lastWhiteMidi = this._lastWhiteMidi();
      const lastBlack = this._blackNoteAfter(lastWhiteMidi);
      const maxVisibleMidi = lastBlack ? lastBlack.midi : lastWhiteMidi;
      if (targetMidi <= maxVisibleMidi) break;
      this._startWhiteMidi = this._nextNaturalMidi(this._startWhiteMidi);
      this.render();
    }
  }

  _isMidiVisible(targetMidi) {
    if (!Number.isFinite(targetMidi)) return false;
    const lastWhiteMidi = this._lastWhiteMidi();
    const lastBlack = this._blackNoteAfter(lastWhiteMidi);
    const maxVisibleMidi = lastBlack ? lastBlack.midi : lastWhiteMidi;
    return targetMidi >= this._startWhiteMidi && targetMidi <= maxVisibleMidi;
  }

  _lastWhiteMidi() {
    let midi = this._startWhiteMidi;
    for (let i = 1; i < this.visibleWhiteKeys; i += 1) {
      midi = this._nextNaturalMidi(midi);
    }
    return midi;
  }

  _nextNaturalMidi(midi) {
    const current = this._naturalNoteNameFromMidi(midi);
    const letter = current.replace(/\d+$/, "");
    const octave = Number(current.match(/-?\d+$/)?.[0] || 4);
    const idx = PianoKeyboardUi.NATURAL_ORDER.indexOf(letter);
    const nextIdx = (idx + 1) % PianoKeyboardUi.NATURAL_ORDER.length;
    const nextOctave = nextIdx === 0 ? octave + 1 : octave;
    return this._naturalMidiFromLetterOctave(PianoKeyboardUi.NATURAL_ORDER[nextIdx], nextOctave);
  }

  _prevNaturalMidi(midi) {
    const current = this._naturalNoteNameFromMidi(midi);
    const letter = current.replace(/\d+$/, "");
    const octave = Number(current.match(/-?\d+$/)?.[0] || 4);
    const idx = PianoKeyboardUi.NATURAL_ORDER.indexOf(letter);
    const prevIdx = (idx - 1 + PianoKeyboardUi.NATURAL_ORDER.length) % PianoKeyboardUi.NATURAL_ORDER.length;
    const prevOctave = idx === 0 ? octave - 1 : octave;
    return this._naturalMidiFromLetterOctave(PianoKeyboardUi.NATURAL_ORDER[prevIdx], prevOctave);
  }

  _midiForNoteSpec(letter, accidentalClass, octave) {
    const cleanLetter = String(letter || "").trim().toUpperCase();
    const numOctave = Number(octave);
    const basePc = PianoKeyboardUi.NATURAL_PITCH_CLASS[cleanLetter];
    if (!Number.isInteger(basePc) || !Number.isFinite(numOctave)) return null;
    const midi = ((numOctave + 1) * 12) + basePc + this._accidentalOffset(accidentalClass);
    return midi;
  }

  _accidentalOffset(accidentalClass) {
    if (!accidentalClass) return 0;
    if (accidentalClass.includes("music-font__doublesharp")) return 2;
    if (accidentalClass.includes("music-font__sharp")) return 1;
    if (accidentalClass.includes("music-font__doubleflat")) return -2;
    if (accidentalClass.includes("music-font__flat")) return -1;
    return 0;
  }

  _naturalMidiFromNoteName(noteName) {
    return naturalMidiFromNoteName(noteName);
  }

  _naturalMidiFromLetterOctave(letter, octave) {
    return naturalMidiFromLetterOctave(letter, octave);
  }

  _noteNameFromMidi(midi) {
    return noteNameFromMidi(midi);
  }

  _naturalNoteNameFromMidi(midi) {
    return naturalNoteNameFromMidi(midi);
  }

  _keyByNoteName(noteName) {
    if (!noteName) return $();
    return $(`${this.rootSelector} [data-note="${noteName}"]`).first();
  }

  _hideAllMarkers() {
    $(`${this.rootSelector} .key-marker`)
      .hide()
      .find("span")
      .text("")
      .css({ backgroundColor: "", color: "" })
      .removeClass("bg-primary bg-secondary");
  }

  _reapplyActiveMarkers() {
    this._hideAllMarkers();
    if (!(this._activeMarkers instanceof Map) || !this._activeMarkers.size) return;

    this._activeMarkers.forEach((marker, noteName) => {
      const $key = this._keyByNoteName(noteName);
      if (!$key.length) return;
      const $marker = $key.find(".key-marker").first();
      this._applyMarkerTone($marker, marker.tone, marker.color);
      this._showMarker($marker, marker.label);
    });
  }

  _whiteKeyWidth() {
    const $whiteKey = $(`${this.rootSelector} .white-key`).first();
    return $whiteKey.length ? ($whiteKey.outerWidth() || 0) : 0;
  }

  _stepOffsetFromDeltaX(deltaX, whiteKeyWidth) {
    if (!Number.isFinite(deltaX) || !Number.isFinite(whiteKeyWidth) || whiteKeyWidth <= 0) return 0;
    if (deltaX > 0) return Math.floor(deltaX / whiteKeyWidth);
    if (deltaX < 0) return -Math.floor(Math.abs(deltaX) / whiteKeyWidth);
    return 0;
  }

  _shiftNaturalMidi(midi, whiteSteps) {
    if (!Number.isFinite(midi) || !Number.isFinite(whiteSteps) || whiteSteps === 0) return midi;

    let nextMidi = midi;
    const stepCount = Math.abs(Math.trunc(whiteSteps));

    for (let i = 0; i < stepCount; i += 1) {
      nextMidi = whiteSteps > 0
        ? this._nextNaturalMidi(nextMidi)
        : this._prevNaturalMidi(nextMidi);
    }

    return nextMidi;
  }

  _finishDrag() {
    if (!this._drag.active) return;

    const $root = $(this.rootSelector).first();
    if ($root.length) {
      $root.removeClass("dragging");
      if ($root[0]?.releasePointerCapture && this._drag.pointerId != null) {
        try {
          $root[0].releasePointerCapture(this._drag.pointerId);
        } catch (_) {
          // Ignore capture release errors when the pointer is already gone.
        }
      }
    }

    this._drag.active = false;
    this._drag.pointerId = null;
    this._drag.startPageX = 0;
    this._drag.originWhiteMidi = this._startWhiteMidi;
    this._drag.suppressClickUntil = this._drag.didMove
      ? Date.now() + 250
      : Math.max(this._drag.suppressClickUntil || 0, Date.now() + 250);
    this._drag.lastStepOffset = 0;
    this._drag.didMove = false;
  }

  _markerMapsEqual(a, b) {
    if (!(a instanceof Map) || !(b instanceof Map)) return false;
    if (a.size !== b.size) return false;
    for (const [key, value] of a) {
      const next = b.get(key);
      if (!next || next.tone !== value.tone || next.label !== value.label || next.color !== value.color) return false;
    }
    return true;
  }

  _markerLabelFromNoteName(noteName) {
    const match = String(noteName || "").trim().match(/^([A-G][#b]?)-?\d+$/);
    return displayNoteName(match ? match[1] : String(noteName || "").trim());
  }

  _applyMarkerTone($marker, tone, color = "") {
    if (!$marker?.length) return;
    const $swatch = $marker.find("span").first();
    if (!$swatch.length) return;
    $swatch.removeClass("bg-primary bg-secondary");
    $swatch.css({ backgroundColor: "", color: "" });
    if (color) {
      $swatch.css({ backgroundColor: color, color: "#111" });
      return;
    }
    $swatch.addClass(tone === "secondary" ? "bg-secondary" : "bg-primary");
  }

  _showMarker($marker, noteName = "") {
    if (!$marker?.length) return;
    $marker.find("span").first().text(noteName || "");
    $marker.show();
  }

  async _ensureAudio() {
    if (this._audioReady && this._synth) return;
    if (!window.Tone) return;

    await Tone.start();
    this._synth = this._synth || GameAudio.createStaffNoteSynth();
    this._audioReady = true;
  }

  _canPlayNote() {
    if (this.canPlayNote) return !!this.canPlayNote();
    return true;
  }

  async _playNoteName(noteName) {
    if (!this._canPlayNote()) return;
    if (!String(noteName || "").trim()) return;

    await this._ensureAudio();
    if (!this._synth) return;

    this._synth.triggerAttackRelease(
      String(noteName).trim(),
      0.45,
      undefined,
      GameAudio.scale("staffNote", 1),
    );
  }
}
