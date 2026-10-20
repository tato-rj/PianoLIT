const assert = require('assert');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

module.exports = function (root, manifest) {
    const publicRoot = path.join(root, 'public');
    const read = file => fs.readFileSync(path.join(publicRoot, file));
    function png(file, width, height, opaque = false) {
        const bytes = read(file);
        assert.strictEqual(bytes.subarray(1, 4).toString(), 'PNG', file + ': invalid PNG');
        assert.strictEqual(bytes.readUInt32BE(16), width, file + ': incorrect width');
        assert.strictEqual(bytes.readUInt32BE(20), height, file + ': incorrect height');
        if (opaque) assert.strictEqual(bytes[25], 2, file + ': install artwork must be RGB with no alpha channel');
    }
    png('images/brand/app-icon-ios-1024.png', 1024, 1024, true);
    for (const prefix of ['', 'admin-']) {
        for (const size of [16, 32]) png(prefix + `favicon-${size}x${size}.png`, size, size);
        png(prefix + 'apple-touch-icon.png', 180, 180, true);
        png(prefix + 'mstile-150x150.png', 150, 150, true);
        const ico = read(prefix + 'favicon.ico');
        assert.strictEqual(ico.readUInt16LE(2), 1, 'Expected Windows icon format');
        assert.strictEqual(ico.readUInt16LE(4), 3, 'Expected all three fallback icon sizes');
        assert.deepStrictEqual([6, 22, 38].map(offset => ico[offset]), [48, 32, 16]);
        const filename = prefix ? 'admin.site.webmanifest' : 'site.webmanifest';
        const app = JSON.parse(read(filename));
        assert.strictEqual(app.name, prefix ? 'PianoLIT Admin' : 'PianoLIT');
        assert.strictEqual(app.theme_color, prefix ? '#ffffff' : '#0055fe');
        assert.strictEqual(app.icons.length, 2);
        for (const icon of app.icons) {
            const size = Number(icon.sizes.split('x')[0]);
            assert([192, 512].includes(size));
            const file = prefix + `android-chrome-${size}x${size}.png`;
            png(file, size, size, true);
            const hash = crypto.createHash('sha256').update(read(file)).digest('hex').slice(0, 16);
            assert.strictEqual(icon.src, '/' + file + '?v=' + hash, 'Stale installed icon URL');
        }
        const tile = prefix + 'mstile-150x150.png';
        const hash = crypto.createHash('sha256').update(read(tile)).digest('hex').slice(0, 16);
        assert(read(prefix ? 'admin.browserconfig.xml' : 'browserconfig.xml').includes(Buffer.from('/' + tile + '?v=' + hash)));
    }
    for (const file of require('../../scripts/brand-assets')) {
        const key = file.replace(/^public/, '');
        assert(manifest[key] && manifest[key].includes('?id='), 'Unversioned brand asset: ' + key);
    }
    console.log('Passed: iOS/install icon sizes and opacity, ICO fallbacks, current manifest/Windows artwork URLs and versioned brand assets.');
};
