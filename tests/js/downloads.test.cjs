const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

function run() {
    function load(saved, blocked = false) {
        const writes = [];
        const controls = ['list', 'small', 'large'].map(value => ({value, checked: value === 'large', events: {}, addEventListener(name, fn) { this.events[name] = fn; }}));
        const placeholder = {hidden: true};
        const image = {complete: true, naturalWidth: 0, hidden: false, events: {}, parentElement: {querySelector: () => placeholder}, addEventListener(name, fn) { this.events[name] = fn; }};
        const library = {querySelectorAll: selector => selector === '[data-download-view]' ? controls : [image]};
        vm.runInNewContext(fs.readFileSync(require.resolve('../../resources/js/views/downloads.js'), 'utf8'), {
            document: {querySelector: () => library},
            window: {localStorage: {getItem() { if (blocked) throw Error('Storage blocked'); return saved; }, setItem(key, value) { if (blocked) throw Error('Storage blocked'); writes.push([key, value]); }}}
        });
        return {controls, writes, image, placeholder};
    }
    const remembered = load('small');
    assert(remembered.controls[1].checked, 'Restores the saved valid view.');
    remembered.controls[0].checked = true;
    remembered.controls[0].events.change();
    assert.deepStrictEqual(remembered.writes, [['pianolit.downloads.view', 'list']]);
    assert(remembered.image.hidden && !remembered.placeholder.hidden, 'A failed cached cover gets the neutral fallback.');
    remembered.image.hidden = false;
    remembered.placeholder.hidden = true;
    remembered.image.events.error();
    assert(remembered.image.hidden && !remembered.placeholder.hidden, 'A later image failure also gets the fallback.');
    const invalid = load('unrecognized');
    assert.deepStrictEqual(invalid.controls.map(control => control.checked), [false, false, true]);
    const blocked = load(null, true);
    assert.doesNotThrow(() => blocked.controls[2].events.change());
    assert(blocked.controls[2].checked);
    console.log('Passed: library view preference, unavailable storage and failed-cover recovery.');
}

module.exports = run;
if (require.main === module) run();
