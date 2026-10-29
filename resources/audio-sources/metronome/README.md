# Original counting recordings

Place your own `1.wav`, `2.wav`, `3.wav`, `4.wav` here. This folder is private to
local development, ignored by Git and never copied to the public website.

Record one clearly spoken number per file in a quiet room, at a consistent
microphone distance and volume. Use your natural voice without effects. Leave a
little silence on either side. Aim for 0.2–0.6 seconds of speech; the complete file
must be under five seconds. Export uncompressed PCM WAV, mono or stereo, 16-bit,
24-bit or 32-bit, at 24, 44.1 or 48 kHz. Avoid clipping, music and background noise.

From the project root, run:

```sh
python3 scripts/build-metronome-voices.py
npm run production
```

Requires Python 3 and FFmpeg. Only the original voice is published; effects are
created temporarily in the browser when chosen, and reset on refresh. See
[the complete workflow](../../../docs/METRONOME_VOICES.md).
