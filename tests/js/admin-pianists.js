const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = function () {
    function element(tag) {
        return {tag, children: [], attrs: {}, listeners: {}, textContent: '',
            appendChild(child) { this.children.push(child); },
            setAttribute(key, value) { this.attrs[key] = value; },
            addEventListener(key, value) { this.listeners[key] = value; }};
    }
    function setup(artistId = '123', modal = false) {
        const container = element('div'), status = element('p'), results = element('div'), retry = element('button');
        container.getAttribute = () => artistId;
        container.querySelector = selector => selector === '[data-albums-status]' ? status : results;
        const calls = [];
        let opened = 0;
        const document = {createElement: element, querySelector(selector) {
            return selector === '#add-modal.autoshow' ? (modal ? {} : null)
                : selector === '[data-pianist-albums]' ? container : retry;
        }};
        const window = {bootstrap: {Modal: {getOrCreateInstance() { return {show() { opened++; }}; }}}};
        vm.runInNewContext(fs.readFileSync('resources/js/views/admin-pianists.js', 'utf8'), {document, window, URL, $: {ajax(options) { calls.push(options); }}});
        return {container, status, results, retry, calls, opened};
    }
    const state = setup();
    assert.strictEqual(state.calls.length, 1);
    assert.strictEqual(state.retry.disabled, true);
    state.retry.listeners.click();
    assert.strictEqual(state.calls.length, 1, 'Do not overlap requests.');
    state.calls[0].error(); state.calls[0].complete();
    assert.strictEqual(state.retry.disabled, false);
    assert.strictEqual(state.container.attrs['aria-busy'], 'false');
    assert.match(state.status.textContent, /could not be loaded/);
    state.retry.listeners.click();
    state.calls[1].success({results: [
        {wrapperType: 'artist'},
        {collectionType: 'Album', collectionName: '<img onerror=unsafe>', collectionViewUrl: 'javascript:unsafe', artworkUrl100: 'https://untrusted.example/image.jpg'},
        {collectionType: 'Album', collectionName: 'Piano works', collectionViewUrl: 'https://music.apple.com/album/123', artworkUrl100: 'https://is1-ssl.mzstatic.com/image.jpg', collectionPrice: 9.99, currency: 'USD'},
    ]});
    state.calls[1].complete();
    assert.strictEqual(state.results.children.length, 2);
    const unsafeTitle = state.results.children[0].children[0].children[0].children[0];
    assert.strictEqual(unsafeTitle.tag, 'span');
    assert.strictEqual(unsafeTitle.textContent, '<img onerror=unsafe>');
    const link = state.results.children[1].children[0].children[1].children[0];
    assert.strictEqual(link.href, 'https://music.apple.com/album/123');
    assert.strictEqual(link.rel, 'noopener noreferrer');
    assert.strictEqual(setup('', true).opened, 1, 'Validation reopens the add dialog.');
    assert.strictEqual(setup('').calls.length, 0, 'An optional artist ID makes no request.');
    console.log('Passed: pianist album retries, loading recovery, safe external content and add-dialog recovery.');
};
