#!/usr/bin/env python3
"""Rebuild PianoLIT web/install icons from the two approved SVG masters.

Requires ImageMagick 7 (`magick`). Run manually with npm run brand:icons;
ordinary frontend builds use the committed exports without this dependency.
"""
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
PUBLIC = ROOT / 'public'
BRAND = ROOT / 'resources/images/brand'
NS = {'s': 'http://www.w3.org/2000/svg'}
ET.register_namespace('', NS['s'])


def run(*args):
    subprocess.run(['magick', *map(str, args)], check=True)


def export(source, destination, size, opaque=False):
    args = [source, '-resize', size, '-strip']
    if opaque:
        args += ['-background', '#ffffff', '-alpha', 'remove', '-alpha', 'off']
    run(*args, ('PNG24:' if opaque else 'PNG32:') + str(destination))


def digest(path):
    return hashlib.sha256((PUBLIC / path).read_bytes()).hexdigest()[:16]


def rounded(svg):
    start = svg.index('  <path')
    return svg[:start] + '<defs><clipPath id="tile"><rect width="512" height="512" rx="102.4"/></clipPath></defs><g clip-path="url(#tile)">' + svg[start:].replace('</svg>', '</g></svg>')


def pinned(svg):
    master = ET.fromstring(svg)
    groups = master.findall('s:g', NS)
    paths = master.findall('s:path', NS)
    # One connected piano silhouette with transparent score pages, then score
    # lines and all six black keys. Safari uses opacity as its monochrome mask.
    body = 'M128 112h256q24 0 24 24v150h104v90H0v-90h104V136q0-24 24-24z'
    pages = paths[3].attrib['d'] + ' ' + paths[4].attrib['d']
    marks = ''.join(ET.tostring(node, encoding='unicode') for node in groups[1])
    keys = ''.join(ET.tostring(node, encoding='unicode') for node in groups[-1])
    return '<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512"><title>PianoLIT pinned tab</title><g fill="#000"><path fill-rule="evenodd" d="' + body + ' ' + pages + '"/>' + marks + keys + '</g></svg>\n'


if not shutil.which('magick'):
    raise SystemExit('ImageMagick 7 is required: install it before running brand:icons.')

with tempfile.TemporaryDirectory(prefix='pianolit-brand-') as directory:
    work = Path(directory)
    for variant, prefix, name, color in [
        ('app', '', 'PianoLIT', '#0055fe'),
        ('admin', 'admin-', 'PianoLIT Admin', '#ffffff'),
    ]:
        source = BRAND / f'{variant}-icon.svg'
        svg = source.read_text()
        square = work / f'{variant}-square.png'
        tile = work / f'{variant}-rounded.svg'
        tile.write_text(rounded(svg))
        rounded_png = work / f'{variant}-rounded.png'
        # Rasterize once per shape at 4x, then downsample every export.
        run('-background', 'none', '-density', '384', source, '-resize', '2048x2048', square)
        run('-background', 'none', '-density', '384', tile, '-resize', '2048x2048', rounded_png)
        for size in (16, 32):
            export(rounded_png, PUBLIC / f'{prefix}favicon-{size}x{size}.png', f'{size}x{size}')
        ico48 = work / f'{variant}-48.png'
        export(rounded_png, ico48, '48x48')
        run(ico48, PUBLIC / f'{prefix}favicon-32x32.png', PUBLIC / f'{prefix}favicon-16x16.png', PUBLIC / f'{prefix}favicon.ico')
        for filename, size in [('apple-touch-icon.png', 180), ('android-chrome-192x192.png', 192), ('android-chrome-512x512.png', 512), ('mstile-150x150.png', 150)]:
            export(square, PUBLIC / f'{prefix}{filename}', f'{size}x{size}', opaque=True)
        (PUBLIC / f'{prefix}safari-pinned-tab.svg').write_text(pinned(svg))
        if variant == 'app':
            export(square, BRAND / 'app-icon.png', '1238x1200!', opaque=True)
            export(square, BRAND / 'app-icon-ios-1024.png', '1024x1024', opaque=True)
            export(rounded_png, BRAND / 'icon-rounded.png', '1200x1200')
            start = svg.index('  <path')
            margin = svg[:start] + '<defs><clipPath id="outer"><rect width="512" height="512" rx="102.4"/></clipPath><clipPath id="inner"><rect width="512" height="512" rx="102.4"/></clipPath></defs><g clip-path="url(#outer)"><path fill="#fff" d="M0 0h512v512H0z"/><g transform="translate(36 36) scale(.859375)" clip-path="url(#inner)">' + svg[start:].replace('</svg>', '</g></g></svg>')
            (work / 'margin.svg').write_text(margin)
            run('-background', 'none', '-density', '384', work / 'margin.svg', '-resize', '1200x1200', '-strip', 'PNG32:' + str(BRAND / 'icon-rounded-margin.png'))
        else:
            export(square, BRAND / 'admin-icon.png', '512x512', opaque=True)
        for source_file in BRAND.glob('*'):
            if source_file.suffix in ('.svg', '.png'):
                shutil.copyfile(source_file, PUBLIC / 'images/brand' / source_file.name)
        icons = []
        for size in (192, 512):
            path = f'{prefix}android-chrome-{size}x{size}.png'
            icons.append({'src': '/' + path + '?v=' + digest(path), 'sizes': f'{size}x{size}', 'type': 'image/png', 'purpose': 'any'})
        manifest = {'name': name, 'short_name': name, 'description': 'Find music that inspires you.' if variant == 'app' else 'PianoLIT administration.', 'icons': icons, 'theme_color': color, 'background_color': '#181e22' if variant == 'admin' else '#ffffff', 'display': 'standalone'}
        manifest_name = 'site.webmanifest' if variant == 'app' else 'admin.site.webmanifest'
        (PUBLIC / manifest_name).write_text(json.dumps(manifest, indent=4) + '\n')
        tile_path = f'{prefix}mstile-150x150.png'
        browserconfig = 'browserconfig.xml' if variant == 'app' else 'admin.browserconfig.xml'
        (PUBLIC / browserconfig).write_text(f'''<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square150x150logo src="/{tile_path}?v={digest(tile_path)}"/>
            <TileColor>{color}</TileColor>
        </tile>
    </msapplication>
</browserconfig>
''')
print('Built both brand variants, browser/install assets and the opaque 1024 × 1024 iOS export.')
subprocess.run([sys.executable, str(ROOT / 'scripts/build-brand-splashes.py')], check=True)
