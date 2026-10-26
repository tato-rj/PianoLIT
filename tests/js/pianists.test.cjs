const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = function () {
    function node() {
        const value = {children: [], attributes: {}, events: {}, hidden: false, disabled: false, fields: {},
            appendChild(child) { this.children.push(child); },
            setAttribute(name, content) { this.attributes[name] = content; },
            addEventListener(name, callback) { this.events[name] = callback; },
            querySelector(selector) { return this.fields[selector]; }
        };
        let text = '';
        Object.defineProperty(value, 'textContent', {get() { return text; }, set(content) { text = content; this.children = []; }});
        return value;
    }
    function setup(artistId = '123456') {
        const container = node(), status = node(), results = node(), retry = node(), calls = [];
        container.getAttribute = () => artistId;
        container.fields = {'[data-recordings-status]': status, '[data-recordings-results]': results, '[data-recordings-retry]': retry};
        const template = {content: {firstElementChild: {cloneNode() {
            const card = node();
            for (const name of ['link', 'title', 'image', 'art', 'details']) card.fields['[data-album-' + name + ']'] = node();
            return card;
        }}}};
        const document = {querySelector() { return container; }, getElementById() { return template; }};
        vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/pianists.js'), 'utf8'), {
            document, URL, window: {jQuery: {ajax(options) { calls.push(options); }}}
        });
        return {container, status, results, retry, calls};
    }
    const state = setup();
    assert.strictEqual(state.calls.length, 1);
    assert.strictEqual(state.calls[0].url, 'https://itunes.apple.com/lookup');
    assert.strictEqual(state.calls[0].dataType, 'jsonp');
    assert.strictEqual(state.calls[0].data.id, '123456');
    assert.strictEqual(state.calls[0].data.entity, 'album');
    assert.strictEqual(state.calls[0].data.limit, 200);
    assert.strictEqual(state.calls[0].timeout, 15000);
    assert(state.retry.disabled && state.retry.hidden);
    state.retry.events.click();
    assert.strictEqual(state.calls.length, 1, 'Do not overlap Apple lookups.');
    state.calls[0].error(); state.calls[0].complete();
    assert(!state.retry.disabled && !state.retry.hidden);
    assert.strictEqual(state.container.attributes['aria-busy'], 'false');
    assert.match(state.status.textContent, /couldn’t load/);
    state.retry.events.click();
    state.calls[1].success({results: [
        {wrapperType: 'artist', artistName: 'Example artist'},
        {collectionType: 'Album', collectionId: 1, collectionName: '<img src=x onerror=unsafe>', collectionViewUrl: 'https://music.apple.com/us/album/piano/1', artworkUrl100: 'https://is1-ssl.mzstatic.com/piano.jpg', releaseDate: '2020-05-01T00:00:00Z', trackCount: 12},
        {collectionType: 'Album', collectionId: 1, collectionName: 'Duplicate', collectionViewUrl: 'https://music.apple.com/album/1'},
        {collectionType: 'Album', collectionId: 2, collectionName: 'Unsafe', collectionViewUrl: 'javascript:unsafe'},
        {collectionType: 'Album', collectionId: 3, collectionName: 'Unsafe domain', collectionViewUrl: 'https://music.apple.com.evil.test/album/1'},
        {collectionType: 'Album', collectionId: 4, collectionName: 'Piano works.', collectionViewUrl: 'https://itunes.apple.com/us/album/piano/4', artworkUrl100: 'https://untrusted.example/image.jpg'},
        {collectionType: 'Album', collectionId: 5, collectionName: 'Unsafe port', collectionViewUrl: 'https://music.apple.com:8000/album/5'},
        {collectionType: 'Album', collectionId: 6, collectionName: 'Unsafe credentials', collectionViewUrl: 'https://user:pass@music.apple.com/album/6'},
    ]});
    state.calls[1].complete();
    assert.strictEqual(state.results.children.length, 2, 'Only unique albums with trusted destinations are displayed.');
    const first = state.results.children[0], second = state.results.children[1];
    assert.strictEqual(first.fields['[data-album-title]'].textContent, '<img src=x onerror=unsafe>');
    assert.strictEqual(first.fields['[data-album-link]'].href, 'https://music.apple.com/us/album/piano/1');
    assert.strictEqual(first.fields['[data-album-image]'].src, 'https://is1-ssl.mzstatic.com/piano.jpg');
    assert.strictEqual(first.fields['[data-album-details]'].textContent, '2020 · 12 tracks');
    assert.strictEqual(second.fields['[data-album-title]'].textContent, 'Piano works');
    assert.strictEqual(second.fields['[data-album-art]'].hidden, true);
    assert.strictEqual(second.fields['[data-album-details]'].textContent, '', 'Missing metadata stays absent.');
    assert.strictEqual(state.status.textContent, 'Showing 2 albums on Apple Music');
    assert(state.retry.hidden && !state.retry.disabled);

    const malformed = setup();
    malformed.calls[0].success({results: 'invalid'}); malformed.calls[0].complete();
    assert(!malformed.retry.hidden, 'A malformed response allows retry instead of claiming no albums exist.');
    malformed.retry.events.click();
    malformed.calls[1].success({results: []}); malformed.calls[1].complete();
    assert.strictEqual(malformed.status.textContent, 'No albums are listed for this pianist yet.');
    assert(malformed.retry.hidden);

    const capped = setup();
    capped.calls[0].success({results: Array.from({length: 200}, (_, index) => ({
        collectionType: 'Album', collectionId: index, collectionViewUrl: 'https://music.apple.com/album/' + index, collectionName: 'Album ' + index
    }))});
    capped.calls[0].complete();
    assert.strictEqual(capped.status.textContent, 'Showing 200 albums on Apple Music', 'A cap does not establish an unseen total.');
    for (const id of ['', 'not-an-id', '123&callback=unsafe']) assert.strictEqual(setup(id).calls.length, 0);
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../resources/js/views/pianists.js'), 'utf8'), {document: {querySelector() { return null; }}});
    console.log('Passed: public pianist album loading/retry, request guards, safe provider fields, duplicate filtering, metadata and honest counts.');
};

if (require.main === module) module.exports();
