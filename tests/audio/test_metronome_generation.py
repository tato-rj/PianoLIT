import importlib.util
import json
import math
from pathlib import Path
import shutil
import struct
import tempfile
import unittest
import wave

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('builder', ROOT / 'scripts/build-metronome-voices.py')
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)


class OriginalVoiceBuildTest(unittest.TestCase):
    def test_missing_recordings_do_not_publish_or_replace_anything(self):
        with tempfile.TemporaryDirectory() as temp:
            output = Path(temp) / 'output'
            with self.assertRaisesRegex(ValueError, 'Missing'):
                builder.build(Path(temp), output, ROOT / 'config/metronome-voices.json')
            self.assertFalse(output.exists())

    @unittest.skipUnless(shutil.which('ffmpeg'), 'FFmpeg is required for the offline build smoke test')
    def test_original_only_is_normalized_bounded_fingerprinted_and_repeatable(self):
        with tempfile.TemporaryDirectory() as temp:
            source, output = Path(temp), Path(temp) / 'output'
            # Synthetic test signal only. Never publish these as a voice library.
            for number in range(1, 5):
                with wave.open(str(source / f'{number}.wav'), 'wb') as recording:
                    recording.setparams((1, 2, 24000, 0, 'NONE', 'not compressed'))
                    recording.writeframes(b''.join(struct.pack('<h', round(10000 * math.sin(i * 0.1)) if 2400 <= i < 12000 else 0) for i in range(14400)))
            manifest = builder.build(source, output, ROOT / 'config/metronome-voices.json')
            self.assertEqual([voice['id'] for voice in manifest['voices']], ['classic'])
            self.assertEqual(manifest, builder.build(source, output, ROOT / 'config/metronome-voices.json'))
            self.assertEqual(json.loads((output / 'manifest.json').read_text()), manifest)
            for file in manifest['voices'][0]['files']:
                self.assertLess((output / file).stat().st_size, 58000)
                with wave.open(str(output / file), 'rb') as recording:
                    self.assertEqual(recording.getparams()[:3], (1, 2, 24000))
                    self.assertLess(recording.getnframes(), 12000)
            (source / '4.wav').unlink()
            with self.assertRaises(ValueError):
                builder.build(source, output, ROOT / 'config/metronome-voices.json')
            self.assertEqual(json.loads((output / 'manifest.json').read_text()), manifest)


if __name__ == '__main__':
    unittest.main()
