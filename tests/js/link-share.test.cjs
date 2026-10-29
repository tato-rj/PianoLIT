const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../resources/js/components/link-share.js'), 'utf8');

module.exports = async function () {
    function fixture(navigator = {}, legacyCopy = true) {
        const parts = {};
        for (const selector of ['.modal', 'input', '[data-share-copy]', '[data-share-status]', '[data-share-error]', '[data-share-sms]', '[data-share-error-message]']) {
            parts[selector] = {hidden: true, events: {}, attrs: {}, value: 'https://my.pianolit.com/pieces/42',
                addEventListener(name, handler) { this.events[name] = handler; },
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
        return {parts, root, win, timers, tooltips, async click(selector) { await parts[selector].events.click(); },
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

    let finish;
    f = fixture({clipboard: {writeText() { return new Promise(resolve => { finish = resolve; }); }}});
    const pending = f.click('[data-share-copy]');
    f.close(); finish(); await pending;
    assert.equal(f.parts['[data-share-status]'].textContent, '', 'A closed dialog cannot receive stale copy feedback.');
    console.log('Passed: field/icon copy, timed tooltip, permission fallback, failure selection, SMS variants and stale dialog feedback.');
};
