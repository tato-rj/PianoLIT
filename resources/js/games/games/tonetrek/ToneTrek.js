import {
  renderFinalResultsOverlay,
  queueFinalResultsReveal,
} from "../shared/finalResults.js";
import { BaseStaffGame } from "../base/BaseStaffGame.js";
import { GameAudio } from "../shared/GameAudio.js";
import { InstructionsUi } from "../shared/InstructionsUi.js";
import {
  MusicKeyboardController,
  applyAccidentalToSingleNoteValue,
  deleteSingleNoteValue,
  keyboardLabelForLetter,
} from "../shared/MusicKeyboardController.js";

export class ToneTrek {
  static INTERVALS_FALLBACK = [
    "m2", "M2", "m3", "M3", "P4", "A4", "d5", "P5", "m6", "M6", "m7", "M7", "P8",
  ];
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
  static SOLFEGE_TO_LETTER = {
    DO: "C",
    RE: "D",
    MI: "E",
    FA: "F",
    SOL: "G",
    LA: "A",
    SI: "B",
  };

  constructor(options = {}) {
    const defaults = {
      tableEl: "table.table",
      pathLength: 9,
      maxGenerateAttempts: 2000,
      maxStraightRun: 3,
      intervals: null,
      initialNotes: null,
      allowAccidentals: false,
      solfege: false,
      sound: true,
      basePoints: 1,
      firstTryBonus: 2,
      numOfChallenges: 4,
      namespace: "toneTrek",
    };

    this.opts = { ...defaults, ...(options || {}) };
    this.opts.numOfChallenges = this._normalizeNumOfChallenges(this.opts.numOfChallenges);
    this.$table = $(this.opts.tableEl).first();
    this.$musicKeyboard = $("#music-keyboard").first();
    this.$feedback = $("#feedback-success");
    this.$helpBtn = $("#help");
    this.$skipWrap = $("#skip");
    this.$skipBtn = this.$skipWrap.find("button");
    this.$checkWrap = $("#check");
    this.$checkBtn = $("#check button");
    this.$continueWrap = $("#continue");
    this.$continueBtn = $("#continue button");
    this.$timeupMessage = $("#timeup-message");
    this.$timer = $("#timer");
    this.$timerBox = this.$timer.children("div").first();
    this.$timerText = this.$timer.find("span");
    this.$progressBar = $("#progress-bar");
    this.$progressCounter = $("#progress-counter");
    this.$points = $("#points");
    this.$finalOverlay = $("#final-overlay");
    this.$doublePoints = $("#double-points");
    this.instructionsUi = new InstructionsUi("#instructions");
    this.ns = this.opts.namespace || "toneTrek";
    this._audioReady = false;
    this._synth = null;
    this._uiSfxReady = false;
    this._uiSfxSynth = null;
    this._uiSfxNoise = null;
    this._uiTimerSfxSynth = null;
    this._audioUnlockArmed = false;
    this._revealTimeouts = [];
    this._timerTimeoutId = null;
    this._timerRemainingSec = 0;
    this._timerEndsAtMs = 0;
    this.$activeBlockInput = null;
    this.$activeBlockCell = null;
    this._musicKeyboard = new MusicKeyboardController({
      namespace: this.ns,
      $keyboard: this.$musicKeyboard,
      pushTimeout: (id) => this._revealTimeouts.push(id),
    });
    this._correctionMode = false;
    this._wrongEditableIndexes = new Set();
    this._finalStartMs = Date.now();
    this._currentRound = 1;
    this._roundLocked = false;
    this._usedHintThisRound = false;
    this._madeMistakeThisRound = false;
    this._madeAnyMistake = false;
    this._clearCorrectStreak();
    this._points = 0;
    this._stats = {
      checksTotal: 0,
      checksCorrect: 0,
      finishedAtMs: null,
    };
    this._roundAnswerKey = {};
    this._roundRecords = [];
    this._finalMetricsSfxTimeouts = [];
    this._finalCountupTimeouts = [];
    this._checkWrongStateTimeout = null;
  }

  start() {
    if (!this.$table.length) return;
    this._wireControls();
    this._wireNoteInputUx();
    this._wireIntervalBlocks();
    this._showStandardGameUi();
    this._resetRunUi();
    this._startRound();
  }

  _normalizeNumOfChallenges(raw) {
    const n = Number(raw);
    const safe = Number.isFinite(n) ? Math.trunc(n) : 4;
    if (safe < ToneTrek.MIN_CHALLENGES) return ToneTrek.MIN_CHALLENGES;
    if (safe > ToneTrek.MAX_CHALLENGES) return ToneTrek.MAX_CHALLENGES;
    return safe;
  }

  _resetRunUi() {
    this._finalStartMs = Date.now();
    this._currentRound = 1;
    this._roundLocked = false;
    this._usedHintThisRound = false;
    this._madeMistakeThisRound = false;
    this._madeAnyMistake = false;
    this._clearCorrectStreak();
    this._points = 0;
    this._stats = {
      checksTotal: 0,
      checksCorrect: 0,
      finishedAtMs: null,
    };
    this._roundAnswerKey = {};
    this._roundRecords = [];
    this._clearFinalMetricsSfxTimers();
    this._clearFinalCountupTimers();

    this._syncPracticeUi();
    this.$points.text("0");
    this.$feedback.hide();
    this.$helpBtn.hide();
    this.$skipWrap.hide();
    this.$continueWrap.hide();
    this._hideTimeUpMessage();
    this._setTimedOutInteractivityDisabled(false);
    this.$finalOverlay.hide();
    this.$doublePoints?.hide?.();
    this.$checkBtn.attr("state", "waiting").empty();
    this.$progressBar.data("progress", 0).css({ width: "0%" });
    this.$progressCounter.text(this._isPracticeMode() ? "Practice" : `0 of ${this.opts.numOfChallenges}`);
    if (this._isTimerEnabled()) this.$timer.show();
    else this.$timer.hide();
  }

  _startRound() {
    this._roundLocked = false;
    this._correctionMode = false;
    this._wrongEditableIndexes = new Set();
    this._usedHintThisRound = false;
    this._madeMistakeThisRound = false;
    this.$helpBtn.hide();
    this.$skipWrap.hide();
    this.$continueWrap.hide();
    this.$feedback.hide();
    this.$checkWrap.hide().addClass("invisible");
    this._setInstructions("Tap an interval to hear it, then add the next note.");
    this._armUiSfxOnFirstGesture();
    this._syncKeyboardLabels();
    this._hideTimeUpMessage();
    this._setTimedOutInteractivityDisabled(false);
    this._hideMusicKeyboard();
    this._clearActiveBlockInput();
    const $rows = this.$table.find("tbody tr");
    const rowCount = $rows.length;
    const colCount = rowCount ? $rows.first().find("td").length : 0;
    if (!rowCount || !colCount) return;

    this._clearRevealTimers();
    this.$table.find("td")
      .removeClass("block interval-block initial-block")
      .removeAttr("id")
      .empty();
    this._hideAllCellsVisual();

    const maxCells = rowCount * colCount;
    const pathLength = Math.max(1, Math.min(maxCells, Number(this.opts.pathLength) || 9));
    const layout = this._buildRoundLayout({
      rowCount,
      colCount,
      pathLength,
    });
    if (!layout) return;
    const revealItems = layout.revealItems;
    this._roundAnswerKey = layout.answerKey || {};

    this._revealPathCells(revealItems);
    if (this._isTimerEnabled()) this._resetRoundTimerIfEnabled();
    else {
      this._stopGameTimer();
      this.$timer.hide();
    }
  }

  _buildRoundLayout({ rowCount, colCount, pathLength }) {
    const maxAttempts = Math.max(1, Number(this.opts.maxGenerateAttempts) || 2000);
    const maxStraightRun = Math.max(1, Number(this.opts.maxStraightRun) || 3);
    const intervalPool = this._intervalPool();

    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
      const path = this._generatePath({
        rowCount,
        colCount,
        length: pathLength,
        maxAttempts: 1,
        maxStraightRun,
      });
      if (!path) continue;

      const initialRaw = this._pickInitialNote();
      const initialNote = this._parseSpelledNote(initialRaw) || this._parseSpelledNote("C");
      if (!initialNote) continue;

      const revealItems = [];
      const answerKey = {};
      let prevExpected = initialNote;
      let failed = false;

      for (let i = 0; i < path.length; i += 1) {
        const cell = path[i];
        if (i === 0) {
          const next = path[1] || null;
          const arrowClass = this._arrowClassForNextStep(cell, next);
          revealItems.push({
            r: cell.r,
            c: cell.c,
            i,
            cls: "initial-block",
            html: `<div><span>START HERE</span><input type="text" name="note" value="${this._noteDisplay(initialNote)}" disabled=""><i class="fa-solid ${arrowClass}"></i></div>`,
          });
          continue;
        }

        if (i % 2 === 1) {
          let pickedInterval = null;
          let pickedDir = null;
          let nextExpected = null;

          const localAttempts = Math.max(8, intervalPool.length * 3);
          for (let t = 0; t < localAttempts; t += 1) {
            const interval = intervalPool[Math.floor((this._duelRandom?.() ?? Math.random()) * intervalPool.length)];
            const dir = (this._duelRandom?.() ?? Math.random()) < 0.5 ? -1 : 1;
            const candidate = this._spelledIntervalTarget(prevExpected, interval, dir);
            if (!candidate) continue;
            pickedInterval = interval;
            pickedDir = dir;
            nextExpected = candidate;
            break;
          }

          if (!pickedInterval || !nextExpected) {
            failed = true;
            break;
          }

          answerKey[i + 1] = nextExpected;
          prevExpected = nextExpected;
          revealItems.push({
            r: cell.r,
            c: cell.c,
            i,
            cls: "interval-block",
            html: `<div><button type="button" data-interval="${pickedInterval}"><i class="fa-solid fa-volume-up"></i></button><span interval>${pickedInterval}</span><span direction>${pickedDir === -1 ? "DOWN" : "UP"}</span></div>`,
          });
          continue;
        }

        revealItems.push({
          r: cell.r,
          c: cell.c,
          i,
          cls: "block",
          html: `<div><span style="opacity: 0.2; font-size: 1rem;">ADD NOTE HERE</span><input type="text" name="note"></div>`,
        });
      }

      if (!failed) return { revealItems, answerKey };
    }

    return null;
  }

  _intervalPool() {
    const fromOptions = Array.isArray(this.opts.intervals)
      ? this.opts.intervals.filter(Boolean).map((x) => String(x).trim())
      : [];
    return fromOptions.length ? fromOptions : ToneTrek.INTERVALS_FALLBACK.slice();
  }

  _normalizeOnOff(v) {
    if (v === true) return true;
    const s = String(v ?? "").trim().toLowerCase();
    return s === "on" || s === "true" || s === "1";
  }

  _readBoolSetting(key, fallback = false) {
    const direct = this.opts?.[key];
    if (direct !== undefined && direct !== null && String(direct).trim() !== "") {
      return this._normalizeOnOff(direct);
    }

    try {
      const qs = new URLSearchParams(window.location.search || "");
      if (qs.has(key)) return this._normalizeOnOff(qs.get(key));
    } catch (_) {}

    return !!fallback;
  }

  _isSolfegeEnabled() {
    return this._readBoolSetting("solfege", false);
  }

  _allowsInitialAccidentals() {
    return this._readBoolSetting("allowAccidentals", false);
  }

  _noteDisplay(noteObj) {
    if (!noteObj) return "";
    const letter = String(noteObj.letter || "").toUpperCase();
    const base = this._isSolfegeEnabled()
      ? (ToneTrek.LETTER_TO_SOLFEGE[letter] || letter)
      : letter;
    return `${base}${this._accidentalDisplayFromOffset(Number(noteObj.accOffset) || 0)}`;
  }

  _syncKeyboardLabels() {
    this._musicKeyboard.syncLetterButtonLabels({
      useSolfege: this._isSolfegeEnabled(),
      letterToSolfege: ToneTrek.LETTER_TO_SOLFEGE,
    });
  }

  _generatePath({ rowCount, colCount, length, maxAttempts, maxStraightRun }) {
    const dirs = [
      { dr: 1, dc: 0 },
      { dr: -1, dc: 0 },
      { dr: 0, dc: 1 },
      { dr: 0, dc: -1 },
    ];

    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
      const startCol = Math.floor((this._duelRandom?.() ?? Math.random()) * colCount);
      const path = [{ r: 0, c: startCol }];
      const occupied = new Set([`0,${startCol}`]);

      let prevDir = null;
      let sameDirRun = 0;

      while (path.length < length) {
        const cur = path[path.length - 1];
        const candidates = [];

        for (let i = 0; i < dirs.length; i += 1) {
          const dir = dirs[i];

          if (
            prevDir &&
            dir.dr === prevDir.dr &&
            dir.dc === prevDir.dc &&
            sameDirRun >= maxStraightRun
          ) {
            continue;
          }

          const nr = cur.r + dir.dr;
          const nc = cur.c + dir.dc;

          if (nr < 0 || nr >= rowCount || nc < 0 || nc >= colCount) continue;
          const key = `${nr},${nc}`;
          if (occupied.has(key)) continue;

          const sideNeighbors = this._sideNeighbors(nr, nc, occupied);
          if (sideNeighbors.length !== 1) continue;
          if (sideNeighbors[0] !== `${cur.r},${cur.c}`) continue;

          candidates.push({ r: nr, c: nc, dir });
        }

        if (!candidates.length) break;

        const picked = candidates[Math.floor((this._duelRandom?.() ?? Math.random()) * candidates.length)];
        path.push({ r: picked.r, c: picked.c });
        occupied.add(`${picked.r},${picked.c}`);

        if (prevDir && picked.dir.dr === prevDir.dr && picked.dir.dc === prevDir.dc) {
          sameDirRun += 1;
        } else {
          prevDir = picked.dir;
          sameDirRun = 1;
        }
      }

      if (path.length === length) return path;
    }

    return null;
  }

  _sideNeighbors(r, c, occupied) {
    const out = [];
    const neighbors = [
      [r + 1, c],
      [r - 1, c],
      [r, c + 1],
      [r, c - 1],
    ];

    for (let i = 0; i < neighbors.length; i += 1) {
      const key = `${neighbors[i][0]},${neighbors[i][1]}`;
      if (occupied.has(key)) out.push(key);
    }

    return out;
  }

  _cellAt(r, c) {
    const $row = this.$table.find("tbody tr").eq(r);
    return $row.find("td").eq(c);
  }

  _pickInitialInterval() {
    const fromOptions = Array.isArray(this.opts.intervals)
      ? this.opts.intervals.filter(Boolean)
      : [];
    const pool = fromOptions.length ? fromOptions : ToneTrek.INTERVALS_FALLBACK;
    return String(pool[Math.floor((this._duelRandom?.() ?? Math.random()) * pool.length)] || "M2");
  }

  _arrowClassForNextStep(cur, next) {
    if (!cur || !next) return "fa-arrow-right";
    const dr = next.r - cur.r;
    const dc = next.c - cur.c;
    if (dr === 1 && dc === 0) return "fa-arrow-down";
    if (dr === -1 && dc === 0) return "fa-arrow-up";
    if (dr === 0 && dc === 1) return "fa-arrow-right";
    if (dr === 0 && dc === -1) return "fa-arrow-left";
    return "fa-arrow-right";
  }

  _pickInitialNote() {
    const fromOptions = Array.isArray(this.opts.initialNotes)
      ? this.opts.initialNotes.filter(Boolean).map((x) => String(x).trim())
      : [];
    const fallback = ["C", "D", "E", "F", "G", "A", "B"];
    const pool = fromOptions.length ? fromOptions : fallback;
    const picked = String(pool[Math.floor((this._duelRandom?.() ?? Math.random()) * pool.length)] || "E");
    const parsed = this._parseSpelledNote(picked) || this._parseSpelledNote("E");
    if (!parsed) return "E";
    if (!this._allowsInitialAccidentals()) return `${parsed.letter}`;

    const accidentalChoices = [0, 0, 0, 1, -1];
    const off = accidentalChoices[Math.floor((this._duelRandom?.() ?? Math.random()) * accidentalChoices.length)] || 0;
    const acc = off === 1 ? "#" : off === -1 ? "b" : "";
    return `${parsed.letter}${acc}`;
  }

  _wireIntervalBlocks() {
    this.$table
      .off(`click.${this.ns}Block`)
      .on(`click.${this.ns}Block`, "td.interval-block", (e) => {
        if ($(e.target).closest("button[data-interval]").length) return;
        const $btn = $(e.currentTarget).find('button[data-interval]').first();
        if ($btn.length) $btn.trigger("click");
      });

    this.$table
      .off(`click.${this.ns}Btn`)
      .on(`click.${this.ns}Btn`, 'td.interval-block button[data-interval]', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(e.currentTarget);
        const $cell = $btn.closest("td.interval-block");
        const idx = parseInt($cell.attr("data-path-index"), 10);
        if (!Number.isFinite(idx) || idx <= 0) {
          this._shakeCell($cell);
          return;
        }

        const $prev = this.$table.find(`td[data-path-index="${idx - 1}"]`).first();
        const prevMidi = this._midiFromCell($prev);
        if (!Number.isFinite(prevMidi)) {
          this._shakeCell($cell);
          return;
        }

        const interval = String($btn.attr("data-interval") || "").trim();
        const dirRaw = String($cell.find("span[direction]").first().text() || "").trim().toUpperCase();
        const dir = dirRaw === "DOWN" ? -1 : 1;
        const semis = this._intervalToSemitones(interval);
        if (!Number.isFinite(semis)) {
          this._shakeCell($cell);
          return;
        }

        const secondMidi = prevMidi + (dir * semis);
        this._playDictationLike(prevMidi, secondMidi);
      });
  }

  _wireControls() {
    this.$checkBtn
      .off(`click.${this.ns}Check`)
      .on(`click.${this.ns}Check`, (e) => {
        e.preventDefault();
        this._onCheck();
      });

    this.$helpBtn
      .off(`click.${this.ns}Help`)
      .on(`click.${this.ns}Help`, (e) => {
        e.preventDefault();
        this._onHelp();
      });

    this.$continueBtn
      .off(`click.${this.ns}Continue`)
      .on(`click.${this.ns}Continue`, (e) => {
        e.preventDefault();
        this.$continueWrap.hide();
        this.$skipWrap.hide();
        this._hideTimeUpMessage();
        this._setTimedOutInteractivityDisabled(false);
        if (this._isPracticeMode()) {
          this._currentRound += 1;
          this._startRound();
          return;
        }
        if (this._currentRound >= this.opts.numOfChallenges) {
          this._showFinalResults();
          return;
        }
        this._currentRound += 1;
        this._startRound();
      });

    this.$skipBtn
      .off(`click.${this.ns}Skip`)
      .on(`click.${this.ns}Skip`, (e) => {
        e.preventDefault();
        this._finishRoundAsTimedOut();
      });
  }

  _onCheck() {
    if (this._roundLocked) return;

    const evalResult = this._evaluateRoundAnswers();
    this._renderBlockCheckMarks(evalResult);

    const isComplete = this._areAllBlockInputsFilled();
    if (!isComplete) {
      this._syncBlocksCompletionUi(false);
      this._failAnimation();
      this._clearCorrectStreak();
      this._playFailSfx();
      this.$helpBtn.show();
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this._setInstructions("Add each missing note before checking your answer.");
      return;
    }

    this._recordRoundCheck(evalResult);
    this._stats.checksTotal += 1;

    if (evalResult.correct) {
      this._correctionMode = false;
      this._wrongEditableIndexes = new Set();
      this._stats.checksCorrect += 1;
      const earned = this._awardPointsForCorrect();
      this._finalizeRoundRecord({ passed: true, earned });
      this._pauseGameTimer();
      if (this._isPracticeMode()) {
        this._clearCorrectStreak();
        this._playSuccessSfxBasic();
      } else if (!this._usedHintThisRound && !this._madeMistakeThisRound) {
        this._applyCorrectStreakForOutcome({ firstTry: true });
        this._playSuccessSfxBonus();
      } else {
        this._clearCorrectStreak();
        this._playSuccessSfxBasic();
      }
      this._showSuccessAnimation();
      this._setInstructions("You got it! Continue when you’re ready.");
      this._updateProgressBar();
      this._roundLocked = true;
      this.$table.find('td.block input[name="note"]').prop("disabled", true);
      this.$helpBtn.hide();
      if (this._currentRound >= this.opts.numOfChallenges) {
        this.$checkWrap.hide();
        this.$continueWrap.hide();
        const tid = queueFinalResultsReveal({
          $button: this.$checkBtn,
          showFinalResults: () => this._showFinalResults(),
          delayMs: 1400,
        });
        this._revealTimeouts.push(tid);
      } else {
        this.$checkWrap.hide();
        this.$continueWrap.show();
        this.$continueBtn.text("Continue");
      }

      if (earned > 0) {
        $("#score").animateCSS && $("#score").animateCSS("heartBeat");
      }
      return;
    }

    this._madeAnyMistake = true;
    this._madeMistakeThisRound = true;
    this._clearCorrectStreak();
    this._finalizeRoundRecord({ passed: false, earned: 0 });
    this._enterCorrectionMode(evalResult);
    this._failAnimation();
    this._playFailSfx();
    this.$helpBtn.show();
    this._setInstructions("Not quite—adjust the marked notes and try again.");
  }

  _onHelp() {
    if (this._roundLocked) return;
    const evalResult = this._evaluateRoundAnswers();
    const answers = evalResult.expectedByIndex || {};
    const entries = Object.keys(answers);
    if (!entries.length) return;

    this._usedHintThisRound = true;
    this._ensureRoundRecord().usedHint = true;

    entries.forEach((idxStr) => {
      const idx = Number(idxStr);
      const $cell = this.$table.find(`td[data-path-index="${idx}"]`).first();
      if (!$cell.length || !$cell.hasClass("block")) return;
      const $input = $cell.find('input[name="note"]').first();
      if (!$input.length) return;
      const note = String(this._noteDisplay(answers[idxStr]) || "");
      if (!note) return;
      $input.val(note).trigger("input");
    });

    this.$helpBtn.hide();
    this._setInstructions("Here’s the completed path. Check it when you’re ready.");
  }

  _areAllBlockInputsFilled() {
    const $inputs = this.$table.find('td.block input[name="note"]');
    if (!$inputs.length) return false;
    let allFilled = true;
    $inputs.each((_, el) => {
      if (!String(el.value || "").trim()) allFilled = false;
    });
    return allFilled;
  }

  _evaluateRoundAnswers() {
    const expectedByIndex = this._roundAnswerKey || {};
    const marksByIndex = {};
    const expectedKeys = Object.keys(expectedByIndex);
    if (!expectedKeys.length) {
      return { correct: false, expectedByIndex, marksByIndex, correctCount: 0, wrongCount: 0 };
    }

    let correct = true;
    let correctCount = 0;
    let wrongCount = 0;
    expectedKeys.forEach((idxStr) => {
      const expected = expectedByIndex[idxStr];
      const $target = this.$table.find(`td.block[data-path-index="${idxStr}"]`).first();
      const user = this._noteFromCell($target);
      const ok = !!(user && user.canonical === expected.canonical);
      marksByIndex[idxStr] = ok;
      if (!ok) {
        correct = false;
        wrongCount += 1;
      } else {
        correctCount += 1;
      }
    });

    return { correct, expectedByIndex, marksByIndex, correctCount, wrongCount };
  }

  _ensureRoundRecord() {
    const idx = Math.max(0, Number(this._currentRound || 1) - 1);
    if (!this._roundRecords[idx]) {
      this._roundRecords[idx] = {
        round: idx + 1,
        checks: 0,
        firstCorrect: null,
        firstWrong: null,
        finalCorrect: 0,
        finalWrong: 0,
        usedHint: false,
        passed: false,
        points: 0,
      };
    }
    return this._roundRecords[idx];
  }

  _recordRoundCheck(evalResult) {
    const rec = this._ensureRoundRecord();
    rec.checks += 1;
    if (rec.firstCorrect == null || rec.firstWrong == null) {
      rec.firstCorrect = Number(evalResult?.correctCount || 0);
      rec.firstWrong = Number(evalResult?.wrongCount || 0);
    }
    rec.finalCorrect = Number(evalResult?.correctCount || 0);
    rec.finalWrong = Number(evalResult?.wrongCount || 0);
    rec.usedHint = rec.usedHint || this._usedHintThisRound;
  }

  _finalizeRoundRecord({ passed, earned }) {
    const rec = this._ensureRoundRecord();
    rec.passed = !!passed;
    rec.usedHint = rec.usedHint || this._usedHintThisRound;
    rec.points = Number(earned || 0);
  }

  _enterCorrectionMode(evalResult) {
    const marks = evalResult?.marksByIndex || {};
    this._correctionMode = true;
    this._wrongEditableIndexes = new Set();

    Object.keys(marks).forEach((idxStr) => {
      if (!marks[idxStr]) this._wrongEditableIndexes.add(Number(idxStr));
    });

    this._updateNoteInputProgression();
  }

  _renderBlockCheckMarks(evalResult) {
    const marks = evalResult?.marksByIndex || {};
    this.$table.find("td.block .block-correct").remove();
    this.$table.find("td.block .block-wrong").remove();
    this.$table.find("td.block > div").removeClass("bg-green bg-red");
    this.$table.find("td.block").removeClass("animate__animated animate__rubberBand animate__shakeX");

    Object.keys(marks).forEach((idxStr) => {
      const ok = !!marks[idxStr];
      const $cell = this.$table.find(`td.block[data-path-index="${idxStr}"]`).first();
      if (!$cell.length) return;
      const $wrap = $cell.children("div").first();
      if (!$wrap.length) return;

      $wrap.addClass(ok ? "bg-green" : "bg-red");

      $cell.removeClass("animate__animated animate__rubberBand animate__shakeX");
      // eslint-disable-next-line no-unused-expressions
      $cell[0] && $cell[0].offsetWidth;
      if (ok) $cell.addClass("animate__animated animate__rubberBand");

      const html = ok
        ? '<span class="block-correct"><i class="fa-solid fa-circle-check"></i></span>'
        : '<span class="block-wrong"><i class="fa-solid fa-circle-xmark"></i></span>';
      $wrap.append(html);
    });
  }

  _noteFromCell($cell) {
    if (!$cell || !$cell.length) return null;
    const textFromInput = String($cell.find('input[name="note"]').first().val() || "").trim();
    const raw = textFromInput || String($cell.text() || "").trim();
    return this._parseSpelledNote(raw);
  }

  _parseSpelledNote(rawText) {
    const normalized = String(rawText || "")
      .trim()
      .replaceAll("𝄪", "##")
      .replaceAll("♯", "#")
      .replaceAll("𝄫", "bb")
      .replaceAll("♭", "b");

    const m = normalized.match(/^([A-Ga-g]|DO|RE|MI|FA|SOL|LA|SI)((?:#{1,2})|(?:b{1,2})|)?(\d+)?$/i);
    if (!m) return null;

    const token = m[1].toUpperCase();
    const letter = ToneTrek.SOLFEGE_TO_LETTER[token] || token;
    const acc = m[2] || "";
    const accOffset =
      acc === "##" ? 2 :
      acc === "#" ? 1 :
      acc === "bb" ? -2 :
      acc === "b" ? -1 :
      0;
    const basePc = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 }[letter];
    const pitchClass = ((basePc + accOffset) % 12 + 12) % 12;
    return {
      letter,
      accOffset,
      pitchClass,
      canonical: `${letter}${acc}`,
      display: this._noteDisplay({ letter, accOffset }),
    };
  }

  _accidentalDisplayFromOffset(off) {
    if (off === 2) return "𝄪";
    if (off === 1) return "♯";
    if (off === -1) return "♭";
    if (off === -2) return "𝄫";
    return "";
  }

  _spelledIntervalTarget(prevNote, intervalAbbr, dir) {
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

    const accText =
      off === 2 ? "##" :
      off === 1 ? "#" :
      off === -1 ? "b" :
      off === -2 ? "bb" :
      "";

    return {
      letter: targetLetter,
      accOffset: off,
      pitchClass: targetPc,
      canonical: `${targetLetter}${accText}`,
      display: this._noteDisplay({ letter: targetLetter, accOffset: off }),
    };
  }

  _awardPointsForCorrect() {
    if (this._isPracticeMode()) {
      this.$points.text("0");
      return 0;
    }
    if (this._usedHintThisRound) return 0;
    const base = Number.isFinite(Number(this.opts.basePoints)) ? Number(this.opts.basePoints) : 1;
    const bonus = Number.isFinite(Number(this.opts.firstTryBonus)) ? Number(this.opts.firstTryBonus) : 2;
    const earned = this._madeMistakeThisRound ? base : (base + bonus);
    this._points += Math.max(0, earned);
    this.$points.text(String(this._points));
    return earned;
  }

  _showSuccessAnimation() {
    this.$feedback.find(".message span").text("Great job");
    this.$feedback.stop(true, true).fadeIn("fast");
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

  _failAnimation() {
    this.$checkWrap?.removeClass?.("animate__animated animate__shakeX");
    this._showCheckWrongState();
  }

  _updateProgressBar() {
    if (this._isPracticeMode()) {
      this.$progressBar.data("progress", 0);
      this.$progressBar.css({ width: "0%" });
      this.$progressCounter.text("Practice");
      return 0;
    }
    const steps = Math.max(1, Number(this.opts.numOfChallenges) || 1);
    const increment = 100 / steps;
    let current = parseFloat(this.$progressBar.data("progress")) || 0;
    current = Math.min(100, current + increment);
    this.$progressBar.data("progress", current);
    this.$progressBar.css({ width: `${current}%` });
    const completed = Math.min(steps, Math.round(current / increment));
    this.$progressCounter.text(`${completed} of ${steps}`);
    return current;
  }

  _showFinalResults() {
    if (this._isPracticeMode()) return;
    this._stopGameTimer();
    this._stats.finishedAtMs = Date.now();
    const records = this._roundRecords.filter(Boolean);
    let answersCorrect = 0;
    let answersWrong = 0;
    records.forEach((r) => {
      const c = Number.isFinite(r.firstCorrect) ? r.firstCorrect : Number(r.finalCorrect || 0);
      const w = Number.isFinite(r.firstWrong) ? r.firstWrong : Number(r.finalWrong || 0);
      answersCorrect += Math.max(0, c);
      answersWrong += Math.max(0, w);
    });
    const accuracyBase = answersCorrect + answersWrong;
    const accuracy = accuracyBase ? Math.round((answersCorrect / accuracyBase) * 100) : 0;
    const durationSec = Math.max(0, Math.floor((this._stats.finishedAtMs - this._finalStartMs) / 1000));
    const perfectGame = records.length > 0 && !this._madeAnyMistake;
    const scoreSummary = BaseStaffGame.prototype._buildFinalScoreSummary.call(this, {
      basePoints: this._points,
      accuracy,
      perfectGame,
    });

    if (perfectGame) {
      const tid = setTimeout(() => {
        this.$doublePoints?.show?.();
        this._playPerfectGameBonusSfx();
      }, 1750);
      this._revealTimeouts.push(tid);
    } else {
      this.$doublePoints?.hide?.();
    }

    if (scoreSummary.settingsBonus) {
      // eslint-disable-next-line no-console
      console.log("[ToneTrek] Extra doubling is happening because the user selected challenging settings.", {
        scoreBeforeSettingsBonus: scoreSummary.initialScore,
        finalPoints: scoreSummary.finalScore,
      });
    }

    this.$musicKeyboard.hide();
    this.$continueWrap.hide();
    this.$helpBtn.hide();
    this.$checkWrap.hide();
    renderFinalResultsOverlay({
      $finalOverlay: this.$finalOverlay,
      rounds: this.opts.numOfChallenges,
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

  isSoundEnabled() {
    return this._isSoundEnabled();
  }

  _ensureUiSfxAudio() {
    return BaseStaffGame.prototype._ensureUiSfxAudio.call(this);
  }

  _armUiSfxOnFirstGesture() {
    return BaseStaffGame.prototype._armUiSfxOnFirstGesture.call(this);
  }

  _playSuccessSfxBasic() {
    return BaseStaffGame.prototype._playSuccessSfxBasic.call(this);
  }

  _playSuccessSfxBonus() {
    return BaseStaffGame.prototype._playSuccessSfxBonus.call(this);
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

  _playFinalSfx() {
    return BaseStaffGame.prototype._playFinalSfx.call(this);
  }

  _playPerfectGameBonusSfx() {
    return BaseStaffGame.prototype._playPerfectGameBonusSfx.call(this);
  }

  _playFinalMetricPopSfx(index) {
    return BaseStaffGame.prototype._playFinalMetricPopSfx.call(this, index);
  }

  _playHingeSfx() {
    if (!this._isSoundEnabled() || !window.Tone) return;
    this._ensureUiSfxAudio().then(() => {
      const now = Tone.now();
      if (this._uiSfxNoise) {
        this._uiSfxNoise.triggerAttackRelease(0.04, now, GameAudio.scale("hinge", 0.07));
      }
      const synth = this._uiTimerSfxSynth || this._uiSfxSynth;
      if (!synth) return;
      synth.triggerAttackRelease("E4", 0.04, now, GameAudio.scale("hinge", 0.12));
      synth.triggerAttackRelease("C4", 0.05, now + 0.04, GameAudio.scale("hinge", 0.16));
    });
  }

  _clearFinalMetricsSfxTimers() {
    return BaseStaffGame.prototype._clearFinalMetricsSfxTimers.call(this);
  }

  _animateFinalMetricsWithSfx() {
    return BaseStaffGame.prototype._animateFinalMetricsWithSfx.call(this);
  }

  _clearFinalCountupTimers() {
    if (!Array.isArray(this._finalCountupTimeouts)) {
      this._finalCountupTimeouts = [];
      return;
    }
    this._finalCountupTimeouts.forEach((id) => clearTimeout(id));
    this._finalCountupTimeouts = [];
  }

  _isPracticeMode() {
    return this._normalizeOnOff(this.opts.practiceMode);
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

  _isTimerEnabled() {
    return this._normalizeOnOff(this.opts.timer);
  }

  _formatTimerDisplay(sec) {
    const safe = Math.max(0, Math.floor(Number(sec) || 0));
    const mm = String(Math.floor(safe / 60)).padStart(2, "0");
    const ss = String(safe % 60).padStart(2, "0");
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
    const $blocksWrapper = $("#blocks-wrapper");
    this.$table.find("td.block, td.interval-block, td.initial-block").css("pointer-events", on ? "none" : "");
    $blocksWrapper.attr("disabled", on ? "disabled" : null);
    $blocksWrapper.attr("aria-disabled", on ? "true" : "false");
    $blocksWrapper.css("pointer-events", on ? "none" : "");
    if (on) {
      this.$table.find('input[name="note"]').prop("disabled", true);
      this._hideMusicKeyboard();
      this._clearActiveBlockInput();
    } else {
      this._updateNoteInputProgression();
    }
  }

  _hideTimeUpMessage() {
    if (!this.$timeupMessage?.length) return;
    this.$timeupMessage.hide().removeClass("animate__animated animate__flash");
  }

  _showTimeUpMessage() {
    this._setInstructions("Time’s up. Let’s try another path.");
    if (!this.$timeupMessage?.length) return;
    this.$timeupMessage.show();
    this.$timeupMessage.removeClass("animate__animated animate__flash");
    // eslint-disable-next-line no-unused-expressions
    this.$timeupMessage[0] && this.$timeupMessage[0].offsetWidth;
    this.$timeupMessage.addClass("animate__animated animate__flash");
  }

  _wouldReachLastRoundAfterAdvance() {
    return !this._isPracticeMode() && this._currentRound >= this.opts.numOfChallenges;
  }

  _finishRoundAsTimedOut() {
    this._madeAnyMistake = true;
    this._madeMistakeThisRound = true;
    this._clearCorrectStreak();
    this._pauseGameTimer();
    this._hideSkipRoundButton();
    this.$helpBtn.hide();
    this.$checkWrap.hide();

    if (this._wouldReachLastRoundAfterAdvance()) {
      this._stats.finishedAtMs = Date.now();
      const tid = queueFinalResultsReveal({
        showFinalResults: () => this._showFinalResults(),
        delayMs: 600,
      });
      this._revealTimeouts.push(tid);
      return;
    }

    this._setTimedOutInteractivityDisabled(false);
    this._hideTimeUpMessage();

    this._currentRound += 1;
    this._startRound();
  }

  _pulseTimerWarning() {
    if (!this.$timer?.length) return;
    this.$timer.removeClass("animate__animated animate__pulse");
    // eslint-disable-next-line no-unused-expressions
    this.$timer[0] && this.$timer[0].offsetWidth;
    this.$timer.addClass("animate__animated animate__pulse");
  }

  _playTimerWarningBeep() {
    return BaseStaffGame.prototype._playTimerWarningBeep.call(this);
  }

  _playTimerTimeUpSfx() {
    return BaseStaffGame.prototype._playTimerTimeUpSfx.call(this);
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
      this.$checkWrap.hide();
      this.$helpBtn.hide();
      this._setTimedOutInteractivityDisabled(true);
      this._pulseTimerWarning();
      this._playTimerTimeUpSfx();
      this._showTimeUpMessage();
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
    const raw = Number(this.opts.timeLimit != null ? this.opts.timeLimit : this.opts.timerLimit);
    if (!Number.isFinite(raw)) return 10;
    return Math.max(0, Math.floor(raw));
  }

  _resetRoundTimerIfEnabled() {
    if (!this._isTimerEnabled()) return;
    this.$timer.show();
    this._startGameTimer(this._timerLimitSeconds());
  }

  _showSkipRoundButton() {
    if (!this.$skipWrap?.length) return;
    this.$skipWrap.stop(true, true).show();
  }

  _hideSkipRoundButton() {
    if (!this.$skipWrap?.length) return;
    this.$skipWrap.stop(true, true).hide();
  }

  _wireNoteInputUx() {
    this.$table
      .off(`pointerdown.${this.ns}Cover mousedown.${this.ns}Cover touchstart.${this.ns}Cover click.${this.ns}Cover`, ".block-cover")
      .on(`pointerdown.${this.ns}Cover mousedown.${this.ns}Cover touchstart.${this.ns}Cover click.${this.ns}Cover`, ".block-cover", (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
      });

    this.$table
      .off(`blur.${this.ns}Note`, 'input[name="note"]')
      .on(`blur.${this.ns}Note`, 'input[name="note"]', (e) => {
        const $input = $(e.currentTarget);
        if (!$input.closest("td.block").length) return;
        this._updateNoteInputProgression();
      });

    this.$table
      .off(`input.${this.ns}Note`, 'input[name="note"]')
      .on(`input.${this.ns}Note`, 'input[name="note"]', (e) => {
        const $input = $(e.currentTarget);
        if (!$input.closest("td.block").length) return;
        const prevVal = String($input.data("prev-value") || "");
        const curVal = String($input.val() || "");
        const becameFilled = prevVal.trim().length === 0 && curVal.trim().length > 0;
        if (becameFilled) {
          this._revealNextCovers(2);
        }
        $input.data("prev-value", curVal);
        this._updateNoteInputProgression();
      });

    this._musicKeyboard.bind({
      $root: this.$table,
      focusSelector: 'input[name="note"]',
      clickSelector: "td.block",
      resolveInputFromClick: ($cell) => $cell.find('input[name="note"]').first(),
      getActiveInput: () => this.$activeBlockInput,
      setActiveInput: ($input) => this._setActiveBlockInput($input),
      clearActiveInput: () => this._clearActiveBlockInput(),
      isManagedInput: ($input) => $input.is('input[name="note"]') && $input.closest("td.block").length > 0,
      isInputDisabled: ($input) => $input.prop("disabled"),
      transformButtonPress: ({ action, currentValue }) => {
        if (action.isDelete) {
          const nextValue = deleteSingleNoteValue(currentValue);
          return nextValue === currentValue ? null : nextValue;
        }

        if (action.letter) {
          return keyboardLabelForLetter({
            letter: action.letter,
            solfege: action.solfege,
            useSolfege: this._isSolfegeEnabled(),
            letterToSolfege: ToneTrek.LETTER_TO_SOLFEGE,
          });
        }

        if (action.accidentalType) {
          return this._applyAccidentalToInputValue(currentValue, action.accidentalType);
        }

        return action.text || null;
      },
    });
  }

  _applyAccidentalToInputValue(current, accidentalType) {
    return applyAccidentalToSingleNoteValue(current, accidentalType, {
      useSolfege: this._isSolfegeEnabled(),
      solfegeToLetter: ToneTrek.SOLFEGE_TO_LETTER,
      letterToSolfege: ToneTrek.LETTER_TO_SOLFEGE,
    });
  }

  _updateNoteInputProgression() {
    const $blocks = this.$table.find("td.block").toArray().sort((a, b) => {
      const ia = parseInt(a.getAttribute("data-path-index"), 10);
      const ib = parseInt(b.getAttribute("data-path-index"), 10);
      return (Number.isFinite(ia) ? ia : 9999) - (Number.isFinite(ib) ? ib : 9999);
    });

    if (this._correctionMode) {
      let allFilledCorrection = $blocks.length > 0;
      for (let i = 0; i < $blocks.length; i += 1) {
        const $cell = $($blocks[i]);
        const idx = parseInt($cell.attr("data-path-index"), 10);
        const $input = $cell.find('input[name="note"]').first();
        const $label = $cell.children("div").children("span").not(".block-arrow").first();
        if (!$input.length) continue;

        const value = String($input.val() || "").trim();
        if (!value) allFilledCorrection = false;

        const editable = this._wrongEditableIndexes.has(idx);
        $input.prop("disabled", !editable);
        if (editable) {
          if (value) $label.hide();
          else {
            $label.css("opacity", "0.2");
            $label.show();
          }
        } else {
          $label.hide();
        }
      }

      if (this.$activeBlockInput && this.$activeBlockInput.length && this.$activeBlockInput.prop("disabled")) {
        this._clearActiveBlockInput();
        this._hideMusicKeyboard();
      }

      this._syncActiveBlockLabelOpacity();
      this._syncBlocksCompletionUi(allFilledCorrection);
      return;
    }

    let gateOpen = true;
    let allFilled = $blocks.length > 0;
    for (let i = 0; i < $blocks.length; i += 1) {
      const $cell = $($blocks[i]);
      const $input = $cell.find('input[name="note"]').first();
      const $label = $cell.children("div").children("span").not(".block-arrow").first();
      if (!$input.length) continue;

      const value = String($input.val() || "").trim();
      if (!value) allFilled = false;

      if (gateOpen) {
        $input.prop("disabled", false);
        if (value) {
          $label.hide();
        } else {
          $label.css("opacity", "0.2");
          $label.show();
          gateOpen = false;
        }
      } else {
        $input.prop("disabled", true);
        $label.hide();
      }
    }

    if (this.$activeBlockInput && this.$activeBlockInput.length && this.$activeBlockInput.prop("disabled")) {
      this._clearActiveBlockInput();
    }

    this._syncActiveBlockLabelOpacity();
    this._syncBlocksCompletionUi(allFilled);
  }

  _syncBlocksCompletionUi(allFilled) {
    if (this._roundLocked) return;
    this.$checkWrap
      .toggle(!!allFilled)
      .toggleClass("invisible", !allFilled);
    this._setInstructions(
      allFilled
        ? "When you’re ready, check your answer."
        : "Tap an interval to hear it, then add the next note.",
    );
  }

  _setInstructions(message) {
    this.instructionsUi.show().setHtml(message, { animate: false });
  }

  _setActiveBlockInput($input) {
    if (!$input || !$input.length) return;
    const $cell = $input.closest("td.block");
    if (!$cell.length) return;

    this.$activeBlockInput = $input;
    this.$activeBlockCell = $cell;

    this.$table.find("td.block").removeClass("active-editable").css({
      boxShadow: "",
      borderColor: "",
      zIndex: "",
    });

    $cell.addClass("active-editable").css({
      boxShadow: "0 0 0 3px rgba(13, 110, 253, 0.55) inset, 0 0 0 2px rgba(13, 110, 253, 0.75)",
      borderColor: "#0d6efd",
      zIndex: "2",
    });

    this._syncActiveBlockLabelOpacity();
  }

  _clearActiveBlockInput() {
    this.$activeBlockInput = null;
    this.$activeBlockCell = null;
    this.$table.find("td.block").removeClass("active-editable").css({
      boxShadow: "",
      borderColor: "",
      zIndex: "",
    });
    this._syncActiveBlockLabelOpacity();
  }

  _syncActiveBlockLabelOpacity() {
    const $labels = this.$table.find('td.block > div > span').not(".block-arrow");
    $labels.css("opacity", "0.2");

    if (!this.$activeBlockCell || !this.$activeBlockCell.length) return;
    const $activeLabel = this.$activeBlockCell.children("div").children("span").not(".block-arrow").first();
    if (!$activeLabel.length || !$activeLabel.is(":visible")) return;
    $activeLabel.css("opacity", "0.05");
  }

  _isSoundEnabled() {
    const v = this.opts.sound;
    return v === true || String(v || "").trim().toLowerCase() === "on";
  }

  async _ensureAudio() {
    if (!this._isSoundEnabled()) return;
    if (this._audioReady) return;
    if (!window.Tone) return;
    try {
      await Tone.start();
    } catch (_) {
      return;
    }

    this._synth = GameAudio.createSequenceSynth();
    this._audioReady = true;
  }

  _playMidi(midi, durSeconds, atSecondsFromNow) {
    if (!window.Tone) return;
    this._ensureAudio().then(() => {
      if (!this._synth) return;
      const now = Tone.now();
      const when = now + (Number(atSecondsFromNow) || 0);
      const dur = Number(durSeconds) || 0.6;
      this._synth.triggerAttackRelease(Tone.Frequency(midi, "midi"), dur, when, GameAudio.scale("sequence", 1));
    });
  }

  _playDictationLike(firstMidi, secondMidi) {
    if (!this._isSoundEnabled()) return;
    this._playMidi(firstMidi, 0.6, 0.0);
    this._playMidi(secondMidi, 0.6, 1.0);
    this._playMidi(firstMidi, 0.6, 2.0);
    this._playMidi(firstMidi, 0.6, 3.0);
    this._playMidi(secondMidi, 0.6, 3.0);
  }

  _midiFromCell($cell) {
    if (!$cell || !$cell.length) return null;

    const note = this._noteFromCell($cell);
    if (!note) return null;
    // Keep playback in a stable middle register, matching previous behavior.
    return 60 + Number(note.pitchClass || 0);
  }

  _parseIntervalAbbr(abbr) {
    const s = String(abbr || "").trim();
    const m = s.match(/^([PMAmd]+)(\d+)$/);
    if (!m) return null;
    return { quality: m[1], number: parseInt(m[2], 10) };
  }

  _intervalSemitones(quality, simpleNum) {
    const baseMajorPerfect = { 1: 0, 2: 2, 3: 4, 4: 5, 5: 7, 6: 9, 7: 11, 8: 12 }[simpleNum];
    if (baseMajorPerfect == null) return null;

    const isPerfectClass = simpleNum === 1 || simpleNum === 4 || simpleNum === 5 || simpleNum === 8;
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

  _intervalToSemitones(abbr) {
    const parsed = this._parseIntervalAbbr(abbr);
    if (!parsed || !Number.isFinite(parsed.number) || parsed.number < 1) return null;
    const simpleNum = ((parsed.number - 1) % 7) + 1;
    const octaves = Math.floor((parsed.number - 1) / 7);
    const base = this._intervalSemitones(parsed.quality, simpleNum);
    if (base == null) return null;
    return base + 12 * octaves;
  }

  _shakeCell($cell) {
    if (!$cell || !$cell.length) return;
    $cell.removeClass("animate__animated animate__shakeX");
    // eslint-disable-next-line no-unused-expressions
    $cell[0] && $cell[0].offsetWidth;
    $cell.addClass("animate__animated animate__shakeX");
    $cell
      .off(`animationend.${this.ns}Shake webkitAnimationEnd.${this.ns}Shake`)
      .one(`animationend.${this.ns}Shake webkitAnimationEnd.${this.ns}Shake`, () => {
        $cell.removeClass("animate__animated animate__shakeX");
      });
  }

  _clearRevealTimers() {
    if (!Array.isArray(this._revealTimeouts)) {
      this._revealTimeouts = [];
      return;
    }
    this._revealTimeouts.forEach((id) => clearTimeout(id));
    this._revealTimeouts = [];
  }

  _revealPathCells(items, onDone = null) {
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      if (typeof onDone === "function") onDone();
      return;
    }

    for (let i = 0; i < list.length; i += 1) {
      const item = list[i];
      const tid = setTimeout(() => {
        const $cell = this._cellAt(item.r, item.c);
        $cell.attr("data-path-index", String(item.i));
        $cell.addClass(item.cls).append(item.html);
        if (item.i >= 2) {
          $cell.append('<div class="block-cover"></div>');
        }
        if (item.cls === "block") {
          $cell.find('input[name="note"]').prop("disabled", true).prop("readonly", true).attr({
            inputmode: "none",
            autocomplete: "off",
            autocorrect: "off",
            autocapitalize: "off",
            spellcheck: "false",
          });
          $cell.children("div").children("span").not(".block-arrow").first().hide();
        }
        this._showCellVisual($cell);
        this._updateNoteInputProgression();
      }, i * 80);
      this._revealTimeouts.push(tid);
    }

    const coversTid = setTimeout(() => {
      this._revealNextCovers(1);
    }, list.length * 80);
    this._revealTimeouts.push(coversTid);

    if (typeof onDone === "function") {
      const doneTid = setTimeout(() => onDone(), list.length * 80);
      this._revealTimeouts.push(doneTid);
    }
  }

  _revealNextCovers(count = 1) {
    const maxCount = Math.max(0, Number(count) || 0);
    if (!maxCount) return;

    const $covers = this.$table
      .find("td[data-path-index]")
      .toArray()
      .sort((a, b) => {
        const ia = parseInt(a.getAttribute("data-path-index"), 10);
        const ib = parseInt(b.getAttribute("data-path-index"), 10);
        return (Number.isFinite(ia) ? ia : 9999) - (Number.isFinite(ib) ? ib : 9999);
      })
      .map((td) => $(td).children(".block-cover").first())
      .filter(($c) => $c && $c.length && !$c.hasClass("cover-revealing") && !$c.hasClass("cover-revealed"))
      .slice(0, maxCount);

    $covers.forEach(($cover, i) => {
      const tid = setTimeout(() => {
        this._playHingeSfx();
        $cover.addClass("cover-revealing block-falling");
        $cover
          .removeClass("animate__animated animate__hinge")
          .addClass("animate__animated animate__hinge")
          .one(`animationend.${this.ns}CoverHinge webkitAnimationEnd.${this.ns}CoverHinge`, () => {
            $cover.hide();
            $cover.removeClass("animate__animated animate__hinge cover-revealing");
            $cover.addClass("cover-revealed");
          });

        // Fallback in case animationend does not fire.
        const fallbackTid = setTimeout(() => {
          $cover.hide();
          $cover.removeClass("animate__animated animate__hinge cover-revealing");
          $cover.addClass("cover-revealed");
        }, 2200);
        this._revealTimeouts.push(fallbackTid);
      }, i * 500);
      this._revealTimeouts.push(tid);
    });
  }

  _showStandardGameUi() {
    this._syncKeyboardLabels();
    this.instructionsUi.show().replay();
    $("#controls").show();
  }

  _showMusicKeyboard() {
    this._musicKeyboard.show();
  }

  _hideMusicKeyboard() {
    this._musicKeyboard.hide();
  }

  _hideAllCellsVisual() {
    this.$table.find("td").css({
      visibility: "visible",
      borderColor: "transparent",
      backgroundColor: "transparent",
      color: "transparent",
    });
  }

  _showCellVisual($cell) {
    if (!$cell || !$cell.length) return;
    $cell.css({
      borderColor: "",
      backgroundColor: "",
      color: "",
    });
  }
}
