const assert = require('assert');
const fs = require('fs');
const path = require('path');
const sass = require('sass');
const {pathToFileURL} = require('url');
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

function ruleValue(stylesheet, selector, property) {
    let found;
    stylesheet.walkRules(rule => {
        if (!postcss.list.comma(rule.selector).includes(selector)) return;
        rule.walkDecls(property, declaration => { found = declaration.value; });
    });
    return found;
}

// Compile every entry, including the separately appended shared utility layer.
// New Sass warnings must fail this check rather than disappear in child stats.
const sources = {};
for (const entry of ['app', 'admin', 'shared']) {
    const warnings = [];
    sources[entry] = sass.compile(path.join(root, 'resources/sass', entry + '.scss'), {
        verbose: true,
        logger: {
            warn(message, details) {
                const location = details.span ? details.span.url + ':' + (details.span.start.line + 1) : entry;
                warnings.push(location + ': ' + message);
            }
        },
        importers: [{
            findFileUrl(url) {
                return url.startsWith('~') ? pathToFileURL(path.join(root, 'node_modules', url.slice(1))) : null;
            }
        }]
    }).css;
    assert.deepStrictEqual(warnings, [], entry + ': Sass must compile without warnings');
}
console.log('Passed: app, admin and shared Sass compile without warnings.');

// Test the shipped output, since production's old cssnano longhand optimizer
// previously stripped border variables even though the Sass compiled correctly.
for (const entry of ['app', 'admin']) {
    const source = sources[entry];
    const built = fs.readFileSync(path.join(root, 'public/css', entry + '.css'), 'utf8');
    const builtStylesheet = postcss.parse(built);
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

    // Real cards, footer layouts, configurable actions and timeline/status badges
    // depend on these helpers even though they are not Bootstrap components.
    for (const [selector, property, expected] of [
        ['.flex-center', 'justify-content', 'center'],
        ['.d-apart', 'display', 'flex'],
        ['.py-6', 'padding-top', '4rem'],
        ['.my-7', 'margin-bottom', '5rem'],
        ['.absolute-center', 'position', 'absolute'],
        ['.btn-grey', 'background-color', '#9aa9b7'],
        ['.btn-red', 'background-color', '#e3342f'],
        ['.btn-teal-outline', 'border-color', '#4dc0b5'],
        ['.btn-teal-outline:hover', 'background-color', '#4dc0b5'],
        ['.alert-grey', 'background-color', '#f3f5f7'],
        ['.alert-yellow', 'border-color', '#f2da00'],
        ['.bg-blue-dark', 'background-color', '#1f70b4'],
        ['.custom-scroll::-webkit-scrollbar', 'width', '1em'],
    ]) {
        assert.strictEqual(ruleValue(builtStylesheet, selector, property), expected, entry + ': missing required ' + selector + ' ' + property);
    }
    console.log('Passed: ' + entry + ' keeps required layout, palette and interaction helpers.');

    // A focused URL tab panel must never acquire the browser's default outline.
    // Reject custom outlines too, so component-specific !important rules cannot revive it.
    builtStylesheet.walkDecls('outline', declaration => {
        assert(/^(?:none|0)(?:$|[a-z%])/.test(declaration.value), entry + ': visible outline returned in ' + declaration.parent.selector);
        if (declaration.prop === 'outline' && postcss.list.comma(declaration.parent.selector).includes('*')) {
            assert(declaration.important, entry + ': the native outline reset must override vendor states');
        }
    });
    assert(/^(?:none|0)$/.test(ruleValue(builtStylesheet, '*', 'outline')), entry + ': missing native outline reset');
    assert.strictEqual(rootValues['--bs-focus-ring-width'], '0', entry + ': Bootstrap focus halos are disabled');
    assert.strictEqual(rootValues['--bs-focus-ring-opacity'], '0', entry + ': Bootstrap focus halo opacity is disabled');
    for (const [selector, property] of [
        ['.btn', '--bs-btn-focus-box-shadow'],
        ['.btn-close', '--bs-btn-close-focus-shadow'],
        ['.accordion', '--bs-accordion-btn-focus-box-shadow'],
        ['.pagination', '--bs-pagination-focus-box-shadow'],
        ['.nav-link:focus-visible', 'box-shadow'],
        ['.form-range:focus::-webkit-slider-thumb', 'box-shadow'],
        ['.form-range:focus::-moz-range-thumb', 'box-shadow'],
        ['.focus-ring:focus', 'box-shadow'],
    ]) assert.strictEqual(ruleValue(builtStylesheet, selector, property), 'none', entry + ': focus halo returned on ' + selector);
    console.log('Passed: ' + entry + ' removes native/custom outlines and Bootstrap focus halos.');
}
