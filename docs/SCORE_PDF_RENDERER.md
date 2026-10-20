# Web score PDF renderer

Piece pages use a local, versioned copy of Mozilla PDF.js **2.3.200** at
`resources/js/vendor/pdfjs/pdf.js`. Its upstream source is
https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.js (Apache 2.0; the
original license notice is retained). Laravel Mix copies it to
`public/js/vendor/pdfjs/pdf.js` and versions that URL. The piece Blade view
explicitly selects the matching 2.3.200 CDN worker; deploy the view, renderer,
score-editor JavaScript and Mix manifest together.

This is the same PDF.js release previously loaded from the CDN. It contains one
local change in `CanvasGraphics.paintInlineImageXObject`, marked `PianoLIT`:
decoded images above 5,242,880 pixels render as at most 2,048 × 2,048 tiles.
Packed grayscale, RGB and RGBA image data retain their original coordinates and
resolution. Tile widths are byte aligned for 1-bit scans. The existing renderer
handles each tile, including downscaling and graphics-state cleanup.

The reported Brahms Op. 118 No. 2 PDF (piece 1180) has four A4 pages with
4,964 × 7,020-pixel, 1-bit scans. The old renderer allocated a 34,847,280-pixel
intermediate canvas despite the editor's smaller final canvas. This exceeds the
16,777,216-pixel iPad Safari limit documented in
[Mozilla issue 9474](https://github.com/mozilla/pdf.js/issues/9474).
Reducing the editor's display zoom alone does not avoid this intermediate.

The editor also resets its detached render buffer to zero dimensions after
success or failure, releasing backing memory between page turns and zooms.

The local renderer applies to full web scores, blurred first-page previews and
web printing on piece pages. PDF bytes, original URLs, downloads, PDF.js
fingerprints, account-owned annotations and mobile delivery are unchanged.
Other PDF.js consumers retain their existing includes. Future library upgrades
must verify scan rendering under a canvas cap and compatibility with existing
annotation fingerprints; do not discard this fix without that check.

`tests/js/score-pdf-images.js` covers tile bounds, packed row padding, color
channels, orientation, source immutability and failure cleanup. The score-editor
regressions cover buffer disposal, including failures and queued zoom renders.
Browser QA used the actual downloaded Brahms PDF and the exact old/new renderer,
with all remote requests blocked and a simulated 16,777,216-pixel canvas limit.
A physical iPad check remains necessary after deployment.
