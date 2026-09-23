const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function load(file, globals) {
    const context = vm.createContext(globals);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../resources/js', file), 'utf8'), context);
    return context;
}

function element(value = '') {
    return {
        value, visible: false, events: {}, content: '',
        val() { return this.value; },
        attr() { return '/search'; },
        on(event, callback) { this.events[event] = callback; return this; },
        empty() { this.content = ''; return this; },
        html(content) { this.content = content; return this; },
        show() { this.visible = true; return this; },
        hide() { this.visible = false; return this; }
    };
}

async function main() {
    await require('./webapp-search')();
    await require('./piece-access')();
    const document = {cookie: 'first=one; second=two%20words; third=three'};
    const cookies = load('helpers/cookie.js', {document});
    assert.strictEqual(cookies.getCookie('first'), 'one');
    assert.strictEqual(cookies.getCookie('second'), 'two words');
    assert.strictEqual(cookies.getCookie('third'), 'three');
    assert.strictEqual(cookies.getCookie('missing'), null);
    document.cookie = 'broken=50%off';
    assert.strictEqual(cookies.getCookie('broken'), '50%off');
    cookies.setCookie('recent', 'a;b=c & d');
    assert.strictEqual(document.cookie, 'recent=a%3Bb%3Dc%20%26%20d');
    assert.strictEqual(cookies.getCookie('recent'), 'a;b=c & d');
    cookies.setCookie('recent', '[]', 2);
    assert(document.cookie.includes(';expires='));

    const location = {search: '?page=1&search=old'};
    const urls = load('helpers/url.js', {document: {location}, window: {}});
    urls.insertParam('search', 'Bach & Mozart=great');
    assert.strictEqual(location.search, 'page=1&search=Bach%20%26%20Mozart%3Dgreat');
    location.search = '';
    urls.insertParam('search', 'a+b');
    assert.strictEqual(location.search, 'search=a%2Bb');

    let resets = 0;
    const icons = {removeClass() { resets++; return this; }, addClass() { return this; }};
    class AudioStub {
        constructor() { this.events = {}; this.src = 'clip.mp3'; }
        pause() { this.paused = true; }
        removeAttribute() { this.src = undefined; }
        getAttribute() { return this.src; }
        load() { this.loaded = true; }
        play() { this.paused = true; return Promise.reject(new Error('Playback unavailable')); }
        addEventListener(event, callback) { this.events[event] = callback; }
    }
    const audio = load('components/audio.js', {
        Audio: AudioStub, document: {},
        $: selector => typeof selector === 'string' ? icons : {on() {}}
    });
    audio.stop();
    assert.strictEqual(audio.audio.paused, true);
    assert.strictEqual(audio.audio.src, undefined);
    assert.strictEqual(audio.audio.loaded, true);
    audio.audio.events.ended();
    audio.play('broken.mp3');
    await Promise.resolve();
    assert.strictEqual(resets, 2);

    const pending = [];
    const timers = new Map();
    let timerId = 0;
    let canceled = 0;
    const searchContext = load('search/Search.js', {
        window: {}, $: input => input,
        setTimeout(callback) { timers.set(++timerId, callback); return timerId; },
        clearTimeout(id) { timers.delete(id); },
        axios: {
            CancelToken: {source: () => ({token: {}, cancel() { canceled++; }})},
            isCancel: () => false,
            get() { return new Promise((resolve, reject) => pending.push({resolve, reject})); }
        }
    });
    const search = new searchContext.window.Search;
    const input = element();
    search.listenTo(input);
    search.$results = element();
    search.$loading = element();
    search.$error = element();
    search.ready();
    function type(value) { input.value = value; input.events.input.call(input); }
    function runTimers() { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(callback => callback()); }
    async function settle() { await Promise.resolve(); await Promise.resolve(); await Promise.resolve(); }

    type('Bac'); type('Bach');
    assert.strictEqual(pending.length, 0);
    runTimers();
    assert.strictEqual(pending.length, 1);
    type('Mozart'); runTimers();
    assert.strictEqual(canceled, 1);
    pending[0].reject(new Error('Old request failed'));
    await settle();
    assert.strictEqual(search.$error.visible, false);
    assert.strictEqual(search.$loading.visible, true);
    pending[1].resolve({data: 'Mozart results'});
    await settle();
    assert.strictEqual(search.$results.content, 'Mozart results');
    assert.strictEqual(search.$loading.visible, false);

    type('Bach'); runTimers();
    type('Mozart'); runTimers();
    type('Bach'); runTimers();
    pending[4].resolve({data: 'Current Bach results'});
    await settle();
    pending[2].resolve({data: 'Stale Bach results'});
    await settle();
    assert.strictEqual(search.$results.content, 'Current Bach results');
    type(''); runTimers();
    pending[3].resolve({data: 'Stale Mozart results'});
    await settle();
    assert.strictEqual(search.$results.content, '');
    assert.strictEqual(search.$loading.visible, false);

    const lookupInput = element();
    const wrapper = {on() {}};
    lookupInput.parent = () => wrapper;
    const lookupTimers = new Map();
    const lookupContext = load('vendor/lookup.js', {
        document: {}, $: selector => typeof selector === 'string' ? lookupInput : {on() {}},
        setTimeout(callback) { lookupTimers.set(++timerId, callback); return timerId; },
        clearTimeout(id) { lookupTimers.delete(id); }
    });
    const lookup = new lookupContext.Lukup({field: 'name', autofill: []});
    let calls = 0;
    lookup.createHtmlElements = () => {};
    lookup.reset = () => {};
    lookup.autocomplete = () => { calls++; };
    lookup.enable();
    lookupInput.events.input(); lookupInput.events.input();
    assert.strictEqual(calls, 0);
    assert.strictEqual(lookupTimers.size, 1);
    [...lookupTimers.values()][0]();
    assert.strictEqual(calls, 1);
    console.log('Passed: cookie parsing, URL encoding, audio lifecycle, search races/cancellation, lookup debounce.');
}

main().catch(error => { console.error(error); process.exitCode = 1; });
