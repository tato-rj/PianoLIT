// Full, real web-app screenshots inside the same portrait-tablet frame.
// Capture masters stay separate from public email exports; never redraw UI.
const fs = require('fs');
const path = require('path');
const sharp = require(process.env.PIANOLIT_SHARP_MODULE || 'sharp');
const root = path.resolve(__dirname, '..');
const output = path.join(root, 'resources/images/emails/welcome');

function tablet(screen) {
    return `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="600" height="800" viewBox="0 0 600 800">
    <defs><clipPath id="screen"><rect x="48" y="62" width="504" height="672" rx="12"/></clipPath></defs>
    <rect x="32" y="27" width="536" height="750" rx="30" fill="#212529" opacity=".12"/>
    <rect x="32" y="22" width="536" height="750" rx="30" fill="#202630" stroke="#aeb6c2" stroke-width="2"/>
    <circle cx="300" cy="42" r="3" fill="#6c757d"/>
    <image x="48" y="62" width="504" height="672" clip-path="url(#screen)" xlink:href="data:image/jpeg;base64,${screen.toString('base64')}"/>
    <rect x="264" y="752" width="72" height="4" rx="2" fill="#adb5bd"/>
    </svg>`;
}

(async () => {
    for (const name of ['performance', 'synthesia', 'recordings', 'scores']) {
        const capture = fs.readFileSync(path.join(root, `resources/email-sources/membership/${name}-tablet.jpg`));
        const metadata = await sharp(capture).metadata();
        if (metadata.width !== 768 || metadata.height !== 1024) {
            throw new Error(`${name}: expected an intact 768×1024 tablet viewport capture`);
        }
        const file = path.join(output, `feature-${name}.png`);
        await sharp(Buffer.from(tablet(capture)), {density: 144}).resize(432, 576)
            .png({compressionLevel: 9, palette: true, colours: 256, effort: 10}).toFile(file);
        console.log(path.relative(root, file));
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
