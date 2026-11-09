const assert = require('assert');
const init = require('../../resources/js/components/webapp-navigation');

function setup() {
    const events = {}, menuEvents = {}, documentEvents = {}, frames = [], values = {}, classes = new Set();
    let clock = 0, pinned = false, height = 68, bottom = '12px';
    const mobile = {matches: true};
    const menu = {dataset: {}, contains: element => element === menu,
        addEventListener: (name, callback) => { menuEvents[name] = callback; },
        getBoundingClientRect: () => ({height}),
        classList: {toggle(name, on) { if (on) classes.add(name); else classes.delete(name); }}};
    const doc = {activeElement: null, documentElement: {scrollHeight: 4800, style: {setProperty(name, value) { values[name] = value; }}},
        querySelector: selector => selector === '[data-webapp-navigation]' ? menu : (pinned ? {} : null),
        getElementById: () => null, addEventListener: (name, callback) => { documentEvents[name] = callback; }};
    const win = {document: doc, scrollY: 0, innerHeight: 800, performance: {now: () => clock},
        matchMedia: () => mobile, getComputedStyle: () => ({bottom}),
        addEventListener: (name, callback) => { events[name] = callback; },
        requestAnimationFrame: callback => { frames.push(callback); return frames.length; }};
    const api = init(win);
    return {win, doc, menu, api, mobile, events, menuEvents, documentEvents, values, frames,
        hidden: () => classes.has('is-scrolled-away'),
        pin: value => { pinned = value; },
        dimensions: (h, b) => { height = h; bottom = b; api.measure(); },
        scroll(y, ms = 16) { clock += ms; win.scrollY = y; events.scroll(); frames.splice(0).forEach(callback => callback()); }};
}

function main() {
    const app = setup();
    assert.strictEqual(app.values['--webapp-menu-height'], '80px', 'Player offset includes the floating gap.');
    app.scroll(120);
    assert(app.hidden(), 'A fast downward scroll hides navigation.');
    assert.strictEqual(app.values['--webapp-menu-height'], '0px', 'Players move down when navigation hides.');
    assert.strictEqual(app.values['--webapp-menu-space'], '80px', 'Content spacing stays stable.');
    app.events.resize();
    assert(app.hidden(), 'Mobile browser chrome resizing does not reveal navigation mid-gesture.');
    app.scroll(117); assert(app.hidden(), 'Small scroll jitter keeps navigation hidden.');
    app.scroll(110); assert(!app.hidden(), 'A short upward scroll reveals navigation.');

    app.documentEvents['hidden.bs.offcanvas']();
    for (let y = 115; y <= 180; y += 5) app.scroll(y, 100);
    assert(!app.hidden(), 'Slow downward reading does not hide navigation.');
    app.scroll(260); assert(app.hidden());
    app.menuEvents.focusin(); assert(!app.hidden(), 'Keyboard focus reveals the menu.');
    app.doc.activeElement = app.menu;
    app.scroll(420); assert(!app.hidden(), 'Navigation stays visible while focused.');
    app.doc.activeElement = null;
    app.pin(true); app.scroll(560); assert(!app.hidden(), 'Open overlays keep the menu visible.');
    app.pin(false); app.scroll(700); assert(app.hidden());
    app.events.pageshow(); assert(!app.hidden(), 'Back/forward restoration reveals navigation.');

    app.scroll(4000); assert(app.hidden());
    app.scroll(4200); app.scroll(4100);
    assert(app.hidden(), 'Bottom rubber-band motion does not look like an upward scroll.');
    app.scroll(3990); assert(!app.hidden());
    app.scroll(-40); assert(!app.hidden(), 'Top overscroll always leaves navigation visible.');

    app.dimensions(68, '34px');
    assert.strictEqual(app.values['--webapp-menu-height'], '102px', 'The safe-area gap is part of the player offset.');
    app.dimensions(0, '34px');
    assert.strictEqual(app.values['--webapp-menu-height'], '0px', 'Intentionally absent menus reserve no space.');
    app.dimensions(68, '12px');
    app.scroll(240); assert(app.hidden());
    app.mobile.matches = false; app.events.resize();
    assert(!app.hidden(), 'Desktop navigation stays visible.');
    app.scroll(800); assert(!app.hidden());
    app.events.scroll(); app.events.scroll();
    assert.strictEqual(app.frames.length, 1, 'Scroll work is coalesced into one frame.');
    assert.strictEqual(init({document: {querySelector: () => null}}), undefined);
    console.log('Passed: floating navigation speed/direction, jitter, overscroll, focus, overlays, safe areas, restoration and responsive spacing.');
}

module.exports = main;
if (require.main === module) main();
