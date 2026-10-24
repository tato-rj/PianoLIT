#!/usr/bin/env python3
"""Compose the welcome assets from approved artwork and real product screenshots.

Requires ImageMagick 7. Reuses existing assets without generating artwork or UI.
The tool source is a local browser capture; crop excludes debug UI/test products.
"""
from pathlib import Path
import argparse
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / 'resources/images/emails/welcome'
parser = argparse.ArgumentParser(description=__doc__)
selection = parser.add_mutually_exclusive_group()
selection.add_argument('--logo-only', action='store_true', help='Rebuild only the floating logo, preserving the screenshot mockups.')
selection.add_argument('--platform-mockups-only', action='store_true', help='Rebuild only the web app/iOS device variants, preserving the website mockup.')
selection.add_argument('--badge-only', action='store_true', help='Export only the supplied Apple download badge for email use.')
selection.add_argument('--hubs-only', action='store_true', help='Export compact JPEG crops of the approved homepage hub artwork.')
args = parser.parse_args()


def magick(*args):
    subprocess.run(['magick', *map(str, args)], check=True)


def app_store_badge():
    # The supplied vector matches the existing approved Apple download artwork.
    magick('-background', 'none', '-density', '288', ROOT / 'resources/images/apple/download.svg',
           '-resize', '540x160!', '-strip', OUTPUT / 'app-store.png')


def hub_artwork():
    # Remove the homepage's empty heading space. JPEGs work in email clients
    # without WebP support, and 420px supplies a sharp source for the 160px rows.
    for name in ['learn', 'blog', 'metronome', 'shop', 'games']:
        top = 180 if name == 'metronome' else 240
        magick(ROOT / f'resources/images/home/editorial/{name}.webp',
               '-crop', f'720x720+0+{top}', '+repage', '-resize', '420x420',
               '-strip', '-sampling-factor', '4:2:0', '-quality', '85',
               OUTPUT / f'hub-{name}.jpg')


def rounded_screen(source, width, height, radius, output, temp):
    magick(source, '-resize', f'{width}x{height}!', temp / 'screen.png')
    magick('-size', f'{width}x{height}', 'xc:none', '-fill', 'white', '-draw',
           f'roundrectangle 0,0 {width - 1},{height - 1} {radius},{radius}', temp / 'mask.png')
    magick(temp / 'screen.png', temp / 'mask.png', '-alpha', 'off', '-compose',
           'CopyOpacity', '-composite', output)


def platform_mockups(temp):
    # Use the existing real tablet capture and native iOS phone artwork.
    rounded_screen(ROOT / 'resources/images/webapp/carousel/screens/piece-tablet.jpg',
                   394, 524, 10, temp / 'tablet-screen.png', temp)
    magick('-size', '430x572', 'xc:none', '-fill', '#202630', '-stroke', '#aeb6c2',
           '-strokewidth', '2', '-draw', 'roundrectangle 2,2 428,570 24,24',
           '-stroke', 'none', '-fill', '#6c757d', '-draw', 'circle 215,12 217,12',
           temp / 'tablet-screen.png', '-geometry', '+18+24', '-composite',
           temp / 'tablet.png')
    magick(ROOT / 'resources/images/mockup/main-view.png', '-crop', '264x538+318+70',
           '+repage', '-resize', '244x497!', temp / 'native-phone.png')
    magick('-size', '1200x620', 'xc:white', temp / 'tablet.png', '-geometry', '+228+24',
           '-composite', temp / 'native-phone.png', '-geometry', '+724+82', '-composite',
           '-strip', '-quality', '88', OUTPUT / 'tablet-iphone.jpg')

    # The supplied mobile web screenshot is inserted intact, without inventing
    # or reflowing its UI. Only the surrounding phone hardware is drawn.
    rounded_screen(ROOT / 'resources/email-sources/welcome/discover-mobile.png',
                   228, 454, 22, temp / 'web-phone-screen.png', temp)
    magick('-size', '244x497', 'xc:white', '-fill', '#202630', '-stroke', '#aeb6c2',
           '-strokewidth', '1', '-draw', 'roundrectangle 1,1 242,495 30,30',
           '-stroke', 'none', temp / 'web-phone-screen.png', '-geometry', '+8+20', '-composite',
           '-fill', '#6c757d', '-draw', 'circle 122,10 124,10',
           '-fill', '#eef0f3', '-draw', 'roundrectangle 90,484 154,487 2,2', temp / 'web-phone.png')
    # Start with the approved website composition so its browser stays the same.
    magick(OUTPUT / 'app-devices.jpg', '-fill', 'white', '-draw', 'rectangle 933,80 1181,581',
           temp / 'web-phone.png', '-geometry', '+935+82', '-composite', '-strip',
           '-quality', '88', OUTPUT / 'webapp-devices.jpg')


with tempfile.TemporaryDirectory(prefix='pianolit-welcome-') as temp:
    temp = Path(temp)
    if args.hubs_only:
        hub_artwork()
        raise SystemExit(0)
    if args.badge_only:
        app_store_badge()
        print(OUTPUT / 'app-store.png')
        raise SystemExit(0)
    if args.platform_mockups_only:
        platform_mockups(temp)
        print(OUTPUT / 'tablet-iphone.jpg')
        print(OUTPUT / 'webapp-devices.jpg')
        raise SystemExit(0)
    # Keep the approved icon upright, with the floating gap and soft floor shadow
    # baked in so email clients need no CSS transforms or filters.
    magick(ROOT / 'resources/images/brand/icon-rounded.png', '-resize', '288x288',
           temp / 'logo.png')
    # Trace the artwork's alpha edge, including the white keys, rather than
    # outlining the larger white canvas that also contains the floating shadow.
    magick(temp / 'logo.png', '-alpha', 'extract', '-virtual-pixel', 'black',
           '-morphology', 'EdgeIn', 'Disk:3', temp / 'logo-border-mask.png')
    magick('-size', '288x288', 'xc:#d7dde5', temp / 'logo-border-mask.png',
           '-alpha', 'off', '-compose', 'CopyOpacity', '-composite', temp / 'logo-border.png')
    magick(temp / 'logo.png', temp / 'logo-border.png', '-compose', 'Over', '-composite',
           temp / 'logo.png')
    magick('-size', '480x450', 'xc:white', '-fill', '#21252920', '-draw',
           'ellipse 240,370 120,10 0,360', '-blur', '0x9', temp / 'floor.png')
    magick(temp / 'floor.png', temp / 'logo.png', '-geometry', '+96+36', '-composite',
           '-strip', OUTPUT / 'floating-logo.png')

    if args.logo_only:
        print(OUTPUT / 'floating-logo.png')
        raise SystemExit(0)

    magick(ROOT / 'resources/images/webapp/carousel/screens/discover-desktop.jpg',
           '-resize', '900x506!', temp / 'desktop.png')
    # The center device in this existing marketing export is a real iOS screen.
    magick(ROOT / 'resources/images/mockup/main-view.png', '-crop', '264x538+318+70',
           '+repage', '-resize', '244x497!', temp / 'ios.png')
    magick('-size', '1200x620', 'xc:white', '-fill', '#e9edf3', '-draw',
           'roundrectangle 24,25 950,578 18,18', '-fill', '#f8f9fa', '-draw',
           'roundrectangle 27,28 947,575 16,16', '-fill', '#d7dde5', '-draw',
           'circle 46,44 49,44 circle 59,44 62,44 circle 72,44 75,44',
           temp / 'desktop.png', '-geometry', '+37+60', '-composite',
           temp / 'ios.png', '-geometry', '+935+82', '-composite', '-strip', '-quality', '88', OUTPUT / 'app-devices.jpg')

    magick(ROOT / 'resources/email-sources/welcome/theory.png', '-crop', '800x340+108+304', '+repage',
           '-resize', '1000x425!', temp / 'theory.png')
    magick('-size', '1200x540', 'xc:white', '-fill', '#e9edf3', '-draw',
           'roundrectangle 80,20 1120,510 18,18', '-fill', '#f8f9fa', '-draw',
           'roundrectangle 83,23 1117,507 16,16', '-fill', '#d7dde5', '-draw',
           'circle 106,41 109,41 circle 119,41 122,41 circle 132,41 135,41',
           temp / 'theory.png', '-geometry', '+100+66', '-composite', '-strip', '-quality', '88', OUTPUT / 'resource-browser.jpg')

    platform_mockups(temp)
    app_store_badge()
    hub_artwork()
    for name in ['floating-logo.png', 'app-devices.jpg', 'tablet-iphone.jpg', 'webapp-devices.jpg', 'resource-browser.jpg', 'app-store.png']:
        print(OUTPUT / name)
