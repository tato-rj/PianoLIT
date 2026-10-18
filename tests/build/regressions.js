const assert = require('assert');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '../..');
const publicRoot = path.join(root, 'public');
const manifest = JSON.parse(fs.readFileSync(path.join(publicRoot, 'mix-manifest.json'), 'utf8'));
const entryPoints = ['css/app.css', 'css/admin.css', 'css/shared.css', 'js/app.js', 'js/admin.js', 'js/tone.js'];

function filesIn(directory) {
    return fs.readdirSync(directory, {withFileTypes: true}).flatMap(entry => {
        if (entry.name.startsWith('.')) return [];
        const file = path.join(directory, entry.name);
        return entry.isDirectory() ? filesIn(file) : [file];
    });
}

// Check the contract Laravel actually consumes, including literal mix() calls.
const requiredAssets = new Set(entryPoints);
for (const file of filesIn(path.join(root, 'resources/views'))) {
    if (!file.endsWith('.blade.php')) continue;
    const source = fs.readFileSync(file, 'utf8');
    for (const match of source.matchAll(/\bmix\(\s*['"]([^'"]+)['"]/g)) requiredAssets.add(match[1]);
}
for (const file of requiredAssets) {
    assert(manifest['/' + file], 'Missing Laravel asset: ' + file);
    assert.strictEqual(manifest['/' + file].split('?')[0], '/' + file, 'Changed asset path: ' + file);
    assert(fs.statSync(path.join(publicRoot, file)).size > 0, 'Empty Laravel asset: ' + file);
}

let versioned = 0;
for (const [asset, url] of Object.entries(manifest)) {
    if (!url.includes('?id=')) continue;
    const hash = crypto.createHash('md5').update(fs.readFileSync(path.join(publicRoot, asset))).digest('hex');
    assert.strictEqual(url, asset + '?id=' + hash, 'Stale version after concatenation/minification: ' + asset);
    versioned++;
}
for (const file of entryPoints) assert(manifest['/' + file].includes('?id='), 'Unversioned entry point: ' + file);
for (const file of entryPoints.filter(file => file.endsWith('.js'))) {
    new vm.Script(fs.readFileSync(path.join(publicRoot, file), 'utf8'), {filename: file});
}

// These are raw copies: verify every byte, including binary design sources.
let copied = 0;
for (const [source, destination] of [
    ['resources/js/vendor', 'js/vendor'],
    ['resources/js/views', 'js/views'],
    ['resources/js/components', 'js/components'],
    ['resources/js/tinyeditor', 'js/tinyeditor'],
    ['resources/images', 'images'],
    ['resources/audio/metronome', 'audio/metronome'],
    ['node_modules/flag-icon-css/flags', 'css/vendor/flags'],
]) {
    const directory = path.join(root, source);
    for (const file of filesIn(directory)) {
        const target = path.join(publicRoot, destination, path.relative(directory, file));
        assert(fs.readFileSync(file).equals(fs.readFileSync(target)), 'Changed copy: ' + target);
        copied++;
    }
}
for (const [source, target] of [
    ['resources/data/composer-globe.geojson', 'data/composer-globe.geojson'],
    ['resources/fonts/escore/LibreBodoni-Regular.ttf', 'fonts/escore/LibreBodoni-Regular.ttf'],
    ['resources/fonts/escore/LibreBodoni-Bold.ttf', 'fonts/escore/LibreBodoni-Bold.ttf'],
    ['resources/fonts/escore/OFL.txt', 'fonts/escore/OFL.txt'],
    ['node_modules/flag-icon-css/css/flag-icon.min.css', 'css/vendor/flag-icon/flag-icon.min.css'],
]) assert(fs.readFileSync(path.join(root, source)).equals(fs.readFileSync(path.join(publicRoot, target))), 'Changed copy: ' + target);

// Fonts, SVG arrows and score fonts must still resolve after Sass URL rewriting.
for (const file of entryPoints.filter(file => file.endsWith('.css'))) {
    const source = fs.readFileSync(path.join(publicRoot, file), 'utf8');
    for (const match of source.matchAll(/url\(\s*['"]?([^)'"\s]+)['"]?\s*\)/g)) {
        const url = match[1];
        if (/^(?:data:|https?:|\/\/|#)/.test(url)) continue;
        const target = url.startsWith('/') ? path.join(publicRoot, url.split('?')[0]) : path.resolve(publicRoot, path.dirname(file), url.split('?')[0]);
        assert(fs.existsSync(target), file + ': broken CSS URL ' + url);
    }
}
console.log(`Passed: ${requiredAssets.size} Laravel asset paths, ${versioned} final-content hashes, JavaScript syntax, ${copied + 5} exact copies and CSS asset URLs.`);
