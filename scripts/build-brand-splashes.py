#!/usr/bin/env python3
"""Build opaque home-screen startup images from the approved vector icons.

Run npm run brand:splashes with ImageMagick 7. No native app code is changed.
"""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import zipfile

ROOT = Path(__file__).resolve().parents[1]
BRAND = ROOT / 'resources/images/brand'
PUBLIC = ROOT / 'public/images/brand'
PROFILES = json.loads((ROOT / 'resources/data/brand-splash-screens.json').read_text())
ICON_CSS_SIZE = 120


def artwork(icon, color, width, height, scale=1):
    # A constant 120 CSS-pixel mark stays centered and comfortably inside the
    # safe area in either orientation, including smaller phones and tablets.
    size = ICON_CSS_SIZE * scale
    x = (width - size) / 2
    y = (height - size) / 2
    # Keep the blue icon's outline one CSS pixel wide at every device scale.
    border_width = 512 / ICON_CSS_SIZE if color == '#ffffff' else 1
    border_inset = border_width / 2
    border_color = '#dee2e6' if color == '#ffffff' else color
    body = icon[icon.index('  <path'):icon.rindex('</svg>')]
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">
  <title>PianoLIT home-screen splash</title>
  <path fill="{color}" d="M0 0h{width}v{height}H0z"/>
  <defs><clipPath id="tile"><rect width="512" height="512" rx="102.4"/></clipPath></defs>
  <g transform="translate({x:g} {y:g}) scale({size / 512:g})">
    <g clip-path="url(#tile)">{body}</g>
    <rect x="{border_inset:g}" y="{border_inset:g}" width="{512 - border_width:g}" height="{512 - border_width:g}" rx="{102.4 - border_inset:g}" fill="none" stroke="{border_color}" stroke-width="{border_width:g}" stroke-opacity="1"/>
  </g>
</svg>
'''


if not shutil.which('magick'):
    raise SystemExit('ImageMagick 7 is required for brand:splashes.')

(BRAND / 'splash').mkdir(exist_ok=True)
(PUBLIC / 'splash').mkdir(parents=True, exist_ok=True)
with tempfile.TemporaryDirectory(prefix='pianolit-splash-') as directory:
    temporary = Path(directory) / 'splash.svg'
    for variant, color in [('app', '#ffffff'), ('admin', '#181e22')]:
        icon = (BRAND / f'{variant}-icon.svg').read_text()
        source = BRAND / f'{variant}-splash.svg'
        source.write_text(artwork(icon, color, 390, 844))
        shutil.copyfile(source, PUBLIC / source.name)
        readme = [f'PianoLIT {variant} home-screen splash screens',
                  'Opaque RGB PNGs; approved icon centered on the brand background.',
                  'These files are for installed web apps, not a native iOS app release.', '']
        files = []
        for profile in PROFILES:
            w, h, scale = profile['width'], profile['height'], profile['scale']
            for orientation in ('portrait', 'landscape'):
                width, height = (w * scale, h * scale) if orientation == 'portrait' else (h * scale, w * scale)
                name = f'{variant}-{width}x{height}.png'
                target = BRAND / 'splash' / name
                temporary.write_text(artwork(icon, color, width, height, scale))
                subprocess.run(['magick', '-background', color, str(temporary), '-resize', f'{width}x{height}!', '-alpha', 'off', '-strip', 'PNG24:' + str(target)], check=True)
                shutil.copyfile(target, PUBLIC / 'splash' / name)
                files.append(target)
                readme.append(f'{name}: {width} x {height} pixels; {w} x {h} CSS pixels; @{scale}x; {orientation}.')
        archive = BRAND / f'{variant}-splash-screens.zip'
        # Stable archive metadata keeps content versions repeatable.
        with zipfile.ZipFile(archive, 'w', compression=zipfile.ZIP_DEFLATED) as bundle:
            for target in files:
                info = zipfile.ZipInfo(target.name, (2026, 1, 1, 0, 0, 0))
                info.compress_type = zipfile.ZIP_DEFLATED
                bundle.writestr(info, target.read_bytes())
            info = zipfile.ZipInfo('README.txt', (2026, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            bundle.writestr(info, '\n'.join(readme) + '\n')
        shutil.copyfile(archive, PUBLIC / archive.name)
        print(f'Built {variant}: {len(files)} portrait/landscape PNGs, editable SVG and ZIP.', flush=True)
