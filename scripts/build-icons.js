// Only ship the icons PianoLIT uses. Add canonical Lucide names to names.json.
const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '..');
const names = require('../resources/icons/names.json');
const icons = {};
names.forEach(name => {
    if (!/^[a-z0-9-]+$/.test(name)) throw new Error('Invalid icon name: ' + name);
    const svg = fs.readFileSync(path.join(root, 'node_modules/lucide-static/icons', name + '.svg'), 'utf8');
    const body = svg.match(/<svg\b[^>]*>([\s\S]*?)<\/svg>/);
    if (!body) throw new Error('Invalid Lucide SVG: ' + name);
    icons[name] = body[1].trim().replace(/\s+/g, ' ');
});
fs.writeFileSync(path.join(root, 'resources/icons/lucide.json'), JSON.stringify(icons, null, 2) + '\n');
fs.copyFileSync(path.join(root, 'node_modules/lucide-static/LICENSE'), path.join(root, 'resources/icons/LUCIDE-LICENSE'));
console.log('Generated ' + names.length + ' Lucide icons.');
