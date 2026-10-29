#!/usr/bin/env python3
"""Prepare the four original counting samples. Effects are temporary in the browser. No network, speech synthesis or uploads."""
import argparse
import array
import hashlib
import json
import math
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import wave

ROOT = Path(__file__).resolve().parents[1]
RATE = 24000


def run(command):
    result = subprocess.run(command, capture_output=True, check=False)
    if result.returncode:
        raise ValueError(result.stderr.decode(errors='replace')[-2000:])
    return result.stdout


def normalize_pcm(raw):
    """Trim only the outer silence; match short-word RMS, cap peaks and fade edges."""
    samples = array.array('h', raw)
    if sys.byteorder != 'little':
        samples.byteswap()
    if not samples:
        raise ValueError('No audio remains after processing')
    peak = max(abs(value) for value in samples)
    if peak < 100:
        raise ValueError('Recording is silent or too quiet')
    # A 5 ms RMS window retains consonants while discarding quiet effect tails.
    window = RATE // 200
    threshold = max(40, peak * 0.012)
    active = [i for i in range(0, len(samples), window)
              if math.sqrt(sum(v * v for v in samples[i:i + window]) / len(samples[i:i + window])) > threshold]
    if not active:
        raise ValueError('No spoken number detected')
    start = max(0, active[0] - RATE // 200)
    end = min(len(samples), active[-1] + window + RATE // 100)
    samples = samples[start:end]
    if not 0.06 <= len(samples) / RATE <= 1.2:
        raise ValueError('Each trimmed number must last 0.06–1.2 seconds; record a shorter, clear count')
    rms = math.sqrt(sum(v * v for v in samples) / len(samples))
    gain = min(32767 * 10 ** (-19 / 20) / rms, 32767 * 10 ** (-2 / 20) / max(abs(v) for v in samples))
    fade = RATE // 200
    for i, value in enumerate(samples):
        samples[i] = round(value * gain * min(1, i / fade, (len(samples) - 1 - i) / fade))
    if sys.byteorder != 'little':
        samples.byteswap()
    return samples.tobytes()


def build(source, output, definitions, ffmpeg='ffmpeg'):
    spec = json.loads(definitions.read_text())
    if spec.get('beats') != 4 or not spec.get('voices'):
        raise ValueError('This metronome requires four counts and at least one voice')
    ids = [voice['id'] for voice in spec['voices']]
    if len(set(ids)) != len(ids) or any(not re.fullmatch('[a-z][a-z0-9-]{0,39}', id) for id in ids):
        raise ValueError('Voice IDs must be unique, short lowercase slugs')
    sources = [source / f'{number}.wav' for number in range(1, 5)]
    missing = [str(path) for path in sources if not path.is_file()]
    if missing:
        raise ValueError('Add your four original recordings first. Missing: ' + ', '.join(missing))
    for path in sources:
        with wave.open(str(path), 'rb') as recording:
            if (recording.getcomptype() != 'NONE' or recording.getnchannels() not in (1, 2)
                    or recording.getsampwidth() not in (2, 3, 4)
                    or recording.getframerate() not in (24000, 44100, 48000)
                    or not 0.06 <= recording.getnframes() / recording.getframerate() <= 5):
                raise ValueError(f'{path.name}: use a PCM WAV, 24/44.1/48 kHz, mono/stereo, under five seconds')
    output.mkdir(parents=True, exist_ok=True)
    # Stage everything before publishing a manifest. Failed builds keep the last library usable.
    with tempfile.TemporaryDirectory(prefix='.build-', dir=output) as temporary:
        stage = Path(temporary)
        manifest = {'version': 1, 'beats': 4, 'voices': []}
        for voice in spec['voices']:
            files = []
            for number, original in enumerate(sources, 1):
                # Normalize the original only. Never write effect variations to disk.
                raw = run([ffmpeg, '-nostdin', '-hide_banner', '-loglevel', 'error', '-i', str(original),
                           '-map', '0:a:0', '-af', 'highpass=f=70',
                           '-ar', str(RATE), '-ac', '1', '-f', 's16le', '-'])
                pcm = normalize_pcm(raw)
                temporary_wav = stage / f'{voice["id"]}-{number}.wav'
                with wave.open(str(temporary_wav), 'wb') as recording:
                    recording.setparams((1, 2, RATE, 0, 'NONE', 'not compressed'))
                    recording.writeframes(pcm)
                digest = hashlib.sha256(temporary_wav.read_bytes()).hexdigest()[:16]
                name = f'{voice["id"]}-{number}-{digest}.wav'
                temporary_wav.rename(stage / name)
                files.append(name)
            manifest['voices'].append({'id': voice['id'], 'files': files})
        for path in stage.iterdir():
            path.replace(output / path.name)
        (stage / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
        (stage / 'manifest.json').replace(output / 'manifest.json')
    return manifest


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', type=Path, default=ROOT / 'resources/audio-sources/metronome')
    parser.add_argument('--output', type=Path, default=ROOT / 'resources/audio/metronome/spoken')
    parser.add_argument('--config', type=Path, default=ROOT / 'config/metronome-voices.json')
    parser.add_argument('--ffmpeg', default='ffmpeg')
    args = parser.parse_args()
    try:
        manifest = build(args.source, args.output, args.config, args.ffmpeg)
    except (ValueError, OSError, wave.Error, KeyError) as error:
        parser.exit(1, f'{error}\nNo new voice library was published.\n')
    print(f'Built {len(manifest["voices"])} voices × 4 counts in {args.output}. Run npm run production to publish locally.')


if __name__ == '__main__':
    main()
