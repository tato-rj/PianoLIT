// Ship view literals plus the explicit catalog for dynamically selected icons.
const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '..');

function discoverNames(source, available, aliases) {
    // Also cover @button(['icon' => '…']) and inline PHP render calls in views.
    const literals = /(?:@icon\s*\(\s*|Icon::render\s*\(\s*|['"]icon['"]\s*=>\s*)['"]([a-z0-9-]+)['"]/g;
    const names = new Set();
    let match;
    while ((match = literals.exec(source))) {
        const name = aliases[match[1]] || match[1];
        if (available.has(name)) names.add(name);
    }
    return Array.from(names);
}

function build() {
    const directory = path.join(root, 'node_modules/lucide-static/icons');
    const available = new Set(fs.readdirSync(directory).filter(file => file.endsWith('.svg')).map(file => file.slice(0, -4)));
    const aliases = require('../resources/icons/aliases.json');
    const names = new Set(require('../resources/icons/names.json'));
    function scan(directory) {
        fs.readdirSync(directory, {withFileTypes: true}).forEach(entry => {
            const file = path.join(directory, entry.name);
            if (entry.isDirectory()) scan(file);
            else if (entry.isFile() && entry.name.endsWith('.blade.php')) {
                discoverNames(fs.readFileSync(file, 'utf8'), available, aliases).forEach(name => names.add(name));
            }
        });
    }
    scan(path.join(root, 'resources/views'));
    const icons = {};
    Array.from(names).sort().forEach(name => {
        if (!/^[a-z0-9-]+$/.test(name)) throw new Error('Invalid icon name: ' + name);
        const svg = fs.readFileSync(path.join(directory, name + '.svg'), 'utf8');
        const body = svg.match(/<svg\b[^>]*>([\s\S]*?)<\/svg>/);
        if (!body) throw new Error('Invalid Lucide SVG: ' + name);
        icons[name] = body[1].trim().replace(/\s+/g, ' ');
    });
    fs.writeFileSync(path.join(root, 'resources/icons/lucide.json'), JSON.stringify(icons, null, 2) + '\n');
    // PianoLIT artwork stays separate from the generated third-party catalog.
    const customDirectory = path.join(root, 'resources/icons/custom');
    const custom = {};
    fs.readdirSync(customDirectory).filter(file => file.endsWith('.svg')).sort().forEach(file => {
        const name = file.slice(0, -4);
        if (!/^[a-z0-9-]+$/.test(name) || available.has(name)) throw new Error('Invalid custom icon name: ' + name);
        const svg = fs.readFileSync(path.join(customDirectory, file), 'utf8');
        const body = svg.match(/<svg\b[^>]*viewBox="0 0 24 24"[^>]*>([\s\S]*?)<\/svg>/);
        if (!body) throw new Error('Custom icons must use a 24 × 24 viewBox: ' + name);
        custom[name] = body[1].replace(/<!--[\s\S]*?-->/g, '').trim().replace(/\s+/g, ' ');
    });
    fs.writeFileSync(path.join(root, 'resources/icons/custom.json'), JSON.stringify(custom, null, 2) + '\n');
    fs.copyFileSync(path.join(root, 'node_modules/lucide-static/LICENSE'), path.join(root, 'resources/icons/LUCIDE-LICENSE'));
    console.log('Generated ' + names.size + ' Lucide icons and ' + Object.keys(custom).length + ' custom icons.');
}

module.exports = {discoverNames};
if (require.main === module) build();
