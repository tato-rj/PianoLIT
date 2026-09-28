const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const script = fs.readFileSync(path.join(__dirname, '../../resources/js/components/history-back.js'), 'utf8');
const startup = fs.readFileSync(path.join(__dirname, '../../resources/js/startup.js'), 'utf8');
const origin = 'https://my.pianolit.com';

module.exports = function () {
    function tabs(history) {
        let click;
        const document = {};
        vm.runInNewContext(startup, {
            document, window: {history},
            $: target => target === document ? {
                ready() {},
                on(event, selector, callback) {
                    if (selector === '[data-anchor]') click = callback;
                }
            } : {attr: () => target.anchor}
        });
        return anchor => click.call({anchor});
    }

    function page(history, referrer) {
        let click;
        const link = {addEventListener(name, callback) { click = callback; }};
        vm.runInNewContext(script, {
            URL,
            document: {referrer, querySelectorAll: () => [link]},
            window: {history, location: {origin}}
        });
        return overrides => {
            const event = Object.assign({button: 0, prevented: false,
                preventDefault() { this.prevented = true; }}, overrides);
            click(event);
            return event.prevented;
        };
    }

    // Simulate full document loads on Back: the latest referrer may point to
    // the child page, but Back must keep unwinding the original tab history.
    const entries = [{url: '/search?search=Bach&page=2', state: null}];
    let index = 0;
    let backs = 0;
    const history = {
        get length() { return entries.length; },
        get state() { return entries[index].state; },
        replaceState(state, title, url) {
            entries[index].state = state;
            if (url !== undefined) {
                const resolved = new URL(url, origin + entries[index].url);
                entries[index].url = resolved.pathname + resolved.search + resolved.hash;
            }
        },
        back() { backs++; if (index > 0) index--; }
    };
    for (const url of ['/pieces/1', '/pieces/1/composer', '/pieces/2', '/pieces/3']) {
        const referrer = origin + entries[index].url;
        entries.push({url, state: null});
        index++;
        page(history, referrer);
    }
    for (const expected of ['/pieces/2', '/pieces/1/composer', '/pieces/1', '/search?search=Bach&page=2']) {
        assert.strictEqual(page(history, origin + entries[index].url)(), true);
        assert.strictEqual(entries[index].url, expected);
        assert.strictEqual(entries.length, 5, 'Back must not add entries');
    }
    assert.strictEqual(backs, 4);

    // Repeated tab selections update only the current page entry, retaining
    // its query and Back metadata. Nested pages still unwind in one click each.
    entries.splice(0, entries.length, {url: '/', state: null},
        {url: '/pieces/1?source=search#about', state: {otherFeature: 'kept'}});
    index = 1;
    const pieceBack = page(history, origin + '/');
    const stateBeforeTabs = history.state;
    const selectTab = tabs(history);
    for (const anchor of ['score', 'synthesia', 'timeline', 'about', 'score']) {
        selectTab(anchor);
        assert.strictEqual(entries.length, 2, 'Tabs must not add history entries');
        assert.strictEqual(entries[index].url, '/pieces/1?source=search#' + anchor);
        assert.strictEqual(history.state, stateBeforeTabs, 'Retain existing history state');
    }
    entries.push({url: '/pieces/2', state: null});
    index++;
    const nestedBack = page(history, origin + entries[1].url);
    tabs(history)('synthesia');
    assert.strictEqual(entries.length, 3);
    assert.strictEqual(nestedBack(), true);
    assert.strictEqual(entries[index].url, '/pieces/1?source=search#score');
    assert.strictEqual(pieceBack(), true);
    assert.strictEqual(entries[index].url, '/');

    // A new tab can have a same-origin referrer but no earlier entry. Its
    // fallback must survive returning from a child and then reloading.
    entries.splice(0, entries.length, {url: '/pieces/1', state: {otherFeature: 'kept'}});
    index = 0;
    assert.strictEqual(page(history, origin + '/search')(), false);
    tabs(history)('score');
    tabs(history)('synthesia');
    assert.strictEqual(entries.length, 1);
    assert.strictEqual(history.state.pianolitCanGoBack, false);
    assert.strictEqual(history.state.otherFeature, 'kept');
    entries.push({url: '/pieces/2', state: null});
    index++;
    assert.strictEqual(page(history, origin + '/pieces/1')(), true);
    assert.strictEqual(index, 0);
    assert.strictEqual(page(history, origin + '/pieces/2')(), false);

    for (const referrer of ['', 'https://example.com/', 'malformed']) {
        entries[0].state = null;
        assert.strictEqual(page(history, referrer)(), false);
    }

    entries[0].state = {pianolitCanGoBack: true};
    const click = page(history, origin + '/search');
    for (const overrides of [{ctrlKey: true}, {metaKey: true}, {shiftKey: true},
        {altKey: true}, {button: 1}, {defaultPrevented: true}]) {
        const before = backs;
        assert.strictEqual(click(overrides), false);
        assert.strictEqual(backs, before);
    }

    let restrictedBacks = 0;
    assert.strictEqual(page({length: 2, state: null,
        replaceState() { throw new Error('Unavailable'); },
        back() { restrictedBacks++; }}, origin + '/search')(), true);
    assert.strictEqual(restrictedBacks, 1);
    console.log('Passed: Back unwinds nested pieces, ignores tab selections, preserves URLs/state, new-tab/direct fallbacks, reloads, and modified clicks.');
};
