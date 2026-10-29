// Raster email exports of the project's existing brand/Lucide icons and preview art.
const fs = require('fs');
const path = require('path');
const {execFileSync} = require('child_process');
const sharp = require(process.env.PIANOLIT_SHARP_MODULE || 'sharp');
const root = path.resolve(__dirname, '..');
const destination = path.join(root, 'resources/images/emails/welcome');
(async () => {
    const platformIcon = fs.readFileSync(path.join(root, 'resources/icons/custom/pianolit.svg'), 'utf8')
        .replaceAll('currentColor', '#ffffff');
    await sharp(Buffer.from(platformIcon), {density: 384}).resize(80, 80).png()
        .toFile(path.join(destination, 'pianolit-white.png'));
    if (process.argv.includes('--button-icon-only')) return;

    for (const name of ['video', 'piano', 'headphones', 'file-music']) {
        const icon = name === 'piano' ? 'keyboard-music' : name;
        const source = fs.readFileSync(path.join(root, `node_modules/lucide-static/icons/${icon}.svg`), 'utf8')
            .replace('stroke="currentColor"', 'stroke="#0055fe"')
            .replace('stroke-width="2"', 'stroke-width="1.7"');
        await sharp(Buffer.from(source), {density: 480}).resize(160, 160).png()
            .toFile(path.join(destination, `premium-${name}.png`));
    }
    for (const [name, period] of [['prelude', 'romantic'], ['arabesque', 'impressionist'], ['bagatelle', 'classical']]) {
        execFileSync('magick', [path.join(root, `resources/images/backgrounds/periods/${period}/01.jpg`), '-resize', '480x300^', '-gravity', 'center', '-extent', '480x300', '-strip', '-quality', '85', path.join(destination, `preview-${name}.jpg`)]);
    }
    fs.copyFileSync(path.join(root, 'node_modules/lucide-static/LICENSE'), path.join(destination, 'LUCIDE-LICENSE.txt'));
})().catch(error => { console.error(error); process.exitCode = 1; });
