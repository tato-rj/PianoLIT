const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

function main() {
    const events = {}, documentEvents = {}, frames = [];
    const win = {innerHeight: 800, scrollY: 0, addEventListener: (name, callback) => { events[name] = callback; },
        requestAnimationFrame: callback => frames.push(callback)};
    const tops = [300, 900, 2600, 3650];
    const ids = ['intro', 'details', 'preferences', 'contact'];
    const links = ids.map(id => ({attributes: {}, getAttribute: () => '#'+id,
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; }}));
    const nav = {querySelectorAll: () => links};
    const doc = {documentElement: {scrollHeight: 4100}, querySelectorAll: () => [nav],
        addEventListener: (name, callback) => { documentEvents[name] = callback; },
        getElementById: id => ({getBoundingClientRect: () => ({top: tops[ids.indexOf(id)] - win.scrollY})})};
    const source = fs.readFileSync(require.resolve('../../resources/js/components/section-navigation.js'), 'utf8');
    vm.runInNewContext(source, {document: doc, window: win});
    function current() { return links.map((link, index) => link.attributes['aria-current'] === 'location' ? ids[index] : null).filter(Boolean); }
    function flush() { frames.splice(0).forEach(callback => callback()); }
    assert.deepStrictEqual(current(), ['intro'], 'The initial visible section is selected without a click.');
    win.scrollY = 1700; events.scroll(); events.scroll();
    assert.strictEqual(frames.length, 1, 'Scroll work runs at most once per animation frame.');
    flush(); assert.deepStrictEqual(current(), ['details'], 'A tall section stays selected while scrolling through its content.');
    win.scrollY = 2500; events.scroll(); flush();
    assert.deepStrictEqual(current(), ['preferences'], 'Manual scrolling updates the menu without clicking a link.');
    win.scrollY = 3200; events.scroll(); flush(); assert.deepStrictEqual(current(), ['preferences']);
    win.scrollY = 3300; events.scroll(); flush();
    assert.deepStrictEqual(current(), ['contact'], 'The short final section activates at the bottom of the page.');
    win.scrollY = 100; events.scroll(); flush(); assert.deepStrictEqual(current(), ['intro'], 'Scrolling back up clears the previous selection.');
    win.scrollY = 2500; events.pageshow(); flush(); assert.deepStrictEqual(current(), ['preferences'], 'Restored scroll positions update the selection.');
    tops[2] = 3000; events.resize(); flush(); assert.deepStrictEqual(current(), ['details'], 'Layout changes recalculate the active section.');
    tops[2] = 2600; documentEvents['shown.bs.collapse'](); flush();
    assert.deepStrictEqual(current(), ['preferences'], 'Expanding an FAQ updates the current section when its layout moves.');
    tops[2] = 3000; documentEvents['hidden.bs.collapse'](); flush();
    assert.deepStrictEqual(current(), ['details'], 'Collapsing an FAQ updates the current section.');
    vm.runInNewContext(source, {document: {querySelectorAll: () => []}, window: {}});
    console.log('Passed: shared section navigation on initial load, scrolling, restoration, resizing, FAQ layout changes and short final sections.');
}

module.exports = main;
if (require.main === module) main();
