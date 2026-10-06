const assert = require('assert');
const fs = require('fs');
const path = require('path');
const sass = require('node-sass');
const postcss = require('postcss');

const root = path.resolve(__dirname, '../..');
function variables(css) {
    const names = new Set();
    postcss.parse(css).walkDecls(declaration => {
        if (declaration.prop.startsWith('--bs-') && declaration.value.trim()) {
            names.add(declaration.prop);
        }
    });
    return names;
}

// Test the shipped output, since production's old cssnano longhand optimizer
// previously stripped border variables even though the Sass compiled correctly.
for (const entry of ['app', 'admin']) {
    const source = sass.renderSync({
        file: path.join(root, 'resources/sass', entry + '.scss'),
        importer(url) {
            return url.startsWith('~') ? {file: path.join(root, 'node_modules', url.slice(1))} : null;
        }
    }).css.toString();
    const built = fs.readFileSync(path.join(root, 'public/css', entry + '.css'), 'utf8');
    const shippedVariables = variables(built);
    const missing = [...variables(source)].filter(name => !shippedVariables.has(name));
    assert.deepStrictEqual(missing, [], entry + ': production removed Bootstrap CSS variables');

    const rootValues = {};
    postcss.parse(built).walkRules(rule => {
        if (!rule.selector.split(',').some(selector => selector.trim() === ':root')) return;
        rule.walkDecls(declaration => { rootValues[declaration.prop] = declaration.value; });
    });
    assert.strictEqual(rootValues['--bs-border-width'], '1px', entry + ': missing border width');
    assert.strictEqual(rootValues['--bs-border-style'], 'solid', entry + ': missing border style');
    assert.strictEqual(rootValues.border, undefined, entry + ': minifier created a page border');
    postcss.parse(built).walkRules(rule => {
        rule.selector.split(',').filter(selector => selector.includes('.form-check-input') && selector.includes(':indeterminate'))
            .forEach(selector => assert(/\[type=["']?checkbox["']?\]/.test(selector), entry + ': indeterminate color must not fill unselected radios'));
    });
    console.log('Passed: ' + entry + ' production preserves ' + shippedVariables.size + ' Bootstrap CSS variables.');
}
