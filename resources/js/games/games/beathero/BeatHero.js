import {
  renderFinalResultsOverlay,
  queueFinalResultsReveal,
} from "../shared/finalResults.js";
import { GameAudio } from "../shared/GameAudio.js";
import { GameCountdown } from "../shared/GameCountdown.js";
import { beatMsForBpm, normalizeMetronomeBpm } from "../shared/tempo.js";
import { rhythmNotationSvg } from "./rhythmNotation.js";
import { buildBeatHeroSequence } from "./beatHeroSequence.js";

export class BeatHero {
  static MIN_CHALLENGES = 2;
  static MAX_CHALLENGES = 12;
  static DEFAULT_FIGURE_IDS = [
    "quarter",
    "two-eighths",
    "four-sixteenths",
    "eighth-two-sixteenths",
  ];

  static FIGURES = [
    {
      id: "half",
      label: "Half note",
      beats: 2,
      events: [0],
      notes: [{ value: 2 }],
    },
    {
      id: "quarter",
      label: "Quarter note",
      beats: 1,
      events: [0],
      notes: [{ value: 4 }],
    },
    {
      id: "two-eighths",
      label: "Two eighth notes",
      beats: 1,
      events: [0, 0.5],
      notes: [{ value: 8 }, { value: 8 }],
    },
    {
      id: "triplets",
      label: "Eighth-note triplets",
      beats: 1,
      events: [0, 1 / 3, 2 / 3],
      notes: [{ value: 8 }, { value: 8 }, { value: 8 }],
      tuplet: { num_notes: 3, notes_occupied: 2 },
    },
    {
      id: "eighth-two-sixteenths",
      label: "Eighth note, two sixteenth notes",
      beats: 1,
      events: [0, 0.5, 0.75],
      notes: [{ value: 8 }, { value: 16 }, { value: 16 }],
    },
    {
      id: "sixteenth-eighth-sixteenth",
      label: "Sixteenth note, eighth note, sixteenth note",
      beats: 1,
      events: [0, 0.25, 0.75],
      notes: [{ value: 16 }, { value: 8 }, { value: 16 }],
    },
    {
      id: "two-sixteenths-eighth",
      label: "Two sixteenth notes, eighth note",
      beats: 1,
      events: [0, 0.25, 0.5],
      notes: [{ value: 16 }, { value: 16 }, { value: 8 }],
    },
    {
      id: "four-sixteenths",
      label: "Four sixteenth notes",
      beats: 1,
      events: [0, 0.25, 0.5, 0.75],
      notes: [{ value: 16 }, { value: 16 }, { value: 16 }, { value: 16 }],
    },
    {
      id: "dotted-eighth-sixteenth",
      label: "Dotted eighth note, sixteenth note",
      beats: 1,
      events: [0, 0.75],
      notes: [{ value: 8, dotted: true }, { value: 16 }],
    },
    {
      id: "sixteenth-dotted-eighth",
      label: "Sixteenth note, dotted eighth note",
      beats: 1,
      events: [0, 0.25],
      notes: [{ value: 16 }, { value: 8, dotted: true }],
    },
  ];

  constructor(options = {}) {
    this.opts = {
      wrapperSelector: "#rhythm-card-grid",
      dotsSelector: "#sequence-dots",
      statusSelector: "#sequence-status",
      numOfChallenges: 4,
      numOfCards: 2,
      bpm: 80,
      practiceMode: false,
      figures: BeatHero.DEFAULT_FIGURE_IDS,
      sound: true,
      ...options,
    };

    this.opts.numOfChallenges = this._normalizeChallengeCount(this.opts.numOfChallenges);
    this.opts.numOfCards = this._normalizeCardCount(this.opts.numOfCards);
    this.opts.bpm = this._normalizeBpm(this.opts.bpm);
    this.opts.practiceMode = this._normalizeBool(this.opts.practiceMode);
    this.opts.figures = this._normalizeFigureIds(this.opts.figures);
    this.opts.sound = this._normalizeBool(this.opts.sound);

    this.$grid = $(this.opts.wrapperSelector);
    this.$dots = $(this.opts.dotsSelector);
    this.$status = $(this.opts.statusSelector);
    this.$playWrap = $("#play");
    this.$playBtn = this.$playWrap.find('button[action="play"]');
    this.$stopBtn = this.$playWrap.find('button[action="stop"]');
    this.$continueWrap = $("#continue");
    this.$continueBtn = this.$continueWrap.find("button");
    this.$progressBar = $("#progress-bar");
    this.$progressCounter = $("#progress-counter");
    this.$points = $("#points");
    this.$increment = $("#increment");
    this.$finalOverlay = $("#final-overlay");
    this._countdown = new GameCountdown({
      valueElement: this.$stopBtn.get(0),
    });
    this._playbackRun = 0;

    this._cards = [];
    this._answer = [];
    this._previousAnswerIds = [];
    this._selection = [];
    this._round = 1;
    this._roundHadMistake = false;
    this._madeAnyMistake = false;
    this._state = "ready";
    this._inputLocked = false;
    this._pointsValue = 0;
    this._correctTaps = 0;
    this._wrongTaps = 0;
    this._startedAt = Date.now();
    this._timers = new Set();
    this._auditionTimers = new Set();
    this._auditionRun = 0;
    this._finalResultsTimer = null;
    this._rhythmSynth = null;
    this._uiSynth = null;
    this._uiNoise = null;
    this._audioReady = false;
  }

  start() {
    this._wireFigurePicker();
    if (!this.$grid.length) return;

    this._wireControls();
    this._resetGameUi();
    this._startRound();
  }

  _wireControls() {
    this.$playBtn
      .off("click.beatHero")
      .on("click.beatHero", (event) => {
        event.preventDefault();
        this._playChallenge();
      });

    this.$stopBtn
      .off("click.beatHero")
      .on("click.beatHero", (event) => {
        event.preventDefault();
        this._stopChallenge();
      });

    this.$grid
      .off("click.beatHero", ".rhythm-card")
      .on("click.beatHero", ".rhythm-card", (event) => {
        event.preventDefault();
        this._handleCardTap(event.currentTarget);
      });

    this.$continueBtn
      .off("click.beatHero")
      .on("click.beatHero", (event) => {
        event.preventDefault();
        this._continue();
      });
  }

  _wireFigurePicker() {
    const picker = document.querySelector("[data-beat-hero-symbol-picker]");
    if (!picker) return;

    const checkboxes = [...picker.querySelectorAll(".beat-hero-symbol-input")];
    const message = document.querySelector("[data-beat-hero-symbol-message]");
    const form = picker.closest("form");

    picker.querySelectorAll("[data-beat-hero-figure-thumbnail]").forEach((thumbnail) => {
      const figure = BeatHero.FIGURES.find((item) => item.id === thumbnail.dataset.beatHeroFigureThumbnail);
      if (figure) thumbnail.innerHTML = this._figureSvg(figure);
    });

    const selectedCount = () => checkboxes.filter((checkbox) => checkbox.checked).length;
    const showCount = (error = "") => {
      if (!message) return;
      message.classList.toggle("is-error", Boolean(error));
      message.textContent = error || `${selectedCount()} selected`;
    };

    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", () => {
        const count = selectedCount();

        if (count < 2) {
          checkbox.checked = true;
          showCount("Choose at least 2 symbols.");
          return;
        }

        showCount();
      });
    });

    form?.addEventListener("submit", (event) => {
      const count = selectedCount();
      if (count >= 2) return;

      event.preventDefault();
      showCount("Choose at least 2 symbols.");
    });

    showCount();
  }

  _resetGameUi() {
    this._round = 1;
    this._pointsValue = 0;
    this._correctTaps = 0;
    this._wrongTaps = 0;
    this._madeAnyMistake = false;
    this._previousAnswerIds = [];
    this._startedAt = Date.now();

    $("#timer").hide();
    $("#feedback-success").hide();
    $("#check, #help, #skip").hide();
    $("#controls").show();
    this.$finalOverlay.hide();
    this.$continueWrap.hide();
    this.$points.text("0");
    this.$increment.css("opacity", 0);
    this.$progressBar.data("progress", 0).css("width", "0%");
    this.$progressCounter.text(this.opts.practiceMode ? "Practice" : `0 of ${this.opts.numOfChallenges}`);
    this._setPlayButtons(false);
  }

  _startRound() {
    this._cancelTimers();
    this._state = "ready";
    this._inputLocked = false;
    this._selection = [];
    this._roundHadMistake = false;
    this.$continueWrap.hide();

    const pool = BeatHero.FIGURES.filter((figure) => this.opts.figures.includes(figure.id));
    this._cards = this._shuffle(pool);
    this._answer = buildBeatHeroSequence({
      pool,
      count: this.opts.numOfCards,
      shuffle: (figures) => this._shuffle(figures),
      previousIds: this._previousAnswerIds,
    });
    this._previousAnswerIds = this._answer.map((figure) => figure.id);

    this._renderCards();
    this.$dots
      .attr("aria-label", `${this.opts.numOfCards}-card sequence progress`)
      .html(Array.from({ length: this.opts.numOfCards }, (_, index) => `
        <span class="sequence-dot" aria-hidden="true">${this._dotNumberMarkup(index)}</span>
      `).join(""));
    this._resetDots();
    this._setStatus(this._readyInstructions());
    this._setPlayButtons(false);
  }

  _renderCards() {
    const html = this._cards.map((figure, index) => `
      <button
        type="button"
        class="rhythm-card"
        data-figure-id="${figure.id}"
        aria-label="Card ${index + 1}: ${figure.label}${figure.beats > 1 ? ` (${figure.beats} cards)` : ""}"
      >
        <span class="rhythm-card__number" aria-hidden="true"></span>
        <span class="rhythm-card__figure" aria-hidden="true">
          ${this._figureSvg(figure)}
        </span>
      </button>
    `).join("");

    this.$grid
      .attr("data-card-count", this._cards.length)
      .html(html);
  }

  _figureSvg(figure) {
    return rhythmNotationSvg(figure);
  }

  async _playChallenge() {
    if (this._state === "playing" || this._state === "complete") return;

    this._cancelTimers();
    this._clearSelectionMarks();
    this._selection = [];
    this._state = "playing";
    this._inputLocked = true;
    this._resetDots();
    this._setStatus("Get ready…");
    this._setPlayButtons(true);

    const playbackRun = this._playbackRun;
    await this._ensureAudio();
    if (this._state !== "playing" || playbackRun !== this._playbackRun) return;

    const beatMs = this._beatMs();
    const countInMs = this._countdown.start({ beatMs });
    this._setTimer(() => {
      this._setStatus("Listen carefully…");
    }, countInMs);

    let endsAt = countInMs;
    this._answer.forEach((figure) => {
      const startsAt = endsAt;
      endsAt += figure.beats * beatMs;
      this._scheduleFigureAudio(figure, startsAt);
    });
    for (let index = 0; index < this.opts.numOfCards; index += 1) {
      this._setTimer(() => this._activateDot(index), countInMs + index * beatMs);
      this._setTimer(() => this._completeDot(index), countInMs + (index + 1) * beatMs);
    }

    this._setTimer(() => {
      this._state = "answering";
      this._inputLocked = false;
      this._setPlayButtons(false);
      this._resetDots();
      this._setStatus(`Fill the ${this.opts.numOfCards} cards in order.${this._halfNoteHint()}`);
    }, endsAt + 120);
  }

  _stopChallenge() {
    if (this._state !== "playing") return;

    this._cancelTimers();
    this._state = "ready";
    this._inputLocked = false;
    this._resetDots();
    this._setPlayButtons(false);
    this._setStatus("Playback stopped. Press Play when you’re ready.");
  }

  async _handleCardTap(cardElement) {
    if (this._inputLocked || this._state === "playing" || this._state === "complete") return;

    const figure = this._cards.find((item) => item.id === cardElement.dataset.figureId);
    if (!figure) return;

    const auditionRun = this._cancelCardAudition();

    if (this._state === "ready") {
      await this._ensureAudio();
      if (this._state !== "ready" || auditionRun !== this._auditionRun) return;

      this._scheduleFigureAudio(figure, 0, cardElement);
      cardElement.classList.add("is-previewing");
      this._setStatus(this._readyInstructions());
      this._setAuditionTimer(() => {
        cardElement.classList.remove("is-previewing");
      }, figure.beats * this._beatMs());
      return;
    }

    if (this._state !== "answering") return;

    this._scheduleFigureAudio(figure, 0, cardElement);
    const answerIndex = this._selection.reduce((total, item) => total + item.beats, 0);
    const expected = this._answer[this._selection.length];
    this._activateDot(answerIndex);

    if (expected && expected.id === figure.id) {
      this._correctTaps += 1;
      this._selection.push(figure);
      cardElement.classList.remove("is-wrong");
      cardElement.classList.add("is-correct");
      const badge = cardElement.querySelector(".rhythm-card__number");
      const cardNumbers = figure.beats > 1 ? `${answerIndex + 1}–${answerIndex + figure.beats}` : answerIndex + 1;
      badge.textContent = [badge.textContent, cardNumbers].filter(Boolean).join(", ");
      this._chooseDot(answerIndex, figure);

      if (this._selection.length === this._answer.length) {
        this._finishRound();
        return;
      }

      this._setStatus(`Great — now choose card ${answerIndex + figure.beats + 1} of ${this.opts.numOfCards}.`);
      return;
    }

    this._inputLocked = true;
    this._wrongTaps += 1;
    this._roundHadMistake = true;
    this._madeAnyMistake = true;
    cardElement.classList.add("is-wrong");
    this._wrongDot(answerIndex);
    this._setStatus("Not quite. Start again with the first card.");
    this._playFailSound();

    const resetDelay = Math.min(800, Math.max(600, Math.round(this._beatMs() * 0.75)));
    this._setTimer(() => {
      this._cancelCardAudition();
      this._selection = [];
      this._clearSelectionMarks();
      this._resetDots();
      this._inputLocked = false;
    }, resetDelay);
  }

  _finishRound() {
    this._state = "complete";
    this._inputLocked = true;
    const earned = this._roundHadMistake ? 1 : 2;
    this._pointsValue += earned;
    this.$points.text(String(this._pointsValue));
    this.$increment.text(`+${earned}`).css("opacity", 1);
    this._setTimer(() => this.$increment.css("opacity", 0), 900);
    this._updateProgress();
    this._setStatus("Perfect order! Ready for another sequence?");
    this._setPlayButtons(false);
    this.$playWrap.hide();
    this.$continueWrap.show();
    const isFinalRound = !this.opts.practiceMode && this._round >= this.opts.numOfChallenges;
    if (isFinalRound) {
      this._finalResultsTimer = queueFinalResultsReveal({
        $button: this.$continueBtn,
        showFinalResults: () => {
          this._finalResultsTimer = null;
          if (this._state === "complete") this._showFinalResults();
        },
      });
    } else {
      this.$continueBtn.removeAttr("state").text("Continue");
    }
    this._playSuccessSound();
  }

  _continue() {
    if (this._state !== "complete") return;

    if (!this.opts.practiceMode && this._round >= this.opts.numOfChallenges) {
      this._showFinalResults();
      return;
    }

    this._round += 1;
    this.$playWrap.show();
    this._startRound();
  }

  _updateProgress() {
    if (this.opts.practiceMode) {
      this.$progressCounter.text("Practice");
      return;
    }

    const completed = Math.min(this._round, this.opts.numOfChallenges);
    const progress = (completed / this.opts.numOfChallenges) * 100;
    this.$progressBar.data("progress", progress).css("width", `${progress}%`);
    this.$progressCounter.text(`${completed} of ${this.opts.numOfChallenges}`);
  }

  _showFinalResults() {
    this._cancelTimers();
    this.$continueWrap.hide();
    this.$playWrap.hide();
    $("#controls").hide();

    const totalTaps = this._correctTaps + this._wrongTaps;
    const accuracy = totalTaps ? Math.round((this._correctTaps / totalTaps) * 100) : 0;
    const durationSec = Math.max(0, Math.floor((Date.now() - this._startedAt) / 1000));

    renderFinalResultsOverlay({
      $finalOverlay: this.$finalOverlay,
      rounds: this.opts.numOfChallenges,
      score: this._pointsValue,
      accuracy,
      durationSec,
      settingsBonus: false,
      playFinalSfx: () => this._playFinalSound(),
    });
  }

  _scheduleFigureAudio(figure, startsAtMs = 0, cardElement = null) {
    const beatMs = this._beatMs();
    const setTimer = cardElement
      ? (callback, delayMs) => this._setAuditionTimer(callback, delayMs)
      : (callback, delayMs) => this._setTimer(callback, delayMs);

    figure.events.forEach((offset) => {
      setTimer(() => {
        this._playRhythmHit();
        if (!cardElement) return;

        cardElement.classList.remove("is-sounding");
        void cardElement.offsetWidth;
        cardElement.classList.add("is-sounding");
        this._setAuditionTimer(() => cardElement.classList.remove("is-sounding"), 120);
      }, startsAtMs + (offset * beatMs));
    });
  }

  _cancelCardAudition() {
    this._auditionRun += 1;
    this._auditionTimers.forEach((timer) => clearTimeout(timer));
    this._auditionTimers.clear();
    this.$grid.find(".rhythm-card").removeClass("is-previewing is-sounding");

    try {
      this._rhythmSynth?.triggerRelease?.(window.Tone?.now?.());
    } catch (_) {}

    return this._auditionRun;
  }

  async _ensureAudio() {
    if (!window.Tone) return;

    try {
      await Tone.start();
      await GameAudio.ensureMetronomeAudio();
      if (!this._rhythmSynth) {
        this._rhythmSynth = new Tone.MembraneSynth({
          pitchDecay: 0.012,
          octaves: 0.8,
          oscillator: { type: "sine" },
          envelope: { attack: 0.001, decay: 0.085, sustain: 0, release: 0.02 },
          volume: -2,
        }).toDestination();
      }
      if (this.opts.sound && !this._audioReady) {
        this._uiSynth = GameAudio.createUiPolySynth();
        this._uiNoise = GameAudio.createUiNoiseSynth();
        this._audioReady = true;
      }
    } catch (_) {
      this._setStatus("Audio could not start. Check your browser sound settings.");
    }
  }

  _playRhythmHit() {
    if (!this._rhythmSynth || !window.Tone) return;

    this._rhythmSynth.triggerAttackRelease("C5", "32n", Tone.now(), 0.95);
  }

  _playSuccessSound() {
    if (!this.opts.sound || !this._uiSynth || !window.Tone) return;

    const now = Tone.now();
    ["C6", "E6", "G6"].forEach((note, index) => {
      this._uiSynth.triggerAttackRelease(note, 0.08, now + (index * 0.055), GameAudio.scale("successBasic", 0.5));
    });
  }

  _playFailSound() {
    if (!this.opts.sound || !this._audioReady || !window.Tone) return;

    const now = Tone.now();
    this._uiNoise?.triggerAttackRelease(0.06, now, GameAudio.scale("failNoise", 0.35));
    this._uiSynth?.triggerAttackRelease("A2", 0.1, now + 0.02, GameAudio.scale("failNote", 0.45));
  }

  _playFinalSound() {
    if (!this.opts.sound || !window.Tone) return;
    GameAudio.playFinalResults();
  }

  _activateDot(index) {
    const dot = this.$dots.find(".sequence-dot").get(index);
    if (!dot) return;

    dot.classList.remove("is-complete", "is-wrong", "is-chosen");
    dot.classList.add("is-active");
  }

  _completeDot(index) {
    const dot = this.$dots.find(".sequence-dot").get(index);
    if (!dot) return;

    dot.classList.remove("is-active");
    dot.classList.add("is-complete");
  }

  _chooseDot(index, figure) {
    if (!figure) return;
    for (let beat = 0; beat < figure.beats; beat += 1) {
      const dot = this.$dots.find(".sequence-dot").get(index + beat);
      if (!dot) continue;
      dot.classList.remove("is-active", "is-wrong");
      dot.classList.add("is-chosen");
      dot.innerHTML = beat === 0
        ? `<span class="sequence-dot__figure">${this._figureSvg(figure)}</span>`
        : `<span class="sequence-dot__hold">—</span>`;
    }
  }

  _wrongDot(index) {
    this._resetDots();
    const dot = this.$dots.find(".sequence-dot").get(index);
    if (!dot) return;

    dot.classList.add("is-wrong");
  }

  _resetDots() {
    this.$dots.find(".sequence-dot")
      .removeClass("is-active is-complete is-chosen is-wrong")
      .each((index, dot) => {
        dot.innerHTML = this._dotNumberMarkup(index);
      });
  }

  _dotNumberMarkup(index) {
    return `<span class="sequence-dot__number">${index + 1}</span>`;
  }

  _clearSelectionMarks() {
    this.$grid.find(".rhythm-card")
      .removeClass("is-correct is-wrong is-previewing is-sounding");
    this.$grid.find(".rhythm-card__number").text("");
  }

  _setPlayButtons(isPlaying) {
    this.$playWrap.show();
    this.$playBtn.toggle(!isPlaying);
    this.$stopBtn.toggle(isPlaying);
  }

  _setStatus(message) {
    this.$status.text(message);
  }

  _readyInstructions() {
    return `Press Play, listen to the ${this.opts.numOfCards} beats, then fill their cards in order. Rhythms can repeat.${this._halfNoteHint()}`;
  }

  _halfNoteHint() {
    return this.opts.figures.includes("half") ? " Tap a half note once to fill two cards; the second card holds the note." : "";
  }

  _beatMs() {
    return beatMsForBpm(this.opts.bpm);
  }

  _setTimer(callback, delayMs) {
    const timer = setTimeout(() => {
      this._timers.delete(timer);
      callback();
    }, Math.max(0, delayMs));
    this._timers.add(timer);
    return timer;
  }

  _setAuditionTimer(callback, delayMs) {
    const timer = setTimeout(() => {
      this._auditionTimers.delete(timer);
      callback();
    }, Math.max(0, delayMs));
    this._auditionTimers.add(timer);
    return timer;
  }

  _cancelTimers() {
    this._playbackRun += 1;
    this._timers.forEach((timer) => clearTimeout(timer));
    this._timers.clear();
    if (this._finalResultsTimer != null) clearTimeout(this._finalResultsTimer);
    this._finalResultsTimer = null;
    this._cancelCardAudition();
    this._countdown.cancel();
  }

  _shuffle(items) {
    const shuffled = [...items];
    for (let index = shuffled.length - 1; index > 0; index -= 1) {
      const other = Math.floor((this._duelRandom?.() ?? Math.random()) * (index + 1));
      [shuffled[index], shuffled[other]] = [shuffled[other], shuffled[index]];
    }
    return shuffled;
  }

  _normalizeChallengeCount(value) {
    const count = Math.trunc(Number(value));
    if (!Number.isFinite(count)) return 4;
    return Math.min(BeatHero.MAX_CHALLENGES, Math.max(BeatHero.MIN_CHALLENGES, count));
  }

  _normalizeBpm(value) {
    return normalizeMetronomeBpm(value);
  }

  _normalizeCardCount(value) {
    const count = Math.trunc(Number(value));
    if (!Number.isFinite(count)) return 2;
    return Math.min(6, Math.max(2, count));
  }

  _normalizeFigureIds(value) {
    const validIds = new Set(BeatHero.FIGURES.map((figure) => figure.id));
    const selected = [...new Set(Array.isArray(value) ? value : [])]
      .filter((figureId) => validIds.has(figureId));

    BeatHero.DEFAULT_FIGURE_IDS.forEach((figureId) => {
      if (selected.length < 2 && !selected.includes(figureId)) selected.push(figureId);
    });

    return selected;
  }

  _normalizeBool(value) {
    return value === true
      || value === 1
      || ["1", "true", "on", "yes"].includes(String(value).toLowerCase());
  }
}
