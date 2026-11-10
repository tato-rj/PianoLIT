const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

module.exports = async function () {
    function element() {
        return {hidden: false, dataset: {}, events: {}, attrs: {},
            addEventListener(name, fn) { this.events[name] = fn; },
            setAttribute(name, value) { this.attrs[name] = value; },
            removeAttribute(name) { delete this[name]; },
            focus() { this.focused = true; }, pause() { this.paused = true; }, load() { this.reloaded = true; }};
    }
    const content = element(), picker = element(), title = element(), status = element(), add = element();
    content.querySelectorAll = () => [];
    content.contains = node => !!node.inContent;
    picker.querySelector = selector => selector === '.modal-title' ? title : content;
    function row(index) {
        const value = element(), image = element(), audio = element(), preview = element(), remove = element(), missing = element(), name = element();
        value.value = ''; image.hidden = true; audio.hidden = true;
        const block = {querySelector(selector) { return {'[data-question-media-value]': value, '[data-question-media-image]': image, '[data-question-media-audio]': audio, '[data-question-media-preview]': preview, '[data-remove-question-media]': remove, '[data-question-media-missing]': missing, '[data-question-media-name]': name}[selector]; }};
        const input = {value: 'Unchanged question ' + index};
        const result = {connected: true, dataset: {questionIndex: String(index)}, value, image, audio, preview, missing, name, input,
            querySelectorAll: () => [audio], querySelector(selector) { return selector === '[data-question-media]' ? block : selector === 'input' ? input : element(); }};
        function button(type) {
            const button = element(); button.row = result;
            button.dataset = {chooseQuestionMedia: type, url: 'http://admin.test/quiz/media/' + type + '/choose'};
            button.closest = selector => selector === '[data-quiz-question]' ? result : null;
            return button;
        }
        result.chooseAudio = button('audio'); result.chooseImage = button('images');
        remove.row = result; remove.closest = () => result; result.removeMedia = remove;
        return result;
    }
    const first = row(0), second = row(1), rows = element();
    rows.querySelectorAll = () => [first, second];
    const form = element();
    form.contains = node => (node === first || node === second) ? node.connected : !!(node.row && node.row.connected);
    form.querySelector = selector => ({'[data-quiz-questions]': rows, '[data-quiz-question-template]': {}, '[data-add-question]': add, '[data-save-status]': status}[selector] || null);
    const requests = [];
    let visible = false;
    const document = {querySelector: () => form, getElementById(id) { return {'quiz-media-picker': picker, 'quiz-media-loading': {innerHTML: 'Loading'}, 'quiz-media-error': {innerHTML: 'Load failed; Retry'}}[id]; }};
    const window = {AbortController, URL, URLSearchParams, location: {href: 'http://admin.test/quiz/edit'},
        FormData: class {constructor(form) { return new Map([['search', form.search]]); }},
        bootstrap: {Modal: {getOrCreateInstance() { return {show() { visible = true; }, hide() { picker.events['hide.bs.modal'](); visible = false; picker.events['hidden.bs.modal'](); }}; }}},
        fetch(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); }};
    vm.runInNewContext(fs.readFileSync('resources/js/views/admin-quiz-editor.js', 'utf8'), {document, window});
    const flush = async () => { for (let i = 0; i < 6; i++) await Promise.resolve(); };
    function open(button) { rows.events.click({target: {closest(selector) { return selector === '[data-choose-question-media]' ? button : null; }}}); }
    function response(html, extra = {}) { return {ok: true, redirected: false, headers: {get: () => '1'}, text: async () => html, ...extra}; }
    function click(selector, node) { picker.events.click({target: {closest: wanted => wanted === selector ? node : null}, preventDefault() {}}); }
    open(first.chooseAudio);
    assert(visible); assert(first.audio.paused); assert.equal(title.textContent, 'Choose audio');
    const canceled = requests[0];
    window.bootstrap.Modal.getOrCreateInstance().hide();
    assert(canceled.options.signal.aborted); assert(first.chooseAudio.focused);
    open(second.chooseImage);
    requests[1].resolve(response('Image choices')); await flush();
    canceled.resolve(response('Late audio choices')); await flush();
    assert.equal(content.innerHTML, 'Image choices', 'A dismissed request cannot replace a newer question/type library.');
    click('[data-select-quiz-media]', {dataset: {path: '/storage/quiz/images/note.png', name: '<note>.png', type: 'images', url: 'http://admin.test/storage/quiz/images/note.png'}});
    assert.equal(first.value.value, '');
    assert.equal(second.value.value, '/storage/quiz/images/note.png');
    assert.equal(second.name.textContent, '<note>.png', 'Filenames are assigned as text.');
    assert.equal(second.image.hidden, false); assert.equal(second.audio.hidden, true);
    assert.equal(second.input.value, 'Unchanged question 1');
    assert.equal(status.textContent, 'Unsaved changes'); assert(!visible); assert(second.chooseImage.focused);
    rows.events.click({target: {closest(selector) { return selector === '[data-remove-question-media]' ? second.removeMedia : null; }}});
    assert.equal(second.value.value, ''); assert(second.preview.hidden); assert(second.audio.paused); assert(second.audio.reloaded);
    open(first.chooseAudio);
    requests[2].resolve(response('Sign-in page', {redirected: true})); await flush();
    assert.equal(content.innerHTML, 'Load failed; Retry', 'Expired sessions are not injected into the library.');
    click('[data-retry-quiz-media]', {});
    assert.equal(requests[3].url, first.chooseAudio.dataset.url);
    requests[3].resolve(response('Audio choices')); await flush();
    const search = {action: first.chooseAudio.dataset.url, search: 'interval'};
    picker.events.submit({target: search, preventDefault() { this.prevented = true; }});
    assert(requests[4].url.endsWith('?search=interval'));
    requests[4].resolve(response('Search results')); await flush();
    click('a', {href: first.chooseAudio.dataset.url + '?search=interval&page=2', inContent: true});
    assert(requests[5].url.endsWith('search=interval&page=2'));
    first.connected = false;
    requests[5].resolve(response('Removed question results')); await flush();
    assert.equal(content.innerHTML, 'Loading', 'A detached question cannot receive a library response.');
    assert.equal(second.input.value, 'Unchanged question 1');
    console.log('Passed: quiz media selection/removal, preserved drafts, canceled/stale replies, focus return, session failures, retry, search and pagination.');
};
