const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

module.exports = async function () {
    const requests = [], windowEvents = {};
    let document, candidates = [], rendered = '', replacements = 0;
    function node(attributes = {}) {
        return {
            attributes, dataset: {}, listeners: {}, disabled: false, hidden: false, textContent: '',
            addEventListener(name, listener) { this.listeners[name] = listener; },
            hasAttribute(name) { return Object.hasOwn(this.attributes, name); },
            getAttribute(name) { return this.attributes[name] || null; },
            setAttribute(name, value) { this.attributes[name] = value; },
            closest(selector) { return selector.split(',').some(part => this.hasAttribute(part.trim().slice(1, -1))) ? this : null; },
            focus() { document.activeElement = this; },
        };
    }
    const body = node(); body.children = [];
    body.querySelectorAll = () => body.children.filter(row => row.dataset.selectedPiece);
    body.appendChild = row => { row.parent = body; body.children.push(row); };
    body.insertBefore = (row, reference) => { body.children.splice(body.children.indexOf(row), 1); body.children.splice(body.children.indexOf(reference), 0, row); };
    function row(id) {
        const item = node({'data-selected-piece': ''}); item.dataset = {selectedPiece: String(id), pieceName: 'Piece ' + id};
        item.position = node(); item.up = node({'data-piece-move': ''}); item.up.dataset.pieceMove = 'up';
        item.down = node({'data-piece-move': ''}); item.down.dataset.pieceMove = 'down'; item.removeButton = node({'data-piece-remove': ''});
        for (const button of [item.up, item.down, item.removeButton]) {
            const closest = button.closest;
            button.closest = selector => selector === '[data-selected-piece]' ? item : closest.call(button, selector);
        }
        Object.defineProperties(item, {
            previousElementSibling: {get: () => body.children[body.children.indexOf(item) - 1]},
            nextElementSibling: {get: () => body.children[body.children.indexOf(item) + 1]},
        });
        item.querySelector = selector => ({'[data-piece-position]': item.position, '[data-piece-move="up"]': item.up, '[data-piece-move="down"]': item.down,
            '[data-piece-remove]': item.removeButton, '[data-piece-move]:not(:disabled)': [item.up, item.down].find(button => !button.disabled)})[selector];
        item.remove = () => { body.children.splice(body.children.indexOf(item), 1); };
        item.cloneNode = () => row(id);
        return item;
    }
    body.appendChild(row(1)); body.appendChild(row(2));
    function candidate(id) {
        const button = node({'data-piece-add': '', 'aria-label': 'Add Piece ' + id + ' to playlist'}); button.dataset.pieceAdd = String(id);
        const icon = {classList: {replace() {}}}; button.querySelector = () => icon; button.replaceChildren = () => {};
        const closest = button.closest;
        button.closest = selector => selector === '[data-finder-piece]' ? {querySelector: () => ({content: {firstElementChild: row(id)}})} : closest.call(button, selector);
        return button;
    }
    candidates = [candidate(1), candidate(3)];
    const results = node(); results.querySelectorAll = selector => selector === '[data-piece-add]' ? candidates : [];
    Object.defineProperty(results, 'innerHTML', {set(value) { rendered = value; replacements++; }});
    const count = node(), status = node(), save = node(), add = node(), cover = node(), file = node();
    const form = node(); form.dataset.restored = 'false';
    form.querySelector = selector => ({'#playlist-selected-table tbody': body, '[data-playlist-count]': count, '[data-save-status]': status,
        '[data-playlist-save]': save, '[data-bs-target="#playlist-piece-finder"]': add, '[data-playlist-cover]': cover, '[name="cover"]': file})[selector];
    const finder = node(), finderStatus = node(), error = node(); finder.dataset.url = 'https://admin.test/playlists/1/pieces';
    finder.querySelector = selector => ({'[data-finder-results]': results, '[data-finder-status]': finderStatus, '[data-finder-error]': error})[selector];
    const root = node(); root.isConnected = true;
    const empty = {content: {cloneNode() { const item = node(); item.remove = () => body.children.splice(body.children.indexOf(item), 1); return item; }}};
    root.querySelector = selector => ({'[data-playlist-editor]': form, '[data-playlist-empty]': empty, '[data-playlist-finder]': finder})[selector];
    document = {querySelector: () => root, createTextNode: text => ({textContent: text}), activeElement: null};
    const window = {location: {href: 'https://admin.test/playlists/1/edit'}, addEventListener(name, listener) { windowEvents[name] = listener; }};
    const fetch = (url, options) => new Promise((resolve, reject) => requests.push({url, options, resolve, reject}));
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-playlist-editor.js', 'utf8'), {document, window, fetch, URL, URLSearchParams, AbortController});
    const click = (target, surface = body) => surface.listeners.click({target, preventDefault() {}});
    const ids = () => body.querySelectorAll().map(item => item.dataset.selectedPiece);
    const tick = () => new Promise(resolve => setImmediate(resolve));
    const response = (request, html, extra = {}) => request.resolve({ok: true, json: async () => ({html, total: 12, ...extra})});
    function browse(page) {
        const link = node(); link.href = finder.dataset.url + '?page=' + page; link.closest = selector => selector === 'a[href]' ? link : null;
        click(link, finder);
    }

    assert.strictEqual(candidates[0].disabled, true, 'Already selected pieces cannot be added twice.');
    assert.strictEqual(body.children[0].up.disabled, true); assert.strictEqual(body.children[1].down.disabled, true);
    click(candidates[1], finder); click(candidates[1], finder);
    assert.deepStrictEqual(ids(), ['1', '2', '3']); assert.strictEqual(count.textContent, '3 pieces in this playlist');
    assert.strictEqual(status.textContent, 'Unsaved changes'); assert.strictEqual(requests.length, 0, 'Selection changes stay local until Save.');
    click(body.children[2].up); assert.deepStrictEqual(ids(), ['1', '3', '2']);
    assert.deepStrictEqual(body.children.map(item => item.position.textContent), [1, 2, 3]);
    click(body.children[1].removeButton); assert.deepStrictEqual(ids(), ['1', '2']); assert.strictEqual(candidates[1].disabled, false);

    browse(2); browse(3); assert.strictEqual(requests[0].options.signal.aborted, true);
    assert.strictEqual(requests[1].options.credentials, 'same-origin');
    response(requests[1], 'current'); await tick(); response(requests[0], 'stale'); await tick();
    assert.strictEqual(rendered, 'current'); assert.deepStrictEqual(ids(), ['1', '2']);
    browse(2); requests.at(-1).reject(new Error('Offline')); await tick();
    assert.strictEqual(error.hidden, false); assert.strictEqual(error.textContent, 'Offline'); assert.strictEqual(rendered, 'current');
    assert.strictEqual(results.attributes['aria-busy'], 'false');
    browse(2); requests.at(-1).resolve({ok: false, status: 401, json: async () => ({})}); await tick();
    assert.match(error.textContent, /session has expired/);
    browse(2); requests.at(-1).resolve({ok: true, json: async () => { throw new SyntaxError('Unexpected HTML'); }}); await tick();
    assert.strictEqual(error.textContent, 'Unable to load the pieces. Please try again.');
    browse(2); const before = replacements; root.isConnected = false; response(requests.at(-1), 'detached'); await tick();
    assert.strictEqual(replacements, before); root.isConnected = true;

    click(body.children[0].removeButton); click(body.children[0].removeButton);
    assert.deepStrictEqual(ids(), []); assert.strictEqual(body.children.length, 1, 'The empty state replaces the last row.');
    assert.strictEqual(document.activeElement, add);
    click(candidates[1], finder); assert.deepStrictEqual(ids(), ['3']); assert.strictEqual(body.children.length, 1);
    let prevented = false;
    windowEvents.beforeunload({preventDefault() { prevented = true; }}); assert(prevented);
    form.listeners.submit({preventDefault() { throw new Error('First save must submit.'); }});
    assert.strictEqual(save.disabled, true); assert.strictEqual(status.textContent, 'Saving changes…');
    let duplicate = false; form.listeners.submit({preventDefault() { duplicate = true; }}); assert(duplicate);
    windowEvents.beforeunload({preventDefault() { throw new Error('Save must not trigger the unsaved warning.'); }});
    windowEvents.pageshow(); assert.strictEqual(save.disabled, false); assert.strictEqual(status.textContent, 'Unsaved changes');
    console.log('Passed: playlist draft add/remove/reorder, duplicate/empty states, finder request races and recovery, detached results, save and back-navigation guards.');
};
