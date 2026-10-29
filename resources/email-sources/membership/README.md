# Membership feature captures

Captured from Arthur's open, signed-in PianoLIT web app tab on October 29, 2026,
using the actual page at a 768×1024 tablet viewport. Each JPEG retains the whole
viewport, including the piece artwork, title, tabs and bottom navigation.

| Capture | Live page and state |
| --- | --- |
| `performance-tablet.jpg` | `https://my.pianolit.com/pieces/1180`, About, performance paused with the pianist's hands visible |
| `synthesia-tablet.jpg` | `https://my.pianolit.com/pieces/960`, Synthesia, paused with falling notes visible |
| `recordings-tablet.jpg` | `https://my.pianolit.com/pieces/1180`, Score with the real Listen player open and paused |
| `scores-tablet.jpg` | `https://my.pianolit.com/pieces/1180`, Score with the PDF and its controls loaded |

The captures show public piece content and no account menu or personal details.
They are source masters, outside the public image directory. Do not replace them
with cropped players, redrawn controls, old promotional artwork or invented UI.
Future updates should recapture the complete device viewport from the web app.

`scripts/build-email-feature-mockups.js` adds only a portrait-tablet hardware
frame and exports transparent 432×576px PNGs for email. It validates the source
dimensions and preserves each screen's proportions and layout. Set
`PIANOLIT_SHARP_MODULE` if using `sharp` from the bundled workspace runtime.
