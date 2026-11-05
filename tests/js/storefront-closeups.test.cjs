const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

module.exports = function () {
    function picture(complete, naturalWidth) {
        return {complete, naturalWidth, hidden: false, events: {}, addEventListener(name, callback) { this.events[name] = callback; }};
    }
    const ready = picture(true, 800), cachedFailure = picture(true, 0), loading = picture(false, 0);
    const root = {querySelectorAll(selector) { return selector === '[data-card-closeup]' ? [ready, cachedFailure, loading] : []; }};
    vm.runInNewContext(fs.readFileSync(require.resolve('../../resources/js/views/storefront.js'), 'utf8'), {
        document: {querySelector: () => root}
    });
    assert.equal(ready.hidden, false, 'An available close-up stays usable');
    assert.equal(cachedFailure.hidden, true, 'A cached broken close-up must leave the overview visible');
    assert.equal(loading.hidden, false, 'Lazy images are not discarded before loading');
    loading.events.error();
    assert.equal(loading.hidden, true, 'A later download failure restores the regular image');
    console.log('Passed: optional Shop close-up loading and broken-image fallback.');
};
