const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const template = fs.readFileSync(path.join(__dirname, '../../resources/views/auth/google/button.blade.php'), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];
const documentEvents = {}, windowEvents = {};
const attrs = {}, label = {textContent: 'Continue with Google'};
const hidden = new Set(['d-none']);
const spinner = {classList: {add: value => hidden.add(value), remove: value => hidden.delete(value)}};
const link = {
    getAttribute: name => attrs[name], setAttribute: (name, value) => attrs[name] = value,
    removeAttribute: name => delete attrs[name],
    querySelector: selector => selector === '[data-google-label]' ? label : spinner
};
const document = {addEventListener: (name, fn) => documentEvents[name] = fn, querySelectorAll: () => [link]};
const window = {addEventListener: (name, fn) => windowEvents[name] = fn};
vm.runInNewContext(script, {document, window});
let prevented = 0;
const click = overrides => documentEvents.click(Object.assign({target: {closest: () => link}, button: 0, preventDefault: () => prevented++}, overrides));
click();
assert.strictEqual(attrs['aria-busy'], 'true');
assert.strictEqual(label.textContent, 'Opening Google…');
assert.strictEqual(hidden.has('d-none'), false);
click();
assert.strictEqual(prevented, 1, 'Duplicate navigation must be blocked.');
windowEvents.pageshow({persisted: true});
assert.strictEqual(attrs['aria-busy'], undefined);
assert.strictEqual(label.textContent, 'Continue with Google');
assert.strictEqual(hidden.has('d-none'), true, 'Back from a cancelled/failed navigation restores controls.');
for (const modifier of ['ctrlKey', 'metaKey', 'shiftKey', 'altKey']) click({[modifier]: true});
click({button: 1});
click({defaultPrevented: true});
click({target: {closest: () => null}});
assert.strictEqual(attrs['aria-busy'], undefined, 'Modified clicks and unrelated links keep native behavior.');
console.log('Google sign-in loading/back-navigation checks passed.');
