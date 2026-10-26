import { beatMsForBpm } from "../shared/tempo.js";
import { GameAudio } from "../shared/GameAudio.js";

// An eight-bar chiptune in C major. Each entry is an eighth-note position;
// keeping the harmony fixed lets layers enter and leave as the snake changes size.
const SONG = [
  { bass: "C2", fifth: "G2", chord: ["C5", "E5", "G5", "B5"], melody: ["E5", null, "G5", "E5", "C6", null, "G5", "E5"] },
  { bass: "C2", fifth: "G2", chord: ["C5", "E5", "G5", "B5"], melody: ["D5", "E5", "G5", null, "E5", "D5", "C5", null] },
  { bass: "A2", fifth: "E3", chord: ["A4", "C5", "E5", "G5"], melody: ["E5", null, "A5", "G5", "E5", null, "C5", "E5"] },
  { bass: "A2", fifth: "E3", chord: ["A4", "C5", "E5", "G5"], melody: ["G5", "E5", "C5", null, "B4", "C5", "E5", null] },
  { bass: "F2", fifth: "C3", chord: ["F4", "A4", "C5", "E5"], melody: ["A5", null, "G5", "E5", "F5", null, "A5", "C6"] },
  { bass: "F2", fifth: "C3", chord: ["F4", "A4", "C5", "E5"], melody: ["A5", "G5", "F5", null, "E5", "F5", "A5", null] },
  { bass: "G2", fifth: "D3", chord: ["G4", "B4", "D5", "F5"], melody: ["G5", null, "B5", "A5", "G5", null, "D5", "F5"] },
  { bass: "G2", fifth: "D3", chord: ["G4", "B4", "D5", "F5"], melody: ["E5", "D5", "B4", "D5", "G5", "F5", "D5", "B4"] },
];

const downOctave = (note) => note.replace(/\d$/, (octave) => String(Number(octave) - 1));
// Root–third–fifth–third gives the faster layer a clear melodic contour.
// Use the triad so sustained melody notes do not clash with a repeated seventh.
const ARPEGGIO = [0, 1, 2, 1];

export class NotePythonMusic {
  constructor() {
    this._voices = null;
    this._victoryVoices = [];
    this._victoryTimer = null;
    this._step = 0;
  }

  _synth(type, volume, release = 0.04) {
    return new window.Tone.Synth({
      oscillator: { type },
      envelope: { attack: 0.004, decay: 0.06, sustain: 0.3, release },
      volume,
    }).toDestination();
  }

  start(bpm) {
    this.stop();
    if (!window.Tone) return;
    this.setTempo(bpm);
    this._nextTime = window.Tone.now();
    this._voices = {
      lead: this._synth("triangle", -23, 0.07),
      bass: this._synth("triangle", -16, 0.06),
      arp: this._synth("square", -30),
      keys: new window.Tone.PolySynth(window.Tone.Synth, {
        oscillator: { type: "triangle" },
        envelope: { attack: 0.008, decay: 0.12, sustain: 0.2, release: 0.09 },
        volume: -27,
      }).toDestination(),
      kick: new window.Tone.MembraneSynth({
        pitchDecay: 0.025,
        octaves: 2,
        oscillator: { type: "sine" },
        envelope: { attack: 0.001, decay: 0.15, sustain: 0, release: 0.04 },
        volume: -17,
      }).toDestination(),
      snare: new window.Tone.NoiseSynth({
        noise: { type: "pink" },
        envelope: { attack: 0.001, decay: 0.07, sustain: 0, release: 0.02 },
        volume: -26,
      }).toDestination(),
      hats: new window.Tone.NoiseSynth({
        noise: { type: "white" },
        envelope: { attack: 0.001, decay: 0.012, sustain: 0, release: 0.008 },
        volume: -38,
      }).toDestination(),
    };
  }

  // Change spacing at an eighth-note boundary without cutting off voices or
  // moving the already scheduled boundary in _nextTime.
  setTempo(bpm) {
    this._eighth = beatMsForBpm(bpm) / 2000;
  }

  // Called on the same eighth-note tick that moves the snake. Schedule extra
  // sixteenths on the audio clock, without introducing another game timer.
  playStep(snakeLength) {
    if (!this._voices) return;
    const now = window.Tone.now();
    const time = Math.max(now, this._nextTime);
    this._nextTime = time + this._eighth;
    const step = this._step++;
    const slot = step % 8;
    const bar = SONG[Math.floor(step / 8) % SONG.length];
    // A normal four-round game starts at length 2 and finishes at 6. Make
    // every pickup audible, reaching the frantic arrangement before the win.
    const growth = Math.max(0, Math.min(6, Math.floor(snakeLength) - 2));
    const lively = growth >= 1;
    const driving = growth >= 2;
    const frantic = growth >= 3;
    const { lead, bass, arp, keys, kick, snare, hats } = this._voices;

    // Keep the warm register, but leave space at the start. Increase rhythmic
    // subdivisions and accents as the snake grows, never the underlying BPM.
    if (bar.melody[slot] && (lively || slot % 2 === 0)) {
      lead.triggerAttackRelease(downOctave(bar.melody[slot]), this._eighth * (frantic ? 0.45 : 0.85), time, GameAudio.scale("notePythonMelody", 0.5 + growth * 0.015));
    }
    if (slot % (lively ? 2 : 4) === 0 || frantic || (driving && slot === 7)) {
      const note = slot % 4 >= 2 ? bar.fifth : bar.bass;
      bass.triggerAttackRelease(note, this._eighth * (frantic ? 0.45 : 1.3), time, GameAudio.scale("notePythonLowEnd", slot % 2 === 0 ? 0.7 + growth * 0.025 : 0.65));
    }
    if (growth >= 5 && slot % 4 === 3) {
      bass.triggerAttackRelease(bar.fifth, this._eighth * 0.25, time + this._eighth / 2, GameAudio.scale("notePythonLowEnd", 0.5));
    }
    const playChord = driving ? slot % 2 === 0
        : lively ? slot % 4 === 0 : slot === 0;
    if (playChord) {
      const chord = bar.chord.slice(0, 3).map((note) => downOctave(downOctave(note)));
      keys.triggerAttackRelease(chord, this._eighth * (frantic ? 0.4 : 1.5), time, GameAudio.scale("notePythonLowEnd", lively ? 0.6 : 0.4));
    }
    if (driving) {
      // Stage three joins the snake's eighth-note pulse directly. Later stages
      // subdivide that same pulse evenly; never shift it by half an eighth.
      const subdivisions = frantic ? 2 : 1;
      for (let part = 0; part < subdivisions; part++) {
        const offset = part / subdivisions;
        const note = downOctave(bar.chord[ARPEGGIO[(slot * subdivisions + part) % ARPEGGIO.length]]);
        const accent = (slot * subdivisions + part) % (2 * subdivisions) === 0;
        arp.triggerAttackRelease(note, this._eighth * 0.18, time + offset * this._eighth, GameAudio.scale("notePythonMelody", accent ? 0.5 : 0.32));
      }
    }
    if (slot % (driving ? 2 : 4) === 0 || (frantic && slot === 7)) {
      kick.triggerAttackRelease("C1", 0.06, time, GameAudio.scale("notePythonDrums", lively ? 0.85 : 0.6));
    }
    if (lively ? slot % 4 === 2 : slot === 6) snare.triggerAttackRelease(0.045, time, GameAudio.scale("notePythonDrums", lively ? 0.7 : 0.35));
    // Reserve the densest fills for the last beat of the bar. The bass and
    // chord accents remain stable underneath, even for a very long snake.
    if (growth >= 6 && slot === 6) snare.triggerAttackRelease(0.02, time + this._eighth / 2, GameAudio.scale("notePythonDrums", 0.25));
    if (growth >= 6 && slot === 7) snare.triggerAttackRelease(0.02, time, GameAudio.scale("notePythonDrums", 0.3));
    if (growth >= 4 && slot === 7) snare.triggerAttackRelease(0.025, time + this._eighth / 2, GameAudio.scale("notePythonDrums", 0.45));
    if (driving || (lively && slot % 2 === 1)) {
      hats.triggerAttackRelease(0.01, time, GameAudio.scale("notePythonDrums", slot % 2 === 1 ? 0.3 : 0.18));
    }
    if (growth >= 4 || (frantic && slot % 2 === 1)) {
      hats.triggerAttackRelease(0.008, time + this._eighth / 2, GameAudio.scale("notePythonDrums", 0.25));
    }
  }

  stop() {
    // Disposing also cancels notes scheduled just ahead of a collision/pause.
    if (this._voices) Object.values(this._voices).forEach((voice) => voice.dispose());
    this._voices = null;
  }

  playVictory(bpm) {
    this.reset();
    if (!window.Tone) return;
    const beat = beatMsForBpm(bpm) / 1000;
    const start = window.Tone.now();
    const lead = this._synth("triangle", -23, 0.07);
    const bass = this._synth("triangle", -16, 0.06);
    this._victoryVoices = [lead, bass];

    // A brief resolution at the gameplay instruments' levels, ending before
    // the separate final-results reveal music starts.
    [
      [0, 0.18, "E4"],
      [0.25, 0.18, "G4"],
      [0.5, 0.35, "C5"],
    ].forEach(([offset, duration, note]) => {
      lead.triggerAttackRelease(note, duration * beat, start + offset * beat, GameAudio.scale("notePythonVictory", GameAudio.scale("notePythonMelody", 0.5)));
    });
    bass.triggerAttackRelease("C2", beat * 0.35, start + 0.5 * beat, GameAudio.scale("notePythonVictory", GameAudio.scale("notePythonLowEnd", 0.7)));
    this._victoryTimer = setTimeout(() => this._stopVictory(), (0.85 * beat + 0.15) * 1000);
  }

  _stopVictory() {
    if (this._victoryTimer != null) clearTimeout(this._victoryTimer);
    this._victoryTimer = null;
    this._victoryVoices.forEach((voice) => voice.dispose());
    this._victoryVoices = [];
  }

  reset() {
    this.stop();
    this._stopVictory();
    this._step = 0;
  }
}
