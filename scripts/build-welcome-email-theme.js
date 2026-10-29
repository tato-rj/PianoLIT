const fs = require('fs');
const path = require('path');
const sass = require('sass');

const root = path.resolve(__dirname, '..');
for (const [source, destination] of [
    ['welcome.scss', 'resources/views/emails/components/html/themes/welcome.css'],
    ['default.scss', 'resources/views/vendor/mail/html/themes/default.css'],
]) {
    const result = sass.compile(path.join(root, 'resources/sass/emails', source), {style: 'expanded'});
    // Preserve the existing transactional body styles byte for byte; only the
    // shared branding is compiled into both themes.
    const legacy = source === 'default.scss' ? fs.readFileSync(path.join(root, 'resources/sass/emails/legacy.css'), 'utf8') + '\n' : '';
    fs.writeFileSync(path.join(root, destination), legacy + result.css + '\n');
}
