import { BaseStaffGame } from "../base/BaseStaffGame.js";
import {
  renderFinalResultsOverlay,
  queueFinalResultsReveal,
} from "../shared/finalResults.js";
import { playSnakeCellBreakBurstAtElement } from "../shared/mojsEffects.js";
import { playSmokePuffAtElement } from "../shared/mojsEffects.js";
import { PromptUi } from "../shared/PromptUi.js";
import { GameAudio } from "../shared/GameAudio.js";
import { GameCountdown } from "../shared/GameCountdown.js";
import { InstructionsUi } from "../shared/InstructionsUi.js";
import { NotePythonMusic } from "./NotePythonMusic.js";
import { displayAccidental } from "../shared/noteNames.js";
import {
  beatMsForBpm,
  eighthNoteMsForBpm,
  normalizeMetronomeBpm,
} from "../shared/tempo.js";

export class NotePython {
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
      boardEl: "#board",
      rows: 9,
      cols: 9,
      bpm: 80,
      sound: true,
      basePoints: 1,
      firstTryBonus: 2,
      numOfChallenges: 4,
      practiceMode: false,
      solfege: false,
      strictDirection: false,
      showBombs: false,
      realWalls: false,
      speedUpEachRound: false,
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
        "That’s it",
      ],
      intervals: Object.keys(NotePython.INTERVAL_FULL_NAME_MAP),
      namespace: "notePython",
    };

    this.opts = { ...defaults, ...(options || {}) };
    this.opts.bpm = normalizeMetronomeBpm(this.opts.bpm);
    this._currentBpm = this.opts.bpm;
    this._pendingBpm = null;
    this.ns = this.opts.namespace || "notePython";
    this.$board = $(this.opts.boardEl).first();
    this.$playWrap = $("#play");
    this.$playBtn = this.$playWrap.find('button[action="play"]');
    this.$stopBtn = this.$playWrap.find('button[action="stop"]');
    this._countdown = new GameCountdown({
      valueElement: this.$stopBtn.get(0),
    });
    this.prompt = new PromptUi("#prompt");
    this.instructionsUi = new InstructionsUi("#instructions");
    this.$points = $("#points");
    this.$increment = $("#increment");
    this.$feedback = $("#feedback-success");
    this.$helpBtn = $("#help");
    this.$accidentals = $("#accidentals");
    this.$progressBar = $("#progress-bar");
    this.$progressCounter = $("#progress-counter");
    this.$finalOverlay = $("#final-overlay");
    this.$doublePoints = $("#double-points");
    this.$controls = $("#controls");
    this.$restart = $("#restart");
    this._rows = 9;
    this._cols = 9;
    this._snake = [];
    this._foods = [];
    this._bombs = [];
    this._foodIdCounter = 1;
    this._animatedFoodIds = new Set();
    this._foodAnimTimeouts = [];
    this._direction = { dr: 1, dc: 0 }; // starts moving downward
    this._directionQueue = [];
    this._tickTimer = null;
    this._music = new NotePythonMusic();
    this._countdownTimeouts = [];
    this._countdownRun = 0;
    this._isGameOver = false;
    this._currentIntervalAbbr = null;
    this._currentIntervalDirection = 1; // 1=up, -1=down
    this._headNote = null;
    this._targetNotes = [];
    this._uiSfxReady = false;
    this._uiSfxSynth = null;
    this._uiSfxNoise = null;
    this._uiTimerSfxSynth = null;
    this._audioUnlockArmed = false;
    this._finalMetricsSfxTimeouts = [];
    this._finalCountupTimeouts = [];
    this._finalStartMs = Date.now();
    this._finalResultsTimeoutId = null;
    this._pausedByModal = false;
    this._pointsValue = 0;
    this._roundsCompleted = 0;
    this._madeMistakeThisRound = false;
    this._madeAnyMistake = false;
    this._correctStreak = 0;
    this._stats = {
      checksTotal: 0,
      checksCorrect: 0,
      finishedAtMs: null,
    };
    this.successPhrases = Array.isArray(this.opts.successPhrases) && this.opts.successPhrases.length
      ? this.opts.successPhrases.slice()
      : ["Great job"];
  }

  _gridRows() {
    const n = Number(this.opts.rows);
    return Math.max(2, Math.floor(Number.isFinite(n) ? n : 9));
  }

  _gridCols() {
    const n = Number(this.opts.cols);
    return Math.max(2, Math.floor(Number.isFinite(n) ? n : 9));
  }

  _renderBoard() {
    if (!this.$board.length) return;

    const rows = this._gridRows();
    const cols = this._gridCols();
    this._rows = rows;
    this._cols = cols;
    const total = rows * cols;

    this.$board.empty();
    this.$board
      .attr("data-rows", String(rows))
      .attr("data-cols", String(cols))
      .css({
        display: "grid",
        gridTemplateColumns: `repeat(${cols}, 1fr)`,
      });

    for (let i = 0; i < total; i += 1) {
      const r = Math.floor(i / cols);
      const c = i % cols;
      $("<div></div>")
        .addClass("board-cell")
        .attr("data-row", String(r))
        .attr("data-col", String(c))
        .attr("data-index", String(i))
        .appendTo(this.$board);
    }
  }

  _snakeSpeedMs() {
    return eighthNoteMsForBpm(this._currentBpm);
  }

  _beatMs() {
    return beatMsForBpm(this._currentBpm);
  }

  _advanceRoundTempo() {
    if (!this._normalizeOnOff(this.opts.speedUpEachRound)) return;
    const nextBpm = normalizeMetronomeBpm((this._pendingBpm ?? this._currentBpm) + 10);
    if (nextBpm === this._currentBpm) return;
    // Finish the current eighth note before changing both clocks together.
    if (this._tickTimer != null) this._pendingBpm = nextBpm;
    else this._currentBpm = nextBpm;
  }

  _wrapCell({ r, c }) {
    const rr = ((r % this._rows) + this._rows) % this._rows;
    const cc = ((c % this._cols) + this._cols) % this._cols;
    return { r: rr, c: cc };
  }

  _sameCell(a, b) {
    return !!a && !!b && a.r === b.r && a.c === b.c;
  }

  _torusAxisDistance(a, b, size) {
    const d = Math.abs(a - b);
    return Math.min(d, Math.max(0, size) - d);
  }

  _isTooCloseToHead(cell, minDistance = 2) {
    const head = this._snake?.[0];
    if (!head || !cell) return false;
    const dr = this._torusAxisDistance(cell.r, head.r, this._rows);
    const dc = this._torusAxisDistance(cell.c, head.c, this._cols);
    return dr <= minDistance && dc <= minDistance;
  }

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
    return NotePython.INTERVAL_FULL_NAME_MAP[key] || this._deriveFullNameFromAbbr(key);
  }

  _intervalPool() {
    const pool = Array.isArray(this.opts.intervals) ? this.opts.intervals : null;
    if (pool && pool.length) return pool.slice();
    return Object.keys(NotePython.INTERVAL_FULL_NAME_MAP);
  }

  _pickInterval() {
    const pool = this._intervalPool();
    return pool[Math.floor((this._duelChallengeRandom?.() ?? Math.random()) * pool.length)] || "M3";
  }

  _setIntervalUI(intervalAbbr) {
    this._setIntervalUIWithDirection(intervalAbbr, this._currentIntervalDirection);
  }

  _setIntervalUIWithDirection(intervalAbbr, direction = 1) {
    const abbr = String(intervalAbbr || "").trim();
    this._currentIntervalAbbr = abbr;
    this._currentIntervalDirection = Number(direction) === -1 ? -1 : 1;
    this.prompt.setShort(abbr);
    if (this._isStrictDirection()) this.prompt.showDirection(this._currentIntervalDirection);
    else this.prompt.hideDirection();
    this.prompt.setLong(this._fullNameForInterval(abbr));
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

  _useRealWalls() {
    return this._normalizeOnOff(this.opts.realWalls);
  }

  _isSolfege() {
    return this._normalizeOnOff(this.opts.solfege);
  }

  _pickIntervalDirection() {
    if (!this._isStrictDirection()) return 1;
    return (this._duelChallengeRandom?.() ?? Math.random()) < 0.5 ? -1 : 1;
  }

  _noteDisplay({ letter, accOffset = 0 } = {}) {
    const L = String(letter || "").toUpperCase();
    const base = this._isSolfege()
      ? (NotePython.LETTER_TO_SOLFEGE[L] || L)
      : L;
    const off = Number(accOffset) || 0;
    return `${base}${displayAccidental(off)}`;
  }

  _noteObj(letter, accOffset = 0) {
    const off = Number(accOffset) || 0;
    const accText =
      off === 2 ? "##" :
      off === 1 ? "#" :
      off === -1 ? "b" :
      off === -2 ? "bb" :
      "";
    const naturalPc = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 }[letter];
    const pitchClass = ((naturalPc + off) % 12 + 12) % 12;
    return {
      letter,
      accOffset: off,
      pitchClass,
      canonical: `${letter}${accText}`,
      display: this._noteDisplay({ letter, accOffset: off }),
    };
  }

  _cloneNote(note) {
    if (!note) return null;
    return this._noteObj(String(note.letter || "C").toUpperCase(), Number(note.accOffset) || 0);
  }

  _parseIntervalAbbr(abbr) {
    const m = String(abbr || "").trim().match(/^([PMAmd])(\d+)$/);
    if (!m) return null;
    return { quality: m[1], number: Number(m[2]) };
  }

  _intervalToSemitones(intervalAbbr) {
    const map = {
      P1: 0, m2: 1, M2: 2, m3: 3, M3: 4, P4: 5, A4: 6, d5: 6,
      P5: 7, m6: 8, M6: 9, m7: 10, M7: 11, P8: 12,
    };
    return map[String(intervalAbbr || "").trim()];
  }

  _spelledIntervalTarget(prevNote, intervalAbbr, dir = 1) {
    if (!prevNote) return null;
    const semis = this._intervalToSemitones(intervalAbbr);
    const parsed = this._parseIntervalAbbr(intervalAbbr);
    if (!Number.isFinite(semis) || !parsed || !Number.isFinite(parsed.number)) return null;

    const letters = ["C", "D", "E", "F", "G", "A", "B"];
    const fromIdx = letters.indexOf(prevNote.letter);
    if (fromIdx < 0) return null;

    const diatonicSteps = Math.max(0, parsed.number - 1);
    const toIdxRaw = fromIdx + (dir * diatonicSteps);
    const toIdx = ((toIdxRaw % 7) + 7) % 7;
    const targetLetter = letters[toIdx];

    const targetPc = ((prevNote.pitchClass + (dir * semis)) % 12 + 12) % 12;
    const naturalPc = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 }[targetLetter];

    let off = targetPc - naturalPc;
    while (off > 6) off -= 12;
    while (off < -6) off += 12;
    if (off > 2) off -= 12;
    if (off < -2) off += 12;
    if (off < -2 || off > 2) return null;

    return this._noteObj(targetLetter, off);
  }

  _randomNoteObj() {
    const letters = ["C", "D", "E", "F", "G", "A", "B"];
    const letter = letters[Math.floor((this._duelChallengeRandom?.() ?? Math.random()) * letters.length)];
    const accPool = [0, 0, 0, 0, 1, -1];
    const off = accPool[Math.floor((this._duelChallengeRandom?.() ?? Math.random()) * accPool.length)];
    return this._noteObj(letter, off);
  }

  _computeTargetNotes() {
    const interval = this._currentIntervalAbbr || "M3";
    const list = this._isStrictDirection()
      ? [this._spelledIntervalTarget(
        this._headNote,
        interval,
        this._currentIntervalDirection === -1 ? -1 : 1,
      )].filter(Boolean)
      : [
        this._spelledIntervalTarget(this._headNote, interval, 1),
        this._spelledIntervalTarget(this._headNote, interval, -1),
      ].filter(Boolean);
    const seen = new Set();
    return list.filter((n) => {
      const key = String(n.canonical || "");
      if (!key || seen.has(key)) return false;
      seen.add(key);
      return true;
    });
  }

  _isPracticeMode() {
    const v = this.opts.practiceMode;
    if (v === true) return true;
    const s = String(v ?? "").trim().toLowerCase();
    return s === "on" || s === "true" || s === "1";
  }

  _showBombs() {
    return this._normalizeOnOff(this.opts.showBombs);
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

  _placeInitialSnake() {
    this._snakeHop = null;
    const centerCol = Math.floor(this._cols / 2);
    const startRow = Math.min(1, this._rows - 1); // second row from top
    const head = { r: startRow, c: centerCol };
    this._direction = { dr: 1, dc: 0 };
    this._snake = [
      head,
      { r: Math.max(0, startRow - 1), c: centerCol },
    ];
    this._headNote = this._randomNoteObj();
  }

  _enqueueDirection(next) {
    if (this._isGameOver) return;
    if (!next || !Number.isFinite(next.dr) || !Number.isFinite(next.dc)) return;

    const lastQueued = this._directionQueue.length
      ? this._directionQueue[this._directionQueue.length - 1]
      : this._direction;

    if (next.dr === lastQueued.dr && next.dc === lastQueued.dc) return;
    // Prevent immediate 180 turn against effective direction (current or last queued).
    if (next.dr === -lastQueued.dr && next.dc === -lastQueued.dc) return;

    // Keep a short buffer so rapid inputs can chain (e.g. right then down).
    if (this._directionQueue.length >= 2) return;
    this._directionQueue.push({ dr: next.dr, dc: next.dc });
  }

  _wireKeyboardControls() {
    const ns = `.toneTrailKeys.${this.ns}`;
    $(document)
      .off(`keydown${ns}`)
      .on(`keydown${ns}`, (e) => {
        const key = String(e.key || "").toLowerCase();
        if (key === "arrowup") {
          e.preventDefault();
          this._enqueueDirection({ dr: -1, dc: 0 });
        } else if (key === "arrowdown") {
          e.preventDefault();
          this._enqueueDirection({ dr: 1, dc: 0 });
        } else if (key === "arrowleft") {
          e.preventDefault();
          this._enqueueDirection({ dr: 0, dc: -1 });
        } else if (key === "arrowright") {
          e.preventDefault();
          this._enqueueDirection({ dr: 0, dc: 1 });
        }
      });
  }

  _wireSwipeControls() {
    if (!this.$board?.length) return;

    const ns = `.toneTrailSwipe.${this.ns}`;
    let startX = 0;
    let startY = 0;
    let tracking = false;
    const minSwipe = 18;

    const queueFromDelta = (dx, dy) => {
      if (Math.max(Math.abs(dx), Math.abs(dy)) < minSwipe) return;
      if (Math.abs(dx) > Math.abs(dy)) {
        this._enqueueDirection(dx > 0 ? { dr: 0, dc: 1 } : { dr: 0, dc: -1 });
      } else {
        this._enqueueDirection(dy > 0 ? { dr: 1, dc: 0 } : { dr: -1, dc: 0 });
      }
    };

    this.$board
      .off(`touchstart${ns} touchmove${ns} touchend${ns} touchcancel${ns}`)
      .on(`touchstart${ns}`, (e) => {
        const t = e.originalEvent?.touches?.[0];
        if (!t) return;
        startX = t.clientX;
        startY = t.clientY;
        tracking = true;
      })
      .on(`touchmove${ns}`, (e) => {
        if (!tracking) return;
        const t = e.originalEvent?.touches?.[0];
        if (!t) return;
        const dx = t.clientX - startX;
        const dy = t.clientY - startY;
        if (Math.max(Math.abs(dx), Math.abs(dy)) >= 10) e.preventDefault();
      })
      .on(`touchend${ns}`, (e) => {
        if (!tracking) return;
        const t = e.originalEvent?.changedTouches?.[0];
        tracking = false;
        if (!t) return;
        queueFromDelta(t.clientX - startX, t.clientY - startY);
      })
      .on(`touchcancel${ns}`, () => {
        tracking = false;
      });
  }

  _wireModalPause() {
    const ns = `.toneTrailModalPause.${this.ns}`;
    $(document)
      .off(`shown.bs.modal${ns}`)
      .off(`hidden.bs.modal${ns}`)
      .on(`shown.bs.modal${ns}`, () => {
        this._pausedByModal = true;
        this._stopLoop();
      })
      .on(`hidden.bs.modal${ns}`, () => {
        // If another modal is still open, stay paused.
        if ($(".modal.show").length) return;
        if (!this._pausedByModal) return;
        this._pausedByModal = false;
        if (this._isGameOver) return;
        if (!this._snake.length) return; // game not started yet
        if (this._tickTimer != null) return;
        this._startLoop();
      });
  }

  _spawnFoods(count = 1, { preferredRow = null } = {}) {
    const n = Math.max(0, Math.floor(Number(count) || 0));
    if (!n) return;

    const hasPreferredRow = Number.isFinite(preferredRow);
    const targetRow = hasPreferredRow
      ? Math.max(0, Math.min(this._rows - 1, Math.floor(preferredRow)))
      : null;

    for (let k = 0; k < n; k += 1) {
      const free = [];
      const rowStart = hasPreferredRow ? targetRow : 0;
      const rowEnd = hasPreferredRow ? (targetRow + 1) : this._rows;
      for (let r = rowStart; r < rowEnd; r += 1) {
        for (let c = 0; c < this._cols; c += 1) {
          const cell = { r, c };
          const occupiedBySnake = this._snake.some((s) => this._sameCell(s, cell));
          const occupiedByFood = this._foods.some((f) => this._sameCell(f, cell));
          const occupiedByBomb = this._bombs.some((b) => this._sameCell(b, cell));
          const tooCloseToHead = this._isTooCloseToHead(cell, 2);
          if (!occupiedBySnake && !occupiedByFood && !occupiedByBomb && !tooCloseToHead) free.push(cell);
        }
      }

      if (!free.length) {
        if (hasPreferredRow) {
          this._spawnFoods(1);
          continue;
        }
        return;
      }

      const pick = free[Math.floor((this._duelRandom?.() ?? Math.random()) * free.length)];
      this._foods.push({
        id: this._foodIdCounter++,
        r: pick.r,
        c: pick.c,
        note: this._randomNoteObj(),
      });
    }
  }

  _spawnBombs(count = 1, { preferredRow = null } = {}) {
    if (!this._showBombs()) return;
    const n = Math.max(0, Math.floor(Number(count) || 0));
    if (!n) return;

    const hasPreferredRow = Number.isFinite(preferredRow);
    const targetRow = hasPreferredRow
      ? Math.max(0, Math.min(this._rows - 1, Math.floor(preferredRow)))
      : null;

    for (let k = 0; k < n; k += 1) {
      const free = [];
      const rowStart = hasPreferredRow ? targetRow : 0;
      const rowEnd = hasPreferredRow ? (targetRow + 1) : this._rows;

      for (let r = rowStart; r < rowEnd; r += 1) {
        for (let c = 0; c < this._cols; c += 1) {
          const cell = { r, c };
          const occupiedBySnake = this._snake.some((s) => this._sameCell(s, cell));
          const occupiedByFood = this._foods.some((f) => this._sameCell(f, cell));
          const occupiedByBomb = this._bombs.some((b) => this._sameCell(b, cell));
          const tooCloseToHead = this._isTooCloseToHead(cell, 2);
          if (!occupiedBySnake && !occupiedByFood && !occupiedByBomb && !tooCloseToHead) free.push(cell);
        }
      }

      if (!free.length) {
        if (hasPreferredRow) {
          this._spawnBombs(1);
          continue;
        }
        return;
      }

      const pick = free[Math.floor((this._duelRandom?.() ?? Math.random()) * free.length)];
      this._bombs.push({ r: pick.r, c: pick.c });
    }
  }

  _ensureTargetFoodPresent() {
    if (!this._foods.length) return;
    this._targetNotes = this._computeTargetNotes();

    if (this._isStrictDirection() && !this._targetNotes.length) {
      // Re-pick prompt until the current head note can produce a valid strict target.
      for (let i = 0; i < 120 && !this._targetNotes.length; i += 1) {
        this._setIntervalUIWithDirection(this._pickInterval(), this._pickIntervalDirection());
        this._targetNotes = this._computeTargetNotes();
      }
    }

    if (!this._targetNotes.length) return;

    const valid = new Set(
      this._targetNotes.map((n) => String(n.canonical || "").trim()).filter(Boolean),
    );

    // Hard guarantee: force at least one valid food note to exist.
    for (let attempt = 0; attempt < 3; attempt += 1) {
      const has = this._foods.some((f) => valid.has(String(f.note?.canonical || "").trim()));
      if (has) return;
      const idx = Math.floor((this._duelRandom?.() ?? Math.random()) * this._foods.length);
      const pick = this._targetNotes[Math.floor((this._duelRandom?.() ?? Math.random()) * this._targetNotes.length)];
      this._foods[idx].note = this._cloneNote(pick);
    }
  }

  _snakeHopStyle(cell, index, now) {
    const hop = this._snakeHop;
    if (!hop) return "";
    const step = hop.stepMs;
    const elapsed = now - hop.startedAt;
    const duration = step * 0.68;
    const delay = Math.min(index * step * 0.055, step * 0.24);
    if (elapsed >= duration + delay) return "";
    const from = hop.from[Math.min(index, hop.from.length - 1)];
    if (!from) return "";
    const dc = from.c - cell.c;
    const dr = from.r - cell.r;
    // Wrapping across an edge should bounce into place, never fly across the board.
    const wrapped = Math.abs(dc) > 1 || Math.abs(dr) > 1;
    return `--hop-x: ${wrapped ? 0 : dc}; --hop-y: ${wrapped ? 0 : dr}; --hop-duration: ${duration}ms; --hop-delay: ${delay - elapsed}ms;`;
  }

  _renderEntities() {
    if (!this.$board.length) return;
    const now = performance.now();
    const $cells = this.$board.find(".board-cell");
    $cells.removeClass("snake snake-head food bomb animate__animated animate__rubberBand").html("");

    this._snake.forEach((cell, i) => {
      const selector = `.board-cell[data-row="${cell.r}"][data-col="${cell.c}"]`;
      const $cell = this.$board.find(selector);
      if (!$cell.length) return;
      $cell.addClass("snake");
      const hopStyle = this._snakeHopStyle(cell, i, now);
      const $hop = $(`<span class="snake-hop${hopStyle ? " snake-hopping" : ""}" style="${hopStyle}"></span>`);
      $hop.append('<span class="snake-shadow" aria-hidden="true"></span>');
      const $segment = $('<span class="snake-segment"></span>');
      $hop.append($segment);
      $cell.append($hop);
      if (i === 0) {
        $cell.addClass("snake-head");
        if (this._headNote?.display) $segment.html(`<span class="food-note">${this._headNote.display}</span>`);
        const facing = this._direction.dc === 1 ? 90
          : this._direction.dr === 1 ? 180
            : this._direction.dc === -1 ? 270 : 0;
        // Carry the animation phase across cell redraws, even at the fastest tempo.
        const motionTime = -now;
        $segment.append(`
          <span class="snake-face" aria-hidden="true" style="--snake-facing: ${facing}deg; --snake-motion-time: ${motionTime}ms">
            <span class="snake-face-bob">
              <span class="snake-eye snake-eye-left"><span class="snake-pupil"></span></span>
              <span class="snake-eye snake-eye-right"><span class="snake-pupil"></span></span>
            </span>
          </span>
        `);
      }
    });

    this._foods.forEach((food, i) => {
      const $food = this.$board.find(
        `.board-cell[data-row="${food.r}"][data-col="${food.c}"]`,
      );
      if (!$food.length) return;
      $food
        .addClass("food")
        .html(`<span class="food-note">${String(food.note?.display || "")}</span>`);
      if (food?.id == null || this._animatedFoodIds.has(food.id)) return;
      this._animatedFoodIds.add(food.id);

      const tid = setTimeout(() => {
        const $target = this.$board.find(
          `.board-cell[data-row="${food.r}"][data-col="${food.c}"]`,
        ).first();
        if (!$target.length) return;
        $target.removeClass("animate__animated animate__rubberBand");
        // eslint-disable-next-line no-unused-expressions
        $target[0] && $target[0].offsetWidth;
        $target.addClass("animate__animated animate__rubberBand");
        $target
          .off(`animationend.${this.ns}FoodHB webkitAnimationEnd.${this.ns}FoodHB`)
          .one(`animationend.${this.ns}FoodHB webkitAnimationEnd.${this.ns}FoodHB`, () => {
            $target.removeClass("animate__animated animate__rubberBand");
          });
      }, 0);
      this._foodAnimTimeouts.push(tid);
    });

    this._bombs.forEach((bomb) => {
      if (!this._showBombs()) return;
      const $bomb = this.$board.find(
        `.board-cell[data-row="${bomb.r}"][data-col="${bomb.c}"]`,
      );
      if (!$bomb.length) return;
      $bomb
        .addClass("bomb")
        .html('<div class="bomb"><i class="fa-solid fa-bomb"></i></div>');
    });
  }

  _explodeSnakeAndEndGame() {
    if (this._isGameOver) return;
    this._isGameOver = true;
    this._stopLoop();
    this._clearCorrectStreak();
    this.$board?.addClass?.("failed");
    this._playWallCrashSfx();
    this._playBombHitFailSfx();
    this._hingeClearSnake();
  }

  _hingeClearSnake() {
    if (!this.$board?.length) return;

    const $targets = this.$board.find(".board-cell.snake, .board-cell.snake-head");
    if (!$targets.length) {
      this.$restart?.show?.();
      return;
    }

    const lastIndex = $targets.length - 1;
    $targets.each((i, el) => {
      const delay = i * 85;
      const tid = setTimeout(() => {
        const $el = $(el);
        $el
          .removeClass("animate__animated animate__hinge animate__rubberBand")
          .addClass("animate__animated animate__hinge")
          .off(`animationend.${this.ns}SnakeHinge webkitAnimationEnd.${this.ns}SnakeHinge`)
          .one(`animationend.${this.ns}SnakeHinge webkitAnimationEnd.${this.ns}SnakeHinge`, () => {
            $el
              .removeClass("snake snake-head animate__animated animate__hinge animate__rubberBand")
              .html("")
              .css("animation-delay", "");
            if (i === lastIndex) this.$restart?.show?.();
          });
      }, delay);
      this._countdownTimeouts.push(tid);
    });
  }

  _explodeBombCollision(hitCell) {
    if (!this.$board?.length) return;

    const parentEl = this.$board[0] || document.body;
    const explodedSelectors = [];
    this._snake.forEach((cell, i) => {
      const $cell = this.$board.find(
        `.board-cell[data-row="${cell.r}"][data-col="${cell.c}"]`,
      ).first();
      if (!$cell.length) return;
      playSnakeCellBreakBurstAtElement($cell[0], { parentEl, index: i });
      explodedSelectors.push(`.board-cell[data-row="${cell.r}"][data-col="${cell.c}"]`);
    });

    if (hitCell) {
      const $bombCell = this.$board.find(
        `.board-cell[data-row="${hitCell.r}"][data-col="${hitCell.c}"]`,
      ).first();
      if ($bombCell.length) {
        playSnakeCellBreakBurstAtElement($bombCell[0], {
          parentEl,
          index: this._snake.length + 1,
        });
        explodedSelectors.push(`.board-cell[data-row="${hitCell.r}"][data-col="${hitCell.c}"]`);
      }
    }

    const deduped = Array.from(new Set(explodedSelectors));
    const tid = setTimeout(() => {
      deduped.forEach((selector) => {
        this.$board.find(selector)
          .removeClass("snake snake-head bomb food animate__animated animate__rubberBand animate__hinge")
          .html("");
      });
    }, 110);
    this._countdownTimeouts.push(tid);
  }

  _playBombHitFailSfx() {
    if (!this._isSoundEnabled() || !window.Tone) return Promise.resolve(0);

    return this._ensureUiSfxAudio().then(() => {
      const synth = this._uiSfxSynth;
      const noiseSynth = this._uiSfxNoise;
      if (!synth) return 0;

      const now = Tone.now();
      const oldEnv = { ...synth.get().envelope };
      const oldOsc = synth.get().oscillator?.type;

      try {
        synth.set({
          oscillator: { type: "triangle" },
          envelope: { attack: 0.004, decay: 0.16, sustain: 0.08, release: 0.38 },
        });
      } catch (_) {}

      ["E5", "D5", "C5", "A4", "G4", "E4", "D4", "B3", "A3", "F3", "E3"].forEach((n, i) => {
        const when = now + (i * 0.17);
        synth.triggerAttackRelease(n, 0.15, when, GameAudio.scale("bombFail", 0.42));
        if (noiseSynth && i < 8) {
          noiseSynth.triggerAttackRelease(0.05, when + 0.015, GameAudio.scale("bombFail", 0.14));
        }
      });

      const restoreDelay = 2200;
      const tid = setTimeout(() => {
        try {
          synth.set({
            oscillator: { type: oldOsc || "triangle" },
            envelope: oldEnv,
          });
        } catch (_) {}
      }, restoreDelay);
      this._countdownTimeouts.push(tid);

      return restoreDelay;
    });
  }

  _playWallCrashSfx() {
    if (!this._isSoundEnabled() || !window.Tone) return;

    this._ensureUiSfxAudio().then(() => {
      const synth = this._uiTimerSfxSynth || this._uiSfxSynth;
      const noiseSynth = this._uiSfxNoise;
      if (!synth) return;

      const now = Tone.now();
      if (noiseSynth) {
        noiseSynth.triggerAttackRelease(0.12, now, GameAudio.scale("wallCrash", 0.32));
        noiseSynth.triggerAttackRelease(0.09, now + 0.045, GameAudio.scale("wallCrash", 0.22));
      }
      synth.triggerAttackRelease("G3", 0.08, now, GameAudio.scale("wallCrash", 0.85));
      synth.triggerAttackRelease("D3", 0.12, now + 0.04, GameAudio.scale("wallCrash", 0.7));
      synth.triggerAttackRelease("A2", 0.18, now + 0.11, GameAudio.scale("wallCrash", 0.62));
    });
  }

  _hingeClearBoardEntities() {
    if (!this.$board?.length) return;

    const $targets = this.$board.find(".board-cell").filter((_, el) => {
      const $el = $(el);
      return $el.hasClass("snake")
        || $el.hasClass("snake-head")
        || $el.hasClass("food")
        || $el.hasClass("bomb")
        || $el.children().length > 0;
    });

    if (!$targets.length) {
      this.$restart?.show?.();
      return;
    }

    const lastIndex = $targets.length - 1;
    $targets.each((i, el) => {
      const delay = i * 85;
      const tid = setTimeout(() => {
        const $el = $(el);
        $el
          .removeClass("animate__animated animate__hinge animate__rubberBand")
          .addClass("animate__animated animate__hinge")
          .off(`animationend.${this.ns}BombHinge webkitAnimationEnd.${this.ns}BombHinge`)
          .one(`animationend.${this.ns}BombHinge webkitAnimationEnd.${this.ns}BombHinge`, () => {
            $el
              .removeClass("snake snake-head food bomb animate__animated animate__hinge animate__rubberBand")
              .html("")
              .css("animation-delay", "");
            if (i === lastIndex) this.$restart?.show?.();
          });
      }, delay);
      this._countdownTimeouts.push(tid);
    });
  }

  _handleBombCollision() {
    if (this._isGameOver) return;
    const hitCell = this._snake?.[0]
      ? this._wrapCell({
        r: this._snake[0].r + this._direction.dr,
        c: this._snake[0].c + this._direction.dc,
      })
      : null;
    this._isGameOver = true;
    this._stopLoop();
    this._clearCorrectStreak();
    this.$board?.addClass?.("failed");

    this._playWallCrashSfx();
    this._playBombHitFailSfx();
    this._runBoardPreExplosionShake(() => {
      this._explodeBombCollision(hitCell);
      const tid = setTimeout(() => {
        this._hingeClearBoardEntities();
      }, 520);
      this._countdownTimeouts.push(tid);
    });
  }

  _runBoardPreExplosionShake(onDone) {
    if (!this.$board?.length) {
      if (typeof onDone === "function") onDone();
      return;
    }

    const durationMs = 520;
    const stepMs = 22;
    const startedAt = Date.now();
    const $b = this.$board;

    const tick = setInterval(() => {
      const elapsed = Date.now() - startedAt;
      if (elapsed >= durationMs) {
        clearInterval(tick);
        $b.css("transform", "");
        if (typeof onDone === "function") onDone();
        return;
      }

      const t = elapsed / durationMs;
      const amp = 3 + (7 * t); // ramps up as if about to explode
      const dx = (Math.random() * 2 - 1) * amp;
      const dy = (Math.random() * 2 - 1) * amp;
      const rot = (Math.random() * 2 - 1) * (0.8 + 1.2 * t);
      $b.css("transform", `translate(${dx}px, ${dy}px) rotate(${rot}deg)`);
    }, stepMs);

    this._countdownTimeouts.push(tick);
  }

  _animateBoardCorrectHit() {
    if (!this.$board?.length) return;
    this.$board.removeClass("board-correct-hit");
    // eslint-disable-next-line no-unused-expressions
    this.$board[0] && this.$board[0].offsetWidth;
    this.$board.addClass("board-correct-hit");

    const tid = setTimeout(() => {
      this.$board.removeClass("board-correct-hit");
    }, 900);
    this._countdownTimeouts.push(tid);
  }

  _animateBoardWrongHit() {
    if (!this.$board?.length) return;
    this.$board.removeClass("board-wrong-hit");
    // eslint-disable-next-line no-unused-expressions
    this.$board[0] && this.$board[0].offsetWidth;
    this.$board.addClass("board-wrong-hit");

    const tid = setTimeout(() => {
      this.$board.removeClass("board-wrong-hit");
    }, 420);
    this._countdownTimeouts.push(tid);
  }

  _animateSnakeFinalCelebrate() {
    if (!this.$board?.length) return;
    const $cells = this.$board.find(".board-cell.snake");
    $cells.each((_, el) => {
      const $el = $(el);
      const dur = (0.85 + (Math.random() * 0.6)).toFixed(2);
      $el
        .removeClass("animate__animated animate__shakeY")
        .css("animation-duration", `${dur}s`);
      // eslint-disable-next-line no-unused-expressions
      el && el.offsetWidth;
      $el
        .addClass("animate__animated animate__shakeY")
        .off(`animationend.${this.ns}SnakeCelebrate webkitAnimationEnd.${this.ns}SnakeCelebrate`)
        .one(`animationend.${this.ns}SnakeCelebrate webkitAnimationEnd.${this.ns}SnakeCelebrate`, () => {
          $el.removeClass("animate__animated animate__shakeY").css("animation-duration", "");
        });
    });
  }

  _advanceSnake() {
    if (!this._snake.length || this._isGameOver) return;
    if (this._directionQueue.length) {
      const queued = this._directionQueue.shift();
      if (queued) this._direction = queued;
    }

    const head = this._snake[0];
    const rawNext = {
      r: head.r + this._direction.dr,
      c: head.c + this._direction.dc,
    };
    const hitWall = this._useRealWalls()
      && (rawNext.r < 0 || rawNext.r >= this._rows || rawNext.c < 0 || rawNext.c >= this._cols);
    if (hitWall) {
      this._playWallCrashSfx();
      this._explodeSnakeAndEndGame();
      return;
    }

    const next = this._wrapCell(rawNext);
    const hitBomb = this._showBombs() && this._bombs.some((bomb) => this._sameCell(bomb, next));
    if (hitBomb) {
      this._handleBombCollision();
      return;
    }
    const eatenFoodIdx = this._foods.findIndex((f) => this._sameCell(f, next));
    const eatenFood = eatenFoodIdx >= 0 ? this._foods[eatenFoodIdx] : null;
    const validTargets = new Set(
      (Array.isArray(this._targetNotes) ? this._targetNotes : [])
        .map((n) => String(n?.canonical || "").trim())
        .filter(Boolean),
    );
    const isCorrectFood = !!(
      eatenFood && validTargets.has(String(eatenFood.note?.canonical || "").trim())
    );
    const willGrow = !!isCorrectFood;

    this._snakeHop = {
      from: this._snake.slice(),
      startedAt: performance.now(),
      stepMs: this._snakeSpeedMs(),
    };
    this._snake.unshift(next);

    if (!willGrow) {
      this._snake.pop();
    }

    if (eatenFoodIdx >= 0) {
      const $eatenCell = this.$board.find(
        `.board-cell[data-row="${eatenFood.r}"][data-col="${eatenFood.c}"]`,
      ).first();
      if ($eatenCell.length) {
        playSmokePuffAtElement($eatenCell[0], { parentEl: document.body });
      }
      if (eatenFood?.id != null) this._animatedFoodIds.delete(eatenFood.id);
      this._foods.splice(eatenFoodIdx, 1);
    }

    if (isCorrectFood) {
      this._stats.checksTotal += 1;
      this._stats.checksCorrect += 1;
      this._roundsCompleted += 1;
      const firstTry = !this._madeMistakeThisRound;
      this._applyCorrectStreakForOutcome({ firstTry });

      const earned = this._awardPointsForCorrect();
      const isBonusSuccess = !this._isPracticeMode() && !this._madeMistakeThisRound;
      this._successAnimation({ isBonus: isBonusSuccess });
      if (earned > 0) this._showIncrement(earned);

      this._updateProgressBar();
      this._madeMistakeThisRound = false;
      this._headNote = this._cloneNote(eatenFood.note);
      this._animateBoardCorrectHit();
      this._renderEntities();

      if (!this._isPracticeMode() && this._roundsCompleted >= (Number(this.opts.numOfChallenges) || 4)) {
        this._isGameOver = true;
        this._stopLoop();
        this._playVictorySfx();
        this._animateSnakeFinalCelebrate();
        this._stats.finishedAtMs = Date.now();
        if (this._finalResultsTimeoutId != null) clearTimeout(this._finalResultsTimeoutId);
        this._finalResultsTimeoutId = queueFinalResultsReveal({
          showFinalResults: () => {
            this._finalResultsTimeoutId = null;
            this._showFinalResults();
          },
        });
        return;
      }

      this._runSuccessFeedbackTransition({
        $interval: this.$interval,
        delayMs: 700,
        onDone: () => {
          if (this._isGameOver) return;
          this._setIntervalUIWithDirection(this._pickInterval(), this._pickIntervalDirection());
          this._spawnFoods(2);
          this._spawnBombs(1);
          this._ensureTargetFoodPresent();
          this._renderEntities();
          this._advanceRoundTempo();
        },
      });
      return;
    } else if (eatenFood) {
      this._stats.checksTotal += 1;
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this._clearCorrectStreak();
      this._playFailSfx();
      this._animateBoardWrongHit();
      if (this._snake.length > 1) this._snake.pop(); // extra shrink by one
      this._spawnFoods(1);
      this._ensureTargetFoodPresent();
    }

    this._renderEntities();
  }

  _stopLoop() {
    if (this._pendingBpm != null) {
      this._currentBpm = this._pendingBpm;
      this._pendingBpm = null;
    }
    this._snakeHop = null;
    // Settle before pause, crash, or victory animations take over the cells.
    this.$board?.find?.(".snake-hopping").removeClass("snake-hopping");
    this._music.stop();
    if (this._tickTimer != null) {
      clearTimeout(this._tickTimer);
      this._tickTimer = null;
    }
  }

  _clearCountdownTimers() {
    this._countdown?.cancel();
    if (!Array.isArray(this._countdownTimeouts)) {
      this._countdownTimeouts = [];
      return;
    }
    this._countdownTimeouts.forEach((id) => clearTimeout(id));
    this._countdownTimeouts = [];
  }

  async _runCountdownThenStart() {
    const countdownRun = ++this._countdownRun;
    this._setPlayButtons(true);
    await this._countdown.prepareAudio();
    if (countdownRun !== this._countdownRun) return;

    this._countdown.start({
      beatMs: this._beatMs(),
      onComplete: () => {
        if (countdownRun !== this._countdownRun) return;
        this.$playWrap.hide();
        this._placeInitialSnake();
        this._directionQueue = [];
        this._spawnFoods(2, { preferredRow: this._rows - 2 });
        if (this._showBombs()) this._spawnBombs(2);
        this._ensureTargetFoodPresent();
        this._renderEntities();
        this._startLoop();
      },
    });
  }

  _awaitStartThenCountdown() {
    this._setPlayButtons(false);
  }

  _wireStartControls() {
    this.$playBtn
      .off(`click.${this.ns}Start`)
      .on(`click.${this.ns}Start`, (event) => {
        event.preventDefault();
        this._runCountdownThenStart();
      });

    this.$stopBtn
      .off(`click.${this.ns}Start`)
      .on(`click.${this.ns}Start`, (event) => {
        event.preventDefault();
        this._countdownRun += 1;
        this._clearCountdownTimers();
        this._awaitStartThenCountdown();
      });
  }

  _setPlayButtons(isPlaying) {
    this.$playWrap.show();
    this.$playBtn.toggle(!isPlaying);
    this.$stopBtn.toggle(isPlaying);
  }

  _startLoop() {
    if (this._pausedByModal) return;
    this._stopLoop();
    if (this._isSoundEnabled()) {
      this._music.start(this._currentBpm);
      this._music.playStep(this._snake.length);
    }
    let nextTickAt = performance.now() + this._snakeSpeedMs();
    const tick = () => {
      const tickStartedAt = performance.now();
      if (this._pendingBpm != null) {
        this._currentBpm = this._pendingBpm;
        this._pendingBpm = null;
        this._music.setTempo(this._currentBpm);
      }
      this._advanceSnake();
      if (this._isGameOver || this._tickTimer == null) return;

      this._music.playStep(this._snake.length);
      // Include rendering time in the beat, and avoid catch-up bursts after a stall.
      nextTickAt = Math.max(nextTickAt, tickStartedAt) + this._snakeSpeedMs();
      this._tickTimer = setTimeout(tick, Math.max(0, nextTickAt - performance.now()));
    };
    this._tickTimer = setTimeout(tick, this._snakeSpeedMs());
  }

  _showStandardGameUi() {
    if (typeof this._syncKeyboardLabels === "function") this._syncKeyboardLabels();
    this.instructionsUi.show().replay();
    $("#controls").show();
    $("#board-wrapper").show();
    this.$board?.toggleClass?.("walled", this._useRealWalls());
    this.$interval?.show?.();
    if (!this._currentIntervalAbbr) {
      this._setIntervalUIWithDirection(this._pickInterval(), this._pickIntervalDirection());
    }
    this._targetNotes = this._computeTargetNotes();
  }

  _clearFoodAnimTimers() {
    if (!Array.isArray(this._foodAnimTimeouts)) {
      this._foodAnimTimeouts = [];
      return;
    }
    this._foodAnimTimeouts.forEach((id) => clearTimeout(id));
    this._foodAnimTimeouts = [];
  }

  start() {
    this._stopLoop();
    this._music.reset();
    this._currentBpm = this.opts.bpm;
    $(window).off(`pagehide.${this.ns}Music`).on(`pagehide.${this.ns}Music`, () => {
      this._stopLoop();
      this._music.reset();
    });
    if (this._finalResultsTimeoutId != null) {
      clearTimeout(this._finalResultsTimeoutId);
      this._finalResultsTimeoutId = null;
    }
    this._clearCountdownTimers();
    this._clearFoodAnimTimers();
    this._wireStartControls();
    this._wireKeyboardControls();
    this._wireModalPause();
    this._armUiSfxOnFirstGesture();
    this._renderBoard();
    this._wireSwipeControls();
    this._currentIntervalAbbr = null;
    this._currentIntervalDirection = 1;
    this._showStandardGameUi();
    this.$board.find(".board-cell").removeClass("snake snake-head food");
    this._snake = [];
    this._foods = [];
    this._bombs = [];
    this._foodIdCounter = 1;
    this._animatedFoodIds = new Set();
    this._isGameOver = false;
    this._pausedByModal = false;
    this._directionQueue = [];
    this._headNote = null;
    this._targetNotes = [];
    this._pointsValue = 0;
    this._roundsCompleted = 0;
    this._madeMistakeThisRound = false;
    this._madeAnyMistake = false;
    this._clearCorrectStreak();
    this._stats = { checksTotal: 0, checksCorrect: 0, finishedAtMs: null };
    this._finalStartMs = Date.now();
    this._syncPracticeUi();
    this.$feedback?.hide?.();
    this.$restart?.hide?.();
    this.$board?.removeClass?.("failed");
    this.$points.text("0");
    this.$progressBar.data("progress", 0).css({ width: "0%" });
    if (this._isPracticeMode()) this.$progressCounter.text("Practice");
    else this.$progressCounter.text(`0 of ${Number(this.opts.numOfChallenges) || 4}`);
    this._awaitStartThenCountdown();
  }

  _isSoundEnabled() {
    const v = this.opts.sound;
    if (v === true) return true;
    const s = String(v ?? "").trim().toLowerCase();
    if (v === false) return false;
    return !(s === "off" || s === "false" || s === "0");
  }

  isSoundEnabled() {
    return this._isSoundEnabled();
  }

  _awardPointsForCorrect() {
    if (this._isPracticeMode()) {
      this.$points.text("0");
      return 0;
    }
    const base = Number.isFinite(Number(this.opts.basePoints)) ? Number(this.opts.basePoints) : 1;
    const bonus = Number.isFinite(Number(this.opts.firstTryBonus)) ? Number(this.opts.firstTryBonus) : 2;
    const earned = this._madeMistakeThisRound ? base : (base + bonus);
    this._pointsValue += Math.max(0, earned);
    this.$points.text(String(this._pointsValue));
    return earned;
  }

  _updateProgressBar() {
    if (this._isPracticeMode()) {
      this.$progressBar.data("progress", 0).css({ width: "0%" });
      this.$progressCounter.text("Practice");
      return 0;
    }
    const steps = Math.max(1, Number(this.opts.numOfChallenges) || 1);
    const progress = Math.min(100, (this._roundsCompleted / steps) * 100);
    this.$progressBar.data("progress", progress).css({ width: `${progress}%` });
    this.$progressCounter.text(`${Math.min(steps, this._roundsCompleted)} of ${steps}`);
    return progress;
  }

  _showFinalResults() {
    if (this._isPracticeMode()) return;
    this._stopLoop();
    this._stats.finishedAtMs = this._stats.finishedAtMs || Date.now();
    const accuracy = this._stats.checksTotal
      ? Math.round((this._stats.checksCorrect / this._stats.checksTotal) * 100)
      : 0;
    const durationSec = Math.max(0, Math.floor((this._stats.finishedAtMs - this._finalStartMs) / 1000));
    const perfectGame = this._stats.checksCorrect > 0 && !this._madeAnyMistake;
    const scoreSummary = BaseStaffGame.prototype._buildFinalScoreSummary.call(this, {
      basePoints: this._pointsValue,
      accuracy,
      perfectGame,
    });

    if (perfectGame) {
      const tid = setTimeout(() => {
        this.$doublePoints?.show?.();
        this._playPerfectGameBonusSfx();
      }, 1750);
      this._countdownTimeouts.push(tid);
    } else {
      this.$doublePoints?.hide?.();
    }

    if (scoreSummary.settingsBonus) {
      // eslint-disable-next-line no-console
      console.log("[NotePython] Extra doubling is happening because the user selected challenging settings.", {
        scoreBeforeSettingsBonus: scoreSummary.initialScore,
        finalPoints: scoreSummary.finalScore,
      });
    }

    this.$controls?.hide?.();
    renderFinalResultsOverlay({
      $finalOverlay: this.$finalOverlay,
      rounds: Number(this.opts.numOfChallenges) || 0,
      score: scoreSummary.finalScore,
      accuracy,
      durationSec,
      settingsBonus: scoreSummary.settingsBonus,
      clearCountupTimers: () => this._clearFinalCountupTimers(),
      countupTimers: this._finalCountupTimeouts,
      animateMetrics: () => this._animateFinalMetricsWithSfx(),
      playFinalSfx: () => this._playFinalSfx(),
    });
  }

  _ensureUiSfxAudio() {
    return BaseStaffGame.prototype._ensureUiSfxAudio.call(this);
  }

  _armUiSfxOnFirstGesture() {
    return BaseStaffGame.prototype._armUiSfxOnFirstGesture.call(this);
  }

  _playSuccessSfxBasic() {
    if (this._hasCompletedRounds()) return;
    return BaseStaffGame.prototype._playSuccessSfxBasic.call(this);
  }

  _playSuccessSfxBonus() {
    if (this._hasCompletedRounds()) return;
    return BaseStaffGame.prototype._playSuccessSfxBonus.call(this);
  }

  _successAnimation(args) {
    return BaseStaffGame.prototype._successAnimation.call(this, args);
  }

  _runSuccessFeedbackTransition(args) {
    return BaseStaffGame.prototype._runSuccessFeedbackTransition.call(this, args);
  }

  _applyCorrectStreakForOutcome(args) {
    return BaseStaffGame.prototype._applyCorrectStreakForOutcome.call(this, args);
  }

  getCorrectStreak() {
    return BaseStaffGame.prototype.getCorrectStreak.call(this);
  }

  _syncStreakBarClass() {
    return BaseStaffGame.prototype._syncStreakBarClass.call(this);
  }

  _clearCorrectStreak() {
    return BaseStaffGame.prototype._clearCorrectStreak.call(this);
  }

  _playFailSfx() {
    return BaseStaffGame.prototype._playFailSfx.call(this);
  }

  _playVictorySfx() {
    if (this._isSoundEnabled()) this._music.playVictory(this._currentBpm);
  }

  _playFinalSfx() {
    return BaseStaffGame.prototype._playFinalSfx.call(this);
  }

  _playPerfectGameBonusSfx() {
    return BaseStaffGame.prototype._playPerfectGameBonusSfx.call(this);
  }

  _hasCompletedRounds() {
    return !this._isPracticeMode()
      && this._roundsCompleted >= (Number(this.opts.numOfChallenges) || 4);
  }

  _playFinalMetricPopSfx(index) {
    return BaseStaffGame.prototype._playFinalMetricPopSfx.call(this, index);
  }

  _clearFinalMetricsSfxTimers() {
    return BaseStaffGame.prototype._clearFinalMetricsSfxTimers.call(this);
  }

  _animateFinalMetricsWithSfx() {
    return BaseStaffGame.prototype._animateFinalMetricsWithSfx.call(this);
  }

  _showIncrement(earned) {
    return BaseStaffGame.prototype._showIncrement.call(this, earned);
  }

  _clearFinalCountupTimers() {
    if (!Array.isArray(this._finalCountupTimeouts)) {
      this._finalCountupTimeouts = [];
      return;
    }
    this._finalCountupTimeouts.forEach((id) => clearTimeout(id));
    this._finalCountupTimeouts = [];
  }
}
