const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
module.exports = async function () {
    class Node {
        constructor() { this.events = {}; this.nodes = {}; this.dataset = {}; this.style = {}; this.hidden = false; this.disabled = false; this.checked = true; this.value = ''; this.textContent = ''; this.clientWidth = 500; this.children = []; this.classes = new Set(); this.classList = {toggle: (name, value) => value ? this.classes.add(name) : this.classes.delete(name), add: name => this.classes.add(name), remove: name => this.classes.delete(name)}; }
        querySelector(selector) { return this.nodes[selector] || (this.nodes[selector] = new Node()); }
        querySelectorAll(selector) { return this.lists && this.lists[selector] || []; }
        addEventListener(name, handler) { (this.events[name] || (this.events[name] = [])).push(handler); }
        removeEventListener(name, handler) { this.events[name] = (this.events[name] || []).filter(item => item !== handler); }
        fire(name, event = {}) { (this.events[name] || []).forEach(handler => handler(event)); }
        setAttribute() {} removeAttribute() {} append(...nodes) { this.children.push(...nodes); } replaceChildren(...nodes) { this.children = nodes; }
        getContext() { return {measureText: text => ({width: text.length * 6}), drawImage() {}}; }
        toDataURL() { return 'data:image/png;base64,AA=='; }
        getBoundingClientRect() { return {top: 0, bottom: 300, height: 300}; }
        matches() { return false; } focus() {} closest() { return modal; }
    }
    const form = new Node(), modal = new Node(), row = new Node(), selected = row.querySelector('[data-escore-select]'), handle = row.querySelector('[data-escore-drag]');
    row.dataset = {escorePiece: '7', eligible: 'true'};
    selected.closest = handle.closest = () => row;
    form.action = '/folder/pdf'; form.elements = {};
    ['title', 'subtitle', 'comment', 'bottom_text', 'color', '_token', 'page_numbers', 'composer_names', 'include_edition', 'blank_pages'].forEach(name => { form.elements[name] = new Node(); });
    form.elements.title.value = 'My book'; form.elements.color.value = '#00a2ff'; form.elements._token.value = 'session-token';
    form.lists = {'[data-escore-piece]': [row], '[data-escore-select]': [selected], '[data-escore-drag]': [handle]};
    const requests = [], renderedPages = [], timers = new Map(); let nextTimer = 0, destroyed = 0, renderGate = null;
    const document = new Node();
    Object.assign(document, {readyState: 'loading', createElement: () => new Node(), querySelector: () => null});
    const context = vm.createContext({module: {exports: {}}, document,
        window: {pdfjsLib: {GlobalWorkerOptions: {}, getDocument: () => ({promise: Promise.resolve({numPages: 3, destroy() { destroyed++; }, getPage: async number => { renderedPages.push(number); return {getViewport: ({scale}) => ({width: 612 * scale, height: 792 * scale}), render: () => { const promise = renderGate || Promise.resolve(); renderGate = null; return {promise}; }}; }})})}},
        Uint8Array, atob: text => Buffer.from(text, 'base64').toString('binary'),
        AbortController: class { constructor() { this.signal = {aborted: false}; } abort() { this.signal.aborted = true; } },
        FormData: class { constructor() { this.values = {}; } set(key, value) { this.values[key] = value; } append(key, value) { (this.values[key] || (this.values[key] = [])).push(value); } },
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({url, options, resolve, reject})),
        setTimeout: callback => { timers.set(++nextTimer, callback); return nextTimer; }, clearTimeout: id => timers.delete(id), console
    });
    vm.runInContext(fs.readFileSync('resources/js/views/escore.js', 'utf8'), context);
    context.module.exports.initWizard(form);
    const cover = form.querySelector('[data-escore-cover]');
    cover.dataset.escoreImageCover = 'true';
    form.elements.subtitle.value = 'A collection of pieces';
    form.elements.comment.value = 'for piano';
    form.elements.bottom_text.value = 'PianoLIT eScore';
    form.fire('input');
    assert.strictEqual(form.querySelector('[data-escore-preview="title"]').textContent, 'MY BOOK');
    assert.strictEqual(form.querySelector('[data-escore-preview="bottom_text"]').textContent, 'PianoLIT\neScore');
    assert(parseFloat(form.querySelector('[data-escore-preview="comment"]').style.top) < 48.4848, 'Image cover text stays above its bottom image');
    form.elements.color.value = '#111111'; form.fire('input');
    assert.strictEqual(cover.style.backgroundColor, '#111111');
    assert.strictEqual(cover.style.color, '#ffffff');
    form.elements.bottom_text.value = ''; form.fire('input');
    assert(cover.classes.has('escore-cover-preview--no-brand'), 'Blank branding hides the image-cover rules');
    cover.dataset.escoreImageCover = 'false';
    form.elements.color.value = '#00a2ff'; form.fire('input');
    assert.strictEqual(form.querySelector('[data-escore-preview="title"]').textContent, 'My book', 'Ordinary covers preserve their existing title case');
    const settle = async () => { for (let i = 0; i < 4; i++) await new Promise(resolve => setImmediate(resolve)); };
    const response = pages => ({ok: true, headers: {get: () => 'application/json'}, json: async () => ({pdf: Buffer.from('%PDF').toString('base64'), pages, sections: {cover: 1, title: 0, index: 1, edition: 0, blank: 0}, entries: [{id: 7, title: 'Solo', composer: 'Composer', pages: 1, start: 3}]})});
    modal.fire('shown.bs.modal');
    const previewPage = form.querySelector('[data-escore-main-page]');
    const loader = form.querySelector('[data-escore-loader]');
    assert(previewPage.classes.has('escore-page--pending'), 'Initial HTML cover stays concealed');
    assert.strictEqual(loader.hidden, false, 'Opening shows the initial preview loader');
    assert(!previewPage.classes.has('escore-page--updating'), 'Opening the modal does not fade its initial preview');
    assert.strictEqual(requests.length, 1);
    assert.strictEqual(requests[0].options.method, 'POST');
    assert.strictEqual(requests[0].options.headers['X-CSRF-TOKEN'], 'session-token');
    assert.deepStrictEqual(Array.from(requests[0].options.body.values['piece_ids[]']), ['7']);
    form.fire('input', {target: form.elements.title});
    assert(!previewPage.classes.has('escore-page--updating'), 'Initial edits keep the placeholder concealed instead of fading it');
    assert(requests[0].options.signal.aborted, 'Editing cancels the previous preview');
    form.querySelector('[data-escore-retry]').fire('click');
    let finishInitialRender;
    renderGate = new Promise(resolve => { finishInitialRender = resolve; });
    requests[1].resolve(response(3)); await settle();
    assert(previewPage.classes.has('escore-page--pending'), 'Receiving the PDF does not expose unfinished text');
    assert.strictEqual(loader.hidden, false, 'Loader remains visible until the first canvas is rendered');
    finishInitialRender(); await settle();
    assert(!previewPage.classes.has('escore-page--pending'), 'Only the completed PDF is revealed');
    assert.strictEqual(loader.hidden, true, 'Completed rendering removes the loader');
    assert(!previewPage.classes.has('escore-page--updating'), 'A completed preview restores full opacity');
    assert.strictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview ready · 3 pages');
    renderedPages.length = 0;
    form.querySelector('[data-escore-next]').fire('click'); await settle();
    assert.deepStrictEqual(renderedPages, [2], 'Step two uses one main preview for the contents page');
    renderedPages.length = 0;
    form.querySelector('[data-escore-next-page]').fire('click'); await settle();
    assert.deepStrictEqual(renderedPages, [3], 'Step two pager navigates the same main preview');
    form.querySelector('[data-escore-back]').fire('click'); await settle();
    requests[0].resolve(response(99)); await settle();
    assert.strictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview ready · 3 pages', 'An older response cannot replace the latest PDF');
    const mainCanvas = form.querySelector('[data-escore-main-canvas]');
    const thumbnails = form.querySelector('[data-escore-thumbnails]');
    const previousThumbnails = thumbnails.children;
    const previousAspect = form.querySelector('[data-escore-main-page]').style.aspectRatio;
    assert.strictEqual(previousThumbnails.length, 3, 'All thumbnails are committed together');
    form.fire('input', {target: form.elements.title});
    assert.strictEqual(mainCanvas.hidden, false, 'Typing keeps the rendered PDF visible');
    assert(previewPage.classes.has('escore-page--updating'), 'Typing fades the retained PDF during the debounce');
    assert.strictEqual(loader.hidden, true, 'Later edits preserve the page without the initial loader');
    assert.strictEqual(cover.hidden, true, 'Typing never switches back to different HTML text metrics');
    assert.strictEqual(form.querySelector('[data-escore-main-page]').style.aspectRatio, previousAspect, 'Typing retains the paper geometry');
    assert.strictEqual(thumbnails.children, previousThumbnails, 'Typing keeps completed thumbnails in place');
    form.querySelector('[data-escore-retry]').fire('click');
    requests[2].reject(new Error('Preview failed')); await settle();
    assert(!previewPage.classes.has('escore-page--updating'), 'Failure clears the updating fade');
    assert.strictEqual(form.querySelector('[data-escore-retry]').hidden, false, 'Failure exposes retry');
    assert.strictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview failed');
    assert.strictEqual(mainCanvas.hidden, false, 'Request failure preserves the last rendered page');
    assert.strictEqual(cover.hidden, true);
    assert.strictEqual(thumbnails.children, previousThumbnails, 'Request failure preserves completed thumbnails');
    let finishRender;
    renderGate = new Promise(resolve => { finishRender = resolve; });
    form.querySelector('[data-escore-retry]').fire('click');
    requests[3].resolve(response(3)); await settle();
    assert.strictEqual(mainCanvas.hidden, false, 'A pending PDF render preserves the last visible canvas');
    assert.strictEqual(cover.hidden, true, 'Even during slow PDF rendering the HTML cover stays hidden');
    assert.strictEqual(thumbnails.children, previousThumbnails, 'Pending rendering preserves the thumbnail grid');
    finishRender(); await settle();
    assert.strictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview ready · 3 pages');
    assert.strictEqual(thumbnails.children.length, 3, 'Completed replacement contains all thumbnails');
    assert.notStrictEqual(thumbnails.children, previousThumbnails, 'Ready thumbnails replace the old set together');
    form.querySelector('[data-escore-retry]').fire('click');
    modal.fire('hidden.bs.modal');
    assert(!previewPage.classes.has('escore-page--updating'), 'Closing clears any updating fade');
    assert(requests[4].options.signal.aborted, 'Closing cancels the current request');
    assert(destroyed > 0, 'Closing releases the loaded PDF');
    requests[4].resolve(response(88)); await settle();
    assert.notStrictEqual(form.querySelector('[data-escore-status]').textContent, 'Preview ready · 88 pages');
    assert.strictEqual(timers.size, 0, 'Closing clears scheduled previews');
    selected.checked = false;
    modal.fire('shown.bs.modal');
    assert(!previewPage.classes.has('escore-page--updating'), 'Reopening never retains a stale updating fade');
    assert.strictEqual(requests.length, 5, 'Empty selections never submit a preview');
    assert.strictEqual(form.querySelector('[data-escore-next]').disabled, true, 'Empty selections cannot advance or download');
    const second = new Node(), third = new Node(), rows = form.lists['[data-escore-piece]'];
    second.dataset = {escorePiece: '8', eligible: 'true'}; third.dataset = {escorePiece: '9', eligible: 'true'};
    rows.push(second, third); selected.checked = true;
    for (const item of rows) {
        item.closest = () => item;
        item.getBoundingClientRect = () => ({top: rows.indexOf(item) * 50, height: 50});
        item.before = moving => { rows.splice(rows.indexOf(moving), 1); rows.splice(rows.indexOf(item), 0, moving); };
        item.after = moving => { rows.splice(rows.indexOf(moving), 1); rows.splice(rows.indexOf(item) + 1, 0, moving); };
    }
    form.contains = item => rows.includes(item);
    document.elementFromPoint = () => third;
    const event = {button: 0, pointerId: 1, preventDefault() {}};
    handle.fire('pointerdown', event);
    document.fire('pointermove', {pointerId: 2, clientX: 0, clientY: 140});
    assert.deepStrictEqual(rows.map(item => item.dataset.escorePiece), ['7', '8', '9'], 'Other pointers cannot move this drag');
    document.fire('pointermove', {pointerId: 1, clientX: 0, clientY: 140});
    document.fire('pointerup', {pointerId: 1});
    assert.deepStrictEqual(rows.map(item => item.dataset.escorePiece), ['8', '9', '7'], 'Drop moves the piece without number inputs');
    assert(!row.classes.has('dragging'), 'Drop clears the drag state');
    assert.strictEqual(document.events.pointermove.length, 0, 'Drop removes pointer listeners');
    form.querySelector('[data-escore-retry]').fire('click');
    assert.deepStrictEqual(Array.from(requests[5].options.body.values['piece_ids[]']), ['8', '9', '7'], 'Preview submits the dropped order');
    handle.fire('pointerdown', event); document.fire('pointercancel', {pointerId: 1});
    assert(!row.classes.has('dragging'), 'Cancellation clears the drag state');
    handle.fire('pointerdown', event); modal.fire('hidden.bs.modal');
    assert(!row.classes.has('dragging'), 'Closing clears an active drag');
    const coldForm = new Node(), coldModal = new Node();
    coldForm.closest = () => coldModal; coldForm.elements = form.elements; coldForm.action = form.action;
    coldForm.lists = {'[data-escore-piece]': [row]};
    context.module.exports.initWizard(coldForm);
    coldModal.fire('show.bs.modal');
    const coldPage = coldForm.querySelector('[data-escore-main-page]'), coldLoader = coldForm.querySelector('[data-escore-loader]');
    assert(coldPage.classes.has('escore-page--pending'), 'Placeholder is concealed before the modal opening transition');
    assert.strictEqual(coldLoader.hidden, false);
    coldModal.fire('shown.bs.modal');
    requests[requests.length - 1].reject(new Error('Initial preview failed')); await settle();
    assert(coldPage.classes.has('escore-page--pending'), 'Initial failure never reveals the HTML placeholder');
    assert.strictEqual(coldLoader.hidden, true, 'Initial failure stops the loader');
    assert.strictEqual(coldForm.querySelector('[data-escore-retry]').hidden, false);
    coldForm.querySelector('[data-escore-retry]').fire('click');
    assert.strictEqual(coldLoader.hidden, false, 'Retry resumes the initial loader');
    coldModal.fire('hidden.bs.modal');
    requests[requests.length - 1].resolve(response(3)); await settle();
    assert.strictEqual(coldLoader.hidden, true, 'Closing stops the loader');
    assert(coldPage.classes.has('escore-page--pending'), 'A response after closing cannot reveal the page');
    console.log('Passed: eScore image-cover text/color/branding, drag/drop export order without number inputs, pointer cleanup, session POST, stable typing and delayed renders, atomic thumbnails, preview races, failures/retry and modal cleanup.');
};
