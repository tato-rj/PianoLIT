const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = function () {
    // Exercise the local image tiling branch with decoded PDF.js image kinds.
    // Browser QA separately renders the original Brahms PDF with a canvas cap.
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/vendor/pdfjs/pdf.js'), 'utf8');
    const view = fs.readFileSync(path.join(__dirname, '../../resources/views/webapp/piece/index.blade.php'), 'utf8');
    assert(source.includes("var pdfjsVersion = '2.3.200';"), 'Preserve PDF.js document/fingerprint compatibility');
    assert(view.includes("mix('js/vendor/pdfjs/pdf.js')"), 'Piece pages must use the patched, versioned renderer');
    assert(view.includes("pdfjs-dist@2.3.200/build/pdf.worker.min.js"), 'The renderer and worker versions must match');
    const start = source.indexOf('      // PianoLIT: scanned scores');
    const end = source.indexOf('\n      this.save();\n      ctx.scale', start);
    assert(start > 0 && end > start, 'The pinned renderer must retain its bounded image path');
    const paint = vm.runInNewContext('(function(imgData) { var width = imgData.width, height = imgData.height, ctx = this.ctx; ' +
        source.slice(start, end) + '\nreturn false; })', {_util: {ImageKind: {GRAYSCALE_1BPP: 1, RGB_24BPP: 2, RGBA_32BPP: 3}}});
    for (const [kind, components] of [[1, 0], [2, 3], [3, 4]]) {
        // Odd widths exercise packed 1-bit row padding and right/bottom edges.
        const width = 2051, height = 2560;
        const stride = components ? width * components : Math.ceil(width / 8);
        const data = Uint8Array.from({length: stride * height}, (_, i) => (i * 37 + Math.floor(i / stride)) % 256);
        const before = Buffer.from(data);
        const transforms = [], tiles = [];
        let depth = 0;
        const renderer = {
            save() { depth++; }, restore() { depth--; },
            ctx: {translate(x, y) { transforms.push([x, y]); }, scale(x, y) { transforms.push([x, y]); }},
            paintInlineImageXObject(tile) { tiles.push(tile); assert.strictEqual(depth, 1); }
        };
        paint.call(renderer, {width, height, kind, data});
        assert.strictEqual(tiles.length, 4);
        assert.strictEqual(depth, 0);
        assert(before.equals(Buffer.from(data)), 'Rendering must preserve cached PDF image bytes');
        tiles.forEach((tile, index) => {
            const x = index % 2 * 2048, y = Math.floor(index / 2) * 2048;
            assert.strictEqual(tile.width, Math.min(2048, width - x));
            assert.strictEqual(tile.height, Math.min(2048, height - y));
            assert(tile.width * tile.height <= 4194304, 'Every backing canvas stays below the iPad area limit');
            assert.strictEqual(tile.kind, kind);
            assert.deepStrictEqual(transforms[index * 2], [x / width, (height - y - tile.height) / height]);
            assert.deepStrictEqual(transforms[index * 2 + 1], [tile.width / width, tile.height / height]);
            const tileStride = components ? tile.width * components : Math.ceil(tile.width / 8);
            for (let row = 0; row < tile.height; row++) {
                const offset = (y + row) * stride + (components ? x * components : x / 8);
                assert(Buffer.from(tile.data.subarray(row * tileStride, (row + 1) * tileStride)).equals(before.subarray(offset, offset + tileStride)),
                    'Tile rows preserve pixel channels, padding and top-to-bottom order');
            }
        });
        renderer.paintInlineImageXObject = () => { throw new Error('Rendering failed'); };
        assert.throws(() => paint.call(renderer, {width, height, kind, data}), /Rendering failed/);
        assert.strictEqual(depth, 0, 'A failed tile restores the PDF graphics state');
    }
    assert.strictEqual(paint.call({}, {width: 600, height: 800, data: new Uint8Array(1)}), false,
        'Ordinary score images retain the original rendering path');
    console.log('Passed: bounded score image tiles, 1-bit padding, RGB/RGBA channels, placement, immutable source and failure cleanup.');
};
