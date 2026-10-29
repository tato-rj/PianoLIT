# Spoken counting and temporary voice effects

The standalone metronome counts **one through four**, matching its existing 1–4
beats per bar. It does not expose 6/8 or an eighth-note subdivision selector.
Click, Woodblock, Beep and Cowbell retain their default behavior.

## Using it

Open Settings → Sound. Choose a saved counting recording, or use **New recording**
to record your four numbers through the existing private account flow. Choose
**Voice effect** for Classic, Cartoon, Deep Space, Robot, Ghost or Chipmunk.
The chosen effect stays selected while you change BPM, beats, appearance or
sound, and while you close/reopen settings. **Refreshing returns to Classic**.
The chosen original sound still follows the existing browser/account preferences.

Arthur's October 29 follow-up supersedes the initial request to save six generated
voice libraries: effects are generated on demand, only in browser memory. They
are never saved to an account, localStorage, IndexedDB, audio files or the server.
Classic plays the original recording unchanged. Private recording previews and
saved WAVs remain original. No speech synthesis, AI API, third-party voice samples,
backend processing, subscriptions or paid services are involved.

## Optional built-in original voice

The feature also supports a public Classic voice, playable without signing in.
No original recordings have been supplied; this option remains unavailable until
all four real recordings are prepared. Existing legacy audio assets in the
repository are **not** substituted for the requested original voice.

1. Record yourself saying one, two, three and four, one file per word. Use a quiet
   room, a consistent microphone distance and natural, clear speech without effects.
   Aim for 0.2–0.6 seconds per word, with a little silence before and after.
2. Export uncompressed PCM WAV (mono/stereo, 16/24/32-bit, 24/44.1/48 kHz). Each
   source file must be under five seconds; avoid clipping and background music.
3. Put `1.wav`, `2.wav`, `3.wav`, `4.wav` in
   `resources/audio-sources/metronome/`. This folder's audio is Git-ignored and is
   never copied to the public website.
4. Install FFmpeg locally if needed, then run from the project root:

   ```sh
   python3 scripts/build-metronome-voices.py
   npm run production
   ```

The offline command prepares **only the original voice**, using Python 3 and
FFmpeg. It downmixes/resamples, trims outer silence without removing gaps inside a
word, matches short-word RMS with a peak ceiling, and fades the edges. It rejects
silent or overlong counts. See [FFmpeg's filter documentation](https://ffmpeg.org/ffmpeg-filters.html).
The four output files are mono 24 kHz / 16-bit PCM WAV, each at most about 58 KB.
WAV avoids codec delay and works with Safari's AudioBuffer decoder. Four typical
half-second counts total about 96 KB; effects require no further audio downloads.

Generated files and `manifest.json` live in `resources/audio/metronome/spoken/`.
Mix copies them to `public/audio/metronome/spoken/`. Publish matching generated
source/public files, config, code and versioned bundles through the normal Git
release. Source recordings themselves stay private. The manifest is published
only after a complete successful build; failure keeps the previous library.
Fingerprint filenames invalidate audio caches. Old generated filenames remain
available for already-open pages; prune obsolete versions separately after release.
To replace the voice, replace the four source files and rerun the same command.

## Implementation

- `resources/data/metronome-voice-effects.json`: labels, icons and effect parameters.
  Additional styles can use the same supported pitch, tempo, filter, brightness,
  modulation and short-echo parameters. Use an icon already in the shared catalog.
- `voice-effects.js`: pure DSP. Resampling shifts pitch; waveform-matched time
  stretching restores word duration. Filters, gentle modulation and short echoes
  distinguish styles, followed by source-relative normalization and edge fades.
- `voice-effects-worker.js`: processes four short clips off the UI/audio timing
  thread. Transferred copies leave source buffers intact. Only the current result
  is retained; the worker terminates after each job. Another choice cancels it.
- `voice-effect-selection.js`: page-session selection, source identity checks,
  cancellation, stale-result protection and Classic fallback. BPM/appearance edits
  do not regenerate effects. Effects are compressed for fast tempos by the existing
  clock's once-per-tempo preparation, never inside beat scheduling.
- `spoken-voices.js` and `Services/Metronome/SpokenVoices`: optional public original
  library discovery, bounded lazy loading, decode-once caching and stale selection
  protection. The server lists only complete local assets. Failed fetch/decode
  returns to Click and disables the failed option for that page visit.
- `clock.js`: private and public source slots stay separate; both use decoded
  AudioBuffers scheduled against AudioContext time. Changing effects preserves the
  beat timeline, while restarting begins at One. No regular HTML audio or timer-
  only spoken-beat timing is used. Short gain ramps prevent overlapping clip edges.
- `settings-pages.js`: one Bootstrap bottom sheet, focused Sound/Rhythm/Appearance
  pages and a nested Voice effect picker, with parent navigation and focus return.
  Shared selection buttons, navigation rows and bottom-sheet header are represented
  in the Design System. All microphone/session/ownership protections remain.

## Verification and limits

```sh
node --test tests/js/metronome.test.cjs tests/js/metronome-voice.test.cjs tests/js/metronome-effects.test.cjs
python3 -m unittest discover -s tests/audio -v
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'Metronome|PublicProjectPagesTest'
npm run production
npm run test:build
git diff --check
```

Automated DSP checks use synthetic test signals only, never fabricated voice
recordings published as real options. They cover duration/pitch, level limits,
source immutability, session reset, worker cancellation, stale loads, cache reuse,
clock sequencing and independent private/public sources. Extreme compression at
240–300 BPM can reduce intelligibility; short, clearly articulated recordings
work best. Final listening/timbre tuning needs the real voice recordings. Physical
iPhone Safari microphone permissions, interruptions, Silent Mode and perceived
clarity remain device checks. Worker failure leaves the original voice available;
missing optional public recordings leave all existing clicks and private voices
working. No migration, production activation or external service is required.
