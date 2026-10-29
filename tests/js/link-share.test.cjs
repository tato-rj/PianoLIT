const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../resources/js/components/link-share.js'), 'utf8');

module.exports = async function () {
    function fixture(navigator = {}, legacyCopy = true) {
        const parts = {};
        for (const selector of ['.modal', 'input', '[data-share-copy]', '[data-share-status]', '[data-share-error]', '[data-share-sms]', '[data-share-facebook]', '[data-share-error-message]']) {
            parts[selector] = {hidden: true, events: {}, attrs: {}, value: 'https://my.pianolit.com/pieces/42',
                addEventListener(name, handler) { this.events[name] = handler; },
                removeEventListener(name) { delete this.events[name]; },
                setAttribute(name, value) { this.attrs[name] = value; },
                focus() { this.focused = true; }, select() { this.selected = true; }, setSelectionRange() { this.selected = true; }};
        }
        const root = {dataset: {shareTitle: 'A & B by Frédéric Chopin', shareUrl: 'https://my.pianolit.com/pieces/42', shareMessage: 'Hi!\n\nI found A & B by Frédéric Chopin on PianoLIT and thought you might enjoy it.\n\nhttps://my.pianolit.com/pieces/42'}, querySelector(selector) { return parts[selector]; }};
        const doc = {readyState: 'complete', querySelectorAll() { return [root]; }, execCommand(command) { assert.equal(command, 'copy'); return legacyCopy; }};
        const timers = new Map();
        let timerId = 0;
        const tooltips = [];
        const win = {navigator, setTimeout(callback, delay) { const id = ++timerId; timers.set(id, {callback, delay}); return id; }, clearTimeout(id) { timers.delete(id); },
            bootstrap: {Tooltip: class {
                constructor(element, options) { this.element = element; this.options = options; this.visible = false; tooltips.push(this); }
                show() { this.visible = true; } hide() { this.visible = false; } dispose() { this.disposed = true; }
            }}};
        vm.runInNewContext(source, {document: doc, window: win});
        return {parts, root, win, timers, tooltips, async click(selector, event) { await parts[selector].events.click(event); },
            close() { parts['.modal'].events['hide.bs.modal'](); parts['.modal'].events['hidden.bs.modal'](); },
            expire() { const pending = [...timers.values()]; timers.clear(); pending.forEach(timer => { assert.equal(timer.delay, 1600); timer.callback(); }); }};
    }

    let copied;
    let f = fixture({clipboard: {async writeText(value) { copied = value; }}});
    await f.click('[data-share-copy]');
    assert.equal(copied, 'https://my.pianolit.com/pieces/42');
    assert.equal(f.parts['[data-share-status]'].textContent, 'Copied!');
    assert.equal(f.tooltips[0].visible, true);
    assert.equal(f.tooltips[0].options.trigger, 'manual');
    f.expire();
    assert.equal(f.tooltips[0].visible, false, 'Copy confirmation disappears automatically.');
    await f.click('input');
    assert.equal(f.parts['[data-share-status]'].textContent, 'Copied!', 'Clicking the URL copies just like the icon.');
    assert.equal(f.tooltips[0].visible, true);
    let prevented = false;
    await f.parts.input.events.keydown({key: 'Enter', preventDefault() { prevented = true; }});
    assert.equal(prevented, true, 'Enter copies the focused readonly URL.');
    f.close();
    assert.equal(f.parts['[data-share-status]'].textContent, '');
    assert.equal(f.tooltips[0].disposed, true);
    assert.equal(f.timers.size, 0);

    f = fixture({clipboard: {async writeText() { throw new Error('Permission denied'); }}});
    await f.click('[data-share-copy]');
    assert.equal(f.parts.input.selected, true);
    assert.equal(f.parts['[data-share-status]'].textContent, 'Copied!', 'Denied clipboard permission uses the selected-field fallback.');
    f = fixture({}, false);
    await f.click('[data-share-copy]');
    assert.equal(f.parts['[data-share-status]'].textContent, '');
    assert.equal(f.parts['[data-share-error]'].hidden, false);
    assert.equal(f.parts.input.selected, true);

    f = fixture({userAgent: 'iPhone', clipboard: {async writeText() {}}});
    assert.equal(f.parts['[data-share-sms]'].attrs.href, 'sms:&body=' + encodeURIComponent(f.root.dataset.shareMessage));
    assert.equal(decodeURIComponent(f.parts['[data-share-sms]'].attrs.href.split('body=')[1]), 'Hi!\n\nI found A & B by Frédéric Chopin on PianoLIT and thought you might enjoy it.\n\nhttps://my.pianolit.com/pieces/42');
    f = fixture({platform: 'MacIntel', maxTouchPoints: 5});
    assert.ok(f.parts['[data-share-sms]'].attrs.href.startsWith('sms:&body='));

    let sharedUrl;
    let facebookPrevented = false;
    f = fixture({userAgent: 'iPhone', async share(data) { sharedUrl = data; }, canShare() { return true; }});
    await f.click('[data-share-facebook]', {preventDefault() { facebookPrevented = true; }});
    assert.equal(facebookPrevented, true, 'iPhone Facebook sharing avoids the broken app-link handoff.');
    assert.equal(JSON.stringify(sharedUrl), JSON.stringify({url: 'https://my.pianolit.com/pieces/42'}));
    await f.click('[data-share-facebook]', {ctrlKey: true, preventDefault() { assert.fail('Modified link clicks retain their normal destination.'); }});
    f = fixture({platform: 'MacIntel', maxTouchPoints: 5, async share() { throw {name: 'AbortError'}; }});
    await f.click('[data-share-facebook]', {preventDefault() {}});
    assert.equal(f.parts['[data-share-error]'].hidden, true, 'Canceling the iPadOS share sheet is silent.');
    f = fixture({userAgent: 'iPhone', async share() { throw new Error('Unavailable'); }});
    await f.click('[data-share-facebook]', {preventDefault() {}});
    assert.equal(f.parts['[data-share-error]'].hidden, false);
    assert.match(f.parts['[data-share-error-message]'].textContent, /Copy the page link/);
    for (const navigator of [
        {userAgent: 'Android', share() {}},
        {userAgent: 'iPhone'},
        {userAgent: 'iPhone', share() {}, canShare() { return false; }},
        {userAgent: 'iPhone', share() {}, canShare() { throw new Error('Denied'); }},
    ]) {
        f = fixture(navigator);
        assert.equal(f.parts['[data-share-facebook]'].events.click, undefined, 'Other devices and unavailable native sharing retain the ordinary Facebook link.');
    }
    let finishSharing;
    let facebookCalls = 0;
    f = fixture({userAgent: 'iPhone', share() { facebookCalls++; return new Promise((resolve, reject) => { finishSharing = reject; }); }});
    const facebookPending = f.click('[data-share-facebook]', {preventDefault() {}});
    await f.click('[data-share-facebook]', {preventDefault() {}});
    assert.equal(facebookCalls, 1, 'Repeated taps cannot open multiple share sheets.');
    f.close(); finishSharing(new Error('Late failure')); await facebookPending;
    assert.equal(f.parts['[data-share-error]'].hidden, true, 'A closed dialog cannot receive late sharing failures.');

    let finish;
    f = fixture({clipboard: {writeText() { return new Promise(resolve => { finish = resolve; }); }}});
    const pending = f.click('[data-share-copy]');
    f.close(); finish(); await pending;
    assert.equal(f.parts['[data-share-status]'].textContent, '', 'A closed dialog cannot receive stale copy feedback.');
    f.root.isConnected = false;
    f.win.PianoLITLinkShare.init();
    assert.equal(f.parts['.modal'].events['hide.bs.modal'], undefined, 'Replaced quiz fragments release their modal listeners.');
    console.log('Passed: field/icon copy, timed tooltip, permission fallback, failure selection, SMS variants, iOS Facebook sharing/fallback/cancellation and stale dialog feedback.');
};
