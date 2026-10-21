const fs = require('fs');
const path = require('path');
const sass = require('sass');

const root = path.resolve(__dirname, '..');
const result = sass.compile(path.join(root, 'resources/sass/emails/welcome.scss'), {style: 'expanded'});
fs.writeFileSync(path.join(root, 'resources/views/emails/components/html/themes/welcome.css'), result.css + '\n');
