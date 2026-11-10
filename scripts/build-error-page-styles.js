const fs = require('fs');
const path = require('path');
const sass = require('sass');

const root = path.resolve(__dirname, '..');
const result = sass.compile(path.join(root, 'resources/sass/errors.scss'), {
    style: 'compressed',
    loadPaths: [path.join(root, 'node_modules')],
    quietDeps: true,
});
// Embed the small stylesheet so recovery still works when external assets fail.
fs.writeFileSync(path.join(root, 'resources/views/errors/styles.css'), result.css + '\n');
