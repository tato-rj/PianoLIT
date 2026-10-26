export class GameAudio {
  // Tweak these values to rebalance the whole app.
  static SYNTH_VOLUME_DB = {
    uiPoly: -10,
    uiNoise: -16,
    uiTimer: -14,
    metronome: -12,
    rhythmHit: -10,
    staffNote: -8,
    dictation: -9,
    sequence: -9,
  };

  static VELOCITY = {
    staffNote: 1.0,
    dictation: 1.0,
    sequence: 1.0,
    successBasic: 0.7,
    successBonus: 0.7,
    failNoise: 1,
    failNote: 1,
    bombFail: 1,
    wallCrash: .4,
    final: 0.5,
    finalMetric: 0.85,
    perfectBonus: 0.25,
    runStart: 0.9,
    timerBeep: 0.7,
    timerTimeUp: 0.95,
    countdownBeep: 1,
    metronomeBeat: 0.4,
    metronomeDownbeat: 0.6,
    rhythmHit: 0.65,
    hinge: 0.55,
    notePythonMelody: 1,
    notePythonLowEnd: 1,
    notePythonDrums: 1,
    notePythonVictory: 1,
  };

  static SOUND_LIBRARY = [
    {
      id: "staffNote",
      label: "Staff Note",
      volumeKey: "staffNote",
      description: "Base loudness for staff note playback.",
    },
    {
      id: "dictation",
      label: "Dictation",
      volumeKey: "dictation",
      description: "Dictation playback loudness in PitchDetective.",
    },
    {
      id: "sequence",
      label: "Sequence",
      volumeKey: "sequence",
      description: "Sequence playback loudness in ToneTrek and similar games.",
    },
    {
      id: "successBasic",
      label: "Success",
      volumeKey: "successBasic",
      description: "Normal correct-answer sound.",
    },
    {
      id: "successBonus",
      label: "Streak Bonus",
      volumeKey: "successBonus",
      description: "Streak / bonus correct-answer sound.",
    },
    {
      id: "failNoise",
      label: "Fail Noise",
      volumeKey: "failNoise",
      description: "Noise portion of the fail sound.",
    },
    {
      id: "failNote",
      label: "Fail Notes",
      volumeKey: "failNote",
      description: "Pitched portion of the fail sound.",
    },
    {
      id: "bombFail",
      label: "Bomb Hit",
      volumeKey: "bombFail",
      description: "Long stumbling fail sound when the snake hits a bomb.",
    },
    {
      id: "wallCrash",
      label: "Wall Crash",
      volumeKey: "wallCrash",
      description: "Sharp breaking impact when the snake crashes into a wall.",
    },
    {
      id: "final",
      label: "Final Results",
      volumeKey: "final",
      description: "Final results reveal fanfare.",
    },
    {
      id: "finalMetric",
      label: "Metric Pop",
      volumeKey: "finalMetric",
      description: "Small pop sound as each final metric box appears.",
    },
    {
      id: "perfectBonus",
      label: "Perfect Bonus",
      volumeKey: "perfectBonus",
      description: "Extra reward sound for perfect/no-mistakes games.",
    },
    {
      id: "runStart",
      label: "Run Start",
      volumeKey: "runStart",
      description: "Opening fanfare at the start of a run/countdown.",
    },
    {
      id: "timerBeep",
      label: "Timer Warning",
      volumeKey: "timerBeep",
      description: "Repeating warning beep in the last timer seconds.",
    },
    {
      id: "timerTimeUp",
      label: "Time Up",
      volumeKey: "timerTimeUp",
      description: "Stronger sound when the timer actually runs out.",
    },
    {
      id: "countdownBeep",
      label: "Countdown Tick",
      volumeKey: "countdownBeep",
      description: "Simple 3-2-1 countdown tick sound.",
    },
    {
      id: "metronomeBeat",
      label: "Metronome Beat",
      volumeKey: "metronomeBeat",
      description: "Regular metronome click.",
    },
    {
      id: "metronomeDownbeat",
      label: "Metronome Downbeat",
      volumeKey: "metronomeDownbeat",
      description: "Higher-pitched click on the first beat of each measure.",
    },
    {
      id: "rhythmHit",
      label: "Rhythm Hit",
      volumeKey: "rhythmHit",
      description: "Low percussive sound for Beat Hero rhythm notes.",
    },
    {
      id: "hinge",
      label: "Hinge",
      volumeKey: "hinge",
      description: "Short hinge/fall sound used by ToneTrek block reveals.",
    },
    {
      id: "notePythonMelody",
      label: "Note Python Melody",
      volumeKey: "notePythonMelody",
      description: "Lead melody and fast arpeggios in the Note Python soundtrack.",
    },
    {
      id: "notePythonLowEnd",
      label: "Note Python Bass & Chords",
      volumeKey: "notePythonLowEnd",
      description: "Bass line and low harmony in the Note Python soundtrack.",
    },
    {
      id: "notePythonDrums",
      label: "Note Python Drums",
      volumeKey: "notePythonDrums",
      description: "Kick, snare, and hi-hat layers in the Note Python soundtrack.",
    },
    {
      id: "notePythonVictory",
      label: "Note Python Victory",
      volumeKey: "notePythonVictory",
      description: "Brief three-note victory cue at the Note Python soundtrack volume.",
    },
  ];

  static _previewSynths = {};

  static scale(kind, base = 1) {
    const mult = Number(GameAudio.VELOCITY[kind]);
    return (Number.isFinite(mult) ? mult : 1) * (Number(base) || 0);
  }

  static getSoundLibrary() {
    return GameAudio.SOUND_LIBRARY.map((sound) => ({
      ...sound,
      valuePercent: GameAudio.getVelocityPercent(sound.volumeKey),
    }));
  }

  static getVelocityPercent(kind) {
    const value = Number(GameAudio.VELOCITY[kind]);
    if (!Number.isFinite(value)) return 0;
    return Math.max(0, Math.min(100, Math.round(value * 100)));
  }

  static setVelocityPercent(kind, percent) {
    const next = Math.max(0, Math.min(100, Number(percent) || 0));
    if (!Object.prototype.hasOwnProperty.call(GameAudio.VELOCITY, kind)) return 0;
    GameAudio.VELOCITY[kind] = next / 100;
    return next;
  }

  static async previewSound(soundId) {
    if (!window.Tone) return;

    await Tone.start();

    const previewers = {
      staffNote: () => {
        GameAudio._getPreviewSynth("staffNote", () => GameAudio.createStaffNoteSynth())
          .triggerAttackRelease("C4", 0.5, undefined, GameAudio.scale("staffNote", 1));
      },
      dictation: () => {
        GameAudio._getPreviewSynth("dictation", () => GameAudio.createDictationSynth())
          .triggerAttackRelease(["C4", "E4"], 0.3, undefined, GameAudio.scale("dictation", 1));
      },
      sequence: () => {
        GameAudio._getPreviewSynth("sequence", () => GameAudio.createSequenceSynth())
          .triggerAttackRelease(["C4", "G4"], 0.26, undefined, GameAudio.scale("sequence", 1));
      },
      successBasic: () => {
        const synth = GameAudio._getPreviewSynth("uiPoly", () => GameAudio.createUiPolySynth());
        const now = Tone.now();
        ["C6", "E6", "G6"].forEach((n, i) => {
          synth.triggerAttackRelease(n, 0.07, now + i * 0.05, GameAudio.scale("successBasic", 0.42));
        });
      },
      successBonus: () => {
        const synth = GameAudio._getPreviewSynth("uiPoly", () => GameAudio.createUiPolySynth());
        const now = Tone.now();
        const oldEnv = { ...synth.get().envelope };
        const oldOsc = synth.get().oscillator?.type;

        try {
          synth.set({
            oscillator: { type: "sine" },
            envelope: { attack: 0.004, decay: 0.12, sustain: 0.15, release: 0.65 },
          });
        } catch (_) {}

        const semitoneShift = 3;
        const toNote = (midi) => Tone.Frequency(midi, "midi").toNote();
        [62, 66, 69, 73, 74].map((m) => toNote(m + semitoneShift)).forEach((n, i) => {
          synth.triggerAttackRelease(n, 0.06, now + i * 0.045, GameAudio.scale("successBonus", 0.45));
        });
        [62, 69, 74, 78].map((m) => toNote(m + semitoneShift)).forEach((n) => {
          synth.triggerAttackRelease(n, 0.12, now + 0.26, GameAudio.scale("successBonus", 0.30));
        });

        setTimeout(() => {
          try {
            synth.set({
              oscillator: { type: oldOsc || "triangle" },
              envelope: oldEnv,
            });
          } catch (_) {}
        }, 600);
      },
      failNoise: () => {
        GameAudio._getPreviewSynth("uiNoise", () => GameAudio.createUiNoiseSynth())
          .triggerAttackRelease(0.06, Tone.now(), GameAudio.scale("failNoise", 0.45));
      },
      failNote: () => {
        const synth = GameAudio._getPreviewSynth("uiPoly", () => GameAudio.createUiPolySynth());
        const now = Tone.now();
        synth.triggerAttackRelease("A2", 0.10, now + 0.01, GameAudio.scale("failNote", 0.55));
        synth.triggerAttackRelease("G2", 0.12, now + 0.08, GameAudio.scale("failNote", 0.6));
      },
      bombFail: () => {
        const synth = GameAudio._getPreviewSynth("uiPoly", () => GameAudio.createUiPolySynth());
        const noiseSynth = GameAudio._getPreviewSynth("uiNoise", () => GameAudio.createUiNoiseSynth());
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
          if (i < 8) noiseSynth.triggerAttackRelease(0.05, when + 0.015, GameAudio.scale("bombFail", 0.14));
        });

        setTimeout(() => {
          try {
            synth.set({
              oscillator: { type: oldOsc || "triangle" },
              envelope: oldEnv,
            });
          } catch (_) {}
        }, 2200);
      },
      wallCrash: () => {
        const synth = GameAudio._getPreviewSynth("uiTimer", () => GameAudio.createUiTimerSynth());
        const noiseSynth = GameAudio._getPreviewSynth("uiNoise", () => GameAudio.createUiNoiseSynth());
        const now = Tone.now();
        noiseSynth.triggerAttackRelease(0.12, now, GameAudio.scale("wallCrash", 0.32));
        noiseSynth.triggerAttackRelease(0.09, now + 0.045, GameAudio.scale("wallCrash", 0.22));
        synth.triggerAttackRelease("G3", 0.08, now, GameAudio.scale("wallCrash", 0.85));
        synth.triggerAttackRelease("D3", 0.12, now + 0.04, GameAudio.scale("wallCrash", 0.7));
        synth.triggerAttackRelease("A2", 0.18, now + 0.11, GameAudio.scale("wallCrash", 0.62));
      },
      final: () => {
        GameAudio.playFinalResults();
      },
      finalMetric: () => {
        const synth = GameAudio._getPreviewSynth("uiTimer", () => GameAudio.createUiTimerSynth());
        const now = Tone.now();
        synth.triggerAttackRelease("G5", 0.055, now, GameAudio.scale("finalMetric", 0.44));
        synth.triggerAttackRelease("C6", 0.045, now + 0.03, GameAudio.scale("finalMetric", 0.34));
      },
      perfectBonus: () => {
        const synth = GameAudio._getPreviewSynth("uiPoly", () => GameAudio.createUiPolySynth());
        const now = Tone.now();
        const oldEnv = { ...synth.get().envelope };
        const oldOsc = synth.get().oscillator?.type;

        try {
          synth.set({
            oscillator: { type: "triangle" },
            envelope: { attack: 0.01, decay: 0.18, sustain: 0.25, release: 0.8 },
          });
        } catch (_) {}

        ["C5", "E5", "G5", "C6", "E6", "G6", "C7"].forEach((n, i) => {
          synth.triggerAttackRelease(n, 0.09, now + i * 0.06, GameAudio.scale("perfectBonus", 0.62));
        });

        setTimeout(() => {
          try {
            synth.set({
              oscillator: { type: oldOsc || "triangle" },
              envelope: oldEnv,
            });
          } catch (_) {}
        }, 1400);
      },
      runStart: () => {
        const synth = GameAudio._getPreviewSynth("uiPoly", () => GameAudio.createUiPolySynth());
        const now = Tone.now();
        const toNote = (m) => Tone.Frequency(m, "midi").toNote();
        [
          [[0, 7], 0.00, 0.19, 0.20],
          [[3, 7], 0.24, 0.06, 0.18],
          [[0, 5, 10], 0.38, 0.18, 0.22],
          [[2, 5, 9], 0.62, 0.06, 0.18],
          [[0, 7, 12], 0.76, 0.15, 0.22],
        ].forEach(([intervals, t, dur, vel]) => {
          synth.triggerAttackRelease(
            intervals.map((i) => toNote(60 + i)),
            dur,
            now + t,
            GameAudio.scale("runStart", vel),
          );
        });
      },
      timerBeep: () => {
        GameAudio._getPreviewSynth("uiTimer", () => GameAudio.createUiTimerSynth())
          .triggerAttackRelease("C6", 0.06, Tone.now(), GameAudio.scale("timerBeep", 0.5));
      },
      timerTimeUp: () => {
        const timerSynth = GameAudio._getPreviewSynth("uiTimer", () => GameAudio.createUiTimerSynth());
        const noiseSynth = GameAudio._getPreviewSynth("uiNoise", () => GameAudio.createUiNoiseSynth());
        const now = Tone.now();
        noiseSynth.triggerAttackRelease(0.12, now, GameAudio.scale("timerTimeUp", 0.2));
        timerSynth.triggerAttackRelease("G4", 0.11, now, GameAudio.scale("timerTimeUp", 0.72));
        timerSynth.triggerAttackRelease("E4", 0.13, now + 0.10, GameAudio.scale("timerTimeUp", 0.76));
        timerSynth.triggerAttackRelease("C4", 0.18, now + 0.22, GameAudio.scale("timerTimeUp", 0.82));
      },
      countdownBeep: () => {
        GameAudio._getPreviewSynth("uiTimer", () => GameAudio.createUiTimerSynth())
          .triggerAttackRelease("B5", 0.06, Tone.now(), GameAudio.scale("countdownBeep", 0.2));
      },
      metronomeBeat: () => {
        GameAudio.playMetronomeClick(false);
      },
      metronomeDownbeat: () => {
        GameAudio.playMetronomeClick(true);
      },
      rhythmHit: () => {
        GameAudio.playRhythmHit();
      },
      hinge: () => {
        const noiseSynth = GameAudio._getPreviewSynth("uiNoise", () => GameAudio.createUiNoiseSynth());
        const synth = GameAudio._getPreviewSynth("uiTimer", () => GameAudio.createUiTimerSynth());
        const now = Tone.now();
        noiseSynth.triggerAttackRelease(0.04, now, GameAudio.scale("hinge", 0.07));
        synth.triggerAttackRelease("E4", 0.04, now, GameAudio.scale("hinge", 0.12));
        synth.triggerAttackRelease("C4", 0.05, now + 0.04, GameAudio.scale("hinge", 0.16));
      },
      notePythonMelody: () => {
        const lead = GameAudio._getPreviewSynth("notePythonLead", () => new Tone.Synth({
          oscillator: { type: "triangle" },
          envelope: { attack: 0.004, decay: 0.06, sustain: 0.3, release: 0.07 },
          volume: -23,
        }).toDestination());
        const arp = GameAudio._getPreviewSynth("notePythonArp", () => new Tone.Synth({
          oscillator: { type: "square" },
          envelope: { attack: 0.004, decay: 0.06, sustain: 0.3, release: 0.04 },
          volume: -30,
        }).toDestination());
        const now = Tone.now();
        ["E4", "G4", "C5", "G4"].forEach((note, i) => {
          lead.triggerAttackRelease(note, 0.18, now + i * 0.2, GameAudio.scale("notePythonMelody", 0.52));
          arp.triggerAttackRelease(["C4", "E4", "G4", "B4"][i], 0.07, now + i * 0.2 + 0.1, GameAudio.scale("notePythonMelody", 0.5));
        });
      },
      notePythonLowEnd: () => {
        const bass = GameAudio._getPreviewSynth("notePythonBass", () => new Tone.Synth({
          oscillator: { type: "triangle" },
          envelope: { attack: 0.004, decay: 0.06, sustain: 0.3, release: 0.06 },
          volume: -16,
        }).toDestination());
        const keys = GameAudio._getPreviewSynth("notePythonKeys", () => new Tone.PolySynth(Tone.Synth, {
          oscillator: { type: "triangle" },
          envelope: { attack: 0.008, decay: 0.12, sustain: 0.2, release: 0.09 },
          volume: -27,
        }).toDestination());
        const now = Tone.now();
        bass.triggerAttackRelease("C2", 0.42, now, GameAudio.scale("notePythonLowEnd", 0.75));
        bass.triggerAttackRelease("G2", 0.32, now + 0.4, GameAudio.scale("notePythonLowEnd", 0.68));
        keys.triggerAttackRelease(["C3", "E3", "G3"], 0.5, now, GameAudio.scale("notePythonLowEnd", 0.55));
      },
      notePythonDrums: () => {
        const kick = GameAudio._getPreviewSynth("notePythonKick", () => new Tone.MembraneSynth({
          pitchDecay: 0.025,
          octaves: 2,
          oscillator: { type: "sine" },
          envelope: { attack: 0.001, decay: 0.15, sustain: 0, release: 0.04 },
          volume: -17,
        }).toDestination());
        const snare = GameAudio._getPreviewSynth("notePythonSnare", () => new Tone.NoiseSynth({
          noise: { type: "pink" },
          envelope: { attack: 0.001, decay: 0.07, sustain: 0, release: 0.02 },
          volume: -26,
        }).toDestination());
        const hats = GameAudio._getPreviewSynth("notePythonHats", () => new Tone.NoiseSynth({
          noise: { type: "white" },
          envelope: { attack: 0.001, decay: 0.012, sustain: 0, release: 0.008 },
          volume: -38,
        }).toDestination());
        const now = Tone.now();
        [0, 0.2, 0.4, 0.6].forEach((offset, i) => {
          if (i % 2 === 0) kick.triggerAttackRelease("C1", 0.06, now + offset, GameAudio.scale("notePythonDrums", 0.8));
          else snare.triggerAttackRelease(0.045, now + offset, GameAudio.scale("notePythonDrums", 0.65));
          hats.triggerAttackRelease(0.01, now + offset + 0.1, GameAudio.scale("notePythonDrums", 0.3));
        });
      },
      notePythonVictory: () => {
        const lead = GameAudio._getPreviewSynth("notePythonVictoryLead", () => new Tone.Synth({
          oscillator: { type: "triangle" },
          envelope: { attack: 0.004, decay: 0.06, sustain: 0.3, release: 0.07 },
          volume: -23,
        }).toDestination());
        const bass = GameAudio._getPreviewSynth("notePythonVictoryBass", () => new Tone.Synth({
          oscillator: { type: "triangle" },
          envelope: { attack: 0.004, decay: 0.06, sustain: 0.3, release: 0.06 },
          volume: -16,
        }).toDestination());
        const now = Tone.now();
        [
          [0, 0.09, "E4"],
          [0.125, 0.09, "G4"],
          [0.25, 0.175, "C5"],
        ].forEach(([offset, duration, note]) => {
          lead.triggerAttackRelease(note, duration, now + offset, GameAudio.scale("notePythonVictory", GameAudio.scale("notePythonMelody", 0.5)));
        });
        bass.triggerAttackRelease("C2", 0.175, now + 0.25, GameAudio.scale("notePythonVictory", GameAudio.scale("notePythonLowEnd", 0.7)));
      },
    };

    previewers[soundId]?.();
  }

  static createUiPolySynth() {
    return new Tone.PolySynth(Tone.Synth, {
      oscillator: { type: "triangle" },
      envelope: { attack: 0.005, decay: 0.12, sustain: 0.0, release: 0.25 },
      volume: GameAudio.SYNTH_VOLUME_DB.uiPoly,
    }).toDestination();
  }

  static playFinalResults() {
    if (!window.Tone) return;

    const synth = GameAudio._getPreviewSynth("finalResults", () => new Tone.PolySynth(Tone.Synth, {
      oscillator: { type: "sine" },
      envelope: { attack: 0.02, decay: 0.25, sustain: 0.35, release: 0.9 },
      volume: GameAudio.SYNTH_VOLUME_DB.uiPoly,
    }).toDestination());
    const now = Tone.now();
    const variants = [
      () => {
        ["C4", "G4", "C5", "E5"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.9, now, GameAudio.scale("final", 0.6)),
        );
        ["G5", "A5", "B5", "C6", "D6", "E6", "G6"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.08, now + 0.25 + index * 0.06, GameAudio.scale("final", 0.35));
        });
      },
      () => {
        ["C5", "E5", "G5", "B5", "D6", "G6"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.11, now + index * 0.08, GameAudio.scale("final", 0.44));
        });
        ["C6", "E6", "G6"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.28, now + 0.62, GameAudio.scale("final", 0.5)),
        );
      },
      () => {
        ["A3", "E4", "A4", "C5"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.45, now, GameAudio.scale("final", 0.45)),
        );
        ["F4", "A4", "C5", "F5"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.52, now + 0.35, GameAudio.scale("final", 0.48)),
        );
        ["C5", "F5", "A5"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.24, now + 0.78, GameAudio.scale("final", 0.4)),
        );
      },
      () => {
        ["E5", "G5", "A5", "B5", "D6", "E6", "G6", "A6"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.075, now + index * 0.055, GameAudio.scale("final", 0.34));
        });
        ["A5", "A6", "C7"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.2, now + 0.52, GameAudio.scale("final", 0.42)),
        );
      },
      () => {
        ["D4", "A4", "D5", "F#5"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.36, now, GameAudio.scale("final", 0.45)),
        );
        ["D5", "F#5", "A5", "D6", "F#6"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.095, now + 0.2 + index * 0.065, GameAudio.scale("final", 0.4));
        });
      },
      () => {
        ["G3", "D4", "G4", "B4"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.4, now, GameAudio.scale("final", 0.4)),
        );
        ["C4", "E4", "G4", "C5"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.44, now + 0.32, GameAudio.scale("final", 0.46)),
        );
        ["E5", "G5", "C6"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.22, now + 0.72, GameAudio.scale("final", 0.4)),
        );
      },
      () => {
        ["C6", "D6", "E6", "G6", "A6", "G6", "E6", "C7"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.06, now + index * 0.048, GameAudio.scale("final", 0.3));
        });
        ["G6", "C7"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.2, now + 0.48, GameAudio.scale("final", 0.38)),
        );
      },
      () => {
        ["F4", "C5", "A5"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.16, now + index * 0.06, GameAudio.scale("final", 0.45));
        });
        ["G4", "D5", "B5"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.16, now + 0.28 + index * 0.06, GameAudio.scale("final", 0.48));
        });
        ["C5", "E5", "G5", "C6"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.26, now + 0.54, GameAudio.scale("final", 0.44)),
        );
      },
      () => {
        ["A6", "G6", "E6", "D6", "C6"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.08, now + index * 0.06, GameAudio.scale("final", 0.34));
        });
        ["E6", "G6", "C7"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.24, now + 0.4, GameAudio.scale("final", 0.42)),
        );
      },
      () => {
        ["C4", "E4", "G4", "C5"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.34, now, GameAudio.scale("final", 0.48)),
        );
        ["E5", "G5", "B5", "D6", "E6"].forEach((note, index) => {
          synth.triggerAttackRelease(note, 0.09, now + 0.18 + index * 0.055, GameAudio.scale("final", 0.38));
        });
        ["C6", "E6", "G6", "C7"].forEach((note) =>
          synth.triggerAttackRelease(note, 0.3, now + 0.58, GameAudio.scale("final", 0.5)),
        );
      },
    ];

    variants[Math.floor(Math.random() * variants.length)]();
  }

  static createUiNoiseSynth() {
    return new Tone.NoiseSynth({
      noise: { type: "pink" },
      envelope: { attack: 0.001, decay: 0.08, sustain: 0.0, release: 0.06 },
      volume: GameAudio.SYNTH_VOLUME_DB.uiNoise,
    }).toDestination();
  }

  static createUiTimerSynth() {
    return new Tone.Synth({
      oscillator: { type: "square" },
      envelope: { attack: 0.001, decay: 0.03, sustain: 0.0, release: 0.06 },
      volume: GameAudio.SYNTH_VOLUME_DB.uiTimer,
    }).toDestination();
  }

  static async ensureMetronomeAudio() {
    if (!window.Tone) return null;

    await Tone.start();
    return GameAudio._getPreviewSynth("metronome", () => GameAudio.createMetronomeSynth());
  }

  static playMetronomeClick(isDownbeat = false) {
    if (!window.Tone) return;

    const synth = GameAudio._getPreviewSynth("metronome", () => GameAudio.createMetronomeSynth());
    const kind = isDownbeat ? "metronomeDownbeat" : "metronomeBeat";

    synth.triggerAttackRelease(
      isDownbeat ? "C6" : "C5",
      "16n",
      Tone.now(),
      GameAudio.scale(kind, 1),
    );
  }

  static createMetronomeSynth() {
    return new Tone.Synth({
      oscillator: { type: "square" },
      envelope: { attack: 0.001, decay: 0.04, sustain: 0, release: 0.01 },
      volume: GameAudio.SYNTH_VOLUME_DB.metronome,
    }).toDestination();
  }

  static playRhythmHit() {
    if (!window.Tone) return;

    const synth = GameAudio._getPreviewSynth("rhythmHit", () => GameAudio.createRhythmHitSynth());
    synth.triggerAttackRelease("C2", "8n", Tone.now(), GameAudio.scale("rhythmHit", 1));
  }

  static createRhythmHitSynth() {
    return new Tone.MembraneSynth({
      pitchDecay: 0.035,
      octaves: 2.5,
      oscillator: { type: "sine" },
      envelope: { attack: 0.001, decay: 0.12, sustain: 0, release: 0.06 },
      volume: GameAudio.SYNTH_VOLUME_DB.rhythmHit,
    }).toDestination();
  }

  static createStaffNoteSynth() {
    return new Tone.Synth({
      oscillator: { type: "sine" },
      envelope: { attack: 0.01, decay: 0.08, sustain: 0.6, release: 0.12 },
      volume: GameAudio.SYNTH_VOLUME_DB.staffNote,
    }).toDestination();
  }

  static createDictationSynth() {
    return new Tone.PolySynth(Tone.Synth, {
      oscillator: { type: "sine" },
      envelope: { attack: 0.01, decay: 0.08, sustain: 0.35, release: 0.25 },
      volume: GameAudio.SYNTH_VOLUME_DB.dictation,
    }).toDestination();
  }

  static createSequenceSynth() {
    return new Tone.PolySynth(Tone.Synth, {
      oscillator: { type: "sine" },
      envelope: { attack: 0.01, decay: 0.08, sustain: 0.35, release: 0.25 },
      volume: GameAudio.SYNTH_VOLUME_DB.sequence,
    }).toDestination();
  }

  static _getPreviewSynth(key, factory) {
    if (!GameAudio._previewSynths[key]) {
      GameAudio._previewSynths[key] = factory();
    }
    return GameAudio._previewSynths[key];
  }
}
